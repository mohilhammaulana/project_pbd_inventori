<?php

require_once __DIR__ . '/../../auth/cek_role.php';
require_once __DIR__ . '/../../config/koneksi.php';

cek_role(['Admin', 'Gudang']);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Membuat token keamanan
if (empty($_SESSION['csrf_penerimaan'])) {
    $_SESSION['csrf_penerimaan'] = bin2hex(random_bytes(32));
}

// Mengamankan tampilan data
function pn_e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Format mata uang
function pn_rupiah($nilai): string
{
    return 'Rp ' . number_format($nilai, 0, ',', '.');
}

// Redirect halaman
function pn_redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

// Menyimpan pesan notifikasi
function pn_flash(string $tipe, string $pesan): void
{
    $_SESSION['flash_penerimaan'] = [
        'tipe' => $tipe,
        'pesan' => $pesan
    ];
}

// Menampilkan pesan notifikasi
function pn_tampil_flash(): void
{
    if (isset($_SESSION['flash_penerimaan'])) {

        $flash = $_SESSION['flash_penerimaan'];

        $class = $flash['tipe'] === 'success'
            ? 'alert-success'
            : 'alert-error';

        echo '<div class="' . $class . '">';
        echo pn_e($flash['pesan']);
        echo '</div>';

        unset($_SESSION['flash_penerimaan']);
    }
}

// Validasi metode POST dan token keamanan
function pn_validasi_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

        http_response_code(405);
        exit('Metode tidak diizinkan.');
    }

    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals(
            $_SESSION['csrf_penerimaan'],
            $token
        )
    ) {
        http_response_code(403);
        exit('Token keamanan tidak valid.');
    }
}

// Membersihkan hasil Stored Procedure
function pn_bersihkan_procedure(mysqli $koneksi): void
{
    while ($koneksi->more_results()) {

        $koneksi->next_result();

        if ($result = $koneksi->store_result()) {
            $result->free();
        }
    }
}

// Mengambil informasi pengadaan
function pn_ambil_pengadaan(
    mysqli $koneksi,
    int $idpengadaan
): ?array
{
    $stmt = $koneksi->prepare(
        "SELECT *
         FROM v_pengadaan
         WHERE idpengadaan = ?"
    );

    $stmt->bind_param('i', $idpengadaan);
    $stmt->execute();

    $data = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    return $data ?: null;
}

// Mengambil progress penerimaan barang
function pn_ambil_progress(
    mysqli $koneksi,
    int $idpengadaan
): array
{
    $stmt = $koneksi->prepare(
        "SELECT *
         FROM v_progress_pengadaan
         WHERE idpengadaan = ?
         ORDER BY idbarang ASC"
    );

    $stmt->bind_param('i', $idpengadaan);
    $stmt->execute();

    $data = $stmt->get_result()->fetch_all(
        MYSQLI_ASSOC
    );

    $stmt->close();

    return $data;
}

// Mengambil informasi penerimaan
function pn_ambil_penerimaan(
    mysqli $koneksi,
    int $idpenerimaan
): ?array
{
    $stmt = $koneksi->prepare(
        "SELECT *
         FROM v_penerimaan
         WHERE idpenerimaan = ?"
    );

    $stmt->bind_param('i', $idpenerimaan);
    $stmt->execute();

    $data = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    return $data ?: null;
}

// Mengunci pengadaan selama transaksi
function pn_kunci_pengadaan(
    mysqli $koneksi,
    int $idpengadaan
): void
{
    $stmt = $koneksi->prepare(
        "SELECT status
         FROM pengadaan
         WHERE idpengadaan = ?
         FOR UPDATE"
    );

    $stmt->bind_param('i', $idpengadaan);
    $stmt->execute();

    $data = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (!$data || $data['status'] !== 'P') {

        throw new RuntimeException(
            'Pengadaan tidak tersedia atau sudah selesai.'
        );
    }
}
<?php

require_once __DIR__ . '/../../auth/cek_role.php';
require_once __DIR__ . '/../../config/koneksi.php';

cek_role(['Admin', 'Gudang']);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (empty($_SESSION['csrf_retur'])) {
    $_SESSION['csrf_retur'] = bin2hex(random_bytes(32));
}

// Mengamankan tampilan
function rt_e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Format rupiah
function rt_rupiah($nilai): string
{
    return 'Rp ' . number_format($nilai, 0, ',', '.');
}

// Redirect halaman
function rt_redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

// Menyimpan notifikasi
function rt_flash(string $tipe, string $pesan): void
{
    $_SESSION['flash_retur'] = [
        'tipe' => $tipe,
        'pesan' => $pesan
    ];
}

// Menampilkan notifikasi
function rt_tampil_flash(): void
{
    if (isset($_SESSION['flash_retur'])) {

        $flash = $_SESSION['flash_retur'];

        $class = $flash['tipe'] === 'success'
            ? 'alert-success'
            : 'alert-error';

        echo '<div class="' . $class . '">';
        echo rt_e($flash['pesan']);
        echo '</div>';

        unset($_SESSION['flash_retur']);
    }
}

// Validasi metode POST dan CSRF
function rt_validasi_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

        http_response_code(405);
        exit('Metode tidak diizinkan.');
    }

    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_retur'], $token)
    ) {

        http_response_code(403);
        exit('Token keamanan tidak valid.');
    }
}

// Membersihkan hasil Stored Procedure
function rt_bersihkan_procedure(mysqli $koneksi): void
{
    while ($koneksi->more_results()) {

        $koneksi->next_result();

        if ($result = $koneksi->store_result()) {
            $result->free();
        }
    }
}

// Mengambil informasi penerimaan
function rt_ambil_penerimaan(
    mysqli $koneksi,
    int $id
): ?array
{
    $stmt = $koneksi->prepare(
        "SELECT *
         FROM v_penerimaan
         WHERE idpenerimaan = ?"
    );

    $stmt->bind_param('i', $id);
    $stmt->execute();

    $data = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    return $data ?: null;
}

// Mengambil progress retur
function rt_ambil_progress(
    mysqli $koneksi,
    int $id
): array
{
    $stmt = $koneksi->prepare(
        "SELECT *
         FROM v_progress_retur
         WHERE idpenerimaan = ?
         ORDER BY idbarang ASC"
    );

    $stmt->bind_param('i', $id);
    $stmt->execute();

    $data = $stmt->get_result()->fetch_all(
        MYSQLI_ASSOC
    );

    $stmt->close();

    return $data;
}

// Mengambil informasi retur
function rt_ambil_retur(
    mysqli $koneksi,
    int $id
): ?array
{
    $stmt = $koneksi->prepare(
        "SELECT *
         FROM v_retur
         WHERE idretur = ?"
    );

    $stmt->bind_param('i', $id);
    $stmt->execute();

    $data = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    return $data ?: null;
}

// Mengunci penerimaan
function rt_kunci_penerimaan(
    mysqli $koneksi,
    int $id
): void
{
    $stmt = $koneksi->prepare(
        "SELECT status
         FROM penerimaan
         WHERE idpenerimaan = ?
         FOR UPDATE"
    );

    $stmt->bind_param('i', $id);
    $stmt->execute();

    $data = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (!$data || $data['status'] !== 'S') {

        throw new RuntimeException(
            'Penerimaan tidak ditemukan atau belum selesai.'
        );
    }
}

// Mengunci barang untuk menjaga konsistensi stok
function rt_kunci_barang(
    mysqli $koneksi,
    int $id
): void
{
    $stmt = $koneksi->prepare(
        "SELECT idbarang
         FROM barang
         WHERE idbarang = ?
         FOR UPDATE"
    );

    $stmt->bind_param('i', $id);
    $stmt->execute();

    $data = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (!$data) {

        throw new RuntimeException(
            'Barang tidak ditemukan.'
        );
    }
}
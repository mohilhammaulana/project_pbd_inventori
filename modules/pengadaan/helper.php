<?php

require_once __DIR__ . '/../../auth/cek_role.php';
require_once __DIR__ . '/../../config/koneksi.php';

cek_role(['Admin', 'Gudang']);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (empty($_SESSION['csrf_pengadaan'])) {
    $_SESSION['csrf_pengadaan'] = bin2hex(random_bytes(32));
}

function pg_e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function pg_rupiah($nilai): string
{
    return 'Rp ' . number_format($nilai, 0, ',', '.');
}

function pg_redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function pg_flash(string $tipe, string $pesan): void
{
    $_SESSION['flash_pengadaan'] = [
        'tipe' => $tipe,
        'pesan' => $pesan
    ];
}

function pg_tampil_flash(): void
{
    if (isset($_SESSION['flash_pengadaan'])) {

        $flash = $_SESSION['flash_pengadaan'];

        $class = $flash['tipe'] === 'success'
            ? 'alert-success'
            : 'alert-error';

        echo '<div class="' . $class . '">';
        echo pg_e($flash['pesan']);
        echo '</div>';

        unset($_SESSION['flash_pengadaan']);
    }
}

function pg_validasi_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Metode tidak diizinkan.');
    }

    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals(
            $_SESSION['csrf_pengadaan'],
            $token
        )
    ) {
        http_response_code(403);
        exit('Token keamanan tidak valid.');
    }
}

function pg_ambil_pengadaan(
    mysqli $koneksi,
    int $id
): ?array
{
    $stmt = $koneksi->prepare(
        "SELECT *
         FROM v_pengadaan
         WHERE idpengadaan = ?"
    );

    $stmt->bind_param('i', $id);
    $stmt->execute();

    $data = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    return $data ?: null;
}

// Memeriksa apakah penerimaan sudah dibuat
function pg_sudah_penerimaan(
    mysqli $koneksi,
    int $id
): bool
{
    $stmt = $koneksi->prepare(
        "SELECT idpenerimaan
         FROM penerimaan
         WHERE idpengadaan = ?
         LIMIT 1"
    );

    $stmt->bind_param('i', $id);
    $stmt->execute();

    $ada = $stmt->get_result()->num_rows > 0;

    $stmt->close();

    return $ada;
}

// Mengunci pengadaan sebelum menambah
// atau menghapus detail barang
function pg_kunci_pengadaan(
    mysqli $koneksi,
    int $id
): void
{
    $stmt = $koneksi->prepare(
        "SELECT status
         FROM pengadaan
         WHERE idpengadaan = ?
         FOR UPDATE"
    );

    $stmt->bind_param('i', $id);
    $stmt->execute();

    $data = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (!$data || $data['status'] !== 'P') {

        throw new RuntimeException(
            'Pengadaan tidak tersedia atau sudah selesai.'
        );
    }

    if (pg_sudah_penerimaan($koneksi, $id)) {

        throw new RuntimeException(
            'Pesanan tidak dapat diubah karena penerimaan sudah dimulai.'
        );
    }
}

// Mengambil progress penerimaan pengadaan
function pg_ambil_progress(
    mysqli $koneksi,
    int $id
): array
{
    $stmt = $koneksi->prepare(
        "SELECT *
         FROM v_progress_pengadaan
         WHERE idpengadaan = ?
         ORDER BY iddetail_pengadaan ASC"
    );

    $stmt->bind_param('i', $id);
    $stmt->execute();

    $data = $stmt->get_result()->fetch_all(
        MYSQLI_ASSOC
    );

    $stmt->close();

    return $data;
}

// Menghitung ulang total setelah detail dihapus
function pg_hitung_total(
    mysqli $koneksi,
    int $id
): void
{
    $stmt = $koneksi->prepare(
        "SELECT COALESCE(SUM(sub_total), 0) AS subtotal
         FROM detail_pengadaan
         WHERE idpengadaan = ?"
    );

    $stmt->bind_param('i', $id);
    $stmt->execute();

    $data = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    $subtotal = (int) $data['subtotal'];

    $stmt = $koneksi->prepare(
        "UPDATE pengadaan
         SET subtotal_nilai = ?,
             ppn = fn_ppn(?, 11),
             total_nilai = ? + fn_ppn(?, 11)
         WHERE idpengadaan = ?"
    );

    $stmt->bind_param(
        'iiiii',
        $subtotal,
        $subtotal,
        $subtotal,
        $subtotal,
        $id
    );

    $stmt->execute();
    $stmt->close();
}
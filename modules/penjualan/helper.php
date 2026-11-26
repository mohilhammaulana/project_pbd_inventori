<?php

require_once __DIR__ . '/../../auth/cek_role.php';
require_once __DIR__ . '/../../config/koneksi.php';

cek_role(['Admin', 'Kasir']);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Membuat token keamanan
if (empty($_SESSION['csrf_penjualan'])) {

    $_SESSION['csrf_penjualan'] =
        bin2hex(random_bytes(32));
}

// Mengamankan tampilan data
function pj_e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Format mata uang
function pj_rupiah($nilai): string
{
    return 'Rp ' . number_format(
        $nilai,
        0,
        ',',
        '.'
    );
}

// Redirect halaman
function pj_redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

// Menyimpan pesan notifikasi
function pj_flash(string $tipe, string $pesan): void
{
    $_SESSION['flash_penjualan'] = [
        'tipe' => $tipe,
        'pesan' => $pesan
    ];
}

// Menampilkan pesan notifikasi
function pj_tampil_flash(): void
{
    if (isset($_SESSION['flash_penjualan'])) {

        $flash = $_SESSION['flash_penjualan'];

        $class = $flash['tipe'] === 'success'
            ? 'alert-success'
            : 'alert-error';

        echo '<div class="' . $class . '">';
        echo pj_e($flash['pesan']);
        echo '</div>';

        unset($_SESSION['flash_penjualan']);
    }
}

// Validasi metode POST dan token CSRF
function pj_validasi_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

        http_response_code(405);

        exit('Metode tidak diizinkan.');
    }

    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals(
            $_SESSION['csrf_penjualan'],
            $token
        )
    ) {

        http_response_code(403);

        exit('Token keamanan tidak valid.');
    }
}

// Membersihkan hasil Stored Procedure
function pj_bersihkan_procedure(
    mysqli $koneksi
): void
{
    while ($koneksi->more_results()) {

        $koneksi->next_result();

        if ($result = $koneksi->store_result()) {

            $result->free();
        }
    }
}

// Mengambil margin penjualan aktif
function pj_ambil_margin(
    mysqli $koneksi
): ?array
{
    $result = $koneksi->query(
        "SELECT
            idmargin_penjualan,
            persen
         FROM margin_penjualan
         WHERE status = 1
         ORDER BY idmargin_penjualan DESC
         LIMIT 1"
    );

    $data = $result->fetch_assoc();

    $result->free();

    return $data ?: null;
}

// Mengambil barang yang tersedia untuk dijual
function pj_ambil_barang(
    mysqli $koneksi,
    float $margin
): array
{
    $stmt = $koneksi->prepare(
        "SELECT
            vb.idbarang,
            vb.nama,
            vb.nama_satuan,
            vb.harga,
            vb.stok,
            fn_harga_jual(
                vb.harga,
                ?
            ) AS harga_jual
         FROM v_barang vb
         JOIN satuan s
             ON vb.idsatuan = s.idsatuan
         WHERE vb.status = 1
         AND s.status = 1
         AND vb.stok > 0
         ORDER BY vb.nama ASC"
    );

    $stmt->bind_param('d', $margin);

    $stmt->execute();

    $data = $stmt->get_result()->fetch_all(
        MYSQLI_ASSOC
    );

    $stmt->close();

    return $data;
}

// Mengambil informasi transaksi penjualan
function pj_ambil_penjualan(
    mysqli $koneksi,
    int $idpenjualan
): ?array
{
    $stmt = $koneksi->prepare(
        "SELECT *
         FROM v_penjualan
         WHERE idpenjualan = ?"
    );

    $stmt->bind_param('i', $idpenjualan);

    $stmt->execute();

    $data = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    return $data ?: null;
}

// Mengunci dan memvalidasi barang
// selama transaksi penjualan
function pj_kunci_barang(
    mysqli $koneksi,
    int $idbarang
): array
{
    $stmt = $koneksi->prepare(
        "SELECT
            b.idbarang,
            b.nama,
            b.harga,
            b.status AS status_barang,
            s.status AS status_satuan
         FROM barang b
         JOIN satuan s
             ON b.idsatuan = s.idsatuan
         WHERE b.idbarang = ?
         FOR UPDATE"
    );

    $stmt->bind_param('i', $idbarang);

    $stmt->execute();

    $barang = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (!$barang) {

        throw new RuntimeException(
            'Barang tidak ditemukan.'
        );
    }

    if (
        (int) $barang['status_barang'] !== 1 ||
        (int) $barang['status_satuan'] !== 1
    ) {

        throw new RuntimeException(
            'Barang atau satuan tidak aktif.'
        );
    }

    // Mengambil stok setelah barang dikunci
    $stmt = $koneksi->prepare(
        "SELECT fn_stok_barang(?) AS stok"
    );

    $stmt->bind_param('i', $idbarang);

    $stmt->execute();

    $data_stok =
        $stmt->get_result()->fetch_assoc();

    $stmt->close();

    $barang['stok'] =
        (int) $data_stok['stok'];

    return $barang;
}
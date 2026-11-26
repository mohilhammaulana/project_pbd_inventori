<?php

require_once __DIR__ . '/../../auth/cek_role.php';
require_once __DIR__ . '/../../config/koneksi.php';

cek_role(['Admin', 'Gudang']);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);
    exit('Metode tidak diizinkan.');
}

// Validasi token keamanan
$token = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_aksi_barang']) ||
    !is_string($token) ||
    !hash_equals(
        $_SESSION['csrf_aksi_barang'],
        $token
    )
) {

    http_response_code(403);
    exit('Token keamanan tidak valid.');
}

// Mengambil ID dan jenis tindakan
$idbarang = filter_input(
    INPUT_POST,
    'idbarang',
    FILTER_VALIDATE_INT
);

$aksi = $_POST['aksi'] ?? '';

if (
    !$idbarang ||
    $idbarang < 1 ||
    !in_array(
        $aksi,
        ['nonaktifkan', 'aktifkan', 'hapus_permanen'],
        true
    )
) {

    $_SESSION['error'] = 'Data atau tindakan tidak valid.';

    header('Location: index.php');
    exit;
}

// Hapus permanen khusus Admin
if ($aksi === 'hapus_permanen') {
    cek_role(['Admin']);
}

try {

    // SOFT DELETE
    if ($aksi === 'nonaktifkan') {

        $stmt = $koneksi->prepare(
            "UPDATE barang
             SET status = 0,
                 nonaktif_karena_satuan = 0
             WHERE idbarang = ?"
        );

        $stmt->bind_param('i', $idbarang);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {

            $_SESSION['success'] =
                'Barang berhasil dinonaktifkan.';

        } else {

            $_SESSION['error'] =
                'Barang sudah nonaktif atau tidak ditemukan.';
        }

        $stmt->close();
    }

    // MENGAKTIFKAN KEMBALI
    elseif ($aksi === 'aktifkan') {

        $stmt = $koneksi->prepare(
            "UPDATE barang b
             JOIN satuan s
                ON b.idsatuan = s.idsatuan
             SET b.status = 1,
                 b.nonaktif_karena_satuan = 0
             WHERE b.idbarang = ?
             AND s.status = 1
             AND b.status = 0"
        );

        $stmt->bind_param('i', $idbarang);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {

            $_SESSION['success'] =
                'Barang berhasil diaktifkan.';

        } else {

            $_SESSION['error'] =
                'Barang tidak dapat diaktifkan. Periksa status barang dan satuannya.';
        }

        $stmt->close();
    }

    // HARD DELETE BERSYARAT
    elseif ($aksi === 'hapus_permanen') {

        $stmt = $koneksi->prepare(
            "DELETE FROM barang
             WHERE idbarang = ?

             AND fn_stok_barang(idbarang) = 0

             AND NOT EXISTS (
                SELECT 1
                FROM detail_pengadaan dp
                WHERE dp.idbarang = ?
             )

             AND NOT EXISTS (
                SELECT 1
                FROM detail_penerimaan dp
                WHERE dp.barang_idbarang = ?
             )

             AND NOT EXISTS (
                SELECT 1
                FROM detail_penjualan dp
                WHERE dp.idbarang = ?
             )

             AND NOT EXISTS (
                SELECT 1
                FROM kartu_stok k
                WHERE k.idbarang = ?
             )"
        );

        $stmt->bind_param(
            'iiiii',
            $idbarang,
            $idbarang,
            $idbarang,
            $idbarang,
            $idbarang
        );

        $stmt->execute();

        if ($stmt->affected_rows > 0) {

            $_SESSION['success'] =
                'Barang berhasil dihapus permanen.';

        } else {

            $_SESSION['error'] =
                'Barang tidak dapat dihapus permanen karena memiliki stok, riwayat transaksi, atau tidak ditemukan.';
        }

        $stmt->close();
    }

} catch (mysqli_sql_exception $e) {

    error_log($e->getMessage());

    if ($e->getCode() == 1451) {

        $_SESSION['error'] =
            'Barang masih digunakan oleh data lain dan tidak dapat dihapus.';

    } elseif ($e->getCode() == 1644) {

        $_SESSION['error'] =
            'Perubahan ditolak oleh aturan database. Periksa status satuan.';

    } else {

        $_SESSION['error'] =
            'Tindakan terhadap barang gagal dilakukan.';
    }
}

header('Location: index.php');
exit;
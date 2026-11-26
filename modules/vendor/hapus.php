<?php

require_once __DIR__ . '/../../auth/cek_role.php';
require_once __DIR__ . '/../../config/koneksi.php';

cek_role(['Admin', 'Gudang']);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Hanya menerima metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);
    exit('Metode tidak diizinkan.');
}

// Validasi CSRF
$token = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_aksi_vendor']) ||
    !is_string($token) ||
    !hash_equals(
        $_SESSION['csrf_aksi_vendor'],
        $token
    )
) {

    http_response_code(403);
    exit('Token keamanan tidak valid.');
}

// Mengambil data dari form
$idvendor = filter_input(
    INPUT_POST,
    'idvendor',
    FILTER_VALIDATE_INT
);

$aksi = $_POST['aksi'] ?? '';

// Validasi ID dan tindakan
if (
    !$idvendor ||
    $idvendor < 1 ||
    !in_array(
        $aksi,
        ['nonaktifkan', 'aktifkan', 'hapus_permanen'],
        true
    )
) {

    $_SESSION['error'] =
        'Data atau tindakan tidak valid.';

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
            "UPDATE vendor
             SET status = 'N'
             WHERE idvendor = ?
             AND status = 'A'"
        );

        $stmt->bind_param('i', $idvendor);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {

            $_SESSION['success'] =
                'Vendor berhasil dinonaktifkan.';

        } else {

            $_SESSION['error'] =
                'Vendor sudah nonaktif atau tidak ditemukan.';
        }

        $stmt->close();
    }

    // AKTIFKAN KEMBALI
    elseif ($aksi === 'aktifkan') {

        $stmt = $koneksi->prepare(
            "UPDATE vendor
             SET status = 'A'
             WHERE idvendor = ?
             AND status = 'N'"
        );

        $stmt->bind_param('i', $idvendor);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {

            $_SESSION['success'] =
                'Vendor berhasil diaktifkan.';

        } else {

            $_SESSION['error'] =
                'Vendor sudah aktif atau tidak ditemukan.';
        }

        $stmt->close();
    }

    // HARD DELETE BERSYARAT
    elseif ($aksi === 'hapus_permanen') {

        $stmt = $koneksi->prepare(
            "DELETE FROM vendor
             WHERE idvendor = ?

             AND NOT EXISTS (
                SELECT 1
                FROM pengadaan p
                WHERE p.vendor_idvendor = ?
             )"
        );

        $stmt->bind_param(
            'ii',
            $idvendor,
            $idvendor
        );

        $stmt->execute();

        if ($stmt->affected_rows > 0) {

            $_SESSION['success'] =
                'Vendor berhasil dihapus permanen.';

        } else {

            $_SESSION['error'] =
                'Vendor tidak dapat dihapus permanen karena memiliki riwayat pengadaan atau tidak ditemukan.';
        }

        $stmt->close();
    }

} catch (mysqli_sql_exception $e) {

    error_log($e->getMessage());

    if ($e->getCode() == 1451) {

        $_SESSION['error'] =
            'Vendor masih digunakan oleh transaksi lain dan tidak dapat dihapus.';

    } else {

        $_SESSION['error'] =
            'Tindakan terhadap vendor gagal dilakukan.';
    }
}

header('Location: index.php');
exit;
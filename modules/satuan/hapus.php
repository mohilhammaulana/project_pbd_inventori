<?php

require_once __DIR__ . '/../../auth/cek_role.php';
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/helper.php';

cek_role(['Admin', 'Gudang']);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Validasi metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);
    exit('Metode tidak diizinkan.');
}

// Validasi token keamanan
$token = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_aksi_satuan']) ||
    !is_string($token) ||
    !hash_equals(
        $_SESSION['csrf_aksi_satuan'],
        $token
    )
) {

    http_response_code(403);
    exit('Token keamanan tidak valid.');
}

// Mengambil ID satuan
$idsatuan = filter_input(
    INPUT_POST,
    'idsatuan',
    FILTER_VALIDATE_INT
);

$aksi = $_POST['aksi'] ?? '';

// Validasi data
if (
    !$idsatuan ||
    $idsatuan < 1 ||
    !in_array(
        $aksi,
        ['nonaktifkan', 'aktifkan', 'hapus_permanen'],
        true
    )
) {

    $_SESSION['error'] =
        'Data atau tindakan tidak valid.';

    redirect_satuan();
}

// Hapus permanen khusus Admin
if ($aksi === 'hapus_permanen') {

    cek_role(['Admin']);
}

try {

    // SOFT DELETE
    if ($aksi === 'nonaktifkan') {

        $stmt = $koneksi->prepare(
            "UPDATE satuan
             SET status = 0
             WHERE idsatuan = ?
             AND status = 1"
        );

        $stmt->bind_param('i', $idsatuan);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {

            $_SESSION['success'] =
                'Satuan berhasil dinonaktifkan. Barang aktif yang menggunakan satuan tersebut ikut dinonaktifkan.';

        } else {

            $_SESSION['error'] =
                'Satuan sudah nonaktif atau tidak ditemukan.';
        }

        $stmt->close();
    }

    // AKTIFKAN KEMBALI
    elseif ($aksi === 'aktifkan') {

        $stmt = $koneksi->prepare(
            "UPDATE satuan
             SET status = 1
             WHERE idsatuan = ?
             AND status = 0"
        );

        $stmt->bind_param('i', $idsatuan);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {

            $_SESSION['success'] =
                'Satuan berhasil diaktifkan. Barang yang sebelumnya nonaktif akibat satuan ikut diaktifkan kembali.';

        } else {

            $_SESSION['error'] =
                'Satuan sudah aktif atau tidak ditemukan.';
        }

        $stmt->close();
    }

    // HARD DELETE BERSYARAT
    elseif ($aksi === 'hapus_permanen') {

        $stmt = $koneksi->prepare(
            "DELETE FROM satuan
             WHERE idsatuan = ?

             AND NOT EXISTS (
                 SELECT 1
                 FROM barang b
                 WHERE b.idsatuan = ?
             )"
        );

        $stmt->bind_param(
            'ii',
            $idsatuan,
            $idsatuan
        );

        $stmt->execute();

        if ($stmt->affected_rows > 0) {

            $_SESSION['success'] =
                'Satuan berhasil dihapus permanen.';

        } else {

            $_SESSION['error'] =
                'Satuan tidak dapat dihapus permanen karena masih digunakan oleh barang atau tidak ditemukan.';
        }

        $stmt->close();
    }

} catch (mysqli_sql_exception $e) {

    error_log($e->getMessage());

    if ($e->getCode() == 1451) {

        $_SESSION['error'] =
            'Satuan masih digunakan oleh data barang.';

    } else {

        $_SESSION['error'] =
            'Tindakan terhadap satuan gagal dilakukan.';
    }
}

redirect_satuan();
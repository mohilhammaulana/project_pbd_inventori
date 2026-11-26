<?php

require_once __DIR__ . '/../../auth/cek_role.php';
require_once __DIR__ . '/../../config/koneksi.php';

cek_role(['Admin']);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Validasi metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode tidak diizinkan.');
}

// Validasi CSRF
$token = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_aksi_user']) ||
    !is_string($token) ||
    !hash_equals($_SESSION['csrf_aksi_user'], $token)
) {
    http_response_code(403);
    exit('Token keamanan tidak valid.');
}

$iduser = filter_input(
    INPUT_POST,
    'iduser',
    FILTER_VALIDATE_INT
);

$aksi = $_POST['aksi'] ?? '';

if (
    !$iduser ||
    $iduser < 1 ||
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

// Melindungi akun sendiri
if ($iduser === (int) $_SESSION['iduser']) {

    $_SESSION['error'] =
        'Tidak dapat melakukan tindakan terhadap akun sendiri.';

    header('Location: index.php');
    exit;
}

try {

    // Mengambil data user
    $stmt = $koneksi->prepare(
        "SELECT iduser, username, nama_role, status
         FROM v_user
         WHERE iduser = ?"
    );

    $stmt->bind_param('i', $iduser);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (!$user) {

        throw new RuntimeException(
            'User tidak ditemukan.'
        );
    }

    // Melindungi akun Admin
    if ($user['nama_role'] === 'Admin') {

        throw new RuntimeException(
            'Akun Admin dilindungi oleh sistem.'
        );
    }

    // SOFT DELETE
    if ($aksi === 'nonaktifkan') {

        $stmt = $koneksi->prepare(
            "UPDATE `user`
             SET status = 0
             WHERE iduser = ?
             AND status = 1"
        );

        $stmt->bind_param('i', $iduser);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {

            $_SESSION['success'] =
                'User berhasil dinonaktifkan.';

        } else {

            $_SESSION['error'] =
                'User sudah nonaktif atau tidak ditemukan.';
        }

        $stmt->close();
    }

    // AKTIFKAN KEMBALI
    elseif ($aksi === 'aktifkan') {

        $stmt = $koneksi->prepare(
            "UPDATE `user`
             SET status = 1
             WHERE iduser = ?
             AND status = 0"
        );

        $stmt->bind_param('i', $iduser);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {

            $_SESSION['success'] =
                'User berhasil diaktifkan.';

        } else {

            $_SESSION['error'] =
                'User sudah aktif atau tidak ditemukan.';
        }

        $stmt->close();
    }

    // HARD DELETE BERSYARAT
    elseif ($aksi === 'hapus_permanen') {

        $stmt = $koneksi->prepare(
            "DELETE FROM `user`
             WHERE iduser = ?

             AND NOT EXISTS (
                SELECT 1
                FROM pengadaan
                WHERE user_iduser = ?
             )

             AND NOT EXISTS (
                SELECT 1
                FROM penerimaan
                WHERE iduser = ?
             )

             AND NOT EXISTS (
                SELECT 1
                FROM penjualan
                WHERE iduser = ?
             )

             AND NOT EXISTS (
                SELECT 1
                FROM retur
                WHERE iduser = ?
             )

             AND NOT EXISTS (
                SELECT 1
                FROM margin_penjualan
                WHERE iduser = ?
             )"
        );

        $stmt->bind_param(
            'iiiiii',
            $iduser,
            $iduser,
            $iduser,
            $iduser,
            $iduser,
            $iduser
        );

        $stmt->execute();

        if ($stmt->affected_rows > 0) {

            $_SESSION['success'] =
                'User berhasil dihapus permanen.';

        } else {

            $_SESSION['error'] =
                'User tidak dapat dihapus karena memiliki riwayat transaksi atau tidak ditemukan.';
        }

        $stmt->close();
    }

} catch (mysqli_sql_exception $e) {

    error_log($e->getMessage());

    if ($e->getCode() == 1451) {

        $_SESSION['error'] =
            'User masih digunakan oleh data lain.';

    } elseif ($e->getCode() == 1644) {

        $_SESSION['error'] =
            'Tindakan ditolak oleh aturan database.';

    } else {

        $_SESSION['error'] =
            'Tindakan terhadap user gagal dilakukan.';
    }

} catch (RuntimeException $e) {

    $_SESSION['error'] = $e->getMessage();
}

header('Location: index.php');
exit;
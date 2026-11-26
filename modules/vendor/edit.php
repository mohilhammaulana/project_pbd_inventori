<?php

require_once __DIR__ . '/../../auth/cek_role.php';
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/helper.php';

cek_role(['Admin', 'Gudang']);

$page_title = 'Edit Vendor';
$current_page = 'vendor';

$idvendor = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$idvendor || $idvendor < 1) {

    $_SESSION['error'] = "ID vendor tidak valid.";

    redirect_vendor();
}

$stmt = $koneksi->prepare(
    "SELECT *
     FROM v_vendor
     WHERE idvendor = ?"
);

$stmt->bind_param("i", $idvendor);
$stmt->execute();

$data = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$data) {

    $_SESSION['error'] = "Vendor tidak ditemukan.";

    redirect_vendor();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    cek_csrf();

    $data['nama_vendor'] =
        trim($_POST['nama_vendor'] ?? '');

    $data['badan_hukum'] =
        $_POST['badan_hukum'] ?? '';

    $data['status'] =
        $_POST['status'] ?? '';

    $errors = validasi_vendor($data);

    if (empty($errors)) {

        try {

            $stmt = $koneksi->prepare(
                "UPDATE vendor
                 SET nama_vendor = ?,
                     badan_hukum = ?,
                     status = ?
                 WHERE idvendor = ?"
            );

            $stmt->bind_param(
                "sssi",
                $data['nama_vendor'],
                $data['badan_hukum'],
                $data['status'],
                $idvendor
            );

            $stmt->execute();
            $stmt->close();

            $_SESSION['success'] =
                "Data vendor berhasil diperbarui.";

            redirect_vendor();

        } catch (mysqli_sql_exception $e) {

            error_log($e->getMessage());

            $errors[] = "Data vendor gagal diperbarui.";
        }
    }
}

require __DIR__ . '/form.php';
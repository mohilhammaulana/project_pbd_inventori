<?php

require_once __DIR__ . '/../../auth/cek_role.php';
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/helper.php';

cek_role(['Admin', 'Gudang']);

$page_title = 'Edit Satuan';
$current_page = 'satuan';

$idsatuan = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$idsatuan || $idsatuan < 1) {

    $_SESSION['error'] = "ID satuan tidak valid.";

    redirect_satuan();
}

$stmt = $koneksi->prepare(
    "SELECT *
     FROM v_satuan
     WHERE idsatuan = ?"
);

$stmt->bind_param("i", $idsatuan);
$stmt->execute();

$data = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$data) {

    $_SESSION['error'] = "Data satuan tidak ditemukan.";

    redirect_satuan();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    cek_csrf();

    $data['nama_satuan'] =
        trim($_POST['nama_satuan'] ?? '');

    $data['status'] =
        $_POST['status'] ?? '';

    $errors = validasi_satuan(
        $koneksi,
        $data,
        $idsatuan
    );

    if (empty($errors)) {

        try {

            $status = (int) $data['status'];

            $stmt = $koneksi->prepare(
                "UPDATE satuan
                 SET nama_satuan = ?,
                     status = ?
                 WHERE idsatuan = ?"
            );

            $stmt->bind_param(
                "sii",
                $data['nama_satuan'],
                $status,
                $idsatuan
            );

            $stmt->execute();
            $stmt->close();

            $_SESSION['success'] =
                "Data satuan berhasil diperbarui.";

            redirect_satuan();

        } catch (mysqli_sql_exception $e) {

            error_log($e->getMessage());

            if ($e->getCode() == 1062) {

                $errors[] =
                    "Nama satuan sudah digunakan.";

            } else {

                $errors[] =
                    "Data satuan gagal diperbarui.";
            }
        }
    }
}

require __DIR__ . '/form.php';
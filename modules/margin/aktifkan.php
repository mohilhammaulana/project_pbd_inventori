<?php

require_once __DIR__ . '/../../auth/cek_role.php';
require_once __DIR__ . '/../../config/koneksi.php';

cek_role(['Admin']);

require_once __DIR__ . '/helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);
    exit("Metode tidak diizinkan.");
}

cek_csrf_margin();

$idmargin = filter_input(
    INPUT_POST,
    'idmargin',
    FILTER_VALIDATE_INT
);

if (!$idmargin || $idmargin < 1) {

    $_SESSION['error'] =
        "ID margin tidak valid.";

    redirect_margin();
}

try {

    $stmt = $koneksi->prepare(
        "CALL sp_aktifkan_margin(?)"
    );

    $stmt->bind_param("i", $idmargin);
    $stmt->execute();
    $stmt->close();

    bersihkan_hasil_procedure($koneksi);

    $_SESSION['success'] =
        "Margin penjualan berhasil diaktifkan.";

} catch (mysqli_sql_exception $e) {

    error_log($e->getMessage());

    $_SESSION['error'] =
        "Margin penjualan gagal diaktifkan.";
}

redirect_margin();
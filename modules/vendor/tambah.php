<?php

require_once __DIR__ . '/../../auth/cek_role.php';
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/helper.php';

cek_role(['Admin', 'Gudang']);

$page_title = 'Tambah Vendor';
$current_page = 'vendor';

$errors = [];

$data = [
    'nama_vendor' => '',
    'badan_hukum' => 'N',
    'status' => 'A'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    cek_csrf();

    $data = [
        'nama_vendor' => trim($_POST['nama_vendor'] ?? ''),
        'badan_hukum' => $_POST['badan_hukum'] ?? '',
        'status' => $_POST['status'] ?? ''
    ];

    $errors = validasi_vendor($data);

    if (empty($errors)) {

        try {

            $stmt = $koneksi->prepare(
                "INSERT INTO vendor
                (nama_vendor, badan_hukum, status)
                VALUES (?, ?, ?)"
            );

            $stmt->bind_param(
                "sss",
                $data['nama_vendor'],
                $data['badan_hukum'],
                $data['status']
            );

            $stmt->execute();
            $stmt->close();

            $_SESSION['success'] =
                "Data vendor berhasil ditambahkan.";

            redirect_vendor();

        } catch (mysqli_sql_exception $e) {

            error_log($e->getMessage());

            $errors[] = "Data vendor gagal ditambahkan.";
        }
    }
}

require __DIR__ . '/form.php';
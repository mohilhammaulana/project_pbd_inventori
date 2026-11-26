<?php

require_once __DIR__ . '/../../auth/cek_role.php';
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/helper.php';

cek_role(['Admin', 'Gudang']);

$page_title = 'Tambah Satuan';
$current_page = 'satuan';

$errors = [];

$data = [
    'nama_satuan' => '',
    'status' => 1
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    cek_csrf();

    $data = [
        'nama_satuan' => trim($_POST['nama_satuan'] ?? ''),
        'status' => $_POST['status'] ?? ''
    ];

    $errors = validasi_satuan($koneksi, $data);

    if (empty($errors)) {

        try {

            $status = (int) $data['status'];

            $stmt = $koneksi->prepare(
                "INSERT INTO satuan
                (nama_satuan, status)
                VALUES (?, ?)"
            );

            $stmt->bind_param(
                "si",
                $data['nama_satuan'],
                $status
            );

            $stmt->execute();
            $stmt->close();

            $_SESSION['success'] =
                "Data satuan berhasil ditambahkan.";

            redirect_satuan();

        } catch (mysqli_sql_exception $e) {

            error_log($e->getMessage());

            if ($e->getCode() == 1062) {

                $errors[] = "Nama satuan sudah digunakan.";

            } else {

                $errors[] = "Data satuan gagal ditambahkan.";
            }
        }
    }
}

require __DIR__ . '/form.php';
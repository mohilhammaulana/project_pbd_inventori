<?php

require_once __DIR__ . '/../../auth/cek_role.php';
require_once __DIR__ . '/../../config/koneksi.php';

cek_role(['Admin']);

require_once __DIR__ . '/helper.php';

$page_title = 'Tambah User';
$current_page = 'user';

$is_edit = false;
$errors = [];
$roles = ambil_role($koneksi);

$data = [
    'username' => '',
    'idrole' => 0
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    cek_csrf_user();

    $data = [
        'username' => trim($_POST['username'] ?? ''),
        'idrole' => (int) ($_POST['idrole'] ?? 0)
    ];

    $password = $_POST['password'] ?? '';

    $errors = validasi_user(
        $koneksi,
        $data,
        $password
    );

    if (empty($errors)) {

        try {

            $hash = password_hash(
                $password,
                PASSWORD_BCRYPT
            );

            $stmt = $koneksi->prepare(
                "INSERT INTO `user`
                 (username, password, idrole)
                 VALUES (?, ?, ?)"
            );

            $stmt->bind_param(
                "ssi",
                $data['username'],
                $hash,
                $data['idrole']
            );

            $stmt->execute();
            $stmt->close();

            $_SESSION['success'] =
                "User berhasil ditambahkan.";

            redirect_user();

        } catch (mysqli_sql_exception $e) {

            error_log($e->getMessage());

            $errors[] = $e->getCode() == 1062
                ? "Username sudah digunakan."
                : "User gagal ditambahkan.";
        }
    }
}

require __DIR__ . '/form.php';
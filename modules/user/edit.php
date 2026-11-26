<?php

require_once __DIR__ . '/../../auth/cek_role.php';
require_once __DIR__ . '/../../config/koneksi.php';

cek_role(['Admin']);

require_once __DIR__ . '/helper.php';

$page_title = 'Edit User';
$current_page = 'user';

$is_edit = true;
$errors = [];
$roles = ambil_role($koneksi);

$id_login = id_user_login();

$iduser = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$iduser || $iduser < 1) {

    $_SESSION['error'] = "ID user tidak valid.";

    redirect_user();
}

$stmt = $koneksi->prepare(
    "SELECT *
     FROM v_user
     WHERE iduser = ?"
);

$stmt->bind_param("i", $iduser);
$stmt->execute();

$data = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$data) {

    $_SESSION['error'] = "User tidak ditemukan.";

    redirect_user();
}

$role_lama = (int) $data['idrole'];
$nama_role_lama = $data['nama_role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    cek_csrf_user();

    $data['username'] =
        trim($_POST['username'] ?? '');

    $data['idrole'] =
        (int) ($_POST['idrole'] ?? 0);

    $password = $_POST['password'] ?? '';

    $errors = validasi_user(
        $koneksi,
        $data,
        $password,
        $iduser
    );

    if (
        ($iduser == $id_login ||
         $nama_role_lama === 'Admin') &&
        $data['idrole'] !== $role_lama
    ) {

        $errors[] =
            "Role akun Admin tidak boleh diubah.";
    }

    if (empty($errors)) {

        try {

            if ($password !== '') {

                $hash = password_hash(
                    $password,
                    PASSWORD_BCRYPT
                );

                $stmt = $koneksi->prepare(
                    "UPDATE `user`
                     SET username = ?,
                         password = ?,
                         idrole = ?
                     WHERE iduser = ?"
                );

                $stmt->bind_param(
                    "ssii",
                    $data['username'],
                    $hash,
                    $data['idrole'],
                    $iduser
                );

            } else {

                $stmt = $koneksi->prepare(
                    "UPDATE `user`
                     SET username = ?,
                         idrole = ?
                     WHERE iduser = ?"
                );

                $stmt->bind_param(
                    "sii",
                    $data['username'],
                    $data['idrole'],
                    $iduser
                );
            }

            $stmt->execute();
            $stmt->close();

            if ($iduser == $id_login) {
                $_SESSION['username'] = $data['username'];
            }

            $_SESSION['success'] =
                "User berhasil diperbarui.";

            redirect_user();

        } catch (mysqli_sql_exception $e) {

            error_log($e->getMessage());

            $errors[] = $e->getCode() == 1062
                ? "Username sudah digunakan."
                : "User gagal diperbarui.";
        }
    }
}

require __DIR__ . '/form.php';
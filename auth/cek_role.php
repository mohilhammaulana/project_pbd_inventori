<?php

require_once __DIR__ . '/cek_login.php';
require_once __DIR__ . '/../config/koneksi.php';

// =========================================
// MEMERIKSA HAK AKSES ROLE
// =========================================

function punya_akses(array $roles): bool
{
    $role = $_SESSION['nama_role'] ?? '';

    return in_array($role, $roles, true);
}

// =========================================
// VALIDASI AKUN DAN HAK AKSES
// =========================================

function cek_role(array $roles): void
{
    global $koneksi;

    $iduser = (int) ($_SESSION['iduser'] ?? 0);

    // Memastikan akun masih tersedia dan aktif

    $stmt = $koneksi->prepare(
        "SELECT
            iduser,
            username,
            idrole,
            nama_role,
            status

         FROM v_user

         WHERE iduser = ?
         AND status = 1

         LIMIT 1"
    );

    $stmt->bind_param("i", $iduser);

    $stmt->execute();

    $user_aktif = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    // Jika akun sudah nonaktif atau tidak tersedia

    if (!$user_aktif) {

        // Menghapus seluruh informasi login

        $_SESSION = [];

        session_regenerate_id(true);

        // Membuat token keamanan baru

        $_SESSION['csrf_token'] =
            bin2hex(random_bytes(32));

        // Menampilkan pesan pada halaman login

        $_SESSION['error'] =
            "Akun tidak aktif atau tidak tersedia. "
            . "Silakan hubungi Admin.";

        header("Location: /stokflow/auth/login.php");
        exit;
    }

    // Memperbarui informasi session

    $_SESSION['iduser'] =
        $user_aktif['iduser'];

    $_SESSION['username'] =
        $user_aktif['username'];

    $_SESSION['idrole'] =
        $user_aktif['idrole'];

    $_SESSION['nama_role'] =
        $user_aktif['nama_role'];

    // Memastikan role memiliki akses halaman

    if (!punya_akses($roles)) {

        header("Location: /stokflow/403.php");
        exit;
    }
}
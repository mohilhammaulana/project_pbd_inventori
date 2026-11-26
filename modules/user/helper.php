<?php

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function redirect_user(): void
{
    header("Location: /stokflow/modules/user/index.php");
    exit;
}

function cek_csrf_user(): void
{
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(403);
        exit("Permintaan tidak valid.");
    }
}

function id_user_login(): int
{
    $id = (int) ($_SESSION['iduser'] ?? 0);

    if ($id < 1) {
        http_response_code(403);
        exit("Session user tidak valid.");
    }

    return $id;
}

function ambil_role(mysqli $koneksi): array
{
    $result = $koneksi->query(
        "SELECT idrole, nama_role
         FROM role
         ORDER BY idrole ASC"
    );

    return $result->fetch_all(MYSQLI_ASSOC);
}

function validasi_user(
    mysqli $koneksi,
    array $data,
    string $password,
    int $iduser = 0
): array
{
    $errors = [];

    if (
        !preg_match(
            '/^[a-zA-Z0-9_.-]{3,45}$/',
            $data['username']
        )
    ) {
        $errors[] =
            "Username harus 3-45 karakter tanpa spasi.";
    }

    if ($iduser === 0 || $password !== '') {

        if (strlen($password) < 8 ||
            strlen($password) > 72) {

            $errors[] =
                "Password harus 8-72 byte.";
        }
    }

    $stmt = $koneksi->prepare(
        "SELECT idrole
         FROM role
         WHERE idrole = ?"
    );

    $stmt->bind_param("i", $data['idrole']);
    $stmt->execute();

    if ($stmt->get_result()->num_rows === 0) {
        $errors[] = "Role tidak valid.";
    }

    $stmt->close();

    $stmt = $koneksi->prepare(
        "SELECT iduser
         FROM `user`
         WHERE username = ?
         AND iduser != ?"
    );

    $stmt->bind_param(
        "si",
        $data['username'],
        $iduser
    );

    $stmt->execute();

    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Username sudah digunakan.";
    }

    $stmt->close();

    return $errors;
}
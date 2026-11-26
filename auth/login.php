<?php

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/koneksi.php';

if (isset($_SESSION['iduser'])) {

    header("Location: /stokflow/dashboard.php");
    exit;
}

$error = $_SESSION['error'] ?? '';

unset($_SESSION['error']);

$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = is_string($_POST['username'] ?? null)
        ? trim($_POST['username'])
        : '';

    $password = is_string($_POST['password'] ?? null)
        ? $_POST['password']
        : '';

    $token = $_POST['csrf_token'] ?? '';

    // Validasi CSRF

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {

        $error = "Permintaan tidak valid. Silakan coba lagi.";

    } elseif ($username === '' || $password === '') {

        $error = "Username dan password wajib diisi.";

    } else {

        // Mengambil akun pengguna yang masih aktif

        $sql = "SELECT
                    u.iduser,
                    u.username,
                    u.password,
                    u.idrole,
                    r.nama_role

                FROM `user` u

                JOIN role r
                    ON u.idrole = r.idrole

                WHERE u.username = ?
                AND u.status = 1

                LIMIT 1";

        $stmt = $koneksi->prepare($sql);

        $stmt->bind_param("s", $username);

        $stmt->execute();

        $result = $stmt->get_result();

        $user = $result->fetch_assoc();

        // Validasi password pengguna

        if (
            $user &&
            password_verify(
                $password,
                $user['password']
            )
        ) {

            session_regenerate_id(true);

            $_SESSION['iduser'] = $user['iduser'];

            $_SESSION['username'] = $user['username'];

            $_SESSION['idrole'] = $user['idrole'];

            $_SESSION['nama_role'] = $user['nama_role'];

            $_SESSION['csrf_token'] =
                bin2hex(random_bytes(32));

            $stmt->close();

            header("Location: /stokflow/dashboard.php");
            exit;

        } else {

            $error =
                "Login gagal. Username atau password salah, "
                . "atau akun tidak aktif.";

        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Login - STOKFLOW</title>

    <link
        rel="stylesheet"
        href="../assets/css/login.css"
    >

</head>

<body>

    <div class="login-container">

        <div class="brand-icon">
            SF
        </div>

        <h1>STOKFLOW</h1>

        <p class="subtitle">
            Sistem Informasi Inventori Barang
        </p>

        <?php if ($error !== '') : ?>

            <div class="alert">

                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>

            </div>

        <?php endif; ?>

        <form method="POST" action="">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $_SESSION['csrf_token'],
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>"
            >

            <div class="form-group">

                <label for="username">
                    Username
                </label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Masukkan username"
                    value="<?= htmlspecialchars(
                        $username,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>"
                    autocomplete="username"
                    required
                >

            </div>

            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Masukkan password"
                    autocomplete="current-password"
                    required
                >

            </div>

            <button
                type="submit"
                class="btn-login"
            >
                Masuk
            </button>

        </form>

        <div class="footer">

            &copy; <?= date('Y'); ?> STOKFLOW

        </div>

    </div>

</body>

</html>
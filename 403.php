<?php

require_once __DIR__ . '/auth/cek_login.php';

http_response_code(403);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Akses Ditolak - STOKFLOW</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .error-box {
            background: white;
            padding: 40px;
            border-radius: 16px;
            text-align: center;
            max-width: 500px;
            box-shadow: 0 10px 30px #00000010;
        }

        h1 {
            font-size: 80px;
            color: #dc2626;
            margin: 0;
        }

        h2 {
            color: #0f172a;
        }

        p {
            color: #64748b;
            line-height: 1.6;
        }

        a {
            display: inline-block;
            margin-top: 15px;
            padding: 12px 20px;
            background: #2563eb;
            color: white;
            border-radius: 8px;
            text-decoration: none;
        }
    </style>
</head>

<body>

    <div class="error-box">

        <h1>403</h1>

        <h2>Akses Ditolak</h2>

        <p>
            Anda tidak memiliki hak akses
            untuk membuka halaman ini.
        </p>

        <p>
            Role Anda:
            <strong>
                <?= htmlspecialchars($_SESSION['nama_role']); ?>
            </strong>
        </p>

        <a href="/stokflow/dashboard.php">
            Kembali ke Dashboard
        </a>

    </div>

</body>
</html>
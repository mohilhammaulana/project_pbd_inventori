<?php

require_once __DIR__ . '/../auth/cek_login.php';

$page_title = $page_title ?? 'Dashboard';

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($page_title); ?> - STOKFLOW
    </title>

    <link rel="stylesheet"
          href="/stokflow/assets/css/style.css">

</head>

<body>

<div class="app-layout">

    <?php require __DIR__ . '/sidebar.php'; ?>

    <div class="main-content">

        <header class="topbar">

            <div class="topbar-left">

                <button
                    type="button"
                    class="menu-toggle"
                    id="menuToggle"
                    aria-label="Buka atau tutup menu"
                >
                    &#9776;
                </button>

                <h2>
                    <?= htmlspecialchars($page_title); ?>
                </h2>

            </div>

            <div class="profile">

                <div class="avatar">
                    <?= htmlspecialchars(
                        strtoupper(
                            substr($_SESSION['username'], 0, 1)
                        )
                    ); ?>
                </div>

                <div class="profile-info">

                    <strong>
                        <?= htmlspecialchars(
                            $_SESSION['username']
                        ); ?>
                    </strong>

                    <small>
                        <?= htmlspecialchars(
                            $_SESSION['nama_role']
                        ); ?>
                    </small>

                </div>

            </div>

        </header>

        <main class="content">
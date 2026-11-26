<?php

require_once __DIR__ . '/../config/session.php';

if (!isset($_SESSION['iduser'])) {

    $_SESSION['error'] = "Silakan login terlebih dahulu.";

    header("Location: /stokflow/auth/login.php");
    exit;
}

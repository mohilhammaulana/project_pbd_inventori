<?php

function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function redirect_vendor(): void
{
    header("Location: /stokflow/modules/vendor/index.php");
    exit;
}

function cek_csrf(): void
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

function validasi_vendor(array $data): array
{
    $errors = [];

    if (
        $data['nama_vendor'] === '' ||
        mb_strlen($data['nama_vendor']) > 100
    ) {
        $errors[] = "Nama vendor wajib diisi, maksimal 100 karakter.";
    }

    if (!in_array($data['badan_hukum'], ['Y', 'N'], true)) {
        $errors[] = "Badan hukum tidak valid.";
    }

    if (!in_array($data['status'], ['A', 'N'], true)) {
        $errors[] = "Status vendor tidak valid.";
    }

    return $errors;
}
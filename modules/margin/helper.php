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

function redirect_margin(): void
{
    header("Location: /stokflow/modules/margin/index.php");
    exit;
}

function cek_csrf_margin(): void
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

function rupiah($nilai): string
{
    return 'Rp ' . number_format(
        $nilai,
        0,
        ',',
        '.'
    );
}

function bersihkan_hasil_procedure(
    mysqli $koneksi
): void
{
    while ($koneksi->more_results()) {

        if (!$koneksi->next_result()) {
            break;
        }

        $result = $koneksi->store_result();

        if ($result) {
            $result->free();
        }
    }
}
<?php

function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function redirect_satuan(): void
{
    header("Location: /stokflow/modules/satuan/index.php");
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

function validasi_satuan(
    mysqli $koneksi,
    array $data,
    int $idsatuan = 0
): array
{
    $errors = [];

    if (
        $data['nama_satuan'] === '' ||
        mb_strlen($data['nama_satuan']) > 45
    ) {
        $errors[] =
            "Nama satuan wajib diisi, maksimal 45 karakter.";
    }

    if (
        !in_array(
            (string) $data['status'],
            ['0', '1'],
            true
        )
    ) {
        $errors[] = "Status satuan tidak valid.";
    }

    if (empty($errors)) {

        $stmt = $koneksi->prepare(
            "SELECT idsatuan
             FROM satuan
             WHERE nama_satuan = ?
             AND idsatuan != ?"
        );

        $stmt->bind_param(
            "si",
            $data['nama_satuan'],
            $idsatuan
        );

        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {

            $errors[] = "Nama satuan sudah digunakan.";
        }

        $stmt->close();
    }

    return $errors;
}
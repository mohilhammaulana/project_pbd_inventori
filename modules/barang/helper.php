<?php

function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function redirect_barang(): void
{
    header("Location: /stokflow/modules/barang/index.php");
    exit;
}

function cek_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';

    if (
        empty($_SESSION['csrf_token']) ||
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {

        http_response_code(403);
        exit("Permintaan tidak valid.");
    }
}

function bersihkan_hasil_procedure(mysqli $koneksi): void
{
    while ($koneksi->more_results()) {

        $koneksi->next_result();

        $result = $koneksi->store_result();

        if ($result instanceof mysqli_result) {

            $result->free();

        }
    }
}

// =========================================
// VALIDASI DATA BARANG
// =========================================

function validasi_barang(
    mysqli $koneksi,
    array $data,
    bool $edit = false,
    int $idsatuan_lama = 0
): array
{
    $errors = [];

    // Validasi jenis barang

    if (!in_array($data['jenis'], ['M', 'N'], true)) {

        $errors[] = "Jenis barang tidak valid.";
    }

    // Validasi nama barang

    if (
        !is_string($data['nama']) ||
        trim($data['nama']) === '' ||
        mb_strlen($data['nama']) > 45
    ) {

        $errors[] =
            "Nama barang wajib diisi, maksimal 45 karakter.";
    }

    // Validasi harga barang

    if (
        filter_var(
            $data['harga'],
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                    'max_range' => 2147483647
                ]
            ]
        ) === false
    ) {

        $errors[] =
            "Harga harus berupa bilangan bulat lebih dari 0.";
    }

    // Validasi status barang

    $status = $edit
        ? (string) $data['status']
        : '1';

    if (!in_array($status, ['0', '1'], true)) {

        $errors[] = "Status barang tidak valid.";
    }

    // Validasi ID satuan

    $idsatuan = filter_var(
        $data['idsatuan'],
        FILTER_VALIDATE_INT,
        [
            'options' => [
                'min_range' => 1
            ]
        ]
    );

    if ($idsatuan === false) {

        $errors[] = "Pilih satuan barang yang valid.";

        return $errors;
    }

    // Mengambil status satuan

    $stmt = $koneksi->prepare(
        "SELECT idsatuan, status
         FROM satuan
         WHERE idsatuan = ?"
    );

    $stmt->bind_param("i", $idsatuan);

    $stmt->execute();

    $satuan = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (!$satuan) {

        $errors[] = "Satuan barang tidak ditemukan.";

    } elseif ((int) $satuan['status'] === 0) {

        // Barang baru wajib menggunakan satuan aktif

        if (!$edit) {

            $errors[] =
                "Barang baru harus menggunakan satuan aktif.";

        // Barang aktif wajib menggunakan satuan aktif

        } elseif ($status === '1') {

            $errors[] =
                "Barang tidak dapat diaktifkan karena satuannya nonaktif.";

        // Satuan nonaktif hanya boleh dipertahankan

        } elseif ($idsatuan !== $idsatuan_lama) {

            $errors[] =
                "Tidak boleh mengganti satuan barang ke satuan nonaktif.";

        }
    }

    return $errors;
}
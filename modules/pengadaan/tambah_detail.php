<?php

require_once __DIR__ . '/helper.php';

pg_validasi_post();

$idpengadaan = (int) ($_POST['idpengadaan'] ?? 0);
$idbarang = (int) ($_POST['idbarang'] ?? 0);

$harga = filter_var(
    $_POST['harga_satuan'] ?? '',
    FILTER_VALIDATE_INT
);

$jumlah = filter_var(
    $_POST['jumlah'] ?? '',
    FILTER_VALIDATE_INT
);

if (
    $idpengadaan <= 0 ||
    $idbarang <= 0 ||
    $harga === false ||
    $harga < 0 ||
    $harga > 100000000 ||
    $jumlah === false ||
    $jumlah <= 0 ||
    $jumlah > 1000000 ||
    $harga * $jumlah > 2147483647
) {

    pg_flash(
        'error',
        'Data barang, harga atau jumlah tidak valid.'
    );

    pg_redirect('detail.php?id=' . $idpengadaan);
}

try {

    $koneksi->begin_transaction();

    // Memastikan pesanan masih dapat diubah
    pg_kunci_pengadaan(
        $koneksi,
        $idpengadaan
    );

    // Memeriksa status barang dan satuannya
    $stmt = $koneksi->prepare(
        "SELECT b.idbarang
         FROM barang b
         JOIN satuan s
             ON b.idsatuan = s.idsatuan
         WHERE b.idbarang = ?
         AND b.status = 1
         AND s.status = 1
         FOR UPDATE"
    );

    $stmt->bind_param('i', $idbarang);
    $stmt->execute();

    $barang = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (!$barang) {

        throw new RuntimeException(
            'Barang atau satuan tidak aktif.'
        );
    }

    // Memeriksa barang duplikat
    $stmt = $koneksi->prepare(
        "SELECT iddetail_pengadaan
         FROM detail_pengadaan
         WHERE idpengadaan = ?
         AND idbarang = ?"
    );

    $stmt->bind_param(
        'ii',
        $idpengadaan,
        $idbarang
    );

    $stmt->execute();

    $duplikat = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if ($duplikat) {

        throw new RuntimeException(
            'Barang sudah ada dalam transaksi. Hapus detail lama jika ingin menggantinya.'
        );
    }

    // Menambahkan detail pengadaan
    $stmt = $koneksi->prepare(
        "INSERT INTO detail_pengadaan (
            harga_satuan,
            jumlah,
            idbarang,
            idpengadaan
        )
        VALUES (?, ?, ?, ?)"
    );

    $stmt->bind_param(
        'iiii',
        $harga,
        $jumlah,
        $idbarang,
        $idpengadaan
    );

    $stmt->execute();
    $stmt->close();

    // Subtotal, PPN dan total dihitung Trigger
    $koneksi->commit();

    pg_flash(
        'success',
        'Barang berhasil ditambahkan.'
    );

} catch (Throwable $e) {

    $koneksi->rollback();

    error_log($e->getMessage());

    $pesan = $e instanceof mysqli_sql_exception
        ? 'Gagal menambahkan barang. Periksa data dan trigger database.'
        : $e->getMessage();

    pg_flash('error', $pesan);
}

pg_redirect('detail.php?id=' . $idpengadaan);
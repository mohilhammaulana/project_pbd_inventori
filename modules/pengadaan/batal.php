<?php

require_once __DIR__ . '/helper.php';

pg_validasi_post();

// =========================================
// VALIDASI ID PENGADAAN
// =========================================

$idpengadaan = filter_var(
    $_POST['idpengadaan'] ?? '',
    FILTER_VALIDATE_INT
);

if (!$idpengadaan || $idpengadaan < 1) {

    pg_flash(
        'error',
        'ID pengadaan tidak valid.'
    );

    pg_redirect('index.php');
}

$transaksi_aktif = false;

try {

    // Menggunakan data transaksi terbaru

    $koneksi->query(
        "SET TRANSACTION ISOLATION LEVEL READ COMMITTED"
    );

    $koneksi->begin_transaction();

    $transaksi_aktif = true;

    // Mengunci pengadaan dan memastikan
    // belum memiliki transaksi penerimaan

    pg_kunci_pengadaan(
        $koneksi,
        $idpengadaan
    );

    // Mengubah status menjadi Dibatalkan

    $stmt = $koneksi->prepare(
        "UPDATE pengadaan
         SET status = 'B'
         WHERE idpengadaan = ?
         AND status = 'P'"
    );

    $stmt->bind_param(
        'i',
        $idpengadaan
    );

    $stmt->execute();

    if ($stmt->affected_rows !== 1) {

        $stmt->close();

        throw new RuntimeException(
            'Pengadaan tidak dapat dibatalkan.'
        );
    }

    $stmt->close();

    $koneksi->commit();

    $transaksi_aktif = false;

    pg_flash(
        'success',
        'Pengadaan berhasil dibatalkan. '
        . 'Riwayat pesanan tetap tersimpan.'
    );

} catch (Throwable $e) {

    if ($transaksi_aktif) {

        $koneksi->rollback();
    }

    error_log($e->getMessage());

    $pesan = $e instanceof mysqli_sql_exception
        ? 'Pengadaan gagal dibatalkan.'
        : $e->getMessage();

    pg_flash('error', $pesan);
}

pg_redirect('index.php');
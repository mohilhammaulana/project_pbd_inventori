<?php

require_once __DIR__ . '/helper.php';

pg_validasi_post();

$idpengadaan = (int) ($_POST['idpengadaan'] ?? 0);

$iddetail = (int) ($_POST['iddetail_pengadaan'] ?? 0);

if ($idpengadaan <= 0 || $iddetail <= 0) {

    pg_flash('error', 'Data detail tidak valid.');

    pg_redirect('index.php');
}

try {

    $koneksi->begin_transaction();

    // Penghapusan hanya boleh dilakukan
    // sebelum penerimaan dimulai
    pg_kunci_pengadaan(
        $koneksi,
        $idpengadaan
    );

    $stmt = $koneksi->prepare(
        "DELETE FROM detail_pengadaan
         WHERE iddetail_pengadaan = ?
         AND idpengadaan = ?"
    );

    $stmt->bind_param(
        'ii',
        $iddetail,
        $idpengadaan
    );

    $stmt->execute();

    if ($stmt->affected_rows !== 1) {

        $stmt->close();

        throw new RuntimeException(
            'Detail barang tidak ditemukan.'
        );
    }

    $stmt->close();

    // Menghitung ulang subtotal, PPN dan total
    pg_hitung_total(
        $koneksi,
        $idpengadaan
    );

    $koneksi->commit();

    pg_flash(
        'success',
        'Detail barang berhasil dihapus.'
    );

} catch (Throwable $e) {

    $koneksi->rollback();

    error_log($e->getMessage());

    $pesan = $e instanceof mysqli_sql_exception
        ? 'Detail barang gagal dihapus.'
        : $e->getMessage();

    pg_flash('error', $pesan);
}

pg_redirect('detail.php?id=' . $idpengadaan);
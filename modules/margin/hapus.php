<?php

require_once __DIR__ . '/../../auth/cek_role.php';
require_once __DIR__ . '/../../config/koneksi.php';

cek_role(['Admin']);

require_once __DIR__ . '/helper.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Validasi metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);
    exit('Metode tidak diizinkan.');
}

// Validasi CSRF
cek_csrf_margin();

// Mengambil ID margin
$idmargin = filter_input(
    INPUT_POST,
    'idmargin',
    FILTER_VALIDATE_INT
);

if (!$idmargin || $idmargin < 1) {

    $_SESSION['error'] =
        'ID margin tidak valid.';

    redirect_margin();
}

try {

    $koneksi->begin_transaction();

    // Mengambil dan mengunci margin
    $stmt = $koneksi->prepare(
        "SELECT idmargin_penjualan, status
         FROM margin_penjualan
         WHERE idmargin_penjualan = ?
         FOR UPDATE"
    );

    $stmt->bind_param('i', $idmargin);
    $stmt->execute();

    $margin = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    // Memastikan margin tersedia
    if (!$margin) {

        throw new RuntimeException(
            'Margin penjualan tidak ditemukan.'
        );
    }

    // Margin aktif tidak boleh dihapus
    if ((int) $margin['status'] === 1) {

        throw new RuntimeException(
            'Margin aktif tidak dapat dihapus. Aktifkan margin lain terlebih dahulu.'
        );
    }

    // Memeriksa riwayat penjualan
    $stmt = $koneksi->prepare(
        "SELECT COUNT(*) AS jumlah
         FROM penjualan
         WHERE idmargin_penjualan = ?"
    );

    $stmt->bind_param('i', $idmargin);
    $stmt->execute();

    $riwayat = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if ((int) $riwayat['jumlah'] > 0) {

        throw new RuntimeException(
            'Margin tidak dapat dihapus karena sudah digunakan dalam transaksi penjualan.'
        );
    }

    // HARD DELETE BERSYARAT
    $stmt = $koneksi->prepare(
        "DELETE FROM margin_penjualan
         WHERE idmargin_penjualan = ?
         AND status = 0
         AND NOT EXISTS (
             SELECT 1
             FROM penjualan p
             WHERE p.idmargin_penjualan = ?
         )"
    );

    $stmt->bind_param(
        'ii',
        $idmargin,
        $idmargin
    );

    $stmt->execute();

    $berhasil = $stmt->affected_rows;

    $stmt->close();

    if ($berhasil !== 1) {

        throw new RuntimeException(
            'Margin gagal dihapus.'
        );
    }

    $koneksi->commit();

    $_SESSION['success'] =
        'Margin penjualan berhasil dihapus permanen.';

} catch (mysqli_sql_exception $e) {

    $koneksi->rollback();

    error_log($e->getMessage());

    if ($e->getCode() == 1451) {

        $_SESSION['error'] =
            'Margin masih digunakan dalam transaksi penjualan.';

    } else {

        $_SESSION['error'] =
            'Margin penjualan gagal dihapus.';
    }

} catch (RuntimeException $e) {

    $koneksi->rollback();

    $_SESSION['error'] =
        $e->getMessage();
}

redirect_margin();
<?php

require_once __DIR__ . '/helper.php';

// Validasi metode POST dan CSRF
pn_validasi_post();

// Mengambil data dari form
$idpengadaan = (int) ($_POST['idpengadaan'] ?? 0);
$iduser = (int) ($_SESSION['iduser'] ?? 0);
$jumlah_input = $_POST['jumlah'] ?? [];

// Validasi data awal
if (
    $idpengadaan <= 0 ||
    $iduser <= 0 ||
    !is_array($jumlah_input) ||
    empty($jumlah_input)
) {

    pn_flash('error', 'Data penerimaan tidak valid.');

    pn_redirect('tambah.php');
}

// Menampung barang yang diterima
$barang_diterima = [];

foreach ($jumlah_input as $idbarang => $jumlah) {

    $idbarang = filter_var(
        $idbarang,
        FILTER_VALIDATE_INT
    );

    $jumlah = filter_var(
        $jumlah,
        FILTER_VALIDATE_INT
    );

    if (
        $idbarang === false ||
        $idbarang <= 0 ||
        $jumlah === false ||
        $jumlah < 0 ||
        $jumlah > 2147483647
    ) {

        pn_flash(
            'error',
            'Jumlah penerimaan tidak valid.'
        );

        pn_redirect(
            'tambah.php?idpengadaan=' . $idpengadaan
        );
    }

    // Barang dengan jumlah 0 tidak disimpan
    if ($jumlah > 0) {

        $barang_diterima[$idbarang] = $jumlah;
    }
}

// Minimal harus ada satu barang diterima
if (empty($barang_diterima)) {

    pn_flash(
        'error',
        'Minimal satu barang harus diterima.'
    );

    pn_redirect(
        'tambah.php?idpengadaan=' . $idpengadaan
    );
}

// Mengurutkan ID barang untuk penguncian
ksort($barang_diterima, SORT_NUMERIC);

$transaksi_aktif = false;

try {

    // Menggunakan data terbaru dalam transaksi
    $koneksi->query(
        "SET TRANSACTION ISOLATION LEVEL READ COMMITTED"
    );

    // Memulai transaksi database
    $koneksi->begin_transaction();

    $transaksi_aktif = true;

    // Mengunci pengadaan
    pn_kunci_pengadaan(
        $koneksi,
        $idpengadaan
    );

    // Memeriksa penerimaan lama yang belum selesai
    $stmt = $koneksi->prepare(
        "SELECT p.idpenerimaan
         FROM penerimaan p
         JOIN detail_penerimaan dp
             ON p.idpenerimaan = dp.idpenerimaan
         WHERE p.idpengadaan = ?
         AND p.status = 'P'
         LIMIT 1"
    );

    $stmt->bind_param('i', $idpengadaan);
    $stmt->execute();

    $penerimaan_pending =
        $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if ($penerimaan_pending) {

        throw new RuntimeException(
            'Terdapat penerimaan lama yang belum diselesaikan.'
        );
    }

    // Mengunci barang selama transaksi berlangsung
    $stmt = $koneksi->prepare(
        "SELECT idbarang
         FROM barang
         WHERE idbarang = ?
         FOR UPDATE"
    );

    foreach ($barang_diterima as $idbarang => $jumlah) {

        $stmt->bind_param('i', $idbarang);
        $stmt->execute();

        $barang = $stmt->get_result()->fetch_assoc();

        if (!$barang) {

            $stmt->close();

            throw new RuntimeException(
                'Barang tidak ditemukan.'
            );
        }
    }

    $stmt->close();

    // Mengambil progress pengadaan terbaru
    $progress = pn_ambil_progress(
        $koneksi,
        $idpengadaan
    );

    if (empty($progress)) {

        throw new RuntimeException(
            'Pengadaan belum memiliki detail barang.'
        );
    }

    // Membuat daftar barang dan sisa pesanan
    $daftar_barang = [];

    foreach ($progress as $p) {

        $daftar_barang[(int) $p['idbarang']] = $p;
    }

    // Validasi setiap barang yang diterima
    foreach ($barang_diterima as $idbarang => $jumlah) {

        if (!isset($daftar_barang[$idbarang])) {

            throw new RuntimeException(
                'Barang tidak terdapat dalam pengadaan.'
            );
        }

        $data = $daftar_barang[$idbarang];

        $sisa = (int) $data['sisa_barang'];

        if ($jumlah > $sisa) {

            throw new RuntimeException(
                'Jumlah penerimaan ' .
                $data['nama_barang'] .
                ' melebihi sisa pesanan.'
            );
        }
    }

    // Membuat header penerimaan melalui procedure
    $stmt = $koneksi->prepare(
        "CALL sp_buat_penerimaan(
            ?,
            ?,
            @idpenerimaan
        )"
    );

    $stmt->bind_param(
        'ii',
        $idpengadaan,
        $iduser
    );

    $stmt->execute();
    $stmt->close();

    pn_bersihkan_procedure($koneksi);

    // Mengambil ID penerimaan yang baru dibuat
    $hasil = $koneksi->query(
        "SELECT @idpenerimaan AS id"
    )->fetch_assoc();

    $idpenerimaan = (int) ($hasil['id'] ?? 0);

    if ($idpenerimaan <= 0) {

        throw new RuntimeException(
            'Gagal memperoleh ID penerimaan.'
        );
    }

    // Menyimpan detail penerimaan
    $stmt = $koneksi->prepare(
        "INSERT INTO detail_penerimaan (
            idpenerimaan,
            barang_idbarang,
            jumlah_terima
        )
        VALUES (?, ?, ?)"
    );

    foreach ($barang_diterima as $idbarang => $jumlah) {

        $stmt->bind_param(
            'iii',
            $idpenerimaan,
            $idbarang,
            $jumlah
        );

        $stmt->execute();
    }

    $stmt->close();

    // Menyelesaikan transaksi penerimaan
    // sekaligus memperbarui status pengadaan
    $stmt = $koneksi->prepare(
        "CALL sp_selesaikan_penerimaan(?)"
    );

    $stmt->bind_param(
        'i',
        $idpenerimaan
    );

    $stmt->execute();
    $stmt->close();

    pn_bersihkan_procedure($koneksi);

    // Mengambil status pengadaan terbaru
    $pengadaan = pn_ambil_pengadaan(
        $koneksi,
        $idpengadaan
    );

    // Menyimpan seluruh perubahan
    $koneksi->commit();

    $transaksi_aktif = false;

    // Mencegah pengiriman ulang form lama
    unset($_SESSION['csrf_penerimaan']);

    // Menampilkan pesan sesuai status pengadaan
    if (
        $pengadaan &&
        $pengadaan['status'] === 'S'
    ) {

        pn_flash(
            'success',
            'Penerimaan berhasil. Seluruh barang telah diterima dan pengadaan selesai.'
        );

    } else {

        pn_flash(
            'success',
            'Penerimaan berhasil. Stok telah bertambah dan pengadaan masih dalam proses.'
        );
    }

    pn_redirect(
        'detail.php?id=' . $idpenerimaan
    );

} catch (Throwable $e) {

    // Membatalkan seluruh perubahan jika gagal
    if ($transaksi_aktif) {

        $koneksi->rollback();
    }

    error_log($e->getMessage());

    if ($e instanceof mysqli_sql_exception) {

        $pesan =
            'Penerimaan gagal. Periksa jumlah barang, procedure dan trigger database.';

    } else {

        $pesan = $e->getMessage();
    }

    pn_flash('error', $pesan);

    pn_redirect(
        'tambah.php?idpengadaan=' . $idpengadaan
    );
}
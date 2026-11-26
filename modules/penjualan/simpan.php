<?php

require_once __DIR__ . '/helper.php';

// Validasi metode POST dan CSRF
pj_validasi_post();

$iduser = (int) ($_SESSION['iduser'] ?? 0);

$idmargin_form = filter_var(
    $_POST['idmargin'] ?? '',
    FILTER_VALIDATE_INT
);

$jumlah_input = $_POST['jumlah'] ?? [];
$harga_estimasi = $_POST['harga_estimasi'] ?? [];

// Validasi data awal
if (
    $iduser <= 0 ||
    $idmargin_form === false ||
    $idmargin_form <= 0 ||
    !is_array($jumlah_input) ||
    !is_array($harga_estimasi) ||
    empty($jumlah_input)
) {

    pj_flash(
        'error',
        'Data penjualan tidak valid.'
    );

    pj_redirect('tambah.php');
}

// Menampung barang yang akan dijual
$barang_dijual = [];

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

        pj_flash(
            'error',
            'Jumlah penjualan tidak valid.'
        );

        pj_redirect('tambah.php');
    }

    // Hanya barang dengan jumlah lebih dari 0
    if ($jumlah > 0) {

        $barang_dijual[$idbarang] = $jumlah;
    }
}

// Minimal satu barang harus dijual
if (empty($barang_dijual)) {

    pj_flash(
        'error',
        'Minimal satu barang harus dijual.'
    );

    pj_redirect('tambah.php');
}

// Mengurutkan ID barang sebelum penguncian
ksort($barang_dijual, SORT_NUMERIC);

$transaksi_aktif = false;

try {

    // Menggunakan data terbaru
    $koneksi->query(
        "SET TRANSACTION ISOLATION LEVEL READ COMMITTED"
    );

    // Memulai transaksi
    $koneksi->begin_transaction();

    $transaksi_aktif = true;

    $data_barang = [];

    // Mengunci dan memeriksa stok setiap barang
    foreach ($barang_dijual as $idbarang => $jumlah) {

        $barang = pj_kunci_barang(
            $koneksi,
            $idbarang
        );

        if ($jumlah > (int) $barang['stok']) {

            throw new RuntimeException(
                'Stok ' .
                $barang['nama'] .
                ' tidak mencukupi.'
            );
        }

        $data_barang[$idbarang] = $barang;
    }

    // Membuat header penjualan
    $stmt = $koneksi->prepare(
        "CALL sp_buat_penjualan(
            ?,
            @idpenjualan
        )"
    );

    $stmt->bind_param('i', $iduser);

    $stmt->execute();
    $stmt->close();

    pj_bersihkan_procedure($koneksi);

    // Mengambil ID penjualan baru
    $hasil = $koneksi->query(
        "SELECT @idpenjualan AS id"
    )->fetch_assoc();

    $idpenjualan = (int) ($hasil['id'] ?? 0);

    if ($idpenjualan <= 0) {

        throw new RuntimeException(
            'Gagal memperoleh ID penjualan.'
        );
    }

    // Mengambil dan mengunci margin yang digunakan
    $stmt = $koneksi->prepare(
        "SELECT
            p.idmargin_penjualan,
            m.persen,
            m.status
         FROM penjualan p
         JOIN margin_penjualan m
             ON p.idmargin_penjualan =
                m.idmargin_penjualan
         WHERE p.idpenjualan = ?
         FOR UPDATE"
    );

    $stmt->bind_param('i', $idpenjualan);
    $stmt->execute();

    $margin = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (
        !$margin ||
        (int) $margin['status'] !== 1 ||
        (int) $margin['idmargin_penjualan'] !==
            $idmargin_form
    ) {

        throw new RuntimeException(
            'Margin penjualan telah berubah. Silakan periksa kembali transaksi.'
        );
    }

    // Menghitung ulang harga jual menggunakan Function
    $persen = (float) $margin['persen'];

    $subtotal_cek = 0;

    $stmt = $koneksi->prepare(
        "SELECT fn_harga_jual(?, ?) AS harga_jual"
    );

    foreach ($barang_dijual as $idbarang => $jumlah) {

        $harga_dasar =
            (int) $data_barang[$idbarang]['harga'];

        $stmt->bind_param(
            'id',
            $harga_dasar,
            $persen
        );

        $stmt->execute();

        $hasil_harga =
            $stmt->get_result()->fetch_assoc();

        $harga_jual =
            (int) $hasil_harga['harga_jual'];

        $harga_form = filter_var(
            $harga_estimasi[$idbarang] ?? '',
            FILTER_VALIDATE_INT
        );

        // Mencegah transaksi dengan harga yang sudah berubah
        if (
            $harga_form === false ||
            $harga_form !== $harga_jual
        ) {

            $stmt->close();

            throw new RuntimeException(
                'Harga jual telah berubah. Silakan periksa kembali transaksi.'
            );
        }

        // Memeriksa batas maksimal nilai INT database
        if (
            $harga_jual < 0 ||
            $harga_jual > intdiv(2147483647, $jumlah)
        ) {

            $stmt->close();

            throw new RuntimeException(
                'Nilai penjualan barang terlalu besar.'
            );
        }

        $subtotal_cek += $harga_jual * $jumlah;

        if ($subtotal_cek > 2147483647) {

            $stmt->close();

            throw new RuntimeException(
                'Subtotal penjualan terlalu besar.'
            );
        }
    }

    $stmt->close();

    // Memeriksa PPN dan total penjualan
    $stmt = $koneksi->prepare(
        "SELECT fn_ppn(?, 11) AS ppn"
    );

    $stmt->bind_param('i', $subtotal_cek);
    $stmt->execute();

    $hasil_ppn = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    $ppn_cek = (int) $hasil_ppn['ppn'];

    if (
        $subtotal_cek + $ppn_cek > 2147483647
    ) {

        throw new RuntimeException(
            'Total penjualan melebihi batas database.'
        );
    }

    // Menyimpan detail penjualan
    $stmt = $koneksi->prepare(
        "INSERT INTO detail_penjualan (
            penjualan_idpenjualan,
            idbarang,
            jumlah
        )
        VALUES (?, ?, ?)"
    );

    foreach ($barang_dijual as $idbarang => $jumlah) {

        $stmt->bind_param(
            'iii',
            $idpenjualan,
            $idbarang,
            $jumlah
        );

        $stmt->execute();
    }

    $stmt->close();

    /*
        Trigger BEFORE INSERT:
        - Validasi barang dan stok
        - Menghitung harga jual
        - Menghitung subtotal

        Trigger AFTER INSERT:
        - Mengurangi stok
        - Menambahkan kartu stok
        - Menghitung subtotal penjualan
        - Menghitung PPN
        - Menghitung total penjualan
    */

    // Menyimpan seluruh perubahan
    $koneksi->commit();

    $transaksi_aktif = false;

    // Mencegah pengiriman ulang form yang sama
    unset($_SESSION['csrf_penjualan']);

    pj_flash(
        'success',
        'Penjualan berhasil disimpan dan stok barang telah berkurang.'
    );

    pj_redirect(
        'detail.php?id=' . $idpenjualan
    );

} catch (Throwable $e) {

    // Membatalkan seluruh transaksi jika gagal
    if ($transaksi_aktif) {

        $koneksi->rollback();
    }

    error_log($e->getMessage());

    if ($e instanceof mysqli_sql_exception) {

        $pesan =
            'Penjualan gagal. Periksa stok, margin, procedure dan trigger database.';

    } else {

        $pesan = $e->getMessage();
    }

    pj_flash('error', $pesan);

    pj_redirect('tambah.php');
}
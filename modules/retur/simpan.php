<?php

require_once __DIR__ . '/helper.php';

rt_validasi_post();

$idpenerimaan = (int) ($_POST['idpenerimaan'] ?? 0);
$iduser = (int) ($_SESSION['iduser'] ?? 0);

$jumlah_input = $_POST['jumlah'] ?? [];
$alasan_input = $_POST['alasan'] ?? [];

// Validasi data awal
if (
    $idpenerimaan <= 0 ||
    $iduser <= 0 ||
    !is_array($jumlah_input) ||
    !is_array($alasan_input) ||
    empty($jumlah_input)
) {

    rt_flash('error', 'Data retur tidak valid.');

    rt_redirect('tambah.php');
}

$url_kembali =
    'tambah.php?idpenerimaan=' . $idpenerimaan;

$barang_retur = [];

// Validasi jumlah dan alasan retur
foreach ($jumlah_input as $iddetail => $jumlah) {

    $iddetail = filter_var(
        $iddetail,
        FILTER_VALIDATE_INT
    );

    $jumlah = filter_var(
        $jumlah,
        FILTER_VALIDATE_INT
    );

    if (
        $iddetail === false ||
        $iddetail <= 0 ||
        $jumlah === false ||
        $jumlah < 0 ||
        $jumlah > 2147483647
    ) {

        rt_flash(
            'error',
            'Jumlah retur tidak valid.'
        );

        rt_redirect($url_kembali);
    }

    // Jumlah 0 tidak dimasukkan ke transaksi
    if ($jumlah === 0) {

        continue;
    }

    $alasan = $alasan_input[$iddetail] ?? '';

    if (!is_string($alasan)) {

        rt_flash('error', 'Alasan retur tidak valid.');

        rt_redirect($url_kembali);
    }

    $alasan = trim($alasan);

    if (
        $alasan === '' ||
        preg_match('/^.{1,200}$/us', $alasan) !== 1
    ) {

        rt_flash(
            'error',
            'Alasan retur wajib diisi, maksimal 200 karakter.'
        );

        rt_redirect($url_kembali);
    }

    $barang_retur[$iddetail] = [
        'jumlah' => $jumlah,
        'alasan' => $alasan
    ];
}

// Minimal satu barang harus diretur
if (empty($barang_retur)) {

    rt_flash(
        'error',
        'Minimal satu barang harus diretur.'
    );

    rt_redirect($url_kembali);
}

$transaksi_aktif = false;

try {

    // Menggunakan data stok terbaru
    $koneksi->query(
        "SET TRANSACTION ISOLATION LEVEL READ COMMITTED"
    );

    $koneksi->begin_transaction();

    $transaksi_aktif = true;

    // Mengunci penerimaan agar tidak terjadi
    // retur bersamaan pada penerimaan yang sama
    rt_kunci_penerimaan(
        $koneksi,
        $idpenerimaan
    );

    // Mengambil detail penerimaan
    $progress = rt_ambil_progress(
        $koneksi,
        $idpenerimaan
    );

    $daftar_detail = [];

    foreach ($progress as $p) {

        $daftar_detail[
            (int) $p['iddetail_penerimaan']
        ] = $p;
    }

    // Memastikan barang berasal dari penerimaan
    // yang dipilih
    $idbarang_dikunci = [];

    foreach ($barang_retur as $iddetail => $data) {

        if (!isset($daftar_detail[$iddetail])) {

            throw new RuntimeException(
                'Barang tidak terdapat dalam penerimaan.'
            );
        }

        $idbarang_dikunci[] =
            (int) $daftar_detail[$iddetail]['idbarang'];
    }

    // Mengurutkan penguncian barang
    $idbarang_dikunci = array_unique(
        $idbarang_dikunci
    );

    sort($idbarang_dikunci, SORT_NUMERIC);

    // Mengunci barang untuk menjaga stok
    foreach ($idbarang_dikunci as $idbarang) {

        rt_kunci_barang(
            $koneksi,
            $idbarang
        );
    }

    // Mengambil progress terbaru setelah dikunci
    $progress = rt_ambil_progress(
        $koneksi,
        $idpenerimaan
    );

    $daftar_detail = [];

    foreach ($progress as $p) {

        $daftar_detail[
            (int) $p['iddetail_penerimaan']
        ] = $p;
    }

    // Validasi sisa retur dan stok
    $jumlah_per_barang = [];

    foreach ($barang_retur as $iddetail => $data) {

        if (!isset($daftar_detail[$iddetail])) {

            throw new RuntimeException(
                'Detail penerimaan tidak ditemukan.'
            );
        }

        $detail = $daftar_detail[$iddetail];

        $jumlah = $data['jumlah'];

        $idbarang = (int) $detail['idbarang'];

        $sisa_retur = (int) $detail['sisa_retur'];

        $stok = (int) $detail['stok'];

        if ($jumlah > $sisa_retur) {

            throw new RuntimeException(
                'Jumlah retur ' .
                $detail['nama_barang'] .
                ' melebihi sisa penerimaan.'
            );
        }

        $jumlah_per_barang[$idbarang] =
            ($jumlah_per_barang[$idbarang] ?? 0)
            + $jumlah;

        if ($jumlah_per_barang[$idbarang] > $stok) {

            throw new RuntimeException(
                'Stok ' .
                $detail['nama_barang'] .
                ' tidak mencukupi untuk retur.'
            );
        }
    }

    // Membuat header retur menggunakan procedure
    $stmt = $koneksi->prepare(
        "CALL sp_buat_retur(
            ?,
            ?,
            @idretur
        )"
    );

    $stmt->bind_param(
        'ii',
        $idpenerimaan,
        $iduser
    );

    $stmt->execute();
    $stmt->close();

    rt_bersihkan_procedure($koneksi);

    // Mengambil ID retur baru
    $hasil = $koneksi->query(
        "SELECT @idretur AS id"
    )->fetch_assoc();

    $idretur = (int) ($hasil['id'] ?? 0);

    if ($idretur <= 0) {

        throw new RuntimeException(
            'Gagal memperoleh ID retur.'
        );
    }

    // Menyimpan detail retur
    $stmt = $koneksi->prepare(
        "INSERT INTO detail_retur (
            jumlah,
            alasan,
            idretur,
            iddetail_penerimaan
        )
        VALUES (?, ?, ?, ?)"
    );

    foreach ($barang_retur as $iddetail => $data) {

        $jumlah = $data['jumlah'];
        $alasan = $data['alasan'];

        $stmt->bind_param(
            'isii',
            $jumlah,
            $alasan,
            $idretur,
            $iddetail
        );

        $stmt->execute();
    }

    $stmt->close();

    /*
        Trigger BEFORE INSERT:
        - Validasi detail penerimaan
        - Validasi jumlah retur
        - Validasi stok

        Trigger AFTER INSERT:
        - Mengurangi stok
        - Mencatat kartu stok dengan kode R
    */

    // Menyimpan transaksi
    $koneksi->commit();

    $transaksi_aktif = false;

    // Mencegah pengiriman ulang form lama
    unset($_SESSION['csrf_retur']);

    rt_flash(
        'success',
        'Retur berhasil disimpan dan stok barang telah berkurang.'
    );

    rt_redirect(
        'detail.php?id=' . $idretur
    );

} catch (Throwable $e) {

    if ($transaksi_aktif) {

        $koneksi->rollback();
    }

    error_log($e->getMessage());

    if ($e instanceof mysqli_sql_exception) {

        $pesan =
            'Retur gagal. Periksa jumlah, stok, procedure dan trigger database.';

    } else {

        $pesan = $e->getMessage();
    }

    rt_flash('error', $pesan);

    rt_redirect($url_kembali);
}
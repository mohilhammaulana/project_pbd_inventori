<?php

require_once __DIR__ . '/helper.php';

$page_title = 'Kartu Stok';
$current_page = 'kartu_stok';

// Mengambil filter
$keyword = ks_get('keyword');
$idbarang = (int) ks_get('idbarang');
$jenis = ks_get('jenis');
$tanggal_awal = ks_get('tanggal_awal');
$tanggal_akhir = ks_get('tanggal_akhir');

$errors = [];

// Validasi jenis transaksi
if (!in_array($jenis, ['', 'M', 'J', 'R'], true)) {

    $errors[] = 'Jenis transaksi tidak valid.';
}

// Validasi tanggal awal
if (!ks_validasi_tanggal($tanggal_awal)) {

    $errors[] = 'Tanggal awal tidak valid.';
}

// Validasi tanggal akhir
if (!ks_validasi_tanggal($tanggal_akhir)) {

    $errors[] = 'Tanggal akhir tidak valid.';
}

// Validasi rentang tanggal
if (
    $tanggal_awal !== '' &&
    $tanggal_akhir !== '' &&
    $tanggal_awal > $tanggal_akhir
) {

    $errors[] =
        'Tanggal awal tidak boleh melebihi tanggal akhir.';
}

// Mengambil daftar barang
$daftar_barang = ks_ambil_barang($koneksi);

$barang_terpilih = null;

foreach ($daftar_barang as $b) {

    if ((int) $b['idbarang'] === $idbarang) {

        $barang_terpilih = $b;

        break;
    }
}

if (
    $idbarang !== 0 &&
    !$barang_terpilih
) {

    $errors[] = 'Barang tidak ditemukan.';
}

$details = [];

$total_masuk = 0;
$total_keluar = 0;

// Menjalankan query jika filter valid
if (empty($errors)) {

    $sql = "SELECT
                k.idkartu_stok,
                k.created_at,
                k.idbarang,
                b.nama AS nama_barang,
                b.nama_satuan,
                k.jenis_transaksi,
                k.masuk,
                k.keluar,
                k.stock,
                k.idtransaksi,
                dp.idpenerimaan,
                dj.penjualan_idpenjualan AS idpenjualan,
                dr.idretur

            FROM v_kartu_stok k

            JOIN v_barang b
                ON k.idbarang = b.idbarang

            LEFT JOIN detail_penerimaan dp
                ON k.jenis_transaksi = 'M'
                AND k.idtransaksi =
                    dp.iddetail_penerimaan

            LEFT JOIN detail_penjualan dj
                ON k.jenis_transaksi = 'J'
                AND k.idtransaksi =
                    dj.iddetail_penjualan

            LEFT JOIN detail_retur dr
                ON k.jenis_transaksi = 'R'
                AND k.idtransaksi =
                    dr.iddetail_retur

            WHERE b.nama LIKE ?

            AND (? = 0 OR k.idbarang = ?)

            AND (? = '' OR k.jenis_transaksi = ?)

            AND (
                ? IS NULL
                OR DATE(k.created_at) >= ?
            )

            AND (
                ? IS NULL
                OR DATE(k.created_at) <= ?
            )

            ORDER BY k.idkartu_stok DESC";

    $stmt = $koneksi->prepare($sql);

    $cari = '%' . $keyword . '%';

    $awal = $tanggal_awal !== ''
        ? $tanggal_awal
        : null;

    $akhir = $tanggal_akhir !== ''
        ? $tanggal_akhir
        : null;

    $stmt->bind_param(
        'siissssss',
        $cari,
        $idbarang,
        $idbarang,
        $jenis,
        $jenis,
        $awal,
        $awal,
        $akhir,
        $akhir
    );

    $stmt->execute();

    $details = $stmt->get_result()->fetch_all(
        MYSQLI_ASSOC
    );

    $stmt->close();

    // Menghitung pergerakan berdasarkan filter
    foreach ($details as $d) {

        $total_masuk += (int) $d['masuk'];

        $total_keluar += (int) $d['keluar'];
    }
}

require __DIR__ . '/../../includes/header.php';

?>

<div class="section-heading">

    <h2>Kartu Stok</h2>

    <p>
        Pantau riwayat barang masuk,
        barang keluar, dan saldo stok barang.
    </p>

</div>

<?php foreach ($errors as $error): ?>

    <div class="alert-error">

        <?= ks_e($error); ?>

    </div>

<?php endforeach; ?>

<!-- FILTER KARTU STOK -->

<section class="panel">

    <div class="panel-header">

        <h2>Filter Kartu Stok</h2>

    </div>

    <div class="panel-body">

        <form method="GET" action="index.php">

            <div class="form-group">

                <label>Cari Nama Barang</label>

                <input
                    type="text"
                    name="keyword"
                    class="form-control"
                    placeholder="Masukkan nama barang..."
                    value="<?= ks_e($keyword); ?>"
                >

            </div>

            <div class="form-group">

                <label>Barang</label>

                <select
                    name="idbarang"
                    class="form-control"
                >

                    <option value="0">
                        Semua Barang
                    </option>

                    <?php foreach ($daftar_barang as $b): ?>

                        <option
                            value="<?= (int) $b['idbarang']; ?>"
                            <?= $idbarang == $b['idbarang']
                                ? 'selected' : ''; ?>
                        >

                            <?= ks_e($b['nama']); ?>

                            (<?= ks_e($b['nama_satuan']); ?>)

                            <?= (int) $b['status'] === 0
                                ? ' - Tidak Aktif' : ''; ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="form-group">

                <label>Jenis Transaksi</label>

                <select
                    name="jenis"
                    class="form-control"
                >

                    <option value="">
                        Semua Transaksi
                    </option>

                    <option
                        value="M"
                        <?= $jenis === 'M' ? 'selected' : ''; ?>
                    >
                        Penerimaan
                    </option>

                    <option
                        value="J"
                        <?= $jenis === 'J' ? 'selected' : ''; ?>
                    >
                        Penjualan
                    </option>

                    <option
                        value="R"
                        <?= $jenis === 'R' ? 'selected' : ''; ?>
                    >
                        Retur
                    </option>

                </select>

            </div>

            <div class="form-group">

                <label>Tanggal Awal</label>

                <input
                    type="date"
                    name="tanggal_awal"
                    class="form-control"
                    value="<?= ks_e($tanggal_awal); ?>"
                >

            </div>

            <div class="form-group">

                <label>Tanggal Akhir</label>

                <input
                    type="date"
                    name="tanggal_akhir"
                    class="form-control"
                    value="<?= ks_e($tanggal_akhir); ?>"
                >

            </div>

            <div class="form-actions">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Tampilkan
                </button>

                <a
                    href="index.php"
                    class="btn btn-secondary"
                >
                    Reset
                </a>

            </div>

        </form>

    </div>

</section>

<!-- INFORMASI STOK BARANG -->

<?php if ($barang_terpilih && empty($errors)): ?>

<section class="panel">

    <div class="panel-header">

        <h2>Informasi Stok Barang</h2>

    </div>

    <div class="panel-body">

        <p>

            <strong>Nama Barang:</strong>

            <?= ks_e($barang_terpilih['nama']); ?>

        </p>

        <p>

            <strong>Satuan:</strong>

            <?= ks_e($barang_terpilih['nama_satuan']); ?>

        </p>

        <p>

            <strong>Status Barang:</strong>

            <?php if ((int) $barang_terpilih['status'] === 1): ?>

                <span class="badge badge-success">
                    Aktif
                </span>

            <?php else: ?>

                <span class="badge badge-danger">
                    Tidak Aktif
                </span>

            <?php endif; ?>

        </p>

        <div class="transaction-summary">

            <div>

                <span>Total Masuk Sesuai Filter</span>

                <strong>

                    <?= $total_masuk; ?>

                    <?= ks_e($barang_terpilih['nama_satuan']); ?>

                </strong>

            </div>

            <div>

                <span>Total Keluar Sesuai Filter</span>

                <strong>

                    <?= $total_keluar; ?>

                    <?= ks_e($barang_terpilih['nama_satuan']); ?>

                </strong>

            </div>

            <div class="transaction-total">

                <span>Stok Saat Ini</span>

                <strong>

                    <?= (int) $barang_terpilih['stok']; ?>

                    <?= ks_e($barang_terpilih['nama_satuan']); ?>

                </strong>

            </div>

        </div>

        <div class="form-note">

            Total masuk dan keluar mengikuti filter
            yang dipilih, sedangkan stok saat ini
            merupakan saldo barang terbaru.

        </div>

    </div>

</section>

<?php endif; ?>

<!-- TABEL KARTU STOK -->

<section class="panel">

    <div class="panel-header">

        <h2>Riwayat Kartu Stok</h2>

    </div>

    <div class="panel-body">

        <div class="table-responsive">

            <table class="data-table">

                <thead>

                    <tr>

                        <th>No</th>
                        <th>ID Kartu Stok</th>
                        <th>Tanggal</th>
                        <th>Nama Barang</th>
                        <th>Satuan</th>
                        <th>Transaksi</th>
                        <th>Referensi</th>
                        <th>Masuk</th>
                        <th>Keluar</th>
                        <th>Saldo Stok</th>

                    </tr>

                </thead>

                <tbody>

                    <?php if (empty($details)): ?>

                        <tr>

                            <td colspan="10">

                                Tidak ada data kartu stok
                                yang sesuai.

                            </td>

                        </tr>

                    <?php endif; ?>

                    <?php $no = 1; ?>

                    <?php foreach ($details as $d): ?>

                        <tr>

                            <td><?= $no++; ?></td>

                            <td>

                                <?= ks_format_id(
                                    'KS',
                                    (int) $d['idkartu_stok']
                                ); ?>

                            </td>

                            <td>

                                <?= ks_e($d['created_at']); ?>

                            </td>

                            <td>

                                <?= ks_e($d['nama_barang']); ?>

                            </td>

                            <td>

                                <?= ks_e($d['nama_satuan']); ?>

                            </td>

                            <td>

                                <span class="<?= ks_e(
                                    ks_badge($d['jenis_transaksi'])
                                ); ?>">

                                    <?= ks_e(
                                        ks_jenis_transaksi(
                                            $d['jenis_transaksi']
                                        )
                                    ); ?>

                                </span>

                            </td>

                            <td>

                                <?= ks_e(ks_referensi($d)); ?>

                            </td>

                            <td>

                                <?php if ((int) $d['masuk'] > 0): ?>

                                    +<?= (int) $d['masuk']; ?>

                                <?php else: ?>

                                    0

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php if ((int) $d['keluar'] > 0): ?>

                                    -<?= (int) $d['keluar']; ?>

                                <?php else: ?>

                                    0

                                <?php endif; ?>

                            </td>

                            <td>

                                <strong>

                                    <?= (int) $d['stock']; ?>

                                </strong>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

        <div class="form-note">

            Jumlah riwayat ditampilkan:
            <?= count($details); ?> transaksi.

        </div>

        <div class="form-note">

            Kartu stok dicatat secara otomatis
            melalui transaksi Penerimaan,
            Penjualan, dan Retur.

            Saldo stok pada setiap baris merupakan
            saldo setelah transaksi tersebut terjadi.

        </div>

    </div>

</section>

<?php

require __DIR__ . '/../../includes/footer.php';
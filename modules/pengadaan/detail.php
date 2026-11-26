<?php

require_once __DIR__ . '/helper.php';

$page_title = 'Detail Pengadaan';
$current_page = 'pengadaan';

// =========================================
// VALIDASI ID PENGADAAN
// =========================================

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id || $id < 1) {

    pg_flash('error', 'ID pengadaan tidak valid.');

    pg_redirect('index.php');
}

// =========================================
// INISIALISASI DATA
// =========================================

$pengadaan = null;
$details = [];
$barang = [];
$errors = [];

$is_proses = false;
$is_batal = false;
$boleh_edit = false;
$masih_ada_sisa = false;

$jumlah_penerimaan = 0;
$jumlah_detail = 0;

try {

    // =====================================
    // A. MENGAMBIL HEADER PENGADAAN
    // =====================================

    $pengadaan = pg_ambil_pengadaan(
        $koneksi,
        $id
    );

    if (!$pengadaan) {

        pg_flash(
            'error',
            'Pengadaan tidak ditemukan.'
        );

        pg_redirect('index.php');
    }

    // =====================================
    // B. MEMERIKSA STATUS PENGADAAN
    // =====================================

    $is_proses = $pengadaan['status'] === 'P';

    $is_batal = $pengadaan['status'] === 'B';

    // Menghitung jumlah penerimaan

    $stmt = $koneksi->prepare(
        "SELECT COUNT(*) AS jumlah
         FROM penerimaan
         WHERE idpengadaan = ?"
    );

    $stmt->bind_param('i', $id);

    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();

    $jumlah_penerimaan = (int) $row['jumlah'];

    $stmt->close();

    // Detail hanya dapat diubah sebelum penerimaan

    $boleh_edit = $is_proses
        && $jumlah_penerimaan === 0;

    // =====================================
    // C. MENGAMBIL PROGRESS PENGADAAN
    // =====================================

    try {

        $stmt = $koneksi->prepare(
            "SELECT
                iddetail_pengadaan,
                idpengadaan,
                idbarang,
                nama_barang,
                nama_satuan,
                harga_satuan,
                jumlah_pesan,
                jumlah_diterima,
                sisa_barang,
                sub_total

             FROM v_progress_pengadaan

             WHERE idpengadaan = ?

             ORDER BY iddetail_pengadaan ASC"
        );

        $stmt->bind_param('i', $id);

        $stmt->execute();

        $details = $stmt->get_result()
            ->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

    } catch (mysqli_sql_exception $e) {

        // Jika View progress bermasalah,
        // gunakan View detail pengadaan
        // dan data penerimaan sebagai alternatif.

        error_log(
            'View Progress Pengadaan: '
            . $e->getMessage()
        );

        $stmt = $koneksi->prepare(
            "SELECT

                d.iddetail_pengadaan,
                d.idpengadaan,
                d.idbarang,
                d.nama_barang,
                d.nama_satuan,
                d.harga_satuan,

                d.jumlah AS jumlah_pesan,

                COALESCE(
                    r.jumlah_diterima, 0
                ) AS jumlah_diterima,

                d.jumlah - COALESCE(
                    r.jumlah_diterima, 0
                ) AS sisa_barang,

                d.sub_total

             FROM v_detail_pengadaan d

             LEFT JOIN (

                 SELECT
                     p.idpengadaan,
                     dp.barang_idbarang AS idbarang,

                     SUM(dp.jumlah_terima)
                         AS jumlah_diterima

                 FROM penerimaan p

                 JOIN detail_penerimaan dp
                    ON p.idpenerimaan = dp.idpenerimaan

                 WHERE p.status = 'S'

                 GROUP BY
                     p.idpengadaan,
                     dp.barang_idbarang

             ) r

             ON d.idpengadaan = r.idpengadaan
             AND d.idbarang = r.idbarang

             WHERE d.idpengadaan = ?

             ORDER BY d.iddetail_pengadaan ASC"
        );

        $stmt->bind_param('i', $id);

        $stmt->execute();

        $details = $stmt->get_result()
            ->fetch_all(MYSQLI_ASSOC);

        $stmt->close();
    }

    $jumlah_detail = count($details);

    foreach ($details as $d) {

        if ((int) $d['sisa_barang'] > 0) {

            $masih_ada_sisa = true;

            break;
        }
    }

    // =====================================
    // D. MENGAMBIL BARANG AKTIF
    // =====================================

    if ($boleh_edit) {

        $result = $koneksi->query(
            "SELECT
                v.idbarang,
                v.nama,
                v.nama_satuan,
                v.harga

             FROM v_barang v

             JOIN barang b
                ON v.idbarang = b.idbarang

             JOIN satuan s
                ON b.idsatuan = s.idsatuan

             WHERE v.status = 1
             AND b.status = 1
             AND s.status = 1

             ORDER BY v.nama ASC"
        );

        $barang = $result->fetch_all(
            MYSQLI_ASSOC
        );

        $result->free();
    }

} catch (Throwable $e) {

    $boleh_edit = false;

    error_log(
        'Detail Pengadaan: '
        . $e->getMessage()
    );

    $pesan = 'Data pengadaan gagal dimuat.';

    // Detail error hanya ditampilkan di localhost.

    if (in_array(
        $_SERVER['REMOTE_ADDR'] ?? '',
        ['127.0.0.1', '::1'],
        true
    )) {

        $pesan .= ' ' . $e->getMessage();
    }

    $errors[] = $pesan;
}

// =========================================
// HEADER WEBSITE
// =========================================

require __DIR__ . '/../../includes/header.php';

?>

<div class="tx-detail">

    <div class="section-heading">

        <h2>Detail Pengadaan</h2>

        <p>
            Kelola rincian pesanan dan progress
            penerimaan barang.
        </p>

    </div>

    <?php pg_tampil_flash(); ?>

    <!-- NOTIFIKASI ERROR -->

    <?php if (!empty($errors)): ?>

        <?php foreach ($errors as $error): ?>

            <div class="alert-error">

                <?= pg_e($error); ?>

            </div>

        <?php endforeach; ?>

        <a
            href="index.php"
            class="btn btn-secondary"
        >
            &larr; Kembali ke Pengadaan
        </a>

    <?php endif; ?>


    <?php if ($pengadaan && empty($errors)): ?>

        <!-- =================================
             INFORMASI PENGADAAN
        ================================== -->

        <section class="panel">

            <div class="panel-header tx-panel-header">

                <h2>Informasi Pengadaan</h2>

                <a
                    href="index.php"
                    class="btn btn-secondary"
                >
                    &larr; Kembali
                </a>

            </div>

            <div class="panel-body tx-panel-body">

                <div class="tx-info-grid">

                    <div class="tx-info-item">

                        <span>ID Pengadaan</span>

                        <strong>

                            PG-<?= str_pad(
                                (string) $id,
                                5,
                                '0',
                                STR_PAD_LEFT
                            ); ?>

                        </strong>

                    </div>

                    <div class="tx-info-item">

                        <span>Vendor</span>

                        <strong>

                            <?= pg_e(
                                $pengadaan['nama_vendor']
                            ); ?>

                        </strong>

                    </div>

                    <div class="tx-info-item">

                        <span>Tanggal Pengadaan</span>

                        <strong>

                            <?= date(
                                'd/m/Y H:i',
                                strtotime(
                                    $pengadaan['timestamp']
                                )
                            ); ?>

                        </strong>

                    </div>

                    <div class="tx-info-item">

                        <span>Dibuat Oleh</span>

                        <strong>

                            <?= pg_e(
                                $pengadaan['username']
                            ); ?>

                        </strong>

                    </div>

                    <div class="tx-info-item">

                        <span>Jumlah Jenis Barang</span>

                        <strong>

                            <?= $jumlah_detail; ?>
                            jenis barang

                        </strong>

                    </div>

                    <div class="tx-info-item">

                        <span>Jumlah Penerimaan</span>

                        <strong>

                            <?= $jumlah_penerimaan; ?>
                            transaksi

                        </strong>

                    </div>

                </div>

                <!-- STATUS PENGADAAN -->

                <div class="tx-status-row">

                    <span>Status Pengadaan</span>

                    <?php if ($is_batal): ?>

                        <span class="tx-status tx-status-danger">

                            Dibatalkan

                        </span>

                    <?php elseif ($is_proses): ?>

                        <span class="tx-status tx-status-process">

                            Dalam Proses

                        </span>

                    <?php elseif (
                        $pengadaan['status'] === 'S'
                    ): ?>

                        <span class="tx-status tx-status-success">

                            Selesai

                        </span>

                    <?php else: ?>

                        <span class="tx-status">

                            Status Tidak Dikenal

                        </span>

                    <?php endif; ?>

                </div>

            </div>

        </section>


        <!-- =================================
             FORM TAMBAH BARANG
        ================================== -->

        <?php if ($boleh_edit): ?>

            <section class="panel">

                <div class="panel-header tx-panel-header">

                    <h2>Tambah Barang</h2>

                </div>

                <div class="panel-body tx-panel-body">

                    <?php if (empty($barang)): ?>

                        <div class="tx-note">

                            Tidak ada barang aktif
                            yang dapat ditambahkan.

                        </div>

                    <?php else: ?>

                        <form
                            action="tambah_detail.php"
                            method="POST"
                            class="tx-form-grid"
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= pg_e(
                                    $_SESSION['csrf_pengadaan']
                                ); ?>"
                            >

                            <input
                                type="hidden"
                                name="idpengadaan"
                                value="<?= $id; ?>"
                            >

                            <!-- PILIH BARANG -->

                            <div class="form-group tx-full">

                                <label for="idbarang">
                                    Barang
                                </label>

                                <select
                                    name="idbarang"
                                    id="idbarang"
                                    class="form-control"
                                    required
                                >

                                    <option value="">
                                        -- Pilih Barang --
                                    </option>

                                    <?php foreach ($barang as $b): ?>

                                        <option
                                            value="<?= (int)
                                                $b['idbarang']; ?>"

                                            data-harga="<?= (int)
                                                $b['harga']; ?>"
                                        >

                                            <?= pg_e(
                                                $b['nama']
                                            ); ?>

                                            (<?= pg_e(
                                                $b['nama_satuan']
                                            ); ?>)

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>


                            <!-- HARGA SATUAN -->

                            <div class="form-group">

                                <label for="harga_satuan">

                                    Harga Satuan (Rp)

                                </label>

                                <input
                                    type="number"
                                    name="harga_satuan"
                                    id="harga_satuan"
                                    class="form-control"
                                    min="0"
                                    max="100000000"
                                    placeholder="0"
                                    required
                                >

                            </div>


                            <!-- JUMLAH BARANG -->

                            <div class="form-group">

                                <label for="jumlah">

                                    Jumlah Barang

                                </label>

                                <input
                                    type="number"
                                    name="jumlah"
                                    id="jumlah"
                                    class="form-control"
                                    min="1"
                                    max="1000000"
                                    value="1"
                                    required
                                >

                            </div>


                            <!-- ESTIMASI SUBTOTAL -->

                            <div class="tx-form-bottom tx-full">

                                <div>

                                    <label for="estimasi">

                                        Estimasi Subtotal

                                    </label>

                                    <input
                                        type="text"
                                        id="estimasi"
                                        class="tx-estimate"
                                        value="Rp 0"
                                        readonly
                                    >

                                </div>

                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                >

                                    + Tambah Barang

                                </button>

                            </div>

                        </form>

                    <?php endif; ?>

                </div>

            </section>

        <?php endif; ?>


        <!-- =================================
             DAFTAR BARANG PENGADAAN
        ================================== -->

        <section class="panel">

            <div class="panel-header tx-panel-header">

                <h2>Daftar Barang Pengadaan</h2>

                <span>

                    <?= $jumlah_detail; ?> jenis barang

                </span>

            </div>

            <div class="panel-body tx-panel-body">

                <div class="table-responsive">

                    <table class="data-table">

                        <thead>

                            <tr>

                                <th>No</th>
                                <th>Nama Barang</th>
                                <th>Satuan</th>
                                <th>Harga Satuan</th>
                                <th>Pesan</th>
                                <th>Diterima</th>
                                <th>Sisa</th>
                                <th>Progress</th>
                                <th>Subtotal</th>

                                <?php if ($boleh_edit): ?>

                                    <th>Aksi</th>

                                <?php endif; ?>

                            </tr>

                        </thead>

                        <tbody>

                            <?php if (empty($details)): ?>

                                <tr>

                                    <td
                                        colspan="<?= $boleh_edit
                                            ? 10 : 9; ?>"
                                        class="tx-empty"
                                    >

                                        Belum ada barang
                                        dalam pengadaan.

                                    </td>

                                </tr>

                            <?php endif; ?>


                            <?php foreach ($details as $i => $d): ?>

                                <?php

                                $pesan = (int)
                                    $d['jumlah_pesan'];

                                $diterima = (int)
                                    $d['jumlah_diterima'];

                                $sisa = (int)
                                    $d['sisa_barang'];

                                $progress = $pesan > 0

                                    ? max(
                                        0,
                                        min(
                                            100,
                                            round(
                                                $diterima
                                                / $pesan * 100
                                            )
                                        )
                                    )

                                    : 0;

                                ?>

                                <tr>

                                    <td>

                                        <?= $i + 1; ?>

                                    </td>

                                    <td>

                                        <strong>

                                            <?= pg_e(
                                                $d['nama_barang']
                                            ); ?>

                                        </strong>

                                    </td>

                                    <td>

                                        <?= pg_e(
                                            $d['nama_satuan']
                                        ); ?>

                                    </td>

                                    <td class="tx-money">

                                        <?= pg_rupiah(
                                            $d['harga_satuan']
                                        ); ?>

                                    </td>

                                    <td class="tx-center">

                                        <?= $pesan; ?>

                                    </td>

                                    <td class="tx-center">

                                        <?= $diterima; ?>

                                    </td>

                                    <td class="tx-center">

                                        <?= $sisa; ?>

                                    </td>

                                    <!-- PROGRESS -->

                                    <td>

                                        <?php if ($is_batal): ?>

                                            <span class="tx-status tx-status-danger">

                                                Dibatalkan

                                            </span>

                                        <?php else: ?>

                                            <div class="tx-progress">

                                                <span class="tx-progress-label">

                                                    <?= $progress; ?>%

                                                </span>

                                                <div class="tx-progress-track">

                                                    <div
                                                        class="tx-progress-fill
                                                        <?= $progress >= 100
                                                            ? 'tx-progress-complete'
                                                            : ''; ?>"

                                                        style="width: <?= $progress; ?>%;"
                                                    ></div>

                                                </div>

                                            </div>

                                        <?php endif; ?>

                                    </td>

                                    <!-- SUBTOTAL -->

                                    <td class="tx-money">

                                        <?= pg_rupiah(
                                            $d['sub_total']
                                        ); ?>

                                    </td>


                                    <!-- HAPUS DETAIL -->

                                    <?php if ($boleh_edit): ?>

                                        <td>

                                            <form
                                                action="hapus_detail.php"
                                                method="POST"
                                                onsubmit="return confirm('Hapus barang ini dari pengadaan?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= pg_e(
                                                        $_SESSION['csrf_pengadaan']
                                                    ); ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="idpengadaan"
                                                    value="<?= $id; ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="iddetail_pengadaan"
                                                    value="<?= (int)
                                                        $d['iddetail_pengadaan']; ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="btn btn-danger btn-sm"
                                                >

                                                    Hapus

                                                </button>

                                            </form>

                                        </td>

                                    <?php endif; ?>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>


                <!-- =========================
                     RINGKASAN PENGADAAN
                ========================== -->

                <div class="tx-summary">

                    <div class="tx-summary-row">

                        <span>Subtotal</span>

                        <strong>

                            <?= pg_rupiah(
                                $pengadaan['subtotal_nilai']
                            ); ?>

                        </strong>

                    </div>

                    <div class="tx-summary-row">

                        <span>PPN (11%)</span>

                        <strong>

                            <?= pg_rupiah(
                                $pengadaan['ppn']
                            ); ?>

                        </strong>

                    </div>

                    <div class="tx-summary-row tx-summary-total">

                        <span>Total Pengadaan</span>

                        <strong>

                            <?= pg_rupiah(
                                $pengadaan['total_nilai']
                            ); ?>

                        </strong>

                    </div>

                </div>


                <!-- =========================
                     KETERANGAN STATUS
                ========================== -->

                <?php if ($is_batal): ?>

                    <div class="tx-note">

                        Pengadaan ini telah dibatalkan.

                        Detail pesanan tetap tersimpan
                        sebagai riwayat.

                        Pengadaan tidak dapat diubah
                        atau dilanjutkan ke penerimaan.

                        Nilai di atas merupakan nilai
                        pesanan sebelum dibatalkan.

                    </div>

                <?php elseif (
                    $pengadaan['status'] === 'S'
                ): ?>

                    <div class="tx-note">

                        Pengadaan ini telah selesai.

                        Seluruh barang sudah diterima.

                    </div>

                <?php elseif (
                    $is_proses && $masih_ada_sisa
                ): ?>

                    <div class="tx-note">

                        Pengadaan akan otomatis selesai
                        setelah seluruh barang diterima.

                        <?php if (!$boleh_edit): ?>

                            Detail pengadaan tidak dapat
                            diubah karena sudah memiliki
                            transaksi penerimaan.

                        <?php endif; ?>

                    </div>

                    <div class="tx-actions">

                        <a
                            href="../penerimaan/tambah.php?idpengadaan=<?= $id; ?>"
                            class="btn btn-primary"
                        >

                            Lanjut ke Penerimaan &rarr;

                        </a>

                    </div>

                <?php elseif (
                    $is_proses && empty($details)
                ): ?>

                    <div class="tx-note">

                        Tambahkan barang terlebih dahulu
                        sebelum melakukan penerimaan.

                    </div>

                <?php endif; ?>

            </div>

        </section>

    <?php endif; ?>

</div>


<!-- =========================================
     JAVASCRIPT ESTIMASI SUBTOTAL
========================================== -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const barang =
            document.getElementById('idbarang');

        const harga =
            document.getElementById('harga_satuan');

        const jumlah =
            document.getElementById('jumlah');

        const estimasi =
            document.getElementById('estimasi');

        // Mencegah error JavaScript jika form
        // tidak tersedia pada halaman.

        if (
            !barang ||
            !harga ||
            !jumlah ||
            !estimasi
        ) {

            return;
        }

        function hitungSubtotal() {

            const nilaiHarga =
                Number(harga.value) || 0;

            const nilaiJumlah =
                Number(jumlah.value) || 0;

            const subtotal =
                nilaiHarga * nilaiJumlah;

            estimasi.value = 'Rp ' +
                subtotal.toLocaleString('id-ID');
        }

        barang.addEventListener(
            'change',
            function () {

                const pilihan =
                    barang.options[barang.selectedIndex];

                harga.value = pilihan
                    ? pilihan.dataset.harga || ''
                    : '';

                hitungSubtotal();
            }
        );

        harga.addEventListener(
            'input',
            hitungSubtotal
        );

        jumlah.addEventListener(
            'input',
            hitungSubtotal
        );

        hitungSubtotal();
    }
);

</script>

<?php

require __DIR__ . '/../../includes/footer.php';

?>
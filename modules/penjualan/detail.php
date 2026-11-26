<?php

require_once __DIR__ . '/helper.php';

$page_title = 'Detail Penjualan';
$current_page = 'penjualan';

// =========================================
// VALIDASI ID PENJUALAN
// =========================================

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id || $id < 1) {

    pj_redirect('index.php');
}

// =========================================
// MENGAMBIL INFORMASI PENJUALAN
// =========================================

$penjualan = pj_ambil_penjualan(
    $koneksi,
    $id
);

if (!$penjualan) {

    pj_flash(
        'error',
        'Transaksi penjualan tidak ditemukan.'
    );

    pj_redirect('index.php');
}

// =========================================
// MENGAMBIL DETAIL PENJUALAN
// =========================================

$stmt = $koneksi->prepare(
    "SELECT *
     FROM v_detail_penjualan
     WHERE idpenjualan = ?
     ORDER BY iddetail_penjualan ASC"
);

$stmt->bind_param('i', $id);

$stmt->execute();

$details = $stmt->get_result()->fetch_all(
    MYSQLI_ASSOC
);

$stmt->close();

// Margin yang digunakan saat transaksi

$margin = $penjualan['persen']
    ?? $penjualan['margin']
    ?? null;

require __DIR__ . '/../../includes/header.php';

?>

<div class="tx-detail">

    <div class="section-heading">

        <h2>Detail Penjualan</h2>

        <p>
            Lihat rincian barang dan nilai
            transaksi penjualan.
        </p>

    </div>

    <?php pj_tampil_flash(); ?>


    <!-- INFORMASI PENJUALAN -->

    <section class="panel">

        <div class="panel-header tx-panel-header">

            <h2>Informasi Penjualan</h2>

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

                    <span>ID Penjualan</span>

                    <strong>

                        PJ-<?= str_pad(
                            $id,
                            5,
                            '0',
                            STR_PAD_LEFT
                        ); ?>

                    </strong>

                </div>

                <div class="tx-info-item">

                    <span>Tanggal Penjualan</span>

                    <strong>

                        <?= date(
                            'd/m/Y H:i',
                            strtotime(
                                $penjualan['created_at']
                            )
                        ); ?>

                    </strong>

                </div>

                <div class="tx-info-item">

                    <span>Dilayani Oleh</span>

                    <strong>

                        <?= pj_e(
                            $penjualan['username']
                        ); ?>

                    </strong>

                </div>

                <div class="tx-info-item">

                    <span>Margin Penjualan</span>

                    <strong>

                        <?= $margin !== null
                            ? pj_e($margin) . '%'
                            : '-'; ?>

                    </strong>

                </div>

                <div class="tx-info-item">

                    <span>ID Margin</span>

                    <strong>

                        MRG-<?= str_pad(
                            $penjualan['idmargin_penjualan'],
                            3,
                            '0',
                            STR_PAD_LEFT
                        ); ?>

                    </strong>

                </div>

                <div class="tx-info-item">

                    <span>Jumlah Jenis Barang</span>

                    <strong>

                        <?= count($details); ?>
                        jenis barang

                    </strong>

                </div>

            </div>

            <div class="tx-status-row">

                <span>Keterangan Transaksi</span>

                <span class="tx-status
                    <?= empty($details)
                        ? 'tx-status-process'
                        : 'tx-status-success'; ?>">

                    <?= empty($details)
                        ? 'Belum Ada Detail'
                        : 'Tercatat'; ?>

                </span>

            </div>

        </div>

    </section>


    <!-- RINCIAN PENJUALAN -->

    <section class="panel">

        <div class="panel-header tx-panel-header">

            <h2>Daftar Barang Penjualan</h2>

            <span>

                <?= count($details); ?> jenis barang

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
                            <th>Harga Jual</th>
                            <th>Jumlah</th>
                            <th>Subtotal</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if (empty($details)): ?>

                            <tr>

                                <td
                                    colspan="6"
                                    class="tx-empty"
                                >

                                    Belum ada detail penjualan.

                                </td>

                            </tr>

                        <?php endif; ?>

                        <?php foreach ($details as $i => $d): ?>

                            <tr>

                                <td>

                                    <?= $i + 1; ?>

                                </td>

                                <td>

                                    <strong>

                                        <?= pj_e(
                                            $d['nama_barang']
                                        ); ?>

                                    </strong>

                                </td>

                                <td>

                                    <?= pj_e(
                                        $d['nama_satuan']
                                    ); ?>

                                </td>

                                <td class="tx-money">

                                    <?= pj_rupiah(
                                        $d['harga_satuan']
                                    ); ?>

                                </td>

                                <td class="tx-center">

                                    <?= (int) $d['jumlah']; ?>

                                </td>

                                <td class="tx-money">

                                    <strong>

                                        <?= pj_rupiah(
                                            $d['subtotal']
                                        ); ?>

                                    </strong>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>


            <!-- RINGKASAN PENJUALAN -->

            <div class="tx-summary">

                <div class="tx-summary-row">

                    <span>Subtotal Penjualan</span>

                    <strong>

                        <?= pj_rupiah(
                            $penjualan['subtotal_nilai']
                        ); ?>

                    </strong>

                </div>

                <div class="tx-summary-row">

                    <span>PPN (11%)</span>

                    <strong>

                        <?= pj_rupiah(
                            $penjualan['ppn']
                        ); ?>

                    </strong>

                </div>

                <div class="tx-summary-row tx-summary-total">

                    <span>Total Penjualan</span>

                    <strong>

                        <?= pj_rupiah(
                            $penjualan['total_nilai']
                        ); ?>

                    </strong>

                </div>

            </div>


            <!-- CATATAN TRANSAKSI -->

            <div class="tx-note">

                Harga jual dan subtotal ditampilkan
                berdasarkan nilai yang tersimpan pada
                transaksi penjualan.

                Perubahan margin baru tidak mengubah
                nilai transaksi sebelumnya.

            </div>


            <!-- TOMBOL AKSI -->

            <div class="tx-actions">

                <?php if (!empty($details)): ?>

                    <a
                        href="nota.php?id=<?= $id; ?>"
                        class="btn btn-secondary"
                    >
                        Cetak Nota
                    </a>

                <?php endif; ?>

                <a
                    href="tambah.php"
                    class="btn btn-primary"
                >
                    + Penjualan Baru
                </a>

            </div>

        </div>

    </section>

</div>

<?php

require __DIR__ . '/../../includes/footer.php';

?>
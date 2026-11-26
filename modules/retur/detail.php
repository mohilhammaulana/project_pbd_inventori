<?php

require_once __DIR__ . '/helper.php';

$page_title = 'Detail Retur';
$current_page = 'retur';

$id = (int) ($_GET['id'] ?? 0);

$esc = static function ($value): string {
    return htmlspecialchars(
        (string) $value, ENT_QUOTES, 'UTF-8'
    );
};

$rp = static function ($value): string {
    return 'Rp ' . number_format($value, 0, ',', '.');
};

// Mengambil informasi retur
$stmt = $koneksi->prepare(
    "SELECT *
     FROM v_retur
     WHERE idretur = ?"
);

$stmt->bind_param('i', $id);
$stmt->execute();

$retur = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$retur) {

    header('Location: index.php');
    exit;
}

$idpenerimaan = (int) $retur['idpenerimaan'];

// Mengambil referensi pengadaan
$stmt = $koneksi->prepare(
    "SELECT idpengadaan
     FROM v_penerimaan
     WHERE idpenerimaan = ?"
);

$stmt->bind_param('i', $idpenerimaan);
$stmt->execute();

$penerimaan = $stmt->get_result()->fetch_assoc();

$stmt->close();

$idpengadaan = (int)
    ($penerimaan['idpengadaan'] ?? 0);

// Mengambil detail retur
$stmt = $koneksi->prepare(
    "SELECT *
     FROM v_detail_retur
     WHERE idretur = ?
     ORDER BY iddetail_retur ASC"
);

$stmt->bind_param('i', $id);
$stmt->execute();

$details = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt->close();

// Menghitung total nilai retur
$total_retur = 0;

foreach ($details as $d) {

    $total_retur += (int) $d['nilai_retur'];
}

require __DIR__ . '/../../includes/header.php';

?>

<div class="tx-detail">

    <div class="section-heading">

        <h2>Detail Retur Barang</h2>

        <p>
            Lihat rincian pengembalian barang kepada vendor.
        </p>

    </div>

    <?php

    if (function_exists('rt_tampil_flash')) {

        rt_tampil_flash();

    } elseif (isset($_SESSION['flash_retur'])) {

        $flash = $_SESSION['flash_retur'];

        $class = ($flash['tipe'] ?? '') === 'success'
            ? 'alert-success'
            : 'alert-error';

        echo '<div class="' . $class . '">';
        echo $esc($flash['pesan'] ?? '');
        echo '</div>';

        unset($_SESSION['flash_retur']);
    }

    ?>

    <!-- INFORMASI RETUR -->

    <section class="panel">

        <div class="panel-header tx-panel-header">

            <h2>Informasi Retur</h2>

            <a href="index.php" class="btn btn-secondary">
                &larr; Kembali
            </a>

        </div>

        <div class="panel-body tx-panel-body">

            <div class="tx-info-grid">

                <div class="tx-info-item">

                    <span>ID Retur</span>

                    <strong>
                        RT-<?= str_pad(
                            $id, 5, '0', STR_PAD_LEFT
                        ); ?>
                    </strong>

                </div>

                <div class="tx-info-item">

                    <span>Tanggal Retur</span>

                    <strong>
                        <?= date(
                            'd/m/Y H:i',
                            strtotime($retur['created_at'])
                        ); ?>
                    </strong>

                </div>

                <div class="tx-info-item">

                    <span>ID Penerimaan</span>

                    <strong>

                        <a
                            href="../penerimaan/detail.php?id=<?= $idpenerimaan; ?>"
                        >

                            PN-<?= str_pad(
                                $idpenerimaan,
                                5,
                                '0',
                                STR_PAD_LEFT
                            ); ?>

                        </a>

                    </strong>

                </div>

                <div class="tx-info-item">

                    <span>ID Pengadaan</span>

                    <strong>

                        <?php if ($idpengadaan > 0): ?>

                            <a
                                href="../pengadaan/detail.php?id=<?= $idpengadaan; ?>"
                            >

                                PG-<?= str_pad(
                                    $idpengadaan,
                                    5,
                                    '0',
                                    STR_PAD_LEFT
                                ); ?>

                            </a>

                        <?php else: ?>

                            -

                        <?php endif; ?>

                    </strong>

                </div>

                <div class="tx-info-item">

                    <span>Vendor</span>

                    <strong>
                        <?= $esc($retur['nama_vendor']); ?>
                    </strong>

                </div>

                <div class="tx-info-item">

                    <span>Diproses Oleh</span>

                    <strong>
                        <?= $esc($retur['username']); ?>
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

    <!-- DAFTAR BARANG RETUR -->

    <section class="panel">

        <div class="panel-header tx-panel-header">

            <h2>Daftar Barang Retur</h2>

            <span>
                <?= count($details); ?> rincian barang
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
                            <th>Jumlah Retur</th>
                            <th>Alasan Retur</th>
                            <th>Nilai Retur</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if (empty($details)): ?>

                            <tr>

                                <td colspan="7" class="tx-empty">

                                    Belum ada detail retur.

                                </td>

                            </tr>

                        <?php endif; ?>

                        <?php foreach ($details as $i => $d): ?>

                            <tr>

                                <td><?= $i + 1; ?></td>

                                <td>

                                    <strong>
                                        <?= $esc($d['nama_barang']); ?>
                                    </strong>

                                </td>

                                <td>
                                    <?= $esc($d['nama_satuan']); ?>
                                </td>

                                <td class="tx-money">

                                    <?= $rp(
                                        $d['harga_satuan_terima']
                                    ); ?>

                                </td>

                                <td class="tx-center">

                                    <?= (int) $d['jumlah']; ?>

                                </td>

                                <td>

                                    <?= $esc($d['alasan']); ?>

                                </td>

                                <td class="tx-money">

                                    <strong>

                                        <?= $rp($d['nilai_retur']); ?>

                                    </strong>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

            <!-- RINGKASAN RETUR -->

            <div class="tx-summary">

                <div class="tx-summary-row">

                    <span>Jumlah Rincian Barang</span>

                    <strong>
                        <?= count($details); ?>
                    </strong>

                </div>

                <div class="tx-summary-row tx-summary-total">

                    <span>Total Nilai Retur</span>

                    <strong>
                        <?= $rp($total_retur); ?>
                    </strong>

                </div>

            </div>

            <div class="tx-note">

                Nilai retur dihitung berdasarkan harga satuan
                penerimaan dikalikan jumlah barang yang diretur,
                sebelum PPN.

                Riwayat barang keluar akibat retur dapat dilihat
                melalui menu Kartu Stok.

            </div>

            <div class="tx-actions">

                <a
                    href="../penerimaan/detail.php?id=<?= $idpenerimaan; ?>"
                    class="btn btn-secondary"
                >
                    Lihat Penerimaan
                </a>

                <a
                    href="../kartu_stok/index.php"
                    class="btn btn-primary"
                >
                    Lihat Kartu Stok &rarr;
                </a>

            </div>

        </div>

    </section>

</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
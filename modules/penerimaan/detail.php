<?php

require_once __DIR__ . '/helper.php';

$page_title = 'Detail Penerimaan';
$current_page = 'penerimaan';

$id = (int) ($_GET['id'] ?? 0);

$esc = static function ($value): string {
    return htmlspecialchars(
        (string) $value, ENT_QUOTES, 'UTF-8'
    );
};

$rp = static function ($value): string {
    return 'Rp ' . number_format($value, 0, ',', '.');
};

// Mengambil informasi penerimaan
$stmt = $koneksi->prepare(
    "SELECT *
     FROM v_penerimaan
     WHERE idpenerimaan = ?"
);

$stmt->bind_param('i', $id);
$stmt->execute();

$penerimaan = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$penerimaan) {

    header('Location: index.php');
    exit;
}

$idpengadaan = (int) $penerimaan['idpengadaan'];

// Mengambil detail penerimaan
$stmt = $koneksi->prepare(
    "SELECT *
     FROM v_detail_penerimaan
     WHERE idpenerimaan = ?
     ORDER BY iddetail_penerimaan ASC"
);

$stmt->bind_param('i', $id);
$stmt->execute();

$details = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt->close();

// Mengambil status pengadaan
$stmt = $koneksi->prepare(
    "SELECT status
     FROM v_pengadaan
     WHERE idpengadaan = ?"
);

$stmt->bind_param('i', $idpengadaan);
$stmt->execute();

$pengadaan = $stmt->get_result()->fetch_assoc();

$stmt->close();

// Mengambil progress pengadaan
$stmt = $koneksi->prepare(
    "SELECT *
     FROM v_progress_pengadaan
     WHERE idpengadaan = ?
     ORDER BY iddetail_pengadaan ASC"
);

$stmt->bind_param('i', $idpengadaan);
$stmt->execute();

$progress_barang = $stmt->get_result()
    ->fetch_all(MYSQLI_ASSOC);

$stmt->close();

$total_penerimaan = 0;
$masih_ada_sisa = false;

foreach ($details as $d) {

    $total_penerimaan += (int) $d['sub_total_terima'];
}

foreach ($progress_barang as $p) {

    if ((int) $p['sisa_barang'] > 0) {

        $masih_ada_sisa = true;
    }
}

$is_selesai = $penerimaan['status'] === 'S';

require __DIR__ . '/../../includes/header.php';

?>

<div class="tx-detail">

    <div class="section-heading">

        <h2>Detail Penerimaan</h2>

        <p>
            Lihat rincian barang yang diterima dari vendor.
        </p>

    </div>

    <?php

    if (function_exists('pn_tampil_flash')) {

        pn_tampil_flash();

    } elseif (isset($_SESSION['flash_penerimaan'])) {

        $flash = $_SESSION['flash_penerimaan'];

        $class = ($flash['tipe'] ?? '') === 'success'
            ? 'alert-success'
            : 'alert-error';

        echo '<div class="' . $class . '">';
        echo $esc($flash['pesan'] ?? '');
        echo '</div>';

        unset($_SESSION['flash_penerimaan']);
    }

    ?>

    <!-- INFORMASI PENERIMAAN -->

    <section class="panel">

        <div class="panel-header tx-panel-header">

            <h2>Informasi Penerimaan</h2>

            <a href="index.php" class="btn btn-secondary">
                &larr; Kembali
            </a>

        </div>

        <div class="panel-body tx-panel-body">

            <div class="tx-info-grid">

                <div class="tx-info-item">

                    <span>ID Penerimaan</span>

                    <strong>
                        PN-<?= str_pad(
                            $id, 5, '0', STR_PAD_LEFT
                        ); ?>
                    </strong>

                </div>

                <div class="tx-info-item">

                    <span>ID Pengadaan</span>

                    <strong>

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

                    </strong>

                </div>

                <div class="tx-info-item">

                    <span>Vendor</span>

                    <strong>
                        <?= $esc($penerimaan['nama_vendor']); ?>
                    </strong>

                </div>

                <div class="tx-info-item">

                    <span>Tanggal Penerimaan</span>

                    <strong>
                        <?= date(
                            'd/m/Y H:i',
                            strtotime($penerimaan['created_at'])
                        ); ?>
                    </strong>

                </div>

                <div class="tx-info-item">

                    <span>Diterima Oleh</span>

                    <strong>
                        <?= $esc($penerimaan['username']); ?>
                    </strong>

                </div>

                <div class="tx-info-item">

                    <span>Jumlah Jenis Barang</span>

                    <strong>
                        <?= count($details); ?> jenis barang
                    </strong>

                </div>

            </div>

            <div class="tx-status-row">

                <span>Status Penerimaan</span>

                <span class="tx-status
                    <?= $is_selesai
                        ? 'tx-status-success'
                        : 'tx-status-process'; ?>">

                    <?= $is_selesai
                        ? 'Selesai'
                        : 'Dalam Proses'; ?>

                </span>

            </div>

        </div>

    </section>

    <!-- DETAIL BARANG DITERIMA -->

    <section class="panel">

        <div class="panel-header tx-panel-header">

            <h2>Barang yang Diterima</h2>

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
                            <th>Jumlah Diterima</th>
                            <th>Subtotal</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if (empty($details)): ?>

                            <tr>

                                <td colspan="6" class="tx-empty">

                                    Belum ada detail penerimaan.

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

                                    <?= (int) $d['jumlah_terima']; ?>

                                </td>

                                <td class="tx-money">

                                    <?= $rp(
                                        $d['sub_total_terima']
                                    ); ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

            <div class="tx-summary">

                <div class="tx-summary-row">

                    <span>Jenis Barang</span>

                    <strong><?= count($details); ?></strong>

                </div>

                <div class="tx-summary-row tx-summary-total">

                    <span>Total Nilai Penerimaan</span>

                    <strong>
                        <?= $rp($total_penerimaan); ?>
                    </strong>

                </div>

            </div>

            <?php if ($is_selesai): ?>

                <div class="tx-note">

                    Penerimaan telah selesai.
                    Riwayat penerimaan tersimpan dan perubahan
                    stok dapat dilihat melalui Kartu Stok.

                </div>

            <?php endif; ?>

        </div>

    </section>

    <!-- PROGRESS PENGADAAN -->

    <section class="panel">

        <div class="panel-header tx-panel-header">

            <h2>Progress Pengadaan Saat Ini</h2>

            <span class="tx-status
                <?= ($pengadaan['status'] ?? '') === 'S'
                    ? 'tx-status-success'
                    : 'tx-status-process'; ?>">

                <?= ($pengadaan['status'] ?? '') === 'S'
                    ? 'Selesai'
                    : 'Dalam Proses'; ?>

            </span>

        </div>

        <div class="panel-body tx-panel-body">

            <div class="table-responsive">

                <table class="data-table">

                    <thead>

                        <tr>
                            <th>Nama Barang</th>
                            <th>Satuan</th>
                            <th>Pesanan</th>
                            <th>Total Diterima</th>
                            <th>Sisa Pesanan</th>
                            <th>Progress</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if (empty($progress_barang)): ?>

                            <tr>
                                <td colspan="6" class="tx-empty">
                                    Tidak ada rincian pengadaan.
                                </td>
                            </tr>

                        <?php endif; ?>

                        <?php foreach ($progress_barang as $p): ?>

                            <?php

                            $pesan = (int) $p['jumlah_pesan'];
                            $diterima = (int) $p['jumlah_diterima'];
                            $sisa = (int) $p['sisa_barang'];

                            $persen = $pesan > 0
                                ? min(100, round(
                                    $diterima / $pesan * 100
                                ))
                                : 0;

                            ?>

                            <tr>

                                <td>
                                    <?= $esc($p['nama_barang']); ?>
                                </td>

                                <td>
                                    <?= $esc($p['nama_satuan']); ?>
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

                                <td>

                                    <div class="tx-progress">

                                        <span class="tx-progress-label">
                                            <?= $persen; ?>%
                                        </span>

                                        <div class="tx-progress-track">

                                            <div
                                                class="tx-progress-fill
                                                <?= $persen >= 100
                                                    ? 'tx-progress-complete'
                                                    : ''; ?>"
                                                style="width: <?= $persen; ?>%"
                                            ></div>

                                        </div>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

            <div class="tx-actions">

                <?php if ($is_selesai): ?>

                    <a
                        href="../retur/tambah.php?idpenerimaan=<?= $id; ?>"
                        class="btn btn-secondary"
                    >
                        Buat Retur
                    </a>

                <?php endif; ?>

                <?php if (
                    $is_selesai &&
                    ($pengadaan['status'] ?? '') === 'P' &&
                    $masih_ada_sisa
                ): ?>

                    <a
                        href="tambah.php?idpengadaan=<?= $idpengadaan; ?>"
                        class="btn btn-primary"
                    >
                        Penerimaan Berikutnya &rarr;
                    </a>

                <?php endif; ?>

            </div>

        </div>

    </section>

</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
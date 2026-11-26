<?php

require_once __DIR__ . '/helper.php';

$page_title = 'Penerimaan Barang';
$current_page = 'penerimaan';

$keyword = trim($_GET['keyword'] ?? '');

$sql = "SELECT *
        FROM v_penerimaan
        WHERE (
            CAST(idpenerimaan AS CHAR) LIKE ?
            OR CAST(idpengadaan AS CHAR) LIKE ?
            OR nama_vendor LIKE ?
            OR username LIKE ?
        )
        ORDER BY idpenerimaan DESC";

$stmt = $koneksi->prepare($sql);

$cari = '%' . $keyword . '%';

$stmt->bind_param(
    'ssss',
    $cari,
    $cari,
    $cari,
    $cari
);

$stmt->execute();

$result = $stmt->get_result();

require __DIR__ . '/../../includes/header.php';

?>

<div class="section-heading">

    <h2>Penerimaan Barang</h2>

    <p>
        Kelola transaksi penerimaan barang
        berdasarkan pengadaan dari vendor.
    </p>

</div>

<?php pn_tampil_flash(); ?>

<section class="panel">

    <div class="panel-header">

        <h2>Daftar Penerimaan</h2>

        <a href="tambah.php" class="btn btn-primary">
            + Buat Penerimaan
        </a>

    </div>

    <div class="panel-body">

        <form method="GET" class="search-form">

            <input
                type="text"
                name="keyword"
                class="form-control"
                placeholder="Cari ID, pengadaan, vendor atau user..."
                value="<?= pn_e($keyword); ?>"
            >

            <button type="submit" class="btn btn-primary">
                Cari
            </button>

            <a href="index.php" class="btn btn-secondary">
                Reset
            </a>

        </form>

        <div class="table-responsive">

            <table class="data-table">

                <thead>

                    <tr>
                        <th>No</th>
                        <th>ID Penerimaan</th>
                        <th>Tanggal</th>
                        <th>ID Pengadaan</th>
                        <th>Vendor</th>
                        <th>User</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if ($result->num_rows === 0): ?>

                        <tr>

                            <td colspan="8">
                                Belum ada data penerimaan.
                            </td>

                        </tr>

                    <?php endif; ?>

                    <?php $no = 1; ?>

                    <?php while ($row = $result->fetch_assoc()): ?>

                        <tr>

                            <td><?= $no++; ?></td>

                            <td>
                                PN-<?= str_pad(
                                    $row['idpenerimaan'],
                                    5,
                                    '0',
                                    STR_PAD_LEFT
                                ); ?>
                            </td>

                            <td>
                                <?= pn_e($row['created_at']); ?>
                            </td>

                            <td>

                                <a href="../pengadaan/detail.php?id=<?= (int) $row['idpengadaan']; ?>">

                                    PG-<?= str_pad(
                                        $row['idpengadaan'],
                                        5,
                                        '0',
                                        STR_PAD_LEFT
                                    ); ?>

                                </a>

                            </td>

                            <td>
                                <?= pn_e($row['nama_vendor']); ?>
                            </td>

                            <td>
                                <?= pn_e($row['username']); ?>
                            </td>

                            <td>

                                <?php if ($row['status'] === 'S'): ?>

                                    <span class="badge badge-success">
                                        Selesai
                                    </span>

                                <?php else: ?>

                                    <span class="badge badge-warning">
                                        Dalam Proses
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <a
                                    href="detail.php?id=<?= (int) $row['idpenerimaan']; ?>"
                                    class="btn btn-primary"
                                >
                                    Detail
                                </a>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                </tbody>

            </table>

        </div>

        <div class="form-note">

            Penerimaan barang dapat dilakukan secara
            bertahap sesuai jumlah pesanan yang belum
            diterima.

            Pengadaan akan selesai otomatis setelah
            seluruh barang diterima.

        </div>

    </div>

</section>

<?php

$stmt->close();

require __DIR__ . '/../../includes/footer.php';

?>
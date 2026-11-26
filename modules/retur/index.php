<?php

require_once __DIR__ . '/helper.php';

$page_title = 'Retur Barang';
$current_page = 'retur';

$keyword = trim($_GET['keyword'] ?? '');

$stmt = $koneksi->prepare(
    "SELECT *
     FROM v_retur
     WHERE (
        CAST(idretur AS CHAR) LIKE ?
        OR CAST(idpenerimaan AS CHAR) LIKE ?
        OR nama_vendor LIKE ?
        OR username LIKE ?
     )
     ORDER BY idretur DESC"
);

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

    <h2>Retur Barang</h2>

    <p>Kelola transaksi pengembalian barang kepada vendor.</p>

</div>

<?php rt_tampil_flash(); ?>

<section class="panel">

    <div class="panel-header">

        <h2>Daftar Retur</h2>

        <a href="tambah.php" class="btn btn-primary">
            + Buat Retur
        </a>

    </div>

    <div class="panel-body">

        <form method="GET" class="search-form">

            <input
                type="text"
                name="keyword"
                class="form-control"
                placeholder="Cari ID, penerimaan, vendor atau user..."
                value="<?= rt_e($keyword); ?>"
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
                        <th>ID Retur</th>
                        <th>Tanggal</th>
                        <th>ID Penerimaan</th>
                        <th>Vendor</th>
                        <th>User</th>
                        <th>Aksi</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if ($result->num_rows === 0): ?>

                        <tr>
                            <td colspan="7">
                                Belum ada data retur.
                            </td>
                        </tr>

                    <?php endif; ?>

                    <?php $no = 1; ?>

                    <?php while ($row = $result->fetch_assoc()): ?>

                        <tr>

                            <td><?= $no++; ?></td>

                            <td>
                                RT-<?= str_pad(
                                    $row['idretur'],
                                    5,
                                    '0',
                                    STR_PAD_LEFT
                                ); ?>
                            </td>

                            <td>
                                <?= rt_e($row['created_at']); ?>
                            </td>

                            <td>

                                <a href="../penerimaan/detail.php?id=<?= (int) $row['idpenerimaan']; ?>">

                                    PN-<?= str_pad(
                                        $row['idpenerimaan'],
                                        5,
                                        '0',
                                        STR_PAD_LEFT
                                    ); ?>

                                </a>

                            </td>

                            <td>
                                <?= rt_e($row['nama_vendor']); ?>
                            </td>

                            <td>
                                <?= rt_e($row['username']); ?>
                            </td>

                            <td>

                                <a
                                    href="detail.php?id=<?= (int) $row['idretur']; ?>"
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

            Retur hanya dapat dilakukan berdasarkan
            penerimaan yang sudah selesai.

            Stok barang berkurang secara otomatis
            setelah retur dikonfirmasi.

        </div>

    </div>

</section>

<?php

$stmt->close();

require __DIR__ . '/../../includes/footer.php';
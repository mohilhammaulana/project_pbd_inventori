<?php

require_once __DIR__ . '/helper.php';

$page_title = 'Penjualan Barang';
$current_page = 'penjualan';

$keyword = trim($_GET['keyword'] ?? '');

$stmt = $koneksi->prepare(
    "SELECT *
     FROM v_penjualan
     WHERE (
        CAST(idpenjualan AS CHAR) LIKE ?
        OR username LIKE ?
     )
     ORDER BY idpenjualan DESC"
);

$cari = '%' . $keyword . '%';

$stmt->bind_param('ss', $cari, $cari);
$stmt->execute();

$result = $stmt->get_result();

require __DIR__ . '/../../includes/header.php';

?>

<div class="section-heading">

    <h2>Penjualan Barang</h2>

    <p>Kelola transaksi penjualan barang kepada pelanggan.</p>

</div>

<?php pj_tampil_flash(); ?>

<section class="panel">

    <div class="panel-header">

        <h2>Daftar Penjualan</h2>

        <a href="tambah.php" class="btn btn-primary">
            + Buat Penjualan
        </a>

    </div>

    <div class="panel-body">

        <form method="GET" class="search-form">

            <input
                type="text"
                name="keyword"
                class="form-control"
                placeholder="Cari ID penjualan atau user..."
                value="<?= pj_e($keyword); ?>"
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
                        <th>ID Penjualan</th>
                        <th>Tanggal</th>
                        <th>User</th>
                        <th>Margin</th>
                        <th>Subtotal</th>
                        <th>PPN</th>
                        <th>Total</th>
                        <th>Aksi</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if ($result->num_rows === 0): ?>

                        <tr>
                            <td colspan="9">
                                Belum ada data penjualan.
                            </td>
                        </tr>

                    <?php endif; ?>

                    <?php $no = 1; ?>

                    <?php while ($row = $result->fetch_assoc()): ?>

                        <tr>

                            <td><?= $no++; ?></td>

                            <td>
                                PJ-<?= str_pad(
                                    $row['idpenjualan'],
                                    5,
                                    '0',
                                    STR_PAD_LEFT
                                ); ?>
                            </td>

                            <td>
                                <?= pj_e($row['created_at']); ?>
                            </td>

                            <td>
                                <?= pj_e($row['username']); ?>
                            </td>

                            <td>
                                <?= pj_e(
                                    $row['persen'] ??
                                    $row['margin']
                                ); ?>%
                            </td>

                            <td>
                                <?= pj_rupiah(
                                    $row['subtotal_nilai']
                                ); ?>
                            </td>

                            <td>
                                <?= pj_rupiah($row['ppn']); ?>
                            </td>

                            <td>
                                <?= pj_rupiah(
                                    $row['total_nilai']
                                ); ?>
                            </td>

                            <td>

                                <a
                                    href="detail.php?id=<?= (int) $row['idpenjualan']; ?>"
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

            Harga jual dihitung berdasarkan margin aktif.
            Stok barang berkurang secara otomatis setelah
            transaksi penjualan berhasil disimpan.

        </div>

    </div>

</section>

<?php

$stmt->close();

require __DIR__ . '/../../includes/footer.php';
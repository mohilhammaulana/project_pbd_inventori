<?php

require_once __DIR__ . '/../../auth/cek_role.php';
require_once __DIR__ . '/../../config/koneksi.php';

cek_role(['Admin']);

require_once __DIR__ . '/helper.php';

$page_title = 'Margin Penjualan';
$current_page = 'margin';

// Filter status
$status = $_GET['status'] ?? '';

if (!in_array($status, ['', '0', '1'], true)) {

    $status = '';
}

// Mengambil margin aktif
$margin_aktif = $koneksi->query(
    "SELECT idmargin_penjualan, persen
     FROM v_margin_penjualan
     WHERE status = 1
     ORDER BY idmargin_penjualan DESC
     LIMIT 1"
)->fetch_assoc();

// Mengambil daftar margin
$sql = "SELECT
            m.*,

            (
                SELECT COUNT(*)
                FROM penjualan p
                WHERE p.idmargin_penjualan =
                      m.idmargin_penjualan
            ) AS jumlah_penjualan

        FROM v_margin_penjualan m

        WHERE (? = '' OR m.status = ?)

        ORDER BY m.idmargin_penjualan DESC";

$stmt = $koneksi->prepare($sql);

$stmt->bind_param(
    'ss',
    $status,
    $status
);

$stmt->execute();

$result = $stmt->get_result();

// Notifikasi
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';

unset($_SESSION['success'], $_SESSION['error']);

require __DIR__ . '/../../includes/header.php';

?>

<div class="section-heading">

    <h2>Margin Penjualan</h2>

    <p>
        Kelola persentase tambahan harga jual barang STOKFLOW.
    </p>

</div>

<?php if ($success !== '') : ?>

    <div class="alert-success">
        <?= e($success); ?>
    </div>

<?php endif; ?>

<?php if ($error !== '') : ?>

    <div class="alert-error">
        <?= e($error); ?>
    </div>

<?php endif; ?>

<?php if ($margin_aktif) : ?>

    <div class="alert-success">

        Margin yang sedang berlaku:

        <strong>
            <?= e($margin_aktif['persen']); ?>%
        </strong>

    </div>

<?php else : ?>

    <div class="alert-error">

        Belum ada margin aktif.
        Aktifkan margin sebelum melakukan penjualan.

    </div>

<?php endif; ?>

<section class="panel">

    <div class="panel-header crud-toolbar">

        <div>

            <h2>Daftar Margin Penjualan</h2>

            <p>
                <?= $result->num_rows; ?> margin ditemukan
            </p>

        </div>

        <a href="tambah.php" class="btn btn-primary">

            + Tambah Margin

        </a>

    </div>

    <div class="filter-area">

        <form method="GET" class="filter-form">

            <select name="status" class="form-control">

                <option value="">
                    Semua Status
                </option>

                <option value="1"
                    <?= $status === '1' ? 'selected' : ''; ?>>
                    Aktif
                </option>

                <option value="0"
                    <?= $status === '0' ? 'selected' : ''; ?>>
                    Tidak Aktif
                </option>

            </select>

            <button type="submit" class="btn btn-primary">

                Cari

            </button>

            <a href="index.php" class="btn btn-secondary">

                Reset

            </a>

        </form>

    </div>

    <div class="table-responsive">

        <table class="data-table">

            <thead>

                <tr>

                    <th>No</th>
                    <th>ID Margin</th>
                    <th>Persentase</th>
                    <th>Tanggal Dibuat</th>
                    <th>Dibuat Oleh</th>
                    <th>Jumlah Penjualan</th>
                    <th>Status</th>
                    <th>Aksi</th>

                </tr>

            </thead>

            <tbody>

                <?php if ($result->num_rows > 0) : ?>

                    <?php $no = 1; ?>

                    <?php while ($row = $result->fetch_assoc()) : ?>

                        <tr>

                            <td><?= $no++; ?></td>

                            <td>

                                MRG-<?= str_pad(
                                    $row['idmargin_penjualan'],
                                    3,
                                    '0',
                                    STR_PAD_LEFT
                                ); ?>

                            </td>

                            <td>

                                <strong>

                                    <?= e($row['persen']); ?>%

                                </strong>

                            </td>

                            <td>

                                <?= e($row['created_at']); ?>

                            </td>

                            <td>

                                <?= e(
                                    $row['username']
                                    ?? $row['iduser']
                                    ?? '-'
                                ); ?>

                            </td>

                            <td>

                                <?= (int) $row['jumlah_penjualan']; ?>

                            </td>

                            <td>

                                <?php if ((int) $row['status'] === 1) : ?>

                                    <span class="status success">

                                        Aktif

                                    </span>

                                <?php else : ?>

                                    <span class="status danger">

                                        Tidak Aktif

                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <div class="action-buttons">

                                    <?php if ((int) $row['status'] === 0) : ?>

                                        <!-- AKTIFKAN MARGIN -->

                                        <form
                                            action="aktifkan.php"
                                            method="POST"
                                            onsubmit="return confirm('Aktifkan margin ini? Margin sebelumnya akan dinonaktifkan.');"
                                        >

                                            <input
                                                type="hidden"
                                                name="idmargin"
                                                value="<?= (int) $row['idmargin_penjualan']; ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e($_SESSION['csrf_token']); ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-primary btn-sm"
                                            >

                                                Aktifkan

                                            </button>

                                        </form>

                                        <!-- HAPUS PERMANEN -->

                                        <?php if (
                                            (int) $row['jumlah_penjualan'] === 0
                                        ) : ?>

                                            <form
                                                action="hapus.php"
                                                method="POST"
                                                onsubmit="return confirm('Hapus margin secara permanen?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="idmargin"
                                                    value="<?= (int) $row['idmargin_penjualan']; ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= e($_SESSION['csrf_token']); ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="btn btn-danger btn-sm"
                                                >

                                                    Hapus Permanen

                                                </button>

                                            </form>

                                        <?php else : ?>

                                            <span>

                                                Memiliki Riwayat

                                            </span>

                                        <?php endif; ?>

                                    <?php else : ?>

                                        <span>

                                            Sedang Digunakan

                                        </span>

                                    <?php endif; ?>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else : ?>

                    <tr>

                        <td colspan="8" class="empty-data">

                            Data margin tidak ditemukan.

                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>

<?php

$stmt->close();

require __DIR__ . '/../../includes/footer.php';

?>
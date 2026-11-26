<?php

require_once __DIR__ . '/../../auth/cek_role.php';
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/helper.php';

cek_role(['Admin', 'Gudang']);

$page_title = 'Data Satuan';
$current_page = 'satuan';

// Hak akses hapus permanen
$boleh_hapus_permanen = punya_akses(['Admin']);

// Token keamanan
if (empty($_SESSION['csrf_aksi_satuan'])) {

    $_SESSION['csrf_aksi_satuan'] =
        bin2hex(random_bytes(32));
}

// Mengambil filter
$keyword = $_GET['keyword'] ?? '';

$keyword = is_string($keyword)
    ? trim($keyword)
    : '';

$status = $_GET['status'] ?? '';

if (!in_array($status, ['', '0', '1'], true)) {

    $status = '';
}

$search = '%' . $keyword . '%';

// Mengambil data melalui View
$sql = "SELECT
            s.idsatuan,
            s.nama_satuan,
            s.status,

            (
                SELECT COUNT(*)
                FROM barang b
                WHERE b.idsatuan = s.idsatuan
            ) AS jumlah_barang

        FROM v_satuan s

        WHERE s.nama_satuan LIKE ?
        AND (? = '' OR s.status = ?)

        ORDER BY s.idsatuan DESC";

$stmt = $koneksi->prepare($sql);

$stmt->bind_param(
    'sss',
    $search,
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

    <h2>Data Satuan</h2>

    <p>Kelola data satuan barang STOKFLOW.</p>

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

<section class="panel">

    <div class="panel-header crud-toolbar">

        <div>

            <h2>Daftar Satuan</h2>

            <p>
                <?= $result->num_rows; ?> satuan ditemukan
            </p>

        </div>

        <a href="tambah.php" class="btn btn-primary">
            + Tambah Satuan
        </a>

    </div>

    <div class="filter-area">

        <form method="GET" class="filter-form">

            <input
                type="text"
                name="keyword"
                class="form-control"
                placeholder="Cari nama satuan..."
                value="<?= e($keyword); ?>"
            >

            <select name="status" class="form-control">

                <option value="">
                    Semua Status
                </option>

                <option
                    value="1"
                    <?= $status === '1' ? 'selected' : ''; ?>
                >
                    Aktif
                </option>

                <option
                    value="0"
                    <?= $status === '0' ? 'selected' : ''; ?>
                >
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
                    <th>ID Satuan</th>
                    <th>Nama Satuan</th>
                    <th>Jumlah Barang</th>
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

                                STN-<?= str_pad(
                                    $row['idsatuan'],
                                    3,
                                    '0',
                                    STR_PAD_LEFT
                                ); ?>

                            </td>

                            <td>

                                <?= e($row['nama_satuan']); ?>

                            </td>

                            <td>

                                <?= (int) $row['jumlah_barang']; ?>

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

                                    <!-- EDIT -->

                                    <a
                                        href="edit.php?id=<?= (int) $row['idsatuan']; ?>"
                                        class="btn btn-warning btn-sm"
                                    >
                                        Edit
                                    </a>

                                    <!-- SOFT DELETE -->

                                    <?php if ((int) $row['status'] === 1) : ?>

                                        <form
                                            action="hapus.php"
                                            method="POST"
                                            onsubmit="return confirm('Nonaktifkan satuan ini? Barang aktif yang menggunakan satuan ini akan ikut nonaktif.');"
                                        >

                                            <input
                                                type="hidden"
                                                name="idsatuan"
                                                value="<?= (int) $row['idsatuan']; ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e($_SESSION['csrf_aksi_satuan']); ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="aksi"
                                                value="nonaktifkan"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-danger btn-sm"
                                            >
                                                Nonaktifkan
                                            </button>

                                        </form>

                                    <?php else : ?>

                                        <!-- AKTIFKAN KEMBALI -->

                                        <form
                                            action="hapus.php"
                                            method="POST"
                                            onsubmit="return confirm('Aktifkan kembali satuan ini?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="idsatuan"
                                                value="<?= (int) $row['idsatuan']; ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e($_SESSION['csrf_aksi_satuan']); ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="aksi"
                                                value="aktifkan"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-primary btn-sm"
                                            >
                                                Aktifkan
                                            </button>

                                        </form>

                                    <?php endif; ?>

                                    <!-- HARD DELETE -->

                                    <?php if ($boleh_hapus_permanen) : ?>

                                        <form
                                            action="hapus.php"
                                            method="POST"
                                            onsubmit="return confirm('Yakin ingin menghapus satuan secara permanen? Data tidak dapat dikembalikan.');"
                                        >

                                            <input
                                                type="hidden"
                                                name="idsatuan"
                                                value="<?= (int) $row['idsatuan']; ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e($_SESSION['csrf_aksi_satuan']); ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="aksi"
                                                value="hapus_permanen"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-danger btn-sm"
                                            >
                                                Hapus Permanen
                                            </button>

                                        </form>

                                    <?php endif; ?>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else : ?>

                    <tr>

                        <td colspan="6" class="empty-data">

                            Data satuan tidak ditemukan.

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
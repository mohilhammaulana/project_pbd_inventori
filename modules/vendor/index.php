<?php

require_once __DIR__ . '/../../auth/cek_role.php';
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/helper.php';

cek_role(['Admin', 'Gudang']);

$page_title = 'Data Vendor';
$current_page = 'vendor';

// Hak akses hapus permanen
$boleh_hapus_permanen = punya_akses(['Admin']);

// Token keamanan
if (empty($_SESSION['csrf_aksi_vendor'])) {

    $_SESSION['csrf_aksi_vendor'] =
        bin2hex(random_bytes(32));
}

// Fungsi keamanan tampilan
function vd_e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Mengambil filter
$keyword = $_GET['keyword'] ?? '';

$keyword = is_string($keyword)
    ? trim($keyword)
    : '';

$status = $_GET['status'] ?? '';

if (!in_array($status, ['', 'A', 'N'], true)) {

    $status = '';
}

$search = '%' . $keyword . '%';

// Mengambil data melalui SQL View
$sql = "SELECT *
        FROM v_vendor
        WHERE nama_vendor LIKE ?
        AND (? = '' OR status = ?)
        ORDER BY idvendor DESC";

$stmt = $koneksi->prepare($sql);

$stmt->bind_param(
    'sss',
    $search,
    $status,
    $status
);

$stmt->execute();

$result = $stmt->get_result();

// Mengambil pesan notifikasi
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';

unset($_SESSION['success'], $_SESSION['error']);

require __DIR__ . '/../../includes/header.php';

?>

<div class="section-heading">

    <h2>Data Vendor</h2>

    <p>
        Kelola data vendor pemasok barang STOKFLOW.
    </p>

</div>

<?php if ($success !== '') : ?>

    <div class="alert-success">

        <?= vd_e($success); ?>

    </div>

<?php endif; ?>

<?php if ($error !== '') : ?>

    <div class="alert-error">

        <?= vd_e($error); ?>

    </div>

<?php endif; ?>

<section class="panel">

    <div class="panel-header crud-toolbar">

        <div>

            <h2>Daftar Vendor</h2>

            <p>
                <?= $result->num_rows; ?> vendor ditemukan
            </p>

        </div>

        <a href="tambah.php" class="btn btn-primary">
            + Tambah Vendor
        </a>

    </div>

    <div class="filter-area">

        <form method="GET" class="filter-form">

            <input
                type="text"
                name="keyword"
                class="form-control"
                placeholder="Cari nama vendor..."
                value="<?= vd_e($keyword); ?>"
            >

            <select name="status" class="form-control">

                <option value="">
                    Semua Status
                </option>

                <option
                    value="A"
                    <?= $status === 'A' ? 'selected' : ''; ?>
                >
                    Aktif
                </option>

                <option
                    value="N"
                    <?= $status === 'N' ? 'selected' : ''; ?>
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
                    <th>ID Vendor</th>
                    <th>Nama Vendor</th>
                    <th>Badan Hukum</th>
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

                                VND-<?= str_pad(
                                    $row['idvendor'],
                                    3,
                                    '0',
                                    STR_PAD_LEFT
                                ); ?>

                            </td>

                            <td>

                                <?= vd_e($row['nama_vendor']); ?>

                            </td>

                            <td>

                                <?= $row['badan_hukum'] === 'Y'
                                    ? 'Badan Hukum'
                                    : 'Perorangan'; ?>

                            </td>

                            <td>

                                <?php if ($row['status'] === 'A') : ?>

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
                                        href="edit.php?id=<?= (int) $row['idvendor']; ?>"
                                        class="btn btn-warning btn-sm"
                                    >
                                        Edit
                                    </a>

                                    <!-- SOFT DELETE -->

                                    <?php if ($row['status'] === 'A') : ?>

                                        <form
                                            action="hapus.php"
                                            method="POST"
                                            onsubmit="return confirm('Nonaktifkan vendor ini?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="idvendor"
                                                value="<?= (int) $row['idvendor']; ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= vd_e($_SESSION['csrf_aksi_vendor']); ?>"
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
                                            onsubmit="return confirm('Aktifkan kembali vendor ini?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="idvendor"
                                                value="<?= (int) $row['idvendor']; ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= vd_e($_SESSION['csrf_aksi_vendor']); ?>"
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
                                            onsubmit="return confirm('Yakin ingin menghapus vendor secara permanen? Data tidak dapat dikembalikan.');"
                                        >

                                            <input
                                                type="hidden"
                                                name="idvendor"
                                                value="<?= (int) $row['idvendor']; ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= vd_e($_SESSION['csrf_aksi_vendor']); ?>"
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

                            Data vendor tidak ditemukan.

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
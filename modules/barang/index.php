<?php

require_once __DIR__ . '/../../auth/cek_role.php';
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/helper.php';

cek_role(['Admin', 'Gudang', 'Kasir']);

$page_title = 'Data Barang';
$current_page = 'barang';

// Hak akses pengelolaan barang
$boleh_kelola = punya_akses(['Admin', 'Gudang']);

// Hapus permanen khusus Admin
$boleh_hapus_permanen = punya_akses(['Admin']);

// Token keamanan untuk tindakan terhadap barang
if (empty($_SESSION['csrf_aksi_barang'])) {

    $_SESSION['csrf_aksi_barang'] =
        bin2hex(random_bytes(32));
}

// Filter pencarian dan status
$keyword = $_GET['keyword'] ?? '';

$keyword = is_string($keyword)
    ? trim($keyword)
    : '';

$status = $_GET['status'] ?? '';

if (!in_array($status, ['', '0', '1'], true)) {
    $status = '';
}

// Kasir hanya dapat melihat barang aktif
if (!$boleh_kelola) {
    $status = '1';
}

$search = '%' . $keyword . '%';

$sql = "SELECT *
        FROM v_barang
        WHERE (
            nama LIKE ?
            OR nama_satuan LIKE ?
        )
        AND (? = '' OR status = ?)
        ORDER BY idbarang DESC";

$stmt = $koneksi->prepare($sql);

$stmt->bind_param(
    "ssss",
    $search,
    $search,
    $status,
    $status
);

$stmt->execute();

$result = $stmt->get_result();

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';

unset($_SESSION['success'], $_SESSION['error']);

require __DIR__ . '/../../includes/header.php';

?>

<div class="section-heading">

    <h2>Data Barang</h2>

    <p>Kelola dan pantau seluruh data barang STOKFLOW.</p>

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

            <h2>Daftar Barang</h2>

            <p>
                <?= $result->num_rows; ?> barang ditemukan
            </p>

        </div>

        <?php if ($boleh_kelola) : ?>

            <a href="tambah.php" class="btn btn-primary">
                + Tambah Barang
            </a>

        <?php endif; ?>

    </div>

    <div class="filter-area">

        <form method="GET" class="filter-form">

            <input
                type="text"
                name="keyword"
                class="form-control"
                placeholder="Cari nama barang atau satuan..."
                value="<?= e($keyword); ?>"
            >

            <?php if ($boleh_kelola) : ?>

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

            <?php endif; ?>

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
                    <th>ID Barang</th>
                    <th>Nama Barang</th>
                    <th>Jenis</th>
                    <th>Satuan</th>
                    <th>Harga Pokok</th>
                    <th>Stok</th>
                    <th>Status</th>

                    <?php if ($boleh_kelola) : ?>

                        <th>Aksi</th>

                    <?php endif; ?>

                </tr>

            </thead>

            <tbody>

                <?php if ($result->num_rows > 0) : ?>

                    <?php $no = 1; ?>

                    <?php while ($row = $result->fetch_assoc()) : ?>

                        <tr>

                            <td><?= $no++; ?></td>

                            <td>

                                BRG-<?= str_pad(
                                    $row['idbarang'],
                                    3,
                                    '0',
                                    STR_PAD_LEFT
                                ); ?>

                            </td>

                            <td><?= e($row['nama']); ?></td>

                            <td>

                                <?= $row['jenis'] === 'M'
                                    ? 'Makanan'
                                    : 'Non-Makanan'; ?>

                            </td>

                            <td>
                                <?= e($row['nama_satuan']); ?>
                            </td>

                            <td>

                                Rp <?= number_format(
                                    $row['harga'],
                                    0,
                                    ',',
                                    '.'
                                ); ?>

                            </td>

                            <td><?= (int) $row['stok']; ?></td>

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

                            <?php if ($boleh_kelola) : ?>

                                <td>

                                    <div class="action-buttons">

                                        <!-- TOMBOL EDIT -->

                                        <a
                                            href="edit.php?id=<?= (int) $row['idbarang']; ?>"
                                            class="btn btn-warning btn-sm"
                                        >
                                            Edit
                                        </a>

                                        <!-- SOFT DELETE DAN AKTIFKAN -->

                                        <?php if ((int) $row['status'] === 1) : ?>

                                            <form
                                                action="hapus.php"
                                                method="POST"
                                                onsubmit="return confirm('Nonaktifkan barang ini?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="idbarang"
                                                    value="<?= (int) $row['idbarang']; ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= e($_SESSION['csrf_aksi_barang']); ?>"
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

                                            <form
                                                action="hapus.php"
                                                method="POST"
                                                onsubmit="return confirm('Aktifkan kembali barang ini?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="idbarang"
                                                    value="<?= (int) $row['idbarang']; ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= e($_SESSION['csrf_aksi_barang']); ?>"
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

                                            <?php if (
                                                (int) ($row['nonaktif_karena_satuan'] ?? 0) === 1
                                            ) : ?>

                                                <form
                                                    action="hapus.php"
                                                    method="POST"
                                                    onsubmit="return confirm('Tetapkan barang sebagai nonaktif manual?');"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="idbarang"
                                                        value="<?= (int) $row['idbarang']; ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= e($_SESSION['csrf_aksi_barang']); ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="aksi"
                                                        value="nonaktifkan"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="btn btn-secondary btn-sm"
                                                    >
                                                        Tetapkan Nonaktif
                                                    </button>

                                                </form>

                                            <?php endif; ?>

                                        <?php endif; ?>

                                        <!-- HARD DELETE KHUSUS ADMIN -->

                                        <?php if ($boleh_hapus_permanen) : ?>

                                            <form
                                                action="hapus.php"
                                                method="POST"
                                                onsubmit="return confirm('Yakin ingin menghapus barang secara permanen? Data tidak dapat dikembalikan.');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="idbarang"
                                                    value="<?= (int) $row['idbarang']; ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= e($_SESSION['csrf_aksi_barang']); ?>"
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

                            <?php endif; ?>

                        </tr>

                    <?php endwhile; ?>

                <?php else : ?>

                    <tr>

                        <td
                            colspan="<?= $boleh_kelola ? 9 : 8; ?>"
                            class="empty-data"
                        >
                            Data barang tidak ditemukan.
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
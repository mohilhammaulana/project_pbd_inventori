<?php

require_once __DIR__ . '/../../auth/cek_role.php';
require_once __DIR__ . '/../../config/koneksi.php';

cek_role(['Admin']);

$page_title = 'Data User';
$current_page = 'user';

if (empty($_SESSION['csrf_aksi_user'])) {

    $_SESSION['csrf_aksi_user'] =
        bin2hex(random_bytes(32));
}

function usr_e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

$keyword = $_GET['keyword'] ?? '';

$keyword = is_string($keyword)
    ? trim($keyword)
    : '';

$status = $_GET['status'] ?? '';

if (!in_array($status, ['', '0', '1'], true)) {
    $status = '';
}

$search = '%' . $keyword . '%';

// Menggunakan SQL View
$sql = "SELECT *
        FROM v_user

        WHERE (
            username LIKE ?
            OR nama_role LIKE ?
        )

        AND (? = '' OR status = ?)

        ORDER BY iduser DESC";

$stmt = $koneksi->prepare($sql);

$stmt->bind_param(
    'ssss',
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

    <h2>Data User</h2>

    <p>
        Kelola akun pengguna dan hak akses STOKFLOW.
    </p>

</div>

<?php if ($success !== '') : ?>

    <div class="alert-success">
        <?= usr_e($success); ?>
    </div>

<?php endif; ?>

<?php if ($error !== '') : ?>

    <div class="alert-error">
        <?= usr_e($error); ?>
    </div>

<?php endif; ?>

<section class="panel">

    <div class="panel-header crud-toolbar">

        <div>

            <h2>Daftar User</h2>

            <p>
                <?= $result->num_rows; ?> user ditemukan
            </p>

        </div>

        <a href="tambah.php" class="btn btn-primary">
            + Tambah User
        </a>

    </div>

    <div class="filter-area">

        <form method="GET" class="filter-form">

            <input
                type="text"
                name="keyword"
                class="form-control"
                placeholder="Cari username atau role..."
                value="<?= usr_e($keyword); ?>"
            >

            <select name="status" class="form-control">

                <option value="">Semua Status</option>

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
                    <th>ID User</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Aksi</th>

                </tr>

            </thead>

            <tbody>

                <?php if ($result->num_rows > 0) : ?>

                    <?php $no = 1; ?>

                    <?php while ($row = $result->fetch_assoc()) : ?>

                        <?php

                        $akun_sendiri =
                            (int) $row['iduser'] ===
                            (int) $_SESSION['iduser'];

                        $akun_admin =
                            $row['nama_role'] === 'Admin';

                        $akun_dilindungi =
                            $akun_sendiri || $akun_admin;

                        ?>

                        <tr>

                            <td><?= $no++; ?></td>

                            <td>

                                USR-<?= str_pad(
                                    $row['iduser'],
                                    3,
                                    '0',
                                    STR_PAD_LEFT
                                ); ?>

                            </td>

                            <td>

                                <?= usr_e($row['username']); ?>

                            </td>

                            <td>

                                <?= usr_e($row['nama_role']); ?>

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
                                        href="edit.php?id=<?= (int) $row['iduser']; ?>"
                                        class="btn btn-warning btn-sm"
                                    >
                                        Edit
                                    </a>

                                    <?php if (!$akun_dilindungi) : ?>

                                        <!-- SOFT DELETE / AKTIFKAN -->

                                        <form
                                            action="hapus.php"
                                            method="POST"
                                            onsubmit="return confirm('Ubah status user ini?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="iduser"
                                                value="<?= (int) $row['iduser']; ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= usr_e($_SESSION['csrf_aksi_user']); ?>"
                                            >

                                            <?php if ((int) $row['status'] === 1) : ?>

                                                <button
                                                    type="submit"
                                                    name="aksi"
                                                    value="nonaktifkan"
                                                    class="btn btn-danger btn-sm"
                                                >
                                                    Nonaktifkan
                                                </button>

                                            <?php else : ?>

                                                <button
                                                    type="submit"
                                                    name="aksi"
                                                    value="aktifkan"
                                                    class="btn btn-primary btn-sm"
                                                >
                                                    Aktifkan
                                                </button>

                                            <?php endif; ?>

                                        </form>

                                        <!-- HARD DELETE -->

                                        <form
                                            action="hapus.php"
                                            method="POST"
                                            onsubmit="return confirm('Hapus user secara permanen? Data tidak dapat dikembalikan.');"
                                        >

                                            <input
                                                type="hidden"
                                                name="iduser"
                                                value="<?= (int) $row['iduser']; ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= usr_e($_SESSION['csrf_aksi_user']); ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="aksi"
                                                value="hapus_permanen"
                                                class="btn btn-danger btn-sm"
                                            >
                                                Hapus Permanen
                                            </button>

                                        </form>

                                    <?php else : ?>

                                        <span>
                                            Dilindungi
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else : ?>

                    <tr>

                        <td colspan="6" class="empty-data">
                            Data user tidak ditemukan.
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
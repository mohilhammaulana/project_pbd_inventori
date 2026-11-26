<?php

require_once __DIR__ . '/helper.php';

$page_title = 'Data Role';
$current_page = 'role';

$keyword = trim($_GET['keyword'] ?? '');

$sql = "SELECT *
        FROM v_role
        WHERE nama_role LIKE ?
        ORDER BY idrole ASC";

$stmt = $koneksi->prepare($sql);

$search = '%' . $keyword . '%';

$stmt->bind_param("s", $search);
$stmt->execute();

$data_role = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt->close();

require __DIR__ . '/../../includes/header.php';

?>

<style>
.role-container {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 24px;
}

.role-heading {
    margin-bottom: 20px;
}

.role-heading h1 {
    font-size: 24px;
    margin-bottom: 6px;
    color: #0f172a;
}

.role-heading p {
    font-size: 14px;
    color: #64748b;
}

.role-search {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.role-search input {
    width: 280px;
    max-width: 100%;
    padding: 10px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 14px;
}

.role-table-wrapper {
    overflow-x: auto;
}

.role-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.role-table th {
    background: #f1f5f9;
    color: #334155;
    font-weight: 600;
    text-align: left;
    padding: 14px;
    border-bottom: 1px solid #e2e8f0;
}

.role-table td {
    padding: 14px;
    color: #334155;
    border-bottom: 1px solid #e2e8f0;
}

.role-table tbody tr:hover {
    background: #f8fafc;
}

.role-info {
    margin-top: 18px;
    color: #64748b;
    font-size: 13px;
    line-height: 1.6;
}
</style>

<div class="role-heading">

    <h1>Data Role</h1>

    <p>Daftar hak akses pengguna sistem STOKFLOW.</p>

</div>

<div class="role-container">

    <form method="GET" class="role-search">

        <input
            type="text"
            name="keyword"
            placeholder="Cari nama role..."
            value="<?= escape_role($keyword); ?>"
        >

        <button type="submit" class="btn btn-primary">
            Cari
        </button>

        <a href="index.php" class="btn btn-secondary">
            Reset
        </a>

    </form>

    <div class="role-table-wrapper">

        <table class="role-table">

            <thead>
                <tr>
                    <th>No</th>
                    <th>ID Role</th>
                    <th>Nama Role</th>
                    <th>Jumlah User</th>
                    <th>Keterangan</th>
                </tr>
            </thead>

            <tbody>

                <?php if (empty($data_role)): ?>

                    <tr>
                        <td colspan="5"
                            style="text-align: center;">
                            Data role tidak ditemukan.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php $no = 1; ?>

                    <?php foreach ($data_role as $role): ?>

                        <tr>

                            <td><?= $no++; ?></td>

                            <td>
                                <?= escape_role(
                                    format_id_role($role['idrole'])
                                ); ?>
                            </td>

                            <td>
                                <?= escape_role($role['nama_role']); ?>
                            </td>

                            <td>
                                <?= (int) $role['jumlah_user']; ?>
                            </td>

                            <td>
                                <?= escape_role(
                                    deskripsi_role($role['nama_role'])
                                ); ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

    <div class="role-info">
        Menampilkan <?= count($data_role); ?> data role.
    </div>

</div>

<?php

require __DIR__ . '/../../includes/footer.php';

?>
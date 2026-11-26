<?php

require_once __DIR__ . '/helper.php';

$page_title = 'Pengadaan Barang';
$current_page = 'pengadaan';

// =========================================
// FILTER PENGADAAN
// =========================================

$keyword = is_string($_GET['keyword'] ?? null)
    ? trim($_GET['keyword'])
    : '';

$status = is_string($_GET['status'] ?? null)
    ? $_GET['status']
    : '';

if (!in_array(
    $status,
    ['', 'P', 'S', 'B'],
    true
)) {
    $status = '';
}

// =========================================
// MENGAMBIL DATA PENGADAAN
// =========================================

$sql = "SELECT
            p.*,

            EXISTS (
                SELECT 1
                FROM penerimaan pn
                WHERE pn.idpengadaan = p.idpengadaan
            ) AS sudah_penerimaan

        FROM v_pengadaan p

        WHERE (
            p.nama_vendor LIKE ?
            OR CAST(p.idpengadaan AS CHAR) LIKE ?
            OR p.username LIKE ?
        )

        AND (? = '' OR p.status = ?)

        ORDER BY p.idpengadaan DESC";

$stmt = $koneksi->prepare($sql);

$cari = '%' . $keyword . '%';

$stmt->bind_param(
    'sssss',
    $cari,
    $cari,
    $cari,
    $status,
    $status
);

$stmt->execute();

$result = $stmt->get_result();

require __DIR__ . '/../../includes/header.php';

?>

<div class="section-heading">

    <h2>Pengadaan Barang</h2>

    <p>
        Kelola transaksi pemesanan barang kepada vendor.
    </p>

</div>

<?php pg_tampil_flash(); ?>

<section class="panel">

    <div class="panel-header">

        <h2>Daftar Pengadaan</h2>

        <a
            href="tambah.php"
            class="btn btn-primary"
        >
            + Buat Pengadaan
        </a>

    </div>

    <div class="panel-body">

        <!-- FILTER DATA -->

        <form method="GET" class="search-form">

            <input
                type="text"
                name="keyword"
                class="form-control"
                placeholder="Cari ID, vendor atau user..."
                value="<?= pg_e($keyword); ?>"
            >

            <select
                name="status"
                class="form-control"
            >

                <option value="">
                    Semua Status
                </option>

                <option
                    value="P"
                    <?= $status === 'P'
                        ? 'selected' : ''; ?>
                >
                    Dalam Proses
                </option>

                <option
                    value="S"
                    <?= $status === 'S'
                        ? 'selected' : ''; ?>
                >
                    Selesai
                </option>

                <option
                    value="B"
                    <?= $status === 'B'
                        ? 'selected' : ''; ?>
                >
                    Dibatalkan
                </option>

            </select>

            <button
                type="submit"
                class="btn btn-primary"
            >
                Cari
            </button>

            <a
                href="index.php"
                class="btn btn-secondary"
            >
                Reset
            </a>

        </form>

        <!-- TABEL PENGADAAN -->

        <div class="table-responsive">

            <table class="data-table">

                <thead>

                    <tr>

                        <th>No</th>
                        <th>ID Pengadaan</th>
                        <th>Tanggal</th>
                        <th>Vendor</th>
                        <th>User</th>
                        <th>Subtotal</th>
                        <th>PPN</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Aksi</th>

                    </tr>

                </thead>

                <tbody>

                    <?php if ($result->num_rows === 0): ?>

                        <tr>

                            <td colspan="10">

                                Tidak ada data pengadaan.

                            </td>

                        </tr>

                    <?php endif; ?>

                    <?php $no = 1; ?>

                    <?php while (
                        $row = $result->fetch_assoc()
                    ): ?>

                        <tr>

                            <td>
                                <?= $no++; ?>
                            </td>

                            <td>

                                PG-<?= str_pad(
                                    $row['idpengadaan'],
                                    5,
                                    '0',
                                    STR_PAD_LEFT
                                ); ?>

                            </td>

                            <td>

                                <?= pg_e(
                                    $row['timestamp']
                                ); ?>

                            </td>

                            <td>

                                <?= pg_e(
                                    $row['nama_vendor']
                                ); ?>

                            </td>

                            <td>

                                <?= pg_e(
                                    $row['username']
                                ); ?>

                            </td>

                            <td>

                                <?= pg_rupiah(
                                    $row['subtotal_nilai']
                                ); ?>

                            </td>

                            <td>

                                <?= pg_rupiah(
                                    $row['ppn']
                                ); ?>

                            </td>

                            <td>

                                <?= pg_rupiah(
                                    $row['total_nilai']
                                ); ?>

                            </td>

                            <!-- STATUS -->

                            <td>

                                <?php if (
                                    $row['status'] === 'S'
                                ): ?>

                                    <span class="badge badge-success">
                                        Selesai
                                    </span>

                                <?php elseif (
                                    $row['status'] === 'B'
                                ): ?>

                                    <span class="tx-status tx-status-danger">
                                        Dibatalkan
                                    </span>

                                <?php else: ?>

                                    <span class="badge badge-warning">
                                        Dalam Proses
                                    </span>

                                <?php endif; ?>

                            </td>

                            <!-- TOMBOL AKSI -->

                            <td>

                                <div class="action-buttons">

                                    <a
                                        href="detail.php?id=<?= (int)
                                            $row['idpengadaan']; ?>"
                                        class="btn btn-primary btn-sm"
                                    >
                                        Detail
                                    </a>

                                    <?php if (
                                        $row['status'] === 'P'
                                        &&
                                        (int) $row['sudah_penerimaan'] === 0
                                    ): ?>

                                        <form
                                            method="POST"
                                            action="batal.php"
                                            onsubmit="return confirm(
                                                'Yakin ingin membatalkan pengadaan ini?'
                                            );"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= pg_e(
                                                    $_SESSION['csrf_pengadaan']
                                                ); ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="idpengadaan"
                                                value="<?= (int)
                                                    $row['idpengadaan']; ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-danger btn-sm"
                                            >
                                                Batalkan
                                            </button>

                                        </form>

                                    <?php endif; ?>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                </tbody>

            </table>

        </div>

        <div class="form-note">

            Pengadaan akan selesai secara otomatis
            setelah seluruh barang diterima.

            Pengadaan hanya dapat dibatalkan apabila
            belum memiliki transaksi penerimaan.

            Data pesanan yang dibatalkan tetap tersimpan
            sebagai riwayat pengadaan.

        </div>

    </div>

</section>

<?php

$stmt->close();

require __DIR__ . '/../../includes/footer.php';

?>
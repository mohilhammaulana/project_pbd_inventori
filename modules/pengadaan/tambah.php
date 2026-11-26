<?php

require_once __DIR__ . '/helper.php';

$page_title = 'Buat Pengadaan';
$current_page = 'pengadaan';

$errors = [];
$idvendor = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    pg_validasi_post();

    $idvendor = (int) ($_POST['idvendor'] ?? 0);
    $iduser = (int) $_SESSION['iduser'];

    if ($idvendor <= 0) {
        $errors[] = 'Vendor wajib dipilih.';
    }

    if (empty($errors)) {

        try {

            $stmt = $koneksi->prepare(
                "SELECT idvendor
                 FROM vendor
                 WHERE idvendor = ?
                 AND status = 'A'"
            );

            $stmt->bind_param('i', $idvendor);
            $stmt->execute();

            $vendor_valid = $stmt->get_result()->fetch_assoc();

            $stmt->close();

            if (!$vendor_valid) {
                throw new RuntimeException(
                    'Vendor tidak tersedia atau tidak aktif.'
                );
            }

            $stmt = $koneksi->prepare(
                "CALL sp_buat_pengadaan(?, ?, @idpengadaan)"
            );

            $stmt->bind_param('ii', $iduser, $idvendor);

            $stmt->execute();
            $stmt->close();

            while ($koneksi->more_results()) {

                $koneksi->next_result();

                if ($res = $koneksi->store_result()) {
                    $res->free();
                }
            }

            $hasil = $koneksi->query(
                "SELECT @idpengadaan AS id"
            )->fetch_assoc();

            $idpengadaan = (int) $hasil['id'];

            if ($idpengadaan <= 0) {
                throw new RuntimeException(
                    'ID pengadaan tidak berhasil diperoleh.'
                );
            }

            pg_flash(
                'success',
                'Pengadaan berhasil dibuat. Silakan tambahkan barang.'
            );

            pg_redirect('detail.php?id=' . $idpengadaan);

        } catch (RuntimeException $e) {

            error_log($e->getMessage());

            $errors[] = 'Pengadaan gagal dibuat. Periksa vendor dan database.';
        }
    }
}

$vendors = $koneksi->query(
    "SELECT idvendor, nama_vendor
     FROM v_vendor
     WHERE status = 'A'
     ORDER BY nama_vendor"
);

require __DIR__ . '/../../includes/header.php';

?>

<div class="section-heading">

    <h2>Buat Pengadaan</h2>

    <p>Pilih vendor untuk memulai transaksi pengadaan.</p>

</div>

<section class="panel form-panel">

    <div class="panel-header">
        <h2>Form Pengadaan</h2>
    </div>

    <div class="panel-body">

        <?php foreach ($errors as $error): ?>

            <div class="alert-error">
                <?= pg_e($error); ?>
            </div>

        <?php endforeach; ?>

        <form method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= pg_e($_SESSION['csrf_pengadaan']); ?>"
            >

            <div class="form-group">

                <label>Vendor</label>

                <select
                    name="idvendor"
                    class="form-control"
                    required
                >

                    <option value="">-- Pilih Vendor --</option>

                    <?php while ($vendor = $vendors->fetch_assoc()): ?>

                        <option
                            value="<?= (int) $vendor['idvendor']; ?>"
                            <?= $idvendor == $vendor['idvendor']
                                ? 'selected' : ''; ?>
                        >
                            <?= pg_e($vendor['nama_vendor']); ?>
                        </option>

                    <?php endwhile; ?>

                </select>

            </div>

            <div class="form-group">

                <label>Dibuat Oleh</label>

                <input
                    type="text"
                    class="form-control"
                    value="<?= pg_e($_SESSION['username']); ?>"
                    readonly
                >

            </div>

            <div class="form-actions">

                <button type="submit" class="btn btn-primary">
                    Buat Pengadaan
                </button>

                <a href="index.php" class="btn btn-secondary">
                    Batal
                </a>

            </div>

        </form>

    </div>

</section>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
<?php

if (!isset($data, $errors, $page_title)) {
    http_response_code(403);
    exit("Akses tidak diizinkan.");
}

require __DIR__ . '/../../includes/header.php';

?>

<div class="section-heading">

    <h2><?= e($page_title); ?></h2>

    <p>Lengkapi informasi satuan barang.</p>

</div>

<section class="panel form-panel">

    <div class="panel-header">

        <h2>Form <?= e($page_title); ?></h2>

        <p>Masukkan nama dan status satuan.</p>

    </div>

    <div class="panel-body">

        <?php foreach ($errors as $error) : ?>

            <div class="alert-error">
                <?= e($error); ?>
            </div>

        <?php endforeach; ?>

        <form method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($_SESSION['csrf_token']); ?>"
            >

            <div class="form-group">

                <label for="nama_satuan">
                    Nama Satuan
                </label>

                <input
                    type="text"
                    name="nama_satuan"
                    id="nama_satuan"
                    class="form-control"
                    maxlength="45"
                    placeholder="Contoh: Dus"
                    value="<?= e($data['nama_satuan']); ?>"
                    required
                >

            </div>

            <div class="form-group">

                <label for="status">
                    Status Satuan
                </label>

                <select
                    name="status"
                    id="status"
                    class="form-control"
                    required
                >

                    <option value="1"
                        <?= $data['status'] == 1
                            ? 'selected' : ''; ?>>
                        Aktif
                    </option>

                    <option value="0"
                        <?= $data['status'] == 0
                            ? 'selected' : ''; ?>>
                        Tidak Aktif
                    </option>

                </select>

            </div>

            <div class="form-note">

            Satuan aktif dapat digunakan pada data barang.
            Satuan yang dinonaktifkan tetap tersimpan
            dalam database dan tidak menghapus data
            barang yang menggunakannya.

            </div>

            <div class="form-actions">

                <button type="submit" class="btn btn-primary">
                    Simpan Satuan
                </button>

                <a href="index.php" class="btn btn-secondary">
                    Batal
                </a>

            </div>

        </form>

    </div>

</section>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
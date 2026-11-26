<?php

if (!isset($data, $errors, $page_title)) {
    http_response_code(403);
    exit("Akses tidak diizinkan.");
}

require __DIR__ . '/../../includes/header.php';

?>

<div class="section-heading">

    <h2><?= e($page_title); ?></h2>

    <p>Lengkapi informasi vendor STOKFLOW.</p>

</div>

<section class="panel form-panel">

    <div class="panel-header">

        <h2>Form <?= e($page_title); ?></h2>

        <p>Pastikan data yang dimasukkan sudah benar.</p>

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

                <label for="nama_vendor">
                    Nama Vendor
                </label>

                <input
                    type="text"
                    name="nama_vendor"
                    id="nama_vendor"
                    class="form-control"
                    maxlength="100"
                    placeholder="Contoh: PT Sumber Pangan"
                    value="<?= e($data['nama_vendor']); ?>"
                    required
                >

            </div>

            <div class="form-group">

                <label for="badan_hukum">
                    Badan Hukum
                </label>

                <select
                    name="badan_hukum"
                    id="badan_hukum"
                    class="form-control"
                    required
                >

                    <option value="Y"
                        <?= $data['badan_hukum'] === 'Y'
                            ? 'selected' : ''; ?>>
                        Berbadan Hukum
                    </option>

                    <option value="N"
                        <?= $data['badan_hukum'] === 'N'
                            ? 'selected' : ''; ?>>
                        Tidak Berbadan Hukum
                    </option>

                </select>

            </div>

            <div class="form-group">

                <label for="status">
                    Status Vendor
                </label>

                <select
                    name="status"
                    id="status"
                    class="form-control"
                    required
                >

                    <option value="A"
                        <?= $data['status'] === 'A'
                            ? 'selected' : ''; ?>>
                        Aktif
                    </option>

                    <option value="N"
                        <?= $data['status'] === 'N'
                            ? 'selected' : ''; ?>>
                        Tidak Aktif
                    </option>

                </select>

            </div>

            <div class="form-actions">

                <button type="submit" class="btn btn-primary">
                    Simpan Vendor
                </button>

                <a href="index.php" class="btn btn-secondary">
                    Batal
                </a>

            </div>

        </form>

    </div>

</section>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
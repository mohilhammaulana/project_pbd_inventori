<?php

if (!isset($data, $errors, $roles, $page_title, $is_edit)) {
    http_response_code(403);
    exit("Akses tidak diizinkan.");
}

require_once __DIR__ . '/helper.php';

require __DIR__ . '/../../includes/header.php';

?>

<div class="section-heading">

    <h2><?= e($page_title); ?></h2>

    <p>Kelola informasi akun pengguna STOKFLOW.</p>

</div>

<section class="panel form-panel">

    <div class="panel-header">
        <h2>Form <?= e($page_title); ?></h2>
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

                <label for="username">Username</label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    class="form-control"
                    maxlength="45"
                    value="<?= e($data['username']); ?>"
                    required
                >

            </div>

            <div class="form-group">

                <label for="password">Password</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    minlength="8"
                    maxlength="72"
                    autocomplete="new-password"
                    <?= !$is_edit ? 'required' : ''; ?>
                >

                <?php if ($is_edit) : ?>

                    <small>
                        Kosongkan jika password tidak ingin diubah.
                    </small>

                <?php else : ?>

                    <small>Minimal 8 karakter.</small>

                <?php endif; ?>

            </div>

            <div class="form-group">

                <label for="idrole">Role</label>

                <select
                    name="idrole"
                    id="idrole"
                    class="form-control"
                    required
                >

                    <option value="">
                        -- Pilih Role --
                    </option>

                    <?php foreach ($roles as $role) : ?>

                        <option
                            value="<?= (int) $role['idrole']; ?>"
                            <?= $data['idrole'] == $role['idrole']
                                ? 'selected' : ''; ?>
                        >
                            <?= e($role['nama_role']); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="form-actions">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Simpan User
                </button>

                <a
                    href="index.php"
                    class="btn btn-secondary"
                >
                    Batal
                </a>

            </div>

        </form>

    </div>

</section>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
<?php

require_once __DIR__ . '/../../auth/cek_role.php';
require_once __DIR__ . '/../../config/koneksi.php';

cek_role(['Admin']);

require_once __DIR__ . '/helper.php';

$page_title = 'Tambah Margin';
$current_page = 'margin';

$errors = [];
$persen = '';
$aktif = '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    cek_csrf_margin();

    $persen = trim($_POST['persen'] ?? '');
    $aktif = $_POST['aktif'] ?? '';

    $iduser = (int) ($_SESSION['iduser'] ?? 0);

    if (
        !is_numeric($persen) ||
        (float) $persen < 0 ||
        (float) $persen > 1000
    ) {

        $errors[] =
            "Margin harus berupa angka antara 0 sampai 1000.";
    }

    if (!in_array($aktif, ['0', '1'], true)) {

        $errors[] = "Status margin tidak valid.";
    }

    if ($iduser < 1) {

        $errors[] = "Session user tidak valid.";
    }

    if (empty($errors)) {

        try {

            $nilai_margin = (float) $persen;
            $status = (int) $aktif;

            $stmt = $koneksi->prepare(
                "CALL sp_tambah_margin(?, ?, ?)"
            );

            $stmt->bind_param(
                "dii",
                $nilai_margin,
                $iduser,
                $status
            );

            $stmt->execute();
            $stmt->close();

            bersihkan_hasil_procedure($koneksi);

            $_SESSION['success'] =
                "Margin penjualan berhasil ditambahkan.";

            redirect_margin();

        } catch (mysqli_sql_exception $e) {

            error_log($e->getMessage());

            $errors[] =
            "Margin penjualan gagal ditambahkan.";
        }
    }
}

require __DIR__ . '/../../includes/header.php';

?>

<div class="section-heading">

    <h2>Tambah Margin Penjualan</h2>

    <p>Atur persentase tambahan harga jual barang.</p>

</div>

<section class="panel form-panel">

    <div class="panel-header">

        <h2>Form Tambah Margin</h2>

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

                <label for="persen">
                    Persentase Margin (%)
                </label>

                <input
                    type="number"
                    name="persen"
                    id="persen"
                    class="form-control"
                    min="0"
                    max="1000"
                    step="0.01"
                    placeholder="Contoh: 25"
                    value="<?= e($persen); ?>"
                    required
                >

            </div>

            <div class="form-group">

                <label for="aktif">Status Margin</label>

                <select
                    name="aktif"
                    id="aktif"
                    class="form-control"
                >

                    <option value="1"
                        <?= $aktif === '1'
                            ? 'selected' : ''; ?>>
                        Langsung Aktifkan
                    </option>

                    <option value="0"
                        <?= $aktif === '0'
                            ? 'selected' : ''; ?>>
                        Simpan sebagai Tidak Aktif
                    </option>

                </select>

            </div>

            <div class="form-note">

                Jika margin baru langsung diaktifkan,
                margin sebelumnya otomatis menjadi tidak aktif.

            </div>

            <div class="form-actions">

                <button type="submit" class="btn btn-primary">
                    Simpan Margin
                </button>

                <a href="index.php" class="btn btn-secondary">
                    Batal
                </a>

            </div>

        </form>

    </div>

</section>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
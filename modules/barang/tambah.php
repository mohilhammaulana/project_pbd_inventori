<?php

require_once __DIR__ . '/../../auth/cek_role.php';
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/helper.php';

cek_role(['Admin', 'Gudang']);

$page_title = 'Tambah Barang';
$current_page = 'barang';

$errors = [];

$data = [
    'jenis' => 'M',
    'nama' => '',
    'idsatuan' => '',
    'harga' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    cek_csrf();

    $data = [
        'jenis' => $_POST['jenis'] ?? '',
        'nama' => trim($_POST['nama'] ?? ''),
        'idsatuan' => $_POST['idsatuan'] ?? '',
        'harga' => $_POST['harga'] ?? ''
    ];

    $errors = validasi_barang($koneksi, $data);

    if (empty($errors)) {

        try {

            $jenis = $data['jenis'];
            $nama = $data['nama'];
            $idsatuan = (int) $data['idsatuan'];
            $harga = (int) $data['harga'];

            $stmt = $koneksi->prepare(
                "CALL sp_tambah_barang(?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssii",
                $jenis,
                $nama,
                $idsatuan,
                $harga
            );

            $stmt->execute();

            $stmt->close();

            bersihkan_hasil_procedure($koneksi);

            $_SESSION['success'] =
                "Data barang berhasil ditambahkan.";

            redirect_barang();

        } catch (mysqli_sql_exception $e) {

            error_log($e->getMessage());

            $errors[] =
                "Barang gagal ditambahkan. Periksa kembali data.";

        }
    }
}

$satuan = $koneksi->query(
    "SELECT idsatuan, nama_satuan
     FROM satuan
     WHERE status = 1
     ORDER BY nama_satuan ASC"
);

require __DIR__ . '/../../includes/header.php';

?>

<div class="section-heading">

    <h2>Tambah Barang</h2>

    <p>Masukkan informasi barang baru.</p>

</div>

<section class="panel form-panel">

    <div class="panel-header">

        <h2>Form Tambah Barang</h2>

        <p>Lengkapi seluruh data yang diperlukan.</p>

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

                <label for="nama">Nama Barang</label>

                <input
                    type="text"
                    id="nama"
                    name="nama"
                    class="form-control"
                    maxlength="45"
                    value="<?= e($data['nama']); ?>"
                    placeholder="Contoh: Beras Premium"
                    required
                >

            </div>

            <div class="form-group">

                <label for="jenis">Jenis Barang</label>

                <select
                    name="jenis"
                    id="jenis"
                    class="form-control"
                    required
                >

                    <option value="M"
                        <?= $data['jenis'] === 'M'
                            ? 'selected' : ''; ?>>
                        Makanan
                    </option>

                    <option value="N"
                        <?= $data['jenis'] === 'N'
                            ? 'selected' : ''; ?>>
                        Non-Makanan
                    </option>

                </select>

            </div>

            <div class="form-group">

                <label for="idsatuan">Satuan Barang</label>

                <select
                    name="idsatuan"
                    id="idsatuan"
                    class="form-control"
                    required
                >

                    <option value="">-- Pilih Satuan --</option>

                    <?php while ($row = $satuan->fetch_assoc()) : ?>

                        <option
                            value="<?= (int) $row['idsatuan']; ?>"
                            <?= $data['idsatuan'] == $row['idsatuan']
                                ? 'selected' : ''; ?>
                        >
                            <?= e($row['nama_satuan']); ?>
                        </option>

                    <?php endwhile; ?>

                </select>

            </div>

            <div class="form-group">

                <label for="harga">Harga Pokok (Rp)</label>

                <input
                    type="number"
                    id="harga"
                    name="harga"
                    class="form-control"
                    min="0"
                    max="2147483647"
                    step="1"
                    value="<?= e($data['harga']); ?>"
                    placeholder="Contoh: 15000"
                    required
                >

            </div>

            <div class="form-note">

                Barang baru akan memiliki stok awal 0.
                Stok bertambah melalui transaksi penerimaan.

            </div>

            <div class="form-actions">

                <button type="submit" class="btn btn-primary">
                    Simpan Barang
                </button>

                <a href="index.php" class="btn btn-secondary">
                    Batal
                </a>

            </div>

        </form>

    </div>

</section>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
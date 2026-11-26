<?php

require_once __DIR__ . '/../../auth/cek_role.php';
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/helper.php';

cek_role(['Admin', 'Gudang']);

$page_title = 'Edit Barang';
$current_page = 'barang';

// =========================================
// MENGAMBIL ID BARANG
// =========================================

$idbarang = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$idbarang || $idbarang < 1) {

    $_SESSION['error'] = "ID barang tidak valid.";

    redirect_barang();
}

// =========================================
// MENGAMBIL DATA BARANG
// =========================================

$stmt = $koneksi->prepare(
    "SELECT *
     FROM v_barang
     WHERE idbarang = ?"
);

$stmt->bind_param("i", $idbarang);

$stmt->execute();

$data = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$data) {

    $_SESSION['error'] = "Data barang tidak ditemukan.";

    redirect_barang();
}

// Menyimpan satuan asli sebelum diedit

$idsatuan_lama = (int) $data['idsatuan'];

$errors = [];

// =========================================
// PROSES UPDATE BARANG
// =========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    cek_csrf();

    // Mengambil input form

    foreach (
        ['nama', 'jenis', 'idsatuan', 'harga', 'status']
        as $field
    ) {

        $value = $_POST[$field] ?? '';

        $data[$field] = is_string($value)
            ? trim($value)
            : '';
    }

    // Validasi data barang

    $errors = validasi_barang(
        $koneksi,
        $data,
        true,
        $idsatuan_lama
    );

    // Barang tetap boleh dinonaktifkan
    // meskipun masih mempunyai stok.
    // Stok dan riwayat tidak ikut terhapus.

    if (empty($errors)) {

        try {

            $jenis = $data['jenis'];
            $nama = $data['nama'];
            $idsatuan = (int) $data['idsatuan'];
            $harga = (int) $data['harga'];
            $status = (int) $data['status'];

            // Menggunakan Stored Procedure lama

            $stmt = $koneksi->prepare(
                "CALL sp_update_barang(
                    ?, ?, ?, ?, ?, ?
                )"
            );

            $stmt->bind_param(
                "issiii",
                $idbarang,
                $jenis,
                $nama,
                $idsatuan,
                $harga,
                $status
            );

            $stmt->execute();

            $stmt->close();

            bersihkan_hasil_procedure($koneksi);

            $_SESSION['success'] =
                "Data barang berhasil diperbarui.";

            redirect_barang();

        } catch (mysqli_sql_exception $e) {

            error_log($e->getMessage());

            if ((int) $e->getCode() === 1644) {

                $errors[] = $e->getMessage();

            } else {

                $errors[] =
                    "Data barang gagal diperbarui.";

            }
        }
    }
}

// =========================================
// MENGAMBIL PILIHAN SATUAN
// =========================================

// Menampilkan semua satuan aktif dan
// satuan lama yang digunakan barang tersebut.

$stmt = $koneksi->prepare(
    "SELECT
        idsatuan,
        nama_satuan,
        status
     FROM v_satuan
     WHERE status = 1
     OR idsatuan = ?
     ORDER BY nama_satuan ASC"
);

$stmt->bind_param("i", $idsatuan_lama);

$stmt->execute();

$satuan = $stmt->get_result();

$stmt->close();

require __DIR__ . '/../../includes/header.php';

?>

<div class="section-heading">

    <h2>Edit Barang</h2>

    <p>Perbarui informasi barang yang dipilih.</p>

</div>

<section class="panel form-panel">

    <div class="panel-header">

        <h2>Form Edit Barang</h2>

        <p>ID Barang: <?= (int) $idbarang; ?></p>

    </div>

    <div class="panel-body">

        <?php foreach ($errors as $error): ?>

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

            <!-- NAMA BARANG -->

            <div class="form-group">

                <label for="nama">
                    Nama Barang
                </label>

                <input
                    type="text"
                    name="nama"
                    id="nama"
                    class="form-control"
                    maxlength="45"
                    value="<?= e($data['nama']); ?>"
                    required
                >

            </div>

            <!-- JENIS BARANG -->

            <div class="form-group">

                <label for="jenis">
                    Jenis Barang
                </label>

                <select
                    name="jenis"
                    id="jenis"
                    class="form-control"
                    required
                >

                    <option
                        value="M"
                        <?= $data['jenis'] === 'M'
                            ? 'selected' : ''; ?>
                    >
                        Makanan
                    </option>

                    <option
                        value="N"
                        <?= $data['jenis'] === 'N'
                            ? 'selected' : ''; ?>
                    >
                        Non-Makanan
                    </option>

                </select>

            </div>

            <!-- SATUAN BARANG -->

            <div class="form-group">

                <label for="idsatuan">
                    Satuan Barang
                </label>

                <select
                    name="idsatuan"
                    id="idsatuan"
                    class="form-control"
                    required
                >

                    <option value="">
                        -- Pilih Satuan --
                    </option>

                    <?php while (
                        $row = $satuan->fetch_assoc()
                    ): ?>

                        <option
                            value="<?= (int) $row['idsatuan']; ?>"

                            data-status="<?= (int) $row['status']; ?>"

                            <?= $data['idsatuan'] == $row['idsatuan']
                                ? 'selected' : ''; ?>
                        >

                            <?= e($row['nama_satuan']); ?>

                            <?= (int) $row['status'] === 0
                                ? ' (Nonaktif)' : ''; ?>

                        </option>

                    <?php endwhile; ?>

                </select>

                <small class="form-hint">

                    Satuan nonaktif yang sudah digunakan
                    tetap dapat dipertahankan selama
                    barang berstatus tidak aktif.

                </small>

            </div>

            <!-- HARGA POKOK -->

            <div class="form-group">

                <label for="harga">
                    Harga Pokok (Rp)
                </label>

                <input
                    type="number"
                    name="harga"
                    id="harga"
                    class="form-control"
                    min="1"
                    max="2147483647"
                    step="1"
                    value="<?= e($data['harga']); ?>"
                    required
                >

            </div>

            <!-- STOK BARANG -->

            <div class="form-group">

                <label>
                    Stok Saat Ini
                </label>

                <input
                    type="number"
                    class="form-control"
                    value="<?= (int) $data['stok']; ?>"
                    readonly
                >

                <small class="form-hint">

                    Stok hanya dapat berubah melalui transaksi.

                </small>

            </div>

            <!-- STATUS BARANG -->

            <div class="form-group">

                <label for="status">
                    Status Barang
                </label>

                <select
                    name="status"
                    id="status"
                    class="form-control"
                    required
                >

                    <option
                        value="1"
                        <?= $data['status'] == 1
                            ? 'selected' : ''; ?>
                    >
                        Aktif
                    </option>

                    <option
                        value="0"
                        <?= $data['status'] == 0
                            ? 'selected' : ''; ?>
                    >
                        Tidak Aktif
                    </option>

                </select>

                <small class="form-hint">

                    Menonaktifkan barang tidak menghapus
                    stok maupun riwayat transaksi.

                    Barang nonaktif tidak dapat digunakan
                    untuk pengadaan dan penjualan baru.

                </small>

            </div>

            <!-- INFORMASI STATUS OTOMATIS -->

            <?php if (
                (int) ($data['nonaktif_karena_satuan'] ?? 0) === 1
            ): ?>

                <div class="alert-error">

                    Barang ini dinonaktifkan secara otomatis
                    karena satuannya nonaktif.

                    Barang akan aktif kembali secara otomatis
                    ketika satuannya diaktifkan, selama status
                    dan satuan barang tetap dipertahankan.

                </div>

            <?php endif; ?>

            <!-- TOMBOL AKSI -->

            <div class="form-actions">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Simpan Perubahan
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

<script>

document.addEventListener('DOMContentLoaded', function () {

    const satuan = document.getElementById('idsatuan');

    const status = document.getElementById('status');

    const opsiAktif = status.querySelector(
        'option[value="1"]'
    );

    function cekStatusSatuan() {

        const pilihan = satuan.options[satuan.selectedIndex];

        const satuanNonaktif =
            pilihan && pilihan.dataset.status === '0';

        opsiAktif.disabled = satuanNonaktif;

        if (satuanNonaktif) {

            status.value = '0';

        }
    }

    satuan.addEventListener(
        'change',
        cekStatusSatuan
    );

    cekStatusSatuan();

});

</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
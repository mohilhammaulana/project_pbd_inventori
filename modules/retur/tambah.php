<?php

require_once __DIR__ . '/helper.php';

$page_title = 'Buat Retur';
$current_page = 'retur';

$idpenerimaan = (int) ($_GET['idpenerimaan'] ?? 0);

$penerimaan = null;
$details = [];
$errors = [];

// Daftar penerimaan yang dapat diretur
$daftar_penerimaan = $koneksi->query(
    "SELECT
        p.idpenerimaan,
        p.created_at,
        p.nama_vendor
     FROM v_penerimaan p
     WHERE p.status = 'S'
     AND EXISTS (
        SELECT 1
        FROM v_progress_retur vr
        WHERE vr.idpenerimaan = p.idpenerimaan
        AND vr.maksimal_retur > 0
     )
     ORDER BY p.idpenerimaan DESC"
);

// Mengambil penerimaan yang dipilih
if ($idpenerimaan > 0) {

    $penerimaan = rt_ambil_penerimaan(
        $koneksi,
        $idpenerimaan
    );

    if (!$penerimaan) {

        $errors[] = 'Penerimaan tidak ditemukan.';

    } elseif ($penerimaan['status'] !== 'S') {

        $errors[] = 'Penerimaan belum selesai.';

        $penerimaan = null;

    } else {

        $details = rt_ambil_progress(
            $koneksi,
            $idpenerimaan
        );
    }
}

$bisa_retur = false;

foreach ($details as $d) {

    if ((int) $d['maksimal_retur'] > 0) {
        $bisa_retur = true;
        break;
    }
}

require __DIR__ . '/../../includes/header.php';

?>

<div class="section-heading">

    <h2>Buat Retur</h2>

    <p>
        Pilih transaksi penerimaan dan barang
        yang akan dikembalikan kepada vendor.
    </p>

</div>

<?php rt_tampil_flash(); ?>

<?php foreach ($errors as $error): ?>

    <div class="alert-error">
        <?= rt_e($error); ?>
    </div>

<?php endforeach; ?>

<!-- PILIH PENERIMAAN -->

<section class="panel">

    <div class="panel-header">

        <h2>Pilih Penerimaan</h2>

        <a href="index.php" class="btn btn-secondary">
            Kembali
        </a>

    </div>

    <div class="panel-body">

        <form method="GET" action="tambah.php">

            <div class="form-group">

                <label>Transaksi Penerimaan</label>

                <select
                    name="idpenerimaan"
                    class="form-control"
                    required
                >

                    <option value="">
                        -- Pilih Penerimaan --
                    </option>

                    <?php while (
                        $p = $daftar_penerimaan->fetch_assoc()
                    ): ?>

                        <option
                            value="<?= (int) $p['idpenerimaan']; ?>"
                            <?= $idpenerimaan == $p['idpenerimaan']
                                ? 'selected' : ''; ?>
                        >

                            PN-<?= str_pad(
                                $p['idpenerimaan'],
                                5,
                                '0',
                                STR_PAD_LEFT
                            ); ?>

                            - <?= rt_e($p['nama_vendor']); ?>

                            - <?= rt_e($p['created_at']); ?>

                        </option>

                    <?php endwhile; ?>

                </select>

            </div>

            <button type="submit" class="btn btn-primary">
                Tampilkan Barang
            </button>

        </form>

        <?php if ($daftar_penerimaan->num_rows === 0): ?>

            <div class="form-note">
                Belum ada penerimaan yang dapat diretur.
            </div>

        <?php endif; ?>

    </div>

</section>

<?php if ($penerimaan): ?>

<!-- INFORMASI PENERIMAAN -->

<section class="panel">

    <div class="panel-header">
        <h2>Informasi Penerimaan</h2>
    </div>

    <div class="panel-body">

        <p>

            <strong>ID Penerimaan:</strong>

            PN-<?= str_pad(
                $idpenerimaan,
                5,
                '0',
                STR_PAD_LEFT
            ); ?>

        </p>

        <p>
            <strong>Vendor:</strong>
            <?= rt_e($penerimaan['nama_vendor']); ?>
        </p>

        <p>
            <strong>Tanggal Penerimaan:</strong>
            <?= rt_e($penerimaan['created_at']); ?>
        </p>

        <p>
            <strong>Petugas Penerimaan:</strong>
            <?= rt_e($penerimaan['username']); ?>
        </p>

        <p>
            <strong>Status:</strong>

            <span class="badge badge-success">
                Selesai
            </span>
        </p>

    </div>

</section>

<!-- FORM RETUR -->

<section class="panel">

    <div class="panel-header">
        <h2>Detail Retur Barang</h2>
    </div>

    <div class="panel-body">

        <form
            method="POST"
            action="simpan.php"
            id="formRetur"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= rt_e($_SESSION['csrf_retur']); ?>"
            >

            <input
                type="hidden"
                name="idpenerimaan"
                value="<?= $idpenerimaan; ?>"
            >

            <div class="table-responsive">

                <table class="data-table">

                    <thead>

                        <tr>
                            <th>No</th>
                            <th>Barang</th>
                            <th>Satuan</th>
                            <th>Diterima</th>
                            <th>Sudah Retur</th>
                            <th>Stok</th>
                            <th>Maksimal Retur</th>
                            <th>Jumlah Retur</th>
                            <th>Alasan</th>
                            <th>Nilai Retur</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if (empty($details)): ?>

                            <tr>
                                <td colspan="10">
                                    Belum ada detail penerimaan.
                                </td>
                            </tr>

                        <?php endif; ?>

                        <?php $no = 1; ?>

                        <?php foreach ($details as $d): ?>

                            <?php

                            $id = (int) $d['iddetail_penerimaan'];

                            $maksimal =
                                (int) $d['maksimal_retur'];

                            ?>

                            <tr>

                                <td><?= $no++; ?></td>

                                <td>
                                    <?= rt_e($d['nama_barang']); ?>
                                </td>

                                <td>
                                    <?= rt_e($d['nama_satuan']); ?>
                                </td>

                                <td>
                                    <?= (int) $d['jumlah_terima']; ?>
                                </td>

                                <td>
                                    <?= (int) $d['sudah_retur']; ?>
                                </td>

                                <td>
                                    <?= (int) $d['stok']; ?>
                                </td>

                                <td><?= $maksimal; ?></td>

                                <?php if ($maksimal > 0): ?>

                                    <td>

                                        <input
                                            type="number"
                                            name="jumlah[<?= $id; ?>]"
                                            class="form-control jumlah-retur"
                                            min="0"
                                            max="<?= $maksimal; ?>"
                                            step="1"
                                            value="0"
                                            data-harga="<?= (int) $d['harga_satuan_terima']; ?>"
                                            required
                                        >

                                    </td>

                                    <td>

                                        <input
                                            type="text"
                                            name="alasan[<?= $id; ?>]"
                                            class="form-control alasan-retur"
                                            placeholder="Alasan retur"
                                            maxlength="200"
                                        >

                                    </td>

                                <?php else: ?>

                                    <td>0</td>

                                    <td>Tidak tersedia</td>

                                <?php endif; ?>

                                <td class="nilai-retur">
                                    Rp 0
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

            <div class="transaction-summary">

                <div class="transaction-total">

                    <span>Total Nilai Retur</span>

                    <strong id="totalRetur">Rp 0</strong>

                </div>

            </div>

            <div class="form-note">

                Isi 0 untuk barang yang tidak diretur.
                Alasan wajib diisi untuk setiap barang
                yang akan dikembalikan.

            </div>

            <?php if ($bisa_retur): ?>

                <div class="form-actions">

                    <button
                        type="submit"
                        class="btn btn-primary"
                        id="btnSimpan"
                        disabled
                    >
                        Konfirmasi Retur
                    </button>

                    <a
                        href="index.php"
                        class="btn btn-secondary"
                    >
                        Batal
                    </a>

                </div>

            <?php else: ?>

                <div class="form-note">
                    Tidak ada barang yang dapat diretur.
                </div>

            <?php endif; ?>

        </form>

    </div>

</section>

<script>

const inputs = document.querySelectorAll('.jumlah-retur');
const alasanInputs = document.querySelectorAll('.alasan-retur');
const totalRetur = document.getElementById('totalRetur');
const btnSimpan = document.getElementById('btnSimpan');
const formRetur = document.getElementById('formRetur');

function formatRupiah(nilai) {

    return 'Rp ' + nilai.toLocaleString('id-ID');
}

function hitungRetur() {

    let total = 0;
    let jumlahBarang = 0;
    let valid = true;

    inputs.forEach(function(input) {

        const jumlah = Number(input.value);
        const maksimal = Number(input.max);
        const harga = Number(input.dataset.harga);

        const baris = input.closest('tr');

        const alasan =
            baris.querySelector('.alasan-retur');

        alasan.required = jumlah > 0;

        if (
            input.value === '' ||
            !Number.isInteger(jumlah) ||
            jumlah < 0 ||
            jumlah > maksimal
        ) {

            valid = false;
        }

        if (jumlah > 0 && alasan.value.trim() === '') {

            valid = false;
        }

        const nilai = jumlah * harga;

        baris.querySelector('.nilai-retur')
            .textContent = formatRupiah(nilai);

        total += nilai;
        jumlahBarang += jumlah;
    });

    totalRetur.textContent = formatRupiah(total);

    if (btnSimpan) {

        btnSimpan.disabled =
            !valid || jumlahBarang <= 0;
    }

    return valid && jumlahBarang > 0;
}

inputs.forEach(function(input) {

    input.addEventListener('input', hitungRetur);
});

alasanInputs.forEach(function(input) {

    input.addEventListener('input', hitungRetur);
});

formRetur.addEventListener('submit', function(event) {

    if (!hitungRetur()) {

        event.preventDefault();

        alert('Periksa jumlah dan alasan retur.');

        return;
    }

    if (!confirm(
        'Konfirmasi retur? Stok barang akan berkurang.'
    )) {

        event.preventDefault();

        return;
    }

    if (btnSimpan) {
        btnSimpan.disabled = true;
    }
});

hitungRetur();

</script>

<?php endif; ?>

<?php

require __DIR__ . '/../../includes/footer.php';
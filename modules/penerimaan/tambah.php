<?php

require_once __DIR__ . '/helper.php';

$page_title = 'Buat Penerimaan';
$current_page = 'penerimaan';

$idpengadaan = (int) ($_GET['idpengadaan'] ?? 0);

$pengadaan = null;
$details = [];
$errors = [];

// Mengambil pengadaan yang masih memiliki sisa pesanan
$daftar_pengadaan = $koneksi->query(
    "SELECT p.idpengadaan,
            p.nama_vendor,
            p.timestamp
     FROM v_pengadaan p
     WHERE p.status = 'P'
     AND EXISTS (
         SELECT 1
         FROM v_progress_pengadaan vp
         WHERE vp.idpengadaan = p.idpengadaan
         AND vp.sisa_barang > 0
     )
     ORDER BY p.idpengadaan DESC"
);

// Mengambil detail pengadaan yang dipilih
if ($idpengadaan > 0) {

    $pengadaan = pn_ambil_pengadaan(
        $koneksi,
        $idpengadaan
    );

    if (!$pengadaan) {

        $errors[] = 'Pengadaan tidak ditemukan.';
        $pengadaan = null;

    } elseif ($pengadaan['status'] !== 'P') {

        $errors[] = 'Pengadaan sudah selesai.';
        $pengadaan = null;

    } else {

        $details = pn_ambil_progress(
            $koneksi,
            $idpengadaan
        );
    }
}

// Memeriksa apakah masih ada barang yang belum diterima
$ada_sisa = false;

foreach ($details as $d) {

    if ((int) $d['sisa_barang'] > 0) {
        $ada_sisa = true;
        break;
    }
}

require __DIR__ . '/../../includes/header.php';

?>

<div class="section-heading">

    <h2>Buat Penerimaan</h2>

    <p>
        Konfirmasi barang yang diterima
        berdasarkan transaksi pengadaan.
    </p>

</div>

<?php pn_tampil_flash(); ?>

<?php foreach ($errors as $error): ?>

    <div class="alert-error">
        <?= pn_e($error); ?>
    </div>

<?php endforeach; ?>

<!-- PILIH PENGADAAN -->

<section class="panel">

    <div class="panel-header">

        <h2>Pilih Pengadaan</h2>

        <a href="index.php" class="btn btn-secondary">
            Kembali
        </a>

    </div>

    <div class="panel-body">

        <form method="GET" action="tambah.php">

            <div class="form-group">

                <label for="idpengadaan">
                    Transaksi Pengadaan
                </label>

                <select
                    name="idpengadaan"
                    id="idpengadaan"
                    class="form-control"
                    required
                >

                    <option value="">
                        -- Pilih Pengadaan --
                    </option>

                    <?php while ($p = $daftar_pengadaan->fetch_assoc()): ?>

                        <option
                            value="<?= (int) $p['idpengadaan']; ?>"
                            <?= $idpengadaan == $p['idpengadaan']
                                ? 'selected' : ''; ?>
                        >

                            PG-<?= str_pad(
                                $p['idpengadaan'],
                                5,
                                '0',
                                STR_PAD_LEFT
                            ); ?>

                            - <?= pn_e($p['nama_vendor']); ?>

                            - <?= pn_e($p['timestamp']); ?>

                        </option>

                    <?php endwhile; ?>

                </select>

            </div>

            <button type="submit" class="btn btn-primary">
                Tampilkan Barang
            </button>

        </form>

        <?php if ($daftar_pengadaan->num_rows === 0): ?>

            <div class="form-note">

                Belum ada pengadaan dalam proses
                yang memiliki sisa pesanan.

                Silakan buat pengadaan dan tambahkan
                detail barang terlebih dahulu.

            </div>

        <?php endif; ?>

    </div>

</section>

<!-- INFORMASI PENGADAAN -->

<?php if ($pengadaan): ?>

<section class="panel">

    <div class="panel-header">

        <h2>Informasi Pengadaan</h2>

    </div>

    <div class="panel-body">

        <p>
            <strong>ID Pengadaan:</strong>

            PG-<?= str_pad(
                $pengadaan['idpengadaan'],
                5,
                '0',
                STR_PAD_LEFT
            ); ?>
        </p>

        <p>
            <strong>Vendor:</strong>
            <?= pn_e($pengadaan['nama_vendor']); ?>
        </p>

        <p>
            <strong>Tanggal Pengadaan:</strong>
            <?= pn_e($pengadaan['timestamp']); ?>
        </p>

        <p>
            <strong>Dibuat Oleh:</strong>
            <?= pn_e($pengadaan['username']); ?>
        </p>

        <p>
            <strong>Status:</strong>

            <span class="badge badge-warning">
                Dalam Proses
            </span>
        </p>

    </div>

</section>

<!-- FORM PENERIMAAN BARANG -->

<section class="panel">

    <div class="panel-header">

        <h2>Detail Penerimaan Barang</h2>

    </div>

    <div class="panel-body">

        <form
            action="simpan.php"
            method="POST"
            id="formPenerimaan"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= pn_e(
                    $_SESSION['csrf_penerimaan']
                ); ?>"
            >

            <input
                type="hidden"
                name="idpengadaan"
                value="<?= $idpengadaan; ?>"
            >

            <div class="table-responsive">

                <table class="data-table">

                    <thead>

                        <tr>
                            <th>No</th>
                            <th>Nama Barang</th>
                            <th>Satuan</th>
                            <th>Harga Satuan</th>
                            <th>Dipesan</th>
                            <th>Sudah Diterima</th>
                            <th>Sisa</th>
                            <th>Terima Sekarang</th>
                            <th>Subtotal</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if (empty($details)): ?>

                            <tr>

                                <td colspan="9">
                                    Belum ada detail pengadaan.
                                </td>

                            </tr>

                        <?php endif; ?>

                        <?php $no = 1; ?>

                        <?php foreach ($details as $d): ?>

                            <?php

                            $sisa = max(
                                0,
                                (int) $d['sisa_barang']
                            );

                            ?>

                            <tr>

                                <td><?= $no++; ?></td>

                                <td>
                                    <?= pn_e($d['nama_barang']); ?>
                                </td>

                                <td>
                                    <?= pn_e($d['nama_satuan']); ?>
                                </td>

                                <td>
                                    <?= pn_rupiah(
                                        $d['harga_satuan']
                                    ); ?>
                                </td>

                                <td>
                                    <?= (int) $d['jumlah_pesan']; ?>
                                </td>

                                <td>
                                    <?= (int) $d['jumlah_diterima']; ?>
                                </td>

                                <td>

                                    <?php if ($sisa > 0): ?>

                                        <?= $sisa; ?>

                                    <?php else: ?>

                                        <span class="badge badge-success">
                                            Lengkap
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <?php if ($sisa > 0): ?>

                                        <input
                                            type="number"
                                            name="jumlah[<?= (int) $d['idbarang']; ?>]"
                                            class="form-control jumlah-terima"
                                            min="0"
                                            max="<?= $sisa; ?>"
                                            step="1"
                                            value="0"
                                            data-harga="<?= (int) $d['harga_satuan']; ?>"
                                            required
                                        >

                                    <?php else: ?>

                                        0

                                    <?php endif; ?>

                                </td>

                                <td class="subtotal-terima">
                                    Rp 0
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

            <!-- RINGKASAN PENERIMAAN -->

            <div class="transaction-summary">

                <div class="transaction-total">

                    <span>Total Nilai Penerimaan</span>

                    <strong id="totalPenerimaan">
                        Rp 0
                    </strong>

                </div>

            </div>

            <div class="form-note">

                Masukkan jumlah barang yang diterima
                sekarang. Isi 0 apabila barang belum
                diterima.

                Jumlah penerimaan tidak boleh melebihi
                sisa pesanan.

                Status pengadaan akan selesai secara
                otomatis apabila seluruh barang sudah
                diterima.

            </div>

            <?php if ($ada_sisa): ?>

                <div class="form-actions">

                    <button
                        type="submit"
                        class="btn btn-primary"
                        id="btnKonfirmasi"
                        disabled
                    >
                        Konfirmasi Penerimaan
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
                    Tidak ada sisa barang yang perlu diterima.
                </div>

            <?php endif; ?>

        </form>

    </div>

</section>

<script>

const inputs = document.querySelectorAll('.jumlah-terima');
const totalPenerimaan = document.getElementById('totalPenerimaan');
const btnKonfirmasi = document.getElementById('btnKonfirmasi');
const formPenerimaan = document.getElementById('formPenerimaan');

function formatRupiah(nilai) {

    return 'Rp ' + nilai.toLocaleString('id-ID');
}

function hitungPenerimaan() {

    let total = 0;
    let jumlahBarang = 0;
    let valid = true;

    inputs.forEach(function(input) {

        const jumlah = Number(input.value);
        const harga = Number(input.dataset.harga);
        const maksimal = Number(input.max);

        if (
            input.value === '' ||
            !Number.isInteger(jumlah) ||
            jumlah < 0 ||
            jumlah > maksimal
        ) {
            valid = false;
        }

        const subtotal = jumlah * harga;

        const baris = input.closest('tr');

        baris.querySelector(
            '.subtotal-terima'
        ).textContent = formatRupiah(subtotal);

        total += subtotal;
        jumlahBarang += jumlah;
    });

    totalPenerimaan.textContent = formatRupiah(total);

    if (btnKonfirmasi) {
        btnKonfirmasi.disabled = !valid || jumlahBarang <= 0;
    }

    return valid && jumlahBarang > 0;
}

inputs.forEach(function(input) {

    input.addEventListener('input', hitungPenerimaan);
});

formPenerimaan.addEventListener('submit', function(event) {

    if (!hitungPenerimaan()) {

        event.preventDefault();

        alert(
            'Masukkan jumlah penerimaan yang valid.'
        );

        return;
    }

    if (!confirm(
        'Konfirmasi penerimaan barang? Stok akan bertambah sesuai jumlah yang diterima.'
    )) {
        event.preventDefault();
    }
});

hitungPenerimaan();

</script>

<?php endif; ?>

<?php

require __DIR__ . '/../../includes/footer.php';

?>
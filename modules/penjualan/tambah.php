<?php

require_once __DIR__ . '/helper.php';

$page_title = 'Buat Penjualan';
$current_page = 'penjualan';

// Mengambil margin yang sedang aktif
$margin = pj_ambil_margin($koneksi);

$barang = [];

if ($margin) {

    $barang = pj_ambil_barang(
        $koneksi,
        (float) $margin['persen']
    );
}

require __DIR__ . '/../../includes/header.php';

?>

<div class="section-heading">

    <h2>Buat Penjualan</h2>

    <p>
        Masukkan jumlah barang yang akan dijual
        berdasarkan stok yang tersedia.
    </p>

</div>

<?php pj_tampil_flash(); ?>

<section class="panel">

    <div class="panel-header">

        <h2>Informasi Penjualan</h2>

        <a href="index.php" class="btn btn-secondary">
            Kembali
        </a>

    </div>

    <div class="panel-body">

        <p>
            <strong>Kasir:</strong>
            <?= pj_e($_SESSION['username']); ?>
        </p>

        <p>
            <strong>Margin Penjualan:</strong>

            <?php if ($margin): ?>

                <?= pj_e($margin['persen']); ?>%

            <?php else: ?>

                Tidak tersedia

            <?php endif; ?>

        </p>

        <p>
            <strong>PPN:</strong> 11% (Simulasi)
        </p>

        <?php if (!$margin): ?>

            <div class="alert-error">

                Margin penjualan aktif belum tersedia.
                Hubungi Admin untuk mengaktifkan margin.

            </div>

        <?php endif; ?>

    </div>

</section>

<?php if ($margin): ?>

<section class="panel">

    <div class="panel-header">
        <h2>Daftar Barang Penjualan</h2>
    </div>

    <div class="panel-body">

        <form
            method="POST"
            action="simpan.php"
            id="formPenjualan"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= pj_e(
                    $_SESSION['csrf_penjualan']
                ); ?>"
            >

            <input
                type="hidden"
                name="idmargin"
                value="<?= (int) $margin['idmargin_penjualan']; ?>"
            >

            <div class="form-group">

                <label>Cari Barang</label>

                <input
                    type="text"
                    id="cariBarang"
                    class="form-control"
                    placeholder="Masukkan nama barang..."
                >

            </div>

            <div class="table-responsive">

                <table class="data-table" id="tabelBarang">

                    <thead>

                        <tr>
                            <th>No</th>
                            <th>Nama Barang</th>
                            <th>Satuan</th>
                            <th>Harga Dasar</th>
                            <th>Harga Jual</th>
                            <th>Stok</th>
                            <th>Jumlah Jual</th>
                            <th>Subtotal</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if (empty($barang)): ?>

                            <tr>

                                <td colspan="8">

                                    Belum ada barang
                                    yang tersedia untuk dijual.

                                </td>

                            </tr>

                        <?php endif; ?>

                        <?php $no = 1; ?>

                        <?php foreach ($barang as $b): ?>

                            <tr
                                class="baris-barang"
                                data-nama="<?= pj_e($b['nama']); ?>"
                            >

                                <td><?= $no++; ?></td>

                                <td>
                                    <?= pj_e($b['nama']); ?>
                                </td>

                                <td>
                                    <?= pj_e($b['nama_satuan']); ?>
                                </td>

                                <td>
                                    <?= pj_rupiah($b['harga']); ?>
                                </td>

                                <td>
                                    <?= pj_rupiah($b['harga_jual']); ?>
                                </td>

                                <td>
                                    <?= (int) $b['stok']; ?>
                                </td>

                                <td>

                                    <input
                                        type="number"
                                        name="jumlah[<?= (int) $b['idbarang']; ?>]"
                                        class="form-control jumlah-jual"
                                        min="0"
                                        max="<?= (int) $b['stok']; ?>"
                                        step="1"
                                        value="0"
                                        data-harga="<?= (int) $b['harga_jual']; ?>"
                                        required
                                    >

                                    <input
                                        type="hidden"
                                        name="harga_estimasi[<?= (int) $b['idbarang']; ?>]"
                                        value="<?= (int) $b['harga_jual']; ?>"
                                    >

                                </td>

                                <td class="subtotal-barang">
                                    Rp 0
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

            <div class="transaction-summary">

                <div>

                    <span>Subtotal</span>

                    <strong id="subtotal">
                        Rp 0
                    </strong>

                </div>

                <div>

                    <span>PPN (11%)</span>

                    <strong id="ppn">
                        Rp 0
                    </strong>

                </div>

                <div class="transaction-total">

                    <span>Total Penjualan</span>

                    <strong id="total">
                        Rp 0
                    </strong>

                </div>

            </div>

            <div class="form-note">

                Masukkan jumlah barang yang ingin dijual.
                Isi 0 untuk barang yang tidak dijual.

                Harga dan stok akan diperiksa kembali
                sebelum transaksi disimpan.

            </div>

            <?php if (!empty($barang)): ?>

                <div class="form-actions">

                    <button
                        type="submit"
                        class="btn btn-primary"
                        id="btnSimpan"
                        disabled
                    >
                        Konfirmasi Penjualan
                    </button>

                    <a
                        href="index.php"
                        class="btn btn-secondary"
                    >
                        Batal
                    </a>

                </div>

            <?php endif; ?>

        </form>

    </div>

</section>

<script>

const inputs = document.querySelectorAll('.jumlah-jual');

const subtotalElement = document.getElementById('subtotal');
const ppnElement = document.getElementById('ppn');
const totalElement = document.getElementById('total');

const btnSimpan = document.getElementById('btnSimpan');
const formPenjualan = document.getElementById('formPenjualan');
const cariBarang = document.getElementById('cariBarang');

function formatRupiah(nilai) {

    return 'Rp ' + nilai.toLocaleString('id-ID');
}

function hitungPenjualan() {

    let subtotal = 0;
    let jumlahBarang = 0;
    let valid = true;

    inputs.forEach(function(input) {

        const jumlah = Number(input.value);
        const harga = Number(input.dataset.harga);
        const stok = Number(input.max);

        if (
            input.value === '' ||
            !Number.isInteger(jumlah) ||
            jumlah < 0 ||
            jumlah > stok
        ) {
            valid = false;
        }

        const nilai = jumlah * harga;

        input.closest('tr')
            .querySelector('.subtotal-barang')
            .textContent = formatRupiah(nilai);

        subtotal += nilai;
        jumlahBarang += jumlah;
    });

    const ppn = Math.round(subtotal * 11 / 100);
    const total = subtotal + ppn;

    if (total > 2147483647) {
        valid = false;
    }

    subtotalElement.textContent = formatRupiah(subtotal);
    ppnElement.textContent = formatRupiah(ppn);
    totalElement.textContent = formatRupiah(total);

    if (btnSimpan) {

        btnSimpan.disabled =
            !valid || jumlahBarang <= 0;
    }

    return valid && jumlahBarang > 0;
}

inputs.forEach(function(input) {

    input.addEventListener('input', hitungPenjualan);
});

// Pencarian barang secara langsung
cariBarang.addEventListener('input', function() {

    const keyword = this.value.toLowerCase();

    document.querySelectorAll('.baris-barang')
        .forEach(function(baris) {

            const nama =
                baris.dataset.nama.toLowerCase();

            baris.style.display =
                nama.includes(keyword) ? '' : 'none';
        });
});

// Validasi sebelum form dikirim
formPenjualan.addEventListener('submit', function(event) {

    if (!hitungPenjualan()) {

        event.preventDefault();

        alert('Periksa jumlah barang dan stok tersedia.');

        return;
    }

    if (!confirm(
        'Konfirmasi penjualan? Stok barang akan berkurang.'
    )) {

        event.preventDefault();

        return;
    }

    if (btnSimpan) {
        btnSimpan.disabled = true;
    }
});

hitungPenjualan();

</script>

<?php endif; ?>

<?php

require __DIR__ . '/../../includes/footer.php';
<?php

require_once __DIR__ . '/helper.php';

// =========================================
// MENGAMBIL ID PENJUALAN
// =========================================

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$id || $id < 1) {

    pj_redirect('index.php');
}

// =========================================
// MENGAMBIL HEADER PENJUALAN
// =========================================

$penjualan = pj_ambil_penjualan(
    $koneksi,
    $id
);

if (!$penjualan) {

    pj_flash(
        'error',
        'Transaksi penjualan tidak ditemukan.'
    );

    pj_redirect('index.php');
}

// =========================================
// MENGAMBIL DETAIL PENJUALAN
// =========================================

$stmt = $koneksi->prepare(
    "SELECT
        nama_barang,
        nama_satuan,
        harga_satuan,
        jumlah,
        subtotal
     FROM v_detail_penjualan
     WHERE idpenjualan = ?
     ORDER BY iddetail_penjualan ASC"
);

$stmt->bind_param('i', $id);

$stmt->execute();

$details = $stmt->get_result()->fetch_all(
    MYSQLI_ASSOC
);

$stmt->close();

// Mencegah pencetakan transaksi kosong

if (empty($details)) {

    pj_flash(
        'error',
        'Nota tidak dapat dicetak karena detail transaksi kosong.'
    );

    pj_redirect('detail.php?id=' . $id);
}

$nomor_nota = 'PJ-' . str_pad(
    $id,
    5,
    '0',
    STR_PAD_LEFT
);

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Nota <?= pj_e($nomor_nota); ?> - STOKFLOW
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px 15px;
            background: #f1f5f9;
            color: #111827;
            font-family: Arial, sans-serif;
            font-size: 13px;
        }

        .nota-container {
            width: 100%;
            max-width: 360px;
            margin: 0 auto;
        }

        .nota {
            background: #ffffff;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, .08);
        }

        .nota-header {
            text-align: center;
        }

        .nota-header h1 {
            font-size: 25px;
            letter-spacing: 2px;
            margin: 0 0 8px;
        }

        .nota-header h2 {
            font-size: 14px;
            margin: 0 0 8px;
        }

        .nota-header p {
            font-size: 11px;
            line-height: 1.5;
            margin: 0;
        }

        .garis {
            border: 0;
            border-top: 1px dashed #888;
            margin: 16px 0;
        }

        .baris {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin: 8px 0;
            line-height: 1.5;
        }

        .baris > :last-child {
            text-align: right;
            overflow-wrap: anywhere;
        }

        .barang {
            margin-bottom: 14px;
            break-inside: avoid;
        }

        .nama-barang {
            display: block;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 4px;
            overflow-wrap: anywhere;
        }

        .barang .baris {
            margin: 0;
            font-size: 12px;
        }

        .total {
            font-size: 16px;
            font-weight: bold;
        }

        .nota-footer {
            text-align: center;
            font-size: 11px;
            line-height: 1.6;
        }

        .nota-footer p {
            margin: 5px 0;
        }

        .actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            flex: 1;
            border: none;
            border-radius: 6px;
            padding: 12px;
            cursor: pointer;
            font-size: 13px;
            font-weight: bold;
            text-decoration: none;
            text-align: center;
            font-family: inherit;
        }

        .btn-print {
            background: #2563eb;
            color: #ffffff;
        }

        .btn-back {
            background: #e2e8f0;
            color: #334155;
        }

        @page {
            margin: 5mm;
        }

        @media print {

            body {
                background: #ffffff;
                margin: 0;
                padding: 0;
            }

            .nota-container {
                width: 100%;
                max-width: 72mm;
                margin: 0 auto;
            }

            .nota {
                padding: 0;
                border-radius: 0;
                box-shadow: none;
            }

            .actions {
                display: none !important;
            }

        }

    </style>

</head>

<body>

    <main class="nota-container">

        <section class="nota">

            <!-- HEADER NOTA -->

            <div class="nota-header">

                <h1>STOKFLOW</h1>

                <h2>NOTA PENJUALAN</h2>

                <p>
                    Sistem Informasi Inventori Barang
                </p>

            </div>

            <hr class="garis">

            <!-- INFORMASI TRANSAKSI -->

            <div class="baris">

                <span>No. Nota</span>

                <strong>
                    <?= pj_e($nomor_nota); ?>
                </strong>

            </div>

            <div class="baris">

                <span>Tanggal</span>

                <span>

                    <?= date(
                        'd/m/Y H:i',
                        strtotime($penjualan['created_at'])
                    ); ?>

                </span>

            </div>

            <div class="baris">

                <span>Kasir</span>

                <span>

                    <?= pj_e($penjualan['username']); ?>

                </span>

            </div>

            <hr class="garis">

            <!-- DAFTAR BARANG -->

            <?php foreach ($details as $d): ?>

                <div class="barang">

                    <span class="nama-barang">

                        <?= pj_e($d['nama_barang']); ?>

                    </span>

                    <div class="baris">

                        <span>

                            <?= number_format(
                                $d['jumlah'], 0, ',', '.'
                            ); ?>

                            <?= pj_e($d['nama_satuan']); ?>

                            ×

                            <?= pj_rupiah(
                                $d['harga_satuan']
                            ); ?>

                        </span>

                        <strong>

                            <?= pj_rupiah(
                                $d['subtotal']
                            ); ?>

                        </strong>

                    </div>

                </div>

            <?php endforeach; ?>

            <hr class="garis">

            <!-- RINGKASAN TOTAL -->

            <div class="baris">

                <span>Subtotal</span>

                <strong>

                    <?= pj_rupiah(
                        $penjualan['subtotal_nilai']
                    ); ?>

                </strong>

            </div>

            <div class="baris">

                <span>PPN</span>

                <strong>

                    <?= pj_rupiah(
                        $penjualan['ppn']
                    ); ?>

                </strong>

            </div>

            <hr class="garis">

            <div class="baris total">

                <span>TOTAL</span>

                <span>

                    <?= pj_rupiah(
                        $penjualan['total_nilai']
                    ); ?>

                </span>

            </div>

            <hr class="garis">

            <!-- FOOTER NOTA -->

            <div class="nota-footer">

                <p>

                    Jumlah jenis barang:
                    <?= count($details); ?>

                </p>

                <p>

                    Terima kasih atas pembelian Anda.

                </p>

                <p>

                    Nota ini merupakan bukti
                    transaksi penjualan.

                </p>

            </div>

        </section>

        <!-- TOMBOL AKSI -->

        <div class="actions">

            <a
                href="detail.php?id=<?= $id; ?>"
                class="btn btn-back"
            >
                Kembali
            </a>

            <button
                type="button"
                class="btn btn-print"
                onclick="window.print()"
            >
                Cetak Nota
            </button>

        </div>

    </main>

</body>

</html>
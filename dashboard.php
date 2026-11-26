<?php

require_once __DIR__ . '/auth/cek_role.php';
require_once __DIR__ . '/config/koneksi.php';

cek_role(['Admin', 'Gudang', 'Kasir']);

$page_title = 'Dashboard';
$current_page = 'dashboard';

// =========================================
// FUNGSI BANTU
// =========================================

function dash_e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function dash_rupiah($value): string
{
    return 'Rp ' . number_format(
        (float) $value, 0, ',', '.'
    );
}

$dash_errors = [];

// Mengambil data dan menangani kegagalan query

function dash_query(
    mysqli $koneksi,
    string $sql,
    string $label
): ?array
{
    global $dash_errors;

    try {

        $result = $koneksi->query($sql);

        $data = $result->fetch_all(MYSQLI_ASSOC);

        $result->free();

        return $data;

    } catch (mysqli_sql_exception $e) {

        error_log(
            'Dashboard - ' . $label . ': '
            . $e->getMessage()
        );

        $pesan = $label . ' gagal dimuat.';

        // Detail error hanya ditampilkan di localhost

        if (in_array(
            $_SERVER['REMOTE_ADDR'] ?? '',
            ['127.0.0.1', '::1'],
            true
        )) {
            $pesan .= ' ' . $e->getMessage();
        }

        $dash_errors[] = $pesan;

        return null;
    }
}

// Mengambil nilai statistik

function dash_nilai(
    mysqli $koneksi,
    string $sql,
    string $label
)
{
    $data = dash_query(
        $koneksi,
        $sql,
        $label
    );

    return $data[0]['total'] ?? null;
}

// =========================================
// KONFIGURASI DASHBOARD
// =========================================

$batas_stok = 5;

$show_sales = punya_akses(['Admin', 'Kasir']);

$show_gudang = punya_akses(['Admin', 'Gudang']);

$cards = [];

// =========================================
// A. STATISTIK BARANG
// =========================================

$cards[] = [
    'label' => 'Barang Aktif',
    'value' => dash_nilai(
        $koneksi,
        "SELECT COUNT(*) AS total
         FROM v_barang
         WHERE status = 1",
        'Barang Aktif'
    ),
    'icon' => '▣',
    'color' => 'blue'
];

$cards[] = [
    'label' => 'Jenis Barang Tersedia',
    'value' => dash_nilai(
        $koneksi,
        "SELECT COUNT(*) AS total
         FROM v_barang
         WHERE status = 1
         AND stok > 0",
        'Barang Tersedia'
    ),
    'icon' => '▤',
    'color' => 'green'
];

$cards[] = [
    'label' => 'Stok Menipis',
    'value' => dash_nilai(
        $koneksi,
        "SELECT COUNT(*) AS total
         FROM v_barang
         WHERE status = 1
         AND stok BETWEEN 1 AND 5",
        'Stok Menipis'
    ),
    'icon' => '!',
    'color' => 'orange'
];

$cards[] = [
    'label' => 'Stok Kosong',
    'value' => dash_nilai(
        $koneksi,
        "SELECT COUNT(*) AS total
         FROM v_barang
         WHERE status = 1
         AND stok = 0",
        'Stok Kosong'
    ),
    'icon' => '!',
    'color' => 'red'
];

// =========================================
// B. STATISTIK ADMIN DAN GUDANG
// =========================================

if ($show_gudang) {

    $cards[] = [
        'label' => 'Vendor Aktif',
        'value' => dash_nilai(
            $koneksi,
            "SELECT COUNT(*) AS total
             FROM v_vendor
             WHERE status = 'A'",
            'Vendor Aktif'
        ),
        'icon' => '▥',
        'color' => 'purple'
    ];

    $cards[] = [
        'label' => 'Total Pengadaan',
        'value' => dash_nilai(
            $koneksi,
            "SELECT COUNT(*) AS total
             FROM v_pengadaan",
            'Total Pengadaan'
        ),
        'icon' => '+',
        'color' => 'blue'
    ];

    $cards[] = [
        'label' => 'Pengadaan Dalam Proses',
        'value' => dash_nilai(
            $koneksi,
            "SELECT COUNT(*) AS total
             FROM v_pengadaan
             WHERE status = 'P'",
            'Pengadaan Dalam Proses'
        ),
        'icon' => '◷',
        'color' => 'orange'
    ];

    $cards[] = [
        'label' => 'Penerimaan Selesai',
        'value' => dash_nilai(
            $koneksi,
            "SELECT COUNT(*) AS total
             FROM v_penerimaan
             WHERE status = 'S'",
            'Penerimaan Selesai'
        ),
        'icon' => '↓',
        'color' => 'green'
    ];

}

// =========================================
// C. STATISTIK ADMIN DAN KASIR
// =========================================

$harian = [];

$terlaris = [];

$grafik_harian_valid = false;

$grafik_terlaris_valid = false;

$maks_harian = 1;

$maks_terlaris = 1;

$periode = '-';

if ($show_sales) {

    $cards[] = [
        'label' => 'Total Penjualan',
        'value' => dash_nilai(
            $koneksi,
            "SELECT COUNT(*) AS total
             FROM v_penjualan p
             WHERE EXISTS (
                 SELECT 1
                 FROM v_detail_penjualan d
                 WHERE d.idpenjualan = p.idpenjualan
             )",
            'Total Penjualan'
        ),
        'icon' => '↗',
        'color' => 'blue'
    ];

    $penjualan_hari_ini = dash_nilai(
        $koneksi,
        "SELECT COALESCE(SUM(total_nilai), 0) AS total
         FROM v_penjualan
         WHERE DATE(created_at) = CURDATE()",
        'Penjualan Hari Ini'
    );

    $cards[] = [
        'label' => 'Penjualan Hari Ini',
        'value' => $penjualan_hari_ini !== null
            ? dash_rupiah($penjualan_hari_ini)
            : null,
        'icon' => 'Rp',
        'color' => 'green'
    ];

    // =====================================
    // PERIODE GRAFIK
    // =====================================

    $tanggal_db = dash_query(
        $koneksi,
        "SELECT CURDATE() AS hari_ini",
        'Tanggal Database'
    );

    if (!empty($tanggal_db[0]['hari_ini'])) {

        $hari_ini = new DateTimeImmutable(
            $tanggal_db[0]['hari_ini']
        );

        $awal = $hari_ini->modify('-6 days');

        $periode = $awal->format('d/m/Y')
            . ' - '
            . $hari_ini->format('d/m/Y');

        // =====================================
        // D. GRAFIK PENJUALAN 7 HARI
        // =====================================

        for ($i = 0; $i < 7; $i++) {

            $tanggal = $awal
                ->modify("+$i days")
                ->format('Y-m-d');

            $harian[$tanggal] = 0;
        }

        $data_harian = dash_query(
            $koneksi,
            "SELECT
                DATE(created_at) AS tanggal,
                COALESCE(SUM(total_nilai), 0) AS total

             FROM v_penjualan

             WHERE created_at >=
                   CURDATE() - INTERVAL 6 DAY

             AND created_at <
                 CURDATE() + INTERVAL 1 DAY

             GROUP BY DATE(created_at)

             ORDER BY tanggal ASC",
            'Grafik Penjualan'
        );

        if ($data_harian !== null) {

            $grafik_harian_valid = true;

            foreach ($data_harian as $row) {

                if (array_key_exists(
                    $row['tanggal'],
                    $harian
                )) {

                    $harian[$row['tanggal']] =
                        (float) $row['total'];
                }
            }

            $maks_harian = max(
                1,
                max($harian)
            );
        }

        // =====================================
        // E. GRAFIK BARANG TERLARIS
        // =====================================

        $data_terlaris = dash_query(
            $koneksi,
            "SELECT
                d.idbarang,
                d.nama_barang,
                d.nama_satuan,
                SUM(d.jumlah) AS jumlah_terjual,
                SUM(d.subtotal) AS total

             FROM v_detail_penjualan d

             INNER JOIN v_penjualan p
                ON d.idpenjualan = p.idpenjualan

             WHERE p.created_at >=
                   CURDATE() - INTERVAL 6 DAY

             AND p.created_at <
                 CURDATE() + INTERVAL 1 DAY

             GROUP BY
                d.idbarang,
                d.nama_barang,
                d.nama_satuan

             ORDER BY
                total DESC,
                d.idbarang ASC

             LIMIT 5",
            'Grafik Barang Terlaris'
        );

        if ($data_terlaris !== null) {

            $grafik_terlaris_valid = true;

            $terlaris = $data_terlaris;

            foreach ($terlaris as $row) {

                $maks_terlaris = max(
                    $maks_terlaris,
                    (float) $row['total']
                );

            }
        }
    }
}

// =========================================
// F. INFORMASI STOK BARANG
// =========================================

$barang = dash_query(
    $koneksi,
    "SELECT
        idbarang,
        nama,
        nama_satuan,
        harga,
        stok

     FROM v_barang

     WHERE status = 1

     ORDER BY
        CASE
            WHEN stok = 0 THEN 0
            WHEN stok <= 5 THEN 1
            ELSE 2
        END,

        stok ASC,
        nama ASC

     LIMIT 10",
    'Informasi Stok Barang'
);

// =========================================
// HEADER
// =========================================

require __DIR__ . '/includes/header.php';

?>

<style>

    .dash-chart-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
        margin-bottom: 20px;
        align-items: start;
    }

    .dash-chart-item {
        margin-bottom: 18px;
    }

    .dash-chart-label {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
        font-size: 13px;
        margin-bottom: 8px;
    }

    .dash-chart-track {
        width: 100%;
        height: 12px;
        background: #e2e8f0;
        border-radius: 6px;
        overflow: hidden;
    }

    .dash-chart-bar {
        height: 100%;
        background: #2563eb;
        border-radius: 6px;
    }

    .dash-chart-green {
        background: #16a34a;
    }

    .dash-chart-caption {
        color: #64748b;
        font-size: 12px;
        margin-top: 6px;
    }

    .dash-chart-total {
        border-top: 1px solid #e2e8f0;
        padding-top: 14px;
        margin-top: 15px;
        font-size: 14px;
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
    }

    .dash-error {
        padding: 14px 18px;
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 8px;
        color: #991b1b;
        margin-bottom: 20px;
        font-size: 13px;
        line-height: 1.7;
        overflow-wrap: anywhere;
    }

    .dash-error ul {
        padding-left: 20px;
        margin: 8px 0 0;
    }

    @media (max-width: 1000px) {

        .dash-chart-grid {
            grid-template-columns: 1fr;
        }

    }

</style>


<!-- INFORMASI ERROR -->

<?php if (!empty($dash_errors)): ?>

    <div class="dash-error">

        <strong>
            Sebagian data Dashboard gagal dimuat.
        </strong>

        <ul>

            <?php foreach ($dash_errors as $error): ?>

                <li>
                    <?= dash_e($error); ?>
                </li>

            <?php endforeach; ?>

        </ul>

    </div>

<?php endif; ?>


<!-- WELCOME BANNER -->

<section class="welcome-banner">

    <div>

        <p class="welcome-label">
            SISTEM INFORMASI INVENTORI
        </p>

        <h1>

            Selamat datang,
            <?= dash_e($_SESSION['username'] ?? ''); ?>!

        </h1>

        <p>
            Kelola persediaan dan transaksi barang
            dengan mudah melalui STOKFLOW.
        </p>

    </div>

    <div class="welcome-role">

        <small>Hak Akses</small>

        <strong>

            <?= dash_e(
                $_SESSION['nama_role'] ?? ''
            ); ?>

        </strong>

    </div>

</section>


<!-- RINGKASAN INVENTORI -->

<div class="section-heading">

    <h2>Ringkasan Inventori</h2>

    <p>
        Informasi terkini dari database STOKFLOW.
    </p>

</div>


<!-- KARTU STATISTIK -->

<section class="stats-grid">

    <?php foreach ($cards as $card): ?>

        <div class="stat-card">

            <div class="stat-icon <?= dash_e($card['color']); ?>">

                <?= dash_e($card['icon']); ?>

            </div>

            <div class="stat-info">

                <p>
                    <?= dash_e($card['label']); ?>
                </p>

                <h2>

                    <?php if ($card['value'] === null): ?>

                        -

                    <?php elseif (is_numeric($card['value'])): ?>

                        <?= number_format(
                            (float) $card['value'],
                            0,
                            ',',
                            '.'
                        ); ?>

                    <?php else: ?>

                        <?= dash_e($card['value']); ?>

                    <?php endif; ?>

                </h2>

            </div>

        </div>

    <?php endforeach; ?>

</section>


<!-- GRAFIK PENJUALAN -->

<?php if ($show_sales): ?>

    <div class="section-heading">

        <h2>Analisis Penjualan</h2>

        <p>
            Periode: <?= dash_e($periode); ?>
        </p>

    </div>

    <div class="dash-chart-grid">

        <!-- GRAFIK PENJUALAN HARIAN -->

        <section class="panel">

            <div class="panel-header">

                <h2>Penjualan 7 Hari Terakhir</h2>

                <p>
                    Total nilai penjualan termasuk PPN.
                </p>

            </div>

            <div class="panel-body">

                <?php if (!$grafik_harian_valid): ?>

                    <p class="empty-data">
                        Grafik penjualan gagal dimuat.
                    </p>

                <?php else: ?>

                    <?php foreach ($harian as $tanggal => $total): ?>

                        <?php

                        $persentase = max(
                            0,
                            min(
                                100,
                                ($total / $maks_harian) * 100
                            )
                        );

                        ?>

                        <div class="dash-chart-item">

                            <div class="dash-chart-label">

                                <span>

                                    <?= date(
                                        'd/m/Y',
                                        strtotime($tanggal)
                                    ); ?>

                                </span>

                                <strong>

                                    <?= dash_rupiah($total); ?>

                                </strong>

                            </div>

                            <div class="dash-chart-track">

                                <div
                                    class="dash-chart-bar"
                                    style="width: <?= $persentase; ?>%;"
                                ></div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                    <div class="dash-chart-total">

                        <span>Total Penjualan 7 Hari</span>

                        <strong>

                            <?= dash_rupiah(
                                array_sum($harian)
                            ); ?>

                        </strong>

                    </div>

                <?php endif; ?>

            </div>

        </section>


        <!-- GRAFIK BARANG TERLARIS -->

        <section class="panel">

            <div class="panel-header">

                <h2>5 Barang Terlaris</h2>

                <p>
                    Berdasarkan nilai penjualan sebelum PPN.
                </p>

            </div>

            <div class="panel-body">

                <?php if (!$grafik_terlaris_valid): ?>

                    <p class="empty-data">

                        Grafik barang terlaris gagal dimuat.

                    </p>

                <?php elseif (empty($terlaris)): ?>

                    <p class="empty-data">

                        Belum ada transaksi penjualan
                        pada periode ini.

                    </p>

                <?php else: ?>

                    <?php foreach ($terlaris as $i => $row): ?>

                        <?php

                        $persentase = max(
                            0,
                            min(
                                100,
                                ((float) $row['total']
                                / $maks_terlaris) * 100
                            )
                        );

                        ?>

                        <div class="dash-chart-item">

                            <div class="dash-chart-label">

                                <strong>

                                    <?= $i + 1; ?>.

                                    <?= dash_e(
                                        $row['nama_barang']
                                    ); ?>

                                </strong>

                                <strong>

                                    <?= dash_rupiah(
                                        $row['total']
                                    ); ?>

                                </strong>

                            </div>

                            <div class="dash-chart-track">

                                <div
                                    class="dash-chart-bar dash-chart-green"
                                    style="width: <?= $persentase; ?>%;"
                                ></div>

                            </div>

                            <p class="dash-chart-caption">

                                Terjual:

                                <?= number_format(
                                    (float) $row['jumlah_terjual'],
                                    0,
                                    ',',
                                    '.'
                                ); ?>

                                <?= dash_e(
                                    $row['nama_satuan']
                                ); ?>

                            </p>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </section>

    </div>

<?php endif; ?>


<!-- INFORMASI STOK BARANG -->

<section class="panel">

    <div class="panel-header">

        <h2>Informasi Stok Barang</h2>

        <p>
            Maksimal 10 barang aktif dengan prioritas
            stok kosong dan stok menipis.
        </p>

    </div>

    <div class="table-responsive">

        <table class="data-table">

            <thead>

                <tr>

                    <th>No</th>
                    <th>Nama Barang</th>
                    <th>Satuan</th>
                    <th>Harga Pokok</th>
                    <th>Stok</th>
                    <th>Keterangan</th>

                </tr>

            </thead>

            <tbody>

                <?php if ($barang === null): ?>

                    <tr>

                        <td colspan="6" class="empty-data">

                            Informasi stok gagal dimuat.

                        </td>

                    </tr>

                <?php elseif (empty($barang)): ?>

                    <tr>

                        <td colspan="6" class="empty-data">

                            Belum ada data barang aktif.

                        </td>

                    </tr>

                <?php else: ?>

                    <?php foreach ($barang as $i => $row): ?>

                        <?php

                        $stok = (int) $row['stok'];

                        ?>

                        <tr>

                            <td>

                                <?= $i + 1; ?>

                            </td>

                            <td>

                                <strong>

                                    <?= dash_e(
                                        $row['nama']
                                    ); ?>

                                </strong>

                            </td>

                            <td>

                                <?= dash_e(
                                    $row['nama_satuan']
                                ); ?>

                            </td>

                            <td>

                                <?= dash_rupiah(
                                    $row['harga']
                                ); ?>

                            </td>

                            <td>

                                <strong>

                                    <?= number_format(
                                        $stok,
                                        0,
                                        ',',
                                        '.'
                                    ); ?>

                                </strong>

                            </td>

                            <td>

                                <?php if ($stok === 0): ?>

                                    <span class="status danger">

                                        Stok Kosong

                                    </span>

                                <?php elseif ($stok <= $batas_stok): ?>

                                    <span class="status warning">

                                        Stok Menipis

                                    </span>

                                <?php else: ?>

                                    <span class="status success">

                                        Tersedia

                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

    <div class="panel-body">

        <p style="
            color: #64748b;
            font-size: 13px;
            line-height: 1.6;
        ">

            Keterangan: stok menipis apabila
            persediaan tersisa 1 sampai
            <?= $batas_stok; ?>
            berdasarkan satuan masing-masing barang.

        </p>

    </div>

</section>

<?php

require __DIR__ . '/includes/footer.php';

?>
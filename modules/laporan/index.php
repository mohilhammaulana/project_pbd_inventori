<?php

require_once __DIR__ . '/../../auth/cek_role.php';
require_once __DIR__ . '/../../config/koneksi.php';

cek_role(['Admin']);

$page_title = 'Laporan';
$current_page = 'laporan';

// =========================================
// FUNGSI BANTU
// =========================================

function lap_e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function lap_rupiah($value): string
{
    return 'Rp ' . number_format(
        $value, 0, ',', '.'
    );
}

function lap_get(string $key): string
{
    $value = $_GET[$key] ?? '';

    return is_string($value)
        ? trim($value)
        : '';
}

function lap_validasi_tanggal(string $tanggal): bool
{
    if ($tanggal === '') {
        return true;
    }

    $date = DateTime::createFromFormat(
        '!Y-m-d',
        $tanggal
    );

    return $date &&
        $date->format('Y-m-d') === $tanggal;
}

// =========================================
// KONFIGURASI JENIS LAPORAN
// =========================================

$laporan = [

    // 1. LAPORAN STOK BARANG

    'stok' => [

        'judul' => 'Laporan Stok Barang',
        'menu' => 'Stok Barang',

        'sql' => "SELECT t.*
                  FROM v_barang t",

        'search' => [
            't.nama',
            't.nama_satuan'
        ],

        'tanggal' => null,

        'status' => [
            '1' => 'Aktif',
            '0' => 'Tidak Aktif'
        ],

        'order' => 't.nama ASC',

        'kolom' => [
            'idbarang' => ['ID Barang', 'id'],
            'nama' => ['Nama Barang', 'text'],
            'jenis' => ['Jenis', 'jenis'],
            'nama_satuan' => ['Satuan', 'text'],
            'harga' => ['Harga Pokok', 'rupiah'],
            'stok' => ['Stok', 'number'],
            'status' => ['Status', 'status']
        ],

        'prefix' => 'BRG',
        'total' => null,

        'catatan' =>
            'Menampilkan kondisi stok barang saat ini,
             bukan saldo stok pada tanggal tertentu.'

    ],

    // 2. LAPORAN PENGADAAN

    'pengadaan' => [

        'judul' => 'Laporan Pengadaan',
        'menu' => 'Pengadaan',

        'sql' => "SELECT t.*
                  FROM v_pengadaan t",

        'search' => [
            't.nama_vendor',
            't.username',
            'CAST(t.idpengadaan AS CHAR)'
        ],

        'tanggal' => 't.`timestamp`',

        'status' => [
            'P' => 'Dalam Proses',
            'S' => 'Selesai'
        ],

        'order' => 't.idpengadaan DESC',

        'kolom' => [
            'idpengadaan' => ['ID Pengadaan', 'id'],
            'timestamp' => ['Tanggal', 'tanggal'],
            'nama_vendor' => ['Vendor', 'text'],
            'username' => ['User', 'text'],
            'subtotal_nilai' => ['Subtotal', 'rupiah'],
            'ppn' => ['PPN', 'rupiah'],
            'total_nilai' => ['Total', 'rupiah'],
            'status' => ['Status', 'status']
        ],

        'prefix' => 'PG',
        'total' => 'total_nilai',
        'label_total' => 'Total Nilai Pengadaan',

        'catatan' =>
            'Nilai pengadaan merupakan nilai pesanan,
             bukan bukti pembayaran kepada vendor.'

    ],

    // 3. LAPORAN PENERIMAAN

    'penerimaan' => [

        'judul' => 'Laporan Penerimaan',
        'menu' => 'Penerimaan',

        'sql' => "SELECT
                    t.*,
                    COALESCE(d.nilai, 0)
                        AS nilai_penerimaan

                  FROM v_penerimaan t

                  LEFT JOIN (

                      SELECT
                          idpenerimaan,
                          SUM(sub_total_terima) AS nilai

                      FROM v_detail_penerimaan

                      GROUP BY idpenerimaan

                  ) d

                  ON t.idpenerimaan = d.idpenerimaan",

        'search' => [
            't.nama_vendor',
            't.username',
            'CAST(t.idpenerimaan AS CHAR)',
            'CAST(t.idpengadaan AS CHAR)'
        ],

        'tanggal' => 't.created_at',

        'status' => [
            'P' => 'Dalam Proses',
            'S' => 'Selesai'
        ],

        'order' => 't.idpenerimaan DESC',

        'kolom' => [
            'idpenerimaan' => ['ID Penerimaan', 'id'],
            'idpengadaan' => ['ID Pengadaan', 'pengadaan'],
            'created_at' => ['Tanggal', 'tanggal'],
            'nama_vendor' => ['Vendor', 'text'],
            'username' => ['User', 'text'],
            'nilai_penerimaan' => ['Nilai Penerimaan', 'rupiah'],
            'status' => ['Status', 'status']
        ],

        'prefix' => 'PN',
        'total' => 'nilai_penerimaan',
        'label_total' => 'Total Nilai Penerimaan',

        'catatan' =>
            'Nilai penerimaan dihitung dari detail
             penerimaan sebelum PPN. Status Selesai
             menunjukkan penerimaan telah diselesaikan.'

    ],

    // 4. LAPORAN PENJUALAN

    'penjualan' => [

        'judul' => 'Laporan Penjualan',
        'menu' => 'Penjualan',

        'sql' => "SELECT t.*
                  FROM v_penjualan t",

        'search' => [
            't.username',
            'CAST(t.idpenjualan AS CHAR)'
        ],

        'tanggal' => 't.created_at',
        'status' => [],

        'order' => 't.idpenjualan DESC',

        'kolom' => [
            'idpenjualan' => ['ID Penjualan', 'id'],
            'created_at' => ['Tanggal', 'tanggal'],
            'username' => ['User', 'text'],
            'persen' => ['Margin', 'persen'],
            'subtotal_nilai' => ['Subtotal', 'rupiah'],
            'ppn' => ['PPN', 'rupiah'],
            'total_nilai' => ['Total', 'rupiah']
        ],

        'prefix' => 'PJ',
        'total' => 'total_nilai',
        'label_total' => 'Total Nilai Penjualan',

        'catatan' =>
            'Nilai penjualan mengikuti nominal transaksi
             yang tersimpan dalam database, termasuk PPN.'

    ],

    // 5. LAPORAN RETUR

    'retur' => [

        'judul' => 'Laporan Retur Barang',
        'menu' => 'Retur Barang',

        'sql' => "SELECT
                    t.*,
                    COALESCE(d.nilai, 0) AS total_retur

                  FROM v_retur t

                  LEFT JOIN (

                      SELECT
                          idretur,
                          SUM(nilai_retur) AS nilai

                      FROM v_detail_retur

                      GROUP BY idretur

                  ) d

                  ON t.idretur = d.idretur",

        'search' => [
            't.nama_vendor',
            't.username',
            'CAST(t.idretur AS CHAR)',
            'CAST(t.idpenerimaan AS CHAR)'
        ],

        'tanggal' => 't.created_at',
        'status' => [],

        'order' => 't.idretur DESC',

        'kolom' => [
            'idretur' => ['ID Retur', 'id'],
            'idpenerimaan' => ['ID Penerimaan', 'penerimaan'],
            'created_at' => ['Tanggal', 'tanggal'],
            'nama_vendor' => ['Vendor', 'text'],
            'username' => ['User', 'text'],
            'total_retur' => ['Nilai Retur', 'rupiah']
        ],

        'prefix' => 'RT',
        'total' => 'total_retur',
        'label_total' => 'Total Nilai Retur',

        'catatan' =>
            'Nilai retur dihitung berdasarkan harga
             satuan penerimaan dikalikan jumlah
             barang yang diretur, sebelum PPN.'

    ]

];

// =========================================
// MEMILIH JENIS LAPORAN
// =========================================

$jenis = lap_get('jenis');

if (!isset($laporan[$jenis])) {
    $jenis = 'stok';
}

$config = $laporan[$jenis];

// =========================================
// MENGAMBIL FILTER
// =========================================

$keyword = lap_get('keyword');
$dari = lap_get('dari');
$sampai = lap_get('sampai');
$status = lap_get('status');

$errors = [];

if ($config['tanggal'] === null) {

    $dari = '';
    $sampai = '';
}

if (!array_key_exists($status, $config['status'])) {

    $status = '';
}

if (!lap_validasi_tanggal($dari)) {

    $errors[] = 'Tanggal awal tidak valid.';
}

if (!lap_validasi_tanggal($sampai)) {

    $errors[] = 'Tanggal akhir tidak valid.';
}

if (
    empty($errors) &&
    $dari !== '' &&
    $sampai !== '' &&
    $dari > $sampai
) {

    $errors[] =
        'Tanggal awal tidak boleh melebihi tanggal akhir.';
}

// =========================================
// MENYUSUN QUERY SQL
// =========================================

$sql = $config['sql'];

$where = [];
$params = [];
$types = '';

// Filter pencarian

if ($keyword !== '') {

    $searchConditions = [];

    foreach ($config['search'] as $column) {

        $searchConditions[] = "$column LIKE ?";

        $params[] = '%' . $keyword . '%';

        $types .= 's';
    }

    $where[] = '(' .
        implode(' OR ', $searchConditions) .
        ')';
}

// Filter tanggal awal

if (
    $config['tanggal'] !== null &&
    $dari !== ''
) {

    $where[] =
        "DATE({$config['tanggal']}) >= ?";

    $params[] = $dari;

    $types .= 's';
}

// Filter tanggal akhir

if (
    $config['tanggal'] !== null &&
    $sampai !== ''
) {

    $where[] =
        "DATE({$config['tanggal']}) <= ?";

    $params[] = $sampai;

    $types .= 's';
}

// Filter status

if ($status !== '') {

    $where[] = 't.status = ?';

    $params[] = $status;

    $types .= 's';
}

if (!empty($where)) {

    $sql .= ' WHERE ' .
        implode(' AND ', $where);
}

$sql .= ' ORDER BY ' . $config['order'];

// =========================================
// MENJALANKAN QUERY
// =========================================

$data = [];

if (empty($errors)) {

    try {

        $stmt = $koneksi->prepare($sql);

        if (!empty($params)) {

            $stmt->bind_param(
                $types,
                ...$params
            );
        }

        $stmt->execute();

        $data = $stmt->get_result()
            ->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

    } catch (mysqli_sql_exception $e) {

        error_log($e->getMessage());

        $errors[] =
            'Laporan gagal dimuat. Periksa koneksi dan View.';
    }
}

// =========================================
// MENGHITUNG RINGKASAN
// =========================================

$jumlah_data = count($data);

$total = 0;
$jumlah_aktif = 0;
$jumlah_nonaktif = 0;
$stok_habis = 0;

foreach ($data as $row) {

    if ($config['total'] !== null) {

        $total += (int) $row[$config['total']];
    }

    if ($jenis === 'stok') {

        if ((int) $row['status'] === 1) {

            $jumlah_aktif++;

            if ((int) $row['stok'] === 0) {

                $stok_habis++;
            }

        } else {

            $jumlah_nonaktif++;
        }
    }
}

// =========================================
// KETERANGAN PERIODE
// =========================================

$periode = 'Semua Periode';

if ($jenis === 'stok') {

    $periode = 'Kondisi stok saat laporan dimuat';

} elseif ($dari !== '' && $sampai !== '') {

    $periode = $dari . ' sampai ' . $sampai;

} elseif ($dari !== '') {

    $periode = 'Sejak ' . $dari;

} elseif ($sampai !== '') {

    $periode = 'Sampai ' . $sampai;
}

// =========================================
// MENGAMBIL NILAI KOLOM
// =========================================

function lap_value(array $row, string $field)
{
    // Mendukung kedua alias View Penjualan

    if ($field === 'persen') {

        return $row['persen']
            ?? $row['margin']
            ?? null;
    }

    return $row[$field] ?? null;
}

// =========================================
// FORMAT DATA
// =========================================

function lap_plain(
    $value,
    string $type,
    array $config
): string
{
    if ($value === null) {

        return '-';
    }

    switch ($type) {

        case 'id':

            return $config['prefix'] . '-' .
                str_pad(
                    (string) $value,
                    $config['prefix'] === 'BRG'
                        ? 3 : 5,
                    '0',
                    STR_PAD_LEFT
                );

        case 'pengadaan':

            return 'PG-' . str_pad(
                (string) $value,
                5,
                '0',
                STR_PAD_LEFT
            );

        case 'penerimaan':

            return 'PN-' . str_pad(
                (string) $value,
                5,
                '0',
                STR_PAD_LEFT
            );

        case 'rupiah':

            return lap_rupiah($value);

        case 'tanggal':

            $time = strtotime((string) $value);

            return $time !== false
                ? date('d/m/Y H:i', $time)
                : '-';

        case 'number':

            return number_format(
                (int) $value,
                0,
                ',',
                '.'
            );

        case 'persen':

            return $value . '%';

        case 'jenis':

            return $value === 'M'
                ? 'Makanan'
                : 'Non Makanan';

        case 'status':

            return $config['status'][(string) $value]
                ?? (string) $value;

        default:

            return (string) $value;
    }
}

// Format khusus tampilan HTML

function lap_format(
    $value,
    string $type,
    array $config
): string
{
    $label = lap_plain(
        $value,
        $type,
        $config
    );

    if ($type === 'status' && $value !== null) {

        $status = (string) $value;

        $class = 'rp-status-process';

        if (in_array($status, ['1', 'S'], true)) {

            $class = 'rp-status-success';
        }

        if ($status === '0') {

            $class = 'rp-status-danger';
        }

        return '<span class="rp-status ' .
            $class . '">' .
            lap_e($label) .
            '</span>';
    }

    return lap_e($label);
}

// =========================================
// FITUR BARU: EXPORT CSV
// =========================================

// Mencegah teks dari database dianggap
// sebagai formula oleh aplikasi spreadsheet.

function lap_csv_safe(string $value): string
{
    if (
        preg_match(
            '/^[\s\x00-\x1F]*[=+\-@]/u',
            $value
        ) === 1
        ||
        preg_match(
            '/^[\t\r\n]/',
            $value
        ) === 1
    ) {

        return "'" . $value;
    }

    return $value;
}

// Format khusus isi file CSV

function lap_csv_value(
    $value,
    string $type,
    array $config
)
{
    if ($value === null) {

        return '';
    }

    // Nominal dan jumlah disimpan sebagai angka
    // agar dapat dihitung menggunakan Excel.

    if (in_array(
        $type,
        ['rupiah', 'number'],
        true
    )) {

        return (int) $value;
    }

    // Margin disimpan sebagai nilai persen.
    // Contoh: 20 berarti 20 persen.

    if ($type === 'persen') {

        return str_replace(
            '.',
            ',',
            (string) (float) $value
        );
    }

    // Tanggal menggunakan format standar.

    if ($type === 'tanggal') {

        $time = strtotime((string) $value);

        return $time !== false
            ? date('Y-m-d H:i:s', $time)
            : '';
    }

    return lap_csv_safe(
        lap_plain(
            $value,
            $type,
            $config
        )
    );
}

// Membuat file CSV dari hasil query
// yang sudah difilter.

if (
    lap_get('export') === 'csv' &&
    empty($errors)
) {

    $filename = 'STOKFLOW_Laporan_' .
        $jenis . '_' .
        date('Y-m-d_H-i-s') .
        '.csv';

    header(
        'Content-Type: text/csv; charset=UTF-8'
    );

    header(
        'Content-Disposition: attachment; filename="' .
        $filename . '"'
    );

    header('Cache-Control: no-store');

    header('X-Content-Type-Options: nosniff');

    $output = fopen('php://output', 'w');

    // UTF-8 BOM agar karakter dapat
    // dibaca dengan baik oleh Excel.

    fwrite($output, "\xEF\xBB\xBF");

    // Header kolom CSV

    $headers = ['No'];

    foreach ($config['kolom'] as $column) {

        $label = $column[0];

        if ($column[1] === 'rupiah') {

            $label .= ' (Rp)';
        }

        if ($column[1] === 'persen') {

            $label .= ' (%)';
        }

        $headers[] = $label;
    }

    fputcsv(
        $output,
        $headers,
        ';',
        '"',
        ''
    );

    // Isi data CSV

    foreach ($data as $i => $row) {

        $csvRow = [$i + 1];

        foreach (
            $config['kolom'] as $field => $column
        ) {

            $value = lap_value(
                $row,
                $field
            );

            $csvRow[] = lap_csv_value(
                $value,
                $column[1],
                $config
            );
        }

        fputcsv(
            $output,
            $csvRow,
            ';',
            '"',
            ''
        );
    }

    fclose($output);

    exit;
}

// =========================================
// MEMBUAT LINK EXPORT CSV
// =========================================

$export_url = '?' . http_build_query([

    'jenis' => $jenis,
    'keyword' => $keyword,
    'dari' => $dari,
    'sampai' => $sampai,
    'status' => $status,
    'export' => 'csv'

]);

// =========================================
// TAMPILAN LAPORAN
// =========================================

require __DIR__ . '/../../includes/header.php';

?>

<link
    rel="stylesheet"
    href="/stokflow/modules/laporan/laporan.css"
>

<div class="rp-page">

    <div class="section-heading rp-no-print">

        <h2>Laporan</h2>

        <p>
            Rekapitulasi data inventori dan
            transaksi STOKFLOW.
        </p>

    </div>

    <!-- PILIHAN JENIS LAPORAN -->

    <section class="panel rp-no-print">

        <div class="panel-header rp-panel-header">

            <h2>Pilih Jenis Laporan</h2>

        </div>

        <div class="panel-body">

            <div class="rp-tabs">

                <?php foreach ($laporan as $key => $item): ?>

                    <a
                        href="?jenis=<?= lap_e($key); ?>"
                        class="rp-tab
                        <?= $jenis === $key
                            ? 'rp-active' : ''; ?>"
                    >

                        <?= lap_e($item['menu']); ?>

                    </a>

                <?php endforeach; ?>

            </div>

        </div>

    </section>

    <!-- FILTER LAPORAN -->

    <section class="panel rp-no-print">

        <div class="panel-header rp-panel-header">

            <h2>Filter Laporan</h2>

        </div>

        <div class="panel-body">

            <form method="GET" class="rp-filter">

                <input
                    type="hidden"
                    name="jenis"
                    value="<?= lap_e($jenis); ?>"
                >

                <?php if ($config['tanggal'] !== null): ?>

                    <div class="form-group">

                        <label for="dari">
                            Dari Tanggal
                        </label>

                        <input
                            type="date"
                            name="dari"
                            id="dari"
                            class="form-control"
                            value="<?= lap_e($dari); ?>"
                        >

                    </div>

                    <div class="form-group">

                        <label for="sampai">
                            Sampai Tanggal
                        </label>

                        <input
                            type="date"
                            name="sampai"
                            id="sampai"
                            class="form-control"
                            value="<?= lap_e($sampai); ?>"
                        >

                    </div>

                <?php endif; ?>

                <div class="form-group">

                    <label for="keyword">
                        Pencarian
                    </label>

                    <input
                        type="text"
                        name="keyword"
                        id="keyword"
                        class="form-control"
                        placeholder="Cari data..."
                        value="<?= lap_e($keyword); ?>"
                    >

                </div>

                <?php if (!empty($config['status'])): ?>

                    <div class="form-group">

                        <label for="status">
                            Status
                        </label>

                        <select
                            name="status"
                            id="status"
                            class="form-control"
                        >

                            <option value="">
                                Semua Status
                            </option>

                            <?php foreach (
                                $config['status'] as $value => $label
                            ): ?>

                                <option
                                    value="<?= lap_e($value); ?>"
                                    <?= $status === (string) $value
                                        ? 'selected' : ''; ?>
                                >

                                    <?= lap_e($label); ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                <?php endif; ?>

                <div class="rp-filter-actions">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Filter
                    </button>

                    <a
                        href="?jenis=<?= lap_e($jenis); ?>"
                        class="btn btn-secondary"
                    >
                        Reset
                    </a>

                </div>

            </form>

        </div>

    </section>

    <!-- NOTIFIKASI ERROR -->

    <?php foreach ($errors as $error): ?>

        <div class="alert-error">

            <?= lap_e($error); ?>

        </div>

    <?php endforeach; ?>

    <!-- HASIL LAPORAN -->

    <?php if (empty($errors)): ?>

        <section class="panel rp-report">

            <div class="panel-header rp-panel-header rp-no-print">

                <div>

                    <h2>

                        <?= lap_e($config['judul']); ?>

                    </h2>

                    <p>

                        <?= $jumlah_data; ?> data ditemukan

                    </p>

                </div>

                <!-- TOMBOL EXPORT DAN CETAK -->

                <div style="
                    display: flex;
                    gap: 10px;
                    align-items: center;
                    flex-wrap: wrap;
                ">

                    <a
                        href="<?= lap_e($export_url); ?>"
                        class="btn btn-primary"
                        style="
                            background: #16a34a;
                            border-color: #16a34a;
                            color: #ffffff;
                        "
                    >
                        Export CSV
                    </a>

                    <button
                        type="button"
                        class="btn btn-primary"
                        onclick="window.print()"
                    >
                        Cetak Laporan
                    </button>

                </div>

            </div>

            <div class="panel-body">

                <!-- JUDUL CETAK -->

                <div class="rp-print-heading">

                    <h1>STOKFLOW</h1>

                    <p>
                        Sistem Informasi Inventori Barang
                    </p>

                    <h2>

                        <?= lap_e($config['judul']); ?>

                    </h2>

                </div>

                <!-- INFORMASI LAPORAN -->

                <div class="rp-meta">

                    <div>

                        <span>Periode</span>

                        <strong>

                            <?= lap_e($periode); ?>

                        </strong>

                    </div>

                    <div>

                        <span>Tanggal Laporan</span>

                        <strong>

                            <?= date('d/m/Y H:i'); ?>

                        </strong>

                    </div>

                    <div>

                        <span>Dibuat Oleh</span>

                        <strong>

                            <?= lap_e(
                                $_SESSION['username'] ?? 'Admin'
                            ); ?>

                        </strong>

                    </div>

                    <div>

                        <span>Jumlah Data</span>

                        <strong>

                            <?= $jumlah_data; ?> data

                        </strong>

                    </div>

                    <?php if ($keyword !== ''): ?>

                        <div>

                            <span>Kata Kunci</span>

                            <strong>

                                <?= lap_e($keyword); ?>

                            </strong>

                        </div>

                    <?php endif; ?>

                    <?php if (!empty($config['status'])): ?>

                        <div>

                            <span>Filter Status</span>

                            <strong>

                                <?= lap_e(
                                    $config['status'][$status]
                                        ?? 'Semua Status'
                                ); ?>

                            </strong>

                        </div>

                    <?php endif; ?>

                </div>

                <!-- TABEL LAPORAN -->

                <div class="table-responsive">

                    <table class="data-table rp-table">

                        <thead>

                            <tr>

                                <th>No</th>

                                <?php foreach (
                                    $config['kolom'] as $field => $column
                                ): ?>

                                    <th
                                        class="<?= in_array(
                                            $column[1],
                                            ['rupiah', 'number', 'persen'],
                                            true
                                        ) ? 'rp-number' : ''; ?>"
                                    >

                                        <?= lap_e($column[0]); ?>

                                    </th>

                                <?php endforeach; ?>

                            </tr>

                        </thead>

                        <tbody>

                            <?php if (empty($data)): ?>

                                <tr>

                                    <td
                                        colspan="<?= count(
                                            $config['kolom']
                                        ) + 1; ?>"
                                        class="rp-empty"
                                    >

                                        Tidak ada data laporan.

                                    </td>

                                </tr>

                            <?php endif; ?>

                            <?php foreach ($data as $i => $row): ?>

                                <tr>

                                    <td>

                                        <?= $i + 1; ?>

                                    </td>

                                    <?php foreach (
                                        $config['kolom'] as $field => $column
                                    ): ?>

                                        <?php

                                        $value = lap_value(
                                            $row,
                                            $field
                                        );

                                        ?>

                                        <td
                                            class="<?= in_array(
                                                $column[1],
                                                ['rupiah', 'number', 'persen'],
                                                true
                                            ) ? 'rp-number' : ''; ?>"
                                        >

                                            <?= lap_format(
                                                $value,
                                                $column[1],
                                                $config
                                            ); ?>

                                        </td>

                                    <?php endforeach; ?>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

                <!-- RINGKASAN LAPORAN STOK -->

                <?php if ($jenis === 'stok'): ?>

                    <div class="rp-summary-grid">

                        <div class="rp-summary-item">

                            <span>Jenis Barang</span>

                            <strong>

                                <?= $jumlah_data; ?>

                            </strong>

                        </div>

                        <div class="rp-summary-item">

                            <span>Barang Aktif</span>

                            <strong class="rp-green">

                                <?= $jumlah_aktif; ?>

                            </strong>

                        </div>

                        <div class="rp-summary-item">

                            <span>Barang Nonaktif</span>

                            <strong>

                                <?= $jumlah_nonaktif; ?>

                            </strong>

                        </div>

                        <div class="rp-summary-item">

                            <span>Barang Aktif Stok Habis</span>

                            <strong class="rp-red">

                                <?= $stok_habis; ?>

                            </strong>

                        </div>

                    </div>

                <?php else: ?>

                    <!-- RINGKASAN TRANSAKSI -->

                    <div class="rp-total-box">

                        <div class="rp-total-row">

                            <span>Jumlah Transaksi</span>

                            <strong>

                                <?= $jumlah_data; ?>

                            </strong>

                        </div>

                        <div class="rp-total-row rp-grand-total">

                            <span>

                                <?= lap_e(
                                    $config['label_total']
                                ); ?>

                            </span>

                            <strong>

                                <?= lap_rupiah($total); ?>

                            </strong>

                        </div>

                    </div>

                <?php endif; ?>

                <!-- CATATAN -->

                <div class="rp-note">

                    <?= lap_e($config['catatan']); ?>

                </div>

                <!-- FOOTER CETAK -->

                <div class="rp-print-footer">

                    Dokumen dihasilkan oleh STOKFLOW.

                </div>

            </div>

        </section>

    <?php endif; ?>

</div>

<?php

require __DIR__ . '/../../includes/footer.php';

?>
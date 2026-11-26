<?php

require_once __DIR__ . '/../../auth/cek_role.php';
require_once __DIR__ . '/../../config/koneksi.php';

cek_role(['Admin', 'Gudang']);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Mengamankan tampilan data
function ks_e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Format ID
function ks_format_id(
    string $prefix,
    int $id
): string
{
    return $prefix . '-' . str_pad(
        $id,
        5,
        '0',
        STR_PAD_LEFT
    );
}

// Mengambil nama jenis transaksi
function ks_jenis_transaksi(string $jenis): string
{
    switch ($jenis) {

        case 'M':
            return 'Penerimaan';

        case 'J':
            return 'Penjualan';

        case 'R':
            return 'Retur';

        default:
            return 'Tidak diketahui';
    }
}

// Mengatur warna status transaksi
function ks_badge(string $jenis): string
{
    switch ($jenis) {

        case 'M':
            return 'badge badge-success';

        case 'J':
            return 'badge badge-warning';

        case 'R':
            return 'badge badge-danger';

        default:
            return 'badge';
    }
}

// Mengambil parameter GET
function ks_get(string $key): string
{
    $value = $_GET[$key] ?? '';

    return is_string($value)
        ? trim($value)
        : '';
}

// Validasi tanggal
function ks_validasi_tanggal(string $tanggal): bool
{
    if ($tanggal === '') {

        return true;
    }

    $date = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $tanggal
    );

    return $date !== false &&
           $date->format('Y-m-d') === $tanggal;
}

// Mengambil daftar barang
function ks_ambil_barang(mysqli $koneksi): array
{
    $result = $koneksi->query(
        "SELECT
            idbarang,
            nama,
            nama_satuan,
            stok,
            status
         FROM v_barang
         ORDER BY nama ASC"
    );

    $data = $result->fetch_all(MYSQLI_ASSOC);

    $result->free();

    return $data;
}

// Mengambil referensi transaksi asal
function ks_referensi(array $data): string
{
    switch ($data['jenis_transaksi']) {

        case 'M':

            if ($data['idpenerimaan'] !== null) {

                return ks_format_id(
                    'PN',
                    (int) $data['idpenerimaan']
                );
            }

            break;

        case 'J':

            if ($data['idpenjualan'] !== null) {

                return ks_format_id(
                    'PJ',
                    (int) $data['idpenjualan']
                );
            }

            break;

        case 'R':

            if ($data['idretur'] !== null) {

                return ks_format_id(
                    'RT',
                    (int) $data['idretur']
                );
            }

            break;
    }

    return 'Detail #' . (int) $data['idtransaksi'];
}
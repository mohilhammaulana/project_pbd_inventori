<?php

require_once __DIR__ . '/../../auth/cek_role.php';
require_once __DIR__ . '/../../config/koneksi.php';

cek_role(['Admin']);

function escape_role($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function format_id_role($id)
{
    return 'ROL-' . str_pad(
        $id,
        3,
        '0',
        STR_PAD_LEFT
    );
}

function deskripsi_role($nama_role)
{
    switch ($nama_role) {

        case 'Admin':
            return 'Mengelola seluruh data dan sistem.';

        case 'Gudang':
            return 'Mengelola inventori dan transaksi gudang.';

        case 'Kasir':
            return 'Mengelola transaksi penjualan.';

        default:
            return 'Hak akses belum ditentukan.';
    }
}
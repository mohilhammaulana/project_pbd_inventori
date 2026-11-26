<?php

require_once __DIR__ . '/helper.php';

pg_flash(
    'error',
    'Pengadaan akan selesai otomatis setelah seluruh barang diterima melalui modul Penerimaan.'
);

pg_redirect('index.php');
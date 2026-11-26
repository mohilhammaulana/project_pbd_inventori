-- ==================================================
-- STOKFLOW
-- DATA MANIPULATION LANGUAGE (DML)
-- DATA AWAL MASTER
-- Jalankan hanya pada database baru yang masih kosong.
-- ==================================================

USE stokflow_db;

START TRANSACTION;


-- ==================================================
-- 1. DATA ROLE
-- ==================================================

INSERT INTO role (
    idrole,
    nama_role
)
VALUES
    (1, 'Admin'),
    (2, 'Gudang'),
    (3, 'Kasir');


-- ==================================================
-- 2. DATA USER
-- Password menggunakan BCrypt
-- ==================================================

INSERT INTO `user` (
    iduser,
    username,
    password,
    idrole,
    status
)
VALUES
(
    1,
    'admin',
    '$2y$12$STSxZ9d9zix6wXdfjRlq7Ow8Fvr.L7jLlyrww5cyFuMwcUAyfv2dC',
    1,
    1
),
(
    2,
    'gudang',
    '$2y$12$uo78BAGsNVfts23s0LG5kOPAQ2EUhUrpjlBlpfWkliCAIjXguS2.e',
    2,
    1
),
(
    3,
    'kasir',
    '$2y$12$JaJIH0YtXQj/PM1Beu5Uy.SjrYnN.fZB5fjGqdBydujCpgbu.ebri',
    3,
    1
);


-- ==================================================
-- 3. DATA SATUAN
-- ==================================================

INSERT INTO satuan (
    idsatuan,
    nama_satuan,
    status
)
VALUES
    (1, 'Kg', 1),
    (2, 'Liter', 1),
    (3, 'Pcs', 1),
    (4, 'Bungkus', 1),
    (5, 'Botol', 1);


-- ==================================================
-- 4. DATA VENDOR
-- ==================================================

INSERT INTO vendor (
    nama_vendor,
    badan_hukum,
    status
)
VALUES
    ('PT Sumber Pangan', 'Y', 'A'),
    ('CV Makmur Jaya', 'Y', 'A'),
    ('Toko Grosir Sejahtera', 'N', 'A');


-- ==================================================
-- 5. DATA BARANG
-- ==================================================

INSERT INTO barang (
    jenis,
    nama,
    idsatuan,
    status,
    nonaktif_karena_satuan,
    harga
)
VALUES
    ('M', 'Beras',         1, 1, 0, 14000),
    ('M', 'Gula Pasir',    1, 1, 0, 16000),
    ('M', 'Minyak Goreng', 2, 1, 0, 17000),
    ('M', 'Telur Ayam',    1, 1, 0, 28000),
    ('M', 'Mi Instan',     4, 1, 0, 3000),
    ('M', 'Tepung Terigu', 1, 1, 0, 12000),
    ('M', 'Kecap Manis',   5, 1, 0, 10000),
    ('M', 'Garam',         4, 1, 0, 2500),
    ('N', 'Sabun Mandi',   3, 1, 0, 4000),
    ('N', 'Detergen',      4, 1, 0, 5000);


-- ==================================================
-- 6. DATA MARGIN PENJUALAN
-- ==================================================

INSERT INTO margin_penjualan (
    persen,
    status,
    iduser
)
VALUES
    (20, 1, 1);


COMMIT;


-- ==================================================
-- SELESAI
-- ==================================================
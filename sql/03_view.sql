-- ==================================================
-- STOKFLOW
-- SQL VIEW
-- 6 View Data Master
-- 11 View Pendukung Transaksi
-- ==================================================

USE stokflow_db;


-- ==================================================
-- A. VIEW DATA MASTER
-- ==================================================


-- ==================================================
-- 1. VIEW DATA BARANG
-- ==================================================

CREATE OR REPLACE VIEW v_barang AS
SELECT
    b.idbarang,
    b.nama,
    b.jenis,
    b.idsatuan,
    s.nama_satuan,
    b.harga,
    b.status,
    COALESCE(k.stock, 0) AS stok,
    b.nonaktif_karena_satuan

FROM barang b

JOIN satuan s
    ON b.idsatuan = s.idsatuan

LEFT JOIN kartu_stok k
    ON k.idkartu_stok = (
        SELECT MAX(k2.idkartu_stok)
        FROM kartu_stok k2
        WHERE k2.idbarang = b.idbarang
    );


-- ==================================================
-- 2. VIEW DATA SATUAN
-- ==================================================

CREATE OR REPLACE VIEW v_satuan AS
SELECT
    idsatuan,
    nama_satuan,
    status

FROM satuan;


-- ==================================================
-- 3. VIEW DATA VENDOR
-- ==================================================

CREATE OR REPLACE VIEW v_vendor AS
SELECT
    idvendor,
    nama_vendor,
    badan_hukum,
    status,

    CASE
        WHEN badan_hukum = 'Y'
        THEN 'Badan hukum'
        ELSE 'Perorangan'
    END AS jenis_vendor,

    CASE
        WHEN status = 'A'
        THEN 'Aktif'
        ELSE 'Nonaktif'
    END AS status_vendor

FROM vendor;


-- ==================================================
-- 4. VIEW DATA ROLE
-- ==================================================

CREATE OR REPLACE VIEW v_role AS
SELECT
    r.idrole,
    r.nama_role,
    COUNT(u.iduser) AS jumlah_user

FROM role r

LEFT JOIN `user` u
    ON r.idrole = u.idrole

GROUP BY
    r.idrole,
    r.nama_role;


-- ==================================================
-- 5. VIEW DATA USER
-- ==================================================

CREATE OR REPLACE VIEW v_user AS
SELECT
    u.iduser,
    u.username,
    u.idrole,
    r.nama_role,
    u.status

FROM `user` u

JOIN role r
    ON u.idrole = r.idrole;


-- ==================================================
-- 6. VIEW MARGIN PENJUALAN
-- ==================================================

CREATE OR REPLACE VIEW v_margin_penjualan AS
SELECT
    m.idmargin_penjualan,
    m.created_at,
    m.persen,
    m.status,
    m.iduser,
    u.username,
    m.updated_at,

    CASE
        WHEN m.status = 1
        THEN 'Aktif'
        ELSE 'Tidak Aktif'
    END AS status_margin

FROM margin_penjualan m

JOIN `user` u
    ON m.iduser = u.iduser;


-- ==================================================
-- B. VIEW TRANSAKSI
-- ==================================================


-- ==================================================
-- 7. VIEW PENGADAAN
-- ==================================================

CREATE OR REPLACE VIEW v_pengadaan AS
SELECT
    p.idpengadaan,
    p.`timestamp`,
    p.user_iduser,
    u.username,
    p.vendor_idvendor,
    v.nama_vendor,
    p.status,
    p.subtotal_nilai,
    p.ppn,
    p.total_nilai

FROM pengadaan p

JOIN `user` u
    ON p.user_iduser = u.iduser

JOIN vendor v
    ON p.vendor_idvendor = v.idvendor;


-- ==================================================
-- 8. VIEW DETAIL PENGADAAN
-- ==================================================

CREATE OR REPLACE VIEW v_detail_pengadaan AS
SELECT
    dp.iddetail_pengadaan,
    dp.idpengadaan,
    dp.idbarang,
    b.nama AS nama_barang,
    s.nama_satuan,
    dp.harga_satuan,
    dp.jumlah,
    dp.sub_total

FROM detail_pengadaan dp

JOIN barang b
    ON dp.idbarang = b.idbarang

JOIN satuan s
    ON b.idsatuan = s.idsatuan;


-- ==================================================
-- 9. VIEW PROGRESS PENGADAAN
-- ==================================================

CREATE OR REPLACE VIEW v_progress_pengadaan AS
SELECT
    dg.iddetail_pengadaan,
    dg.idpengadaan,
    dg.idbarang,
    b.nama AS nama_barang,
    s.nama_satuan,
    dg.harga_satuan,
    dg.jumlah AS jumlah_pesan,

    COALESCE(
        pr.jumlah_terima, 0
    ) AS jumlah_diterima,

    dg.jumlah - COALESCE(
        pr.jumlah_terima, 0
    ) AS sisa_barang,

    dg.sub_total

FROM detail_pengadaan dg

JOIN barang b
    ON dg.idbarang = b.idbarang

JOIN satuan s
    ON b.idsatuan = s.idsatuan

LEFT JOIN (

    SELECT
        p.idpengadaan,
        dp.barang_idbarang,
        SUM(dp.jumlah_terima) AS jumlah_terima

    FROM detail_penerimaan dp

    JOIN penerimaan p
        ON dp.idpenerimaan = p.idpenerimaan

    WHERE p.status = 'S'

    GROUP BY
        p.idpengadaan,
        dp.barang_idbarang

) pr

    ON pr.idpengadaan = dg.idpengadaan
    AND pr.barang_idbarang = dg.idbarang;


-- ==================================================
-- 10. VIEW PENERIMAAN
-- ==================================================

CREATE OR REPLACE VIEW v_penerimaan AS
SELECT
    p.idpenerimaan,
    p.created_at,
    p.status,
    p.idpengadaan,
    p.iduser,
    u.username,
    v.nama_vendor

FROM penerimaan p

JOIN `user` u
    ON p.iduser = u.iduser

JOIN pengadaan pg
    ON p.idpengadaan = pg.idpengadaan

JOIN vendor v
    ON pg.vendor_idvendor = v.idvendor;


-- ==================================================
-- 11. VIEW DETAIL PENERIMAAN
-- ==================================================

CREATE OR REPLACE VIEW v_detail_penerimaan AS
SELECT
    dp.iddetail_penerimaan,
    dp.idpenerimaan,
    dp.barang_idbarang AS idbarang,
    b.nama AS nama_barang,
    s.nama_satuan,
    dp.jumlah_terima,
    dp.harga_satuan_terima,
    dp.sub_total_terima

FROM detail_penerimaan dp

JOIN barang b
    ON dp.barang_idbarang = b.idbarang

JOIN satuan s
    ON b.idsatuan = s.idsatuan;


-- ==================================================
-- 12. VIEW PENJUALAN
-- ==================================================

CREATE OR REPLACE VIEW v_penjualan AS
SELECT
    p.idpenjualan,
    p.created_at,
    p.subtotal_nilai,
    p.ppn,
    p.total_nilai,
    p.iduser,
    u.username,
    p.idmargin_penjualan,
    m.persen AS margin

FROM penjualan p

JOIN `user` u
    ON p.iduser = u.iduser

JOIN margin_penjualan m
    ON p.idmargin_penjualan =
       m.idmargin_penjualan;


-- ==================================================
-- 13. VIEW DETAIL PENJUALAN
-- ==================================================

CREATE OR REPLACE VIEW v_detail_penjualan AS
SELECT
    dp.iddetail_penjualan,
    dp.penjualan_idpenjualan AS idpenjualan,
    dp.idbarang,
    b.nama AS nama_barang,
    s.nama_satuan,
    dp.harga_satuan,
    dp.jumlah,
    dp.subtotal

FROM detail_penjualan dp

JOIN barang b
    ON dp.idbarang = b.idbarang

JOIN satuan s
    ON b.idsatuan = s.idsatuan;


-- ==================================================
-- 14. VIEW RETUR
-- ==================================================

CREATE OR REPLACE VIEW v_retur AS
SELECT
    r.idretur,
    r.created_at,
    r.idpenerimaan,
    r.iduser,
    u.username,
    pg.idpengadaan,
    v.nama_vendor

FROM `retur` r

JOIN `user` u
    ON r.iduser = u.iduser

JOIN penerimaan p
    ON r.idpenerimaan = p.idpenerimaan

JOIN pengadaan pg
    ON p.idpengadaan = pg.idpengadaan

JOIN vendor v
    ON pg.vendor_idvendor = v.idvendor;


-- ==================================================
-- 15. VIEW DETAIL RETUR
-- ==================================================

CREATE OR REPLACE VIEW v_detail_retur AS
SELECT
    dr.iddetail_retur,
    dr.idretur,
    dr.iddetail_penerimaan,
    dp.idpenerimaan,
    b.idbarang,
    b.nama AS nama_barang,
    s.nama_satuan,
    dp.harga_satuan_terima,
    dr.jumlah,
    dr.alasan,

    dr.jumlah * dp.harga_satuan_terima
        AS nilai_retur

FROM detail_retur dr

JOIN detail_penerimaan dp
    ON dr.iddetail_penerimaan =
       dp.iddetail_penerimaan

JOIN barang b
    ON dp.barang_idbarang = b.idbarang

JOIN satuan s
    ON b.idsatuan = s.idsatuan;


-- ==================================================
-- 16. VIEW PROGRESS RETUR
-- ==================================================

CREATE OR REPLACE VIEW v_progress_retur AS
SELECT
    dp.iddetail_penerimaan,
    dp.idpenerimaan,
    dp.barang_idbarang AS idbarang,
    vb.nama AS nama_barang,
    vb.nama_satuan,
    dp.harga_satuan_terima,
    dp.jumlah_terima,

    COALESCE(
        r.total_retur, 0
    ) AS sudah_retur,

    dp.jumlah_terima - COALESCE(
        r.total_retur, 0
    ) AS sisa_retur,

    vb.stok,

    GREATEST(
        0,
        LEAST(
            dp.jumlah_terima -
                COALESCE(r.total_retur, 0),
            vb.stok
        )
    ) AS maksimal_retur

FROM detail_penerimaan dp

JOIN v_barang vb
    ON dp.barang_idbarang = vb.idbarang

LEFT JOIN (

    SELECT
        iddetail_penerimaan,
        SUM(jumlah) AS total_retur

    FROM detail_retur

    GROUP BY iddetail_penerimaan

) r

    ON dp.iddetail_penerimaan =
       r.iddetail_penerimaan;


-- ==================================================
-- 17. VIEW KARTU STOK
-- ==================================================

CREATE OR REPLACE VIEW v_kartu_stok AS
SELECT
    k.idkartu_stok,
    k.created_at,
    k.idbarang,
    b.nama AS nama_barang,
    s.nama_satuan,
    k.jenis_transaksi,

    CASE
        WHEN k.jenis_transaksi = 'M'
        THEN 'Penerimaan'

        WHEN k.jenis_transaksi = 'J'
        THEN 'Penjualan'

        WHEN k.jenis_transaksi = 'R'
        THEN 'Retur'

        ELSE 'Tidak diketahui'
    END AS keterangan,

    k.masuk,
    k.keluar,
    k.stock,
    k.idtransaksi

FROM kartu_stok k

JOIN barang b
    ON k.idbarang = b.idbarang

JOIN satuan s
    ON b.idsatuan = s.idsatuan;


-- ==================================================
-- SELESAI
-- TOTAL 17 VIEW
-- ==================================================
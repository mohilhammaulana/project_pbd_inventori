-- ==================================================
-- STOKFLOW - TRIGGER
-- TOTAL: 13 TRIGGER
-- ==================================================

USE stokflow_db;

DELIMITER $$


-- ==================================================
-- A. TRIGGER PENGADAAN
-- ==================================================


-- 1. VALIDASI DETAIL PENGADAAN

DROP TRIGGER IF EXISTS trg_bi_detail_pengadaan$$

CREATE TRIGGER trg_bi_detail_pengadaan
BEFORE INSERT ON detail_pengadaan
FOR EACH ROW
BEGIN

    IF NEW.jumlah <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Jumlah barang harus lebih dari 0';
    END IF;

    IF NEW.harga_satuan < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Harga barang tidak valid';
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM pengadaan
        WHERE idpengadaan = NEW.idpengadaan
        AND status = 'P'
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Pengadaan tidak dalam proses';
    END IF;

    SET NEW.sub_total = fn_subtotal(
        NEW.harga_satuan,
        NEW.jumlah
    );

END$$


-- 2. HITUNG TOTAL PENGADAAN

DROP TRIGGER IF EXISTS trg_ai_detail_pengadaan$$

CREATE TRIGGER trg_ai_detail_pengadaan
AFTER INSERT ON detail_pengadaan
FOR EACH ROW
BEGIN

    DECLARE v_subtotal INT;
    DECLARE v_ppn INT;

    SELECT COALESCE(SUM(sub_total), 0)
    INTO v_subtotal
    FROM detail_pengadaan
    WHERE idpengadaan = NEW.idpengadaan;

    SET v_ppn = fn_ppn(v_subtotal, 11);

    UPDATE pengadaan
    SET
        subtotal_nilai = v_subtotal,
        ppn = v_ppn,
        total_nilai = v_subtotal + v_ppn
    WHERE idpengadaan = NEW.idpengadaan;

END$$


-- ==================================================
-- B. TRIGGER PENERIMAAN
-- ==================================================


-- 3. VALIDASI DETAIL PENERIMAAN

DROP TRIGGER IF EXISTS trg_bi_detail_penerimaan$$

CREATE TRIGGER trg_bi_detail_penerimaan
BEFORE INSERT ON detail_penerimaan
FOR EACH ROW
BEGIN

    DECLARE v_harga INT DEFAULT NULL;
    DECLARE v_jumlah_pesan INT DEFAULT NULL;
    DECLARE v_sudah_terima INT DEFAULT 0;
    DECLARE v_idpengadaan BIGINT DEFAULT NULL;

    SELECT idpengadaan
    INTO v_idpengadaan
    FROM penerimaan
    WHERE idpenerimaan = NEW.idpenerimaan
    AND status = 'P';

    IF v_idpengadaan IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Penerimaan tidak dalam proses';
    END IF;

    SELECT harga_satuan, jumlah
    INTO v_harga, v_jumlah_pesan
    FROM detail_pengadaan
    WHERE idpengadaan = v_idpengadaan
    AND idbarang = NEW.barang_idbarang;

    IF v_harga IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Barang tidak ada dalam pengadaan';
    END IF;

    SELECT COALESCE(SUM(dp.jumlah_terima), 0)
    INTO v_sudah_terima
    FROM detail_penerimaan dp
    JOIN penerimaan p
        ON dp.idpenerimaan = p.idpenerimaan
    WHERE p.idpengadaan = v_idpengadaan
    AND dp.barang_idbarang = NEW.barang_idbarang;

    IF NEW.jumlah_terima <= 0 OR
       v_sudah_terima + NEW.jumlah_terima
       > v_jumlah_pesan THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Jumlah penerimaan tidak valid';

    END IF;

    SET NEW.harga_satuan_terima = v_harga;

    SET NEW.sub_total_terima = fn_subtotal(
        v_harga,
        NEW.jumlah_terima
    );

END$$


-- 4. PENAMBAHAN STOK PENERIMAAN

DROP TRIGGER IF EXISTS trg_ai_detail_penerimaan$$

CREATE TRIGGER trg_ai_detail_penerimaan
AFTER INSERT ON detail_penerimaan
FOR EACH ROW
BEGIN

    DECLARE v_stok_lama INT;
    DECLARE v_stok_baru INT;

    SET v_stok_lama = fn_stok_barang(
        NEW.barang_idbarang
    );

    SET v_stok_baru =
        v_stok_lama + NEW.jumlah_terima;

    INSERT INTO kartu_stok (
        jenis_transaksi,
        masuk,
        keluar,
        stock,
        idtransaksi,
        idbarang
    )
    VALUES (
        'M',
        NEW.jumlah_terima,
        0,
        v_stok_baru,
        NEW.iddetail_penerimaan,
        NEW.barang_idbarang
    );

END$$


-- ==================================================
-- C. TRIGGER PENJUALAN
-- ==================================================


-- 5. VALIDASI DETAIL PENJUALAN

DROP TRIGGER IF EXISTS trg_bi_detail_penjualan$$

CREATE TRIGGER trg_bi_detail_penjualan
BEFORE INSERT ON detail_penjualan
FOR EACH ROW
BEGIN

    DECLARE v_harga INT DEFAULT NULL;
    DECLARE v_margin DOUBLE DEFAULT NULL;
    DECLARE v_stok INT;

    SELECT harga
    INTO v_harga
    FROM barang
    WHERE idbarang = NEW.idbarang
    AND status = 1;

    IF v_harga IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Barang tidak aktif atau tidak ditemukan';
    END IF;

    SELECT m.persen
    INTO v_margin
    FROM penjualan p
    JOIN margin_penjualan m
        ON p.idmargin_penjualan =
           m.idmargin_penjualan
    WHERE p.idpenjualan =
          NEW.penjualan_idpenjualan;

    IF v_margin IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Penjualan atau margin tidak ditemukan';
    END IF;

    SET v_stok = fn_stok_barang(NEW.idbarang);

    IF NEW.jumlah <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Jumlah penjualan tidak valid';
    END IF;

    IF NEW.jumlah > v_stok THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Stok barang tidak mencukupi';
    END IF;

    SET NEW.harga_satuan = fn_harga_jual(
        v_harga,
        v_margin
    );

    SET NEW.subtotal = fn_subtotal(
        NEW.harga_satuan,
        NEW.jumlah
    );

END$$


-- 6. PENGURANGAN STOK DAN TOTAL PENJUALAN

DROP TRIGGER IF EXISTS trg_ai_detail_penjualan$$

CREATE TRIGGER trg_ai_detail_penjualan
AFTER INSERT ON detail_penjualan
FOR EACH ROW
BEGIN

    DECLARE v_stok_lama INT;
    DECLARE v_stok_baru INT;
    DECLARE v_subtotal INT;
    DECLARE v_ppn INT;

    SET v_stok_lama = fn_stok_barang(
        NEW.idbarang
    );

    SET v_stok_baru =
        v_stok_lama - NEW.jumlah;

    INSERT INTO kartu_stok (
        jenis_transaksi,
        masuk,
        keluar,
        stock,
        idtransaksi,
        idbarang
    )
    VALUES (
        'J',
        0,
        NEW.jumlah,
        v_stok_baru,
        NEW.iddetail_penjualan,
        NEW.idbarang
    );

    SELECT COALESCE(SUM(subtotal), 0)
    INTO v_subtotal
    FROM detail_penjualan
    WHERE penjualan_idpenjualan =
          NEW.penjualan_idpenjualan;

    SET v_ppn = fn_ppn(v_subtotal, 11);

    UPDATE penjualan
    SET
        subtotal_nilai = v_subtotal,
        ppn = v_ppn,
        total_nilai = v_subtotal + v_ppn
    WHERE idpenjualan =
          NEW.penjualan_idpenjualan;

END$$


-- ==================================================
-- D. TRIGGER RETUR
-- ==================================================


-- 7. VALIDASI DETAIL RETUR

DROP TRIGGER IF EXISTS trg_bi_detail_retur$$

CREATE TRIGGER trg_bi_detail_retur
BEFORE INSERT ON detail_retur
FOR EACH ROW
BEGIN

    DECLARE v_idbarang INT DEFAULT NULL;
    DECLARE v_jumlah_terima INT;
    DECLARE v_sudah_retur INT;
    DECLARE v_stok INT;

    SELECT
        dp.barang_idbarang,
        dp.jumlah_terima
    INTO
        v_idbarang,
        v_jumlah_terima
    FROM detail_penerimaan dp
    JOIN retur r
        ON dp.idpenerimaan = r.idpenerimaan
    JOIN penerimaan p
        ON p.idpenerimaan = r.idpenerimaan
    WHERE dp.iddetail_penerimaan =
          NEW.iddetail_penerimaan
    AND r.idretur = NEW.idretur
    AND p.status = 'S';

    IF v_idbarang IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Detail penerimaan tidak valid';
    END IF;

    SELECT COALESCE(SUM(jumlah), 0)
    INTO v_sudah_retur
    FROM detail_retur
    WHERE iddetail_penerimaan =
          NEW.iddetail_penerimaan;

    SET v_stok = fn_stok_barang(v_idbarang);

    IF NEW.jumlah <= 0 OR
       v_sudah_retur + NEW.jumlah
       > v_jumlah_terima THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Jumlah retur tidak valid';

    END IF;

    IF NEW.jumlah > v_stok THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Stok tidak cukup untuk retur';
    END IF;

END$$


-- 8. PENGURANGAN STOK RETUR

DROP TRIGGER IF EXISTS trg_ai_detail_retur$$

CREATE TRIGGER trg_ai_detail_retur
AFTER INSERT ON detail_retur
FOR EACH ROW
BEGIN

    DECLARE v_idbarang INT;
    DECLARE v_stok_lama INT;
    DECLARE v_stok_baru INT;

    SELECT barang_idbarang
    INTO v_idbarang
    FROM detail_penerimaan
    WHERE iddetail_penerimaan =
          NEW.iddetail_penerimaan;

    SET v_stok_lama = fn_stok_barang(
        v_idbarang
    );

    SET v_stok_baru =
        v_stok_lama - NEW.jumlah;

    INSERT INTO kartu_stok (
        jenis_transaksi,
        masuk,
        keluar,
        stock,
        idtransaksi,
        idbarang
    )
    VALUES (
        'R',
        0,
        NEW.jumlah,
        v_stok_baru,
        NEW.iddetail_retur,
        v_idbarang
    );

END$$


-- ==================================================
-- E. TRIGGER BARANG DAN SATUAN
-- ==================================================


-- 9. VALIDASI SATUAN SAAT TAMBAH BARANG

DROP TRIGGER IF EXISTS trg_bi_barang_satuan$$

CREATE TRIGGER trg_bi_barang_satuan
BEFORE INSERT ON barang
FOR EACH ROW
BEGIN

    IF NEW.status = 1 AND NOT EXISTS (

        SELECT 1
        FROM satuan
        WHERE idsatuan = NEW.idsatuan
        AND status = 1

    ) THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Barang aktif harus menggunakan satuan aktif';

    END IF;

END$$


-- 10. VALIDASI SATUAN SAAT UPDATE BARANG

DROP TRIGGER IF EXISTS trg_bu_barang_satuan$$

CREATE TRIGGER trg_bu_barang_satuan
BEFORE UPDATE ON barang
FOR EACH ROW
BEGIN

    IF NEW.status = 1 THEN

        IF NOT EXISTS (

            SELECT 1
            FROM satuan
            WHERE idsatuan = NEW.idsatuan
            AND status = 1

        ) THEN

            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT =
                'Barang tidak dapat diaktifkan karena satuan nonaktif';

        END IF;

        SET NEW.nonaktif_karena_satuan = 0;

    ELSEIF NEW.idsatuan <> OLD.idsatuan THEN

        SET NEW.nonaktif_karena_satuan = 0;

    END IF;

END$$


-- 11. SINKRONISASI STATUS SATUAN DAN BARANG

DROP TRIGGER IF EXISTS trg_au_satuan$$

CREATE TRIGGER trg_au_satuan
AFTER UPDATE ON satuan
FOR EACH ROW
BEGIN

    -- Satuan dinonaktifkan

    IF OLD.status = 1 AND NEW.status = 0 THEN

        UPDATE barang
        SET
            status = 0,
            nonaktif_karena_satuan = 1
        WHERE idsatuan = NEW.idsatuan
        AND status = 1;

    -- Satuan diaktifkan kembali

    ELSEIF OLD.status = 0 AND NEW.status = 1 THEN

        UPDATE barang
        SET
            status = 1,
            nonaktif_karena_satuan = 0
        WHERE idsatuan = NEW.idsatuan
        AND status = 0
        AND nonaktif_karena_satuan = 1;

    END IF;

END$$


-- ==================================================
-- F. TRIGGER USER
-- ==================================================


-- 12. PERLINDUNGAN UPDATE AKUN ADMIN

DROP TRIGGER IF EXISTS trg_bu_user$$

CREATE TRIGGER trg_bu_user
BEFORE UPDATE ON `user`
FOR EACH ROW
BEGIN

    IF EXISTS (

        SELECT 1 FROM role
        WHERE idrole = OLD.idrole
        AND nama_role = 'Admin'

    ) THEN

        IF NEW.status <> 1 THEN

            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT =
                'Akun Admin tidak boleh dinonaktifkan';

        END IF;

        IF NEW.idrole <> OLD.idrole THEN

            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT =
                'Role akun Admin tidak boleh diubah';

        END IF;

    END IF;

    IF NEW.status = 0 AND EXISTS (

        SELECT 1 FROM role
        WHERE idrole = NEW.idrole
        AND nama_role = 'Admin'

    ) THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Akun dengan role Admin harus aktif';

    END IF;

END$$


-- 13. PERLINDUNGAN DELETE AKUN ADMIN

DROP TRIGGER IF EXISTS trg_bd_user$$

CREATE TRIGGER trg_bd_user
BEFORE DELETE ON `user`
FOR EACH ROW
BEGIN

    IF EXISTS (

        SELECT 1
        FROM role
        WHERE idrole = OLD.idrole
        AND nama_role = 'Admin'

    ) THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Akun Admin tidak boleh dihapus';

    END IF;

END$$


DELIMITER ;

-- ==================================================
-- SELESAI
-- TOTAL 13 TRIGGER
-- ==================================================
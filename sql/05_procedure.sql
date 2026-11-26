-- ==================================================
-- STOKFLOW - STORED PROCEDURE
-- TOTAL: 13 STORED PROCEDURE
-- ==================================================

USE stokflow_db;

DELIMITER $$


-- ==================================================
-- A. STORED PROCEDURE DATA MASTER
-- ==================================================


-- 1. TAMBAH BARANG

DROP PROCEDURE IF EXISTS sp_tambah_barang$$

CREATE PROCEDURE sp_tambah_barang(
    IN p_jenis CHAR(1),
    IN p_nama VARCHAR(100),
    IN p_idsatuan INT,
    IN p_harga INT
)
BEGIN

    IF p_harga IS NULL OR p_harga <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Harga harus lebih dari 0';
    END IF;

    IF EXISTS (
        SELECT 1 FROM barang
        WHERE nama = p_nama
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Barang sudah tersedia';
    END IF;

    INSERT INTO barang (
        jenis,
        nama,
        idsatuan,
        status,
        harga
    )
    VALUES (
        p_jenis,
        p_nama,
        p_idsatuan,
        1,
        p_harga
    );

END$$


-- 2. UPDATE BARANG

DROP PROCEDURE IF EXISTS sp_update_barang$$

CREATE PROCEDURE sp_update_barang(
    IN p_idbarang INT,
    IN p_jenis CHAR(1),
    IN p_nama VARCHAR(100),
    IN p_idsatuan INT,
    IN p_harga INT,
    IN p_status TINYINT
)
BEGIN

    IF NOT EXISTS (
        SELECT 1 FROM barang
        WHERE idbarang = p_idbarang
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Barang tidak ditemukan';
    END IF;

    IF p_harga IS NULL OR p_harga <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Harga tidak valid';
    END IF;

    IF p_status IS NULL
       OR p_status NOT IN (0, 1) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Status barang tidak valid';
    END IF;

    IF EXISTS (
        SELECT 1 FROM barang
        WHERE nama = p_nama
        AND idbarang <> p_idbarang
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Nama barang sudah digunakan';
    END IF;

    IF p_status = 1 AND NOT EXISTS (
        SELECT 1 FROM satuan
        WHERE idsatuan = p_idsatuan
        AND status = 1
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Satuan barang tidak aktif';
    END IF;

    UPDATE barang
    SET
        jenis = p_jenis,
        nama = p_nama,
        idsatuan = p_idsatuan,
        harga = p_harga,
        status = p_status,
        nonaktif_karena_satuan = 0
    WHERE idbarang = p_idbarang;

END$$


-- 3. TAMBAH MARGIN PENJUALAN

DROP PROCEDURE IF EXISTS sp_tambah_margin$$

CREATE PROCEDURE sp_tambah_margin(
    IN p_persen DOUBLE,
    IN p_iduser INT,
    IN p_status TINYINT
)
BEGIN

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    IF p_persen IS NULL
       OR p_persen < 0
       OR p_persen > 1000 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Persentase margin tidak valid';
    END IF;

    IF p_status IS NULL
       OR p_status NOT IN (0, 1) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Status margin tidak valid';
    END IF;

    START TRANSACTION;

    IF p_status = 1 THEN

        UPDATE margin_penjualan
        SET status = 0
        WHERE status = 1
        AND idmargin_penjualan > 0;

    END IF;

    INSERT INTO margin_penjualan (
        persen,
        status,
        iduser
    )
    VALUES (
        p_persen,
        p_status,
        p_iduser
    );

    COMMIT;

END$$


-- 4. AKTIFKAN MARGIN PENJUALAN

DROP PROCEDURE IF EXISTS sp_aktifkan_margin$$

CREATE PROCEDURE sp_aktifkan_margin(
    IN p_idmargin INT
)
BEGIN

    DECLARE v_jumlah INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    SELECT COUNT(*)
    INTO v_jumlah
    FROM margin_penjualan
    WHERE idmargin_penjualan = p_idmargin;

    IF v_jumlah = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Margin tidak ditemukan';
    END IF;

    UPDATE margin_penjualan
    SET status = 0
    WHERE status = 1
    AND idmargin_penjualan > 0;

    UPDATE margin_penjualan
    SET status = 1
    WHERE idmargin_penjualan = p_idmargin;

    COMMIT;

END$$


-- ==================================================
-- B. STORED PROCEDURE HEADER TRANSAKSI
-- ==================================================


-- 5. BUAT PENGADAAN

DROP PROCEDURE IF EXISTS sp_buat_pengadaan$$

CREATE PROCEDURE sp_buat_pengadaan(
    IN p_iduser INT,
    IN p_idvendor INT,
    OUT p_idpengadaan BIGINT
)
BEGIN

    IF NOT EXISTS (
        SELECT 1 FROM vendor
        WHERE idvendor = p_idvendor
        AND status = 'A'
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Vendor tidak aktif atau tidak ditemukan';
    END IF;

    INSERT INTO pengadaan (
        user_iduser,
        vendor_idvendor,
        status,
        subtotal_nilai,
        ppn,
        total_nilai
    )
    VALUES (
        p_iduser,
        p_idvendor,
        'P',
        0,
        0,
        0
    );

    SET p_idpengadaan = LAST_INSERT_ID();

END$$


-- 6. BUAT PENERIMAAN

DROP PROCEDURE IF EXISTS sp_buat_penerimaan$$

CREATE PROCEDURE sp_buat_penerimaan(
    IN p_idpengadaan BIGINT,
    IN p_iduser INT,
    OUT p_idpenerimaan BIGINT
)
BEGIN

    IF NOT EXISTS (
        SELECT 1 FROM pengadaan
        WHERE idpengadaan = p_idpengadaan
        AND status = 'P'
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Pengadaan tidak ditemukan atau sudah selesai';
    END IF;

    INSERT INTO penerimaan (
        status,
        idpengadaan,
        iduser
    )
    VALUES (
        'P',
        p_idpengadaan,
        p_iduser
    );

    SET p_idpenerimaan = LAST_INSERT_ID();

END$$


-- 7. BUAT PENJUALAN

DROP PROCEDURE IF EXISTS sp_buat_penjualan$$

CREATE PROCEDURE sp_buat_penjualan(
    IN p_iduser INT,
    OUT p_idpenjualan INT
)
BEGIN

    DECLARE v_idmargin INT DEFAULT NULL;

    SELECT idmargin_penjualan
    INTO v_idmargin
    FROM margin_penjualan
    WHERE status = 1
    ORDER BY idmargin_penjualan DESC
    LIMIT 1;

    IF v_idmargin IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Margin penjualan aktif tidak tersedia';
    END IF;

    INSERT INTO penjualan (
        subtotal_nilai,
        ppn,
        total_nilai,
        iduser,
        idmargin_penjualan
    )
    VALUES (
        0,
        0,
        0,
        p_iduser,
        v_idmargin
    );

    SET p_idpenjualan = LAST_INSERT_ID();

END$$


-- 8. BUAT RETUR

DROP PROCEDURE IF EXISTS sp_buat_retur$$

CREATE PROCEDURE sp_buat_retur(
    IN p_idpenerimaan BIGINT,
    IN p_iduser INT,
    OUT p_idretur BIGINT
)
BEGIN

    IF NOT EXISTS (
        SELECT 1 FROM penerimaan
        WHERE idpenerimaan = p_idpenerimaan
        AND status = 'S'
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Penerimaan belum selesai atau tidak ditemukan';
    END IF;

    INSERT INTO retur (
        idpenerimaan,
        iduser
    )
    VALUES (
        p_idpenerimaan,
        p_iduser
    );

    SET p_idretur = LAST_INSERT_ID();

END$$


-- ==================================================
-- C. STORED PROCEDURE DETAIL TRANSAKSI
-- ==================================================
-- Procedure detail berikut masih bersifat tambahan.
-- Validasi dan penguncian transaksi oleh PHP serta
-- Trigger tetap diperlukan.
-- ==================================================


-- 9. DETAIL PENGADAAN

DROP PROCEDURE IF EXISTS sp_detail_pengadaan$$

CREATE PROCEDURE sp_detail_pengadaan(
    IN p_idpengadaan BIGINT,
    IN p_idbarang INT,
    IN p_harga_satuan INT,
    IN p_jumlah INT
)
BEGIN

    DECLARE v_subtotal INT;

    IF p_jumlah IS NULL OR p_jumlah <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Jumlah barang harus lebih dari 0';
    END IF;

    IF p_harga_satuan IS NULL
       OR p_harga_satuan < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Harga satuan tidak valid';
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM pengadaan
        WHERE idpengadaan = p_idpengadaan
        AND status = 'P'
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Pengadaan tidak dalam proses';
    END IF;

    IF EXISTS (
        SELECT 1 FROM penerimaan
        WHERE idpengadaan = p_idpengadaan
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Pengadaan sudah memiliki penerimaan';
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM barang b
        JOIN satuan s
            ON b.idsatuan = s.idsatuan
        WHERE b.idbarang = p_idbarang
        AND b.status = 1
        AND s.status = 1
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Barang atau satuan tidak aktif';
    END IF;

    IF EXISTS (
        SELECT 1 FROM detail_pengadaan
        WHERE idpengadaan = p_idpengadaan
        AND idbarang = p_idbarang
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Barang sudah ada dalam pengadaan';
    END IF;

    SET v_subtotal = fn_subtotal(
        p_harga_satuan,
        p_jumlah
    );

    INSERT INTO detail_pengadaan (
        harga_satuan,
        jumlah,
        sub_total,
        idbarang,
        idpengadaan
    )
    VALUES (
        p_harga_satuan,
        p_jumlah,
        v_subtotal,
        p_idbarang,
        p_idpengadaan
    );

END$$


-- 10. DETAIL PENERIMAAN

DROP PROCEDURE IF EXISTS sp_detail_penerimaan$$

CREATE PROCEDURE sp_detail_penerimaan(
    IN p_idpenerimaan BIGINT,
    IN p_idbarang INT,
    IN p_jumlah_terima INT
)
BEGIN

    DECLARE v_idpengadaan BIGINT DEFAULT NULL;
    DECLARE v_harga INT DEFAULT NULL;
    DECLARE v_jumlah_pesan INT DEFAULT NULL;
    DECLARE v_sudah_terima BIGINT DEFAULT 0;
    DECLARE v_subtotal INT;

    IF p_jumlah_terima IS NULL
       OR p_jumlah_terima <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Jumlah penerimaan harus lebih dari 0';
    END IF;

    SELECT p.idpengadaan
    INTO v_idpengadaan
    FROM penerimaan p
    JOIN pengadaan pg
        ON p.idpengadaan = pg.idpengadaan
    WHERE p.idpenerimaan = p_idpenerimaan
    AND p.status = 'P'
    AND pg.status = 'P';

    IF v_idpengadaan IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Penerimaan tidak dalam proses';
    END IF;

    SELECT harga_satuan, jumlah
    INTO v_harga, v_jumlah_pesan
    FROM detail_pengadaan
    WHERE idpengadaan = v_idpengadaan
    AND idbarang = p_idbarang;

    IF v_harga IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Barang tidak terdapat dalam pengadaan';
    END IF;

    SELECT COALESCE(SUM(dp.jumlah_terima), 0)
    INTO v_sudah_terima
    FROM detail_penerimaan dp
    JOIN penerimaan p
        ON dp.idpenerimaan = p.idpenerimaan
    WHERE p.idpengadaan = v_idpengadaan
    AND dp.barang_idbarang = p_idbarang;

    IF v_sudah_terima + p_jumlah_terima
       > v_jumlah_pesan THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Jumlah penerimaan melebihi pesanan';
    END IF;

    IF EXISTS (
        SELECT 1 FROM detail_penerimaan
        WHERE idpenerimaan = p_idpenerimaan
        AND barang_idbarang = p_idbarang
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Barang sudah ada dalam penerimaan ini';
    END IF;

    SET v_subtotal = fn_subtotal(
        v_harga,
        p_jumlah_terima
    );

    INSERT INTO detail_penerimaan (
        idpenerimaan,
        barang_idbarang,
        jumlah_terima,
        harga_satuan_terima,
        sub_total_terima
    )
    VALUES (
        p_idpenerimaan,
        p_idbarang,
        p_jumlah_terima,
        v_harga,
        v_subtotal
    );

END$$


-- 11. DETAIL PENJUALAN

DROP PROCEDURE IF EXISTS sp_detail_penjualan$$

CREATE PROCEDURE sp_detail_penjualan(
    IN p_idpenjualan INT,
    IN p_idbarang INT,
    IN p_jumlah INT
)
BEGIN

    DECLARE v_harga INT DEFAULT NULL;
    DECLARE v_margin DOUBLE DEFAULT NULL;
    DECLARE v_stok INT DEFAULT 0;
    DECLARE v_harga_jual INT;
    DECLARE v_subtotal INT;

    IF p_jumlah IS NULL OR p_jumlah <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Jumlah penjualan tidak valid';
    END IF;

    SELECT b.harga
    INTO v_harga
    FROM barang b
    JOIN satuan s
        ON b.idsatuan = s.idsatuan
    WHERE b.idbarang = p_idbarang
    AND b.status = 1
    AND s.status = 1;

    IF v_harga IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Barang atau satuan tidak aktif';
    END IF;

    SELECT m.persen
    INTO v_margin
    FROM penjualan p
    JOIN margin_penjualan m
        ON p.idmargin_penjualan =
           m.idmargin_penjualan
    WHERE p.idpenjualan = p_idpenjualan;

    IF v_margin IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Penjualan atau margin tidak ditemukan';
    END IF;

    SET v_stok = fn_stok_barang(p_idbarang);

    IF p_jumlah > v_stok THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Stok barang tidak mencukupi';
    END IF;

    IF EXISTS (
        SELECT 1 FROM detail_penjualan
        WHERE penjualan_idpenjualan = p_idpenjualan
        AND idbarang = p_idbarang
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Barang sudah ada dalam penjualan';
    END IF;

    SET v_harga_jual = fn_harga_jual(
        v_harga,
        v_margin
    );

    SET v_subtotal = fn_subtotal(
        v_harga_jual,
        p_jumlah
    );

    INSERT INTO detail_penjualan (
        harga_satuan,
        jumlah,
        subtotal,
        penjualan_idpenjualan,
        idbarang
    )
    VALUES (
        v_harga_jual,
        p_jumlah,
        v_subtotal,
        p_idpenjualan,
        p_idbarang
    );

END$$


-- 12. DETAIL RETUR

DROP PROCEDURE IF EXISTS sp_detail_retur$$

CREATE PROCEDURE sp_detail_retur(
    IN p_idretur BIGINT,
    IN p_iddetail_penerimaan BIGINT,
    IN p_jumlah INT,
    IN p_alasan VARCHAR(200)
)
BEGIN

    DECLARE v_idbarang INT DEFAULT NULL;
    DECLARE v_jumlah_terima INT;
    DECLARE v_sudah_retur BIGINT DEFAULT 0;
    DECLARE v_stok INT;

    IF p_jumlah IS NULL OR p_jumlah <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Jumlah retur tidak valid';
    END IF;

    IF p_alasan IS NULL
       OR TRIM(p_alasan) = '' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Alasan retur wajib diisi';
    END IF;

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
          p_iddetail_penerimaan
    AND r.idretur = p_idretur
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
          p_iddetail_penerimaan;

    IF v_sudah_retur + p_jumlah
       > v_jumlah_terima THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Jumlah retur melebihi penerimaan';
    END IF;

    SET v_stok = fn_stok_barang(v_idbarang);

    IF p_jumlah > v_stok THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Stok tidak cukup untuk retur';
    END IF;

    INSERT INTO detail_retur (
        jumlah,
        alasan,
        idretur,
        iddetail_penerimaan
    )
    VALUES (
        p_jumlah,
        TRIM(p_alasan),
        p_idretur,
        p_iddetail_penerimaan
    );

END$$


-- ==================================================
-- D. PENYELESAIAN TRANSAKSI
-- ==================================================


-- 13. SELESAIKAN PENERIMAAN

DROP PROCEDURE IF EXISTS sp_selesaikan_penerimaan$$

CREATE PROCEDURE sp_selesaikan_penerimaan(
    IN p_idpenerimaan BIGINT
)
BEGIN

    DECLARE v_idpengadaan BIGINT DEFAULT NULL;
    DECLARE v_jumlah_detail INT DEFAULT 0;
    DECLARE v_belum_lengkap INT DEFAULT 0;

    SELECT idpengadaan
    INTO v_idpengadaan
    FROM penerimaan
    WHERE idpenerimaan = p_idpenerimaan
    AND status = 'P'
    FOR UPDATE;

    IF v_idpengadaan IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Penerimaan tidak ditemukan atau sudah selesai';
    END IF;

    SELECT COUNT(*)
    INTO v_jumlah_detail
    FROM detail_penerimaan
    WHERE idpenerimaan = p_idpenerimaan;

    IF v_jumlah_detail = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Detail penerimaan masih kosong';
    END IF;

    UPDATE penerimaan
    SET status = 'S'
    WHERE idpenerimaan = p_idpenerimaan;

    SELECT COUNT(*)
    INTO v_belum_lengkap
    FROM detail_pengadaan dg
    WHERE dg.idpengadaan = v_idpengadaan
    AND dg.jumlah > (

        SELECT COALESCE(SUM(dp.jumlah_terima), 0)

        FROM detail_penerimaan dp

        JOIN penerimaan p
            ON dp.idpenerimaan = p.idpenerimaan

        WHERE p.idpengadaan = v_idpengadaan
        AND p.status = 'S'
        AND dp.barang_idbarang = dg.idbarang

    );

    IF v_belum_lengkap = 0 THEN

        UPDATE pengadaan
        SET status = 'S'
        WHERE idpengadaan = v_idpengadaan;

    ELSE

        UPDATE pengadaan
        SET status = 'P'
        WHERE idpengadaan = v_idpengadaan;

    END IF;

END$$


DELIMITER ;

-- ==================================================
-- SELESAI
-- TOTAL 13 STORED PROCEDURE
-- ==================================================
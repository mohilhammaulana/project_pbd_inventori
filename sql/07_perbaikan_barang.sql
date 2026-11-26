-- ==================================================
-- STOKFLOW
-- PERLINDUNGAN PERUBAHAN SATUAN BARANG
-- ==================================================

USE stokflow_db;

DELIMITER $$


-- ==================================================
-- 1. TRIGGER UPDATE BARANG
-- ==================================================

DROP TRIGGER IF EXISTS trg_bu_barang_satuan$$

CREATE TRIGGER trg_bu_barang_satuan
BEFORE UPDATE ON barang
FOR EACH ROW
BEGIN

    -- Melindungi satuan barang yang memiliki transaksi

    IF NEW.idsatuan <> OLD.idsatuan THEN

        IF EXISTS (
            SELECT 1
            FROM detail_pengadaan
            WHERE idbarang = OLD.idbarang
        )

        OR EXISTS (
            SELECT 1
            FROM detail_penerimaan
            WHERE barang_idbarang = OLD.idbarang
        )

        OR EXISTS (
            SELECT 1
            FROM detail_penjualan
            WHERE idbarang = OLD.idbarang
        )

        OR EXISTS (
            SELECT 1
            FROM kartu_stok
            WHERE idbarang = OLD.idbarang
        ) THEN

            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT =
                'Satuan tidak dapat diubah karena barang sudah memiliki riwayat transaksi';

        END IF;

    END IF;


    -- Barang aktif wajib menggunakan satuan aktif

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


-- ==================================================
-- 2. STORED PROCEDURE UPDATE BARANG
-- ==================================================

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

    DECLARE v_idsatuan_lama INT DEFAULT NULL;


    -- Memeriksa keberadaan barang

    SELECT idsatuan
    INTO v_idsatuan_lama
    FROM barang
    WHERE idbarang = p_idbarang;

    IF v_idsatuan_lama IS NULL THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Barang tidak ditemukan';

    END IF;


    -- Validasi jenis barang

    IF p_jenis IS NULL
       OR p_jenis NOT IN ('M', 'N') THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Jenis barang tidak valid';

    END IF;


    -- Validasi nama barang

    IF p_nama IS NULL
       OR TRIM(p_nama) = ''
       OR CHAR_LENGTH(TRIM(p_nama)) > 45 THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Nama barang wajib diisi, maksimal 45 karakter';

    END IF;


    -- Validasi harga

    IF p_harga IS NULL OR p_harga <= 0 THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Harga harus lebih dari 0';

    END IF;


    -- Validasi status

    IF p_status IS NULL
       OR p_status NOT IN (0, 1) THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Status barang tidak valid';

    END IF;


    -- Memeriksa duplikasi nama barang

    IF EXISTS (
        SELECT 1
        FROM barang
        WHERE nama = TRIM(p_nama)
        AND idbarang <> p_idbarang
    ) THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Nama barang sudah digunakan';

    END IF;


    -- Memeriksa keberadaan satuan

    IF NOT EXISTS (
        SELECT 1
        FROM satuan
        WHERE idsatuan = p_idsatuan
    ) THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Satuan barang tidak ditemukan';

    END IF;


    -- Barang aktif atau satuan baru
    -- wajib menggunakan satuan aktif

    IF (
        p_status = 1
        OR p_idsatuan <> v_idsatuan_lama
    ) AND NOT EXISTS (
        SELECT 1
        FROM satuan
        WHERE idsatuan = p_idsatuan
        AND status = 1
    ) THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Satuan barang tidak aktif';

    END IF;


    -- Memperbarui data barang
    -- Pertahankan penanda nonaktif otomatis
    -- jika status dan satuannya tidak berubah

    UPDATE barang
    SET

        nonaktif_karena_satuan = CASE

            WHEN p_status = 0
                 AND status = 0
                 AND idsatuan = p_idsatuan
                 AND nonaktif_karena_satuan = 1

            THEN 1

            ELSE 0

        END,

        jenis = p_jenis,
        nama = TRIM(p_nama),
        idsatuan = p_idsatuan,
        harga = p_harga,
        status = p_status

    WHERE idbarang = p_idbarang;

END$$


DELIMITER ;

-- ==================================================
-- SELESAI
-- ==================================================
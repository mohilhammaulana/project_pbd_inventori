-- ==================================================
-- STOKFLOW
-- DATA DEFINITION LANGUAGE (DDL)
-- Struktur 15 tabel
-- ==================================================

CREATE DATABASE IF NOT EXISTS stokflow_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE stokflow_db;


-- ==================================================
-- 1. TABEL ROLE
-- ==================================================

CREATE TABLE role (
    idrole INT AUTO_INCREMENT PRIMARY KEY,
    nama_role VARCHAR(100) NOT NULL UNIQUE
) ENGINE = InnoDB;


-- ==================================================
-- 2. TABEL USER
-- ==================================================

CREATE TABLE `user` (
    iduser INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(45) NOT NULL UNIQUE,
    password VARCHAR(100) NOT NULL,
    idrole INT NOT NULL,
    status TINYINT NOT NULL DEFAULT 1,

    CONSTRAINT fk_user_role
        FOREIGN KEY (idrole)
        REFERENCES role(idrole),

    CONSTRAINT chk_user_status
        CHECK (status IN (0, 1))

) ENGINE = InnoDB;


-- ==================================================
-- 3. TABEL SATUAN
-- ==================================================

CREATE TABLE satuan (
    idsatuan INT AUTO_INCREMENT PRIMARY KEY,
    nama_satuan VARCHAR(45) NOT NULL UNIQUE,
    status TINYINT NOT NULL DEFAULT 1,

    CONSTRAINT chk_satuan_status
        CHECK (status IN (0, 1))

) ENGINE = InnoDB;


-- ==================================================
-- 4. TABEL VENDOR
-- ==================================================

CREATE TABLE vendor (
    idvendor INT AUTO_INCREMENT PRIMARY KEY,
    nama_vendor VARCHAR(100) NOT NULL,
    badan_hukum CHAR(1) NOT NULL DEFAULT 'N',
    status CHAR(1) NOT NULL DEFAULT 'A',

    CONSTRAINT chk_vendor_badan
        CHECK (badan_hukum IN ('Y', 'N')),

    CONSTRAINT chk_vendor_status
        CHECK (status IN ('A', 'N'))

) ENGINE = InnoDB;


-- ==================================================
-- 5. TABEL BARANG
-- ==================================================

CREATE TABLE barang (
    idbarang INT AUTO_INCREMENT PRIMARY KEY,
    jenis CHAR(1) NOT NULL,
    nama VARCHAR(45) NOT NULL,
    idsatuan INT NOT NULL,
    status TINYINT NOT NULL DEFAULT 1,
    nonaktif_karena_satuan TINYINT NOT NULL DEFAULT 0,
    harga INT NOT NULL DEFAULT 0,

    CONSTRAINT fk_barang_satuan
        FOREIGN KEY (idsatuan)
        REFERENCES satuan(idsatuan),

    CONSTRAINT chk_barang_jenis
        CHECK (jenis IN ('M', 'N')),

    CONSTRAINT chk_barang_status
        CHECK (status IN (0, 1)),

    CONSTRAINT chk_barang_harga
        CHECK (harga >= 0),

    CONSTRAINT chk_barang_nonaktif_satuan
        CHECK (
            nonaktif_karena_satuan IN (0, 1)
            AND (
                nonaktif_karena_satuan = 0
                OR status = 0
            )
        )

) ENGINE = InnoDB;


-- ==================================================
-- 6. TABEL MARGIN PENJUALAN
-- ==================================================

CREATE TABLE margin_penjualan (
    idmargin_penjualan INT AUTO_INCREMENT PRIMARY KEY,

    created_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    persen DOUBLE NOT NULL,

    status TINYINT NOT NULL DEFAULT 0,

    iduser INT NOT NULL,

    updated_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_margin_user
        FOREIGN KEY (iduser)
        REFERENCES `user`(iduser),

    CONSTRAINT chk_margin_persen
        CHECK (persen >= 0 AND persen <= 1000),

    CONSTRAINT chk_margin_status
        CHECK (status IN (0, 1))

) ENGINE = InnoDB;


-- ==================================================
-- 7. TABEL PENGADAAN
-- ==================================================

CREATE TABLE pengadaan (
    idpengadaan BIGINT AUTO_INCREMENT PRIMARY KEY,

    `timestamp` TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    user_iduser INT NOT NULL,

    status CHAR(1) NOT NULL DEFAULT 'P',

    vendor_idvendor INT NOT NULL,

    subtotal_nilai INT NOT NULL DEFAULT 0,
    ppn INT NOT NULL DEFAULT 0,
    total_nilai INT NOT NULL DEFAULT 0,

    CONSTRAINT fk_pengadaan_user
        FOREIGN KEY (user_iduser)
        REFERENCES `user`(iduser),

    CONSTRAINT fk_pengadaan_vendor
        FOREIGN KEY (vendor_idvendor)
        REFERENCES vendor(idvendor),

    CONSTRAINT chk_pengadaan_status
        CHECK (status IN ('P', 'S'))

) ENGINE = InnoDB;


-- ==================================================
-- 8. TABEL DETAIL PENGADAAN
-- ==================================================

CREATE TABLE detail_pengadaan (
    iddetail_pengadaan BIGINT AUTO_INCREMENT PRIMARY KEY,

    harga_satuan INT NOT NULL,
    jumlah INT NOT NULL,
    sub_total INT NOT NULL DEFAULT 0,

    idbarang INT NOT NULL,
    idpengadaan BIGINT NOT NULL,

    CONSTRAINT fk_dpengadaan_barang
        FOREIGN KEY (idbarang)
        REFERENCES barang(idbarang),

    CONSTRAINT fk_dpengadaan_header
        FOREIGN KEY (idpengadaan)
        REFERENCES pengadaan(idpengadaan),

    CONSTRAINT uq_dpengadaan_barang
        UNIQUE (idpengadaan, idbarang),

    CONSTRAINT chk_dpengadaan_jumlah
        CHECK (jumlah > 0),

    CONSTRAINT chk_dpengadaan_harga
        CHECK (harga_satuan >= 0)

) ENGINE = InnoDB;


-- ==================================================
-- 9. TABEL PENERIMAAN
-- ==================================================

CREATE TABLE penerimaan (
    idpenerimaan BIGINT AUTO_INCREMENT PRIMARY KEY,

    created_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    status CHAR(1) NOT NULL DEFAULT 'S',

    idpengadaan BIGINT NOT NULL,
    iduser INT NOT NULL,

    CONSTRAINT fk_penerimaan_pengadaan
        FOREIGN KEY (idpengadaan)
        REFERENCES pengadaan(idpengadaan),

    CONSTRAINT fk_penerimaan_user
        FOREIGN KEY (iduser)
        REFERENCES `user`(iduser),

    CONSTRAINT chk_penerimaan_status
        CHECK (status IN ('P', 'S'))

) ENGINE = InnoDB;


-- ==================================================
-- 10. TABEL DETAIL PENERIMAAN
-- ==================================================

CREATE TABLE detail_penerimaan (
    iddetail_penerimaan BIGINT AUTO_INCREMENT PRIMARY KEY,

    idpenerimaan BIGINT NOT NULL,
    barang_idbarang INT NOT NULL,

    jumlah_terima INT NOT NULL,
    harga_satuan_terima INT NOT NULL DEFAULT 0,
    sub_total_terima INT NOT NULL DEFAULT 0,

    CONSTRAINT fk_dpenerimaan_header
        FOREIGN KEY (idpenerimaan)
        REFERENCES penerimaan(idpenerimaan),

    CONSTRAINT fk_dpenerimaan_barang
        FOREIGN KEY (barang_idbarang)
        REFERENCES barang(idbarang),

    CONSTRAINT uq_dpenerimaan_barang
        UNIQUE (idpenerimaan, barang_idbarang),

    CONSTRAINT chk_dpenerimaan_jumlah
        CHECK (jumlah_terima > 0)

) ENGINE = InnoDB;


-- ==================================================
-- 11. TABEL RETUR
-- ==================================================

CREATE TABLE retur (
    idretur BIGINT AUTO_INCREMENT PRIMARY KEY,

    created_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    idpenerimaan BIGINT NOT NULL,
    iduser INT NOT NULL,

    CONSTRAINT fk_retur_penerimaan
        FOREIGN KEY (idpenerimaan)
        REFERENCES penerimaan(idpenerimaan),

    CONSTRAINT fk_retur_user
        FOREIGN KEY (iduser)
        REFERENCES `user`(iduser)

) ENGINE = InnoDB;


-- ==================================================
-- 12. TABEL DETAIL RETUR
-- ==================================================

CREATE TABLE detail_retur (
    iddetail_retur BIGINT AUTO_INCREMENT PRIMARY KEY,

    jumlah INT NOT NULL,
    alasan VARCHAR(200) NOT NULL,

    idretur BIGINT NOT NULL,
    iddetail_penerimaan BIGINT NOT NULL,

    CONSTRAINT fk_dretur_header
        FOREIGN KEY (idretur)
        REFERENCES retur(idretur),

    CONSTRAINT fk_dretur_penerimaan
        FOREIGN KEY (iddetail_penerimaan)
        REFERENCES detail_penerimaan(iddetail_penerimaan),

    CONSTRAINT chk_dretur_jumlah
        CHECK (jumlah > 0)

) ENGINE = InnoDB;


-- ==================================================
-- 13. TABEL PENJUALAN
-- ==================================================

CREATE TABLE penjualan (
    idpenjualan INT AUTO_INCREMENT PRIMARY KEY,

    created_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    subtotal_nilai INT NOT NULL DEFAULT 0,
    ppn INT NOT NULL DEFAULT 0,
    total_nilai INT NOT NULL DEFAULT 0,

    iduser INT NOT NULL,
    idmargin_penjualan INT NOT NULL,

    CONSTRAINT fk_penjualan_user
        FOREIGN KEY (iduser)
        REFERENCES `user`(iduser),

    CONSTRAINT fk_penjualan_margin
        FOREIGN KEY (idmargin_penjualan)
        REFERENCES margin_penjualan(idmargin_penjualan)

) ENGINE = InnoDB;


-- ==================================================
-- 14. TABEL DETAIL PENJUALAN
-- ==================================================

CREATE TABLE detail_penjualan (
    iddetail_penjualan BIGINT AUTO_INCREMENT PRIMARY KEY,

    harga_satuan INT NOT NULL DEFAULT 0,
    jumlah INT NOT NULL,
    subtotal INT NOT NULL DEFAULT 0,

    penjualan_idpenjualan INT NOT NULL,
    idbarang INT NOT NULL,

    CONSTRAINT fk_dpenjualan_header
        FOREIGN KEY (penjualan_idpenjualan)
        REFERENCES penjualan(idpenjualan),

    CONSTRAINT fk_dpenjualan_barang
        FOREIGN KEY (idbarang)
        REFERENCES barang(idbarang),

    CONSTRAINT uq_dpenjualan_barang
        UNIQUE (penjualan_idpenjualan, idbarang),

    CONSTRAINT chk_dpenjualan_jumlah
        CHECK (jumlah > 0)

) ENGINE = InnoDB;


-- ==================================================
-- 15. TABEL KARTU STOK
-- ==================================================

CREATE TABLE kartu_stok (
    idkartu_stok BIGINT AUTO_INCREMENT PRIMARY KEY,

    jenis_transaksi CHAR(1) NOT NULL,

    masuk INT NOT NULL DEFAULT 0,
    keluar INT NOT NULL DEFAULT 0,
    stock INT NOT NULL,

    created_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    idtransaksi BIGINT NOT NULL,
    idbarang INT NOT NULL,

    CONSTRAINT fk_kartu_barang
        FOREIGN KEY (idbarang)
        REFERENCES barang(idbarang),

    CONSTRAINT chk_kartu_jenis
        CHECK (jenis_transaksi IN ('M', 'J', 'R')),

    CONSTRAINT chk_kartu_nilai
        CHECK (
            masuk >= 0
            AND keluar >= 0
            AND stock >= 0
        ),

    INDEX idx_kartu_barang (
        idbarang,
        idkartu_stok
    )

) ENGINE = InnoDB;
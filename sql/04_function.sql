USE stokflow_db;

DROP FUNCTION IF EXISTS fn_harga_jual;

DELIMITER $$

CREATE FUNCTION fn_harga_jual(
    p_harga INT,
    p_margin DOUBLE
)
RETURNS INT
DETERMINISTIC
NO SQL
BEGIN
    DECLARE v_harga_jual INT;

    SET v_harga_jual = ROUND(
        p_harga + (p_harga * p_margin / 100)
    );

    RETURN v_harga_jual;
END$$

DELIMITER ;

DROP FUNCTION IF EXISTS fn_subtotal;

DELIMITER $$

CREATE FUNCTION fn_subtotal(
    p_harga INT,
    p_jumlah INT
)
RETURNS INT
DETERMINISTIC
NO SQL
BEGIN
    RETURN p_harga * p_jumlah;
END$$

DELIMITER ;

DROP FUNCTION IF EXISTS fn_ppn;

DELIMITER $$

CREATE FUNCTION fn_ppn(
    p_subtotal INT,
    p_persen DOUBLE
)
RETURNS INT
DETERMINISTIC
NO SQL
BEGIN
    RETURN ROUND(p_subtotal * p_persen / 100);
END$$

DELIMITER ;

DROP FUNCTION IF EXISTS fn_stok_barang;

DELIMITER $$

CREATE FUNCTION fn_stok_barang(
    p_idbarang INT
)
RETURNS INT
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_stok INT DEFAULT 0;

    SET v_stok = COALESCE(
        (
            SELECT stock
            FROM kartu_stok
            WHERE idbarang = p_idbarang
            ORDER BY idkartu_stok DESC
            LIMIT 1
        ),
        0
    );

    RETURN v_stok;
END$$

DELIMITER ;


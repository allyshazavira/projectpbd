USE PROJECT_APOTEK;

CREATE OR REPLACE VIEW viewpengadaan
AS SELECT p.idpengadaan AS ID, p.timestamp AS Tanggal,
    v.nama_vendor AS Vendor, u.username AS Dibuat_oleh,
    p.subtotal_nilai AS Subtotal, p.ppn AS PPN, p.total_nilai AS Total,
    CASE p.status WHEN 'P' THEN 'Diproses' WHEN 'S' THEN 'Selesai' ELSE p.status END AS Status,
    p.vendor_idvendor AS ID_Vendor,
    hitung_sisa_pengadaan(p.idpengadaan) AS Sisa_Barang,
    -- jumlah yang sudah diisi di penerimaan berstatus Diproses (belum final, belum mengurangi sisa)
    (SELECT COALESCE(SUM(dp.jumlah_terima), 0) FROM detail_penerimaan dp
     JOIN penerimaan pn ON dp.idpenerimaan = pn.idpenerimaan
     WHERE pn.idpengadaan = p.idpengadaan AND pn.status = 'P') AS Sedang_Diproses
FROM pengadaan p
JOIN vendor v ON p.vendor_idvendor = v.idvendor
JOIN user u ON p.user_iduser = u.iduser;

-- Jumlah_Diterima & Sisa dihitung dari penerimaan yang sudah SELESAI
CREATE OR REPLACE VIEW viewdetailpengadaan
AS SELECT d.idpengadaan AS ID_Pengadaan, d.iddetail_pengadaan AS ID_Detail,
    b.nama AS Barang, s.nama_satuan AS Satuan,
    d.harga_satuan AS Harga_Satuan, d.jumlah AS Jumlah, d.sub_total AS Subtotal,
    d.jumlah - hitung_sisa_barang(d.idpengadaan, d.idbarang, 0) AS Jumlah_Diterima,
    hitung_sisa_barang(d.idpengadaan, d.idbarang, 0) AS Sisa,
    (SELECT COALESCE(SUM(dp.jumlah_terima), 0) FROM detail_penerimaan dp
     JOIN penerimaan pn ON dp.idpenerimaan = pn.idpenerimaan
     WHERE pn.idpengadaan = d.idpengadaan AND pn.status = 'P'
       AND dp.barang_idbarang = d.idbarang) AS Sedang_Diproses
FROM detail_pengadaan d
JOIN barang b ON d.idbarang = b.idbarang
JOIN satuan s ON b.idsatuan = s.idsatuan;

DELIMITER //
CREATE FUNCTION hitung_subtotal_pengadaan(p_idpengadaan BIGINT)
RETURNS INT
READS SQL DATA
BEGIN
	RETURN COALESCE((SELECT SUM(SUB_TOTAL) FROM detail_pengadaan
    WHERE idpengadaan = p_idpengadaan), 0);
END //

CREATE PROCEDURE sp_hitung_ulang_pengadaan( IN p_idpengadaan BIGINT)
BEGIN
	DECLARE v_sub INT;
    SET v_sub = hitung_subtotal_pengadaan(p_idpengadaan);
    
    UPDATE pengadaan
    SET subtotal_nilai = v_sub,
    ppn = hitung_ppn(v_sub),
    total_nilai = hitung_total_nilai(v_sub)
    WHERE idpengadaan = p_idpengadaan;
END //

CREATE PROCEDURE sp_tambah_pengadaan(
    IN p_iduser INT, IN p_idvendor INT, OUT p_idpengadaan BIGINT)
BEGIN
    IF NOT EXISTS (SELECT 1 FROM user WHERE iduser = p_iduser) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'User tidak ditemukan';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM vendor WHERE idvendor = p_idvendor AND status = '1') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Vendor tidak ditemukan / tidak aktif';
    END IF;

    INSERT INTO pengadaan (user_iduser, status, vendor_idvendor, subtotal_nilai, ppn, total_nilai)
    VALUES (p_iduser, 'P', p_idvendor, 0, 0, 0);
    SET p_idpengadaan = LAST_INSERT_ID();
END //

DELIMITER //
-- DROP PROCEDURE IF EXISTS sp_tambah_detail_pengadaan;
CREATE PROCEDURE sp_tambah_detail_pengadaan(
    IN p_idpengadaan BIGINT, IN p_idbarang INT, IN p_jumlah INT)
BEGIN
    DECLARE v_status CHAR(1);
    DECLARE v_harga INT;

    SELECT status INTO v_status
    FROM pengadaan WHERE idpengadaan = p_idpengadaan;

    IF v_status IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pengadaan tidak ditemukan';
    END IF;
    IF v_status <> 'P' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pengadaan sudah selesai';
    END IF;
    IF EXISTS (SELECT 1 FROM penerimaan WHERE idpengadaan = p_idpengadaan) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Pengadaan sudah diterima, barang tidak bisa diubah';
    END IF;
    IF p_jumlah IS NULL OR p_jumlah <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Jumlah harus lebih dari 0';
    END IF;

    SELECT harga INTO v_harga
    FROM barang WHERE idbarang = p_idbarang AND status = 1;

    IF v_harga IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Barang tidak ditemukan / tidak aktif';
    END IF;

    IF EXISTS (SELECT 1 FROM detail_pengadaan
               WHERE idpengadaan = p_idpengadaan AND idbarang = p_idbarang) THEN
        UPDATE detail_pengadaan SET jumlah = jumlah + p_jumlah
        WHERE idpengadaan = p_idpengadaan AND idbarang = p_idbarang;
    ELSE
        INSERT INTO detail_pengadaan (harga_satuan, jumlah, sub_total, idbarang, idpengadaan)
        VALUES (v_harga, p_jumlah, hitung_subtotal(v_harga, p_jumlah), p_idbarang, p_idpengadaan);
    END IF;
END //

CREATE TRIGGER trg_detail_pengadaan_bi BEFORE INSERT ON detail_pengadaan
FOR EACH ROW
BEGIN
    DECLARE v_harga INT;

    IF NEW.jumlah <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Jumlah pengadaan harus lebih dari 0';
    END IF;

    SELECT harga INTO v_harga FROM barang WHERE idbarang = NEW.idbarang;
    IF v_harga IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Barang tidak ditemukan';
    END IF;

    SET NEW.harga_satuan = v_harga;                              -- harga selalu dari tabel barang
    SET NEW.sub_total = hitung_subtotal(NEW.harga_satuan, NEW.jumlah);
END //

CREATE TRIGGER trg_detail_pengadaan_bu BEFORE UPDATE ON detail_pengadaan
FOR EACH ROW
BEGIN
    SET NEW.sub_total = hitung_subtotal(NEW.harga_satuan, NEW.jumlah);
END //

CREATE TRIGGER trg_detail_pengadaan_ai AFTER INSERT ON detail_pengadaan
FOR EACH ROW
BEGIN
    CALL sp_hitung_ulang_pengadaan(NEW.idpengadaan);
END //

CREATE TRIGGER trg_detail_pengadaan_au AFTER UPDATE ON detail_pengadaan
FOR EACH ROW
BEGIN
    CALL sp_hitung_ulang_pengadaan(NEW.idpengadaan);
END //

CREATE TRIGGER trg_detail_pengadaan_ad AFTER DELETE ON detail_pengadaan
FOR EACH ROW
BEGIN
    CALL sp_hitung_ulang_pengadaan(OLD.idpengadaan);
END //
DELIMITER ;

DELIMITER //
CREATE FUNCTION hitung_sisa_pengadaan(p_idpengadaan BIGINT)
RETURNS INT
READS SQL DATA
BEGIN
    RETURN COALESCE((SELECT SUM(hitung_sisa_barang(d.idpengadaan, d.idbarang, 0))
                     FROM detail_pengadaan d
                     WHERE d.idpengadaan = p_idpengadaan), 0);
END //

CREATE PROCEDURE sp_terima_pengadaan(
    IN p_idpengadaan BIGINT, IN p_iduser INT, OUT p_idpenerimaan BIGINT)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN ROLLBACK; RESIGNAL; END;

    IF NOT EXISTS (SELECT 1 FROM pengadaan WHERE idpengadaan = p_idpengadaan AND status = 'P') THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Pengadaan tidak ditemukan atau sudah selesai';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM detail_pengadaan WHERE idpengadaan = p_idpengadaan) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pengadaan belum memiliki detail barang';
    END IF;
    IF EXISTS (SELECT 1 FROM penerimaan WHERE idpengadaan = p_idpengadaan AND status = 'P') THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Masih ada penerimaan yang belum diselesaikan pada pengadaan ini';
    END IF;
    IF hitung_sisa_pengadaan(p_idpengadaan) <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Semua barang pada pengadaan ini sudah diterima';
    END IF;

    START TRANSACTION;
        INSERT INTO penerimaan (status, idpengadaan, iduser)
        VALUES ('P', p_idpengadaan, p_iduser);
        SET p_idpenerimaan = LAST_INSERT_ID();

        INSERT INTO detail_penerimaan
            (idpenerimaan, barang_idbarang, jumlah_terima, harga_satuan_terima, sub_total_terima)
        SELECT p_idpenerimaan, d.idbarang, hitung_sisa_barang(d.idpengadaan, d.idbarang, 0),
               d.harga_satuan, 0
        FROM detail_pengadaan d
        WHERE d.idpengadaan = p_idpengadaan
          AND hitung_sisa_barang(d.idpengadaan, d.idbarang, 0) > 0
        ORDER BY d.iddetail_pengadaan;   -- sub_total_terima dihitung ulang oleh trigger
    COMMIT;
END //


DELIMITER ;

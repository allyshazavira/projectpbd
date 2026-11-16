
CREATE OR REPLACE VIEW viewpenerimaan
AS SELECT pn.idpenerimaan AS ID, pn.created_at AS Tanggal,
    pn.idpengadaan AS ID_Pengadaan, v.nama_vendor AS Vendor, u.username AS Diterima_oleh,
    (SELECT COALESCE(SUM(dp.sub_total_terima), 0)
     FROM detail_penerimaan dp WHERE dp.idpenerimaan = pn.idpenerimaan) AS Total,
    CASE pn.status WHEN 'P' THEN 'Diproses' WHEN 'S' THEN 'Selesai' ELSE pn.status END AS Status
FROM penerimaan pn
JOIN pengadaan pg ON pn.idpengadaan = pg.idpengadaan
JOIN vendor v ON pg.vendor_idvendor = v.idvendor
JOIN user u ON pn.iduser = u.iduser;

CREATE OR REPLACE VIEW viewdetailpenerimaan
AS SELECT dp.idpenerimaan AS ID_Penerimaan, dp.iddetail_penerimaan AS ID_Detail,
    b.nama AS Barang, s.nama_satuan AS Satuan,
    dp.jumlah_terima AS Jumlah_Terima, dp.harga_satuan_terima AS Harga_Satuan,
    dp.sub_total_terima AS Subtotal
FROM detail_penerimaan dp
JOIN barang b ON dp.barang_idbarang = b.idbarang
JOIN satuan s ON b.idsatuan = s.idsatuan;


DELIMITER //
CREATE PROCEDURE sp_posting_penerimaan(IN p_idpenerimaan BIGINT)
BEGIN
	DECLARE v_selesai INT DEFAULT 0;
    DECLARE v_idbarang INT;
    DECLARE v_jumlah INT;
    DECLARE v_harga INT;
    DECLARE cur CURSOR FOR
		SELECT barang_idbarang, jumlah_terima, harga_satuan_terima
        FROM detail_penerimaan WHERE idpenerimaan = p_idpenerimaan
        ORDER BY iddetail_penerimaan;
	DECLARE CONTINUE HANDLER  FOR NOT FOUND SET v_selesai = 1;
    
    OPEN cur;
    baca: LOOP
		FETCH cur INTO v_idbarang, v_jumlah, v_harga;
        IF v_selesai = 1 THEN
			LEAVE baca;
		END IF;
        CALL sp_catat_kartu_stok('P', p_idpenerimaan, v_idbarang, v_jumlah, 0);
        UPDATE barang SET harga = v_harga WHERE idbarang = idbarang;
	END LOOP;
    CLOSE cur;
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
    IF EXISTS (SELECT 1 FROM penerimaan WHERE idpengadaan = p_idpengadaan) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Pengadaan ini sudah memiliki penerimaan';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM detail_pengadaan WHERE idpengadaan = p_idpengadaan) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pengadaan belum memiliki detail barang';
    END IF;

    START TRANSACTION;
        INSERT INTO penerimaan (status, idpengadaan, iduser)
        VALUES ('P', p_idpengadaan, p_iduser);
        SET p_idpenerimaan = LAST_INSERT_ID();

        INSERT INTO detail_penerimaan
            (idpenerimaan, barang_idbarang, jumlah_terima, harga_satuan_terima, sub_total_terima)
        SELECT p_idpenerimaan, idbarang, jumlah, harga_satuan, sub_total
        FROM detail_pengadaan
        WHERE idpengadaan = p_idpengadaan
        ORDER BY iddetail_pengadaan;
    COMMIT;
END //

CREATE PROCEDURE sp_ubah_harga_terima(IN p_iddetail BIGINT, IN p_harga INT)
BEGIN
    IF NOT EXISTS (SELECT 1 FROM detail_penerimaan WHERE iddetail_penerimaan = p_iddetail) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Detail penerimaan tidak ditemukan';
    END IF;

    UPDATE detail_penerimaan SET harga_satuan_terima = p_harga
    WHERE iddetail_penerimaan = p_iddetail;      -- trigger: cek status, hitung ulang subtotal
END //

CREATE PROCEDURE sp_selesaikan_penerimaan(IN p_idpenerimaan BIGINT)
BEGIN
    IF NOT EXISTS (SELECT 1 FROM penerimaan WHERE idpenerimaan = p_idpenerimaan) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Penerimaan tidak ditemukan';
    END IF;
    IF EXISTS (SELECT 1 FROM penerimaan WHERE idpenerimaan = p_idpenerimaan AND status = 'S') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Penerimaan sudah selesai';
    END IF;

    UPDATE penerimaan SET status = 'S' WHERE idpenerimaan = p_idpenerimaan;
END //

CREATE TRIGGER trg_penerimaan_bu BEFORE UPDATE ON penerimaan
FOR EACH ROW
BEGIN
    IF NEW.status NOT IN ('P', 'S') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Status penerimaan harus P (diproses) atau S (selesai)';
    END IF;
    IF OLD.status = 'S' AND NEW.status <> 'S' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Penerimaan yang sudah selesai tidak bisa dibuka lagi';
    END IF;
END //

CREATE TRIGGER trg_penerimaan_au AFTER UPDATE ON penerimaan
FOR EACH ROW
BEGIN
    IF OLD.status = 'P' AND NEW.status = 'S' THEN
        UPDATE pengadaan SET status = 'S' WHERE idpengadaan = NEW.idpengadaan;   -- sinkron
        CALL sp_posting_penerimaan(NEW.idpenerimaan);                            -- stok + harga barang
    END IF;
END //

CREATE TRIGGER trg_detail_penerimaan_bi BEFORE INSERT ON detail_penerimaan
FOR EACH ROW
BEGIN
    IF NEW.jumlah_terima <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Jumlah terima harus lebih dari 0';
    END IF;
    IF NEW.harga_satuan_terima <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Harga satuan terima harus lebih dari 0';
    END IF;
    SET NEW.sub_total_terima = hitung_subtotal(NEW.harga_satuan_terima, NEW.jumlah_terima);
END //

CREATE TRIGGER trg_detail_penerimaan_bu BEFORE UPDATE ON detail_penerimaan
FOR EACH ROW
BEGIN
    DECLARE v_status CHAR(1);

    SELECT status INTO v_status FROM penerimaan WHERE idpenerimaan = OLD.idpenerimaan;
    IF v_status = 'S' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Penerimaan sudah selesai, harga terima tidak bisa diubah';
    END IF;
    IF NEW.harga_satuan_terima <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Harga satuan terima harus lebih dari 0';
    END IF;
    SET NEW.sub_total_terima = hitung_subtotal(NEW.harga_satuan_terima, NEW.jumlah_terima);
END //

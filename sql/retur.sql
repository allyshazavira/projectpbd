CREATE OR REPLACE VIEW viewretur
AS SELECT r.idretur AS ID, r.created_at AS Tanggal, r.idpenerimaan AS ID_Penerimaan,
    v.nama_vendor AS Vendor, u.username AS Dibuat_oleh,
    (SELECT COUNT(*) FROM detail_retur dr WHERE dr.idretur = r.idretur) AS Jumlah_Item
FROM retu r
JOIN penerimaan pn ON r.idpenerimaan = pn.idpenerimaan
JOIN pengadaan pg ON pn.idpengadaan = pg.idpengadaan
JOIN vendor v ON pg.vendor_idvendor = v.idvendor
JOIN user u ON r.iduser = u.iduser;

CREATE OR REPLACE VIEW viewdetailretur
AS SELECT dr.idretur AS ID_Retur, dr.iddetail_retur AS ID_Detail,
    b.nama AS Barang, s.nama_satuan AS Satuan, dr.jumlah AS Jumlah, dr.alasan AS Alasan
FROM detail_retur dr
JOIN detail_penerimaan dp ON dr.iddetail_penerimaan = dp.iddetail_penerimaan
JOIN barang b ON dp.barang_idbarang = b.idbarang
JOIN satuan s ON b.idsatuan = s.idsatuan;

DELIMITER //
CREATE PROCEDURE sp_buat_retur(
    IN p_idpenerimaan BIGINT, IN p_iduser INT, OUT p_idretur BIGINT)
BEGIN
    IF NOT EXISTS (SELECT 1 FROM penerimaan WHERE idpenerimaan = p_idpenerimaan) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Penerimaan tidak ditemukan';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM penerimaan WHERE idpenerimaan = p_idpenerimaan AND status = 'S') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Retur hanya bisa dibuat dari penerimaan yang sudah selesai';
    END IF;

    INSERT INTO retu (idpenerimaan, iduser) VALUES (p_idpenerimaan, p_iduser);
    SET p_idretur = LAST_INSERT_ID();
END //

CREATE PROCEDURE sp_tambah_detail_retur(
    IN p_idretur BIGINT, IN p_iddetail_penerimaan BIGINT,
    IN p_jumlah INT, IN p_alasan VARCHAR(200))
BEGIN
    IF p_alasan IS NULL OR TRIM(p_alasan) = '' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Alasan retur wajib diisi';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM retu r
                   JOIN detail_penerimaan dp ON dp.idpenerimaan = r.idpenerimaan
                   WHERE r.idretur = p_idretur
                     AND dp.iddetail_penerimaan = p_iddetail_penerimaan) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Barang tersebut bukan bagian dari penerimaan retur ini';
    END IF;

    INSERT INTO detail_retur (jumlah, alasan, idretur, iddetail_penerimaan)
    VALUES (p_jumlah, p_alasan, p_idretur, p_iddetail_penerimaan);
END //

CREATE TRIGGER trg_detail_retur_bi BEFORE INSERT ON detail_retur
FOR EACH ROW
BEGIN
    DECLARE v_terima INT;
    DECLARE v_sudah_retur INT;
    DECLARE v_idbarang INT;
    DECLARE v_status CHAR(1);

    IF NEW.jumlah <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Jumlah retur harus lebih dari 0';
    END IF;

    SELECT dp.jumlah_terima, dp.barang_idbarang, pn.status
      INTO v_terima, v_idbarang, v_status
    FROM detail_penerimaan dp
    JOIN penerimaan pn ON dp.idpenerimaan = pn.idpenerimaan
    WHERE dp.iddetail_penerimaan = NEW.iddetail_penerimaan;

    IF v_idbarang IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Detail penerimaan tidak ditemukan';
    END IF;
    IF v_status <> 'S' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Retur hanya untuk penerimaan yang sudah selesai';
    END IF;

    SELECT COALESCE(SUM(jumlah), 0) INTO v_sudah_retur
    FROM detail_retur WHERE iddetail_penerimaan = NEW.iddetail_penerimaan;

    IF NEW.jumlah + v_sudah_retur > v_terima THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Jumlah retur melebihi jumlah barang yang diterima';
    END IF;
    IF NEW.jumlah > get_stok(v_idbarang) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stok tidak cukup untuk diretur';
    END IF;
END //

CREATE TRIGGER trg_detail_retur_ai AFTER INSERT ON detail_retur
FOR EACH ROW
BEGIN
    DECLARE v_idbarang INT;

    SELECT barang_idbarang INTO v_idbarang
    FROM detail_penerimaan WHERE iddetail_penerimaan = NEW.iddetail_penerimaan;

    CALL sp_catat_kartu_stok('R', NEW.idretur, v_idbarang, 0, NEW.jumlah);
END //
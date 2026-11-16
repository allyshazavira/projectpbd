CREATE OR REPLACE VIEW viewpenjualan
AS SELECT p.idpenjualan AS ID, p.created_at AS Tanggal, u.username AS Kasir,
    m.persen AS Margin_Persen, p.subtotal_nilai AS Subtotal, p.ppn AS PPN, p.total_nilai AS Total
FROM penjualan p
JOIN user u ON p.iduser = u.iduser
JOIN margin_penjualan m ON p.idmargin_penjualan = m.idmargin_penjualan;

CREATE OR REPLACE VIEW viewdetailpenjualan
AS SELECT d.penjualan_idpenjualan AS ID_Penjualan, d.iddetail_penjualan AS ID_Detail,
    b.nama AS Barang, s.nama_satuan AS Satuan,
    d.harga_satuan AS Harga_Satuan, d.jumlah AS Jumlah, d.subtotal AS Subtotal
FROM detail_penjualan d
JOIN barang b ON d.idbarang = b.idbarang
JOIN satuan s ON b.idsatuan = s.idsatuan;


CREATE FUNCTION hitung_subtotal(p_harga_satuan INT, p_jumlah INT)
RETURNS INT 
DETERMINISTIC NO SQL
BEGIN
	RETURN p_harga_satuan*p_jumlah;
END //

DELIMITER //
CREATE FUNCTION hitung_total_nilai(p_subtotal INT)
RETURNS INT
DETERMINISTIC NO SQL
BEGIN
	RETURN p_subtotal + hitung_ppn(p_subtotal);
END //

CREATE FUNCTION hitung_subtotal_penjualan(p_idpenjualan INT)
RETURNS INT
READS SQL DATA
BEGIN
	RETURN COALESCE((SELECT SUM(SUBTOTAL) FROM detail_penjualan
    WHERE penjualan_idpenjualan = p_idpenjualan), 0);
END //

CREATE PROCEDURE hitung_ulang_penjualan(IN p_idpenjualan INT)
BEGIN
	DECLARE v_sub INT;
    SET v_sub = hitung_subtotal_penjualan(p_idpenjualan);
    
    UPDATE penjualan
    SET subtotal_nilai = v_sub,
    ppn = hitung_ppn(v_sub),
    total_nilai = hitung_total_nilai(v_sub)
    WHERE idpenjualan = p_idpenjualan;
END //

CREATE PROCEDURE sp_buat_penjualan(IN p_iduser INT, OUT p_idpenjualan INT)
BEGIN
    DECLARE v_idmargin INT;

    SET v_idmargin = get_idmargin_aktif();
    IF v_idmargin IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Belum ada margin penjualan yang aktif';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM user WHERE iduser = p_iduser) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'User tidak ditemukan';
    END IF;

    INSERT INTO penjualan (subtotal_nilai, ppn, total_nilai, iduser, idmargin_penjualan)
    VALUES (0, 0, 0, p_iduser, v_idmargin);
    SET p_idpenjualan = LAST_INSERT_ID();
END //

CREATE PROCEDURE sp_tambah_detail_penjualan(
    IN p_idpenjualan INT, IN p_idbarang INT, IN p_jumlah INT)
BEGIN
    DECLARE v_idmargin INT;
    DECLARE v_harga INT;

    SELECT idmargin_penjualan INTO v_idmargin
    FROM penjualan WHERE idpenjualan = p_idpenjualan;
    IF v_idmargin IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Penjualan tidak ditemukan';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM barang WHERE idbarang = p_idbarang AND status = 1) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Barang tidak ditemukan / tidak aktif';
    END IF;

    SET v_harga = hitung_harga_jual(p_idbarang, v_idmargin);

    INSERT INTO detail_penjualan
        (harga_satuan, jumlah, subtotal, penjualan_idpenjualan, idbarang)
    VALUES (v_harga, p_jumlah, hitung_subtotal(v_harga, p_jumlah), p_idpenjualan, p_idbarang);
END //

CREATE TRIGGER trg_detail_penjualan_bi BEFORE INSERT ON detail_penjualan
FOR EACH ROW
BEGIN
    IF NEW.jumlah <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Jumlah penjualan harus lebih dari 0';
    END IF;
    IF NEW.jumlah > get_stok(NEW.idbarang) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stok barang tidak mencukupi';
    END IF;
    SET NEW.subtotal = hitung_subtotal(NEW.harga_satuan, NEW.jumlah);
END //

CREATE TRIGGER trg_detail_penjualan_ai AFTER INSERT ON detail_penjualan
FOR EACH ROW
BEGIN
    CALL sp_catat_kartu_stok('J', NEW.penjualan_idpenjualan, NEW.idbarang, 0, NEW.jumlah);
    CALL sp_hitung_ulang_penjualan(NEW.penjualan_idpenjualan);
END //
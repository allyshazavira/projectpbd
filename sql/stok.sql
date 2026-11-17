CREATE OR REPLACE VIEW viewkartustok
AS SELECT k.idkartu_stok AS ID, k.created_at AS Tanggal, k.idbarang AS ID_Barang,
    b.nama AS Barang,
    CASE k.jenis_transaksi WHEN 'P' THEN 'Penerimaan' WHEN 'J' THEN 'Penjualan'
                           WHEN 'R' THEN 'Retur' ELSE k.jenis_transaksi END AS Transaksi,
    k.idtransaksi AS No_Transaksi, k.masuk AS Masuk, k.keluar AS Keluar, k.stock AS Stok
FROM kartu_stok k
JOIN barang b ON k.idbarang = b.idbarang;

CREATE OR REPLACE VIEW viewstok
AS SELECT b.idbarang AS ID, b.nama AS Barang,
    CASE b.jenis WHEN 'O' THEN 'Obat' WHEN 'A' THEN 'Alat Kesehatan' END AS Kategori,
    s.nama_satuan AS Satuan, get_stok(b.idbarang) AS Stok, b.harga AS Harga_Dasar,
    hitung_harga_jual(b.idbarang, get_idmargin_aktif()) AS Harga_Jual
FROM barang b
JOIN satuan s ON b.idsatuan = s.idsatuan
WHERE b.status = 1;



DELIMITER //
DROP FUNCTION get_stok;
CREATE FUNCTION get_stok(p_idbarang INT)
RETURNS INT
READS SQL DATA
BEGIN
	RETURN COALESCE((SELECT stock FROM kartu_stok WHERE idbarang = p_idbarang 
					ORDER BY idkartu_stok DESC LIMIT 1), 0);
END //

CREATE PROCEDURE sp_catat_kartu_stok(
	IN p_jenis CHAR(1), IN p_idtransaksi INT, IN p_idbarang INT, IN p_masuk INT,
    IN p_keluar INT)
BEGIN
	DECLARE v_stok INT;
    SET v_stok = get_stok(p_idbarang) + p_masuk - p_keluar;
    
    INSERT INTO kartu_stok
		(jenis_transaksi, masuk, keluar, stock, idtransaksi, idbarang)
	VALUES
		(p_jenis, p_masuk, p_keluar, v_stok, p_idtransaksi, p_idbarang);
END //

CREATE TRIGGER trg_kartu_stok_bu BEFORE UPDATE ON kartu_stok
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Kartu stok tidak boleh diubah';
END //

CREATE TRIGGER trg_kartu_stok_bd BEFORE DELETE ON kartu_stok
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Kartu stok tidak boleh dihapus';
END //

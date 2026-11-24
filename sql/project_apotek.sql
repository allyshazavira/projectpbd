-- CREATE DATABASE project_apotek;
USE project_apotek;
CREATE TABLE role (
	idrole INT PRIMARY KEY,
    nama_role VARCHAR(100) NOT NULL
    );

CREATE TABLE user (
	iduser INT PRIMARY KEY,
    username VARCHAR(45) NOT NULL,
    password VARCHAR(100) NOT NULL,
    idrole INT NOT NULL,
    foreign key (idrole) references role(idrole)
);

CREATE TABLE satuan (
	idsatuan INT primary key,
    nama_satuan varchar(45) not null,
    status tinyint not null
);

CREATE TABLE barang (
	idbarang INT primary key,
    jenis char(1) not null,
    nama varchar(45) not null,
    idsatuan int not null,
    status tinyint not null,
    harga int not null,
    foreign key (idsatuan) references satuan(idsatuan)
);

CREATE TABLE kartu_stok (
	idkartu_stok bigint primary key,
    jenis_transaksi char(1) not null,
    masuk int not null,
    keluar int not null,
    stock int not null,
    created_at timestamp default current_timestamp,
    idtransaksi int not null,
    idbarang int not null,
    foreign key (idbarang) references barang(idbarang)
);
select * from kartu_stok;

CREATE TABLE vendor (
	idvendor int primary key,
    nama_vendor varchar(100) not null,
    badan_hukum char(1) not null,
    status char(1) not null
);

CREATE TABLE margin_penjualan (
	idmargin_penjualan int primary key,
    created_at timestamp default current_timestamp,
    persen double not null,
    status tinyint not null,
    iduser int not null,
    updated_at timestamp default current_timestamp on update current_timestamp,
    foreign key (iduser) references user(iduser)
);

CREATE TABLE pengadaan (
	idpengadaan bigint primary key,
    timestamp timestamp default current_timestamp,
    user_iduser int not null,
    status char(1) not null,
    vendor_idvendor int not null,
    subtotal_nilai int not null,
    ppn int not null,
    total_nilai int not null,
    foreign key (user_iduser) references user(iduser),
    foreign key (vendor_idvendor) references vendor(idvendor)
);
CREATE TABLE detail_pengadaan (
	iddetail_pengadaan bigint primary key,
    harga_satuan int not null,
    jumlah int not null,
    sub_total int not null,
    idbarang int not null,
    idpengadaan bigint not null,
    foreign key (idbarang) references barang(idbarang),
    foreign key (idpengadaan) references pengadaan(idpengadaan)
);

CREATE TABLE penerimaan (
	idpenerimaan bigint primary key,
    created_at timestamp default current_timestamp,
    status char(4) not null,
    idpengadaan bigint not null,
    iduser int not null,
    foreign key (idpengadaan) references pengadaan(idpengadaan),
    foreign key (iduser) references user(iduser)
);

CREATE TABLE detail_penerimaan (
	iddetail_penerimaan bigint primary key,
    idpenerimaan bigint not null,
    barang_idbarang int not null,
    jumlah_terima int not null,
    harga_satuan_terima int not null,
    sub_total_terima int not null,
    foreign key (idpenerimaan) references penerimaan(idpenerimaan),
    foreign key (barang_idbarang) references barang(idbarang)
);

CREATE TABLE retu (
	idretur bigint primary key,
    created_at timestamp default current_timestamp,
    idpenerimaan bigint not null,
    iduser int not null,
    foreign key (idpenerimaan) references penerimaan(idpenerimaan),
    foreign key (iduser) references user(iduser)
);

CREATE TABLE detail_retur (
	iddetail_retur bigint primary key,
    jumlah int not null,
    alasan varchar(200) not null,
    idretur bigint not null,
    iddetail_penerimaan bigint not null,
    foreign key (idretur) references retu(idretur),
    foreign key (iddetail_penerimaan) references detail_penerimaan(iddetail_penerimaan)
);

CREATE TABLE penjualan (
	idpenjualan int primary key,
    created_at timestamp default current_timestamp,
    subtotal_nilai int not null,
    ppn int not null,
    total_nilai int not null,
    iduser int not null,
    idmargin_penjualan int not null,
    foreign key (iduser) references user(iduser),
    foreign key (idmargin_penjualan) references margin_penjualan(idmargin_penjualan)
);

CREATE TABLE detail_penjualan (
	iddetail_penjualan bigint primary key,
    harga_satuan int not null,
    jumlah int not null,
    subtotal int not null,
    penjualan_idpenjualan int not null,
    idbarang int not null,
    foreign key (penjualan_idpenjualan) references penjualan(idpenjualan),
    foreign key (idbarang) references barang(idbarang)
);

DELIMITER $$
-- membuat function
CREATE FUNCTION hitung_ppn(p_subtotal INT) -- pajak 11%
RETURNS INT
DETERMINISTIC NO SQL
BEGIN
	RETURN ROUND(p_subtotal*0.11);
END $$

-- harga jual setelah ppn
CREATE FUNCTION hitung_harga_jual(p_idbarang INT, p_idmargin INT)
RETURNS INT
READS SQL DATA
BEGIN
	DECLARE v_harga INT DEFAULT 0;
    DECLARE v_persen DOUBLE DEFAULT 0;
    
    SELECT harga INTO v_harga FROM BARANG WHERE idbarang = p_idbarang;
    SELECT persen INTO v_persen FROM margin_penjualan
    WHERE idmargin_penjualan = p_idmargin;
    
    RETURN ROUND(v_harga * (1 + v_persen / 100));
END $$


-- memncari idmargin yang aktif
CREATE FUNCTION get_idmargin_aktif()
RETURNS INT
READS SQL DATA
BEGIN
	RETURN (SELECT idmargin_penjualan FROM margin_penjualan
			WHERE status = 1
            ORDER BY idmargin_penjualan DESC LIMIT 1);
END $$

-- membuat sp
CREATE PROCEDURE sp_laporan_stok()
BEGIN
    SELECT b.idbarang AS ID, b.nama AS Barang, s.nama_satuan AS Satuan,
           get_stok(b.idbarang) AS Stok, b.harga AS Harga_Dasar,
           hitung_harga_jual(b.idbarang, get_idmargin_aktif()) AS Harga_Jual
    FROM barang b JOIN satuan s ON b.idsatuan = s.idsatuan
    WHERE b.status = 1 ORDER BY b.nama;
END$$

CREATE PROCEDURE sp_laporan_penjualan(IN p_tanggal_awal DATE, IN p_tanggal_akhir DATE)
BEGIN
	SELECT p.idpenjualan AS 'No Penjualan', p.crated_at AS Tanggal, u.username AS Kasir, 
    p.subtotal_nilai AS Subtotal, p.ppn AS PPN, p.total_nilai AS Total
    FROM penjualan p
    JOIN user u ON p.iduser = u.iduser
    WHERE DATE(p.created_at) BETWEEN p_tanggal_awal AND p_tanggal_akhir
    ORDER BY p.created_at;
END $$


-- membuat trigger
CREATE TRIGGER trg_barang_bi
BEFORE INSERT ON barang
FOR EACH ROW
BEGIN
	IF NEW.harga <= 0 THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Harga barang harus lebih dari 0';
	END IF;
    IF NEW.jenis NOT IN ('O', 'A') THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Jenis barang harus O (Obat) atau A (Alat Kesehatan)';
        END IF;
	IF NEW.idbarang IS NULL OR NEW.idbarang = 0 THEN
		SET NEW.idbarang = COALESCE((SELECT MAX(idbarang) FROM barang), 000) +1;
	END IF;
END $$

CREATE TRIGGER trg_barang_bu
BEFORE UPDATE ON barang
FOR EACH ROW
BEGIN
	IF NEW.harga <= 0 THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Harga barang harus lebih dari 0';
	END IF;
    IF NEW.jenis NOT IN ('O', 'A') THEN
		SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Jenis barang harus O (Obat) atau A (Alat Kesehatan)';
        END IF;
END $$

CREATE TRIGGER trg_margin_bi_aktif
BEFORE INSERT ON margin_penjualan
FOR EACH ROW
BEGIN
	IF NEW.status = 1 AND EXISTS
		(SELECT 1 FROM margin_penjualan WHERE status = 1) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Masih ada margin aktif, nonaktifkan terlebih dahulu';
	END IF;
END $$

CREATE TRIGGER trg_margin_bu_aktif
BEFORE UPDATE ON margin_penjualan
FOR EACH ROW
BEGIN
	IF NEW.status = 1 AND OLD.status = 0 AND EXISTS
    (SELECT 1 FROM margin_penjualan WHERE status = 1 AND idmargin_penjualan <> NEW.idmargin_penjualan) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Masih ada margin aktif, nonaktifkan terlebih dahulu';
	END IF;
END $$

-- create id user otomatis
CREATE TRIGGER trg_user_bi BEFORE INSERT ON user
FOR EACH ROW
BEGIN
    IF NEW.iduser IS NULL OR NEW.iduser = 0 THEN
        SET NEW.iduser = COALESCE((SELECT MAX(iduser) FROM user), 260500) + 1;
    END IF;
END$$

DELIMITER ;

DELIMITER //
CREATE PROCEDURE sp_set_margin(
    IN p_persen DOUBLE, IN p_iduser INT, OUT p_idmargin INT)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN ROLLBACK; RESIGNAL; END;

    IF NOT EXISTS (SELECT 1 FROM user u JOIN role r ON u.idrole = r.idrole
                   WHERE u.iduser = p_iduser
                     AND r.nama_role IN ('Apoteker Penanggung Jawab', 'Super Admin')) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Hanya Apoteker Penanggung Jawab atau Super Admin yang boleh mengubah margin';
    END IF;

    START TRANSACTION;
        UPDATE margin_penjualan SET status = 0 WHERE status = 1;
        SELECT COALESCE(MAX(idmargin_penjualan), 0) + 1 INTO p_idmargin
        FROM margin_penjualan;
        INSERT INTO margin_penjualan (idmargin_penjualan, persen, status, iduser)
        VALUES (p_idmargin, p_persen, 1, p_iduser);
    COMMIT;
END //


CREATE TRIGGER trg_margin_bi BEFORE INSERT ON margin_penjualan
FOR EACH ROW
BEGIN
    IF NEW.persen < 0 OR NEW.persen > 100 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Persen margin harus antara 0 - 100';
    END IF;
END //

CREATE PROCEDURE sp_kartu_stok(IN p_idbarang INT)
BEGIN
    SELECT k.created_at AS Tanggal,
           CASE k.jenis_transaksi WHEN 'P' THEN 'Penerimaan' WHEN 'J' THEN 'Penjualan' WHEN 'R' THEN 'Retur' END AS Transaksi,
           k.idtransaksi AS No_Transaksi, k.masuk AS Masuk, k.keluar AS Keluar, k.stock AS Stok
    FROM kartu_stok k WHERE k.idbarang = p_idbarang ORDER BY k.idkartu_stok;
END //
DELIMITER ;

USE PROJECT_APOTEK;
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE PENGADAAN 
MODIFY IDPENGADAAN BIGINT NOT NULL AUTO_INCREMENT;

ALTER TABLE DETAIL_PENGADAAN
MODIFY IDDETAIL_PENGADAAN BIGINT NOT NULL AUTO_INCREMENT;

ALTER TABLE PENERIMAAN
MODIFY IDPENERIMAAN BIGINT NOT NULL AUTO_INCREMENT;

ALTER TABLE DETAIL_PENERIMAAN
MODIFY IDDETAIL_PENERIMAAN BIGINT NOT NULL AUTO_INCREMENT;

ALTER TABLE RETU
MODIFY IDRETUR BIGINT NOT NULL AUTO_INCREMENT;

ALTER TABLE DETAIL_RETUR
MODIFY IDDETAIL_RETUR BIGINT NOT NULL AUTO_INCREMENT;

ALTER TABLE PENJUALAN
MODIFY IDPENJUALAN bigint NOT NULL AUTO_INCREMENT;

ALTER TABLE DETAIL_PENJUALAN
MODIFY IDDETAIL_PENJUALAN BIGINT NOT NULL AUTO_INCREMENT;

ALTER TABLE KARTU_STOK
MODIFY IDKARTU_STOK BIGINT NOT NULL AUTO_INCREMENT;

UPDATE barang SET harga = 3000  WHERE idbarang = 1;
UPDATE barang SET harga = 7000  WHERE idbarang = 2;
DROP PROCEDURE IF EXISTS sp_set_margin;

SELECT ID, Status FROM viewpenerimaan;
SELECT Barang, Jumlah, Jumlah_Diterima, Sisa FROM viewdetailpengadaan;
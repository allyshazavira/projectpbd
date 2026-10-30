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

USE project_apotek;
INSERT INTO role (nama_role, idrole) VALUES 
('Apoteker Penanggung Jawab', 1),
('Asisten Apoteker / Kasir', 2),
('Petugas Gudang Obat', 3);
select * from barang;

INSERT INTO user (username, password, idrole, iduser) VALUES
('apoteker_sherin', 'hashed_pass_apoteker', 1, 260501),
('kasir_bhanu', 'hashed_pass_kasir', 2, 260502),
('gudang_shaka', 'hashed_pass_gudang', 3, 260503);

INSERT INTO satuan (nama_satuan, status, idsatuan) VALUES
('Strip', 1, 1),
('Box', 1, 2),
('Botol', 1, 3),
('Tube', 1, 4),
('Tablet', 1, 5);

INSERT INTO vendor (nama_vendor, badan_hukum, status, idvendor) VALUES
('PT Kimia Farma Trading & Distribution', 'P', 1, 10),
('PT Mandira Distra Abadi', 'P', 1, 20),
('PT Anugerah Argon Medika', 'P', 1, 30);

INSERT INTO margin_penjualan (persen, status, iduser, idmargin_penjualan) VALUES
(10.0, 1, 260501, 1),
(20.0, 1, 260501, 2),
(30.0, 1, 260501, 3),
(40.0, 1, 260501, 4),
(50.0, 1, 260501, 5);

INSERT INTO barang (idbarang, jenis, nama, idsatuan, status, harga) VALUES
(001, 'O', 'Paracetamol 500mg Tablet', 5, 1, 3000),
(002, 'O', 'Ibuprofen 400mg Strip', 1, 1, 7000),
(003, 'O', 'Sirup Batuk Botol', 3, 1, 20000),
(004, 'A', 'Masker Kesehatan 3ply Box', 2, 1, 42000),
(005, 'A', 'Termometer Suhu Digital Box', 2, 1, 25000);
use project_apotek;

CREATE OR REPLACE VIEW viewsatuan
AS SELECT idsatuan AS ID, nama_satuan AS SATUAN,
	CASE
		WHEN status = 0 THEN 'Tidak Aktif'
        ELSE 'Aktif'
	END AS STATUS
FROM satuan;

CREATE OR REPLACE VIEW viewrole
AS SELECT idrole AS ID, nama_role AS "Nama Role" FROM role;

CREATE OR REPLACE VIEW viewuser
AS SELECT u.iduser AS ID, u.username AS Username, r.nama_role AS Role
FROM user u
JOIN role r ON u.idrole = r.idrole;

CREATE OR REPLACE VIEW viewvendor
AS SELECT idvendor, nama_vendor, badan_hukum,
	CASE badan_hukum
		WHEN 'P' THEN 'PT'
        WHEN 'C' THEN 'CV'
	END AS 'Badan Hukum',
    CASE status
		WHEN 0 THEN 'Tidak Aktif'
        ELSE 'Aktif'
	END AS 'Status'
FROM vendor;

CREATE OR REPLACE VIEW viewmargin
AS SELECT m.idmargin_penjualan AS ID, m.persen AS PERSENTASE, 
	CASE m.status
		WHEN 0 THEN 'Tidak aktif'
        ELSE 'Aktif'
	END AS Status,
u.username AS 'dibuat oleh', m.created_at AS 'Tanggal dibuat', m.updated_at  AS 'Tanggal diubah'
FROM margin_penjualan m
JOIN user u ON m.iduser = u.iduser;

CREATE OR REPLACE VIEW viewbarang
AS SELECT b.idbarang AS ID,
	CASE b.jenis
		WHEN 'O' THEN 'Obat'
        WHEN 'A' THEN 'Alat Kesehatan'
	END AS Kategori,
    b.nama AS nama_barang, s.nama_satuan AS Satuan, b.harga AS Harga, 
    CASE b.status
		WHEN 0 THEN 'Tidak Aktif'
        ELSE 'Aktif'
	END AS 'Status'
FROM barang b
JOIN satuan s ON b.idsatuan = s.idsatuan;

select * from viewbarang
DELIMITER $$
CREATE FUNCTION hitung_subtotal(p_harga_satuan INT, p_jumlah INT)
RETURNS INT 
DETERMINISTIC NO SQL
BEGIN
	RETURN p_harga_satuan*p_jumlah;
END $$

CREATE FUNCTION hitung_ppn(p_subtotal INT) -- pajak 11%
RETURNS INT
DETERMINISTIC NO SQL
BEGIN
	RETURN ROUND(p_subtotal*0.11);
END $$

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

CREATE FUNCTION get_idmargin_aktif()
RETURNS INT
READS SQL DATA
BEGIN
	RETURN (SELECT idmargin_penjualan FROM margin_penjualan
			WHERE status = 1
            ORDER BY idmargin_penjualan DESC LIMIT 1);
END $$

CREATE FUNCTION get_stok(p_idbarang INT)
RETURNS INT
READS SQL DATA
BEGIN
	RETURN COALESCE((SELECT stock FROM kartu_stok WHERE idbarang = p_idbarang 
					ORDER BY idkartu_stok DESC LIMIT 1), 0);
END $$

CREATE PROCEDURE sp_laporan_stok()
BEGIN
	SELECT b.idbarang AS ID, b.Nama AS Barang, s.nama_satuan AS Satuan,
    get_stok(b.idbarang) AS Stok, b.harga AS 'Harga Dasar', 
    hitung_harga_jual(b.idbarang, get_margin_aktif()) AS 'Harga Jual'
    FROM barang b
    JOIN satuan s ON b.idsatuan = s.idsatuan
    WHERE b.status = 1
    ORDER BY b.nama;
END $$

CREATE PROCEDURE sp_laporan_penjualan(IN p_tanggal_awal DATE, IN p_tanggal_akhir DATE)
BEGIN
	SELECT p.idpenjualan AS 'No Penjualan', p.crated_at AS Tanggal, u.username AS Kasir, 
    p.subtotal_nilai AS Subtotal, p.ppn AS PPN, p.total_nilai AS Total
    FROM penjualan p
    JOIN user u ON p.iduser = u.iduser
    WHERE DATE(p.created_at) BETWEEN p_tanggal_awal AND p_tanggal_akhir
    ORDER BY p.created_at;
END $$

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

CREATE TRIGGER trg_user_bi BEFORE INSERT ON user
FOR EACH ROW
BEGIN
    IF NEW.iduser IS NULL OR NEW.iduser = 0 THEN
        SET NEW.iduser = COALESCE((SELECT MAX(iduser) FROM user), 260500) + 1;
    END IF;
END$$
DELIMITER ;
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
DELIMITER ;
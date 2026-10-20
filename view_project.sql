use project_apotek;

CREATE OR REPLACE VIEW viewsatuan
AS SELECT idsatuan AS ID, nama_satuan AS SATUAN FROM satuan;

CREATE OR REPLACE VIEW viewrole
AS SELECT idrole AS ID, nama_role AS "Nama Role" FROM role;

CREATE OR REPLACE VIEW viewuser
AS SELECT u.iduser, u.username, r.nama_role 
FROM user u
JOIN role r ON u.idrole = r.idrole;

CREATE OR REPLACE VIEW viewvendor
AS SELECT idvendor, nama_vendor, badan_hukum FROM vendor;

CREATE OR REPLACE VIEW viewmargin
AS SELECT m.idmargin_penjualan, m.persen, m.status,
u.username AS dibuat_oleh, m.created_at, m.updated_at 
FROM margin_penjualan m
JOIN user u ON m.iduser = u.iduser;

CREATE OR REPLACE VIEW viewbarang
AS SELECT b.idbarang, b.jenis, b.nama AS nama_barang, s.nama_satuan, b.harga, b.status
FROM barang b
JOIN satuan s ON b.idsatuan = s.idsatuan;

select * from viewmargin
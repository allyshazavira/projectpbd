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
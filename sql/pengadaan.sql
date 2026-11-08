USE PROJECT_APOTEK;
SELECT * FROM PENGADAAN;
CREATE VIEW viewpengadaan
AS SELECT p.idpengadaan AS ID, p.timestamp AS Tanggal,
		v.nama_vendor AS Vendor, u.username AS Dibuat_oleh, p.subtotal_nilai AS Subtotal,
        p.ppn as PPN, p.total_nilai AS Total, 
        CASE p.status
			WHEN 'P' THEN 'Proses'
            WHEN 'S' THEN 'Selesai'
            ELSE p.status
		END AS Status
FROM pengadaan p
JOIN vendor v ON p.vendor_idvendor = v.idvendor
JOIN user u ON p.user_iduser = u.iduser;
SELECT * FROM viewpengadaan;

CREATE VIEW viewdetailpengadaan
AS SELECT d.idpengadaan AS 'ID Pengadaan', d.iddetail_pengadaan AS 'ID Detail', b.nama AS Barang,
		s.nama_satuan AS Satuan, d.harga_satuan AS 'Harga Satuan', d.jumlah AS Jumlah, d.sub_total AS Subtotal
FROM detail_pengadaan d
JOIN barang b ON d.idbarang = b.idbarang
JOIN satuan s ON b.idsatuan = s.idsatuan;
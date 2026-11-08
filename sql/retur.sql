CREATE VIEW viewretur
AS SELECT r.idretur AS ID, r.created_at AS Tanggal, r.idpenerimaan AS ID_Penerimaan,
    v.nama_vendor AS Vendor, u.username AS Dibuat_oleh,
    (SELECT COUNT(*) FROM detail_retur dr WHERE dr.idretur = r.idretur) AS Jumlah_Item
FROM retu r
JOIN penerimaan pn ON r.idpenerimaan = pn.idpenerimaan
JOIN pengadaan pg ON pn.idpengadaan = pg.idpengadaan
JOIN vendor v ON pg.vendor_idvendor = v.idvendor
JOIN user u ON r.iduser = u.iduser;
 
CREATE VIEW viewdetailretur
AS SELECT dr.idretur AS ID_Retur, dr.iddetail_retur AS ID_Detail,
    b.nama AS Barang, s.nama_satuan AS Satuan, dr.jumlah AS Jumlah,
    dr.alasan AS Alasan
FROM detail_retur dr
JOIN detail_penerimaan dp ON dr.iddetail_penerimaan = dp.iddetail_penerimaan
JOIN barang b ON dp.barang_idbarang = b.idbarang
JOIN satuan s ON b.idsatuan = s.idsatuan;
CREATE VIEW viewpenerimaan
AS SELECT pn.idpenerimaan AS ID, pn.created_at AS Tanggal, pn.idpengadaan AS ID_Pengadaan,
    v.nama_vendor AS Vendor, u.username AS Diterima_oleh,
    (SELECT COALESCE(SUM(dp.sub_total_terima), 0)
     FROM detail_penerimaan dp
     WHERE dp.idpenerimaan = pn.idpenerimaan) AS Total,
    pn.status AS Status
FROM penerimaan pn
JOIN pengadaan pg ON pn.idpengadaan = pg.idpengadaan
JOIN vendor v ON pg.vendor_idvendor = v.idvendor
JOIN user u ON pn.iduser = u.iduser;

CREATE OR REPLACE VIEW viewdetailpenerimaan
AS SELECT dp.idpenerimaan AS ID_Penerimaan, dp.iddetail_penerimaan AS ID_Detail,
    b.nama AS Barang, s.nama_satuan AS Satuan, dp.jumlah_terima AS Jumlah_Terima,
    dp.harga_satuan_terima AS Harga_Satuan, dp.sub_total_terima AS Subtotal
FROM detail_penerimaan dp
JOIN barang b ON dp.barang_idbarang = b.idbarang
JOIN satuan s ON b.idsatuan = s.idsatuan;
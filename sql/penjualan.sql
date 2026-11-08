CREATE VIEW viewpenjualan
AS SELECT p.idpenjualan AS ID, p.created_at AS Tanggal, u.username AS Kasir,
    m.persen AS Margin_Persen, p.subtotal_nilai AS Subtotal, p.ppn AS PPN,
    p.total_nilai AS Total
FROM penjualan p
JOIN user u ON p.iduser = u.iduser
JOIN margin_penjualan m ON p.idmargin_penjualan = m.idmargin_penjualan;
 
CREATE VIEW viewdetailpenjualan
AS SELECT d.penjualan_idpenjualan AS ID_Penjualan, d.iddetail_penjualan AS ID_Detail,
    b.nama AS Barang, s.nama_satuan AS Satuan, d.harga_satuan AS Harga_Satuan,
    d.jumlah AS Jumlah, d.subtotal AS Subtotal
FROM detail_penjualan d
JOIN barang b ON d.idbarang = b.idbarang
JOIN satuan s ON b.idsatuan = s.idsatuan;
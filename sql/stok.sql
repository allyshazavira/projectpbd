CREATE OR REPLACE VIEW viewkartustok
AS SELECT k.idkartu_stok AS ID, k.created_at AS Tanggal, k.idbarang AS ID_Barang,
    b.nama AS Barang,
    CASE k.jenis_transaksi
        WHEN 'P' THEN 'Penerimaan'
        WHEN 'J' THEN 'Penjualan'
        WHEN 'R' THEN 'Retur'
        ELSE k.jenis_transaksi
    END AS Transaksi,
    k.idtransaksi AS No_Transaksi, k.masuk AS Masuk, k.keluar AS Keluar,
    k.stock AS Stok
FROM kartu_stok k
JOIN barang b ON k.idbarang = b.idbarang;
 
-- Stok terkini semua barang aktif + harga jual dengan margin aktif
CREATE OR REPLACE VIEW viewstok
AS SELECT b.idbarang AS ID, b.nama AS Barang,
    CASE b.jenis
        WHEN 'O' THEN 'Obat'
        WHEN 'A' THEN 'Alat Kesehatan'
    END AS Kategori,
    s.nama_satuan AS Satuan, get_stok(b.idbarang) AS Stok,
    b.harga AS Harga_Dasar, hitung_harga_jual(b.idbarang, get_idmargin_aktif()) AS Harga_Jual
FROM barang b
JOIN satuan s ON b.idsatuan = s.idsatuan
WHERE b.status = 1;
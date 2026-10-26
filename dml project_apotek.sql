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
drop table role
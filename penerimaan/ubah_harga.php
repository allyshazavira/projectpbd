<?php
// Penyimpanan jumlah dan harga sekarang dilakukan sekaligus melalui selesai.php.
$base = '../';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_SUPERADMIN, ROLE_GUDANG]);

$id = (int)($_POST['id'] ?? 0);
flash('info', 'Jumlah dan harga disimpan sekaligus saat tombol Simpan Penerimaan digunakan.');
redirect("detail.php?id=$id");

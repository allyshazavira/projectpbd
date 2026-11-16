<?php
$base = '../';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_APOTEKER, ROLE_GUDANG]);
global $conn;

$idp = (int)($_POST['idpengadaan'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $idbarang = (int)($_POST['idbarang'] ?? 0);
    $jumlah   = (int)($_POST['jumlah'] ?? 0);

    try {
        // harga satuan diambil otomatis dari tabel barang oleh procedure/trigger
        $stmt = $conn->prepare("CALL sp_tambah_detail_pengadaan(?, ?, ?)");
        $stmt->bind_param('iii', $idp, $idbarang, $jumlah);
        $stmt->execute();
        $stmt->close();
        flash('success', 'Barang ditambahkan ke pengadaan.');
    } catch (mysqli_sql_exception $e) {
        flash('danger', $e->getMessage());
    }
}
redirect("detail.php?id=$idp");

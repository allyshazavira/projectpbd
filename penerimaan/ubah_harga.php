<?php
$base = '../';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_SUPERADMIN, ROLE_GUDANG]);
global $conn;

$id = (int)($_POST['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $jumlah = $_POST['jumlah'] ?? [];
    $harga  = $_POST['harga'] ?? [];

    try {
        $conn->begin_transaction();
        foreach ($harga as $iddetail => $h) {
            if (!isset($jumlah[$iddetail])) continue;
            $iddetail = (int)$iddetail;
            $j = (int)$jumlah[$iddetail];
            $h = (int)$h;
            // trigger: cek status, batas sisa pesanan, validasi harga, hitung ulang subtotal
            $stmt = $conn->prepare("CALL sp_ubah_detail_penerimaan(?, ?, ?)");
            $stmt->bind_param('iii', $iddetail, $j, $h);
            $stmt->execute();
            $stmt->close();
        }
        $conn->commit();
        flash('success', 'Jumlah dan harga terima berhasil disimpan.');
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        flash('danger', $e->getMessage());
    }
}
redirect("detail.php?id=$id");

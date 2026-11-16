<?php
$base = '../';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_APOTEKER, ROLE_GUDANG]);
global $conn;

$id = (int)($_POST['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $harga = $_POST['harga'] ?? [];

    try {
        $conn->begin_transaction();

        // 1) simpan dulu harga yang sedang diedit
        foreach ($harga as $iddetail => $h) {
            $iddetail = (int)$iddetail;
            $h = (int)$h;
            $stmt = $conn->prepare("CALL sp_ubah_harga_terima(?, ?)");
            $stmt->bind_param('ii', $iddetail, $h);
            $stmt->execute();
            $stmt->close();
        }

        // 2) selesaikan. Trigger: status pengadaan ikut selesai, stok masuk, harga barang diperbarui
        $stmt = $conn->prepare("CALL sp_selesaikan_penerimaan(?)");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        flash('success', 'Penerimaan selesai. Stok bertambah dan harga barang sudah diperbarui.');
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        flash('danger', $e->getMessage());
    }
}
redirect("detail.php?id=$id");

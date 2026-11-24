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

        // 1) simpan dulu jumlah & harga yang sedang diedit
        foreach ($harga as $iddetail => $h) {
            if (!isset($jumlah[$iddetail])) continue;
            $iddetail = (int)$iddetail;
            $j = (int)$jumlah[$iddetail];
            $h = (int)$h;
            $stmt = $conn->prepare("CALL sp_ubah_detail_penerimaan(?, ?, ?)");
            $stmt->bind_param('iii', $iddetail, $j, $h);
            $stmt->execute();
            $stmt->close();
        }

        // 2) selesaikan. Trigger: stok masuk, harga barang diperbarui,
        //    pengadaan jadi Selesai hanya kalau semua barang sudah diterima
        $stmt = $conn->prepare("CALL sp_selesaikan_penerimaan(?)");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        $q = $conn->prepare("SELECT p.Sisa_Barang, p.Status FROM viewpenerimaan r JOIN viewpengadaan p ON p.ID = r.ID_Pengadaan WHERE r.ID = ?");
        $q->bind_param('i', $id);
        $q->execute();
        $st = $q->get_result()->fetch_assoc();
        if ($st && $st['Status'] === 'Selesai') {
            flash('success', 'Penerimaan selesai. Semua barang sudah diterima, pengadaan ditandai Selesai.');
        } else {
            flash('success', 'Penerimaan selesai. Masih ada ' . (int)($st['Sisa_Barang'] ?? 0) . ' barang yang belum dikirim; terima sisanya dari halaman pengadaan.');
        }
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        flash('danger', $e->getMessage());
    }
}
redirect("detail.php?id=$id");

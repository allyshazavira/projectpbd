<?php
$base = '../';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_APOTEKER, ROLE_GUDANG]);
global $conn;

$idp = (int)($_POST['idpengadaan'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $iddetail = (int)($_POST['iddetail'] ?? 0);

    try {
        $cek = $conn->prepare(
            "SELECT p.status, (SELECT COUNT(*) FROM penerimaan WHERE idpengadaan = p.idpengadaan) AS sudah_terima
             FROM pengadaan p WHERE p.idpengadaan = ?");
        $cek->bind_param('i', $idp);
        $cek->execute();
        $row = $cek->get_result()->fetch_assoc();

        if (!$row || $row['status'] !== 'P' || (int)$row['sudah_terima'] > 0) {
            flash('warning', 'Pengadaan sudah diterima / selesai, barangnya tidak bisa dihapus.');
        } else {
            // trigger trg_detail_pengadaan_ad otomatis menghitung ulang total
            $stmt = $conn->prepare("DELETE FROM detail_pengadaan WHERE iddetail_pengadaan = ? AND idpengadaan = ?");
            $stmt->bind_param('ii', $iddetail, $idp);
            $stmt->execute();
            flash('success', 'Barang dihapus dari pengadaan.');
        }
    } catch (mysqli_sql_exception $e) {
        flash('danger', 'Gagal menghapus: ' . $e->getMessage());
    }
}
redirect("detail.php?id=$idp");

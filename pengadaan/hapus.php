<?php
$base = '../';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_SUPERADMIN, ROLE_GUDANG]);
global $conn;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);

    try {
        $cek = $conn->prepare(
            "SELECT p.status, (SELECT COUNT(*) FROM penerimaan WHERE idpengadaan = p.idpengadaan) AS sudah_terima
             FROM pengadaan p WHERE p.idpengadaan = ?");
        $cek->bind_param('i', $id);
        $cek->execute();
        $row = $cek->get_result()->fetch_assoc();

        if (!$row) {
            flash('danger', 'Pengadaan tidak ditemukan.');
        } elseif ($row['status'] !== 'P' || (int)$row['sudah_terima'] > 0) {
            flash('warning', 'Pengadaan yang sudah diterima tidak bisa dihapus.');
        } else {
            $conn->begin_transaction();
            $d = $conn->prepare("DELETE FROM detail_pengadaan WHERE idpengadaan = ?");
            $d->bind_param('i', $id);
            $d->execute();
            $h = $conn->prepare("DELETE FROM pengadaan WHERE idpengadaan = ?");
            $h->bind_param('i', $id);
            $h->execute();
            $conn->commit();
            flash('success', 'Pengadaan berhasil dihapus.');
        }
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        flash('danger', 'Gagal menghapus: ' . $e->getMessage());
    }
}
redirect('index.php');

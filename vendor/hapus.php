<?php
$base = '../';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_APOTEKER, ROLE_GUDANG]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    try {
        $stmt = $conn->prepare("DELETE FROM vendor WHERE idvendor = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        flash('success', $stmt->affected_rows ? 'Vendor berhasil dihapus.' : 'Data vendor tidak ditemukan.');
    } catch (mysqli_sql_exception $e) {
        flash('danger', $e->getCode() == 1451
            ? 'Vendor tidak bisa dihapus karena sudah dipakai di pengadaan. Ubah statusnya menjadi Tidak Aktif saja.'
            : 'Gagal menghapus: ' . $e->getMessage());
    }
}
redirect('index.php');

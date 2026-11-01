<?php
$base = '../';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_APOTEKER, ROLE_GUDANG]);
global $conn;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    try {
        $stmt = $conn->prepare("DELETE FROM satuan WHERE idsatuan = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        flash('success', $stmt->affected_rows ? 'Satuan berhasil dihapus.' : 'Data satuan tidak ditemukan.');
    } catch (mysqli_sql_exception $e) {
        flash('danger', $e->getCode() == 1451
            ? 'Satuan tidak bisa dihapus karena masih dipakai oleh barang. Ubah statusnya menjadi Tidak Aktif saja.'
            : 'Gagal menghapus: ' . $e->getMessage());
    }
}
redirect('index.php');

<?php
$base = '../';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_APOTEKER]);
global $conn;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 3) {
        flash('warning', 'Role bawaan sistem tidak boleh dihapus.');
        redirect('index.php');
    }

    try {
        $stmt = $conn->prepare("DELETE FROM role WHERE idrole = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        flash('success', $stmt->affected_rows ? 'Role berhasil dihapus.' : 'Data role tidak ditemukan.');
    } catch (mysqli_sql_exception $e) {
        flash('danger', $e->getCode() == 1451
            ? 'Role tidak bisa dihapus karena masih dipakai oleh user.'
            : 'Gagal menghapus: ' . $e->getMessage());
    }
}
redirect('index.php');

<?php
$base = '../';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_APOTEKER]);
global $conn;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);

    if ($id === (int)$_SESSION['iduser']) {
        flash('warning', 'Anda tidak bisa menghapus akun yang sedang dipakai.');
        redirect('index.php');
    }

    try {
        $stmt = $conn->prepare("DELETE FROM user WHERE iduser = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        flash('success', $stmt->affected_rows ? 'User berhasil dihapus.' : 'Data user tidak ditemukan.');
    } catch (mysqli_sql_exception $e) {
        flash('danger', $e->getCode() == 1451
            ? 'User tidak bisa dihapus karena sudah tercatat di transaksi (margin, pengadaan, penjualan, dll).'
            : 'Gagal menghapus: ' . $e->getMessage());
    }
}
redirect('index.php');

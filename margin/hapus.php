<?php
$base = '../';
require_once $base . 'helpers.php';
global $conn;
require_login();
require_role([ROLE_APOTEKER]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);

    // Margin yang sedang aktif tidak boleh dihapus
    $cek = $conn->prepare("SELECT status FROM margin_penjualan WHERE idmargin_penjualan = ?");
    $cek->bind_param('i', $id);
    $cek->execute();
    $row = $cek->get_result()->fetch_assoc();
    if ($row && (int)$row['status'] === 1) {
        flash('warning', 'Margin yang sedang aktif tidak bisa dihapus. Aktifkan margin lain dulu.');
        redirect('index.php');
    }

    try {
        $stmt = $conn->prepare("DELETE FROM margin_penjualan WHERE idmargin_penjualan = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        flash('success', $stmt->affected_rows ? 'Margin berhasil dihapus.' : 'Data margin tidak ditemukan.');
    } catch (mysqli_sql_exception $e) {
        flash('danger', $e->getCode() == 1451
            ? 'Margin tidak bisa dihapus karena sudah dipakai di transaksi penjualan.'
            : 'Gagal menghapus: ' . $e->getMessage());
    }
}
redirect('index.php');

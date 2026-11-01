<?php
$base = '../';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_APOTEKER]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);

    try {
        $conn->begin_transaction();
        $conn->query("UPDATE margin_penjualan SET status = 0 WHERE status = 1");
        $stmt = $conn->prepare("UPDATE margin_penjualan SET status = 1 WHERE idmargin_penjualan = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if ($stmt->affected_rows === 0) {
            throw new RuntimeException('Data margin tidak ditemukan.');
        }
        $conn->commit();
        flash('success', 'Margin berhasil diaktifkan.');
    } catch (Throwable $e) {
        $conn->rollback();
        flash('danger', 'Gagal mengaktifkan margin: ' . $e->getMessage());
    }
}
redirect('index.php');
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$role_user = isset($_SESSION['role']) ? $_SESSION['role'] : '';
?>

<div class="sidebar">
    <a href="index.php">Dashboard</a>
    <a href="barang.php">Data Barang</a>

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'Apoteker Penanggung Jawab') : ?>
        <a href="user.php">Data User</a>
        <a href="margin.php">Atur Margin Penjualan</a>
    <?php endif; ?>

    <a href="vendor.php">Data Vendor</a>
    <a href="logout.php" style="color: #ff6b6b; margin-top: 30px;">Keluar</a>
</div>

<div class="main-content">
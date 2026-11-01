<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$base = $base ?? '';
$menu = $menu ?? '';
$isApoteker = isset($_SESSION['role']) && $_SESSION['role'] == 'Apoteker Penanggung Jawab';
?>

<div class="sidebar">
    <a href="<?= $base ?>index.php" class="<?= $menu == 'dashboard' ? 'active' : '' ?>">Dashboard</a>
    <a href="<?= $base ?>barang/index.php" class="<?= $menu == 'barang' ? 'active' : '' ?>">Data Barang</a>
    <a href="<?= $base ?>satuan/index.php" class="<?= $menu == 'satuan' ? 'active' : '' ?>">Data Satuan</a>
    <a href="<?= $base ?>vendor/index.php" class="<?= $menu == 'vendor' ? 'active' : '' ?>">Data Vendor</a>

    <?php if ($isApoteker) : ?>
        <a href="<?= $base ?>user/index.php" class="<?= $menu == 'user' ? 'active' : '' ?>">Data User</a>
        <a href="<?= $base ?>role/index.php" class="<?= $menu == 'role' ? 'active' : '' ?>">Data Role</a>
        <a href="<?= $base ?>margin/index.php" class="<?= $menu == 'margin' ? 'active' : '' ?>">Atur Margin Penjualan</a>
    <?php endif; ?>

    <a href="<?= $base ?>logout.php" style="color: #ff6b6b; margin-top: 30px;">Keluar</a>
</div>

<div class="main-content">

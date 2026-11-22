<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$base = $base ?? '';
$menu = $menu ?? '';

$roleLogin  = $_SESSION['role'] ?? '';
$isSuper    = $roleLogin === 'Super Admin';
$isApoteker = $roleLogin === 'Apoteker Penanggung Jawab';
$bisaGudang = $isSuper || $roleLogin === 'Petugas Gudang Obat';
$bisaKasir  = $isSuper || $roleLogin === 'Asisten Apoteker / Kasir';

$on   = function ($m) use ($menu) { return $menu == $m ? 'active' : ''; };
$buka = function (array $daftar) use ($menu) { return in_array($menu, $daftar, true) ? 'open' : ''; };
?>

<div class="sidebar">
    <a href="<?= $base ?>index.php" class="<?= $on('dashboard') ?>">Dashboard</a>

    <details class="sidebar-group" <?= $buka(['barang', 'satuan', 'vendor', 'user', 'role', 'margin']) ?>>
        <summary>Data Master</summary>
        <a href="<?= $base ?>barang/index.php" class="<?= $on('barang') ?>">Data Barang</a>
        <a href="<?= $base ?>satuan/index.php" class="<?= $on('satuan') ?>">Data Satuan</a>
        <a href="<?= $base ?>vendor/index.php" class="<?= $on('vendor') ?>">Data Vendor</a>
        <?php if ($isSuper) : ?>
            <a href="<?= $base ?>user/index.php" class="<?= $on('user') ?>">Data User</a>
            <a href="<?= $base ?>role/index.php" class="<?= $on('role') ?>">Data Role</a>
            <a href="<?= $base ?>margin/index.php" class="<?= $on('margin') ?>">Atur Margin Penjualan</a>
        <?php endif; ?>
    </details>

    <?php if ($bisaGudang || $bisaKasir) : ?>
    <details class="sidebar-group" <?= $buka(['pengadaan', 'penerimaan', 'retur', 'penjualan']) ?>>
        <summary>Transaksi</summary>
        <?php if ($bisaGudang) : ?>
            <a href="<?= $base ?>pengadaan/index.php" class="<?= $on('pengadaan') ?>">Pengadaan</a>
            <a href="<?= $base ?>penerimaan/index.php" class="<?= $on('penerimaan') ?>">Penerimaan Barang</a>
            <a href="<?= $base ?>retur/index.php" class="<?= $on('retur') ?>">Retur Barang</a>
        <?php endif; ?>
        <?php if ($bisaKasir) : ?>
            <a href="<?= $base ?>penjualan/index.php" class="<?= $on('penjualan') ?>">Penjualan (Kasir)</a>
        <?php endif; ?>
    </details>
    <?php endif; ?>

    <details class="sidebar-group" <?= $buka(['lap_stok', 'kartu_stok', 'lap_penjualan']) ?>>
        <summary>Laporan</summary>
        <a href="<?= $base ?>laporan/stok.php" class="<?= $on('lap_stok') ?>">Stok Barang</a>
        <a href="<?= $base ?>laporan/kartu_stok.php" class="<?= $on('kartu_stok') ?>">Kartu Stok</a>
        <?php if ($bisaKasir) : ?>
            <a href="<?= $base ?>laporan/penjualan.php" class="<?= $on('lap_penjualan') ?>">Laporan Penjualan</a>
        <?php endif; ?>
    </details>

    <a href="<?= $base ?>logout.php" style="color: #ff6b6b; margin-top: 30px;">Keluar</a>
</div>

<div class="main-content">
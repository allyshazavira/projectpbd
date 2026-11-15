<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$base = $base ?? '';
$menu = $menu ?? '';
$roleLogin  = $_SESSION['role'] ?? '';
$isApoteker = $roleLogin == 'Apoteker Penanggung Jawab';
$bisaGudang = $isApoteker || $roleLogin == 'Petugas Gudang Obat';
$bisaKasir  = $isApoteker || $roleLogin == 'Asisten Apoteker / Kasir';
$on = function ($m) use ($menu) { return $menu == $m ? 'active' : ''; };
?>

<div class="sidebar">
    <a href="<?= $base ?>index.php" class="<?= $on('dashboard') ?>">Dashboard</a>

    <details class="sidebar-group"<?= in_array($menu, ['barang', 'satuan', 'vendor', 'user', 'role', 'margin']) ? 'open' : '' ?>>
        <summary>Data Master</summary>
    <a href="<?= $base ?>barang/index.php" class="<?= $on('barang') ?>">Data Barang</a>
    <a href="<?= $base ?>satuan/index.php" class="<?= $on('satuan') ?>">Data Satuan</a>
    <a href="<?= $base ?>vendor/index.php" class="<?= $on('vendor') ?>">Data Vendor</a>
    <?php if ($isApoteker) : ?>
        <a href="<?= $base ?>user/index.php" class="<?= $on('user') ?>">Data User</a>
        <a href="<?= $base ?>role/index.php" class="<?= $on('role') ?>">Data Role</a>
        <a href="<?= $base ?>margin/index.php" class="<?= $on('margin') ?>">Atur Margin Penjualan</a>
    </details>
    <?php endif; ?>

    <?php if ($bisaGudang || $bisaKasir) : ?>
    <details class="sidebar-group" <?= in_array($menu, ['pengadaan', 'penerimaan', 'penjualan']) ? 'open' : '' ?>>
    <summary>Transaksi</summary>
    <?php if ($bisaGudang) : ?>
        <a href="<?= $base ?>pengadaan/index.php" class="<?= $on('pengadaan') ?>">Pengadaan</a>
        <a href="<?= $base ?>penerimaan/index.php" class="<?= $on('penerimaan') ?>">Penerimaan Barang</a>
    <?php endif; ?>
    <?php if ($bisaKasir) : ?>
        <a href="<?= $base ?>penjualan/index.php" class="<?= $on('penjualan') ?>">Penjualan (Kasir)</a>
    <?php endif; ?>
</details>
<?php endif; ?>

    <details class="sidebar-group"<?= in_array($menu, ['laporan', 'lap_stok', 'kartu_stok', 'lap_penjualan']) ? 'open' : '' ?>>
        <summary>Laporan</summary>
    <a href="<?= $base ?>laporan/stok.php" class="<?= $on('lap_stok') ?>">Stok Barang</a>
    <a href="<?= $base ?>laporan/kartu_stok.php" class="<?= $on('kartu_stok') ?>">Kartu Stok</a>
    <?php if ($bisaKasir) : ?>
        <a href="<?= $base ?>laporan/penjualan.php" class="<?= $on('lap_penjualan') ?>">Laporan Penjualan</a>
    </details>
    <?php endif; ?>

    <a href="<?= $base ?>logout.php" style="color: #ff6b6b;">Keluar</a>
</div>

<div class="main-content">

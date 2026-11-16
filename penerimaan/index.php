<?php
$base = '../'; $menu = 'penerimaan';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_APOTEKER, ROLE_GUDANG]);
global $conn;

$result = $conn->query("SELECT * FROM viewpenerimaan ORDER BY ID DESC");

include $base . 'navbar.php';
include $base . 'sidebar.php';
?>

<div class="container-fluid">
    <h3 class="mb-3">Penerimaan Barang</h3>
    <?php show_flash(); ?>
    <div class="alert alert-info py-2">
        Penerimaan dibuat dari halaman <a href="../pengadaan/index.php">Pengadaan</a> lewat tombol <b>Terima Barang</b>.
        Stok baru masuk setelah penerimaan <b>diselesaikan</b>.
    </div>

    <table>
        <thead>
            <tr><th>No</th><th>Tanggal</th><th>Dari Pengadaan</th><th>Vendor</th><th>Diterima oleh</th><th>Total</th><th>Status</th><th style="width:100px">Aksi</th></tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows === 0) : ?>
            <tr><td colspan="8" class="text-center text-muted">Belum ada penerimaan barang.</td></tr>
        <?php endif; ?>
        <?php while ($row = $result->fetch_assoc()) : ?>
            <tr>
                <td>#<?= h($row['ID']) ?></td>
                <td><?= h($row['Tanggal']) ?></td>
                <td><a href="../pengadaan/detail.php?id=<?= (int)$row['ID_Pengadaan'] ?>">#<?= h($row['ID_Pengadaan']) ?></a></td>
                <td><?= h($row['Vendor']) ?></td>
                <td><?= h($row['Diterima_oleh']) ?></td>
                <td><?= rupiah($row['Total']) ?></td>
                <td><?= $row['Status'] === 'Selesai' ? badge('Selesai', 'success') : badge('Diproses', 'warning') ?></td>
                <td><a href="detail.php?id=<?= (int)$row['ID'] ?>" class="btn btn-sm btn-outline-primary">Detail</a></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php include $base . 'footer.php'; ?>

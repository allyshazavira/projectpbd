<?php
$base = '../'; $menu = 'penjualan';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_KASIR, ROLE_APOTEKER]);
global $conn;

$result = $conn->query("SELECT * FROM viewpenjualan ORDER BY ID DESC LIMIT 200");

include $base . 'navbar.php';
include $base . 'sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Penjualan</h3>
        <a href="form.php" class="btn btn-primary btn-sm">+ Transaksi Baru</a>
    </div>

    <?php show_flash(); ?>

    <table>
        <thead>
            <tr><th>No Nota</th><th>Tanggal</th><th>Kasir</th><th>Subtotal</th><th>PPN</th><th>Total</th><th style="width:100px">Aksi</th></tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows === 0) : ?>
            <tr><td colspan="7" class="text-center text-muted">Belum ada penjualan.</td></tr>
        <?php endif; ?>
        <?php while ($row = $result->fetch_assoc()) : ?>
            <tr>
                <td>#<?= h($row['ID']) ?></td>
                <td><?= h($row['Tanggal']) ?></td>
                <td><?= h($row['Kasir']) ?></td>
                <td><?= rupiah($row['Subtotal']) ?></td>
                <td><?= rupiah($row['PPN']) ?></td>
                <td><b><?= rupiah($row['Total']) ?></b></td>
                <td><a href="detail.php?id=<?= (int)$row['ID'] ?>" class="btn btn-sm btn-outline-primary">Nota</a></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php include $base . 'footer.php'; ?>


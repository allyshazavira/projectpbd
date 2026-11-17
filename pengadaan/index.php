<?php
$base = '../'; $menu = 'pengadaan';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_APOTEKER, ROLE_GUDANG]);
global $conn;

$result = $conn->query("SELECT * FROM viewpengadaan ORDER BY ID");

include $base . 'navbar.php';
include $base . 'sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Pengadaan Barang</h3>
        <a href="form.php" class="btn btn-primary btn-sm">+ Buat Pengadaan</a>
    </div>

    <?php show_flash(); ?>

    <table>
        <thead>
            <tr><th>No</th><th>Tanggal</th><th>Vendor</th><th>Dibuat oleh</th><th>Total</th><th>Status</th><th style="width:100px">Aksi</th></tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows === 0) : ?>
            <tr><td colspan="7" class="text-center text-muted">Belum ada pengadaan.</td></tr>
        <?php endif; ?>
        <?php while ($row = $result->fetch_assoc()) : ?>
            <tr>
                <td>#<?= h($row['ID']) ?></td>
                <td><?= h($row['Tanggal']) ?></td>
                <td><?= h($row['Vendor']) ?></td>
                <td><?= h($row['Dibuat_oleh']) ?></td>
                <td><?= rupiah($row['Total']) ?></td>
                <td><?= $row['Status'] === 'Selesai' ? badge('Selesai', 'success') : badge($row['Status'], 'warning') ?></td>
                <td><a href="detail.php?id=<?= (int)$row['ID'] ?>" class="btn btn-sm btn-outline-primary">Detail</a></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php include $base . 'footer.php'; ?>

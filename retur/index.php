<?php
$base = '../'; $menu = 'retur';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_SUPERADMIN, ROLE_GUDANG]);
global $conn;

$result = $conn->query("SELECT * FROM viewretur ORDER BY ID DESC");

include $base . 'navbar.php';
include $base . 'sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Retur Barang ke Vendor</h3>
        <a href="form.php" class="btn btn-primary btn-sm">+ Retur Baru</a>
    </div>

    <?php show_flash(); ?>

    <table>
        <thead>
            <tr><th>No</th><th>Tanggal</th><th>Dari Penerimaan</th><th>Vendor</th><th>Dibuat oleh</th><th>Jumlah Item</th><th style="width:100px">Aksi</th></tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows === 0) : ?>
            <tr><td colspan="7" class="text-center text-muted">Belum ada retur.</td></tr>
        <?php endif; ?>
        <?php while ($row = $result->fetch_assoc()) : ?>
            <tr>
                <td>#<?= h($row['ID']) ?></td>
                <td><?= h($row['Tanggal']) ?></td>
                <td><a href="../penerimaan/detail.php?id=<?= (int)$row['ID_Penerimaan'] ?>">#<?= h($row['ID_Penerimaan']) ?></a></td>
                <td><?= h($row['Vendor']) ?></td>
                <td><?= h($row['Dibuat_oleh']) ?></td>
                <td><?= (int)$row['Jumlah_Item'] ?></td>
                <td><a href="detail.php?id=<?= (int)$row['ID'] ?>" class="btn btn-sm btn-outline-primary">Detail</a></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php include $base . 'footer.php'; ?>

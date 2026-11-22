<?php
$base = '../'; $menu = 'retur';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_SUPERADMIN, ROLE_GUDANG]);
global $conn;

$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM viewretur WHERE ID = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$r = $stmt->get_result()->fetch_assoc();
if (!$r) {
    flash('danger', 'Retur tidak ditemukan.');
    redirect('index.php');
}

$stmt = $conn->prepare("SELECT * FROM viewdetailretur WHERE ID_Retur = ? ORDER BY ID_Detail");
$stmt->bind_param('i', $id);
$stmt->execute();
$items = $stmt->get_result();

include $base . 'navbar.php';
include $base . 'sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Retur #<?= (int)$r['ID'] ?></h3>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">&larr; Kembali</a>
    </div>

    <?php show_flash(); ?>

    <div class="card shadow-sm mb-3">
        <div class="card-body row">
            <div class="col-md-3"><div class="text-muted small">Vendor</div><b><?= h($r['Vendor']) ?></b></div>
            <div class="col-md-3"><div class="text-muted small">Tanggal</div><?= h($r['Tanggal']) ?></div>
            <div class="col-md-3"><div class="text-muted small">Dibuat oleh</div><?= h($r['Dibuat_oleh']) ?></div>
            <div class="col-md-3"><div class="text-muted small">Dari penerimaan</div>
                <a href="../penerimaan/detail.php?id=<?= (int)$r['ID_Penerimaan'] ?>">#<?= (int)$r['ID_Penerimaan'] ?></a></div>
        </div>
    </div>

    <table>
        <thead><tr><th>Barang</th><th>Satuan</th><th>Jumlah</th><th>Alasan</th></tr></thead>
        <tbody>
        <?php while ($d = $items->fetch_assoc()) : ?>
            <tr>
                <td><?= h($d['Barang']) ?></td>
                <td><?= h($d['Satuan']) ?></td>
                <td><?= (int)$d['Jumlah'] ?></td>
                <td><?= h($d['Alasan']) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php include $base . 'footer.php'; ?>

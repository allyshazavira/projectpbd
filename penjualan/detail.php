<?php
$base = '../'; $menu = 'penjualan';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_APOTEKER, ROLE_KASIR]);
global $conn;

$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM viewpenjualan WHERE ID = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$p = $result->fetch_assoc();
$stmt->close();

if(!$p) {
    flash('danger', 'Nota tidak ditemukan.');
    redirect('index.php');
}

$stmt = $conn->prepare("SELECT * FROM viewdetailpenjualan WHERE ID_Penjualan = ? ORDER BY ID_Detail");
$stmt->bind_param('i', $id);
$stmt->execute();
$items = $stmt->get_result();

include $base . 'navbar.php';
include $base . 'sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h3 class="mb-0">nota #<?= (int)$p['ID']?></h3>
        <div>
           <button class="btn btn-putline-primary btn-sm" onclick="window.print()">cetak</button>
           <a href = "form.php" class="btn btn-primary btn-sm">transaksi baru</a>
           <a href = "index.php" class="btn btn-outline-secondary btn-sm">&larr; kembali</a>
        </div>
    </div>

    <div class="no-print"><?php show_flash(); ?></div>

    <div class="card shadow-sm mb-3" style="max-width:760px">
        <div class="card-body">
            <h5 class="mb-1">apotek</h5>
            <div class="text-muted small mb-3">
                Nota #<?= (int)$p['ID'] ?> &bull; <?= h($p['Tanggal']) ?> &bull; kasir: <?= h($p['Kasir']) ?>
            </div>

            <table style="box-shadow:none;margin-top:0">
                <thead><tr><th>Barang</th><th>Jumlah</th><th>Harga</th><th>Total</th></tr></thead>
                <tbody>
                 <?php while ($d = $items->fetch_assoc()) : ?>
                    <tr>
                        <td><?= h($d['Barang']) ?> <span class="text-muted small">(<?= h($d['Satuan']) ?>)</span></td>
                        <td class="text-end"><?= (int)$d['Jumlah'] ?></td>
                        <td class="text-end"><?= rupiah($d['Harga_Satuan']) ?></td>
                        <td class="text-end"><?= rupiah($d['Subtotal']) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr><td colspan="3" class="text-end">Subtotal</td><td class="text-end"><?= rupiah($p['Subtotal']) ?></td></tr>
                    <tr><td colspan="3" class="text-end">PPN 11%</td><td class="text-end"><?= rupiah($p['PPN']) ?></td></tr>
                    <tr><td colspan="3" class="text-end"><b>Total</b></td><td class="text-end"><b><?= rupiah($p['Total']) ?></b></td></tr>
                </tfoot>
            </table>
            <div class="text-muted small mt-2">Margin yang dipakai: <?= h(rtrim(rtrim(number_format($p['Margin_Persen'], 2, '.', ''), '0'), '.')) ?>%</div>
        </div>
    </div>
</div>
<?php include $base . 'footer.php'; ?>
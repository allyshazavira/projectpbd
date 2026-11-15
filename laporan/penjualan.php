<?php
$base = '../'; $menu = 'lap_penjualan';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_APOTEKER, ROLE_KASIR]);
global $conn;

$validDate = static function ($value, $fallback) {
    if (!is_string($value)) { return $fallback; }
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $value : $fallback;
};
$awal  = $validDate($_GET['awal']  ?? '', date('Y-m-01'));
$akhir = $validDate($_GET['akhir'] ?? '', date('Y-m-d'));
if ($awal > $akhir) { $tmp = $awal; $awal = $akhir; $akhir = $tmp; }

$q = $conn->prepare("SELECT * FROM viewpenjualan WHERE DATE(Tanggal) BETWEEN ? AND ? ORDER BY Tanggal");
$q->bind_param('ss', $awal, $akhir);
$q->execute();
$rows = $q->get_result()->fetch_all(MYSQLI_ASSOC);
$sub = $ppn = $total = 0;
foreach ($rows as $r) { $sub += $r['Subtotal']; $ppn += $r['PPN']; $total += $r['Total']; }

include $base . 'navbar.php';
include $base . 'sidebar.php';
?>

<div class="container-fluid">
    <h3 class="mb-3">Laporan Penjualan</h3>

    <form method="get" class="row g-2 mb-3 no-print">
        <div class="col-auto"><label class="form-label small mb-0">Dari</label>
            <input type="date" name="awal" class="form-control" value="<?= h($awal) ?>"></div>
        <div class="col-auto"><label class="form-label small mb-0">Sampai</label>
            <input type="date" name="akhir" class="form-control" value="<?= h($akhir) ?>"></div>
        <div class="col-auto align-self-end">
            <button class="btn btn-primary">Tampilkan</button>
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">Cetak</button>
        </div>
    </form>

    <table>
        <thead><tr><th>No Nota</th><th>Tanggal</th><th>Kasir</th><th>Subtotal</th><th>PPN</th><th>Total</th></tr></thead>
        <tbody>
        <?php if (!$rows) : ?>
            <tr><td colspan="6" class="text-center text-muted">Tidak ada penjualan pada periode ini.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r) : ?>
            <tr>
                <td><a href="../penjualan/detail.php?id=<?= (int)$r['ID'] ?>">#<?= h($r['ID']) ?></a></td>
                <td><?= h($r['Tanggal']) ?></td>
                <td><?= h($r['Kasir']) ?></td>
                <td><?= rupiah($r['Subtotal']) ?></td>
                <td><?= rupiah($r['PPN']) ?></td>
                <td><?= rupiah($r['Total']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <?php if ($rows) : ?>
        <tfoot>
            <tr><td colspan="3" class="text-end"><b>Jumlah <?= count($rows) ?> nota</b></td>
                <td><b><?= rupiah($sub) ?></b></td><td><b><?= rupiah($ppn) ?></b></td><td><b><?= rupiah($total) ?></b></td></tr>
        </tfoot>
        <?php endif; ?>
    </table>
</div>
<?php include $base . 'footer.php'; ?>

<?php
$base = '../'; $menu = 'lap_stok';
require_once $base . 'helpers.php';
require_login();
global $conn;

if (!function_exists('badge')) {
    function badge($text, $variant = 'secondary')
    {
        $safeText = htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
        $safeVariant = preg_replace('/[^a-z0-9_-]/i', '', (string)$variant);

        $class = 'bg-' . ($safeVariant !== '' ? $safeVariant : 'secondary');
        if ($safeVariant === 'warning') {
            $class = 'bg-warning text-dark';
        }

        return '<span class="badge ' . $class . '">' . $safeText . '</span>';
    }
}

$rows = $conn->query("SELECT * FROM viewstok ORDER BY Barang")->fetch_all(MYSQLI_ASSOC);
$idmargin = $conn->query("SELECT get_idmargin_aktif() AS id")->fetch_assoc()['id'];
$persen = null;
if ($idmargin !== null) {
    $persen = $conn->query("SELECT persen FROM margin_penjualan WHERE idmargin_penjualan = " . (int)$idmargin)->fetch_assoc()['persen'];
}
$batas = 10;   // stok dianggap menipis jika <= batas ini

include $base . 'navbar.php';
include $base . 'sidebar.php';
?>

<div class="container-fluid">
    <h3 class="mb-3">Laporan Stok Barang</h3>

    <div class="alert alert-info py-2">
        <?php if ($persen === null) : ?>
            Belum ada margin aktif. Harga jual yang tampil sama dengan harga dasar.
        <?php else : ?>
            Harga jual memakai margin aktif <b><?= h(rtrim(rtrim(number_format($persen, 2, '.', ''), '0'), '.')) ?>%</b>.
        <?php endif; ?>
        Stok <= <?= $batas ?> ditandai kuning.
    </div>

    <table>
        <thead>
            <tr><th>ID</th><th>Barang</th><th>Satuan</th><th>Stok</th><th>Harga Dasar</th><th>Harga Jual</th><th style="width:110px">Kartu Stok</th></tr>
        </thead>
        <tbody>
        <?php if (!$rows) : ?>
            <tr><td colspan="7" class="text-center text-muted">Belum ada barang aktif.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r) : ?>
            <?php $stok = (int)$r['Stok']; ?>
            <tr>
                <td><?= h($r['ID']) ?></td>
                <td><?= h($r['Barang']) ?></td>
                <td><?= h($r['Satuan']) ?></td>
                <td>
                    <?php if ($stok <= 0) echo badge('Habis', 'danger');
                          elseif ($stok <= $batas) echo badge($stok, 'warning');
                          else echo $stok; ?>
                </td>
                <td><?= rupiah($r['Harga_Dasar']) ?></td>
                <td><?= rupiah($r['Harga_Jual']) ?></td>
                <td><a href="kartu_stok.php?id=<?= (int)$r['ID'] ?>" class="btn btn-sm btn-outline-primary">Lihat</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include $base . 'footer.php'; ?>

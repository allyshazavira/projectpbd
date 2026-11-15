<?php
$base = '../'; $menu = 'kartu_stok';
require_once $base . 'helpers.php';
require_login();
global $conn;

$id = (int)($_GET['id'] ?? 0);
$rows = [];
$barangDipilih = null;

if ($id > 0) {
    $stmt = $conn->prepare("SELECT b.nama, s.nama_satuan, get_stok(b.idbarang) AS stok
                            FROM barang b JOIN satuan s ON b.idsatuan = s.idsatuan WHERE b.idbarang = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $barangDipilih = $stmt->get_result()->fetch_assoc();
    if ($barangDipilih) {
        $q = $conn->prepare("SELECT * FROM viewkartustok WHERE ID_Barang = ? ORDER BY ID");
        $q->bind_param('i', $id);
        $q->execute();
        $rows = $q->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

$daftar = $conn->query("SELECT idbarang, nama FROM barang ORDER BY nama");

include $base . 'navbar.php';
include $base . 'sidebar.php';
?>

<div class="container-fluid">
    <h3 class="mb-3">Kartu Stok</h3>

    <form method="get" class="row g-2 mb-3">
        <div class="col-md-5">
            <select name="id" class="form-select" required>
                <option value="">-- Pilih barang --</option>
                <?php while ($b = $daftar->fetch_assoc()) : ?>
                    <option value="<?= (int)$b['idbarang'] ?>" <?= $id === (int)$b['idbarang'] ? 'selected' : '' ?>><?= h($b['nama']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-auto"><button class="btn btn-primary">Tampilkan</button></div>
    </form>

    <?php if ($id > 0 && !$barangDipilih) : ?>
        <div class="alert alert-danger">Barang tidak ditemukan.</div>
    <?php elseif ($barangDipilih) : ?>
        <div class="alert alert-info py-2">
            <b><?= h($barangDipilih['nama']) ?></b> &mdash; stok sekarang:
            <b><?= (int)$barangDipilih['stok'] ?></b> <?= h($barangDipilih['nama_satuan']) ?>
        </div>

        <table>
            <thead><tr><th>Tanggal</th><th>Transaksi</th><th>No Transaksi</th><th>Masuk</th><th>Keluar</th><th>Stok</th></tr></thead>
            <tbody>
            <?php if (!$rows) : ?>
                <tr><td colspan="6" class="text-center text-muted">Belum ada pergerakan stok untuk barang ini.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r) : ?>
                <tr>
                    <td><?= h($r['Tanggal']) ?></td>
                    <td><?= h($r['Transaksi']) ?></td>
                    <td>#<?= h($r['No_Transaksi']) ?></td>
                    <td><?= (int)$r['Masuk'] > 0 ? '<span class="text-success">+' . (int)$r['Masuk'] . '</span>' : '-' ?></td>
                    <td><?= (int)$r['Keluar'] > 0 ? '<span class="text-danger">-' . (int)$r['Keluar'] . '</span>' : '-' ?></td>
                    <td><b><?= (int)$r['Stok'] ?></b></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php include $base . 'footer.php'; ?>

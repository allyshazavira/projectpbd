<?php
$base = '../'; $menu = 'pengadaan';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_SUPERADMIN, ROLE_GUDANG]);
global $conn;

$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM viewpengadaan WHERE ID = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
if (!$p) {
    flash('danger', 'Pengadaan tidak ditemukan.');
    redirect('index.php');
}
$proses = $p['Status'] === 'Diproses';

// Semua penerimaan (pengiriman) milik pengadaan ini
$stmt = $conn->prepare("SELECT ID, Tanggal, Total, Status FROM viewpenerimaan WHERE ID_Pengadaan = ? ORDER BY ID");
$stmt->bind_param('i', $id);
$stmt->execute();
$penerimaan = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$adaTerbuka = false;
foreach ($penerimaan as $r) { if ($r['Status'] === 'Diproses') $adaTerbuka = true; }

$stmt = $conn->prepare("SELECT * FROM viewdetailpengadaan WHERE ID_Pengadaan = ? ORDER BY ID_Detail");
$stmt->bind_param('i', $id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$sisaTotal  = (int)$p['Sisa_Barang'];
// Barang hanya boleh diubah selama diproses dan belum pernah ada penerimaan
$bolehUbah  = $proses && !$penerimaan;
// Penerimaan baru: masih ada sisa, dan tidak ada penerimaan lain yang belum diselesaikan
$bolehTerima = $proses && $items && !$adaTerbuka && $sisaTotal > 0;

if ($bolehUbah) {
    $barang = $conn->query("SELECT ID, nama_barang, Satuan, Harga FROM viewbarangaktif ORDER BY nama_barang");
}

include $base . 'navbar.php';
include $base . 'sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Pengadaan #<?= (int)$p['ID'] ?></h3>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">&larr; Kembali</a>
    </div>

    <?php show_flash(); ?>

    <div class="card shadow-sm mb-3">
        <div class="card-body row">
            <div class="col-md-3"><div class="text-muted small">Vendor</div><b><?= h($p['Vendor']) ?></b></div>
            <div class="col-md-3"><div class="text-muted small">Tanggal</div><?= h($p['Tanggal']) ?></div>
            <div class="col-md-3"><div class="text-muted small">Dibuat oleh</div><?= h($p['Dibuat_oleh']) ?></div>
            <div class="col-md-3"><div class="text-muted small">Status</div>
                <?= $proses ? badge('Diproses', 'warning') : badge('Selesai', 'success') ?>
                <?php if ($proses && $penerimaan) : ?><span class="small text-muted">sisa <?= $sisaTotal ?> belum diterima<?= (int)$p['Sedang_Diproses'] > 0 ? ', ' . (int)$p['Sedang_Diproses'] . ' sedang diproses' : '' ?></span><?php endif; ?></div>
        </div>
    </div>

    <?php if ($bolehUbah) : ?>
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <h6>Tambah barang</h6>
            <form action="tambah_item.php" method="post" class="row g-2 align-items-end">
                <?= csrf_field() ?>
                <input type="hidden" name="idpengadaan" value="<?= (int)$p['ID'] ?>">
                <div class="col-md-7">
                    <label class="form-label small mb-1">Barang (harga satuan otomatis dari data barang)</label>
                    <select name="idbarang" class="form-select" required>
                        <option value="">-- Pilih barang --</option>
                        <?php while ($b = $barang->fetch_assoc()) : ?>
                            <option value="<?= (int)$b['ID'] ?>">
                                <?= h($b['nama_barang']) ?> (<?= h($b['Satuan']) ?>) - <?= rupiah($b['Harga']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Jumlah</label>
                    <input type="number" name="jumlah" class="form-control" min="1" required>
                </div>
                <div class="col-md-2"><button class="btn btn-primary w-100">Tambah</button></div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr><th>Barang</th><th>Satuan</th><th>Harga Satuan</th><th>Dipesan</th><th>Sudah Diterima</th><th>Sedang Diproses</th><th>Sisa</th><th>Subtotal</th>
                <?php if ($bolehUbah) : ?><th style="width:90px">Aksi</th><?php endif; ?></tr>
        </thead>
        <tbody>
        <?php if (!$items) : ?>
            <tr><td colspan="9" class="text-center text-muted">Belum ada barang di pengadaan ini.</td></tr>
        <?php endif; ?>
        <?php foreach ($items as $d) : ?>
            <tr>
                <td><?= h($d['Barang']) ?></td>
                <td><?= h($d['Satuan']) ?></td>
                <td><?= rupiah($d['Harga_Satuan']) ?></td>
                <td><?= (int)$d['Jumlah'] ?></td>
                <td><?= (int)$d['Jumlah_Diterima'] ?></td>
                <td><?= (int)$d['Sedang_Diproses'] > 0 ? '<span class="text-warning-emphasis">' . (int)$d['Sedang_Diproses'] . '</span>' : '0' ?></td>
                <td><?= (int)$d['Sisa'] === 0 ? badge('Lengkap', 'success') : '<b>' . (int)$d['Sisa'] . '</b>' ?></td>
                <td><?= rupiah($d['Subtotal']) ?></td>
                <?php if ($bolehUbah) : ?>
                <td>
                    <form action="hapus_item.php" method="post" onsubmit="return confirm('Hapus barang ini dari pengadaan?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="idpengadaan" value="<?= (int)$p['ID'] ?>">
                        <input type="hidden" name="iddetail" value="<?= (int)$d['ID_Detail'] ?>">
                        <button class="btn btn-sm btn-outline-danger">Hapus</button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr><td colspan="7" class="text-end">Subtotal</td><td><?= rupiah($p['Subtotal']) ?></td><?= $bolehUbah ? '<td></td>' : '' ?></tr>
            <tr><td colspan="7" class="text-end">PPN 11%</td><td><?= rupiah($p['PPN']) ?></td><?= $bolehUbah ? '<td></td>' : '' ?></tr>
            <tr><td colspan="7" class="text-end"><b>Total</b></td><td><b><?= rupiah($p['Total']) ?></b></td><?= $bolehUbah ? '<td></td>' : '' ?></tr>
        </tfoot>
    </table>

    <?php if ($penerimaan) : ?>
    <h6 class="mt-4">Penerimaan barang</h6>
    <table>
        <thead><tr><th>No</th><th>Tanggal</th><th>Nilai Diterima</th><th>Status</th><th style="width:100px">Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($penerimaan as $r) : ?>
            <tr>
                <td>#<?= (int)$r['ID'] ?></td>
                <td><?= h($r['Tanggal']) ?></td>
                <td><?= rupiah($r['Total']) ?></td>
                <td><?= $r['Status'] === 'Selesai' ? badge('Selesai', 'success') : badge('Diproses', 'warning') ?></td>
                <td><a href="../penerimaan/detail.php?id=<?= (int)$r['ID'] ?>" class="btn btn-sm btn-outline-primary">Buka</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <div class="mt-3 d-flex gap-2 align-items-center">
        <?php if ($bolehTerima) : ?>
        <form action="terima.php" method="post"
              onsubmit="return confirm('Buat penerimaan baru? Isi awalnya adalah sisa barang yang belum diterima.')">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$p['ID'] ?>">
            <button class="btn btn-success"><?= $penerimaan ? 'Terima Sisa Barang' : 'Terima Barang' ?></button>
        </form>
        <?php elseif ($proses && $adaTerbuka) : ?>
            <span class="text-muted small">Selesaikan pengisian penerimaan agar stok dan sisa pengadaan langsung diperbarui.</span>
        <?php endif; ?>

        <?php if ($bolehUbah) : ?>
        <form action="hapus.php" method="post" onsubmit="return confirm('Batalkan dan hapus pengadaan ini?')">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$p['ID'] ?>">
            <button class="btn btn-outline-danger">Hapus Pengadaan</button>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php include $base . 'footer.php'; ?>

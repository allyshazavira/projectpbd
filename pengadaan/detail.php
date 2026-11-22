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

// Penerimaan milik pengadaan ini (kalau sudah dibuat)
$stmt = $conn->prepare("SELECT ID, Status FROM viewpenerimaan WHERE ID_Pengadaan = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$pn = $stmt->get_result()->fetch_assoc();

// Barang hanya boleh diubah selama diproses dan belum ada penerimaan
$bolehUbah = $proses && !$pn;

$stmt = $conn->prepare("SELECT * FROM viewdetailpengadaan WHERE ID_Pengadaan = ? ORDER BY ID_Detail");
$stmt->bind_param('i', $id);
$stmt->execute();
$items = $stmt->get_result();

if ($bolehUbah) {
    // semua barang aktif (view aktif)
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
                <?= $proses ? badge('Diproses', 'warning') : badge('Selesai', 'success') ?></div>
        </div>
    </div>

    <?php if ($pn) : ?>
        <div class="alert alert-info py-2">
            Barang sudah diterima di
            <a href="../penerimaan/detail.php?id=<?= (int)$pn['ID'] ?>">Penerimaan #<?= (int)$pn['ID'] ?></a>
            (<?= h($pn['Status']) ?>).
        </div>
    <?php endif; ?>

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
            <tr><th>Barang</th><th>Satuan</th><th>Harga Satuan</th><th>Jumlah</th><th>Subtotal</th>
                <?php if ($bolehUbah) : ?><th style="width:90px">Aksi</th><?php endif; ?></tr>
        </thead>
        <tbody>
        <?php if ($items->num_rows === 0) : ?>
            <tr><td colspan="6" class="text-center text-muted">Belum ada barang di pengadaan ini.</td></tr>
        <?php endif; ?>
        <?php while ($d = $items->fetch_assoc()) : ?>
            <tr>
                <td><?= h($d['Barang']) ?></td>
                <td><?= h($d['Satuan']) ?></td>
                <td><?= rupiah($d['Harga_Satuan']) ?></td>
                <td><?= (int)$d['Jumlah'] ?></td>
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
        <?php endwhile; ?>
        </tbody>
        <tfoot>
            <tr><td colspan="4" class="text-end">Subtotal</td><td><?= rupiah($p['Subtotal']) ?></td><?= $bolehUbah ? '<td></td>' : '' ?></tr>
            <tr><td colspan="4" class="text-end">PPN 11%</td><td><?= rupiah($p['PPN']) ?></td><?= $bolehUbah ? '<td></td>' : '' ?></tr>
            <tr><td colspan="4" class="text-end"><b>Total</b></td><td><b><?= rupiah($p['Total']) ?></b></td><?= $bolehUbah ? '<td></td>' : '' ?></tr>
        </tfoot>
    </table>

    <?php if ($bolehUbah) : ?>
    <div class="mt-3 d-flex gap-2">
        <form action="terima.php" method="post"
              onsubmit="return confirm('Buat penerimaan dari pengadaan ini? Barang di pengadaan tidak bisa diubah lagi.')">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$p['ID'] ?>">
            <button class="btn btn-success" <?= $items->num_rows === 0 ? 'disabled' : '' ?>>Terima Barang</button>
        </form>
        <form action="hapus.php" method="post" onsubmit="return confirm('Batalkan dan hapus pengadaan ini?')">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$p['ID'] ?>">
            <button class="btn btn-outline-danger">Hapus Pengadaan</button>
        </form>
    </div>
    <?php endif; ?>
</div>
<?php include $base . 'footer.php'; ?>

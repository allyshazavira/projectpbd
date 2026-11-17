<?php
$base = '../'; $menu = 'penerimaan';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_APOTEKER, ROLE_GUDANG]);
global $conn;

$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM viewpenerimaan WHERE ID = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$pn = $stmt->get_result()->fetch_assoc();
if (!$pn) {
    flash('danger', 'Penerimaan tidak ditemukan.');
    redirect('index.php');
}
$diproses = $pn['Status'] === 'Diproses';

$stmt = $conn->prepare("SELECT * FROM viewdetailpenerimaan WHERE ID_Penerimaan = ? ORDER BY ID_Detail");
$stmt->bind_param('i', $id);
$stmt->execute();
$items = $stmt->get_result();

include $base . 'navbar.php';
include $base . 'sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Penerimaan #<?= (int)$pn['ID'] ?></h3>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">&larr; Kembali</a>
    </div>

    <?php show_flash(); ?>

    <div class="card shadow-sm mb-3">
        <div class="card-body row">
            <div class="col-md-3"><div class="text-muted small">Vendor</div><b><?= h($pn['Vendor']) ?></b></div>
            <div class="col-md-2"><div class="text-muted small">Tanggal</div><?= h($pn['Tanggal']) ?></div>
            <div class="col-md-2"><div class="text-muted small">Diterima oleh</div><?= h($pn['Diterima_oleh']) ?></div>
            <div class="col-md-3"><div class="text-muted small">Dari pengadaan</div>
                <a href="../pengadaan/detail.php?id=<?= (int)$pn['ID_Pengadaan'] ?>">#<?= (int)$pn['ID_Pengadaan'] ?></a></div>
            <div class="col-md-2"><div class="text-muted small">Status</div>
                <?= $diproses ? badge('Diproses', 'warning') : badge('Selesai', 'success') ?></div>
        </div>
    </div>

    <?php if ($diproses) : ?>
        <div class="alert alert-warning py-2">
            Harga satuan terima bisa diedit selama penerimaan masih <b>Diproses</b>. Saat diselesaikan, stok masuk ke
            kartu stok, status pengadaan ikut <b>Selesai</b>, dan harga barang diperbarui sesuai harga terima.
        </div>
    <?php endif; ?>

    <form method="post" action="ubah_harga.php">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$pn['ID'] ?>">

        <table>
            <thead><tr><th>Barang</th><th>Satuan</th><th>Jumlah Diterima</th><th style="width:200px">Harga Satuan Terima</th><th>Subtotal</th></tr></thead>
            <tbody>
            <?php while ($d = $items->fetch_assoc()) : ?>
                <tr>
                    <td><?= h($d['Barang']) ?></td>
                    <td><?= h($d['Satuan']) ?></td>
                    <td><?= (int)$d['Jumlah_Terima'] ?></td>
                    <td>
                        <?php if ($diproses) : ?>
                            <input type="number" min="1" class="form-control form-control-sm"
                                   name="harga[<?= (int)$d['ID_Detail'] ?>]" value="<?= (int)$d['Harga_Satuan'] ?>" required>
                        <?php else : ?>
                            <?= rupiah($d['Harga_Satuan']) ?>
                        <?php endif; ?>
                    </td>
                    <td><?= rupiah($d['Subtotal']) ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
            <tfoot><tr><td colspan="4" class="text-end"><b>Total (sebelum PPN)</b></td><td><b><?= rupiah($pn['Total']) ?></b></td></tr></tfoot>
        </table>

        <?php if ($diproses) : ?>
        <div class="mt-3 d-flex gap-2">
            <button class="btn btn-primary">Simpan Harga</button>
            <button class="btn btn-success" formaction="selesai.php"
                    onclick="return confirm('Selesaikan penerimaan? Stok akan masuk, harga barang diperbarui, dan penerimaan tidak bisa diubah lagi.')">
                Simpan &amp; Selesaikan Penerimaan
            </button>
        </div>
        <?php endif; ?>
    </form>
</div>
<?php include $base . 'footer.php'; ?>

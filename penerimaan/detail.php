<?php
$base = '../'; $menu = 'penerimaan';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_SUPERADMIN, ROLE_GUDANG]);
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
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

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
            Isi <b>jumlah diterima</b> sesuai barang yang benar-benar datang (boleh kurang dari sisa, isi 0 jika belum dikirim)
            dan koreksi <b>harga terima</b> bila perlu. Sisa yang belum dikirim bisa diterima di penerimaan berikutnya.
            Saat diselesaikan, stok masuk dan harga barang diperbarui sesuai harga terima.
        </div>
    <?php endif; ?>

    <?php if ($diproses) : ?>
        <div class="small text-muted mb-2">Sisa di halaman pengadaan baru berkurang setelah penerimaan ini <b>diselesaikan</b>.</div>
    <?php endif; ?>

    <form method="post" action="ubah_harga.php">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$pn['ID'] ?>">

        <table>
            <thead>
                <tr>
                    <th>Barang</th><th>Satuan</th><th>Dipesan</th><th>Sudah diterima sebelumnya</th>
                    <th style="width:130px">Diterima kali ini</th><th><?= $diproses ? 'Sisa setelah ini (perkiraan)' : 'Sisa belum dikirim' ?></th>
                    <th style="width:160px">Harga satuan terima</th><th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
            <?php $totalAwal = 0; foreach ($items as $d) :
                $dipesan   = (int)$d['Dipesan'];
                $sisaSblm  = (int)$d['Sisa_Sebelum'];
                $sebelumnya = $dipesan - $sisaSblm;
                $totalAwal += (int)$d['Subtotal'];
            ?>
                <tr data-sisa="<?= $sisaSblm ?>">
                    <td><?= h($d['Barang']) ?></td>
                    <td><?= h($d['Satuan']) ?></td>
                    <td><?= $dipesan ?></td>
                    <td><?= $sebelumnya ?></td>
                    <?php if ($diproses) : ?>
                        <td><input type="number" min="0" max="<?= $sisaSblm ?>" class="form-control form-control-sm in-jml"
                                   name="jumlah[<?= (int)$d['ID_Detail'] ?>]" value="<?= (int)$d['Jumlah_Terima'] ?>" required></td>
                        <td class="out-sisa"><?= $sisaSblm - (int)$d['Jumlah_Terima'] ?></td>
                        <td><input type="number" min="1" class="form-control form-control-sm in-harga"
                                   name="harga[<?= (int)$d['ID_Detail'] ?>]" value="<?= (int)$d['Harga_Satuan'] ?>" required></td>
                        <td class="out-sub"><?= rupiah($d['Subtotal']) ?></td>
                    <?php else : ?>
                        <td><?= (int)$d['Jumlah_Terima'] ?></td>
                        <td><?= $sisaSblm - (int)$d['Jumlah_Terima'] === 0 ? badge('Lengkap', 'success') : '<b>' . ($sisaSblm - (int)$d['Jumlah_Terima']) . '</b>' ?></td>
                        <td><?= rupiah($d['Harga_Satuan']) ?></td>
                        <td><?= rupiah($d['Subtotal']) ?></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot><tr><td colspan="7" class="text-end"><b>Nilai diterima (sebelum PPN)</b></td><td><b id="total"><?= rupiah($pn['Total']) ?></b></td></tr></tfoot>
        </table>

        <?php if ($diproses) : ?>
        <div class="mt-3 d-flex gap-2">
            <button class="btn btn-primary">Simpan</button>
            <button class="btn btn-success" formaction="selesai.php"
                    onclick="return confirm('Selesaikan penerimaan ini? Stok masuk, harga barang diperbarui, dan data tidak bisa diubah lagi.')">
                Simpan &amp; Selesaikan Penerimaan
            </button>
        </div>
        <?php endif; ?>
    </form>
</div>

<?php if ($diproses) : ?>
<script>
    // Perkiraan di layar; angka resmi dihitung database saat disimpan.
    function hitung() {
        let total = 0;
        document.querySelectorAll('tr[data-sisa]').forEach(tr => {
            const sisa = parseInt(tr.dataset.sisa) || 0;
            const j = parseInt(tr.querySelector('.in-jml').value) || 0;
            const h = parseInt(tr.querySelector('.in-harga').value) || 0;
            tr.querySelector('.out-sisa').textContent = Math.max(sisa - j, 0);
            tr.querySelector('.out-sub').textContent = 'Rp ' + (j * h).toLocaleString('id-ID');
            total += j * h;
        });
        document.getElementById('total').textContent = 'Rp ' + total.toLocaleString('id-ID');
    }
    document.addEventListener('input', hitung);
    hitung();
</script>
<?php endif; ?>
<?php include $base . 'footer.php'; ?>

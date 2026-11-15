<?php
$base = '../'; $menu = 'penjualan';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_APOTEKER, ROLE_KASIR]);
global $conn;

// Margin aktif (function get_idmargin_aktif) & daftar barang dengan stok + harga jual (viewstok)
$idmargin = $conn->query("SELECT get_idmargin_aktif() AS id")->fetch_assoc()['id'];
$barang = [];
if ($idmargin !== null) {
    $res = $conn->query(
        "SELECT ID AS idbarang, Barang AS nama, Satuan AS nama_satuan,
                Stok AS stok, Harga_Jual AS harga_jual
         FROM viewstok ORDER BY Barang"
    );
    while ($r = $res->fetch_assoc()) $barang[(int)$r['idbarang']] = $r;
}

$errors = [];
$rows   = [];   // baris keranjang yang tampil kembali kalau ada error

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $idmargin !== null) {
    csrf_check();
    $ids = $_POST['idbarang'] ?? [];
    $jml = $_POST['jumlah'] ?? [];
    $items = [];   // idbarang => jumlah (barang yang sama digabung)

    foreach ($ids as $i => $idb) {
        $idb = (int)$idb;
        $j   = (int)($jml[$i] ?? 0);
        if ($idb <= 0 && $j <= 0) continue;                      // baris kosong
        if ($idb <= 0 || $j <= 0) { $errors[] = 'Setiap baris harus memilih barang dan jumlah lebih dari 0.'; continue; }
        $items[$idb] = ($items[$idb] ?? 0) + $j;
        $rows[] = ['id' => $idb, 'jumlah' => $j];
    }
    if (!$items && !$errors) $errors[] = 'Keranjang masih kosong.';

    foreach ($items as $idb => $j) {
        if (!isset($barang[$idb])) { $errors[] = 'Ada barang yang tidak ditemukan / tidak aktif.'; continue; }
        if ($j > (int)$barang[$idb]['stok']) {
            $errors[] = "Stok {$barang[$idb]['nama']} tidak cukup (sisa {$barang[$idb]['stok']}, diminta $j).";
        }
    }
    $errors = array_values(array_unique($errors));

    if (!$errors) {
        try {
            $conn->begin_transaction();   // satu nota: semua item berhasil, atau batal semua
            $iduser = (int)$_SESSION['iduser'];

            $stmt = $conn->prepare("CALL sp_buat_penjualan(?, @idjual)");
            $stmt->bind_param('i', $iduser);
            $stmt->execute();
            $stmt->close();
            $idjual = (int)$conn->query("SELECT @idjual AS id")->fetch_assoc()['id'];

            foreach ($items as $idb => $j) {
                // procedure menghitung harga jual; trigger mengecek & mengurangi stok serta menghitung total nota
                $stmt = $conn->prepare("CALL sp_tambah_detail_penjualan(?, ?, ?)");
                $stmt->bind_param('iii', $idjual, $idb, $j);
                $stmt->execute();
                $stmt->close();
            }

            $conn->commit();
            flash('success', "Transaksi tersimpan. Nota #$idjual.");
            redirect("detail.php?id=$idjual");
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
}
if (!$rows) $rows[] = ['id' => 0, 'jumlah' => ''];

function row_html($barang, $selId, $jumlah) {
    ob_start(); ?>
    <tr>
        <td>
            <select name="idbarang[]" class="form-select form-select-sm">
                <option value="">-- Pilih barang --</option>
                <?php foreach ($barang as $b) : ?>
                    <option value="<?= (int)$b['idbarang'] ?>" data-harga="<?= (int)$b['harga_jual'] ?>"
                            <?= (int)$b['stok'] <= 0 ? 'disabled' : '' ?> <?= $selId == $b['idbarang'] ? 'selected' : '' ?>>
                        <?= h($b['nama']) ?> (<?= h($b['nama_satuan']) ?>) - <?= rupiah($b['harga_jual']) ?> - stok <?= (int)$b['stok'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td style="width:110px"><input type="number" name="jumlah[]" min="1" class="form-control form-control-sm" value="<?= h($jumlah) ?>"></td>
        <td class="sub text-end" style="width:140px">Rp 0</td>
        <td style="width:70px"><button type="button" class="btn btn-sm btn-outline-danger hapus-row">&times;</button></td>
    </tr>
    <?php return ob_get_clean();
}

include $base . 'navbar.php';
include $base . 'sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Transaksi Penjualan</h3>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">&larr; Kembali</a>
    </div>

    <?php if ($idmargin === null) : ?>
        <div class="alert alert-warning">
            Belum ada margin penjualan yang aktif, jadi harga jual belum bisa dihitung.
            Minta Apoteker mengaktifkan margin di menu <b>Atur Margin Penjualan</b>.
        </div>
    <?php else : ?>

    <?php if ($errors) : ?>
        <div class="alert alert-danger"><ul class="mb-0">
            <?php foreach ($errors as $e) : ?><li><?= h($e) ?></li><?php endforeach; ?>
        </ul></div>
    <?php endif; ?>

    <form method="post" action="">
        <?= csrf_field() ?>
        <table>
            <thead><tr><th>Barang</th><th>Jumlah</th><th class="text-end">Subtotal</th><th></th></tr></thead>
            <tbody id="rows">
                <?php foreach ($rows as $r) echo row_html($barang, $r['id'], $r['jumlah']); ?>
            </tbody>
            <tfoot>
                <tr><td colspan="2" class="text-end">Subtotal</td><td class="text-end" id="t-sub">Rp 0</td><td></td></tr>
                <tr><td colspan="2" class="text-end">PPN 11%</td><td class="text-end" id="t-ppn">Rp 0</td><td></td></tr>
                <tr><td colspan="2" class="text-end"><b>Total</b></td><td class="text-end"><b id="t-total">Rp 0</b></td><td></td></tr>
            </tfoot>
        </table>

        <div class="mt-3 d-flex gap-2">
            <button type="button" id="add" class="btn btn-outline-primary">+ Tambah barang</button>
            <button class="btn btn-success">Simpan Transaksi</button>
        </div>
        <div class="form-text mt-2">Total di layar hanya perkiraan. Harga dan total resmi dihitung database saat disimpan.</div>
    </form>

    <template id="row-tpl"><?= row_html($barang, 0, '') ?></template>

    <script>
        const tbody = document.getElementById('rows');
        const rp = n => 'Rp ' + n.toLocaleString('id-ID');

        function hitung() {
            let sub = 0;
            tbody.querySelectorAll('tr').forEach(tr => {
                const opt = tr.querySelector('select').selectedOptions[0];
                const harga = opt && opt.dataset.harga ? parseInt(opt.dataset.harga) : 0;
                const jml = parseInt(tr.querySelector('input').value) || 0;
                tr.querySelector('.sub').textContent = rp(harga * jml);
                sub += harga * jml;
            });
            const ppn = Math.round(sub * 0.11);
            document.getElementById('t-sub').textContent = rp(sub);
            document.getElementById('t-ppn').textContent = rp(ppn);
            document.getElementById('t-total').textContent = rp(sub + ppn);
        }

        document.getElementById('add').addEventListener('click', () => {
            tbody.insertAdjacentHTML('beforeend', document.getElementById('row-tpl').innerHTML);
            hitung();
        });
        tbody.addEventListener('click', e => {
            if (e.target.classList.contains('hapus-row')) {
                if (tbody.querySelectorAll('tr').length > 1) e.target.closest('tr').remove();
                hitung();
            }
        });
        tbody.addEventListener('input', hitung);
        tbody.addEventListener('change', hitung);
        hitung();
    </script>
    <?php endif; ?>
</div>
<?php include $base . 'footer.php'; ?>

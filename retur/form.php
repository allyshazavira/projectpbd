<?php
$base = '../'; $menu = 'retur';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_SUPERADMIN, ROLE_GUDANG]);
global $conn;

$idpn = (int)($_GET['penerimaan'] ?? 0);
$errors = [];

// Daftar penerimaan yang sudah selesai (retur hanya dari penerimaan selesai)
$daftar = $conn->query("SELECT ID, Tanggal, Vendor FROM viewpenerimaan WHERE Status = 'Selesai' ORDER BY ID DESC")->fetch_all(MYSQLI_ASSOC);

$items = [];
if ($idpn > 0) {
    $stmt = $conn->prepare(
        "SELECT v.*, COALESCE((SELECT SUM(dr.jumlah) FROM detail_retur dr WHERE dr.iddetail_penerimaan = v.ID_Detail), 0) AS Sudah_Retur
         FROM viewdetailpenerimaan v WHERE v.ID_Penerimaan = ? ORDER BY v.ID_Detail");
    $stmt->bind_param('i', $idpn);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $idpn > 0) {
    csrf_check();
    $jumlah = $_POST['jumlah'] ?? [];
    $alasan = $_POST['alasan'] ?? [];
    $baris = [];

    foreach ($jumlah as $iddetail => $j) {
        $j = (int)$j;
        if ($j <= 0) continue;
        $baris[] = [(int)$iddetail, $j, trim($alasan[$iddetail] ?? '')];
    }
    if (!$baris) $errors[] = 'Isi jumlah retur minimal pada satu barang.';
    foreach ($baris as $b) {
        if ($b[2] === '') { $errors[] = 'Alasan retur wajib diisi untuk setiap barang yang diretur.'; break; }
    }

    if (!$errors) {
        try {
            $conn->begin_transaction();
            $iduser = (int)$_SESSION['iduser'];

            $stmt = $conn->prepare("CALL sp_buat_retur(?, ?, @idretur)");
            $stmt->bind_param('ii', $idpn, $iduser);
            $stmt->execute();
            $stmt->close();
            $idretur = (int)$conn->query("SELECT @idretur AS id")->fetch_assoc()['id'];

            foreach ($baris as [$iddetail, $j, $alasanTeks]) {
                // trigger: cek jumlah <= diterima, stok cukup, lalu mengurangi stok (kartu stok)
                $stmt = $conn->prepare("CALL sp_tambah_detail_retur(?, ?, ?, ?)");
                $stmt->bind_param('iiis', $idretur, $iddetail, $j, $alasanTeks);
                $stmt->execute();
                $stmt->close();
            }

            $conn->commit();
            flash('success', "Retur #$idretur tersimpan. Stok sudah berkurang.");
            redirect("detail.php?id=$idretur");
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
}

include $base . 'navbar.php';
include $base . 'sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Retur Baru</h3>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">&larr; Kembali</a>
    </div>

    <?php if ($errors) : ?>
        <div class="alert alert-danger"><ul class="mb-0">
            <?php foreach ($errors as $e) : ?><li><?= h($e) ?></li><?php endforeach; ?>
        </ul></div>
    <?php endif; ?>

    <form method="get" class="row g-2 mb-3">
        <div class="col-md-6">
            <label class="form-label small mb-1">Pilih penerimaan (yang sudah selesai)</label>
            <select name="penerimaan" class="form-select" onchange="this.form.submit()" required>
                <option value="">-- Pilih penerimaan --</option>
                <?php foreach ($daftar as $d) : ?>
                    <option value="<?= (int)$d['ID'] ?>" <?= $idpn === (int)$d['ID'] ? 'selected' : '' ?>>
                        #<?= (int)$d['ID'] ?> - <?= h($d['Vendor']) ?> (<?= h($d['Tanggal']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <?php if ($idpn > 0 && !$items) : ?>
        <div class="alert alert-warning">Penerimaan tidak ditemukan.</div>
    <?php elseif ($items) : ?>
    <form method="post" action="">
        <?= csrf_field() ?>
        <table>
            <thead><tr><th>Barang</th><th>Satuan</th><th>Diterima</th><th>Sudah diretur</th><th style="width:130px">Jumlah retur</th><th>Alasan</th></tr></thead>
            <tbody>
            <?php foreach ($items as $d) : ?>
                <tr>
                    <td><?= h($d['Barang']) ?></td>
                    <td><?= h($d['Satuan']) ?></td>
                    <td><?= (int)$d['Jumlah_Terima'] ?></td>
                    <td><?= (int)$d['Sudah_Retur'] ?></td>
                    <td><input type="number" min="0" class="form-control form-control-sm" name="jumlah[<?= (int)$d['ID_Detail'] ?>]" value="0"></td>
                    <td><input type="text" maxlength="200" class="form-control form-control-sm" name="alasan[<?= (int)$d['ID_Detail'] ?>]" placeholder="mis. kemasan rusak"></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <button class="btn btn-success mt-3">Simpan Retur</button>
        <div class="form-text mt-2">Hanya barang dengan jumlah retur lebih dari 0 yang diproses.</div>
    </form>
    <?php endif; ?>
</div>
<?php include $base . 'footer.php'; ?>

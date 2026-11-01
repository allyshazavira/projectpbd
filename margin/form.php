<?php
$base = '../'; $menu = 'margin';
require_once $base . 'helpers.php';
global $conn;
require_login();
require_role([ROLE_APOTEKER]);

$id   = (int)($_GET['id'] ?? 0);
$edit = $id > 0;
$data = ['persen' => ''];

if ($edit) {
    $stmt = $conn->prepare("SELECT persen FROM margin_penjualan WHERE idmargin_penjualan = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        flash('danger', 'Data margin tidak ditemukan.');
        redirect('index.php');
    }

    // Margin yang sudah dipakai transaksi tidak boleh diubah, supaya riwayat harga tetap benar
    $cek = $conn->prepare("SELECT COUNT(*) AS c FROM penjualan WHERE idmargin_penjualan = ?");
    $cek->bind_param('i', $id);
    $cek->execute();
    if ((int)$cek->get_result()->fetch_assoc()['c'] > 0) {
        flash('warning', 'Margin ini sudah dipakai di transaksi penjualan, jadi persentasenya tidak boleh diubah. Buat margin baru saja.');
        redirect('index.php');
    }
    $data = $row;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $raw = str_replace(',', '.', trim($_POST['persen'] ?? ''));
    $data['persen'] = $raw;

    if (!is_numeric($raw))                        $errors[] = 'Persentase harus berupa angka.';
    elseif ($raw < 0 || $raw > 100)               $errors[] = 'Persentase harus antara 0 sampai 100.';

    if (!$errors) {
        $persen = (float)$raw;
        try {
            if ($edit) {
                $stmt = $conn->prepare("UPDATE margin_penjualan SET persen=? WHERE idmargin_penjualan=?");
                $stmt->bind_param('di', $persen, $id);
                $stmt->execute();
                flash('success', 'Margin berhasil diperbarui.');
            } else {
                // Memakai stored procedure: margin lama otomatis dinonaktifkan, margin baru aktif
                $iduser = (int)$_SESSION['iduser'];
                $stmt = $conn->prepare("CALL sp_set_margin(?, ?, @idmargin)");
                $stmt->bind_param('di', $persen, $iduser);
                $stmt->execute();
                $stmt->close();
                flash('success', 'Margin baru berhasil dibuat dan langsung aktif.');
            }
            redirect('index.php');
        } catch (mysqli_sql_exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}

include $base . 'navbar.php';
include $base . 'sidebar.php';
?>

<div class="container-fluid">
    <h3 class="mb-3"><?= $edit ? 'Ubah Margin' : 'Tambah Margin' ?></h3>

    <?php if ($errors) : ?>
        <div class="alert alert-danger form-card"><ul class="mb-0">
            <?php foreach ($errors as $e) : ?><li><?= h($e) ?></li><?php endforeach; ?>
        </ul></div>
    <?php endif; ?>

    <div class="card shadow-sm form-card">
        <div class="card-body">
            <form method="post" action="">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Persentase Margin (%)</label>
                    <input type="text" inputmode="decimal" name="persen" class="form-control" required value="<?= h($data['persen']) ?>">
                    <?php if (!$edit) : ?>
                        <div class="form-text">Margin baru langsung aktif dan margin yang aktif sekarang otomatis dinonaktifkan.</div>
                    <?php endif; ?>
                </div>
                <button class="btn btn-primary">Simpan</button>
                <a href="index.php" class="btn btn-outline-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>
<?php include $base . 'footer.php'; ?>

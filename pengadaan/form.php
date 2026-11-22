<?php
$base = '../'; $menu = 'pengadaan';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_SUPERADMIN, ROLE_GUDANG]);
global $conn;

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $idvendor = (int)($_POST['idvendor'] ?? 0);
    $iduser   = (int)$_SESSION['iduser'];

    if ($idvendor <= 0) {
        $errors[] = 'Vendor wajib dipilih.';
    } else {
        try {
            $stmt = $conn->prepare("CALL sp_tambah_pengadaan(?, ?, @idpengadaan)");
            $stmt->bind_param('ii', $iduser, $idvendor);
            $stmt->execute();
            $stmt->close();
            $idp = (int)$conn->query("SELECT @idpengadaan AS id")->fetch_assoc()['id'];
            flash('success', "Pengadaan #$idp dibuat. Tambahkan barang yang dipesan.");
            redirect("detail.php?id=$idp");
        } catch (mysqli_sql_exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$vendor = $conn->query("SELECT idvendor, nama_vendor FROM viewvendoraktif ORDER BY nama_vendor");

include $base . 'navbar.php';
include $base . 'sidebar.php';
?>

<div class="container-fluid">
    <h3 class="mb-3">Buat Pengadaan</h3>

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
                    <label class="form-label">Vendor</label>
                    <select name="idvendor" class="form-select" required>
                        <option value="">-- Pilih vendor --</option>
                        <?php while ($v = $vendor->fetch_assoc()) : ?>
                            <option value="<?= (int)$v['idvendor'] ?>"><?= h($v['nama_vendor']) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <div class="form-text">Barang yang dipesan ditambahkan setelah pengadaan dibuat.</div>
                </div>
                <button class="btn btn-primary">Buat Pengadaan</button>
                <a href="index.php" class="btn btn-outline-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>
<?php include $base . 'footer.php'; ?>

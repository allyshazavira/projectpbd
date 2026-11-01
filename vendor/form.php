<?php
$base = '../'; $menu = 'vendor';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_APOTEKER, ROLE_GUDANG]);

$id   = (int)($_GET['id'] ?? 0);
$edit = $id > 0;
$data = ['nama_vendor' => '', 'badan_hukum' => 'P', 'status' => '1'];

if ($edit) {
    $stmt = $conn->prepare("SELECT nama_vendor, badan_hukum, status FROM vendor WHERE idvendor = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        flash('danger', 'Data vendor tidak ditemukan.');
        redirect('index.php');
    }
    $data = $row;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $data = [
        'nama_vendor' => trim($_POST['nama_vendor'] ?? ''),
        'badan_hukum' => $_POST['badan_hukum'] ?? '',
        'status'      => $_POST['status'] ?? '',
    ];

    if ($data['nama_vendor'] === '' || mb_strlen($data['nama_vendor']) > 100) $errors[] = 'Nama vendor wajib diisi (maksimal 100 karakter).';
    if (!in_array($data['badan_hukum'], ['P', 'C'], true)) $errors[] = 'Badan hukum harus PT atau CV.';
    if (!in_array($data['status'], ['0', '1'], true)) $errors[] = 'Status tidak valid.';

    if (!$errors) {
        try {
            if ($edit) {
                $stmt = $conn->prepare("UPDATE vendor SET nama_vendor=?, badan_hukum=?, status=? WHERE idvendor=?");
                $stmt->bind_param('sssi', $data['nama_vendor'], $data['badan_hukum'], $data['status'], $id);
                $stmt->execute();
                flash('success', 'Vendor berhasil diperbarui.');
            } else {
                $newId = next_id($conn, 'vendor', 'idvendor');
                $stmt = $conn->prepare("INSERT INTO vendor (idvendor, nama_vendor, badan_hukum, status) VALUES (?,?,?,?)");
                $stmt->bind_param('isss', $newId, $data['nama_vendor'], $data['badan_hukum'], $data['status']);
                $stmt->execute();
                flash('success', 'Vendor berhasil ditambahkan.');
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
    <h3 class="mb-3"><?= $edit ? 'Ubah Vendor' : 'Tambah Vendor' ?></h3>

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
                    <label class="form-label">Nama Vendor</label>
                    <input type="text" name="nama_vendor" class="form-control" maxlength="100" required value="<?= h($data['nama_vendor']) ?>">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Badan Hukum</label>
                        <select name="badan_hukum" class="form-select">
                            <option value="P" <?= $data['badan_hukum'] === 'P' ? 'selected' : '' ?>>PT</option>
                            <option value="C" <?= $data['badan_hukum'] === 'C' ? 'selected' : '' ?>>CV</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="1" <?= (string)$data['status'] === '1' ? 'selected' : '' ?>>Aktif</option>
                            <option value="0" <?= (string)$data['status'] === '0' ? 'selected' : '' ?>>Tidak Aktif</option>
                        </select>
                    </div>
                </div>
                <button class="btn btn-primary">Simpan</button>
                <a href="index.php" class="btn btn-outline-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>
<?php include $base . 'footer.php'; ?>

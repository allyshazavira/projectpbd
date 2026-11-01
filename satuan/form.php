<?php
$base = '../'; $menu = 'satuan';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_APOTEKER, ROLE_GUDANG]);
global $conn;

$id   = (int)($_GET['id'] ?? 0);
$edit = $id > 0;
$data = ['nama_satuan' => '', 'status' => 1];

if ($edit) {
    $stmt = $conn->prepare("SELECT nama_satuan, status FROM satuan WHERE idsatuan = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        flash('danger', 'Data satuan tidak ditemukan.');
        redirect('index.php');
    }
    $data = $row;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $data = [
        'nama_satuan' => trim($_POST['nama_satuan'] ?? ''),
        'status'      => (int)($_POST['status'] ?? 1),
    ];

    if ($data['nama_satuan'] === '' || mb_strlen($data['nama_satuan']) > 45) $errors[] = 'Nama satuan wajib diisi (maksimal 45 karakter).';
    if (!in_array($data['status'], [0, 1], true)) $errors[] = 'Status tidak valid.';

    if (!$errors) {
        try {
            if ($edit) {
                $stmt = $conn->prepare("UPDATE satuan SET nama_satuan=?, status=? WHERE idsatuan=?");
                $stmt->bind_param('sii', $data['nama_satuan'], $data['status'], $id);
                $stmt->execute();
                flash('success', 'Satuan berhasil diperbarui.');
            } else {
                $newId = next_id($conn, 'satuan', 'idsatuan');
                $stmt = $conn->prepare("INSERT INTO satuan (idsatuan, nama_satuan, status) VALUES (?,?,?)");
                $stmt->bind_param('isi', $newId, $data['nama_satuan'], $data['status']);
                $stmt->execute();
                flash('success', 'Satuan berhasil ditambahkan.');
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
    <h3 class="mb-3"><?= $edit ? 'Ubah Satuan' : 'Tambah Satuan' ?></h3>

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
                    <label class="form-label">Nama Satuan</label>
                    <input type="text" name="nama_satuan" class="form-control" maxlength="45" required value="<?= h($data['nama_satuan']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="1" <?= (int)$data['status'] === 1 ? 'selected' : '' ?>>Aktif</option>
                        <option value="0" <?= (int)$data['status'] === 0 ? 'selected' : '' ?>>Tidak Aktif</option>
                    </select>
                </div>
                <button class="btn btn-primary">Simpan</button>
                <a href="index.php" class="btn btn-outline-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>
<?php include $base . 'footer.php'; ?>

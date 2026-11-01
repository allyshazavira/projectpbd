<?php
$base = '../'; $menu = 'role';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_APOTEKER]);
global $conn;

$id   = (int)($_GET['id'] ?? 0);
$edit = $id > 0;
$data = ['nama_role' => ''];

if ($edit) {
    if ($id <= 3) {   // role bawaan dipakai untuk hak akses di aplikasi
        flash('warning', 'Role bawaan sistem tidak boleh diubah.');
        redirect('index.php');
    }
    $stmt = $conn->prepare("SELECT nama_role FROM role WHERE idrole = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        flash('danger', 'Data role tidak ditemukan.');
        redirect('index.php');
    }
    $data = $row;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $data['nama_role'] = trim($_POST['nama_role'] ?? '');

    if ($data['nama_role'] === '' || mb_strlen($data['nama_role']) > 100) $errors[] = 'Nama role wajib diisi (maksimal 100 karakter).';

    if (!$errors) {
        try {
            if ($edit) {
                $stmt = $conn->prepare("UPDATE role SET nama_role=? WHERE idrole=?");
                $stmt->bind_param('si', $data['nama_role'], $id);
                $stmt->execute();
                flash('success', 'Role berhasil diperbarui.');
            } else {
                $newId = next_id($conn, 'role', 'idrole');
                $stmt = $conn->prepare("INSERT INTO role (idrole, nama_role) VALUES (?,?)");
                $stmt->bind_param('is', $newId, $data['nama_role']);
                $stmt->execute();
                flash('success', 'Role berhasil ditambahkan.');
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
    <h3 class="mb-3"><?= $edit ? 'Ubah Role' : 'Tambah Role' ?></h3>

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
                    <label class="form-label">Nama Role</label>
                    <input type="text" name="nama_role" class="form-control" maxlength="100" required value="<?= h($data['nama_role']) ?>">
                </div>
                <button class="btn btn-primary">Simpan</button>
                <a href="index.php" class="btn btn-outline-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>
<?php include $base . 'footer.php'; ?>

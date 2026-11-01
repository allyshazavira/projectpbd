<?php
$base = '../'; $menu = 'user';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_APOTEKER]);
global $conn;

$id   = (int)($_GET['id'] ?? 0);
$edit = $id > 0;
$data = ['username' => '', 'idrole' => ''];

if ($edit) {
    $stmt = $conn->prepare("SELECT username, idrole FROM user WHERE iduser = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        flash('danger', 'Data user tidak ditemukan.');
        redirect('index.php');
    }
    $data = $row;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $data = [
        'username' => trim($_POST['username'] ?? ''),
        'idrole'   => (int)($_POST['idrole'] ?? 0),
    ];
    $password = $_POST['password'] ?? '';

    if ($data['username'] === '' || mb_strlen($data['username']) > 45) $errors[] = 'Username wajib diisi (maksimal 45 karakter).';
    if ($data['idrole'] <= 0) $errors[] = 'Role wajib dipilih.';
    if (!$edit && $password === '') $errors[] = 'Password wajib diisi.';
    if ($password !== '' && mb_strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';

    if (!$errors) {
        $cek = $conn->prepare("SELECT 1 FROM user WHERE username = ? AND iduser <> ?");
        $cek->bind_param('si', $data['username'], $id);
        $cek->execute();
        if ($cek->get_result()->num_rows > 0) $errors[] = 'Username sudah dipakai user lain.';
    }

    if (!$errors) {
        try {
            if ($edit) {
                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE user SET username=?, password=?, idrole=? WHERE iduser=?");
                    $stmt->bind_param('ssii', $data['username'], $hash, $data['idrole'], $id);
                } else {   // password dikosongkan = tidak diubah
                    $stmt = $conn->prepare("UPDATE user SET username=?, idrole=? WHERE iduser=?");
                    $stmt->bind_param('sii', $data['username'], $data['idrole'], $id);
                }
                $stmt->execute();

                // Jika mengubah role akun sendiri, perbarui session
                if ($id === (int)$_SESSION['iduser']) {
                    $_SESSION['username'] = $data['username'];
                    $r = $conn->prepare("SELECT nama_role FROM role WHERE idrole = ?");
                    $r->bind_param('i', $data['idrole']);
                    $r->execute();
                    $_SESSION['role'] = $r->get_result()->fetch_assoc()['nama_role'];
                }
                flash('success', 'User berhasil diperbarui.');
            } else {
                // Format ID: lanjut dari 260501 (sama seperti trigger trg_user_bi)
                $newId = next_id($conn, 'user', 'iduser', 260500);
                $hash  = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO user (iduser, username, password, idrole) VALUES (?,?,?,?)");
                $stmt->bind_param('issi', $newId, $data['username'], $hash, $data['idrole']);
                $stmt->execute();
                flash('success', "User berhasil ditambahkan dengan ID $newId.");
            }
            redirect('index.php');
        } catch (mysqli_sql_exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$roles = $conn->query("SELECT idrole, nama_role FROM role ORDER BY idrole");

include $base . 'navbar.php';
include $base . 'sidebar.php';
?>

<div class="container-fluid">
    <h3 class="mb-3"><?= $edit ? 'Ubah User' : 'Tambah User' ?></h3>

    <?php if ($errors) : ?>
        <div class="alert alert-danger form-card"><ul class="mb-0">
            <?php foreach ($errors as $e) : ?><li><?= h($e) ?></li><?php endforeach; ?>
        </ul></div>
    <?php endif; ?>

    <div class="card shadow-sm form-card">
        <div class="card-body">
            <form method="post" action="" autocomplete="off">
                <?= csrf_field() ?>
                <?php if ($edit) : ?>
                    <div class="mb-3">
                        <label class="form-label">ID User</label>
                        <input type="text" class="form-control" value="<?= $id ?>" disabled>
                    </div>
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" maxlength="45" required value="<?= h($data['username']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" minlength="6" <?= $edit ? '' : 'required' ?>>
                    <?php if ($edit) : ?><div class="form-text">Kosongkan jika tidak ingin mengubah password.</div><?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label">Role</label>
                    <select name="idrole" class="form-select" required>
                        <option value="">-- Pilih role --</option>
                        <?php while ($r = $roles->fetch_assoc()) : ?>
                            <option value="<?= (int)$r['idrole'] ?>" <?= (int)$data['idrole'] === (int)$r['idrole'] ? 'selected' : '' ?>>
                                <?= h($r['nama_role']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button class="btn btn-primary">Simpan</button>
                <a href="index.php" class="btn btn-outline-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>
<?php include $base . 'footer.php'; ?>

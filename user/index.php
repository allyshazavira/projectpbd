<?php
$base = '../'; $menu = 'user';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_APOTEKER]);
global $conn;

$result = $conn->query("SELECT * FROM viewuser ORDER BY ID");

include $base . 'navbar.php';
include $base . 'sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Data Pengguna</h3>
        <a href="form.php" class="btn btn-primary btn-sm">+ Tambah User</a>
    </div>

    <?php show_flash(); ?>

    <table>
        <thead>
            <tr><th>ID User</th><th>Username</th><th>Role / Hak Akses</th><th style="width:160px">Aksi</th></tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()) : ?>
            <?php $self = (int)$row['ID'] === (int)$_SESSION['iduser']; ?>
            <tr>
                <td><?= h($row['ID']) ?></td>
                <td><?= h($row['Username']) ?> <?= $self ? '<span class="badge bg-primary">Anda</span>' : '' ?></td>
                <td><?= h($row['Role']) ?></td>
                <td>
                    <a href="form.php?id=<?= (int)$row['ID'] ?>" class="btn btn-sm btn-outline-warning">Ubah</a>
                    <?php if (!$self) : ?>
                    <form action="hapus.php" method="post" class="d-inline"
                          onsubmit="return confirm('Hapus user ini?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$row['ID'] ?>">
                        <button class="btn btn-sm btn-outline-danger">Hapus</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php include $base . 'footer.php'; ?>

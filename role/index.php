<?php
$base = '../'; $menu = 'role';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_APOTEKER]);
global $conn;

$result = $conn->query("SELECT * FROM viewrole ORDER BY ID");

include $base . 'navbar.php';
include $base . 'sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Data Role</h3>
        <a href="form.php" class="btn btn-primary btn-sm">+ Tambah Role</a>
    </div>

    <?php show_flash(); ?>

    <table>
        <thead>
            <tr><th>ID</th><th>Nama Role</th><th style="width:160px">Aksi</th></tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()) : ?>
            <?php $bawaan = (int)$row['ID'] <= 3; ?>
            <tr>
                <td><?= h($row['ID']) ?></td>
                <td><?= h($row['Nama Role']) ?> <?= $bawaan ? '<span class="badge bg-info">Bawaan sistem</span>' : '' ?></td>
                <td>
                    <?php if ($bawaan) : ?>
                        <span class="text-muted small">Tidak bisa diubah</span>
                    <?php else : ?>
                        <a href="form.php?id=<?= (int)$row['ID'] ?>" class="btn btn-sm btn-outline-warning">Ubah</a>
                        <form action="hapus.php" method="post" class="d-inline"
                              onsubmit="return confirm('Hapus role ini?')">
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

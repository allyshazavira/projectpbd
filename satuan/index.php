<?php
$base = '../'; $menu = 'satuan';
require_once $base . 'helpers.php';
require_login();
global $conn;

$canEdit = has_role([ROLE_APOTEKER, ROLE_GUDANG]);
$result = $conn->query("SELECT * FROM viewsatuan ORDER BY ID");

include $base . 'navbar.php';
include $base . 'sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Data Satuan</h3>
        <?php if ($canEdit) : ?>
            <a href="form.php" class="btn btn-primary btn-sm">+ Tambah Satuan</a>
        <?php endif; ?>
    </div>

    <?php show_flash(); ?>

    <table>
        <thead>
            <tr>
                <th>ID</th><th>Satuan</th><th>Status</th>
                <?php if ($canEdit) : ?><th style="width:160px">Aksi</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows === 0) : ?>
            <tr><td colspan="4" class="text-center text-muted">Belum ada data satuan.</td></tr>
        <?php endif; ?>
        <?php while ($row = $result->fetch_assoc()) : ?>
            <tr>
                <td><?= h($row['ID']) ?></td>
                <td><?= h($row['SATUAN']) ?></td>
                <td><?= badge_status($row['STATUS']) ?></td>
                <?php if ($canEdit) : ?>
                <td>
                    <a href="form.php?id=<?= (int)$row['ID'] ?>" class="btn btn-sm btn-outline-warning">Ubah</a>
                    <form action="hapus.php" method="post" class="d-inline"
                          onsubmit="return confirm('Hapus satuan ini?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$row['ID'] ?>">
                        <button class="btn btn-sm btn-outline-danger">Hapus</button>
                    </form>
                </td>
                <?php endif; ?>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php include $base . 'footer.php'; ?>

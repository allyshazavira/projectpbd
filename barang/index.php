<?php
$base = '../'; $menu = 'barang';
require_once $base . 'helpers.php';
require_login();

$canEdit = has_role([ROLE_APOTEKER, ROLE_GUDANG]);
$q = trim($_GET['q'] ?? '');

if ($q !== '') {
    $stmt = $conn->prepare("SELECT * FROM viewbarang WHERE nama_barang LIKE ? ORDER BY ID");
    $like = "%$q%";
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM viewbarang ORDER BY ID");
}

include $base . 'navbar.php';
include $base . 'sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Data Barang</h3>
        <?php if ($canEdit) : ?>
            <a href="form.php" class="btn btn-primary btn-sm">+ Tambah Barang</a>
        <?php endif; ?>
    </div>

    <?php show_flash(); ?>

    <form method="get" class="row g-2 mb-2">
        <div class="col-md-5">
            <input type="text" name="q" class="form-control" placeholder="Cari nama barang..." value="<?= h($q) ?>">
        </div>
        <div class="col-auto">
            <button class="btn btn-outline-primary">Cari</button>
            <?php if ($q !== '') : ?><a href="index.php" class="btn btn-outline-secondary">Reset</a><?php endif; ?>
        </div>
    </form>

    <table>
        <thead>
            <tr>
                <th>ID</th><th>Nama Barang</th><th>Kategori</th><th>Satuan</th>
                <th>Harga Dasar</th><th>Status</th>
                <?php if ($canEdit) : ?><th style="width:160px">Aksi</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows === 0) : ?>
            <tr><td colspan="7" class="text-center text-muted">Belum ada data barang.</td></tr>
        <?php endif; ?>
        <?php while ($row = $result->fetch_assoc()) : ?>
            <tr>
                <td><?= h($row['ID']) ?></td>
                <td><?= h($row['nama_barang']) ?></td>
                <td><?= h($row['Kategori']) ?></td>
                <td><?= h($row['Satuan']) ?></td>
                <td><?= rupiah($row['Harga']) ?></td>
                <td><?= badge_status($row['Status']) ?></td>
                <?php if ($canEdit) : ?>
                <td>
                    <a href="form.php?id=<?= (int)$row['ID'] ?>" class="btn btn-sm btn-outline-warning">Ubah</a>
                    <form action="hapus.php" method="post" class="d-inline"
                          onsubmit="return confirm('Hapus barang ini?')">
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

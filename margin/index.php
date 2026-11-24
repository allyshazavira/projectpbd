<?php
$base = '../'; $menu = 'margin';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_SUPERADMIN]);
global $conn;

$status = $_GET['status'] ?? 'Aktif';
$q = trim($_GET['q'] ?? '');

if ($status == 'Aktif') {
    $query = "SELECT * FROM viewmarginaktif";
} else {
    $query = "SELECT * FROM viewmargin";
}

if ($q !== '') {
    $query .= " WHERE persen LIKE ?";
}

$query .= " ORDER BY ID ASC";

$stmt = $conn->prepare($query);
if ($q !== '') {
    $like = "%$q%";
    $stmt->bind_param('s', $like);
}

$stmt->execute();
$result = $stmt->get_result();

include $base . 'navbar.php';
include $base . 'sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Margin Penjualan</h3>
        <a href="form.php" class="btn btn-primary btn-sm">+ Tambah Margin</a>
    </div>
<form method="GET" style="margin: 15px 0;">

        <label>Filter Status:</label>

        <select name="status" onchange="this.form.submit()">

            <option value="Semua"
                <?= $status == 'Semua' ? 'selected' : ''; ?>>
                Semua Status
            </option>

            <option value="Aktif"
                <?= $status == 'Aktif' ? 'selected' : ''; ?>>
                Aktif
            </option>
        </select>

    </form>
    <?php show_flash(); ?>

    <div class="alert alert-info py-2">
        Hanya satu margin yang boleh aktif. Harga jual = harga dasar barang + margin aktif.
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th><th>Persentase</th><th>Status</th><th>Dibuat oleh</th>
                <th>Tanggal dibuat</th><th>Tanggal diubah</th><th style="width:230px">Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows === 0) : ?>
            <tr><td colspan="7" class="text-center text-muted">Belum ada margin. Tambahkan margin pertama.</td></tr>
        <?php endif; ?>
        <?php while ($row = $result->fetch_assoc()) : ?>
            <?php $aktif = strtolower($row['Status']) === 'aktif'; ?>
            <tr class="<?= $aktif ? 'row-aktif' : '' ?>">
                <td><?= h($row['ID']) ?></td>
                <td><?= h(rtrim(rtrim(number_format($row['PERSENTASE'], 2, '.', ''), '0'), '.')) ?>%</td>
                <td><?= badge_status($row['Status']) ?></td>
                <td><?= h($row['dibuat oleh']) ?></td>
                <td><?= h($row['Tanggal dibuat']) ?></td>
                <td><?= h($row['Tanggal diubah']) ?></td>
                <td>
                    <?php if (!$aktif) : ?>
                    <form action="aktifkan.php" method="post" class="d-inline"
                          onsubmit="return confirm('Aktifkan margin ini? Margin yang aktif sekarang akan dinonaktifkan.')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$row['ID'] ?>">
                        <button class="btn btn-sm btn-outline-success">Aktifkan</button>
                    </form>
                    <?php endif; ?>
                    <a href="form.php?id=<?= (int)$row['ID'] ?>" class="btn btn-sm btn-outline-warning">Ubah</a>
                    <?php if (!$aktif) : ?>
                    <form action="hapus.php" method="post" class="d-inline"
                          onsubmit="return confirm('Hapus margin ini?')">
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

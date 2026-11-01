<?php
$base = '../'; $menu = 'barang';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_APOTEKER, ROLE_GUDANG]);

$id   = (int)($_GET['id'] ?? 0);
$edit = $id > 0;
$data = ['jenis' => 'O', 'nama' => '', 'idsatuan' => '', 'status' => 1, 'harga' => ''];

if ($edit) {
    $stmt = $conn->prepare("SELECT jenis, nama, idsatuan, status, harga FROM barang WHERE idbarang = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        flash('danger', 'Data barang tidak ditemukan.');
        redirect('index.php');
    }
    $data = $row;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $data = [
        'jenis'    => $_POST['jenis'] ?? '',
        'nama'     => trim($_POST['nama'] ?? ''),
        'idsatuan' => (int)($_POST['idsatuan'] ?? 0),
        'status'   => (int)($_POST['status'] ?? 1),
        'harga'    => (int)($_POST['harga'] ?? 0),
    ];

    if (!in_array($data['jenis'], ['O', 'A'], true)) $errors[] = 'Kategori harus Obat atau Alat Kesehatan.';
    if ($data['nama'] === '' || mb_strlen($data['nama']) > 45) $errors[] = 'Nama barang wajib diisi (maksimal 45 karakter).';
    if ($data['idsatuan'] <= 0) $errors[] = 'Satuan wajib dipilih.';
    if (!in_array($data['status'], [0, 1], true)) $errors[] = 'Status tidak valid.';
    if ($data['harga'] <= 0) $errors[] = 'Harga dasar harus lebih dari 0.';

    if (!$errors) {
        try {
            if ($edit) {
                $stmt = $conn->prepare("UPDATE barang SET jenis=?, nama=?, idsatuan=?, status=?, harga=? WHERE idbarang=?");
                $stmt->bind_param('ssiiii', $data['jenis'], $data['nama'], $data['idsatuan'], $data['status'], $data['harga'], $id);
                $stmt->execute();
                flash('success', 'Barang berhasil diperbarui.');
            } else {
                $newId = next_id($conn, 'barang', 'idbarang');
                $stmt = $conn->prepare("INSERT INTO barang (idbarang, jenis, nama, idsatuan, status, harga) VALUES (?,?,?,?,?,?)");
                $stmt->bind_param('issiii', $newId, $data['jenis'], $data['nama'], $data['idsatuan'], $data['status'], $data['harga']);
                $stmt->execute();
                flash('success', 'Barang berhasil ditambahkan.');
            }
            redirect('index.php');
        } catch (mysqli_sql_exception $e) {
            $errors[] = $e->getMessage();   // termasuk pesan dari trigger validasi
        }
    }
}

$satuan = $conn->query("SELECT idsatuan, nama_satuan, status FROM satuan ORDER BY nama_satuan");

include $base . 'navbar.php';
include $base . 'sidebar.php';
?>

<div class="container-fluid">
    <h3 class="mb-3"><?= $edit ? 'Ubah Barang' : 'Tambah Barang' ?></h3>

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
                    <label class="form-label">Nama Barang</label>
                    <input type="text" name="nama" class="form-control" maxlength="45" required value="<?= h($data['nama']) ?>">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kategori</label>
                        <select name="jenis" class="form-select">
                            <option value="O" <?= $data['jenis'] === 'O' ? 'selected' : '' ?>>Obat</option>
                            <option value="A" <?= $data['jenis'] === 'A' ? 'selected' : '' ?>>Alat Kesehatan</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Satuan</label>
                        <select name="idsatuan" class="form-select" required>
                            <option value="">-- Pilih satuan --</option>
                            <?php while ($s = $satuan->fetch_assoc()) : ?>
                                <option value="<?= (int)$s['idsatuan'] ?>" <?= (int)$data['idsatuan'] === (int)$s['idsatuan'] ? 'selected' : '' ?>>
                                    <?= h($s['nama_satuan']) ?><?= $s['status'] ? '' : ' (tidak aktif)' ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Harga Dasar (Rp)</label>
                        <input type="number" name="harga" class="form-control" min="1" required value="<?= h($data['harga']) ?>">
                        <div class="form-text">Harga jual dihitung otomatis dari harga dasar + margin aktif.</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="1" <?= (int)$data['status'] === 1 ? 'selected' : '' ?>>Aktif</option>
                            <option value="0" <?= (int)$data['status'] === 0 ? 'selected' : '' ?>>Tidak Aktif</option>
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

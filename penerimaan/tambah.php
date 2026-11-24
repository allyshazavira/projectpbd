<?php
$base = '../'; $menu = 'penerimaan';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_SUPERADMIN, ROLE_GUDANG]);
global $conn;

$pengadaan = $conn->query("
    SELECT ID, Tanggal, Vendor, Sisa_Barang
    FROM viewpengadaan
    WHERE Status = 'Diproses'
      AND Sisa_Barang > 0
    ORDER BY ID DESC
");

include $base . 'navbar.php';
include $base . 'sidebar.php';
?>

<div class="container-fluid">
    <h3 class="mb-3">Tambah Penerimaan Barang</h3>

    <?php show_flash(); ?>

    <div class="card shadow-sm form-card">
        <div class="card-body">
            <form method="post" action="../pengadaan/terima.php">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label">Pilih Pengadaan</label>
                    <select name="id" class="form-select" required>
                        <option value="">-- Pilih Pengadaan --</option>

                        <?php while ($p = $pengadaan->fetch_assoc()) : ?>
                            <option value="<?= (int)$p['ID'] ?>">
                                #<?= (int)$p['ID'] ?>
                                - <?= h($p['Vendor']) ?>
                                (Sisa: <?= (int)$p['Sisa_Barang'] ?> barang)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    Buat Penerimaan
                </button>

                <a href="index.php"
                   class="btn btn-outline-secondary">
                    Batal
                </a>
            </form>
        </div>
    </div>
</div>

<?php include $base . 'footer.php'; ?>
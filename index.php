<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: tampilan_login.php");
    exit();
}

include 'DBconnection.php';
include 'navbar.php';
include 'sidebar.php';
?>

<div class="container-fluid">
    <div class="p-4 mb-4 bg-light rounded-3 shadow-sm">
        <div class="container-fluid py-2">
            <h1 class="display-5 fw-bold text-dark">Selamat Datang, <?= htmlspecialchars($_SESSION['username']); ?>! </h1>
            <p class="col-md-8 fs-4 text-muted">Anda masuk sebagai <span class="badge bg-primary"><?= htmlspecialchars($_SESSION['role']); ?></span> di Sistem Informasi Apotek.</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title text-success">Data Barang</h5>
                    <p class="card-text text-muted">Kelola daftar obat, jenis, satuan, dan harga barang di apotek.</p>
                    <a href="barang.php" class="btn btn-outline-success btn-sm">Kelola Barang</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title text-info">Data Vendor</h5>
                    <p class="card-text text-muted">Lihat dan atur daftar supplier atau distributor obat.</p>
                    <a href="vendor.php" class="btn btn-outline-info btn-sm">Kelola Vendor</a>
                </div>
            </div>
        </div>

        <?php if ($_SESSION['role'] == 'Apoteker Penanggung Jawab') : ?>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 border-warning">
                <div class="card-body">
                    <h5 class="card-title text-warning">Kelola User (Admin)</h5>
                    <p class="card-text text-muted">Tambah atau atur hak akses akun pegawai apotek.</p>
                    <a href="user.php" class="btn btn-warning btn-sm text-white">Kelola User</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php 
include 'footer.php'; 
?>
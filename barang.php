<?php
include 'DBconnection.php';

// Panggil header dan sidebar modular
include 'navbar.php';
include 'sidebar.php';

// Ambil data barang dari database
$query = "SELECT * FROM viewbarang";
$result = mysqli_query($conn, $query);
?>

<!-- WRAPPER KONTEN UTAMA -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2> Data Barang Apotek</h2>
    <a href="index.php" style="color: #555; text-decoration: none; font-size: 14px;">&larr; Kembali ke Beranda</a>
</div>

<table>
    <thead>
        <tr>
            <th>ID Barang</th>
            <th>Jenis</th>
            <th>Nama Barang</th>
            <th>Satuan</th>
            <th>Harga</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = mysqli_fetch_assoc($result)) : ?>
        <tr>
            <td><?= $row['idbarang']; ?></td>
            <td><?= $row['jenis']; ?></td>
            <td><?= htmlspecialchars($row['nama_barang']); ?></td>
            <td><?= $row['nama_satuan']; ?></td>
            <td>Rp <?= number_format($row['harga'], 0, ',', '.'); ?></td>
            <td>
                <?= ($row['status'] == 1) ? '<span style="color:green; font-weight:bold;">Aktif</span>' : '<span style="color:red; font-weight:bold;">Non-Aktif</span>'; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php 
// Tutup dengan footer
include 'footer.php'; 
?>
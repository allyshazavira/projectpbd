<?php
session_start();
include 'DBconnection.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Apoteker Penanggung Jawab') {
    header("Location: index.php?pesan=akses_ditolak");
    exit();
}

include 'sidebar.php';

$query = "SELECT * FROM viewuser";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data User - Apotek</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background: #f8f9fa; }
        table { width: 100%; border-collapse: collapse; background: #fff; margin-top: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        th, td { padding: 10px 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #17a2b8; color: white; }
    </style>
</head>
<body>

    <h2>👤 Data Pengguna / User Sistem</h2>
    <a href="index.php" style="color: #555; text-decoration: none;">&larr; Kembali ke Beranda</a>

    <table>
        <thead>
            <tr>
                <th>ID User</th>
                <th>Username</th>
                <th>Role / Hak Akses</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)) : ?>
            <tr>
                <td><?= $row['iduser']; ?></td>
                <td><?= htmlspecialchars($row['username']); ?></td>
                <td><?= htmlspecialchars($row['nama_role']); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

</body>
</html>
<?php include 'footer.php'; ?>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Sistem Informasi Apotek</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; background-color: #f4f7f6; display: flex; }
        
        /* Navbar Atas */
        .navbar { position: fixed; top: 0; left: 0; width: 100%; height: 50px; background: #00459e; color: white; display: flex; align-items: center; justify-content: space-between; padding: 0 20px; box-sizing: border-box; z-index: 1000; }
        .navbar .brand { font-weight: bold; font-size: 18px; color: #ffc107; text-decoration: none; }
        
        /* Tombol untuk Klik / Toggle Sidebar */
        .toggle-btn { background: none; border: none; color: white; font-size: 20px; cursor: pointer; }
        .navbar .user-info { font-size: 14px; margin-right: 20px; }

        /* Sidebar Kiri (Default Terbuka / Bisa Diatur Tertutup) */
        .sidebar { width: 240px; height: 100vh; background: #00459e; position: fixed; top: 50px; left: 0; padding-top: 20px; box-sizing: border-box; transition: 0.3s ease; z-index: 999; }
        .sidebar a { display: block; color: #e6f2ff; padding: 12px 20px; text-decoration: none; transition: 0.2s; font-size: 15px; }
        .sidebar a:hover, .sidebar a.active { background: #007bff; color: white; }

        /* Konten Utama */
        .main-content { margin-left: 240px; margin-top: 50px; padding: 30px; width: calc(100% - 240px); box-sizing: border-box; transition: 0.3s ease; }
        
        /* Kelas Tambahan saat Sidebar Disembunyikan */
        body.sidebar-hidden .sidebar { left: -240px; }
        body.sidebar-hidden .main-content { margin-left: 0; width: 100%; }

        /* Tabel & Komponen Umum */
        table { width: 100%; border-collapse: collapse; background: #fff; margin-top: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-radius: 6px; overflow: hidden; }
        th, td { padding: 12px 15px; border-bottom: 1px solid #dee2e6; text-align: left; }
        th { background-color: #f8f9fa; color: #333; font-weight: bold; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark px-3" style="background-color: #00459e;">
    <div class="container-fluid">
        <!-- Tombol Toggle Sidebar / Brand -->
        <div class="d-flex align-items-center">
            <button class="btn btn-dark me-2" onclick="toggleSidebar() "style="background-color: #00459e">☰</button>
            <a class="navbar-brand" href="index.php">Sistem Manajemen Apotek</a>
        </div>

        <!-- Bagian Kanan Navbar (Dinamis sesuai Session) -->
        <div class="d-flex align-items-center">
            <span class="text-white">
                Halo, <b><?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User'; ?></b> 
            </span>
        </div>
    </div>
</nav>
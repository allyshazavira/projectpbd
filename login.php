<?php
session_start();
include 'DBconnection.php';

if (isset($_POST['username']) && isset($_POST['password'])) {
    
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = "SELECT u.*, r.nama_role FROM user u 
              JOIN role r ON u.idrole = r.idrole 
              WHERE u.username = '$username'";
    $result = mysqli_query($conn, $query);
    $user = mysqli_fetch_assoc($result);

    if ($user && $password == $user['password']) {
        $_SESSION['iduser'] = $user['iduser'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['nama_role'];

        header("Location: index.php");
        exit();
    } else {
        echo "<script>alert('Login gagal! Username atau password salah.'); window.location='tampilan_login.php';</script>";
    }
} else {
    header("Location: tampilan_login.php");
    exit();
}
?>
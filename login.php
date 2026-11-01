<?php
require_once 'helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['username'], $_POST['password'])) {
    redirect('tampilan_login.php');
}

$username = trim($_POST['username']);
$password = $_POST['password'];

// Prepared statement: mencegah SQL injection
$stmt = $conn->prepare(
    "SELECT u.iduser, u.username, u.password, r.nama_role
     FROM user u JOIN role r ON u.idrole = r.idrole
     WHERE u.username = ?"
);
$stmt->bind_param('s', $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$valid = false;
if ($user) {
    // password_verify: password yang dibuat lewat CRUD user (sudah di-hash)
    // hash_equals   : password lama yang masih teks biasa (data awal di DML)
    $valid = password_verify($password, $user['password'])
          || hash_equals($user['password'], $password);
}

if ($valid) {
    session_regenerate_id(true);
    $_SESSION['iduser']   = $user['iduser'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role']     = $user['nama_role'];
    redirect('index.php');
}

echo "<script>alert('Login gagal! Username atau password salah.'); window.location='tampilan_login.php';</script>";

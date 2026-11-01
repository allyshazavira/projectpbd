<?php
// helpers.php - dipakai oleh semua halaman CRUD.
// Isi: session, koneksi database, cek login/role, flash message, CSRF, generator ID.
include 'DBconnection.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Supaya error MySQL (termasuk pesan SIGNAL dari trigger) bisa ditangkap dengan try/catch
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/DBconnection.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
    foreach (['koneksi', 'connection', 'db', 'mysqli', 'link'] as $nama) {
        if (isset($$nama) && $$nama instanceof mysqli) { $conn = $$nama; break; }
    }
}   // harus menghasilkan variabel $conn (mysqli)
$conn->set_charset('utf8mb4');

define('ROLE_APOTEKER', 'Apoteker Penanggung Jawab');
define('ROLE_KASIR',    'Asisten Apoteker / Kasir');
define('ROLE_GUDANG',   'Petugas Gudang Obat');

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit();
}

/* ---------- Flash message ---------- */
function flash($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function show_flash() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="alert alert-' . h($f['type']) . '">' . h($f['msg']) . '</div>';
    }
}

/* ---------- Login & hak akses ---------- */
function require_login() {
    global $base;
    if (empty($_SESSION['iduser'])) {
        redirect(($base ?? '') . 'tampilan_login.php');
    }
}

function has_role(array $roles) {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], $roles, true);
}

function require_role(array $roles) {
    global $base;
    if (!has_role($roles)) {
        flash('danger', 'Anda tidak memiliki akses ke halaman tersebut.');
        redirect(($base ?? '') . 'index.php');
    }
}

/* ---------- CSRF ---------- */
function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

function csrf_check() {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Token tidak valid. Muat ulang halaman lalu coba lagi.');
    }
}

/* ---------- ID otomatis (PK tabel tidak AUTO_INCREMENT) ---------- */
function next_id(mysqli $conn, $table, $col, $min = 0) {
    $r = $conn->query("SELECT COALESCE(MAX($col), $min) + 1 AS n FROM $table")->fetch_assoc();
    return (int)$r['n'];
}

/* ---------- Tampilan ---------- */
function badge_status($text) {
    $aktif = strtolower((string)$text) === 'aktif';
    return '<span class="badge bg-' . ($aktif ? 'success' : 'secondary') . '">' . h($text) . '</span>';
}

function rupiah($n) {
    return 'Rp ' . number_format((float)$n, 0, ',', '.');
}

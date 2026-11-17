<?php
$base = '../';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_APOTEKER, ROLE_GUDANG]);
global $conn;

$idp = (int)($_POST['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $iduser = (int)$_SESSION['iduser'];

    try {
        // Membuat penerimaan berstatus DIPROSES: jumlah -> jumlah_terima, harga -> harga_satuan_terima
        $stmt = $conn->prepare("CALL sp_terima_pengadaan(?, ?, @idpenerimaan)");
        $stmt->bind_param('ii', $idp, $iduser);
        $stmt->execute();
        $stmt->close();
        $idpn = (int)$conn->query("SELECT @idpenerimaan AS id")->fetch_assoc()['id'];
        flash('success', "Penerimaan #$idpn dibuat. Periksa harga terima, lalu selesaikan penerimaan.");
        redirect("../penerimaan/detail.php?id=$idpn");
    } catch (mysqli_sql_exception $e) {
        flash('danger', $e->getMessage());
    }
}
redirect("detail.php?id=$idp");

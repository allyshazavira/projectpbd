<?php
$base = '../';
require_once $base . 'helpers.php';
require_login();
require_role([ROLE_SUPERADMIN, ROLE_GUDANG]);
global $conn;

$id = (int)($_POST['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $id <= 0) {
    flash('danger', 'Permintaan penerimaan tidak valid.');
    redirect('index.php');
}

csrf_check();
$jumlah = $_POST['jumlah'] ?? [];
$harga  = $_POST['harga'] ?? [];

try {
    $conn->begin_transaction();

    // Pastikan penerimaan masih bisa diedit.
    $cek = $conn->prepare("SELECT status FROM penerimaan WHERE idpenerimaan = ? FOR UPDATE");
    $cek->bind_param('i', $id);
    $cek->execute();
    $penerimaan = $cek->get_result()->fetch_assoc();
    $cek->close();

    if (!$penerimaan) {
        throw new Exception('Penerimaan tidak ditemukan.');
    }
    if ($penerimaan['status'] !== 'P') {
        throw new Exception('Penerimaan sudah selesai dan tidak bisa diubah.');
    }

    // Simpan jumlah dan harga setiap barang yang diterima.
    foreach ($harga as $iddetail => $h) {
        if (!isset($jumlah[$iddetail])) {
            continue;
        }

        $iddetail = (int)$iddetail;
        $j = (int)$jumlah[$iddetail];
        $h = (int)$h;

        if ($iddetail <= 0 || $j < 0 || $h <= 0) {
            throw new Exception('Jumlah atau harga penerimaan tidak valid.');
        }

        // Pastikan detail yang dikirim memang milik penerimaan ini.
        $cekDetail = $conn->prepare("
            SELECT COUNT(*) AS total
            FROM detail_penerimaan
            WHERE iddetail_penerimaan = ? AND idpenerimaan = ?
        ");
        $cekDetail->bind_param('ii', $iddetail, $id);
        $cekDetail->execute();
        $valid = (int)$cekDetail->get_result()->fetch_assoc()['total'];
        $cekDetail->close();

        if ($valid === 0) {
            throw new Exception('Detail barang tidak sesuai dengan penerimaan ini.');
        }

        // Trigger database memvalidasi batas sisa dan menghitung subtotal.
        $stmt = $conn->prepare("CALL sp_ubah_detail_penerimaan(?, ?, ?)");
        $stmt->bind_param('iii', $iddetail, $j, $h);
        $stmt->execute();
        $stmt->close();
    }

    // Satu kali simpan langsung menyelesaikan penerimaan.
    // Trigger akan mencatat stok dan memperbarui harga barang.
    $stmt = $conn->prepare("CALL sp_selesaikan_penerimaan(?)");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    $q = $conn->prepare("
        SELECT p.Sisa_Barang, p.Status
        FROM viewpenerimaan r
        JOIN viewpengadaan p ON p.ID = r.ID_Pengadaan
        WHERE r.ID = ?
    ");
    $q->bind_param('i', $id);
    $q->execute();
    $st = $q->get_result()->fetch_assoc();
    $q->close();

    if ($st && (int)$st['Sisa_Barang'] === 0) {
        flash('success', 'Penerimaan berhasil disimpan. Semua barang pada pengadaan sudah diterima.');
    } else {
        flash('success', 'Penerimaan berhasil disimpan. Sisa ' . (int)($st['Sisa_Barang'] ?? 0) . ' barang dapat diterima pada penerimaan berikutnya.');
    }
} catch (Throwable $e) {
    $conn->rollback();
    flash('danger', $e->getMessage());
}

redirect("detail.php?id=$id");

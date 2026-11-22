<?php
require_once 'config.php';
requireMasuk();
$conn = getConnection();
$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $hotel_id = (int)$_POST['hotel_id'];
    $nama     = trim($_POST['nama_kamar']);
    $harga    = (float)$_POST['harga_per_malam'];
    $kap      = max(1, (int)$_POST['kapasitas']);
    $stok     = max(1, (int)$_POST['stok_kamar']);
    $fas      = trim($_POST['fasilitas_kamar'] ?? '');
    $extraBed = (int)($_POST['extra_bed_tersedia'] ?? 1) === 1 ? 1 : 0;
    $extraPrice = max(0, (float)($_POST['harga_extra_bed'] ?? 150000));

    $stmt = $conn->prepare("INSERT INTO kamar (hotel_id, nama_kamar, harga_per_malam, kapasitas, stok_kamar, fasilitas_kamar, extra_bed_tersedia, harga_extra_bed) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isdiisid", $hotel_id, $nama, $harga, $kap, $stok, $fas, $extraBed, $extraPrice);
    if ($stmt->execute()) setSuccess("Tipe kamar berhasil ditambahkan.");
    header("Location: admin-paket.php");
    exit;
}

if ($action === 'delete') {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM kamar WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) setSuccess("Tipe kamar berhasil dihapus.");
    header("Location: admin-paket.php");
    exit;
}
?>

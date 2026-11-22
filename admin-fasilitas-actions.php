<?php
require_once 'config.php';
requireMasuk();
$conn = getConnection();
$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $hotel_id = (int)$_POST['hotel_id'];
    $nama     = trim($_POST['nama_fasilitas']);
    $kat      = $_POST['kategori'];
    $harga    = (float)$_POST['harga_fasilitas'];
    $satuan   = trim($_POST['satuan'] ?? 'per orang');
    $jam      = trim($_POST['jam_operasional'] ?? '07:00 - 21:00 WITA');
    $desk     = trim($_POST['deskripsi'] ?? '');

    $stmt = $conn->prepare("INSERT INTO fasilitas (hotel_id, nama_fasilitas, kategori, harga_fasilitas, satuan, jam_operasional, deskripsi) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issdsss", $hotel_id, $nama, $kat, $harga, $satuan, $jam, $desk);
    if ($stmt->execute()) setSuccess("Fasilitas berhasil ditambahkan.");
    else setError("Gagal menyimpan fasilitas.");
    header("Location: admin-fasilitas.php");
    exit;
}

if ($action === 'delete') {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM fasilitas WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) setSuccess("Fasilitas berhasil dihapus.");
    header("Location: admin-fasilitas.php");
    exit;
}
?>

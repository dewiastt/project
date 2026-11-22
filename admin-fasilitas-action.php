<?php
// www/admin-fasilitas-actions.php
require_once 'config.php';
requireMasuk();
$conn = getConnection();

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $hotel_id = (int)$_POST['hotel_id'];
    $nama     = trim($_POST['nama_fasilitas']);
    $kat      = $_POST['kategori'];
    $harga    = (float)$_POST['harga_daypass'];
    $satuan   = trim($_POST['satuan'] ?? 'per orang');
    $jam      = trim($_POST['jam_operasional'] ?? '07:00 - 20:00 WITA');
    $mandiri  = isset($_POST['bisa_tanpa_kamar']) ? 1 : 0;
    $desk     = trim($_POST['deskripsi'] ?? '');

    $stmt = $conn->prepare("INSERT INTO fasilitas (hotel_id, nama_fasilitas, kategori, harga_daypass, satuan, jam_operasional, bisa_tanpa_kamar, deskripsi) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issdssis", $hotel_id, $nama, $kat, $harga, $satuan, $jam, $mandiri, $desk);
    if ($stmt->execute()) setSuccess("Fasilitas day-pass berhasil ditambahkan.");
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
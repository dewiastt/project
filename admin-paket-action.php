<?php
require_once 'config.php';
requireMasuk();
$conn = getConnection();

$action = $_POST['action'] ?? '';

function uploadFotoKamar($file) {
    if (empty($file['name'])) return null;
    if (!is_dir('assets/kamar')) mkdir('assets/kamar', 0777, true);
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) return null;
    $fname = uniqid('kamar_') . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], 'assets/kamar/' . $fname)) {
        return 'assets/kamar/' . $fname;
    }
    return null;
}

if ($action === 'create') {
    $hotel_id = (int)($_POST['hotel_id'] ?? 0);
    $nama     = trim($_POST['nama_kamar'] ?? '');
    $harga    = (float)($_POST['harga_per_malam'] ?? 0);
    $kap      = max(1, (int)($_POST['kapasitas'] ?? 2));
    $stok     = max(1, (int)($_POST['stok_kamar'] ?? 5));
    $fas      = trim($_POST['fasilitas_kamar'] ?? '');
    $foto     = uploadFotoKamar($_FILES['foto'] ?? []) ?? 'assets/img/reservation.jpg';

    if ($hotel_id <= 0 || $nama === '' || $harga <= 0) {
        setError('Hotel, nama kamar, dan harga wajib diisi.');
        header('Location: admin-paket.php');
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO kamar (hotel_id, nama_kamar, harga_per_malam, kapasitas, stok_kamar, fasilitas_kamar, foto) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isdiiss", $hotel_id, $nama, $harga, $kap, $stok, $fas, $foto);
    if ($stmt->execute()) setSuccess('Tipe kamar berhasil ditambahkan.');
    else setError('Gagal menambahkan kamar: ' . $conn->error);
    header('Location: admin-paket.php');
    exit;
}

if ($action === 'update') {
    $id       = (int)($_POST['id'] ?? 0);
    $hotel_id = (int)($_POST['hotel_id'] ?? 0);
    $nama     = trim($_POST['nama_kamar'] ?? '');
    $harga    = (float)($_POST['harga_per_malam'] ?? 0);
    $kap      = max(1, (int)($_POST['kapasitas'] ?? 2));
    $stok     = max(1, (int)($_POST['stok_kamar'] ?? 5));
    $fas      = trim($_POST['fasilitas_kamar'] ?? '');
    $fotoBaru = uploadFotoKamar($_FILES['foto'] ?? []);

    if ($fotoBaru) {
        $stmt = $conn->prepare("UPDATE kamar SET hotel_id=?, nama_kamar=?, harga_per_malam=?, kapasitas=?, stok_kamar=?, fasilitas_kamar=?, foto=? WHERE id=?");
        $stmt->bind_param("isdiissi", $hotel_id, $nama, $harga, $kap, $stok, $fas, $fotoBaru, $id);
    } else {
        $stmt = $conn->prepare("UPDATE kamar SET hotel_id=?, nama_kamar=?, harga_per_malam=?, kapasitas=?, stok_kamar=?, fasilitas_kamar=? WHERE id=?");
        $stmt->bind_param("isdiisi", $hotel_id, $nama, $harga, $kap, $stok, $fas, $id);
    }

    if ($stmt->execute()) setSuccess('Data kamar berhasil diperbarui.');
    else setError('Gagal memperbarui kamar.');
    header('Location: admin-paket.php');
    exit;
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM kamar WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) setSuccess('Tipe kamar berhasil dihapus.');
    else setError('Gagal menghapus kamar.');
    header('Location: admin-paket.php');
    exit;
}

header('Location: admin-paket.php');
exit;
?>
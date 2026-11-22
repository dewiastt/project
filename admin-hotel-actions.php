<?php
require_once 'config.php';
requireMasuk();
$conn = getConnection();
$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $nama = trim($_POST['nama_hotel'] ?? '');
    $nameCheck = $conn->prepare("SELECT id FROM hotels WHERE nama_hotel = ? LIMIT 1");
    $nameCheck->bind_param("s", $nama);
    $nameCheck->execute();
    $duplicateName = $nameCheck->get_result()->num_rows > 0;
    $nameCheck->close();
    if ($duplicateName) {
        setError("Nama hotel tersebut sudah terdaftar.");
        header("Location: admin-hotel.php");
        exit;
    }

    $kec  = $_POST['kecamatan'] ?? 'Sangatta Utara';
    $bnt  = (int)($_POST['bintang'] ?? 3);
    $almt = trim($_POST['alamat'] ?? '');
    $desk = trim($_POST['deskripsi'] ?? '');
    $foto = 'assets/hero/home.jpeg';

    if (!empty($_FILES['foto']['name'])) {
        if (!is_dir('assets/hotels')) mkdir('assets/hotels', 0777, true);
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $fname = uniqid('hotel_') . '.' . $ext;
        if (move_uploaded_file($_FILES['foto']['tmp_name'], 'assets/hotels/' . $fname)) {
            $foto = 'assets/hotels/' . $fname;
        }
    }

    $stmt = $conn->prepare("INSERT INTO hotels (nama_hotel, kecamatan, alamat, bintang, deskripsi, foto_utama) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssiss", $nama, $kec, $almt, $bnt, $desk, $foto);
    if ($stmt->execute()) {
        seedDefaultFacilitiesForHotel((int)$stmt->insert_id);
        setSuccess("Hotel berhasil ditambahkan beserta katalog fasilitas awal. Silakan sesuaikan harga dan ketersediaan.");
    }
    else setError("Gagal menambah hotel.");
    header("Location: admin-hotel.php");
    exit;
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM hotels WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) setSuccess("Hotel berhasil dihapus.");
    header("Location: admin-hotel.php");
    exit;
}
?>

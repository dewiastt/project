<?php
require_once 'config.php';
requireMasuk();

$conn = getConnection();
ensureTestimoniTable();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create':
            handleCreate($conn);
            break;
        case 'update':
            handlePerbarui($conn);
            break;
        case 'delete':
            handleHapus($conn);
            break;
        default:
            setError("Aksi tidak valid");
            header("Location: admin-testimoni.php");
            exit;
    }
}

function handleCreate($conn) {
    $nama = $_POST['nama'] ?? '';
    $testimoni = $_POST['testimoni'] ?? '';
    $rating = (int)($_POST['rating'] ?? 5);
    $rating = max(1, min(5, $rating));
    $tanggal = $_POST['tanggal'] ?? '';

    // Validasi input
    if (empty($nama) || empty($testimoni) || empty($tanggal)) {
        setError("Semua field harus diisi");
        header("Location: admin-testimoni.php");
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO testimoni (nama, testimoni, rating, tanggal) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $nama, $testimoni, $rating, $tanggal);

    if ($stmt->execute()) {
        setSuccess("Testimoni berhasil ditambahkan");
    } else {
        setError("Gagal menambahkan testimoni: " . $conn->error);
    }

    $stmt->close();
    header("Location: admin-testimoni.php");
    exit;
}

function handlePerbarui($conn) {
    $id = $_POST['id'] ?? '';
    $nama = $_POST['nama'] ?? '';
    $testimoni = $_POST['testimoni'] ?? '';
    $rating = (int)($_POST['rating'] ?? 5);
    $rating = max(1, min(5, $rating));
    $tanggal = $_POST['tanggal'] ?? '';

    // Validasi input
    if (empty($id) || empty($nama) || empty($testimoni) || empty($tanggal)) {
        setError("Semua field harus diisi kecuali foto");
        header("Location: admin-testimoni.php");
        exit;
    }

    $stmt = $conn->prepare("UPDATE testimoni SET nama = ?, testimoni = ?, rating = ?, tanggal = ? WHERE id = ?");
    $stmt->bind_param("ssisi", $nama, $testimoni, $rating, $tanggal, $id);

    if ($stmt->execute()) {
        setSuccess("Testimoni berhasil diperbarui");
    } else {
        setError("Gagal memperbarui testimoni: " . $conn->error);
    }

    $stmt->close();
    header("Location: admin-testimoni.php");
    exit;
}

function handleHapus($conn) {
    $id = $_POST['id'] ?? '';

    if (empty($id)) {
        setError("ID testimoni tidak valid");
        header("Location: admin-testimoni.php");
        exit;
    }

    // Hapus record dari database
    $stmt = $conn->prepare("DELETE FROM testimoni WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        setSuccess("Testimoni berhasil dihapus");
    } else {
        setError("Gagal menghapus testimoni: " . $conn->error);
    }

    $stmt->close();
    header("Location: admin-testimoni.php");
    exit;
}

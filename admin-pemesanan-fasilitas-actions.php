<?php
require_once 'config.php';
requireMasuk();
$conn = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if ($id > 0 && in_array($status, ['lunas', 'batal'], true)) {
        $check = $conn->prepare("SELECT metode_bayar, status_pembayaran FROM pemesanan_fasilitas WHERE id = ? LIMIT 1");
        $check->bind_param("i", $id);
        $check->execute();
        $order = $check->get_result()->fetch_assoc();
        $check->close();

        if ($order && $status === 'lunas'
            && $order['metode_bayar'] === 'QRIS'
            && $order['status_pembayaran'] === 'pending') {
            setError("Status QRIS hanya dapat menjadi lunas setelah notifikasi Midtrans terverifikasi.");
            header("Location: admin-pemesanan-fasilitas.php");
            exit();
        }

        $update = $conn->prepare("UPDATE pemesanan_fasilitas SET status_pembayaran = ? WHERE id = ?");
        $update->bind_param("si", $status, $id);
        if ($update->execute()) {
            setSuccess("Status pesanan fasilitas diubah menjadi " . strtoupper($status) . ".");
        } else {
            setError("Gagal memperbarui status pesanan fasilitas.");
        }
        $update->close();
    }
}

header("Location: admin-pemesanan-fasilitas.php");
exit();

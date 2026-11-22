<?php
require_once 'config.php';
requireMasuk();
$conn = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if ($id > 0 && in_array($status, ['lunas', 'selesai', 'batal'])) {
        $check = $conn->prepare("SELECT metode_bayar, status_pembayaran FROM reservasi WHERE id = ? LIMIT 1");
        $check->bind_param("i", $id);
        $check->execute();
        $reservation = $check->get_result()->fetch_assoc();
        $check->close();

        if ($reservation && $status === 'lunas'
            && $reservation['metode_bayar'] === 'QRIS'
            && $reservation['status_pembayaran'] === 'pending') {
            setError("Status QRIS hanya dapat menjadi lunas setelah notifikasi Midtrans terverifikasi.");
            header("Location: admin-reservasi.php");
            exit;
        }

        $stmt = $conn->prepare("UPDATE reservasi SET status_pembayaran = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
        setSuccess("Status reservasi diubah menjadi " . strtoupper($status));
    }
}
header("Location: admin-reservasi.php");
exit;
?>

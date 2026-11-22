<?php
require_once '../config.php';
ensureUsersTable();

if (!isUserLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../login.php?wajib_login=1");
    exit();
}

$orderType = $_POST['order_type'] ?? '';
$bookingCode = trim($_POST['kode_booking'] ?? '');
if (!in_array($orderType, ['reservation', 'facility'], true) || $bookingCode === '') {
    http_response_code(400);
    exit('Data pesanan tidak valid.');
}

$table = $orderType === 'reservation' ? 'reservasi' : 'pemesanan_fasilitas';
$stmt = $conn->prepare("SELECT * FROM $table WHERE kode_booking = ? AND user_id = ? AND status_pembayaran = 'pending' LIMIT 1");
$userId = (int)$_SESSION['user_id'];
$stmt->bind_param("si", $bookingCode, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order || $order['metode_bayar'] !== 'QRIS') {
    http_response_code(404);
    exit('Pesanan QRIS tidak ditemukan atau sudah tidak menunggu pembayaran.');
}

$customer = [
    'name' => $order['nama_tamu'],
    'email' => $order['email'] ?? '',
    'phone' => $order['no_hp']
];

try {
    createMidtransQrisPayment($orderType, $bookingCode, $order['total_harga'], $customer);
    $target = $orderType === 'reservation' ? '../track-reservasi.php' : '../track-fasilitas.php';
    header("Location: " . $target . "?kode=" . urlencode($bookingCode));
    exit();
} catch (RuntimeException $error) {
    error_log('Midtrans QRIS retry failed: ' . $error->getMessage());
    $target = $orderType === 'reservation' ? '../track-reservasi.php' : '../track-fasilitas.php';
    header("Location: " . $target . "?kode=" . urlencode($bookingCode) . "&qris=error");
    exit();
}

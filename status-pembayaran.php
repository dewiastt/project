<?php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

$orderType = $_GET['type'] ?? '';
$bookingCode = trim($_GET['kode'] ?? '');
if (!in_array($orderType, ['reservation', 'facility'], true) || $bookingCode === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid order reference.']);
    exit();
}

$payment = getPaymentTransaction($orderType, $bookingCode);
if (!$payment) {
    http_response_code(404);
    echo json_encode(['error' => 'Payment not found.']);
    exit();
}

try {
    if ($payment['transaction_status'] === 'pending') {
        $payment = syncMidtransPaymentStatus($payment['order_id']);
    }
    $bookingStatus = $payment['transaction_status'] === 'settlement'
        ? 'lunas'
        : (in_array($payment['transaction_status'], ['expire', 'deny', 'cancel'], true) ? 'batal' : 'pending');
    echo json_encode([
        'status' => $payment['transaction_status'],
        'booking_status' => $bookingStatus
    ]);
} catch (RuntimeException $error) {
    error_log('Midtrans status refresh failed: ' . $error->getMessage());
    http_response_code(503);
    echo json_encode(['error' => 'Status pembayaran sementara tidak dapat diperbarui.']);
}

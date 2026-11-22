<?php
require_once 'config.php';
ensurePaymentTransactionsTable();
header('Content-Type: application/json; charset=utf-8');

$payload = json_decode(file_get_contents('php://input'), true);
$serverKey = getenv('MIDTRANS_SERVER_KEY');
if (!$serverKey) {
    http_response_code(500);
    echo json_encode(['error' => 'Payment gateway is not configured.']);
    exit();
}
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid notification.']);
    exit();
}

$orderId = (string)($payload['order_id'] ?? '');
$statusCode = (string)($payload['status_code'] ?? '');
$grossAmount = (string)($payload['gross_amount'] ?? '');
$signature = strtolower((string)($payload['signature_key'] ?? ''));
if ($orderId === '' || $statusCode === '' || $grossAmount === '' || $signature === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing notification fields.']);
    exit();
}

$expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
if (!hash_equals($expectedSignature, $signature)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid signature.']);
    exit();
}

$stmt = $conn->prepare("SELECT gross_amount FROM payment_transactions WHERE order_id = ? LIMIT 1");
$stmt->bind_param("s", $orderId);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$payment || abs((float)$payment['gross_amount'] - (float)$grossAmount) > 0.01) {
    http_response_code(404);
    echo json_encode(['error' => 'Payment order not found or amount mismatch.']);
    exit();
}

try {
    syncMidtransPaymentStatus($orderId);
    echo json_encode(['status' => 'ok']);
} catch (RuntimeException $error) {
    error_log('Midtrans notification processing failed: ' . $error->getMessage());
    http_response_code(503);
    echo json_encode(['error' => 'Could not verify transaction status.']);
}

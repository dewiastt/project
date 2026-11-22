<?php
require_once 'config.php';
ensurePaymentTransactionsTable();

$orderType = $_GET['type'] ?? '';
$bookingCode = trim($_GET['kode'] ?? '');
if (!in_array($orderType, ['reservation', 'facility'], true) || $bookingCode === '') {
    http_response_code(400);
    exit();
}

$payment = getPaymentTransaction($orderType, $bookingCode);
$serverKey = getenv('MIDTRANS_SERVER_KEY');
if (!$payment || !$serverKey) {
    http_response_code(404);
    exit();
}

$isProduction = filter_var(getenv('MIDTRANS_IS_PRODUCTION') ?: 'false', FILTER_VALIDATE_BOOLEAN);
$expectedHost = $isProduction ? 'api.midtrans.com' : 'api.sandbox.midtrans.com';
$qrUrl = parse_url($payment['qr_code_url']);
if (($qrUrl['scheme'] ?? '') !== 'https'
    || strtolower($qrUrl['host'] ?? '') !== $expectedHost
    || !preg_match('#^/(v2|v4)/qris/[A-Za-z0-9._-]+/qr-code$#', $qrUrl['path'] ?? '')) {
    http_response_code(502);
    exit();
}

$curl = curl_init($payment['qr_code_url']);
if ($curl === false) {
    http_response_code(502);
    exit();
}
curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD => $serverKey . ':',
    CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_HTTPHEADER => ['Accept: image/png']
]);
$image = curl_exec($curl);
$httpStatus = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
$contentType = (string)curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
$curlError = curl_error($curl);
curl_close($curl);

if ($image === false || $httpStatus !== 200 || stripos($contentType, 'image/') !== 0) {
    error_log('Midtrans QR image request failed: ' . ($curlError ?: 'HTTP ' . $httpStatus));
    http_response_code(502);
    exit();
}

header('Content-Type: ' . $contentType);
header('Cache-Control: no-store, private');
echo $image;

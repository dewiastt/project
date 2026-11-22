<?php
require_once 'config.php';
ensureUsersTable();

$kode = trim($_GET['kode'] ?? '');
$data = null;
$payment = null;

if ($kode !== '') {
    $stmt = $conn->prepare("
        SELECT p.*, h.nama_hotel, h.alamat, h.kecamatan, f.nama_fasilitas, f.kategori, f.satuan, f.jam_operasional
        FROM pemesanan_fasilitas p
        JOIN hotels h ON p.hotel_id=h.id
        JOIN fasilitas f ON p.fasilitas_id=f.id
        WHERE p.kode_booking=?
    ");
    $stmt->bind_param("s", $kode);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($data) {
        $payment = getPaymentTransaction('facility', $kode);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cek Pesanan Fasilitas - SangattaStay</title>
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/sangatta-stay.css" rel="stylesheet">
  <link href="assets/css/luxury-theme.css" rel="stylesheet">
</head>
<body>
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-7">
      <div class="booking-builder-card p-4 mb-4 print-hide">
        <h4 class="fw-bold mb-3">Lacak Pesanan Fasilitas Mandiri</h4>
        <form method="GET" class="d-flex gap-2">
          <input type="text" name="kode" class="form-control" value="<?= htmlspecialchars($kode) ?>" placeholder="Masukkan kode pemesanan" required>
          <button class="btn btn-royal px-4">Cari</button>
        </form>
      </div>
      <?php if ($kode !== '' && !$data): ?>
        <div class="alert alert-danger">Kode pemesanan tidak ditemukan.</div>
      <?php elseif ($data): ?>
        <div class="booking-builder-card p-4">
          <?php if (($_GET['qris'] ?? '') === 'error'): ?>
            <div class="alert alert-warning rounded-4">
              QRIS belum berhasil dibuat. Pesanan tetap tersimpan; coba buat ulang QRIS setelah konfigurasi Midtrans tersedia.
              <?php if (isUserLoggedIn() && (int)($_SESSION['user_id'] ?? 0) === (int)$data['user_id'] && $data['status_pembayaran'] === 'pending'): ?>
                <form action="forms/buat-qris.php" method="POST" class="mt-2">
                  <input type="hidden" name="order_type" value="facility">
                  <input type="hidden" name="kode_booking" value="<?= htmlspecialchars($kode) ?>">
                  <button class="btn btn-warning btn-sm">Coba Buat QRIS</button>
                </form>
              <?php endif; ?>
            </div>
          <?php endif; ?>
          <?php if ($payment && $data['status_pembayaran'] === 'pending'): ?>
            <section class="qris-payment-panel mb-4">
              <div>
                <span class="qris-payment-label"><i class="bi bi-qr-code me-1"></i> Pembayaran QRIS</span>
                <h5 class="fw-bold mt-2">Pindai QR untuk membayar</h5>
                <p class="small text-muted mb-1">Status akan diperbarui otomatis setelah Midtrans mengonfirmasi pembayaran.</p>
                <strong><?= rupiah($payment['gross_amount']) ?></strong>
              </div>
              <img src="qris-image.php?type=facility&amp;kode=<?= urlencode($kode) ?>" alt="QRIS pembayaran fasilitas" class="qris-payment-image">
            </section>
          <?php elseif ($data['status_pembayaran'] === 'lunas'): ?>
            <div class="alert alert-success rounded-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
              <span><i class="bi bi-check-circle-fill me-1"></i> Pembayaran lunas. Tunjukkan kode ini saat mengakses fasilitas.</span>
              <button type="button" class="btn btn-success btn-sm print-hide" onclick="window.print()"><i class="bi bi-printer me-1"></i> Cetak Nota</button>
            </div>
            <?php if ($data['status_pembayaran'] === 'lunas'): ?>
              <div class="receipt-code mb-4">
                <small>Kode kunjungan</small>
                <strong><?= htmlspecialchars($data['kode_booking']) ?></strong>
                <span>Tunjukkan kode ini kepada petugas fasilitas.</span>
              </div>
            <?php endif; ?>
          <?php endif; ?>
          <div class="d-flex justify-content-between mb-3">
            <div>
              <span class="badge bg-dark"><?= htmlspecialchars($data['kode_booking']) ?></span>
              <h5 class="fw-bold mt-2 mb-0"><?= htmlspecialchars($data['nama_fasilitas']) ?></h5>
              <small class="text-muted"><?= htmlspecialchars($data['nama_hotel']) ?> - <?= htmlspecialchars($data['kecamatan']) ?></small>
            </div>
            <span class="badge bg-warning text-dark"><?= strtoupper($data['status_pembayaran']) ?></span>
          </div>
          <dl class="row small">
            <dt class="col-5">Tanggal kunjungan</dt><dd class="col-7"><?= htmlspecialchars($data['tanggal_kunjungan']) ?></dd>
            <dt class="col-5">Nama tamu</dt><dd class="col-7"><?= htmlspecialchars($data['nama_tamu']) ?></dd>
            <dt class="col-5">Jumlah</dt><dd class="col-7"><?= (int)$data['jumlah_pax'] ?> <?= htmlspecialchars(preg_replace('/^per\s+/i', '', $data['satuan'])) ?></dd>
            <dt class="col-5">Jam operasional</dt><dd class="col-7"><?= htmlspecialchars($data['jam_operasional']) ?></dd>
            <dt class="col-5">Metode bayar</dt><dd class="col-7"><?= htmlspecialchars($data['metode_bayar']) ?></dd>
          </dl>
          <div class="summary-checkout-bar p-3 d-flex justify-content-between">
            <strong>Total Pembayaran</strong>
            <strong style="color:#fbbf24;"><?= rupiah($data['total_harga']) ?></strong>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php if ($payment && $data && $data['status_pembayaran'] === 'pending'): ?>
<script>
const paymentStatusUrl = 'status-pembayaran.php?type=facility&kode=<?= rawurlencode($kode) ?>';
const paymentCheck = window.setInterval(async () => {
  try {
    const response = await fetch(paymentStatusUrl, {headers: {'Accept': 'application/json'}});
    if (!response.ok) return;
    const result = await response.json();
    if (result.booking_status === 'lunas' || result.booking_status === 'batal') {
      window.clearInterval(paymentCheck);
      window.location.reload();
    }
  } catch (error) {
    console.warn('Tidak dapat memperbarui status pembayaran saat ini.', error);
  }
}, 12000);
</script>
<?php endif; ?>
<script src="assets/js/luxury-motion.js" defer></script>
</body>
</html>

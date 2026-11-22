<?php
require_once 'config.php';
$conn = getConnection();
ensurePaymentTransactionsTable();

$kode = trim($_GET['kode'] ?? '');
$data = null;
$payment = null;
$fasilitasDipilih = [];

if ($kode !== '') {
    $stmt = $conn->prepare("
        SELECT r.*, h.nama_hotel, h.alamat, h.kecamatan, k.nama_kamar
        FROM reservasi r
        JOIN hotels h ON r.hotel_id = h.id
        JOIN kamar k ON r.kamar_id = k.id
        WHERE r.kode_booking = ?
    ");
    $stmt->bind_param("s", $kode);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($data) {
        $payment = getPaymentTransaction('reservation', $kode);
        $rid = (int)$data['id'];
        $qF = $conn->query("
            SELECT rf.*, f.nama_fasilitas, f.kategori, f.jam_operasional
            FROM reservasi_fasilitas rf
            JOIN fasilitas f ON rf.fasilitas_id = f.id
            WHERE rf.reservasi_id = $rid
        ");
        while ($row = $qF->fetch_assoc()) {
            $fasilitasDipilih[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Cek E-Ticket — SangattaStay</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/sangatta-stay.css" rel="stylesheet">
  <link href="assets/css/luxury-theme.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-stay py-3 mb-4 print-hide">
  <div class="container">
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php">
      <span class="brand-mark"><i class="bi bi-building-check"></i></span>
      <span>Sangatta<span style="color:#fbbf24;">Stay</span></span>
    </a>
    <a href="index.php" class="btn btn-outline-light btn-sm rounded-pill px-3">Kembali ke Beranda</a>
  </div>
</nav>

<div class="container pb-5">
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="booking-builder-card p-4 mb-4 print-hide">
        <h4 class="fw-bold mb-2">Lacak Reservasi Kamar &amp; Fasilitas</h4>
        <p class="small text-muted mb-3">Masukkan kode pemesanan (contoh: <code>SGT-...</code>) untuk melihat rincian kamar dan fasilitas pilihan Anda.</p>
        <form method="GET" class="d-flex gap-2">
          <input type="text" name="kode" class="form-control" placeholder="Masukkan Kode Pemesanan..." value="<?= htmlspecialchars($kode) ?>" required>
          <button type="submit" class="btn btn-royal px-4 rounded-3">Cari</button>
        </form>
      </div>

      <?php if ($kode !== '' && !$data): ?>
        <div class="alert alert-danger rounded-4">Kode booking <strong><?= htmlspecialchars($kode) ?></strong> tidak ditemukan.</div>
      <?php elseif ($data): ?>
        <div class="booking-builder-card p-4 p-md-5">
      <?php if (($_GET['qris'] ?? '') === 'error'): ?>
        <div class="alert alert-warning rounded-4">
          QRIS belum berhasil dibuat. Pesanan tetap tersimpan; coba buat ulang QRIS setelah konfigurasi Midtrans tersedia.
          <?php if (isUserLoggedIn() && (int)($_SESSION['user_id'] ?? 0) === (int)$data['user_id'] && $data['status_pembayaran'] === 'pending'): ?>
            <form action="forms/buat-qris.php" method="POST" class="mt-2">
              <input type="hidden" name="order_type" value="reservation">
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
          <img src="qris-image.php?type=reservation&amp;kode=<?= urlencode($kode) ?>" alt="QRIS pembayaran reservasi" class="qris-payment-image">
        </section>
      <?php elseif ($data['status_pembayaran'] === 'lunas'): ?>
        <div class="alert alert-success rounded-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
          <span><i class="bi bi-check-circle-fill me-1"></i> Pembayaran lunas. Nota ini dapat digunakan untuk check-in.</span>
          <button type="button" class="btn btn-success btn-sm print-hide" onclick="window.print()"><i class="bi bi-printer me-1"></i> Cetak Nota / E-Ticket</button>
        </div>
      <?php endif; ?>
          <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
            <div>
              <span class="badge bg-dark mb-1"><?= htmlspecialchars($data['kode_booking']) ?></span>
              <h5 class="fw-bold mb-0"><?= htmlspecialchars($data['nama_hotel']) ?></h5>
              <small class="text-muted"><?= htmlspecialchars($data['alamat']) ?> (<?= htmlspecialchars($data['kecamatan']) ?>)</small>
            </div>
            <?php if ($data['status_pembayaran'] === 'lunas'): ?>
              <div class="receipt-code mb-4">
                <small>Kode check-in</small>
                <strong><?= htmlspecialchars($data['kode_booking']) ?></strong>
                <span>Tunjukkan kode ini kepada petugas hotel.</span>
              </div>
            <?php endif; ?>
            <span class="badge bg-<?= $data['status_pembayaran']==='lunas'?'success':($data['status_pembayaran']==='batal'?'danger':'warning text-dark') ?> fs-6 text-uppercase">
              <?= htmlspecialchars($data['status_pembayaran']) ?>
            </span>
          </div>

          <div class="row small mb-3">
            <div class="col-sm-6 mb-2"><strong>Tamu:</strong><br><?= htmlspecialchars($data['nama_tamu']) ?> &bull; <?= htmlspecialchars($data['no_hp']) ?></div>
            <div class="col-sm-6 mb-2"><strong>Tanggal Masuk / Keluar:</strong><br><?= $data['tanggal_checkin'] ?> s/d <?= $data['tanggal_checkout'] ?> (<?= (int)$data['total_malam'] ?> malam)</div>
          </div>

          <div class="step-box-room p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <div class="fw-bold"><?= htmlspecialchars($data['nama_kamar']) ?></div>
                <small class="text-muted"><?= (int)$data['jumlah_kamar'] ?> Kamar &times; <?= (int)$data['total_malam'] ?> Malam</small>
                <?php if ((int)($data['jumlah_extra_bed'] ?? 0) > 0): ?>
                  <small class="d-block text-warning"><?= (int)$data['jumlah_extra_bed'] ?> Kasur Tambahan &mdash; <?= rupiah($data['subtotal_extra_bed'] ?? 0) ?></small>
                <?php endif; ?>
              </div>
              <strong style="color:var(--royal-indigo);"><?= rupiah($data['subtotal_kamar']) ?></strong>
            </div>
          </div>

          <h6 class="fw-bold mb-2">Fasilitas Berbayar yang Dipilih:</h6>
          <?php if (count($fasilitasDipilih) > 0): ?>
            <ul class="list-group mb-4">
              <?php foreach ($fasilitasDipilih as $f): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <div>
                    <strong><?= htmlspecialchars($f['nama_fasilitas']) ?></strong>
                    <small class="d-block text-muted"><?= htmlspecialchars($f['kategori']) ?> &bull; <?= htmlspecialchars($f['jam_operasional']) ?></small>
                  </div>
                  <span class="badge rounded-pill" style="background:var(--teal-soft); color:var(--teal-mint);">
                    <?= (int)$f['jumlah_pax'] ?> Jumlah Orang &mdash; <?= rupiah($f['subtotal']) ?>
                  </span>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p class="small text-muted fst-italic mb-4">Tidak ada fasilitas tambahan berbayar yang dipilih pada reservasi ini.</p>
          <?php endif; ?>

          <div class="summary-checkout-bar p-3 px-4 d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Total Pembayaran</span>
            <span class="fs-4 fw-bold" style="color:#fbbf24;"><?= rupiah($data['total_harga']) ?></span>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php if ($payment && $data && $data['status_pembayaran'] === 'pending'): ?>
<script>
const paymentStatusUrl = 'status-pembayaran.php?type=reservation&kode=<?= rawurlencode($kode) ?>';
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

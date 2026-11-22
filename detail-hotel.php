<?php
require_once 'config.php';
ensureUsersTable();
ensureAssetMappings();
$conn = getConnection();

$hotel_id = (int)($_GET['hotel_id'] ?? 0);
$kamar_id = (int)($_GET['kamar_id'] ?? 0);
$fas_id   = (int)($_GET['fas_id'] ?? 0);

if ($hotel_id <= 0) {
    header("Location: index.php#daftar-hotel");
    exit;
}

$stmtH = $conn->prepare("SELECT * FROM hotels WHERE id = ? AND status = 'aktif' LIMIT 1");
$stmtH->bind_param("i", $hotel_id);
$stmtH->execute();
$hotel = $stmtH->get_result()->fetch_assoc();
$stmtH->close();

if (!$hotel) {
    header("Location: index.php#daftar-hotel");
    exit;
}

$kamars = [];
$qK = $conn->query("SELECT *, foto AS foto_kamar FROM kamar WHERE hotel_id = $hotel_id ORDER BY harga_per_malam ASC");
while ($row = $qK->fetch_assoc()) {
    $kamars[] = $row;
}

$fasilitas = [];
$qF = $conn->query("SELECT * FROM fasilitas WHERE hotel_id = $hotel_id ORDER BY harga_fasilitas ASC");
while ($row = $qF->fetch_assoc()) {
    $fasilitas[] = $row;
}

$selectedKamar = null;
if ($kamar_id > 0) {
    foreach ($kamars as $k) {
        if ((int)$k['id'] === $kamar_id) {
            $selectedKamar = $k;
            break;
        }
    }
}

$user = currentUser();
$currentDetailUrl = 'detail-hotel.php?hotel_id=' . $hotel_id
    . ($selectedKamar ? '&kamar_id=' . (int)$selectedKamar['id'] : '')
    . ($fas_id > 0 ? '&fas_id=' . $fas_id : '')
    . ($selectedKamar ? '#detail-kamar-form' : '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title><?= htmlspecialchars($hotel['nama_hotel']) ?> — Detail Kamar &amp; Booking | SangattaStay</title>
  <link href="assets/img/favicon.png" rel="icon">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/sangatta-stay.css" rel="stylesheet">
  <link href="assets/css/luxury-theme.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-stay sticky-top py-3">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2 fs-4" href="index.php">
      <span class="brand-mark"><i class="bi bi-building-check"></i></span>
      <span>Sangatta<span style="color:#fbbf24;">Stay</span></span>
    </a>
    <div class="d-flex align-items-center gap-2">
      <a href="index.php#daftar-hotel" class="btn btn-outline-light btn-sm rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i> Daftar Hotel
      </a>
      <?php if ($user): ?>
        <a href="track-reservasi.php" class="btn btn-outline-light btn-sm rounded-pill px-3">Tiket Saya</a>
        <a href="logout.php" class="btn btn-amber-action btn-sm rounded-pill px-3">
          <i class="bi bi-person-check-fill me-1"></i> <?= htmlspecialchars($user['nama_lengkap']) ?> (Keluar)
        </a>
      <?php else: ?>
        <a href="login.php?redirect=<?= urlencode($currentDetailUrl) ?>" class="btn btn-amber-action btn-sm rounded-pill px-4">
          <i class="bi bi-box-arrow-in-right me-1"></i> Login
        </a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<main class="container py-5">
  <!-- HEADER DETAIL HOTEL -->
  <div class="booking-builder-card overflow-hidden mb-4">
    <div class="row g-0">
      <div class="col-lg-5">
        <?php if (isLocalImageUsable($hotel['foto_utama'])): ?>
          <img src="<?= assetUrl($hotel['foto_utama']) ?>" alt="<?= htmlspecialchars($hotel['nama_hotel']) ?>"
               style="width:100%; height:100%; min-height:280px; object-fit:cover;">
        <?php else: ?>
          <div class="lux-photo-fallback lux-photo-hotel lux-photo-detail" role="img" aria-label="Ilustrasi <?= htmlspecialchars($hotel['nama_hotel']) ?>"></div>
        <?php endif; ?>
      </div>
      <div class="col-lg-7 p-4 p-md-5 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <span class="badge rounded-pill" style="background:var(--teal-soft); color:var(--teal-mint);">
              <i class="bi bi-pin-map-fill me-1"></i><?= htmlspecialchars($hotel['kecamatan']) ?>
            </span>
            <span class="text-warning small"><?= str_repeat('<i class="bi bi-star-fill"></i>', (int)$hotel['bintang']) ?></span>
            <small class="text-muted"><?= htmlspecialchars($hotel['jarak_pusat']) ?></small>
          </div>
          <h2 class="fw-bold mb-2"><?= htmlspecialchars($hotel['nama_hotel']) ?></h2>
          <p class="small text-muted mb-3"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($hotel['alamat']) ?></p>
          <p class="text-secondary mb-0"><?= htmlspecialchars($hotel['deskripsi']) ?></p>
        </div>
        <div class="mt-4 pt-3 border-top d-flex flex-wrap justify-content-between align-items-center gap-2 small">
          <span class="text-muted">Pilih salah satu tipe kamar di bawah untuk melihat detail kamar &amp; membuka form reservasi.</span>
          <span class="fw-semibold" style="color:var(--royal-indigo);"><?= count($kamars) ?> Tipe Kamar Tersedia</span>
        </div>
      </div>
    </div>
  </div>

  <!-- DAFTAR TIPE KAMAR DI HOTEL INI -->
  <div class="mb-4">
    <span class="section-kicker">Langkah 1 &bull; Pilih Kamar</span>
    <h4 class="fw-bold mb-1">Tipe Kamar di <?= htmlspecialchars($hotel['nama_hotel']) ?></h4>
    <p class="text-muted small mb-3">Klik <strong>Lihat Detail &amp; Booking</strong> pada kamar yang diinginkan untuk melihat rincian kamar dan form reservasi.</p>

    <div class="row g-3">
      <?php if (count($kamars) > 0): ?>
        <?php foreach ($kamars as $k):
          $isSelected = ($selectedKamar && (int)$selectedKamar['id'] === (int)$k['id']);
        ?>
        <div class="col-md-6 col-lg-4">
          <div class="hotel-card-stay h-100 d-flex flex-column <?= $isSelected ? 'border border-2 border-primary' : '' ?>">
            <?php if (isLocalImageUsable($k['foto_kamar'] ?? '')): ?>
              <div class="hotel-thumb" style="height:175px;">
                <img src="<?= assetUrl($k['foto_kamar']) ?>" alt="<?= htmlspecialchars($k['nama_kamar']) ?>" loading="lazy">
              </div>
            <?php else: ?>
              <div class="hotel-thumb hotel-room-thumb" style="height:175px;" role="img" aria-label="Ilustrasi <?= htmlspecialchars($k['nama_kamar']) ?>"></div>
            <?php endif; ?>
            <div class="p-4 d-flex flex-column flex-grow-1">
              <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                <h5 class="fw-bold mb-0"><?= htmlspecialchars($k['nama_kamar']) ?></h5>
                <?php if ($isSelected): ?>
                  <span class="badge bg-primary rounded-pill">Dipilih</span>
                <?php endif; ?>
              </div>
              <div class="small text-muted mb-2">
                <i class="bi bi-people-fill me-1"></i> Kapasitas Maks. <?= (int)$k['kapasitas'] ?> Tamu
                <?php if (isset($k['stok_kamar'])): ?>
                  &bull; Tersedia <?= (int)$k['stok_kamar'] ?> unit
                <?php endif; ?>
              </div>
              <p class="small text-secondary flex-grow-1 mb-3">
                <?= htmlspecialchars($k['deskripsi'] ?? ($k['fasilitas_kamar'] ?? 'Kamar nyaman ber-AC, Wi-Fi cepat, dan kamar mandi dalam.')) ?>
              </p>
              <div class="hotel-price-box mb-3 d-flex justify-content-between align-items-center">
                <span class="small text-muted">Tarif / malam</span>
                <strong style="color:var(--royal-indigo);"><?= rupiah($k['harga_per_malam']) ?></strong>
              </div>
              <a href="detail-hotel.php?hotel_id=<?= $hotel_id ?>&kamar_id=<?= (int)$k['id'] ?><?= $fas_id > 0 ? '&fas_id='.$fas_id : '' ?>#detail-kamar-form"
                 class="btn <?= $isSelected ? 'btn-amber-action' : 'btn-royal' ?> w-100 rounded-pill py-2">
                <i class="bi bi-door-open me-1"></i> <?= $isSelected ? 'Sedang Dilihat' : 'Lihat Detail & Booking' ?>
              </a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-12">
          <div class="alert alert-warning rounded-4">Belum ada tipe kamar yang tersedia pada hotel ini.</div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- DETAIL KAMAR & FORM RESERVASI (HANYA MUNCUL JIKA USER SUDAH MEMBUKA SALAH SATU KAMAR) -->
  <?php if ($selectedKamar): ?>
    <div id="detail-kamar-form" class="booking-builder-card p-4 p-md-5 mt-4">
      <div class="row g-4 align-items-start mb-4 pb-4 border-bottom">
        <div class="col-lg-7">
          <span class="section-kicker">Detail Kamar Terpilih</span>
          <h3 class="fw-bold mb-2"><?= htmlspecialchars($selectedKamar['nama_kamar']) ?></h3>
          <div class="d-flex flex-wrap gap-3 small text-muted mb-3">
            <span><i class="bi bi-building me-1 text-primary"></i> <?= htmlspecialchars($hotel['nama_hotel']) ?></span>
            <span><i class="bi bi-people-fill me-1 text-primary"></i> Maks. <?= (int)$selectedKamar['kapasitas'] ?> Tamu</span>
            <span><i class="bi bi-cash-stack me-1 text-success"></i> <?= rupiah($selectedKamar['harga_per_malam']) ?> / malam</span>
          </div>
          <p class="text-secondary mb-0">
            <?= htmlspecialchars($selectedKamar['deskripsi'] ?? ($selectedKamar['fasilitas_kamar'] ?? 'Kamar bersih dan modern untuk kebutuhan perjalanan bisnis maupun keluarga di Sangatta.')) ?>
          </p>
        </div>
        <div class="col-lg-5">
          <div class="step-box-room p-3">
            <div class="small text-muted">Tarif Dasar Kamar</div>
            <div class="fs-3 fw-bold" style="color:var(--royal-indigo);"><?= rupiah($selectedKamar['harga_per_malam']) ?> <span class="fs-6 fw-normal text-muted">/ malam</span></div>
            <div class="small text-success mt-1"><i class="bi bi-check2-circle me-1"></i>Fasilitas tambahan berbayar dapat dipilih di bawah</div>
          </div>
        </div>
      </div>

      <?php if (!$user): ?>
        <!-- JIKA BELUM LOGIN: TAMPILKAN AJAKAN LOGIN / REGISTER SEBELUM FORM BOOKING -->
        <div class="p-4 p-md-5 rounded-4 text-center" style="background:var(--surface-tint); border:1.5px dashed var(--royal-indigo);">
          <div class="brand-mark mx-auto mb-3" style="width:54px;height:54px;font-size:1.4rem;">
            <i class="bi bi-lock-fill"></i>
          </div>
          <h4 class="fw-bold mb-2">Login Terlebih Dahulu untuk Melakukan Booking</h4>
          <p class="text-muted mx-auto mb-4" style="max-width:520px;">
            Anda sedang melihat detail kamar <strong><?= htmlspecialchars($selectedKamar['nama_kamar']) ?></strong> di <strong><?= htmlspecialchars($hotel['nama_hotel']) ?></strong>. Silakan masuk ke akun Anda (atau daftar jika belum punya akun) untuk mengisi formulir reservasi.
          </p>
          <div class="d-flex flex-wrap justify-content-center gap-2">
            <a href="login.php?wajib_login=1&redirect=<?= urlencode($currentDetailUrl) ?>" class="btn btn-amber-action btn-lg rounded-pill px-4">
              <i class="bi bi-box-arrow-in-right me-1"></i> Login Sekarang
            </a>
            <a href="register.php?redirect=<?= urlencode($currentDetailUrl) ?>" class="btn btn-royal btn-lg rounded-pill px-4">
              <i class="bi bi-person-plus-fill me-1"></i> Register Akun Baru
            </a>
          </div>
        </div>
      <?php else: ?>
        <!-- JIKA SUDAH LOGIN: TAMPILKAN FORM RESERVASI LENGKAP -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
          <div>
            <span class="section-kicker">Formulir Booking</span>
            <h4 class="fw-bold mb-0">Lengkapi Jadwal &amp; Fasilitas Tambahan</h4>
          </div>
          <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2">
            <i class="bi bi-person-check-fill me-1"></i> Masuk sebagai <?= htmlspecialchars($user['nama_lengkap']) ?>
          </span>
        </div>

        <form action="forms/reservasi.php" method="POST">
          <input type="hidden" name="hotel_id" value="<?= $hotel_id ?>">
          <input type="hidden" name="kamar_id" value="<?= (int)$selectedKamar['id'] ?>">

          <div class="step-box-room p-4 mb-4">
            <h6 class="fw-bold mb-3" style="color:var(--navy-deep);">
              <i class="bi bi-1-circle-fill me-2" style="color:var(--royal-indigo);"></i>Jadwal Menginap &amp; Jumlah Kamar
            </h6>
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label small fw-bold">Jumlah Kamar <span class="text-danger">*</span></label>
                <input type="number" name="jumlah_kamar" id="jumlahKamar" class="form-control" value="1" min="1" max="10" required onchange="hitungTotal()">
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-bold">Kasur Tambahan</label>
                <?php if ((int)($selectedKamar['extra_bed_tersedia'] ?? 1) === 1): ?>
                  <select name="jumlah_extra_bed" id="jumlahExtraBed" class="form-select" onchange="hitungTotal()">
                    <option value="0">Tidak ditambahkan</option>
                    <option value="1">1 Kasur Tambahan</option>
                    <option value="2">2 Kasur Tambahan</option>
                  </select>
                  <div class="small text-muted mt-1"><?= rupiah($selectedKamar['harga_extra_bed'] ?? 150000) ?> / kasur / malam</div>
                <?php else: ?>
                  <select class="form-select" disabled>
                    <option>Tidak tersedia</option>
                  </select>
                  <input type="hidden" name="jumlah_extra_bed" value="0">
                <?php endif; ?>
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-bold">Tanggal Check-In <span class="text-danger">*</span></label>
                <input type="date" name="tanggal_checkin" id="tglCheckin" class="form-control" value="<?= date('Y-m-d') ?>" required onchange="hitungTotal()">
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-bold">Tanggal Check-Out <span class="text-danger">*</span></label>
                <input type="date" name="tanggal_checkout" id="tglCheckout" class="form-control" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" required onchange="hitungTotal()">
              </div>
            </div>
          </div>

          <div class="step-box-facilities p-4 mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
              <h6 class="fw-bold mb-0" style="color:var(--navy-deep);">
                <i class="bi bi-2-circle-fill me-2" style="color:var(--teal-mint);"></i>Pilih Fasilitas Tambahan Berbayar (Opsional)
              </h6>
              <span class="badge rounded-pill" style="background:var(--teal-soft); color:var(--teal-mint);">Bayar yang dicentang saja</span>
            </div>
            <div class="row g-2 mt-1">
              <?php if (count($fasilitas) > 0): ?>
                <?php foreach ($fasilitas as $f):
                  $isChecked = ($fas_id === (int)$f['id']);
                ?>
                <div class="col-md-6">
                  <div class="facility-option-tile <?= $isChecked ? 'selected' : '' ?>" id="tile_<?= (int)$f['id'] ?>">
                    <div class="d-flex justify-content-between align-items-center gap-2">
                      <div class="form-check m-0">
                        <input class="form-check-input fas-check" type="checkbox" name="fasilitas_ids[]"
                               value="<?= (int)$f['id'] ?>" id="fas_<?= (int)$f['id'] ?>"
                               data-harga="<?= (float)$f['harga_fasilitas'] ?>" <?= $isChecked ? 'checked' : '' ?>
                               onchange="toggleTile(<?= (int)$f['id'] ?>); hitungTotal();">
                        <label class="form-check-label small fw-bold" for="fas_<?= (int)$f['id'] ?>">
                          <?= htmlspecialchars($f['nama_fasilitas']) ?>
                          <span class="d-block fw-semibold" style="color:var(--teal-mint);"><?= rupiah($f['harga_fasilitas']) ?> / <?= htmlspecialchars($f['satuan']) ?></span>
                        </label>
                      </div>
                      <div style="width:82px;">
                        <label class="form-label" style="font-size:0.68rem; margin-bottom:2px;">Jumlah</label>
                        <input type="number" name="fasilitas_pax[<?= (int)$f['id'] ?>]" class="form-control form-control-sm fas-pax"
                               data-fid="<?= (int)$f['id'] ?>" value="1" min="1" max="50" onchange="hitungTotal()">
                      </div>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="col-12 small text-muted">Hotel ini belum memiliki fasilitas tambahan berbayar.</div>
              <?php endif; ?>
            </div>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-md-4">
              <label class="form-label small fw-bold">Nama Lengkap Tamu <span class="text-danger">*</span></label>
              <input type="text" name="nama_tamu" class="form-control" value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold">Nomor WhatsApp <span class="text-danger">*</span></label>
              <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($user['no_hp']) ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-bold">Metode Pembayaran</label>
              <select name="metode_bayar" class="form-select">
                <option value="QRIS">QRIS (Semua E-Wallet &amp; Mobile Banking)</option>
                <option value="Transfer Bank">Transfer Bank (BRI / Mandiri / BCA)</option>
                <option value="Bayar di Resepsionis">Bayar di Resepsionis Hotel</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label small fw-bold">Catatan Tambahan (Opsional)</label>
              <input type="text" name="catatan" class="form-control" placeholder="Contoh: Request lantai atas atau jam sarapan">
            </div>
          </div>

          <div class="summary-checkout-bar p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
              <div class="small" style="color:rgba(255,255,255,0.75);">
                Kamar: <strong id="rincianKamar" class="text-white">Rp 0</strong> &bull;
                Fasilitas: <strong id="rincianFasilitas" style="color:#5eead4;">Rp 0</strong> &bull;
                Kasur Tambahan: <strong id="rincianExtraBed" style="color:#fde68a;">Rp 0</strong>
              </div>
              <div class="fs-2 fw-bold mt-1" style="color:#fbbf24;" id="totalBayarDisplay">Rp 0</div>
            </div>
            <button type="submit" class="btn btn-amber-action btn-lg rounded-pill px-5">
              Konfirmasi Reservasi <i class="bi bi-arrow-right-circle-fill ms-1"></i>
            </button>
          </div>
        </form>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</main>

<script>
const hargaKamarPerMalam = <?= $selectedKamar ? (float)$selectedKamar['harga_per_malam'] : 0 ?>;
const hargaExtraBedPerMalam = <?= $selectedKamar ? (float)($selectedKamar['harga_extra_bed'] ?? 150000) : 0 ?>;
const extraBedTersedia = <?= $selectedKamar ? (int)($selectedKamar['extra_bed_tersedia'] ?? 1) : 0 ?>;

function formatRp(num) {
  return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
}

function toggleTile(fid) {
  const cb = document.getElementById('fas_' + fid);
  const tile = document.getElementById('tile_' + fid);
  if (cb && tile) {
    tile.classList.toggle('selected', cb.checked);
  }
}

function hitungTotal() {
  const jmlKamarEl = document.getElementById('jumlahKamar');
  if (!jmlKamarEl) return;

  const jmlKamar = parseInt(jmlKamarEl.value) || 1;
  const tIn = new Date(document.getElementById('tglCheckin').value);
  const tOut = new Date(document.getElementById('tglCheckout').value);
  let malam = 1;
  if (!isNaN(tIn) && !isNaN(tOut) && tOut > tIn) {
    malam = Math.ceil((tOut - tIn) / 86400000);
  }

  const subKamar = hargaKamarPerMalam * jmlKamar * malam;
  const extraBedSelect = document.getElementById('jumlahExtraBed');
  const jmlExtraBed = extraBedTersedia && extraBedSelect ? (parseInt(extraBedSelect.value) || 0) : 0;
  const subExtraBed = jmlExtraBed * hargaExtraBedPerMalam * malam;

  let subFas = 0;
  document.querySelectorAll('.fas-check:checked').forEach(cb => {
    const harga = parseFloat(cb.dataset.harga) || 0;
    const paxEl = document.querySelector(`.fas-pax[data-fid="${cb.value}"]`);
    const pax = paxEl ? (parseInt(paxEl.value) || 1) : 1;
    subFas += harga * pax;
  });

  document.getElementById('rincianKamar').textContent = formatRp(subKamar);
  document.getElementById('rincianFasilitas').textContent = formatRp(subFas);
  document.getElementById('rincianExtraBed').textContent = formatRp(subExtraBed);
  document.getElementById('totalBayarDisplay').textContent = formatRp(subKamar + subFas + subExtraBed);
}

window.addEventListener('DOMContentLoaded', hitungTotal);
</script>
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/luxury-motion.js" defer></script>
</body>
</html>

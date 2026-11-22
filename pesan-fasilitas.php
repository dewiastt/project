<?php
require_once 'config.php';
ensureUsersTable();
requireUserMasuk('pesan-fasilitas.php');

$conn = getConnection();
$user = currentUser();
$hotelId = (int)($_GET['hotel_id'] ?? 0);
$fasilitasId = (int)($_GET['fas_id'] ?? 0);

$hotels = $conn->query("SELECT h.* FROM hotels h
    WHERE h.status = 'aktif'
    AND h.id = (SELECT MIN(h2.id) FROM hotels h2
                WHERE h2.nama_hotel = h.nama_hotel AND h2.status = 'aktif')
    ORDER BY h.nama_hotel ASC");
$facilities = [];
$result = $conn->query("SELECT f.*, h.nama_hotel FROM fasilitas f JOIN hotels h ON f.hotel_id = h.id
    WHERE h.status = 'aktif'
    AND h.id = (SELECT MIN(h2.id) FROM hotels h2
                WHERE h2.nama_hotel = h.nama_hotel AND h2.status = 'aktif')
    ORDER BY h.nama_hotel ASC, f.nama_fasilitas ASC");
while ($facility = $result->fetch_assoc()) {
    if (!isLocalImageUsable($facility['foto'])) {
        $facility['foto'] = 'assets/img/luxury-facility-scene.svg';
    }
    $facilities[] = $facility;
}
$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pesan Fasilitas Mandiri - SangattaStay</title>
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/sangatta-stay.css" rel="stylesheet">
  <link href="assets/css/luxury-theme.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-stay sticky-top py-3">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2 fs-5" href="index.php">
      <span class="brand-mark"><i class="bi bi-building-check"></i></span>
      <span>Sangatta<span style="color:#fbbf24;">Stay</span></span>
    </a>
    <div class="d-flex align-items-center gap-2">
      <a href="track-fasilitas.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
        <i class="bi bi-ticket-perforated me-1"></i> Cek Pesanan
      </a>
      <a href="booking.php" class="btn btn-amber-action btn-sm rounded-pill px-3">
        <i class="bi bi-door-open me-1"></i> Pesan Kamar
      </a>
    </div>
  </div>
</nav>
<main class="container py-4 py-lg-5">
  <section class="facility-booking-hero mb-4 mb-lg-5">
    <div class="facility-booking-hero-content">
      <span class="facility-booking-eyebrow"><i class="bi bi-stars me-1"></i> Pengalaman pilihan Anda</span>
      <h1>Pesan fasilitas,<br><span>tanpa menginap.</span></h1>
      <p>Pilih hotel, tentukan fasilitas dan jadwal kunjungan. Tidak perlu memesan kamar atau check-in.</p>
      <div class="facility-booking-perks">
        <span><i class="bi bi-check-circle-fill"></i> Harga transparan</span>
        <span><i class="bi bi-check-circle-fill"></i> Konfirmasi dengan kode pesanan</span>
      </div>
    </div>
    <div class="facility-booking-hero-art" aria-hidden="true">
      <span class="facility-orbit facility-orbit-one"></span>
      <span class="facility-orbit facility-orbit-two"></span>
      <span class="facility-hero-icon"><i class="bi bi-water"></i></span>
      <span class="facility-floating-icon facility-floating-one"><i class="bi bi-cup-hot"></i></span>
      <span class="facility-floating-icon facility-floating-two"><i class="bi bi-heart-pulse"></i></span>
    </div>
  </section>

  <div class="row g-4 align-items-start">
    <div class="col-lg-8">
      <form action="forms/pesan-fasilitas.php" method="POST" id="facilityBookingForm">
        <section class="facility-form-panel mb-4">
          <div class="facility-panel-heading">
            <span class="facility-step-number">01</span>
            <div><h2>Pilih hotel &amp; fasilitas</h2><p>Pilih fasilitas yang ingin Anda nikmati.</p></div>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="pesanHotel">Hotel</label>
              <select name="hotel_id" id="pesanHotel" class="form-select form-select-lg" required>
                <option value="">Pilih hotel</option>
                <?php while ($hotel = $hotels->fetch_assoc()):
                  $selected = $hotelId === (int)$hotel['id'] ? 'selected' : '';
                ?>
                  <option value="<?= (int)$hotel['id'] ?>" <?= $selected ?>><?= htmlspecialchars($hotel['nama_hotel']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="pesanFasilitas">Fasilitas</label>
              <select name="fasilitas_id" id="pesanFasilitas" class="form-select form-select-lg" required>
                <option value="">Pilih fasilitas</option>
              </select>
            </div>
          </div>
          <div class="facility-preview mt-4" id="facilityPreview" aria-live="polite">
            <div class="facility-preview-placeholder">
              <span><i class="bi bi-building"></i></span>
              <div><strong>Fasilitas pilihan Anda akan tampil di sini</strong><small>Pilih hotel dan fasilitas untuk melihat harga serta detailnya.</small></div>
            </div>
          </div>
          <div class="facility-empty-state d-none mt-3" id="facilityEmptyState">
            <i class="bi bi-info-circle"></i>
            <span>Hotel ini belum memiliki fasilitas yang dapat dipesan. Silakan pilih hotel lain.</span>
          </div>
        </section>

        <section class="facility-form-panel mb-4">
          <div class="facility-panel-heading">
            <span class="facility-step-number">02</span>
            <div><h2>Atur kunjungan</h2><p>Tentukan jadwal dan jumlah tamu.</p></div>
          </div>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label" for="tanggalKunjungan">Tanggal kunjungan</label>
              <input type="date" name="tanggal_kunjungan" id="tanggalKunjungan" class="form-control form-control-lg" min="<?= $today ?>" value="<?= $today ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="jumlahPax">Jumlah <span id="jumlahUnitLabel">orang</span></label>
              <div class="input-group input-group-lg facility-guest-input">
                <span class="input-group-text"><i class="bi bi-hash"></i></span>
                <input type="number" name="jumlah_pax" id="jumlahPax" class="form-control" value="1" min="1" max="50" required>
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="metodeBayar">Metode pembayaran</label>
              <select name="metode_bayar" id="metodeBayar" class="form-select form-select-lg">
                <option value="QRIS">QRIS</option>
                <option value="Transfer Bank">Transfer Bank</option>
                <option value="Bayar di Lokasi">Bayar di Lokasi</option>
              </select>
            </div>
          </div>
        </section>

        <section class="facility-form-panel">
          <div class="facility-panel-heading">
            <span class="facility-step-number">03</span>
            <div><h2>Data pemesan</h2><p>Informasi ini digunakan untuk konfirmasi pesanan.</p></div>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="namaTamu">Nama lengkap</label>
              <input type="text" name="nama_tamu" id="namaTamu" class="form-control form-control-lg" value="<?= htmlspecialchars($user['nama_lengkap']) ?>" autocomplete="name" required>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="noHp">Nomor HP / WhatsApp</label>
              <input type="tel" name="no_hp" id="noHp" class="form-control form-control-lg" value="<?= htmlspecialchars($user['no_hp']) ?>" autocomplete="tel" required>
            </div>
            <div class="col-12">
              <label class="form-label" for="catatan">Catatan <span class="facility-optional-label">Opsional</span></label>
              <textarea name="catatan" id="catatan" class="form-control" rows="3" placeholder="Tuliskan waktu kunjungan atau permintaan khusus"></textarea>
            </div>
          </div>
        </section>
      </form>
    </div>
    <div class="col-lg-4">
      <aside class="facility-order-summary">
        <div class="facility-summary-top">
          <span class="facility-summary-icon"><i class="bi bi-receipt-cutoff"></i></span>
          <span class="facility-summary-tag"><i class="bi bi-shield-check me-1"></i> Pesanan aman</span>
        </div>
        <h2>Ringkasan pesanan</h2>
        <p class="facility-summary-caption">Rincian biaya akan diperbarui otomatis.</p>
        <div class="facility-summary-selection">
          <span id="summaryFacilityName">Pilih fasilitas</span>
          <small id="summaryHotelName">Hotel belum dipilih</small>
        </div>
        <div class="facility-summary-line"><span>Harga / <span id="summaryUnitLabel">unit</span></span><strong id="hargaPerOrang">Rp 0</strong></div>
        <div class="facility-summary-line"><span>Jumlah</span><strong><span id="summaryPax">1</span> <span id="summaryUnit">unit</span></strong></div>
        <div class="facility-summary-total"><span>Total pembayaran</span><strong id="totalFasilitas">Rp 0</strong></div>
        <button type="submit" form="facilityBookingForm" class="btn btn-facility-submit w-100" id="submitFacility">
          Lanjutkan pemesanan <i class="bi bi-arrow-right ms-1"></i>
        </button>
        <div class="facility-summary-note"><i class="bi bi-info-circle me-1"></i> Kode pesanan dapat digunakan untuk melihat status.</div>
      </aside>
    </div>
  </div>
</main>
<script>
const facilities = <?= json_encode($facilities) ?>;
const preselectedFacility = <?= $fasilitasId > 0 ? $fasilitasId : 'null' ?>;
const rupiah = value => 'Rp ' + Number(value || 0).toLocaleString('id-ID');

function renderFacilities() {
  const hotelId = document.getElementById('pesanHotel').value;
  const select = document.getElementById('pesanFasilitas');
  const emptyState = document.getElementById('facilityEmptyState');
  const hotelFacilities = facilities.filter(f => String(f.hotel_id) === hotelId);
  select.innerHTML = '<option value="">-- Pilih Fasilitas --</option>';
  hotelFacilities.forEach(f => {
    const option = document.createElement('option');
    option.value = f.id;
    option.dataset.harga = f.harga_fasilitas;
    option.textContent = f.nama_fasilitas + ' - ' + rupiah(f.harga_fasilitas) + ' / ' + f.satuan;
    option.selected = preselectedFacility === Number(f.id);
    select.appendChild(option);
  });
  emptyState.classList.toggle('d-none', !hotelId || hotelFacilities.length > 0);
  select.disabled = !hotelId || hotelFacilities.length === 0;
  document.getElementById('submitFacility').disabled = hotelFacilities.length === 0;
  hitung();
}

function hitung() {
  const select = document.getElementById('pesanFasilitas');
  const option = select.selectedOptions[0];
  const selected = facilities.find(f => String(f.id) === select.value);
  const pax = parseInt(document.getElementById('jumlahPax').value) || 1;
  const harga = option && option.dataset.harga ? parseFloat(option.dataset.harga) : 0;
  const unitName = selected && selected.satuan ? selected.satuan.replace(/^per\s+/i, '') : 'unit';
  document.getElementById('totalFasilitas').textContent = rupiah(harga * pax);
  document.getElementById('hargaPerOrang').textContent = rupiah(harga);
  document.getElementById('summaryPax').textContent = pax;
  document.getElementById('summaryUnit').textContent = unitName;
  document.getElementById('summaryUnitLabel').textContent = unitName;
  document.getElementById('jumlahUnitLabel').textContent = unitName;
  document.getElementById('summaryFacilityName').textContent = selected ? selected.nama_fasilitas : 'Pilih fasilitas';
  document.getElementById('summaryHotelName').textContent = selected ? selected.nama_hotel : 'Hotel belum dipilih';

  const preview = document.getElementById('facilityPreview');
  if (!selected) {
    preview.innerHTML = '<div class="facility-preview-placeholder"><span><i class="bi bi-building"></i></span><div><strong>Fasilitas pilihan Anda akan tampil di sini</strong><small>Pilih hotel dan fasilitas untuk melihat harga serta detailnya.</small></div></div>';
    return;
  }

  preview.replaceChildren();
  const image = document.createElement('img');
  image.src = selected.foto || 'assets/img/reservation.jpg';
  image.alt = selected.nama_fasilitas;
  image.className = 'facility-preview-image';

  const details = document.createElement('div');
  details.className = 'facility-preview-details';
  const category = document.createElement('span');
  category.className = 'badge-facility-cat';
  category.textContent = selected.kategori;
  const name = document.createElement('h3');
  name.textContent = selected.nama_fasilitas;
  const description = document.createElement('p');
  description.textContent = selected.deskripsi || 'Fasilitas pilihan tersedia untuk kunjungan Anda.';
  const meta = document.createElement('div');
  meta.className = 'facility-preview-meta';
  const hotelName = document.createElement('span');
  hotelName.innerHTML = '<i class="bi bi-building"></i> ';
  hotelName.append(document.createTextNode(selected.nama_hotel));
  const hours = document.createElement('span');
  hours.innerHTML = '<i class="bi bi-clock"></i> ';
  hours.append(document.createTextNode(selected.jam_operasional || 'Jam operasional mengikuti hotel'));
  meta.append(hotelName, hours);
  details.append(category, name, description, meta);

  const price = document.createElement('div');
  price.className = 'facility-preview-price';
  const priceLabel = document.createElement('small');
  priceLabel.textContent = 'Harga / ' + unitName;
  const amount = document.createElement('strong');
  amount.textContent = rupiah(harga);
  const unit = document.createElement('small');
  unit.textContent = '/ ' + selected.satuan;
  price.append(priceLabel, amount, unit);
  preview.append(image, details, price);
}

document.getElementById('pesanHotel').addEventListener('change', renderFacilities);
document.getElementById('pesanFasilitas').addEventListener('change', hitung);
document.getElementById('jumlahPax').addEventListener('input', hitung);
document.getElementById('jumlahPax').addEventListener('change', event => {
  const input = event.currentTarget;
  input.value = Math.max(1, Math.min(50, parseInt(input.value) || 1));
  hitung();
});
renderFacilities();
</script>
<script src="assets/js/luxury-motion.js" defer></script>
</body>
</html>

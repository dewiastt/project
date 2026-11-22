<?php
require_once 'config.php';
ensureUsersTable();

$preHotelId = (int)($_GET['hotel_id'] ?? 0);
$preFasId   = (int)($_GET['fas_id'] ?? 0);
$redirectUrl = 'booking.php' . ($preHotelId > 0 ? '?hotel_id=' . $preHotelId . ($preFasId > 0 ? '&fas_id=' . $preFasId : '') : '');

requireUserMasuk($redirectUrl);

$conn = getConnection();
$user = currentUser();

$allHotelsData = [];
$qAll = $conn->query("SELECT h.* FROM hotels h
    WHERE h.status = 'aktif'
    AND h.id = (SELECT MIN(h2.id) FROM hotels h2
                WHERE h2.nama_hotel = h.nama_hotel AND h2.status = 'aktif')
    ORDER BY h.nama_hotel ASC");
while ($h = $qAll->fetch_assoc()) {
    $hid = (int)$h['id'];
    $kamars = [];
    $qK = $conn->query("SELECT * FROM kamar WHERE hotel_id = $hid ORDER BY harga_per_malam ASC");
    while ($k = $qK->fetch_assoc()) { $kamars[] = $k; }

    $fasilitas = [];
    $qF = $conn->query("SELECT * FROM fasilitas WHERE hotel_id = $hid ORDER BY harga_fasilitas ASC");
    while ($f = $qF->fetch_assoc()) { $fasilitas[] = $f; }

    $allHotelsData[$hid] = [
        'info' => $h,
        'kamar' => $kamars,
        'fasilitas' => $fasilitas
    ];
}

$selectedHotel = ($preHotelId > 0 && isset($allHotelsData[$preHotelId])) ? $allHotelsData[$preHotelId]['info'] : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Halaman Pemesanan — SangattaStay</title>
  <link href="assets/img/favicon.png" rel="icon">
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/sangatta-stay.css" rel="stylesheet">
  <link href="assets/css/luxury-theme.css" rel="stylesheet">
</head>
<body>

<nav class="navbar site-nav sticky-top py-3 mb-4">
  <div class="container d-flex justify-content-between align-items-center">
    <a href="index.php" class="brand-logo"><span class="dot"></span><span>SangattaStay</span></a>
    <div class="d-flex align-items-center gap-2 small">
      <span class="text-muted d-none d-sm-inline"><?= htmlspecialchars($user['nama_lengkap']) ?></span>
      <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-3">Daftar Hotel</a>
      <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-3">Keluar</a>
    </div>
  </div>
</nav>

<main class="container pb-5">
  <div class="panel-surface p-4 mb-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <span class="pill-pine sans-label mb-1 d-inline-block">Halaman Pemesanan</span>
      <h1 class="h3 fw-bold mb-0" id="bannerHotelTitle">
        <?= $selectedHotel ? htmlspecialchars($selectedHotel['nama_hotel']) : 'Pilih Hotel di Sangatta' ?>
      </h1>
      <div class="small text-muted" id="bannerHotelMeta">
        <?= $selectedHotel ? htmlspecialchars($selectedHotel['alamat']) . ' • ' . htmlspecialchars($selectedHotel['kecamatan']) : 'Pilih hotel, tipe kamar, dan fasilitas berbayar di bawah' ?>
      </div>
    </div>
    <div class="d-flex gap-2">
      <a href="pesan-fasilitas.php" class="btn btn-outline-success rounded-3 btn-sm px-3 py-2">Pesan Fasilitas Saja</a>
      <a href="index.php#daftar-hotel" class="btn btn-outline-secondary rounded-3 btn-sm px-3 py-2">Ganti Hotel</a>
    </div>
  </div>

  <?php if (isset($_GET['status']) && $_GET['status'] === 'sukses'): ?>
    <div class="alert alert-success rounded-4 d-flex justify-content-between align-items-center mb-4">
      <div>
        <strong>Pemesanan berhasil!</strong> Kode Pemesanan: <code><?= htmlspecialchars($_GET['kode'] ?? '') ?></code>
      </div>
      <a href="track-reservasi.php?kode=<?= urlencode($_GET['kode'] ?? '') ?>" class="btn btn-success btn-sm rounded-3">Lihat E-Ticket</a>
    </div>
  <?php endif; ?>

  <form action="forms/reservasi.php" method="POST">
    <div class="row g-4">
      <div class="col-lg-8">
        <div class="panel-surface p-4 mb-4">
          <h2 class="h5 fw-bold mb-3">1. Hotel, Kamar &amp; Jadwal Menginap</h2>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Hotel di Sangatta *</label>
              <select name="hotel_id" id="selectHotel" class="form-select rounded-3" required onchange="renderHotelOptions()">
                <option value="">-- Pilih Hotel --</option>
                <?php foreach ($allHotelsData as $hid => $item): ?>
                  <option value="<?= $hid ?>" <?= $preHotelId === $hid ? 'selected' : '' ?>>
                    <?= htmlspecialchars($item['info']['nama_hotel']) ?> (<?= htmlspecialchars($item['info']['kecamatan']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Tipe Kamar *</label>
              <select name="kamar_id" id="selectKamar" class="form-select rounded-3" required onchange="hitungTotal()">
                <option value="">-- Pilih Hotel Terlebih Dahulu --</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Jumlah Kamar *</label>
              <input type="number" name="jumlah_kamar" id="jumlahKamar" class="form-control rounded-3" value="1" min="1" max="10" required onchange="hitungTotal()">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Kasur Tambahan</label>
              <select name="jumlah_extra_bed" id="jumlahExtraBed" class="form-select rounded-3" onchange="hitungTotal()">
                <option value="0">Tidak ditambahkan</option>
              </select>
              <div class="small text-muted mt-1" id="extraBedInfo">Pilih tipe kamar terlebih dahulu</div>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Tanggal Masuk *</label>
              <input type="date" name="tanggal_checkin" id="tglCheckin" class="form-control rounded-3" value="<?= date('Y-m-d') ?>" required onchange="hitungTotal()">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Tanggal Keluar *</label>
              <input type="date" name="tanggal_checkout" id="tglCheckout" class="form-control rounded-3" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" required onchange="hitungTotal()">
            </div>
          </div>
        </div>

        <div class="panel-surface p-4 mb-4">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h5 fw-bold mb-0">2. Fasilitas Berbayar Opsional</h2>
            <span class="small text-muted">Centang fasilitas yang dipakai</span>
          </div>
          <div id="containerFasilitas" class="row g-2 mt-1">
            <div class="col-12 small text-muted">Pilih hotel terlebih dahulu untuk menampilkan fasilitas berbayar.</div>
          </div>
        </div>

        <div class="panel-surface p-4">
          <h2 class="h5 fw-bold mb-3">3. Data Tamu &amp; Pembayaran</h2>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Nama Tamu *</label>
              <input type="text" name="nama_tamu" class="form-control rounded-3" value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Nomor HP / WhatsApp *</label>
              <input type="text" name="no_hp" class="form-control rounded-3" value="<?= htmlspecialchars($user['no_hp']) ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Metode Pembayaran</label>
              <select name="metode_bayar" class="form-select rounded-3">
                <option value="QRIS">QRIS</option>
                <option value="Transfer Bank">Transfer Bank</option>
                <option value="Bayar di Resepsionis">Bayar di Resepsionis</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold">Catatan (Opsional)</label>
              <input type="text" name="catatan" class="form-control rounded-3" placeholder="Catatan tambahan untuk hotel">
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="summary-pine-card p-4">
          <span class="badge bg-light text-dark mb-2">Ringkasan Biaya</span>
          <h2 class="h5 fw-bold text-white mb-3">Rincian Reservasi</h2>
          <div class="small" style="opacity:0.75;">Hotel</div>
          <div class="fw-semibold mb-2" id="summaryHotelName">-</div>
          <div class="small mb-3" style="opacity:0.85;" id="summaryDurasi">1 Malam &bull; 1 Kamar</div>

          <div class="d-flex justify-content-between small py-2 border-top" style="border-color:rgba(255,255,255,0.18) !important;">
            <span style="opacity:0.85;">Subtotal Kamar</span>
            <strong id="rincianKamar">Rp 0</strong>
          </div>
          <div class="d-flex justify-content-between small py-2 border-top border-bottom mb-3" style="border-color:rgba(255,255,255,0.18) !important;">
            <span style="opacity:0.85;">Fasilitas (<span id="jmlFasDipilih">0</span>)</span>
            <strong id="rincianFasilitas">Rp 0</strong>
          </div>
          <div class="d-flex justify-content-between small py-2 border-bottom mb-3" style="border-color:rgba(255,255,255,0.18) !important;">
            <span style="opacity:0.85;">Kasur Tambahan</span>
            <strong id="rincianExtraBed">Rp 0</strong>
          </div>

          <div class="d-flex justify-content-between align-items-baseline mb-4">
            <span class="fw-semibold">Total</span>
            <span class="fs-4 fw-bold" style="color:#fcd34d;" id="totalBayarDisplay">Rp 0</span>
          </div>

          <button type="submit" class="btn btn-terracotta w-100 py-2.5">
            Kirim Permintaan Pemesanan
          </button>
        </div>
      </div>
    </div>
  </form>
</main>

<script>
const hotelsData = <?= json_encode($allHotelsData) ?>;
let preselectedFasId = <?= $preFasId > 0 ? $preFasId : 'null' ?>;

function formatRp(num) {
  return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
}

function renderHotelOptions() {
  const hid = document.getElementById('selectHotel').value;
  const selKamar = document.getElementById('selectKamar');
  const contFas = document.getElementById('containerFasilitas');
  const sumHotel = document.getElementById('summaryHotelName');
  const bannerTitle = document.getElementById('bannerHotelTitle');
  const bannerMeta = document.getElementById('bannerHotelMeta');

  selKamar.innerHTML = '<option value="">-- Pilih Tipe Kamar --</option>';
  contFas.innerHTML = '';

  if (!hid || !hotelsData[hid]) {
    sumHotel.textContent = '-';
    bannerTitle.textContent = 'Pilih Hotel di Sangatta';
    bannerMeta.textContent = 'Pilih hotel, tipe kamar, dan fasilitas berbayar di bawah';
    contFas.innerHTML = '<div class="col-12 small text-muted">Pilih hotel terlebih dahulu.</div>';
    hitungTotal();
    return;
  }

  const hInfo = hotelsData[hid].info;
  sumHotel.textContent = hInfo.nama_hotel;
  bannerTitle.textContent = hInfo.nama_hotel;
  bannerMeta.textContent = `${hInfo.alamat} • ${hInfo.kecamatan}`;

  hotelsData[hid].kamar.forEach(k => {
    const opt = document.createElement('option');
    opt.value = k.id;
    opt.dataset.harga = k.harga_per_malam;
    opt.dataset.extraBedAvailable = k.extra_bed_tersedia;
    opt.dataset.extraBedPrice = k.harga_extra_bed;
    opt.textContent = `${k.nama_kamar} — ${formatRp(k.harga_per_malam)}/malam (Maks ${k.kapasitas} tamu)`;
    selKamar.appendChild(opt);
  });

  if (hotelsData[hid].fasilitas.length === 0) {
    contFas.innerHTML = '<div class="col-12 small text-muted">Tidak ada fasilitas tambahan berbayar.</div>';
  } else {
    hotelsData[hid].fasilitas.forEach(f => {
      const checked = (preselectedFasId == f.id) ? 'checked' : '';
      const col = document.createElement('div');
      col.className = 'col-md-6';
      col.innerHTML = `
        <div class="addon-pick ${checked ? 'selected' : ''}" id="pick_${f.id}">
          <div class="d-flex justify-content-between align-items-center gap-2">
            <div class="form-check m-0">
              <input class="form-check-input fas-check" type="checkbox" name="fasilitas_ids[]"
                     value="${f.id}" id="fas_${f.id}" data-harga="${f.harga_fasilitas}" ${checked}
                     onchange="togglePick(${f.id}); hitungTotal();">
              <label class="form-check-label small fw-semibold" for="fas_${f.id}">
                ${f.nama_fasilitas}
                <span class="d-block" style="color:var(--pine);">${formatRp(f.harga_fasilitas)} / ${f.satuan}</span>
              </label>
            </div>
            <div style="width:70px;">
              <label class="form-label mb-0" style="font-size:0.7rem;">Jumlah Orang</label>
              <input type="number" name="fasilitas_pax[${f.id}]" class="form-control form-control-sm fas-pax"
                     data-fid="${f.id}" value="1" min="1" max="50" onchange="hitungTotal()">
            </div>
          </div>
        </div>
      `;
      contFas.appendChild(col);
    });
  }

  preselectedFasId = null;
  hitungTotal();
}

function togglePick(fid) {
  const cb = document.getElementById('fas_' + fid);
  const pick = document.getElementById('pick_' + fid);
  if (cb && pick) {
    pick.classList.toggle('selected', cb.checked);
  }
}

function hitungTotal() {
  const selKamar = document.getElementById('selectKamar');
  const opt = selKamar.options[selKamar.selectedIndex];
  const hargaKamar = opt && opt.dataset.harga ? parseFloat(opt.dataset.harga) : 0;
  const extraAvailable = opt ? parseInt(opt.dataset.extraBedAvailable || '0') === 1 : false;
  const extraPrice = opt && opt.dataset.extraBedPrice ? parseFloat(opt.dataset.extraBedPrice) : 0;
  const extraSelect = document.getElementById('jumlahExtraBed');
  const extraInfo = document.getElementById('extraBedInfo');
  const selectedExtra = parseInt(extraSelect.value) || 0;

  if (extraSelect.dataset.availability !== (extraAvailable ? '1' : '0')) {
    const previous = selectedExtra;
    extraSelect.innerHTML = extraAvailable
      ? '<option value="0">Tidak ditambahkan</option><option value="1">1 Kasur Tambahan</option><option value="2">2 Kasur Tambahan</option>'
      : '<option value="0">Tidak tersedia</option>';
    if (extraAvailable && previous <= 2) extraSelect.value = String(previous);
    extraSelect.dataset.availability = extraAvailable ? '1' : '0';
  }
  extraSelect.disabled = !extraAvailable;
  extraInfo.textContent = extraAvailable ? formatRp(extraPrice) + ' / kasur / malam' : 'Kasur tambahan tidak tersedia';
  const jmlExtra = extraAvailable ? (parseInt(extraSelect.value) || 0) : 0;
  const jmlKamar = parseInt(document.getElementById('jumlahKamar').value) || 1;

  const tIn = new Date(document.getElementById('tglCheckin').value);
  const tOut = new Date(document.getElementById('tglCheckout').value);
  let malam = 1;
  if (!isNaN(tIn) && !isNaN(tOut) && tOut > tIn) {
    malam = Math.ceil((tOut - tIn) / 86400000);
  }

  document.getElementById('summaryDurasi').textContent = `${malam} Malam • ${jmlKamar} Kamar`;
  const subKamar = hargaKamar * jmlKamar * malam;
  const subExtraBed = jmlExtra * extraPrice * malam;

  let subFas = 0;
  let countFas = 0;
  document.querySelectorAll('.fas-check:checked').forEach(cb => {
    countFas++;
    const harga = parseFloat(cb.dataset.harga) || 0;
    const paxEl = document.querySelector(`.fas-pax[data-fid="${cb.value}"]`);
    const pax = paxEl ? (parseInt(paxEl.value) || 1) : 1;
    subFas += harga * pax;
  });

  document.getElementById('jmlFasDipilih').textContent = countFas;
  document.getElementById('rincianKamar').textContent = formatRp(subKamar);
  document.getElementById('rincianFasilitas').textContent = formatRp(subFas);
  document.getElementById('rincianExtraBed').textContent = formatRp(subExtraBed);
  document.getElementById('totalBayarDisplay').textContent = formatRp(subKamar + subFas + subExtraBed);
}

window.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('selectHotel').value) {
    renderHotelOptions();
  }
});
</script>
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/luxury-motion.js" defer></script>
</body>
</html>

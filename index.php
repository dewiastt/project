<?php
require_once 'config.php';
$conn = getConnection();

$keyword   = trim($_GET['q'] ?? '');
$kecamatan = trim($_GET['kecamatan'] ?? '');

ensureTestimoniTable();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_testimoni') {
    $nama = trim($_POST['nama'] ?? '');
    $testimoni = trim($_POST['testimoni'] ?? '');
    $tanggal = date('Y-m-d');

    if ($nama === '' || $testimoni === '') {
        setError('Nama dan testimoni wajib diisi.');
        header('Location: index.php#testimoni');
        exit;
    }

    $rating = (int)($_POST['rating'] ?? 5);
    if ($rating < 1 || $rating > 5) {
        $rating = 5;
    }

    $stmt = $conn->prepare("INSERT INTO testimoni (nama, testimoni, rating, tanggal) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssis', $nama, $testimoni, $rating, $tanggal);
    if ($stmt->execute()) {
        setSuccess('Testimoni berhasil dikirim dan langsung ditampilkan.');
    } else {
        setError('Testimoni gagal dikirim.');
    }
    $stmt->close();
    header('Location: index.php#testimoni');
    exit;
}

$where = "WHERE h.status = 'aktif'
    AND h.id = (SELECT MIN(h2.id) FROM hotels h2
                WHERE h2.nama_hotel = h.nama_hotel AND h2.status = 'aktif')";
if ($keyword !== '') {
    $kw = $conn->real_escape_string($keyword);
    $where .= " AND (h.nama_hotel LIKE '%$kw%' OR h.alamat LIKE '%$kw%' OR h.deskripsi LIKE '%$kw%')";
}
if ($kecamatan !== '') {
    $kec = $conn->real_escape_string($kecamatan);
    $where .= " AND h.kecamatan = '$kec'";
}

$hotels = $conn->query("SELECT h.* FROM hotels h $where ORDER BY h.bintang DESC, h.nama_hotel ASC");

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

$totalHotelCount = $conn->query("SELECT COUNT(DISTINCT nama_hotel) AS c FROM hotels WHERE status='aktif'")->fetch_assoc()['c'];
$totalFasCount   = $conn->query("SELECT COUNT(*) AS c FROM fasilitas")->fetch_assoc()['c'];
ensureAssetMappings();
ensureKontakTable();
$testimonials = $conn->query("SELECT nama, testimoni, rating, tanggal FROM testimoni ORDER BY tanggal DESC, id DESC");
$kontak = $conn->query("SELECT * FROM kontak ORDER BY id ASC LIMIT 1")->fetch_assoc();
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>SangattaStay — Reservasi Kamar Hotel &amp; Fasilitas Pilihan di Sangatta</title>
  <link href="assets/img/favicon.png" rel="icon">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/sangatta-stay.css?v=20261122" rel="stylesheet">
  <link href="assets/css/luxury-theme.css?v=20261122" rel="stylesheet">
</head>
<body>

<!-- NAVBAR (Tanpa Panel Admin) -->
<nav class="navbar navbar-expand-lg navbar-stay sticky-top py-3">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2 fs-4" href="index.php">
      <span class="brand-mark"><i class="bi bi-building-check"></i></span>
      <span>Sangatta<span style="color:#fbbf24;">Stay</span></span>
    </a>
    <button class="navbar-toggler bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#navSangatta">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navSangatta">
      <ul class="navbar-nav mx-auto gap-lg-2">
        <li class="nav-item"><a class="nav-link active" href="#daftar-hotel">Hotel Sangatta</a></li>
        <li class="nav-item"><a class="nav-link" href="#fasilitas-opsional">Fasilitas Berbayar</a></li>
        <li class="nav-item"><a class="nav-link" href="#testimoni">Testimoni</a></li>
        <li class="nav-item"><a class="nav-link" href="pesan-fasilitas.php">Pesan Fasilitas</a></li>
        <?php if ($user): ?>
          <li class="nav-item"><a class="nav-link" href="track-reservasi.php">Cek E-Ticket</a></li>
        <?php endif; ?>
      </ul>
      <div class="d-flex gap-2 mt-2 mt-lg-0">
        <?php if ($user): ?>
          <a href="track-reservasi.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
            <i class="bi bi-ticket-detailed me-1"></i> E-Ticket
          </a>
          <a href="logout.php" class="btn btn-amber-action btn-sm rounded-pill px-4">
            <i class="bi bi-person-check-fill me-1"></i> <?= htmlspecialchars($user['nama_lengkap']) ?> (Keluar)
          </a>
        <?php else: ?>
          <a href="login.php" class="btn btn-amber-action btn-sm rounded-pill px-4">
            <i class="bi bi-box-arrow-in-right me-1"></i> Login
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<!-- HERO -->
<header class="hero-sangatta">
  <div class="container">
    <div class="row align-items-center g-4">
      <div class="col-lg-8">
        <div class="hero-badge mb-3">
          <i class="bi bi-geo-alt-fill"></i> Direktori Hotel Sangatta, Kutai Timur
        </div>
        <h1 class="display-5 fw-bold mb-3">
          Pengalaman Menginap Berkelas <span class="hero-highlight">di Sangatta</span>
        </h1>
        <p class="lead mb-4" style="color: rgba(255,255,255,0.85);">
          Pilih kamar yang tepat, jelajahi fasilitas hotel, dan pesan pengalaman menginap Anda dengan mudah bersama SangattaStay.
        </p>
        <div class="d-flex flex-wrap gap-2">
          <a href="#daftar-hotel" class="btn btn-amber-action rounded-pill px-4 py-2">
            Jelajahi Kamar <i class="bi bi-arrow-down-right ms-1"></i>
          </a>
          <a href="pesan-fasilitas.php" class="btn btn-outline-light rounded-pill px-4 py-2">
            Pesan Fasilitas
          </a>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="row g-2">
          <div class="col-6">
            <div class="hero-stat-pill">
              <div class="fs-3 fw-bold" style="color:#fbbf24;"><?= (int)$totalHotelCount ?>+</div>
              <div class="small" style="color:rgba(255,255,255,0.8);">Hotel di Sangatta</div>
            </div>
          </div>
          <div class="col-6">
            <div class="hero-stat-pill">
              <div class="fs-3 fw-bold" style="color:#34d399;"><?= (int)$totalFasCount ?>+</div>
              <div class="small" style="color:rgba(255,255,255,0.8);">Fasilitas Tambahan</div>
            </div>
          </div>
          <div class="col-12">
            <div class="hero-stat-pill d-flex align-items-center gap-3">
              <i class="bi bi-sliders2-vertical fs-3" style="color:#fbbf24;"></i>
              <div class="small">
                <strong class="d-block text-white">Bayar Sesuai Pilihan</strong>
                <span style="color:rgba(255,255,255,0.8);">Kamar + centang fasilitas yang dipakai saja</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- FLOATING SEARCH BAR -->
<div class="container">
  <div class="search-floating-card p-4">
    <form method="GET" action="#daftar-hotel" class="row g-3 align-items-end">
      <div class="col-md-5">
        <label class="form-label small fw-bold" style="color:var(--navy-mid);">CARI HOTEL / JALAN DI SANGATTA</label>
        <div class="input-group">
          <span class="input-group-text bg-white border-end-0"><i class="bi bi-search" style="color:var(--royal-indigo);"></i></span>
          <input type="text" name="q" class="form-control border-start-0" placeholder="Royal Victoria, Mesfa Mulia, Q Hotel, Yos Sudarso..." value="<?= htmlspecialchars($keyword) ?>">
        </div>
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-bold" style="color:var(--navy-mid);">KECAMATAN / KAWASAN</label>
        <select name="kecamatan" class="form-select">
          <option value="">Semua Kawasan Sangatta</option>
          <option value="Sangatta Utara" <?= $kecamatan==='Sangatta Utara'?'selected':'' ?>>Sangatta Utara</option>
          <option value="Teluk Lingga" <?= $kecamatan==='Teluk Lingga'?'selected':'' ?>>Teluk Lingga</option>
          <option value="Swarga Bara" <?= $kecamatan==='Swarga Bara'?'selected':'' ?>>Swarga Bara</option>
          <option value="Sangatta Selatan" <?= $kecamatan==='Sangatta Selatan'?'selected':'' ?>>Sangatta Selatan</option>
        </select>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-royal w-100 py-2 rounded-3">Cari Hotel</button>
        <?php if ($keyword !== '' || $kecamatan !== ''): ?>
          <a href="index.php" class="btn btn-outline-secondary py-2 rounded-3" title="Reset"><i class="bi bi-x-lg"></i></a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<!-- DAFTAR HOTEL DI SANGATTA -->
<section id="daftar-hotel" class="py-5 mt-2">
  <div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-end mb-4">
      <div>
        <span class="section-kicker">Akomodasi Sangatta</span>
        <h2 class="fw-bold mb-1">Pilihan Hotel Menginap di Sangatta</h2>
        <p class="text-muted mb-0">Klik <strong>Pilih Penginapan</strong> untuk memesan kamar dan memilih fasilitas berbayar yang Anda inginkan.</p>
      </div>
      <span class="badge bg-white text-dark border px-3 py-2 rounded-pill shadow-sm">
        <?= $hotels->num_rows ?> Hotel Tersedia
      </span>
    </div>

    <div class="row g-4">
      <?php if ($hotels->num_rows > 0): ?>
        <?php while ($hotel = $hotels->fetch_assoc()):
          $hid = (int)$hotel['id'];
          $minKamar = $conn->query("SELECT MIN(harga_per_malam) AS m FROM kamar WHERE hotel_id=$hid")->fetch_assoc()['m'];
          $jmlFas   = $conn->query("SELECT COUNT(*) AS c FROM fasilitas WHERE hotel_id=$hid")->fetch_assoc()['c'];
        ?>
        <div class="col-md-6 col-lg-3">
          <div class="hotel-card-stay h-100 d-flex flex-column">
            <div class="hotel-thumb">
              <?php if (isLocalImageUsable($hotel['foto_utama'])): ?>
                <img src="<?= assetUrl($hotel['foto_utama']) ?>" alt="<?= htmlspecialchars($hotel['nama_hotel']) ?>" loading="lazy">
              <?php else: ?>
                <div class="lux-photo-fallback lux-photo-hotel" role="img" aria-label="Ilustrasi <?= htmlspecialchars($hotel['nama_hotel']) ?>"></div>
              <?php endif; ?>
              <span class="hotel-area-tag"><i class="bi bi-pin-map-fill me-1"></i><?= htmlspecialchars($hotel['kecamatan']) ?></span>
            </div>
            <div class="p-4 d-flex flex-column flex-grow-1">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-warning small"><?= str_repeat('<i class="bi bi-star-fill"></i>', (int)$hotel['bintang']) ?></span>
                <small class="text-muted"><?= htmlspecialchars($hotel['jarak_pusat']) ?></small>
              </div>
              <h5 class="fw-bold mb-1"><?= htmlspecialchars($hotel['nama_hotel']) ?></h5>
              <p class="small text-muted mb-2"><?= htmlspecialchars($hotel['alamat']) ?></p>
              <p class="small text-secondary flex-grow-1"><?= htmlspecialchars($hotel['deskripsi']) ?></p>

              <div class="hotel-price-box mb-3">
                <div class="d-flex justify-content-between small">
                  <span class="text-muted">Kamar mulai</span>
                  <strong style="color:var(--royal-indigo);"><?= $minKamar ? rupiah($minKamar).'/mlm' : 'Hubungi Hotel' ?></strong>
                </div>
                <div class="d-flex justify-content-between small mt-1">
                  <span class="text-muted">Fasilitas opsional</span>
                  <span class="fw-semibold" style="color:var(--teal-mint);"><?= (int)$jmlFas ?> fasilitas bisa dipilih</span>
                </div>
              </div>

              <a href="detail-hotel.php?hotel_id=<?= $hid ?>" class="btn btn-royal w-100 rounded-pill py-2">
                <i class="bi bi-door-open me-1"></i> Lihat Kamar &amp; Detail
              </a>
            </div>
          </div>
        </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="col-12">
          <div class="alert alert-warning rounded-4">Tidak ada hotel di Sangatta yang cocok dengan filter tersebut.</div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- KATALOG FASILITAS BERBAYAR OPSIONAL SAAT MENGINAP -->
<section id="fasilitas-opsional" class="facility-showcase-section py-5">
  <div class="container">
    <div class="mb-4">
      <span class="section-kicker"><i class="bi bi-check2-square me-1"></i> Fasilitas Berbayar</span>
      <h2 class="fw-bold mb-1">Pilih Tambahan Saat Menginap atau Pesan Secara Mandiri</h2>
      <p class="text-muted mb-0">Fasilitas seperti kolam renang, sarapan, gym, atau area bermain dapat ditambahkan saat menginap atau dibayar sendiri tanpa check-in kamar.</p>
    </div>

    <div class="row g-3">
      <?php
      $qFasAll = $conn->query("
        SELECT f.*, h.nama_hotel 
        FROM fasilitas f 
        JOIN hotels h ON f.hotel_id = h.id 
        WHERE h.status = 'aktif'
          AND h.id = (SELECT MIN(h2.id) FROM hotels h2
                      WHERE h2.nama_hotel = h.nama_hotel AND h2.status = 'aktif')
        ORDER BY f.kategori ASC, f.harga_fasilitas ASC
      ");
      while ($fp = $qFasAll->fetch_assoc()):
      ?>
      <div class="col-md-6 col-lg-4">
        <div class="facility-card-item p-3 h-100 d-flex flex-column">
          <div class="d-flex gap-3 align-items-center mb-2">
            <?php if (isLocalImageUsable($fp['foto'])): ?>
              <img src="<?= assetUrl($fp['foto']) ?>" class="rounded-3 border" style="width:72px; height:72px; object-fit:cover;" alt="" loading="lazy">
            <?php else: ?>
              <span class="facility-icon-thumb" aria-hidden="true"><i class="bi bi-stars"></i></span>
            <?php endif; ?>
            <div>
              <span class="badge-facility-cat mb-1 d-inline-block"><?= htmlspecialchars($fp['kategori']) ?></span>
              <h6 class="fw-bold mb-0"><?= htmlspecialchars($fp['nama_fasilitas']) ?></h6>
              <small class="text-muted"><i class="bi bi-building me-1"></i><?= htmlspecialchars($fp['nama_hotel']) ?></small>
            </div>
          </div>
          <p class="small text-secondary flex-grow-1 mb-3"><?= htmlspecialchars($fp['deskripsi']) ?></p>
          <div class="d-flex justify-content-between align-items-center border-top pt-2">
            <div>
              <span class="fw-bold" style="color:var(--navy-deep);"><?= rupiah($fp['harga_fasilitas']) ?></span>
              <small class="text-muted">/ <?= htmlspecialchars($fp['satuan']) ?></small>
              <div class="small text-muted"><i class="bi bi-clock me-1"></i><?= htmlspecialchars($fp['jam_operasional']) ?></div>
            </div>
            <div class="d-flex flex-column gap-1 align-items-end">
              <a href="pesan-fasilitas.php?hotel_id=<?= (int)$fp['hotel_id'] ?>&fas_id=<?= (int)$fp['id'] ?>" class="btn btn-sm rounded-pill px-3 fw-semibold text-decoration-none" style="background:var(--teal-soft); color:var(--teal-mint);">
                Pesan Mandiri
              </a>
              <a href="detail-hotel.php?hotel_id=<?= (int)$fp['hotel_id'] ?>&fas_id=<?= (int)$fp['id'] ?>" class="small text-decoration-none text-muted">
                Tambah Saat Menginap
              </a>
            </div>
          </div>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
  </div>
</section>

<!-- TESTIMONI TAMU -->
<section id="testimoni" class="testimonial-section py-5">
  <div class="container">
    <div class="text-center mb-4">
      <span class="section-kicker">Cerita Tamu</span>
      <h2 class="fw-bold mb-1">Testimoni Pengunjung SangattaStay</h2>
      <p class="text-muted mb-0">Pengalaman tamu dalam memilih hotel dan fasilitas di Sangatta.</p>
    </div>
    <?php if (isset($_SESSION['success_message'])): ?>
      <div class="alert alert-success rounded-4"><?= htmlspecialchars($_SESSION['success_message']) ?></div>
      <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
      <div class="alert alert-danger rounded-4"><?= htmlspecialchars($_SESSION['error_message']) ?></div>
      <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    <div class="testimonial-scroll mb-4">
    <div class="row g-4">
      <?php if ($testimonials && $testimonials->num_rows > 0): ?>
        <?php while ($testimonial = $testimonials->fetch_assoc()):
          $initial = mb_strtoupper(mb_substr(trim($testimonial['nama']), 0, 1, 'UTF-8'), 'UTF-8');
          $rating = max(1, min(5, (int)($testimonial['rating'] ?? 5)));
        ?>
        <div class="col-12">
          <article class="testimonial-card h-100">
            <div class="testimonial-card-top">
              <span class="testimonial-avatar" aria-hidden="true"><?= htmlspecialchars($initial) ?></span>
              <div>
                <h5 class="mb-1"><?= htmlspecialchars($testimonial['nama']) ?></h5>
                <div class="testimonial-stars" aria-label="<?= $rating ?> dari 5 bintang">
                  <?php for ($star = 1; $star <= 5; $star++): ?>
                    <i class="bi <?= $star <= $rating ? 'bi-star-fill' : 'bi-star' ?>"></i>
                  <?php endfor; ?>
                </div>
              </div>
            </div>
            <i class="bi bi-quote testimonial-quote" aria-hidden="true"></i>
            <p class="testimonial-text mb-3"><?= nl2br(htmlspecialchars($testimonial['testimoni'])) ?></p>
            <div class="testimonial-date">
              <i class="bi bi-calendar3 me-1"></i><?= date('d M Y', strtotime($testimonial['tanggal'])) ?>
            </div>
          </article>
        </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="col-12">
          <div class="testimonial-empty text-center">
            <i class="bi bi-chat-heart fs-2 d-block mb-2"></i>
            Belum ada testimoni yang ditampilkan.
          </div>
        </div>
      <?php endif; ?>
    </div>
    </div>
    <div class="testimonial-form-card">
      <div class="row align-items-center g-4">
        <div class="col-lg-4">
          <span class="section-kicker">Bagikan Pengalaman</span>
          <h3 class="fw-bold mt-2 mb-2">Tulis Testimoni Anda</h3>
          <p class="text-muted mb-0">Cerita Anda akan langsung muncul pada daftar testimoni.</p>
        </div>
        <div class="col-lg-8">
          <form method="POST" action="index.php#testimoni" class="row g-3">
            <input type="hidden" name="action" value="submit_testimoni">
            <div class="col-md-4">
              <label class="form-label fw-semibold">Nama</label>
              <input type="text" name="nama" class="form-control" maxlength="120" placeholder="Nama Anda" required>
            </div>
            <div class="col-md-8">
              <label class="form-label fw-semibold">Testimoni</label>
              <textarea name="testimoni" class="form-control" rows="3" maxlength="1000" placeholder="Ceritakan pengalaman Anda" required></textarea>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold d-block mb-1">Penilaian</label>
              <div class="testimonial-rating" role="radiogroup" aria-label="Pilih jumlah bintang">
                <?php for ($value = 5; $value >= 1; $value--): ?>
                  <input type="radio" name="rating" id="rating<?= $value ?>" value="<?= $value ?>" <?= $value === 5 ? 'checked' : '' ?>>
                  <label for="rating<?= $value ?>" title="<?= $value ?> bintang"><i class="bi bi-star-fill"></i></label>
                <?php endfor; ?>
              </div>
            </div>
            <div class="col-12 text-end">
              <button type="submit" class="btn btn-royal rounded-pill px-4">
                <i class="bi bi-send me-1"></i>Kirim Testimoni
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<footer class="footer-stay py-4 mt-4">
  <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3 small">
    <div>&copy; <?= date('Y') ?> <strong class="text-white">SangattaStay</strong> — Reservasi Hotel &amp; Fasilitas Sangatta, Kutai Timur.</div>
    <div class="d-flex flex-wrap gap-3">
      <a href="tel:<?= urlencode($kontak['whatsapp'] ?? '(0098) 5674') ?>" class="text-white text-decoration-none"><i class="bi bi-telephone me-1 text-warning"></i><?= htmlspecialchars($kontak['whatsapp'] ?? '(0098) 5674') ?></a>
      <a href="mailto:<?= htmlspecialchars($kontak['email'] ?? 'sangattastay1890@gmail.com') ?>" class="text-white text-decoration-none"><i class="bi bi-envelope me-1 text-warning"></i><?= htmlspecialchars($kontak['email'] ?? 'sangattastay1890@gmail.com') ?></a>
      <a href="track-reservasi.php" class="text-warning text-decoration-none fw-semibold">Cek Status Pemesanan</a>
    </div>
  </div>
</footer>

<script>
const hotelsData = <?= json_encode($allHotelsData) ?>;
let preselectedFasId = null;

function formatRp(num) {
  return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
}

function renderHotelOptions() {
  const hid = document.getElementById('selectHotel').value;
  const selKamar = document.getElementById('selectKamar');
  const contFas = document.getElementById('containerFasilitas');

  selKamar.innerHTML = '<option value="">-- Pilih Tipe Kamar --</option>';
  contFas.innerHTML = '';

  if (!hid || !hotelsData[hid]) {
    contFas.innerHTML = '<div class="col-12 text-muted small fst-italic">Pilih hotel di Sangatta terlebih dahulu.</div>';
    hitungTotal();
    return;
  }

  hotelsData[hid].kamar.forEach(k => {
    const opt = document.createElement('option');
    opt.value = k.id;
    opt.dataset.harga = k.harga_per_malam;
    opt.textContent = `${k.nama_kamar} — ${formatRp(k.harga_per_malam)}/malam (Maks ${k.kapasitas} tamu)`;
    selKamar.appendChild(opt);
  });

  if (hotelsData[hid].fasilitas.length === 0) {
    contFas.innerHTML = '<div class="col-12 text-muted small">Hotel ini belum memiliki fasilitas tambahan berbayar.</div>';
  } else {
    hotelsData[hid].fasilitas.forEach(f => {
      const checked = (preselectedFasId == f.id) ? 'checked' : '';
      const col = document.createElement('div');
      col.className = 'col-md-6';
      col.innerHTML = `
        <div class="facility-option-tile ${checked ? 'selected' : ''}" id="tile_${f.id}">
          <div class="d-flex justify-content-between align-items-center gap-2">
            <div class="form-check m-0">
              <input class="form-check-input fas-check" type="checkbox" name="fasilitas_ids[]"
                     value="${f.id}" id="fas_${f.id}" data-harga="${f.harga_fasilitas}" ${checked}
                     onchange="toggleTile(${f.id}); hitungTotal();">
              <label class="form-check-label small fw-bold" for="fas_${f.id}">
                ${f.nama_fasilitas}
                <span class="d-block fw-semibold" style="color:var(--teal-mint);">${formatRp(f.harga_fasilitas)} / ${f.satuan}</span>
              </label>
            </div>
            <div style="width:82px;">
              <label class="form-label" style="font-size:0.68rem; margin-bottom:2px;">Jumlah</label>
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

function toggleTile(fid) {
  const cb = document.getElementById('fas_' + fid);
  const tile = document.getElementById('tile_' + fid);
  if (cb && tile) {
    tile.classList.toggle('selected', cb.checked);
  }
}

function hitungTotal() {
  const selKamar = document.getElementById('selectKamar');
  const opt = selKamar.options[selKamar.selectedIndex];
  const hargaKamar = opt && opt.dataset.harga ? parseFloat(opt.dataset.harga) : 0;
  const jmlKamar = parseInt(document.getElementById('jumlahKamar').value) || 1;

  const tIn = new Date(document.getElementById('tglCheckin').value);
  const tOut = new Date(document.getElementById('tglCheckout').value);
  let malam = 1;
  if (!isNaN(tIn) && !isNaN(tOut) && tOut > tIn) {
    malam = Math.ceil((tOut - tIn) / 86400000);
  }

  const subKamar = hargaKamar * jmlKamar * malam;

  let subFas = 0;
  document.querySelectorAll('.fas-check:checked').forEach(cb => {
    const harga = parseFloat(cb.dataset.harga) || 0;
    const paxEl = document.querySelector(`.fas-pax[data-fid="${cb.value}"]`);
    const pax = paxEl ? (parseInt(paxEl.value) || 1) : 1;
    subFas += harga * pax;
  });

  document.getElementById('rincianKamar').textContent = formatRp(subKamar);
  document.getElementById('rincianFasilitas').textContent = formatRp(subFas);
  document.getElementById('totalBayarDisplay').textContent = formatRp(subKamar + subFas);
}

function pilihHotelUntukMenginap(hotelId) {
  document.getElementById('selectHotel').value = hotelId;
  renderHotelOptions();
  document.getElementById('form-reservasi').scrollIntoView({ behavior: 'smooth' });
}

function pilihHotelDenganFasilitas(hotelId, fasId) {
  preselectedFasId = fasId;
  document.getElementById('selectHotel').value = hotelId;
  renderHotelOptions();
  document.getElementById('form-reservasi').scrollIntoView({ behavior: 'smooth' });
}
</script>
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/luxury-motion.js" defer></script>
</body>
</html>
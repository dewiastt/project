<?php
$pageTitle = 'Dasbor SangattaStay';
$pageBreadcrumb = 'Dasbor';
include 'header.php';
$conn = getConnection();

$todaySummary = $conn->query("
    SELECT
        (SELECT COUNT(*) FROM reservasi
         WHERE created_at >= CURDATE() AND created_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY))
        +
        (SELECT COUNT(*) FROM pemesanan_fasilitas
         WHERE created_at >= CURDATE() AND created_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY))
        AS total
")->fetch_assoc();
$todayBookings = (int)$todaySummary['total'];

$todayRevenueSummary = $conn->query("
    SELECT
        (SELECT COALESCE(SUM(total_harga), 0) FROM reservasi
         WHERE status_pembayaran IN ('lunas', 'selesai')
           AND created_at >= CURDATE() AND created_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY))
        +
        (SELECT COALESCE(SUM(total_harga), 0) FROM pemesanan_fasilitas
         WHERE status_pembayaran = 'lunas'
           AND created_at >= CURDATE() AND created_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY))
        AS total
")->fetch_assoc();
$todayRevenue = (float)$todayRevenueSummary['total'];

$pendingSummary = $conn->query("
    SELECT
        (SELECT COUNT(*) FROM reservasi WHERE status_pembayaran = 'pending')
        +
        (SELECT COUNT(*) FROM pemesanan_fasilitas WHERE status_pembayaran = 'pending')
        AS total
")->fetch_assoc();
$pendingBookings = (int)$pendingSummary['total'];

$inventorySummary = $conn->query("
    SELECT COALESCE(SUM(stok_kamar), 0) AS total FROM kamar
")->fetch_assoc();
$totalRooms = (int)$inventorySummary['total'];

$occupiedSummary = $conn->query("
    SELECT COALESCE(SUM(jumlah_kamar), 0) AS total
    FROM reservasi
    WHERE status_pembayaran IN ('lunas', 'selesai')
      AND tanggal_checkin <= CURDATE()
      AND tanggal_checkout > CURDATE()
")->fetch_assoc();
$occupiedRooms = min($totalRooms, (int)$occupiedSummary['total']);
$availableRooms = max(0, $totalRooms - $occupiedRooms);
$occupancyRate = $totalRooms > 0 ? (int)round(($occupiedRooms / $totalRooms) * 100) : 0;

$weekStart = date('Y-m-d', strtotime('-6 days'));
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$chartStatement = $conn->prepare("
    SELECT DATE(event_date) AS event_date, event_type, COUNT(*) AS total
    FROM (
        SELECT tanggal_checkin AS event_date, 'checkin' AS event_type
        FROM reservasi
        WHERE status_pembayaran IN ('lunas', 'selesai')
          AND tanggal_checkin >= ? AND tanggal_checkin < ?
        UNION ALL
        SELECT tanggal_checkout AS event_date, 'checkout' AS event_type
        FROM reservasi
        WHERE status_pembayaran IN ('lunas', 'selesai')
          AND tanggal_checkout >= ? AND tanggal_checkout < ?
    ) AS booking_events
    GROUP BY DATE(event_date), event_type
");
$chartStatement->bind_param('ssss', $weekStart, $tomorrow, $weekStart, $tomorrow);
$chartStatement->execute();
$chartResult = $chartStatement->get_result();
$chartCounts = [];
while ($row = $chartResult->fetch_assoc()) {
    $chartCounts[$row['event_date']][$row['event_type']] = (int)$row['total'];
}
$chartStatement->close();

$weekdayLabels = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
$chartDays = [];
$maxChartCount = 1;
for ($offset = 6; $offset >= 0; $offset--) {
    $timestamp = strtotime('-' . $offset . ' days');
    $dateKey = date('Y-m-d', $timestamp);
    $checkins = $chartCounts[$dateKey]['checkin'] ?? 0;
    $checkouts = $chartCounts[$dateKey]['checkout'] ?? 0;
    $chartDays[] = [
        'label' => $weekdayLabels[(int)date('N', $timestamp) - 1],
        'date' => date('d/m', $timestamp),
        'checkins' => $checkins,
        'checkouts' => $checkouts
    ];
    $maxChartCount = max($maxChartCount, $checkins, $checkouts);
}

$latestBookings = [];
$recentReservations = $conn->query("
    SELECT
        r.id, r.kode_booking, r.nama_tamu, r.tanggal_checkin AS tanggal_mulai,
        r.tanggal_checkout AS tanggal_selesai, r.total_harga, r.status_pembayaran,
        r.created_at, h.nama_hotel, k.nama_kamar AS nama_layanan, 'Kamar' AS jenis
    FROM reservasi r
    JOIN hotels h ON h.id = r.hotel_id
    JOIN kamar k ON k.id = r.kamar_id
    ORDER BY r.created_at DESC, r.id DESC
    LIMIT 8
");
while ($booking = $recentReservations->fetch_assoc()) {
    $latestBookings[] = $booking;
}

$recentFacilityOrders = $conn->query("
    SELECT
        p.id, p.kode_booking, p.nama_tamu, p.tanggal_kunjungan AS tanggal_mulai,
        p.tanggal_kunjungan AS tanggal_selesai, p.total_harga, p.status_pembayaran,
        p.created_at, h.nama_hotel, f.nama_fasilitas AS nama_layanan, 'Fasilitas' AS jenis
    FROM pemesanan_fasilitas p
    JOIN hotels h ON h.id = p.hotel_id
    JOIN fasilitas f ON f.id = p.fasilitas_id
    ORDER BY p.created_at DESC, p.id DESC
    LIMIT 8
");
while ($booking = $recentFacilityOrders->fetch_assoc()) {
    $latestBookings[] = $booking;
}

usort($latestBookings, function ($first, $second) {
    $dateOrder = strcmp($second['created_at'], $first['created_at']);
    return $dateOrder !== 0 ? $dateOrder : ((int)$second['id'] <=> (int)$first['id']);
});
$latestBookings = array_slice($latestBookings, 0, 8);
?>

<div class="admin-dashboard">
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
      <div class="card admin-stat-card admin-stat-bookings h-100 p-3">
        <div class="d-flex align-items-center gap-2 mb-2">
          <span class="admin-stat-icon"><i class="bi bi-calendar-check"></i></span>
          <span class="admin-stat-label">Pesanan Hari Ini</span>
        </div>
        <strong class="admin-stat-value"><?= number_format($todayBookings, 0, ',', '.') ?></strong>
        <small class="admin-stat-note">Kamar dan fasilitas</small>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card admin-stat-card admin-stat-occupancy h-100 p-3">
        <div class="d-flex align-items-center gap-2 mb-2">
          <span class="admin-stat-icon"><i class="bi bi-door-open"></i></span>
          <span class="admin-stat-label">Okupansi Kamar</span>
        </div>
        <strong class="admin-stat-value"><?= $occupancyRate ?>%</strong>
        <small class="admin-stat-note"><?= $occupiedRooms ?> terisi · <?= $availableRooms ?> tersedia</small>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card admin-stat-card admin-stat-revenue h-100 p-3">
        <div class="d-flex align-items-center gap-2 mb-2">
          <span class="admin-stat-icon"><i class="bi bi-cash-stack"></i></span>
          <span class="admin-stat-label">Pendapatan Hari Ini</span>
        </div>
        <strong class="admin-stat-value"><?= rupiah($todayRevenue) ?></strong>
        <small class="admin-stat-note">Transaksi yang sudah dibayar</small>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card admin-stat-card admin-stat-pending h-100 p-3">
        <div class="d-flex align-items-center gap-2 mb-2">
          <span class="admin-stat-icon"><i class="bi bi-hourglass-split"></i></span>
          <span class="admin-stat-label">Menunggu Pembayaran</span>
        </div>
        <strong class="admin-stat-value"><?= number_format($pendingBookings, 0, ',', '.') ?></strong>
        <small class="admin-stat-note">Pesanan kamar dan fasilitas</small>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-xl-8">
      <section class="card admin-dashboard-card h-100">
        <div class="card-body p-3 p-lg-4">
          <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
              <h5 class="admin-dashboard-heading mb-1">Aktivitas Check-in &amp; Check-out</h5>
              <p class="admin-dashboard-subtitle mb-0">Ringkasan tujuh hari terakhir</p>
            </div>
            <div class="admin-chart-legend">
              <span><i class="admin-legend-dot admin-legend-checkin"></i>Check-in</span>
              <span><i class="admin-legend-dot admin-legend-checkout"></i>Check-out</span>
            </div>
          </div>
          <div class="admin-booking-chart" role="img" aria-label="Grafik jumlah check-in dan check-out tujuh hari terakhir">
            <?php foreach ($chartDays as $day): ?>
              <?php
                $checkinHeight = $day['checkins'] > 0 ? max(5, (int)round($day['checkins'] / $maxChartCount * 100)) : 2;
                $checkoutHeight = $day['checkouts'] > 0 ? max(5, (int)round($day['checkouts'] / $maxChartCount * 100)) : 2;
              ?>
              <div class="admin-chart-day">
                <div class="admin-chart-bars">
                  <span class="admin-chart-bar admin-chart-bar-checkin"
                    style="height:<?= $checkinHeight ?>%;"
                    title="Check-in: <?= $day['checkins'] ?>"></span>
                  <span class="admin-chart-bar admin-chart-bar-checkout"
                    style="height:<?= $checkoutHeight ?>%;"
                    title="Check-out: <?= $day['checkouts'] ?>"></span>
                </div>
                <span class="admin-chart-day-label"><?= $day['label'] ?></span>
                <small class="admin-chart-day-date"><?= $day['date'] ?></small>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    </div>
    <div class="col-xl-4">
      <section class="card admin-dashboard-card h-100">
        <div class="card-body p-3 p-lg-4">
          <h5 class="admin-dashboard-heading mb-1">Status Kamar</h5>
          <p class="admin-dashboard-subtitle mb-3">Kondisi untuk hari ini</p>
          <div class="admin-room-status">
            <div class="admin-room-donut<?= $totalRooms === 0 ? ' is-empty' : '' ?>" style="--occupied-rate:<?= $occupancyRate ?>%;"
              role="img" aria-label="<?= $occupiedRooms ?> kamar terisi dan <?= $availableRooms ?> kamar tersedia">
              <div class="admin-room-donut-center">
                <strong><?= $totalRooms ?></strong>
                <span>Total kamar</span>
              </div>
            </div>
            <div class="admin-room-legend">
              <div>
                <span class="admin-legend-dot admin-legend-checkin"></span>
                <span>Terisi</span>
                <strong><?= $occupiedRooms ?></strong>
              </div>
              <div>
                <span class="admin-legend-dot admin-legend-available"></span>
                <span>Tersedia</span>
                <strong><?= $availableRooms ?></strong>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>

  <section class="card admin-dashboard-card">
    <div class="card-body p-3 p-lg-4">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
          <h5 class="admin-dashboard-heading mb-1">Pesanan Terbaru</h5>
          <p class="admin-dashboard-subtitle mb-0">Reservasi kamar dan fasilitas</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <a href="admin-reservasi.php" class="btn btn-sm btn-royal rounded-pill px-3">Reservasi</a>
          <a href="admin-pemesanan-fasilitas.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Fasilitas</a>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table admin-booking-table align-middle mb-0">
          <thead>
            <tr>
              <th>Kode Pesanan</th>
              <th>Tamu</th>
              <th>Hotel &amp; Layanan</th>
              <th>Tanggal</th>
              <th>Total</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($latestBookings) === 0): ?>
              <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada pesanan.</td></tr>
            <?php else: ?>
              <?php foreach ($latestBookings as $booking): ?>
                <?php
                  $statusClass = $booking['status_pembayaran'] === 'lunas' || $booking['status_pembayaran'] === 'selesai'
                    ? 'success'
                    : ($booking['status_pembayaran'] === 'batal' ? 'danger' : 'warning');
                ?>
                <tr>
                  <td>
                    <span class="admin-booking-code"><?= htmlspecialchars($booking['kode_booking']) ?></span>
                    <small class="admin-booking-type"><?= htmlspecialchars($booking['jenis']) ?></small>
                  </td>
                  <td><?= htmlspecialchars($booking['nama_tamu']) ?></td>
                  <td>
                    <span class="d-block fw-semibold"><?= htmlspecialchars($booking['nama_hotel']) ?></span>
                    <small class="text-muted"><?= htmlspecialchars($booking['nama_layanan']) ?></small>
                  </td>
                  <td>
                    <?= date('d/m/Y', strtotime($booking['tanggal_mulai'])) ?>
                    <?php if ($booking['jenis'] === 'Kamar'): ?>
                      <small class="text-muted d-block">s/d <?= date('d/m/Y', strtotime($booking['tanggal_selesai'])) ?></small>
                    <?php endif; ?>
                  </td>
                  <td class="fw-semibold"><?= rupiah($booking['total_harga']) ?></td>
                  <td><span class="badge text-bg-<?= $statusClass ?>"><?= strtoupper(htmlspecialchars($booking['status_pembayaran'])) ?></span></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>

<?php include 'footer.php'; ?>

<?php
// www/admin-reservasi.php
$pageTitle = 'Kelola Reservasi Kamar & Day-Pass';
$pageBreadcrumb = 'Reservasi';
include 'header.php';
$conn = getConnection();

$filterJenis = $_GET['jenis'] ?? 'all';
$where = "";
if (in_array($filterJenis, ['fasilitas_saja', 'kamar_saja', 'kamar_dan_fasilitas'])) {
    $where = "WHERE r.jenis_booking = '$filterJenis'";
}

$sql = "SELECT r.*, h.nama_hotel, k.nama_kamar 
        FROM reservasi r 
        JOIN hotels h ON r.hotel_id = h.id 
        LEFT JOIN kamar k ON r.kamar_id = k.id 
        $where 
        ORDER BY r.id DESC";
$rows = $conn->query($sql);
?>
<div class="card border-0 shadow-sm rounded-4">
  <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <h6 class="fw-bold mb-0">Data Pesanan Masuk</h6>
    <div class="btn-group btn-group-sm">
      <a href="?jenis=all" class="btn btn-<?= $filterJenis==='all'?'dark':'outline-dark' ?>">Semua</a>
      <a href="?jenis=fasilitas_saja" class="btn btn-<?= $filterJenis==='fasilitas_saja'?'success':'outline-success' ?>">Fasilitas Saja (Day-Pass)</a>
      <a href="?jenis=kamar_saja" class="btn btn-<?= $filterJenis==='kamar_saja'?'primary':'outline-primary' ?>">Kamar Hotel</a>
    </div>
  </div>
  <div class="card-body table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Kode Pemesanan</th>
          <th>Hotel & Layanan</th>
          <th>Tamu</th>
          <th>Tanggal</th>
          <th>Total</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($r = $rows->fetch_assoc()): ?>
        <tr>
          <td><span class="badge bg-dark"><?= htmlspecialchars($r['kode_booking']) ?></span></td>
          <td>
            <div class="fw-bold"><?= htmlspecialchars($r['nama_hotel']) ?></div>
            <?php if ($r['jenis_booking'] === 'fasilitas_saja'): ?>
              <span class="badge bg-success-subtle text-success">Tiket Fasilitas Saja (Tanpa Kamar)</span>
            <?php else: ?>
              <small class="text-muted"><?= htmlspecialchars($r['nama_kamar']) ?> (<?= $r['jumlah_kamar'] ?> kmr × <?= $r['total_malam'] ?> mlm)</small>
            <?php endif; ?>
          </td>
          <td>
            <div class="fw-semibold"><?= htmlspecialchars($r['nama_tamu']) ?></div>
            <small class="text-muted"><?= htmlspecialchars($r['no_hp']) ?></small>
          </td>
          <td class="small"><?= $r['tanggal_checkin'] ?></td>
          <td class="fw-bold text-primary"><?= rupiah($r['total_harga']) ?></td>
          <td>
            <span class="badge bg-<?= $r['status_pembayaran']==='lunas'?'success':($r['status_pembayaran']==='batal'?'danger':'warning text-dark') ?>">
              <?= strtoupper($r['status_pembayaran']) ?>
            </span>
          </td>
          <td>
            <?php if ($r['status_pembayaran'] === 'pending'): ?>
              <form action="admin-reservasi-actions.php" method="POST" class="d-inline">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <input type="hidden" name="status" value="lunas">
                <button class="btn btn-sm btn-success" title="Konfirmasi Lunas"><i class="bi bi-check-lg"></i></button>
              </form>
              <form action="admin-reservasi-actions.php" method="POST" class="d-inline">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <input type="hidden" name="status" value="batal">
                <button class="btn btn-sm btn-outline-danger" title="Batalkan"><i class="bi bi-x-lg"></i></button>
              </form>
            <?php else: ?>
              <a href="track-reservasi.php?kode=<?= urlencode($r['kode_booking']) ?>" target="_blank" class="btn btn-sm btn-light border">E-Ticket</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include 'footer.php'; ?>
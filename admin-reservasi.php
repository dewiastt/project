<?php
$pageTitle = 'Kelola Reservasi';
$pageBreadcrumb = 'Reservasi';
include 'header.php';
$conn = getConnection();

$rows = $conn->query("
    SELECT r.*, h.nama_hotel, k.nama_kamar
    FROM reservasi r
    JOIN hotels h ON r.hotel_id = h.id
    JOIN kamar k ON r.kamar_id = k.id
    ORDER BY r.id DESC
");
?>
<div class="card border-0 shadow-sm rounded-4">
  <div class="card-header bg-white py-3">
    <h6 class="fw-bold mb-0">Daftar Reservasi Menginap &amp; Fasilitas Pilihan</h6>
  </div>
  <div class="card-body table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Kode</th>
          <th>Hotel &amp; Kamar</th>
          <th>Tamu</th>
          <th>Tanggal Masuk / Keluar</th>
          <th>Rincian Biaya</th>
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
            <small class="text-muted"><?= htmlspecialchars($r['nama_kamar']) ?> (<?= (int)$r['jumlah_kamar'] ?> kmr &times; <?= (int)$r['total_malam'] ?> mlm)</small>
          </td>
          <td>
            <div class="fw-semibold"><?= htmlspecialchars($r['nama_tamu']) ?></div>
            <small class="text-muted"><?= htmlspecialchars($r['no_hp']) ?></small>
          </td>
          <td class="small"><?= $r['tanggal_checkin'] ?><br><span class="text-muted">s/d</span> <?= $r['tanggal_checkout'] ?></td>
          <td class="small">
            <div>Kamar: <strong><?= rupiah($r['subtotal_kamar']) ?></strong></div>
            <div>Fasilitas: <strong style="color:var(--teal-mint);"><?= rupiah($r['subtotal_fasilitas']) ?></strong></div>
            <div class="text-primary fw-bold">Total: <?= rupiah($r['total_harga']) ?></div>
          </td>
          <td>
            <span class="badge bg-<?= $r['status_pembayaran']==='lunas'?'success':($r['status_pembayaran']==='batal'?'danger':'warning text-dark') ?>">
              <?= strtoupper($r['status_pembayaran']) ?>
            </span>
          </td>
          <td>
            <?php if ($r['status_pembayaran'] === 'pending'): ?>
              <?php if ($r['metode_bayar'] === 'QRIS'): ?>
                <span class="badge bg-info-subtle text-info-emphasis">Menunggu Midtrans</span>
              <?php else: ?>
                <form action="admin-reservasi-actions.php" method="POST" class="d-inline">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>">
                  <input type="hidden" name="status" value="lunas">
                  <button class="btn btn-sm btn-success" title="Konfirmasi Lunas"><i class="bi bi-check-lg"></i></button>
                </form>
              <?php endif; ?>
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

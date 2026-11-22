<?php
$pageTitle = 'Pesanan Fasilitas Mandiri';
$pageBreadcrumb = 'Pesanan Fasilitas';
include 'header.php';
$conn = getConnection();
$orders = $conn->query("
    SELECT p.*, h.nama_hotel, f.nama_fasilitas, f.satuan
    FROM pemesanan_fasilitas p
    JOIN hotels h ON p.hotel_id = h.id
    JOIN fasilitas f ON p.fasilitas_id = f.id
    ORDER BY p.id DESC
");
?>
<div class="card border-0 shadow-sm rounded-4">
  <div class="card-header bg-white py-3">
    <h6 class="fw-bold mb-0">Pesanan Fasilitas Tanpa Check-in Kamar</h6>
  </div>
  <div class="card-body table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Kode</th>
          <th>Fasilitas &amp; Hotel</th>
          <th>Pemesan</th>
          <th>Kunjungan</th>
          <th>Jumlah</th>
          <th>Total</th>
          <th>Pembayaran</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($order = $orders->fetch_assoc()): ?>
        <tr>
          <td><span class="badge bg-dark"><?= htmlspecialchars($order['kode_booking']) ?></span></td>
          <td>
            <div class="fw-bold"><?= htmlspecialchars($order['nama_fasilitas']) ?></div>
            <small class="text-muted"><?= htmlspecialchars($order['nama_hotel']) ?></small>
          </td>
          <td>
            <div class="fw-semibold"><?= htmlspecialchars($order['nama_tamu']) ?></div>
            <small class="text-muted"><?= htmlspecialchars($order['no_hp']) ?></small>
          </td>
          <td class="small"><?= htmlspecialchars($order['tanggal_kunjungan']) ?></td>
          <td><?= (int)$order['jumlah_pax'] ?> <?= htmlspecialchars(preg_replace('/^per\s+/i', '', $order['satuan'])) ?></td>
          <td class="fw-bold"><?= rupiah($order['total_harga']) ?></td>
          <td>
            <div><?= htmlspecialchars($order['metode_bayar']) ?></div>
            <span class="badge bg-<?= $order['status_pembayaran'] === 'lunas' ? 'success' : ($order['status_pembayaran'] === 'batal' ? 'danger' : 'warning text-dark') ?>">
              <?= strtoupper($order['status_pembayaran']) ?>
            </span>
          </td>
          <td>
            <?php if ($order['status_pembayaran'] === 'pending'): ?>
              <?php if ($order['metode_bayar'] === 'QRIS'): ?>
                <span class="small text-muted">Menunggu Midtrans</span>
              <?php else: ?>
                <form action="admin-pemesanan-fasilitas-actions.php" method="POST" class="d-inline">
                  <input type="hidden" name="id" value="<?= (int)$order['id'] ?>">
                  <input type="hidden" name="status" value="lunas">
                  <button class="btn btn-sm btn-success" title="Konfirmasi Lunas"><i class="bi bi-check-lg"></i></button>
                </form>
              <?php endif; ?>
              <form action="admin-pemesanan-fasilitas-actions.php" method="POST" class="d-inline">
                <input type="hidden" name="id" value="<?= (int)$order['id'] ?>">
                <input type="hidden" name="status" value="batal">
                <button class="btn btn-sm btn-outline-danger" title="Batalkan"><i class="bi bi-x-lg"></i></button>
              </form>
            <?php else: ?>
              <a class="btn btn-sm btn-light border" href="track-fasilitas.php?kode=<?= urlencode($order['kode_booking']) ?>" target="_blank">Nota</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include 'footer.php'; ?>

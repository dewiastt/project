<?php
$pageTitle = 'Kelola Tipe Kamar';
$pageBreadcrumb = 'Tipe Kamar';
include 'header.php';
$conn = getConnection();

$hotels = $conn->query("SELECT h.id, h.nama_hotel FROM hotels h
  WHERE h.id = (SELECT MIN(h2.id) FROM hotels h2 WHERE h2.nama_hotel = h.nama_hotel)
  ORDER BY h.nama_hotel ASC");
$hotelOpts = [];
while ($h = $hotels->fetch_assoc()) $hotelOpts[] = $h;

$kamars = $conn->query("SELECT k.*, h.nama_hotel FROM kamar k JOIN hotels h ON k.hotel_id = h.id
  WHERE h.id = (SELECT MIN(h2.id) FROM hotels h2 WHERE h2.nama_hotel = h.nama_hotel)
  ORDER BY h.nama_hotel ASC, k.harga_per_malam ASC");
?>
<div class="card border-0 shadow-sm rounded-4">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <h6 class="fw-bold mb-0">Daftar Tipe Kamar Hotel Sangatta</h6>
    <button class="btn btn-royal btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalKamar">
      <i class="bi bi-plus-lg me-1"></i> Tambah Kamar
    </button>
  </div>
  <div class="card-body table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Hotel</th>
          <th>Tipe Kamar</th>
          <th>Harga / Malam</th>
          <th>Kapasitas &amp; Stok</th>
          <th>Fasilitas Kamar</th>
          <th>Kasur Tambahan</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($k = $kamars->fetch_assoc()): ?>
        <tr>
          <td class="fw-semibold"><?= htmlspecialchars($k['nama_hotel']) ?></td>
          <td class="fw-bold" style="color:var(--royal-indigo);"><?= htmlspecialchars($k['nama_kamar']) ?></td>
          <td class="fw-bold"><?= rupiah($k['harga_per_malam']) ?></td>
          <td class="small"><?= (int)$k['kapasitas'] ?> Tamu &bull; <?= (int)$k['stok_kamar'] ?> Kamar</td>
          <td class="small text-muted"><?= htmlspecialchars($k['fasilitas_kamar']) ?></td>
          <td class="small">
            <?php if ((int)$k['extra_bed_tersedia'] === 1): ?>
              <span class="badge bg-success-subtle text-success">Tersedia</span>
              <div><?= rupiah($k['harga_extra_bed']) ?> / kasur / malam</div>
            <?php else: ?>
              <span class="badge bg-secondary">Tidak tersedia</span>
            <?php endif; ?>
          </td>
          <td>
            <form action="admin-paket-actions.php" method="POST" class="d-inline" onsubmit="return confirm('Hapus tipe kamar ini?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $k['id'] ?>">
              <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="modalKamar" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form action="admin-paket-actions.php" method="POST" class="modal-content">
      <input type="hidden" name="action" value="create">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Tambah Tipe Kamar</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Hotel</label>
          <select name="hotel_id" class="form-select" required>
            <?php foreach ($hotelOpts as $ho): ?>
              <option value="<?= $ho['id'] ?>"><?= htmlspecialchars($ho['nama_hotel']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Nama Tipe Kamar</label>
          <input type="text" name="nama_kamar" class="form-control" required>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label">Harga / Malam (Rp)</label>
            <input type="number" name="harga_per_malam" class="form-control" min="50000" required>
          </div>
          <div class="col-3">
            <label class="form-label">Kapasitas</label>
            <input type="number" name="kapasitas" class="form-control" value="2" min="1">
          </div>
          <div class="col-3">
            <label class="form-label">Stok</label>
            <input type="number" name="stok_kamar" class="form-control" value="5" min="1">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Fasilitas Kamar</label>
          <textarea name="fasilitas_kamar" class="form-control" rows="2"></textarea>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label">Kasur Tambahan</label>
            <select name="extra_bed_tersedia" class="form-select">
              <option value="1">Tersedia</option>
              <option value="0">Tidak Tersedia</option>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label">Harga Kasur / Malam</label>
            <input type="number" name="harga_extra_bed" class="form-control" value="150000" min="0">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-royal">Simpan</button>
      </div>
    </form>
  </div>
</div>
<?php include 'footer.php'; ?>

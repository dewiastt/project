<?php
$pageTitle = 'Kelola Fasilitas Tambahan Berbayar';
$pageBreadcrumb = 'Fasilitas Tambahan';
include 'header.php';
$conn = getConnection();

$hotels = $conn->query("SELECT h.id, h.nama_hotel FROM hotels h
  WHERE h.id = (SELECT MIN(h2.id) FROM hotels h2 WHERE h2.nama_hotel = h.nama_hotel)
  ORDER BY h.nama_hotel ASC");
$fasList = $conn->query("SELECT f.*, h.nama_hotel FROM fasilitas f JOIN hotels h ON f.hotel_id = h.id
  WHERE h.id = (SELECT MIN(h2.id) FROM hotels h2 WHERE h2.nama_hotel = h.nama_hotel)
  ORDER BY f.id DESC");
?>
<div class="card border-0 shadow-sm rounded-4">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <div>
      <h6 class="fw-bold mb-0">Daftar Fasilitas Berbayar Pilihan Tamu</h6>
      <small class="text-muted">Tarif awal dapat disesuaikan dengan harga aktual hotel.</small>
    </div>
    <button class="btn btn-royal btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalFas">
      <i class="bi bi-plus-lg me-1"></i> Tambah Fasilitas
    </button>
  </div>
  <div class="card-body table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Hotel</th>
          <th>Nama Fasilitas</th>
          <th>Kategori</th>
          <th>Tarif</th>
          <th>Jam Buka</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($f = $fasList->fetch_assoc()): ?>
        <tr>
          <td class="fw-semibold"><?= htmlspecialchars($f['nama_hotel']) ?></td>
          <td class="fw-bold"><?= htmlspecialchars($f['nama_fasilitas']) ?></td>
          <td><span class="badge-facility-cat"><?= htmlspecialchars($f['kategori']) ?></span></td>
          <td class="fw-bold" style="color:var(--teal-mint);"><?= rupiah($f['harga_fasilitas']) ?> / <?= htmlspecialchars($f['satuan']) ?></td>
          <td class="small"><?= htmlspecialchars($f['jam_operasional']) ?></td>
          <td>
            <form action="admin-fasilitas-actions.php" method="POST" class="d-inline" onsubmit="return confirm('Hapus fasilitas ini?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $f['id'] ?>">
              <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="modalFas" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form action="admin-fasilitas-actions.php" method="POST" class="modal-content">
      <input type="hidden" name="action" value="create">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Tambah Fasilitas Berbayar</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Hotel</label>
          <select name="hotel_id" class="form-select" required>
            <?php while ($h = $hotels->fetch_assoc()): ?>
              <option value="<?= $h['id'] ?>"><?= htmlspecialchars($h['nama_hotel']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Nama Fasilitas</label>
          <input type="text" name="nama_fasilitas" class="form-control" placeholder="Contoh: Akses Kolam Renang" required>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label">Kategori</label>
            <input type="text" name="kategori" class="form-control" list="kategoriFasilitas" value="Food & Beverage" required>
            <datalist id="kategoriFasilitas">
              <option value="Food & Beverage">
              <option value="Kolam Renang">
              <option value="Paket Kolam Renang">
              <option value="Kebugaran">
              <option value="Spa & Relaksasi">
              <option value="Meeting & Acara">
              <option value="Laundry">
              <option value="Makan Romantis">
            </datalist>
          </div>
          <div class="col-6">
            <label class="form-label">Harga (Rp)</label>
            <input type="number" name="harga_fasilitas" class="form-control" min="0" required>
          </div>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label">Satuan</label>
            <input type="text" name="satuan" class="form-control" value="per orang">
          </div>
          <div class="col-6">
            <label class="form-label">Jam Operasional</label>
            <input type="text" name="jam_operasional" class="form-control" value="07:00 - 21:00 WITA">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Deskripsi</label>
          <textarea name="deskripsi" class="form-control" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-royal">Simpan</button>
      </div>
    </form>
  </div>
</div>
<?php include 'footer.php'; ?>

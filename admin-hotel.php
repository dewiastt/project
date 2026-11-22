<?php
$pageTitle = 'Kelola Hotel Sangatta';
$pageBreadcrumb = 'Hotel Sangatta';
include 'header.php';
$conn = getConnection();
$res = $conn->query("SELECT h.* FROM hotels h
  WHERE h.id = (SELECT MIN(h2.id) FROM hotels h2 WHERE h2.nama_hotel = h.nama_hotel)
  ORDER BY h.id DESC");
?>
<div class="card border-0 shadow-sm rounded-4">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <h6 class="fw-bold mb-0">Daftar Hotel di Sangatta</h6>
    <button class="btn btn-royal btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalHotel">
      <i class="bi bi-plus-lg me-1"></i> Tambah Hotel
    </button>
  </div>
  <div class="card-body table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Foto</th>
          <th>Nama Hotel</th>
          <th>Kecamatan</th>
          <th>Bintang</th>
          <th>Alamat</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($h = $res->fetch_assoc()): ?>
        <tr>
          <td><img src="<?= htmlspecialchars($h['foto_utama']) ?>" class="rounded-3" style="width:64px;height:46px;object-fit:cover;"></td>
          <td class="fw-bold"><?= htmlspecialchars($h['nama_hotel']) ?></td>
          <td><span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($h['kecamatan']) ?></span></td>
          <td class="text-warning"><?= str_repeat('★', (int)$h['bintang']) ?></td>
          <td class="small"><?= htmlspecialchars($h['alamat']) ?></td>
          <td>
            <form action="admin-hotel-actions.php" method="POST" class="d-inline" onsubmit="return confirm('Hapus hotel ini?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $h['id'] ?>">
              <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="modalHotel" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form action="admin-hotel-actions.php" method="POST" enctype="multipart/form-data" class="modal-content">
      <input type="hidden" name="action" value="create">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Tambah Hotel di Sangatta</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Nama Hotel</label>
          <input type="text" name="nama_hotel" class="form-control" required>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-7">
            <label class="form-label">Kecamatan</label>
            <select name="kecamatan" class="form-select">
              <option value="Sangatta Utara">Sangatta Utara</option>
              <option value="Teluk Lingga">Teluk Lingga</option>
              <option value="Swarga Bara">Swarga Bara</option>
              <option value="Sangatta Selatan">Sangatta Selatan</option>
            </select>
          </div>
          <div class="col-5">
            <label class="form-label">Bintang</label>
            <select name="bintang" class="form-select">
              <option value="3" selected>3 Bintang</option>
              <option value="4">4 Bintang</option>
              <option value="5">5 Bintang</option>
              <option value="2">2 Bintang</option>
            </select>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Alamat</label>
          <input type="text" name="alamat" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Deskripsi</label>
          <textarea name="deskripsi" class="form-control" rows="2"></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Foto Hotel</label>
          <input type="file" name="foto" class="form-control" accept="image/*">
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-royal">Simpan</button>
      </div>
    </form>
  </div>
</div>
<?php include 'footer.php'; ?>

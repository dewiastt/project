<?php
require_once 'config.php';

if (isLoggedIn()) {
    header("Location: admin-dashboard.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username !== '' && $password !== '') {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT id, username, password, nama_lengkap FROM admin_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            $user = $res->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_nama'] = $user['nama_lengkap'];
                header("Location: admin-dashboard.php");
                exit();
            } else {
                $error = 'Kata Sandi salah!';
            }
        } else {
            $error = 'Nama Pengguna tidak ditemukan!';
        }
        $stmt->close();
    } else {
        $error = 'Isi username dan password.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Masuk Admin - SangattaStay</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link href="assets/css/sangatta-stay.css" rel="stylesheet">
  <link href="assets/css/luxury-theme.css" rel="stylesheet">
</head>
<body class="admin-auth-body d-flex align-items-center justify-content-center p-3">
  <div class="booking-builder-card p-4 p-md-5 w-100" style="max-width:420px;">
    <div class="text-center mb-4">
      <div class="brand-mark mx-auto mb-2" style="width:52px;height:52px;font-size:1.4rem;"><i class="bi bi-building-check"></i></div>
      <h4 class="fw-bold mb-1">SangattaStay Admin</h4>
      <p class="small text-muted mb-0">Masuk untuk mengelola hotel, kamar &amp; fasilitas</p>
    </div>

    <?php if ($error !== ''): ?>
      <div class="alert alert-danger small rounded-3"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label class="form-label small fw-bold">Nama Pengguna</label>
        <input type="text" name="username" class="form-control" placeholder="admin" required autofocus>
      </div>
      <div class="mb-4">
        <label class="form-label small fw-bold">Kata Sandi</label>
        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-royal w-100 py-2 rounded-3 mb-3">Masuk Dasbor</button>
      <div class="text-center">
        <a href="index.php" class="small text-decoration-none text-muted"><i class="bi bi-arrow-left me-1"></i> Kembali ke Beranda</a>
      </div>
    </form>
  </div>
  <script src="assets/js/luxury-motion.js" defer></script>
</body>
</html>

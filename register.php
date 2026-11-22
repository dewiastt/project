<?php
require_once 'config.php';
ensureUsersTable();

if (isUserLoggedIn()) {
    header("Location: booking.php");
    exit();
}

$conn = getConnection();
$error = '';
if (!empty($_GET['redirect'])) {
    $_SESSION['redirect_after_login'] = $_GET['redirect'];
}
$redirectParam = !empty($_SESSION['redirect_after_login']) ? '?redirect=' . urlencode($_SESSION['redirect_after_login']) : '';
$nama = trim($_POST['nama_lengkap'] ?? '');
$email = trim($_POST['email'] ?? '');
$no_hp = trim($_POST['no_hp'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi_password'] ?? '';

    if ($nama === '' || $email === '' || $no_hp === '' || $password === '') {
        $error = 'Mohon lengkapi semua data pendaftaran akun.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Kata Sandi minimal 6 karakter.';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $stmtCek = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmtCek->bind_param("s", $email);
        $stmtCek->execute();
        $resCek = $stmtCek->get_result();

        if ($resCek && $resCek->num_rows > 0) {
            $error = 'Alamat Email sudah terdaftar. Silakan masuk menggunakan akun tersebut.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmtIns = $conn->prepare("INSERT INTO users (nama_lengkap, email, no_hp, password) VALUES (?, ?, ?, ?)");
            $stmtIns->bind_param("ssss", $nama, $email, $no_hp, $hash);

            if ($stmtIns->execute()) {
                $newUserId = $stmtIns->insert_id;
                session_regenerate_id(true);
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_id'] = (int)$newUserId;
                $_SESSION['user_nama'] = $nama;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_hp'] = $no_hp;

                $target = $_SESSION['redirect_after_login'] ?? 'index.php';
                unset($_SESSION['redirect_after_login']);
                $stmtIns->close();
                header("Location: " . $target);
                exit();
            } else {
                $error = 'Gagal membuat akun: ' . $conn->error;
            }
            $stmtIns->close();
        }
        $stmtCek->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Daftar Akun — SangattaStay</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/sangatta-stay.css" rel="stylesheet">
  <link href="assets/css/luxury-theme.css" rel="stylesheet">
</head>
<body class="site-auth-body d-flex align-items-center justify-content-center py-5 px-3">
  <div class="site-auth-card p-4 p-md-5 w-100 rounded-4 shadow-lg" style="max-width:470px;">
    <div class="text-center mb-4">
      <a href="index.php" class="text-decoration-none d-inline-flex align-items-center gap-2 mb-2">
        <span class="brand-mark"><i class="bi bi-buildings-fill"></i></span>
        <span class="fw-bold fs-4 text-dark">Sangatta<span style="color:var(--amber-gold);">Stay</span></span>
      </a>
      <h4 class="fw-bold mb-1">Buat Akun</h4>
      <p class="small text-muted mb-0">Daftar untuk masuk dan memesan kamar atau fasilitas pilihan di Sangatta</p>
    </div>

    <?php if ($error !== ''): ?>
      <div class="alert alert-danger small rounded-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
      <div class="mb-3">
        <label class="form-label small fw-bold">Nama Lengkap <span class="text-danger">*</span></label>
        <input type="text" name="nama_lengkap" class="form-control rounded-3" value="<?= htmlspecialchars($nama) ?>" placeholder="Nama sesuai KTP" required autofocus>
      </div>
      <div class="mb-3">
        <label class="form-label small fw-bold">Alamat Email <span class="text-danger">*</span></label>
        <input type="email" name="email" class="form-control rounded-3" value="<?= htmlspecialchars($email) ?>" placeholder="nama@email.com" required>
      </div>
      <div class="mb-3">
        <label class="form-label small fw-bold">Nomor WhatsApp / HP <span class="text-danger">*</span></label>
        <input type="text" name="no_hp" class="form-control rounded-3" value="<?= htmlspecialchars($no_hp) ?>" placeholder="0812xxxxxxxx" required>
      </div>
      <div class="row g-2 mb-4">
        <div class="col-sm-6">
          <label class="form-label small fw-bold">Kata Sandi <span class="text-danger">*</span></label>
          <input type="password" name="password" class="form-control rounded-3" placeholder="Min. 6 karakter" required>
        </div>
        <div class="col-sm-6">
          <label class="form-label small fw-bold">Konfirmasi Kata Sandi <span class="text-danger">*</span></label>
          <input type="password" name="konfirmasi_password" class="form-control rounded-3" placeholder="Ulangi password" required>
        </div>
      </div>
      <button type="submit" class="btn btn-coral w-100 py-2.5 rounded-pill mb-3">
        Daftar &amp; Lanjutkan ke Pemesanan <i class="bi bi-arrow-right ms-1"></i>
      </button>
      <div class="text-center small">
        Sudah punya akun? <a href="login.php<?= $redirectParam ?>" class="fw-bold text-decoration-none" style="color:var(--indigo-accent);">Masuk di sini</a>
        <div class="mt-2"><a href="index.php" class="text-muted text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Kembali ke Beranda</a></div>
      </div>
    </form>
  </div>
  <script src="assets/js/luxury-motion.js" defer></script>
</body>
</html>

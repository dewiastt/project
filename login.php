<?php
require_once 'config.php';
ensureUsersTable();

if (isUserLoggedIn()) {
    unset($_SESSION['redirect_after_login']);
    header("Location: index.php");
    exit();
}

$conn = getConnection();
$error = '';
$email = trim($_POST['email'] ?? '');
$wajibMasuk = isset($_GET['wajib_login']);
if (!empty($_GET['redirect'])) {
    $_SESSION['redirect_after_login'] = $_GET['redirect'];
}
$redirectParam = !empty($_SESSION['redirect_after_login']) ? '?redirect=' . urlencode($_SESSION['redirect_after_login']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Silakan isi email dan password.';
    } else {
        $adminStmt = $conn->prepare("SELECT id, username, password, nama_lengkap FROM admin_users WHERE username = ? LIMIT 1");
        $adminStmt->bind_param("s", $email);
        $adminStmt->execute();
        $adminRes = $adminStmt->get_result();

        if ($adminRes && $adminRes->num_rows === 1) {
            $admin = $adminRes->fetch_assoc();
            if (password_verify($password, $admin['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = (int)$admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_nama'] = $admin['nama_lengkap'];
                unset($_SESSION['redirect_after_login']);
                header("Location: admin-dashboard.php");
                exit();
            } else {
                $error = 'Kata Sandi admin salah!';
            }
        }
        $adminStmt->close();

        if ($error === '') {
            $stmt = $conn->prepare("SELECT id, nama_lengkap, email, no_hp, password FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res && $res->num_rows === 1) {
                $user = $res->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_logged_in'] = true;
                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['user_nama'] = $user['nama_lengkap'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_hp'] = $user['no_hp'];

                    unset($_SESSION['redirect_after_login']);
                    $stmt->close();
                    header("Location: index.php");
                    exit();
                } else {
                    $error = 'Kata Sandi salah!';
                }
            } else {
                $error = 'Alamat Email belum terdaftar. Silakan buat akun terlebih dahulu.';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Masuk Akun — SangattaStay</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/sangatta-stay.css?v=20261122" rel="stylesheet">
  <link href="assets/css/luxury-theme.css?v=20261122" rel="stylesheet">
</head>
<body class="site-auth-body d-flex align-items-center justify-content-center py-5 px-3">
  <div class="site-auth-card p-4 p-md-5 w-100 rounded-4 shadow-lg" style="max-width:440px;">
    <div class="text-center mb-4">
      <a href="index.php" class="text-decoration-none d-inline-flex align-items-center gap-2 mb-2">
        <span class="brand-mark"><i class="bi bi-buildings-fill"></i></span>
        <span class="fw-bold fs-4 text-dark">Sangatta<span style="color:var(--amber-gold);">Stay</span></span>
      </a>
      <h4 class="fw-bold mb-1">Masuk</h4>
      <p class="small text-muted mb-0">Masuk dengan akun pengguna atau akun admin</p>
    </div>

    <?php if ($wajibMasuk && $error === ''): ?>
      <div class="alert alert-warning small rounded-4">
        <i class="bi bi-lock-fill me-1"></i> Silakan masuk atau daftar akun terlebih dahulu sebelum melakukan pemesanan.
      </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
      <div class="alert alert-danger small rounded-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
      <div class="mb-3">
        <label class="form-label small fw-bold">Alamat Email / Nama Pengguna Admin</label>
        <input type="text" name="email" class="form-control rounded-3 py-2" value="<?= htmlspecialchars($email) ?>" placeholder="nama@email.com atau admin" required autofocus>
      </div>
      <div class="mb-4">
        <label class="form-label small fw-bold">Kata Sandi</label>
        <input type="password" name="password" class="form-control rounded-3 py-2" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-coral w-100 py-2.5 rounded-pill mb-3">
        Masuk <i class="bi bi-arrow-right ms-1"></i>
      </button>
      <div class="text-center small">
        Belum punya akun? <a href="register.php<?= $redirectParam ?>" class="fw-bold text-decoration-none" style="color:var(--indigo-accent);">Buat Akun Baru</a>
        <div class="mt-3 d-flex flex-column gap-2">
          <a href="index.php" class="text-muted text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Kembali ke Beranda</a>
        </div>
      </div>
    </form>
  </div>
  <script src="assets/js/luxury-motion.js" defer></script>
</body>
</html>

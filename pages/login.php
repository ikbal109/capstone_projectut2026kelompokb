<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth.php';

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM users WHERE username=? AND aktif=1");
    $stmt->bind_param('s', $u);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($p, $user['password'])) {
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nama']     = $user['nama_lengkap'];
        $_SESSION['role']     = $user['role'];
        redirect(BASE_URL . '/pages/admin/dashboard.php');
    } else {
        $err = 'Username atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Admin — BPSDMD Kalsel</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/login.css">
</head>
<body class="login-body">

  <div class="login-wrapper">

    <!-- ============ SISI KIRI: BRANDING ============ -->
    <div class="login-hero">
      <div class="hero-logo">
        <span class="logo-emoji">🏟️</span>
      </div>

      <h1 class="hero-title">
        Sistem Informasi Pengelolaan dan Reservasi
        <span class="highlight">Sarana Olahraga</span>
      </h1>

      <p class="hero-subtitle">
        Badan Pengembangan Sumber Daya Manusia Daerah<br>
        Provinsi Kalimantan Selatan
      </p>

      <div class="hero-badge">
        <span class="dot"></span>
        Sistem Reservasi Online
      </div>
    </div>

    <!-- ============ SISI KANAN: FORM LOGIN ============ -->
    <div class="login-form-wrap">
      <div class="login-card">

        <div class="login-card-header">
          <p class="small-title">
            Sistem Informasi Pengelolaan dan Reservasi<br>Sarana Olahraga
          </p>
          <h1>Login <span class="highlight">Admin</span></h1>
          <div class="login-card-divider"></div>
        </div>

        <?php if ($err): ?>
          <div class="login-alert danger">
            <span>⚠️</span>
            <span><?= e($err) ?></span>
          </div>
        <?php endif; ?>

        <?php if ($m = flash('msg')): ?>
          <div class="login-alert success">
            <span>✅</span>
            <span><?= e($m) ?></span>
          </div>
        <?php endif; ?>

        <form method="POST" class="login-form">

          <div class="field">
            <label for="username">Username</label>
            <div class="input-icon-group">
              <span class="icon-left">👤</span>
              <input type="text" id="username" name="username" 
                     placeholder="Masukkan username" required autofocus
                     value="<?= e($_POST['username'] ?? '') ?>">
            </div>
          </div>

          <div class="field">
            <label for="password">Password</label>
            <div class="input-icon-group">
              <span class="icon-left">🔒</span>
              <input type="password" id="password" name="password" 
                     placeholder="Masukkan password" required>
              <button type="button" class="toggle-password" 
                      onclick="togglePassword()" title="Lihat/Sembunyikan password">
                👁️
              </button>
            </div>
          </div>

          <button type="submit" class="btn-login">
            Login
          </button>
        </form>

        <a href="<?= BASE_URL ?>/index.php" class="back-link">
          ← Kembali ke Beranda
        </a>

      </div>
    </div>
  </div>

  <!-- ============ INFO LAYANAN ============ -->
  <div class="info-layanan">
    <div class="info-layanan-inner">
      <div class="info-item">
        <div class="info-icon">📅</div>
        <div>
          <strong>Reservasi Online</strong>
          <small>Booking kapan saja & di mana saja</small>
        </div>
      </div>
      <div class="info-item">
        <div class="info-icon">🛡️</div>
        <div>
          <strong>Mudah & Aman</strong>
          <small>Data terjaga dan terpercaya</small>
        </div>
      </div>
      <div class="info-item">
        <div class="info-icon">🕐</div>
        <div>
          <strong>Tersedia Setiap Hari</strong>
          <small>Akses 24 jam non-stop</small>
        </div>
      </div>
    </div>
  </div>

<script>
function togglePassword() {
  const input = document.getElementById('password');
  const btn = document.querySelector('.toggle-password');
  if (input.type === 'password') {
    input.type = 'text';
    btn.textContent = '🙈';
  } else {
    input.type = 'password';
    btn.textContent = '👁️';
  }
}
</script>

</body>
</html>
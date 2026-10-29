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
<title>Login Admin — SIPASTRA</title>
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/login.css">
</head>
<body class="login-page">

  <!-- BACKGROUND FULL -->
  <div class="bg-image" style="background-image: url('<?= BASE_URL ?>/assets/img/bg-login.png');"></div>
  <div class="bg-overlay"></div>

  <div class="login-wrapper">
    
    <!-- SISI KIRI: BRANDING -->
    <div class="login-hero">
      <!-- Logo -->
      <img src="<?= BASE_URL ?>/assets/img/loggoo.png" alt="Logo SIPASTRA" class="hero-logo-img">
      
      <h1 class="hero-title">SIPASTRA</h1>
      <p class="hero-subtitle">Sistem Informasi Pengelolaan dan Reservasi<br>Sarana Olahraga</p>

      <!-- Ikon Cabang Olahraga -->
      <div class="hero-sports">
        <div class="sport-item">
          <div class="sport-icon">⚽</div>
          <span>LAPANGAN<br>FUTSAL</span>
        </div>
        <div class="sport-divider"></div>
        <div class="sport-item">
          <div class="sport-icon">🎾</div>
          <span>LAPANGAN<br>TENIS</span>
        </div>
      </div>
    </div>

    <!-- SISI KANAN: FORM LOGIN -->
    <div class="login-form-wrap">
      <div class="login-card">
        
        <div class="login-card-header">
          <h2 class="card-brand">SIPASTRA<span>.</span></h2>
          <p class="card-sub">Sistem Informasi Pengelolaan dan Reservasi<br>Sarana Olahraga</p>
          <h3 class="card-title">Login Admin</h3>
        </div>

        <?php if ($err): ?>
          <div class="alert alert-danger" style="margin-bottom:16px; padding:10px; font-size:0.85rem; background:#fef2f2; color:#991b1b; border-radius:6px; border-left:3px solid #dc2626;">
            <?= e($err) ?>
          </div>
        <?php endif; ?>

        <form method="POST" class="login-form">
          <div class="field">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" 
                   placeholder="Masukkan username" required autofocus
                   value="<?= e($_POST['username'] ?? '') ?>">
          </div>

          <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" 
                   placeholder="Masukkan password" required>
          </div>

          <button type="submit" class="btn-submit">
            Login
          </button>
        </form>

        <a href="<?= BASE_URL ?>/index.php" class="back-link">
          ← Kembali ke Beranda
        </a>

      </div>
    </div>
  </div>

  <!-- FOOTER INFO LAYANAN -->
  <div class="login-footer">
    <!-- Gelombang hijau dekoratif -->
    <div class="footer-wave"></div> 
    
    <div class="footer-content">
      <div class="info-item">
        <div class="info-icon">📅</div>
        <span>Reservasi Online</span>
      </div>
      <div class="info-divider"></div>
      <div class="info-item">
        <div class="info-icon">🛡️</div>
        <span>Mudah & Aman</span>
      </div>
      <div class="info-divider"></div>
      <div class="info-item">
        <div class="info-icon">🕐</div>
        <span>Tersedia Setiap Hari</span>
      </div>
    </div>
  </div>

</body>
</html>
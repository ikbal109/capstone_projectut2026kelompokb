<?php
if (!isset($content)) $content = '';
if (!isset($title)) $title = 'Dashboard';
if (!isset($activeMenu)) $activeMenu = '';
if (!isset($pageTitle)) $pageTitle = $title;

$nama = $_SESSION['nama'] ?? 'Admin';

function menuItem($url, $icon, $label, $key, $activeMenu) {
    $cls = $activeMenu === $key ? 'active' : '';
    echo "<a href=\"$url\" class=\"$cls\"><span class=\"icon\">$icon</span> $label</a>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> — SIPASTRA</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
</head>
<body class="dash-body">

<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <!-- Logo SIPASTRA dipasang di sini dengan batasan ukuran -->
    <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="SIPASTRA" class="sidebar-logo">
    <div class="brand-text" style="margin-top: 10px;">PANEL ADMIN</div>
  </div>

  <nav class="sidebar-menu">
    <?php menuItem(BASE_URL . '/pages/admin/dashboard.php', '🏠', 'Dashboard', 'dashboard', $activeMenu); ?>
    <?php menuItem(BASE_URL . '/pages/admin/kelola_booking.php', '📅', 'Reservasi', 'reservasi', $activeMenu); ?>
    <?php menuItem(BASE_URL . '/pages/admin/kelola_sarana.php', '🏢', 'Fasilitas', 'fasilitas', $activeMenu); ?>
    
    <div class="sidebar-section">Laporan</div>
    <?php menuItem(BASE_URL . '/pages/admin/laporan.php', '📈', 'Laporan', 'laporan', $activeMenu); ?>

    <div class="sidebar-section">Akun</div>
    <?php menuItem(BASE_URL . '/pages/admin/profile.php', '👤', 'Profile Saya', 'profile', $activeMenu); ?>
    <?php menuItem(BASE_URL . '/index.php', '🏡', 'Ke Beranda', 'home', $activeMenu); ?>
    <?php menuItem(BASE_URL . '/pages/logout.php', '🚪', 'Logout', 'logout', $activeMenu); ?>
  </nav>
</aside>

<div class="main-area">
  <header class="topbar">
    <div style="display:flex;align-items:center;gap:14px;">
      <button class="topbar-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">☰</button>
      <h2><?= e($title) ?></h2>
    </div>
    <div class="user-info">
      Admin: <strong><?= e($nama) ?></strong>
    </div>
  </header>

  <main class="content">
    <?= $content ?>
  </main>
</div>

<script>
document.querySelectorAll('.sidebar-menu a').forEach(a => {
  a.addEventListener('click', () => {
    if (window.innerWidth <= 992) document.getElementById('sidebar').classList.remove('open');
  });
});
</script>
</body>
</html>
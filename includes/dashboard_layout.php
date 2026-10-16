<?php
/**
 * Layout wrapper untuk dashboard admin.
 * 
 * Cara pakai:
 *   ob_start(); ...isi konten...; $content = ob_get_clean();
 *   $title = 'Dashboard'; $activeMenu = 'dashboard';
 *   require_once __DIR__ . '/../../includes/dashboard_layout.php';
 */

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
<title><?= e($pageTitle) ?> — BPSDMD Kalsel</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dashboard.css">
</head>
<body class="dash-body">

<!-- ============ SIDEBAR ============ -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="logo-box">🏟️</div>
    <div class="brand-text">
      BPSDMD Kalsel
      <small>Reservasi Sarana</small>
    </div>
  </div>

  <nav class="sidebar-menu">
    <?php menuItem(BASE_URL . '/pages/admin/dashboard.php', '🏠', 'Dashboard', 'dashboard', $activeMenu); ?>

    <div class="sidebar-section">Manajemen</div>
    <?php menuItem(BASE_URL . '/pages/admin/kelola_booking.php', '📅', 'Reservasi', 'reservasi', $activeMenu); ?>
    <?php menuItem(BASE_URL . '/pages/admin/kelola_sarana.php', '🏢', 'Fasilitas', 'fasilitas', $activeMenu); ?>
    <?php menuItem(BASE_URL . '/pages/admin/kelola_diskon.php', '🎁', 'Diskon', 'diskon', $activeMenu); ?>

    <div class="sidebar-section">Akun</div>
    <?php menuItem(BASE_URL . '/pages/admin/profile.php', '👤', 'Profile Saya', 'profile', $activeMenu); ?>

    <div class="sidebar-section">Lainnya</div>
    <?php menuItem(BASE_URL . '/index.php', '🏡', 'Ke Beranda', 'home', $activeMenu); ?>
    <?php menuItem(BASE_URL . '/pages/logout.php', '🚪', 'Logout', 'logout', $activeMenu); ?>
  </nav>
</aside>

<!-- ============ MAIN AREA ============ -->
<div class="main-area">
  <header class="topbar">
    <div style="display:flex;align-items:center;gap:14px;">
      <button class="topbar-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">☰</button>
      <h2><?= e($title) ?></h2>
    </div>
    <div class="user-info" style="display:flex;align-items:center;gap:10px;">
      <a href="<?= BASE_URL ?>/pages/admin/profile.php" 
         style="display:flex;align-items:center;gap:8px;text-decoration:none;color:inherit;">
        <div style="width:32px;height:32px;border-radius:50%;background:#dcfce7;color:#166534;
                    display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;">
          <?= strtoupper(substr($nama,0,1)) ?>
        </div>
        <div>
          <div style="font-size:.85rem;color:#64748b;line-height:1.1;">Admin</div>
          <div style="font-weight:600;color:#166534;font-size:.9rem;"><?= e($nama) ?></div>
        </div>
      </a>
    </div>
  </header>

  <main class="content">
    <?= $content ?>
  </main>
</div>

<script>
document.querySelectorAll('.sidebar-menu a').forEach(a => {
  a.addEventListener('click', () => {
    if (window.innerWidth <= 900) document.getElementById('sidebar').classList.remove('open');
  });
});
</script>

</body>
</html>
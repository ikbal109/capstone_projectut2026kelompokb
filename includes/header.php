<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/functions.php';
$base = defined('BASE_URL') ? BASE_URL : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $title ?? 'Reservasi Sarana Olahraga BPSDMD Kalsel' ?></title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
</head>
<body>
<nav class="navbar">
  <div class="container">
    <a href="<?= $base ?>/index.php" class="brand">🏟️ BPSDMD Kalsel</a>
    <div>
      <a href="<?= $base ?>/index.php">Beranda</a>
      <a href="<?= $base ?>/pages/cek_booking.php">Cek Booking</a>
      <?php if (!empty($_SESSION['user_id'])): ?>
        <?php if ($_SESSION['role'] === 'admin'): ?>
          <a href="<?= $base ?>/pages/admin/dashboard.php">Dashboard</a>
        <?php else: ?>
          <a href="<?= $base ?>/pages/petugas/dashboard.php">Dashboard</a>
        <?php endif; ?>
        <a href="<?= $base ?>/pages/logout.php">Logout (<?= e($_SESSION['nama']) ?>)</a>
      <?php else: ?>
        <a href="<?= $base ?>/pages/login.php">Login Petugas</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
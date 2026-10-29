<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';

// Ambil sarana yang statusnya tersedia saja
$sarana = $conn->query("SELECT * FROM sarana WHERE status='tersedia' ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SIPASTRA - Reservasi Sarana Olahraga BPSDMD Kalsel</title>
<!-- Gunakan font Google mirip dengan desain -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/landing.css">
</head>
<body class="landing-body">

<!-- NAVBAR KUSTOM -->
<nav class="landing-navbar">
  <div class="landing-container nav-inner">
    <a href="<?= BASE_URL ?>/index.php" class="brand-logo">
      SIPASTRA<span>.</span>
    </a>

    <!-- Tombol Hamburger untuk HP -->
    <button class="mobile-menu-btn" onclick="toggleMenu()">☰</button>

    <!-- Menu Links -->
    <div class="nav-links" id="navLinks">
      <a href="#beranda">Beranda</a>
      <a href="<?= BASE_URL ?>/pages/login.php">Admin</a>
      <a href="<?= BASE_URL ?>/pages/informasi.php">Informasi</a>
      <a href="<?= BASE_URL ?>/pages/cek_booking.php" class="btn-cek-reservasi">Cek Reservasi</a>
    </div>
  </div>
</nav>

<!-- HERO SECTION -->
<section class="hero-section" id="beranda">
  <!-- Pastikan foto gedung BPSDMD ada di folder assets/img/ -->
  <div class="hero-bg" style="background-image: url('<?= BASE_URL ?>/assets/img/bpsdmd-bg.jpg');"></div>
  <div class="hero-overlay"></div>
  
  
    <div class="hero-content" style="margin-left: 10%;">
      <h1 class="hero-title">Reservasi Sarana Olahraga<br>dengan Mudah</h1>
      <p class="hero-subtitle">Sistem Informasi Pengelolaan dan Reservasi Sarana Olahraga Berbasis Web untuk memudahkan masyarakat melakukan pemesanan fasilitas olahraga.</p>
      <a href="#fasilitas" class="btn-mulai">Mulai Reservasi</a>
    </div>
  
</section>

<!-- FASILITAS SECTION -->
<section class="fasilitas-section" id="fasilitas">
  <div class="landing-container">
    <div class="section-header">
      <h2>Pilihan Fasilitas</h2>
      <p>Pilih fasilitas olahraga yang ingin Anda gunakan</p>
    </div>

    <div class="fasilitas-grid">
      <?php if ($sarana->num_rows === 0): ?>
        <div style="text-align:center; width:100%; color:#64748b; padding:40px;">
          Belum ada fasilitas yang tersedia saat ini.
        </div>
      <?php endif; ?>

      <?php while($s = $sarana->fetch_assoc()): ?>
        <a href="<?= BASE_URL ?>/pages/detail_sarana.php?slug=<?= urlencode($s['slug'] ?: $s['id']) ?>" class="fasilitas-card">
          <div class="card-img">
            <?php if ($s['gambar']): ?>
              <img src="<?= BASE_URL ?>/assets/img/sarana/<?= e($s['gambar']) ?>" alt="<?= e($s['nama']) ?>">
            <?php else: ?>
              <img src="https://via.placeholder.com/600x400?text=<?= urlencode($s['nama']) ?>" alt="">
            <?php endif; ?>
          </div>
          <div class="card-body">
            <div class="card-icon">
              <?php 
                // Ikon sederhana berdasarkan jenis
                $jenis = strtolower($s['jenis']);
                if (strpos($jenis, 'futsal') !== false || strpos($jenis, 'bola') !== false) echo '⚽';
                elseif (strpos($jenis, 'tenis') !== false) echo '🎾';
                elseif (strpos($jenis, 'badminton') !== false) echo '🏸';
                elseif (strpos($jenis, 'basket') !== false) echo '🏀';
                else echo '🏟️';
              ?>
            </div>
            <div class="card-text">
              <h3><?= e($s['nama']) ?></h3>
              <p><?= e($s['deskripsi']) ?></p>
            </div>
          </div>
        </a>
      <?php endwhile; ?>
    </div>
  </div>
</section>

<!-- STATS / INFORMASI SECTION -->
<section class="stats-section" id="informasi">
  <div class="landing-container stats-grid">
    <div class="stat-item">
      <h3>08.00</h3>
      <p>Jam mulai operasional</p>
    </div>
    <div class="stat-item">
      <h3>22.00</h3>
      <p>Jam akhir operasional</p>
    </div>
    <div class="stat-item">
      <h3>1 Jam</h3>
      <p>Batas waktu pembayaran</p>
    </div>
  </div>
</section>

<footer style="text-align:center; padding:20px; background:#f8fafc; color:#64748b; font-size:.85rem; border-top:1px solid #e2e8f0;">
  &copy; <?= date('Y') ?> SIPASTRA - BPSDMD Provinsi Kalimantan Selatan.
</footer>
<script>
  function toggleMenu() {
    var menu = document.getElementById("navLinks");
    menu.classList.toggle("active");
  }
</script>

</body>
</html>
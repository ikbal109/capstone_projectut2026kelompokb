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
    </div>
  
</section>

<!-- FASILITAS SECTION -->
<section class="fasilitas-section" id="fasilitas">
  <div class="landing-container">
    <div class="section-header">
      <h2>Pilihan Fasilitas</h2>
      <p>Pilih fasilitas olahraga yang ingin Anda gunakan</p>
    </div>

        <!-- AREA SLIDER FASILITAS -->
    <div style="position: relative;">
      <!-- Tombol Panah Kiri (Hanya muncul jika di desktop) -->
      <button onclick="document.getElementById('slider-fasilitas').scrollBy({left: -350, behavior: 'smooth'})" 
              class="slider-btn" style="left: -20px;">
        &#10094;
      </button>

      <!-- Container yang bisa di-scroll -->
      <div id="slider-fasilitas" style="display: flex; gap: 24px; overflow-x: auto; padding-bottom: 20px; scroll-snap-type: x mandatory; scrollbar-width: none; -ms-overflow-style: none; justify-content: center; max-width: 1000px; margin: 0 auto;">
        <style>
          /* Sembunyikan scrollbar bawaan browser di Chrome/Safari */
          #slider-fasilitas::-webkit-scrollbar { display: none; }
        </style>

        <?php if ($sarana->num_rows === 0): ?>
          <div style="width: 100%; text-align: center; color: #64748b; padding: 40px;">
            Belum ada fasilitas yang tersedia saat ini.
          </div>
        <?php endif; ?>

        <?php while($s = $sarana->fetch_assoc()): ?>
          <!-- Card Fasilitas (min-width ditentukan agar ukurannya seragam) -->
          <a href="<?= BASE_URL ?>/pages/detail_sarana.php?slug=<?= urlencode($s['slug'] ?: $s['id']) ?>" 
             class="fasilitas-card" 
             style="min-width: 300px; max-width: 300px; scroll-snap-align: start; flex-shrink: 0;">
            
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

      <!-- Tombol Panah Kanan -->
      <button onclick="document.getElementById('slider-fasilitas').scrollBy({left: 350, behavior: 'smooth'})" 
              class="slider-btn" style="right: -20px;">
        &#10095;
      </button>
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
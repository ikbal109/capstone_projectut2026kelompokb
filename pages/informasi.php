<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Informasi — SIPASTRA</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/landing.css">
<style>
  /* CSS Khusus Halaman Informasi */
  .info-section {
    padding: 60px 0;
    background-color: #f8fafc;
  }
  .info-header {
    text-align: center;
    margin-bottom: 40px;
  }
  .info-header h1 {
    font-size: 2.2rem;
    color: #1e293b;
    margin-bottom: 8px;
    font-weight: 800;
  }
  .info-header p {
    color: #64748b;
    font-size: 1rem;
  }

  .info-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.04);
    border: 1px solid #e2e8f0;
    margin-bottom: 40px;
    max-width: 1000px;
    margin-left: auto;
    margin-right: auto;
  }
  .info-card.border-left {
    border-left: 6px solid #166534;
  }
  .info-card h2 {
    font-size: 1.5rem;
    color: #166534;
    margin-bottom: 16px;
    font-weight: 700;
  }
  .info-card p {
    color: #475569;
    line-height: 1.8;
    font-size: 1rem;
    margin-bottom: 16px;
    text-align: justify;
  }
  .info-card p:last-child { margin-bottom: 0; }

  /* Grid untuk Map & Kontak */
  .contact-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    max-width: 1000px;
    margin: 0 auto;
  }
  .map-wrapper {
    background: #ffffff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.04);
    border: 1px solid #e2e8f0;
    min-height: 350px;
  }
  .map-wrapper iframe {
    width: 100%;
    height: 100%;
    min-height: 350px;
    border: none;
  }
  .contact-wrapper {
    background: #ffffff;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.04);
    border: 1px solid #e2e8f0;
  }
  .contact-wrapper h3 {
    font-size: 1.3rem;
    color: #1e293b;
    margin-bottom: 24px;
    font-weight: 700;
  }
  .contact-item {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 20px;
  }
  .contact-item:last-child { margin-bottom: 0; }
  .contact-icon {
    width: 40px;
    height: 40px;
    background: #f0fdf4;
    color: #166534;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
  }
  .contact-text h4 {
    margin: 0 0 4px 0;
    font-size: 0.95rem;
    color: #1e293b;
    font-weight: 600;
  }
  .contact-text p, .contact-text a {
    margin: 0;
    font-size: 0.9rem;
    color: #64748b;
    text-decoration: none;
    line-height: 1.5;
  }
  .contact-text a:hover { color: #166534; text-decoration: underline; }

  @media (max-width: 768px) {
    .contact-grid { grid-template-columns: 1fr; }
    .info-card { padding: 24px; }
    .map-wrapper { min-height: 250px; }
    .map-wrapper iframe { min-height: 250px; }
  }
</style>
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
      <a href="<?= BASE_URL ?>/index.php#beranda">Beranda</a>
      <a href="<?= BASE_URL ?>/pages/login.php">Admin</a>
      <a href="<?= BASE_URL ?>/pages/informasi.php" style="color:#166534; font-weight:600;">Informasi</a>
      <a href="<?= BASE_URL ?>/pages/cek_booking.php" class="btn-cek-reservasi">Cek Reservasi</a>
    </div>
  </div>
</nav>

<!-- KONTEN INFORMASI -->
<div class="info-section">
  <div class="landing-container">
    
    <div class="info-header">
      <h1>Informasi SIPASTRA</h1>
      <p>Informasi mengenai BPSDM Provinsi Kalimantan Selatan, fasilitas olahraga, dan layanan reservasi SIPASTRA.</p>
    </div>

    <!-- Tentang BPSDM -->
    <div class="info-card">
      <h2>Badan Pengembangan Sumber Daya Manusia Provinsi Kalimantan Selatan</h2>
      <p>Badan Pengembangan Sumber Daya Manusia Daerah Provinsi Kalimantan Selatan merupakan perangkat daerah yang mempunyai tugas membantu melaksanakan urusan pemerintahan fungsi penunjang bidang pengembangan sumber daya manusia yang menjadi kewenangan daerah.</p>
      <p>Dalam melaksanakan tugas tersebut, BPSDM Provinsi Kalimantan Selatan menyelenggarakan berbagai kegiatan pengembangan sumber daya manusia, termasuk kegiatan pendidikan dan pelatihan bagi aparatur.</p>
      <p>Sebagai bagian dari upaya meningkatkan kualitas pelayanan dan pemanfaatan teknologi informasi, dikembangkan SIPASTRA sebagai sistem informasi untuk membantu pengelolaan dan reservasi sarana olahraga secara lebih mudah dan terorganisasi.</p>
    </div>

    <!-- Tentang SIPASTRA -->
    <div class="info-card border-left">
      <h2>SIPASTRA</h2>
      <p>SIPASTRA merupakan Sistem Informasi Pengelolaan dan Reservasi Sarana Olahraga berbasis web yang digunakan untuk membantu masyarakat melakukan reservasi sarana olahraga pada BPSDM Provinsi Kalimantan Selatan.</p>
      <p>Sistem ini menyediakan fasilitas reservasi Lapangan Futsal dan Lapangan Tenis. Pemesan dapat memilih tanggal dan jam penggunaan fasilitas, mengisi data pemesanan, melakukan pembayaran, serta mengunggah bukti pembayaran melalui sistem.</p>
    </div>

    <!-- Kontak & Peta -->
    <div class="contact-grid">
      
      <!-- Peta Lokasi (Google Maps) -->
      <div class="map-wrapper">
        <!-- Ganti URL src iframe di bawah ini dengan link Google Maps BPSDM Kalsel yang asli -->
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3983.038525867793!2d114.58953431475417!3d-3.4451289975167184!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2de681009c4881e5%3A0xe54b4b2f7b8d6b86!2sBPSDM%20Provinsi%20Kalimantan%20Selatan!5e0!3m2!1sid!2sid!4v1698812345678!5m2!1sid!2sid" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
      </div>

      <!-- Informasi Kontak -->
      <div class="contact-wrapper">
        <h3>Hubungi Kami</h3>
        
        <div class="contact-item">
          <div class="contact-icon">📍</div>
          <div class="contact-text">
            <h4>Alamat Kantor</h4>
            <p>Jl. Panglima Batur No. 4, Loktabat Utara, Kec. Banjarbaru Utara, Kota Banjarbaru, Kalimantan Selatan 70714</p>
          </div>
        </div>

        <div class="contact-item">
          <div class="contact-icon">✉️</div>
          <div class="contact-text">
            <h4>Email Resmi</h4>
            <a href="mailto:bpsdm@kalselprov.go.id">bpsdm@kalselprov.go.id</a>
          </div>
        </div>

        <div class="contact-item">
          <div class="contact-icon">📞</div>
          <div class="contact-text">
            <h4>Telepon / WhatsApp</h4>
            <p>(0511) 4772345</p>
          </div>
        </div>

        <div class="contact-item">
          <div class="contact-icon">🕐</div>
          <div class="contact-text">
            <h4>Jam Operasional</h4>
            <p>Senin - Jumat<br>08.00 - 16.00 WITA</p>
          </div>
        </div>
        
      </div>
    </div>

  </div>
</div>

<footer style="text-align:center; padding:20px; background:#f8fafc; color:#64748b; font-size:.85rem; border-top:1px solid #e2e8f0;">
  &copy; <?= date('Y') ?> SIPASTRA - BPSDM Provinsi Kalimantan Selatan.
</footer>

<script>
  function toggleMenu() {
    var menu = document.getElementById("navLinks");
    menu.classList.toggle("active");
  }
</script>

</body>
</html>
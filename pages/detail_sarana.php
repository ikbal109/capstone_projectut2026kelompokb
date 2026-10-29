<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth.php';

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id) {
        $stmt = $conn->prepare("SELECT * FROM sarana WHERE id = ?");
        $stmt->bind_param('i', $id);
    } else {
        redirect(BASE_URL . '/index.php');
    }
} else {
    $stmt = $conn->prepare("SELECT * FROM sarana WHERE slug = ?");
    $stmt->bind_param('s', $slug);
}
$stmt->execute();
$s = $stmt->get_result()->fetch_assoc();

if (!$s) {
    die('<h2 style="text-align:center; margin-top:50px;">Sarana tidak ditemukan. <a href="'.BASE_URL.'/index.php">Kembali</a></h2>');
}

// Parse kelengkapan jadi list
$kelengkapanList = [];
if (!empty($s['kelengkapan'])) {
    foreach (preg_split('/\r\n|\r|\n/', $s['kelengkapan']) as $line) {
        $line = trim($line);
        if ($line !== '') $kelengkapanList[] = $line;
    }
}
$galeri = getGaleri($s['galeri']);

// Ambil booking terbaru untuk sarana ini (info jadwal)
$bookings = $conn->query("
    SELECT tanggal, jam_mulai, jam_selesai, status 
    FROM booking 
    WHERE sarana_id = {$s['id']} 
      AND status IN ('pending','terverifikasi')
      AND tanggal >= CURDATE()
    ORDER BY tanggal ASC, jam_mulai ASC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Detail <?= e($s['nama']) ?> — SIPASTRA</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/landing.css">
<style>
  /* CSS KHUSUS HALAMAN DETAIL SARANA */
  .detail-wrapper {
    padding: 100px 20px 60px 20px; /* Padding atas agar tidak tertutup navbar */
    max-width: 1200px;
    margin: 0 auto;
  }
  
  .detail-grid {
    display: grid;
    grid-template-columns: 1.6fr 1fr;
    gap: 32px;
    margin-top: 20px;
  }

  .back-link {
    display: inline-block;
    color: #64748b;
    text-decoration: none;
    font-size: 0.95rem;
    margin-bottom: 20px;
    font-weight: 500;
    transition: 0.2s;
  }
  .back-link:hover { color: #166534; }

  /* KIRI: FOTO & INFO */
  .main-image {
    width: 100%;
    height: 400px;
    object-fit: cover;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,.08);
  }
  .no-image {
    width: 100%;
    height: 400px;
    background: #e2e8f0;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    font-size: 1.1rem;
  }

  .section-title {
    font-size: 1.25rem;
    color: #1e293b;
    font-weight: 700;
    margin: 32px 0 16px 0;
  }

  .gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 12px;
  }
  .gallery-grid img {
    width: 100%;
    height: 110px;
    object-fit: cover;
    border-radius: 8px;
    cursor: pointer;
    transition: 0.2s;
    box-shadow: 0 2px 8px rgba(0,0,0,.05);
  }
  .gallery-grid img:hover { transform: scale(1.05); }

  .desc-text {
    color: #475569;
    line-height: 1.7;
    font-size: 1.05rem;
  }

  .fasilitas-box {
    background: #f0fdf4;
    border-radius: 12px;
    padding: 24px;
    border: 1px solid #dcfce7;
  }
  .fasilitas-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
  }
  .fasilitas-list li {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    color: #166534;
    font-size: 0.95rem;
    font-weight: 500;
  }

  /* KANAN: BOOKING CARD */
  .booking-card {
    background: #ffffff;
    padding: 28px;
    border-radius: 16px;
    box-shadow: 0 8px 30px rgba(0,0,0,.08);
    border: 1px solid #e2e8f0;
    position: sticky;
    top: 100px;
  }

  .badge-jenis {
    font-size: 0.8rem;
    background: #e0f2fe;
    color: #0369a1;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 600;
    text-transform: uppercase;
  }

  .sarana-title {
    margin: 16px 0 8px 0;
    font-size: 1.8rem;
    color: #1e293b;
    font-weight: 800;
    line-height: 1.2;
  }

  .price-box {
    margin: 24px 0;
    padding: 20px;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #f1f5f9;
  }
  .price-label {
    font-size: 0.85rem;
    color: #64748b;
    font-weight: 500;
  }
  .price-value {
    font-size: 1.8rem;
    font-weight: 800;
    color: #166534;
    margin-top: 4px;
  }
  .price-value span {
    font-size: 0.9rem;
    color: #64748b;
    font-weight: 400;
  }

  .status-box { margin: 16px 0 24px 0; }
  .status-label {
    font-size: 0.85rem;
    color: #64748b;
    margin-bottom: 6px;
    font-weight: 500;
  }
  .status-badge {
    display: inline-block;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
  }
  .status-tersedia { background: #dcfce7; color: #166534; }
  .status-maintenance { background: #fef3c7; color: #92400e; }

  .btn-booking {
    display: block;
    width: 100%;
    text-align: center;
    background: #166534;
    color: #ffffff;
    padding: 16px;
    font-size: 1.05rem;
    font-weight: 700;
    border-radius: 10px;
    text-decoration: none;
    transition: 0.2s;
    box-shadow: 0 4px 12px rgba(22,101,52,.2);
  }
  .btn-booking:hover {
    background: #14532d;
    transform: translateY(-2px);
  }
  .btn-disabled {
    background: #e2e8f0;
    color: #94a3b8;
    cursor: not-allowed;
    box-shadow: none;
  }
  .btn-disabled:hover { transform: none; background: #e2e8f0; }

  /* JADWAL TERDEKAT */
  .jadwal-card {
    background: #ffffff;
    padding: 24px;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,.04);
    border: 1px solid #e2e8f0;
    margin-top: 20px;
  }
  .jadwal-card h4 {
    margin: 0 0 16px 0;
    color: #1e293b;
    font-weight: 700;
    font-size: 1.1rem;
  }
  .jadwal-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
  }
  .jadwal-table tr { border-bottom: 1px solid #f1f5f9; }
  .jadwal-table td { padding: 12px 0; color: #334155; }
  .jadwal-table td:last-child { text-align: right; }
  
  .jadwal-badge {
    font-size: 0.75rem;
    padding: 4px 10px;
    border-radius: 20px;
    font-weight: 600;
  }
  .jadwal-pending { background: #fef3c7; color: #92400e; }
  .jadwal-terisi { background: #dcfce7; color: #166534; }

  /* RESPONSIVE */
  @media (max-width: 992px) {
    .detail-grid { grid-template-columns: 1fr; gap: 24px; }
    .main-image, .no-image { height: 300px; }
    .fasilitas-list { grid-template-columns: 1fr; }
    .booking-card { position: static; }
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
      <a href="<?= BASE_URL ?>/pages/informasi.php">Informasi</a>
      <a href="<?= BASE_URL ?>/pages/cek_booking.php" class="btn-cek-reservasi">Cek Reservasi</a>
    </div>
  </div>
</nav>

<div class="detail-wrapper">
  <a href="<?= BASE_URL ?>/index.php" class="back-link">← Kembali ke Beranda</a>

  <div class="detail-grid">
    
    <!-- KIRI: FOTO & INFO -->
    <div>
      <!-- Foto Utama -->
      <?php if ($s['gambar']): ?>
        <img src="<?= BASE_URL ?>/assets/img/sarana/<?= e($s['gambar']) ?>" class="main-image" alt="<?= e($s['nama']) ?>">
      <?php else: ?>
        <div class="no-image">📷 Belum ada foto</div>
      <?php endif; ?>

      <!-- Galeri -->
      <?php if ($galeri): ?>
        <h3 class="section-title">📸 Galeri Foto</h3>
        <div class="gallery-grid">
          <?php foreach ($galeri as $g): ?>
            <a href="<?= BASE_URL ?>/assets/img/sarana/<?= e($g) ?>" target="_blank">
              <img src="<?= BASE_URL ?>/assets/img/sarana/<?= e($g) ?>" alt="Galeri">
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Deskripsi -->
      <?php if (!empty($s['deskripsi'])): ?>
        <h3 class="section-title">📝 Tentang Sarana</h3>
        <p class="desc-text"><?= nl2br(e($s['deskripsi'])) ?></p>
      <?php endif; ?>

      <!-- Kelengkapan -->
      <?php if ($kelengkapanList): ?>
        <h3 class="section-title">✅ Kelengkapan & Fasilitas</h3>
        <div class="fasilitas-box">
          <ul class="fasilitas-list">
            <?php foreach ($kelengkapanList as $k): ?>
              <li><span>✓</span> <?= e($k) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    </div>

    <!-- KANAN: SIDEBAR BOOKING -->
    <div>
      <div class="booking-card">
        <span class="badge-jenis"><?= e($s['jenis']) ?></span>
        <h1 class="sarana-title"><?= e($s['nama']) ?></h1>

        <div class="price-box">
          <div class="price-label">Harga Sewa</div>
          <div class="price-value">
            Rp <?= number_format($s['harga_per_jam'],0,',','.') ?> 
            <span>/ jam</span>
          </div>
        </div>

        <div class="status-box">
          <div class="status-label">Status Ketersediaan</div>
          <span class="status-badge <?= $s['status']==='tersedia' ? 'status-tersedia' : 'status-maintenance' ?>">
            <?= $s['status']==='tersedia' ? '✅ Tersedia' : '🔧 Sedang Maintenance' ?>
          </span>
        </div>

        <?php if ($s['status'] === 'tersedia'): ?>
          <a href="booking.php?sarana_id=<?= $s['id'] ?>" class="btn-booking">
            📅 Booking Sekarang
          </a>
        <?php else: ?>
          <button class="btn-booking btn-disabled" disabled>
            🔧 Tidak Tersedia
          </button>
        <?php endif; ?>
      </div>

      <!-- Jadwal Terdekat -->
      <?php if ($bookings && $bookings->num_rows > 0): ?>
        <div class="jadwal-card">
          <h4>🗓️ Jadwal Terdekat</h4>
          <table class="jadwal-table">
            <?php while ($b = $bookings->fetch_assoc()): ?>
              <tr>
                <td>
                  <?= date('d M Y', strtotime($b['tanggal'])) ?><br>
                  <small style="color:#64748b;">
                    <?= substr($b['jam_mulai'],0,5) ?> - <?= substr($b['jam_selesai'],0,5) ?>
                  </small>
                </td>
                <td>
                  <span class="jadwal-badge <?= $b['status']==='terverifikasi'?'jadwal-terisi':'jadwal-pending' ?>">
                    <?= $b['status']==='terverifikasi' ? 'Terisi' : 'Pending' ?>
                  </span>
                </td>
              </tr>
            <?php endwhile; ?>
          </table>
          <small style="color:#94a3b8; display:block; margin-top:12px;">*Jadwal dapat berubah</small>
        </div>
      <?php endif; ?>
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
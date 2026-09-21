<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/header.php';

$sarana = $conn->query("SELECT * FROM sarana WHERE status='tersedia' ORDER BY nama");
$diskonAktif = $conn->query("SELECT * FROM diskon WHERE aktif=1 AND CURDATE() BETWEEN tanggal_mulai AND tanggal_selesai ORDER BY persen DESC");
?>

<div class="hero">
  <div class="container">
    <h1>Reservasi Sarana Olahraga</h1>
    <p>Badan Pengembangan Sumber Daya Manusia Daerah Provinsi Kalimantan Selatan</p>
  </div>
</div>

<div class="container page">
  <?php if ($diskonAktif->num_rows > 0): ?>
    <div class="alert alert-success">
      🎉 <strong>Promo Diskon Aktif:</strong>
      <?php while($d = $diskonAktif->fetch_assoc()): ?>
        <?= e($d['nama']) ?> (<?= (int)$d['persen'] ?>%)
        s/d <?= date('d M Y', strtotime($d['tanggal_selesai'])) ?> &nbsp;
      <?php endwhile; ?>
    </div>
  <?php endif; ?>

  <h2>Daftar Sarana Tersedia</h2>
  <div class="grid">
    <?php while($s = $sarana->fetch_assoc()): ?>
      <div class="card">
        <img src="<?= $s['gambar'] ? BASE_URL . '/assets/img/' . e($s['gambar']) : 'https://via.placeholder.com/400x200?text='.urlencode($s['nama']) ?>" alt="">
        <div class="card-body">
          <span class="badge badge-info"><?= e($s['jenis']) ?></span>
          <h3><?= e($s['nama']) ?></h3>
          <p style="color:#64748b;font-size:.9rem;"><?= e($s['deskripsi']) ?></p>
          <div class="price">Rp <?= number_format($s['harga_per_jam'],0,',','.') ?> / jam</div>
          <a href="<?= BASE_URL ?>/pages/booking.php?sarana_id=<?= $s['id'] ?>" class="btn btn-primary btn-block">Booking Sekarang</a>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
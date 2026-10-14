<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
$b = $conn->query("SELECT b.*, s.nama AS sarana FROM booking b JOIN sarana s ON s.id=b.sarana_id WHERE b.id=$id")->fetch_assoc();
if (!$b) die('Booking tidak ditemukan');

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container page">
  <div class="kode-box">
    <h2 style="color:#166534;">✅ Booking Berhasil!</h2>
    <p style="color:#64748b;margin-bottom:20px;">Simpan gambar kode booking di bawah ini dan tunjukkan ke petugas saat check-in.</p>

    <img src="<?= BASE_URL . '/' . e($b['kode_gambar']) ?>" alt="Kode Booking" style="max-width:100%;border-radius:8px;">
    <p style="margin-top:16px;">
      <a href="<?= BASE_URL . '/' . e($b['kode_gambar']) ?>" 
   download="kode-<?= e($b['kode_booking']) ?>.svg" 
   class="btn btn-accent">⬇️ Download Kode</a>
      <a href="<?= BASE_URL ?>/index.php" class="btn btn-primary">Selesai</a>
    </p>

    <hr style="margin:24px 0;border:none;border-top:1px solid #e2e8f0;">
    <table style="width:100%;text-align:left;font-size:.95rem;">
      <tr><td>Kode Booking</td><td><strong><?= e($b['kode_booking']) ?></strong></td></tr>
      <tr><td>Sarana</td><td><?= e($b['sarana']) ?></td></tr>
      <tr><td>Nama</td><td><?= e($b['nama_penyewa']) ?></td></tr>
      <tr><td>Tanggal</td><td><?= date('d M Y', strtotime($b['tanggal'])) ?></td></tr>
      <tr><td>Waktu</td><td><?= substr($b['jam_mulai'],0,5) ?> - <?= substr($b['jam_selesai'],0,5) ?> (<?= $b['durasi_jam'] ?> jam)</td></tr>
      <tr><td>Subtotal</td><td>Rp <?= number_format($b['subtotal'],0,',','.') ?></td></tr>
      <?php if ($b['diskon_persen'] > 0): ?>
        <tr><td>Diskon</td><td style="color:#16a34a;">-<?= (int)$b['diskon_persen'] ?>%</td></tr>
      <?php endif; ?>
      <tr><td><strong>Total Bayar</strong></td><td><strong style="color:#166534;">Rp <?= number_format($b['total_bayar'],0,',','.') ?></strong></td></tr>
      <tr><td>Status</td><td><span class="badge badge-warning"><?= e($b['status']) ?></span></td></tr>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
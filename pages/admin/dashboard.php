<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

$stats = [
  'sarana'  => $conn->query("SELECT COUNT(*) c FROM sarana")->fetch_assoc()['c'],
  'petugas' => $conn->query("SELECT COUNT(*) c FROM users WHERE role='petugas'")->fetch_assoc()['c'],
  'booking' => $conn->query("SELECT COUNT(*) c FROM booking")->fetch_assoc()['c'],
  'pending' => $conn->query("SELECT COUNT(*) c FROM booking WHERE status='pending'")->fetch_assoc()['c'],
];
$recent = $conn->query("SELECT b.*, s.nama sarana FROM booking b JOIN sarana s ON s.id=b.sarana_id ORDER BY b.created_at DESC LIMIT 10");

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="container page">
  <h2>Dashboard Admin</h2>
  <div class="grid" style="margin-top:20px;">
    <div class="card"><div class="card-body"><h3><?= $stats['sarana'] ?></h3><p>Total Sarana</p></div></div>
    <div class="card"><div class="card-body"><h3><?= $stats['petugas'] ?></h3><p>Total Petugas</p></div></div>
    <div class="card"><div class="card-body"><h3><?= $stats['booking'] ?></h3><p>Total Booking</p></div></div>
    <div class="card"><div class="card-body"><h3><?= $stats['pending'] ?></h3><p>Pending Verifikasi</p></div></div>
  </div>

  <h3 style="margin-top:32px;">Menu Admin</h3>
  <div class="grid" style="margin-top:12px;">
    <a href="kelola_sarana.php" class="card"><div class="card-body"><h3>🏟️ Kelola Sarana</h3><p>Tambah, edit, hapus sarana olahraga</p></div></a>
    <a href="kelola_petugas.php" class="card"><div class="card-body"><h3>👤 Kelola Petugas</h3><p>Manajemen akun petugas</p></div></a>
    <a href="kelola_diskon.php" class="card"><div class="card-body"><h3>🎁 Kelola Diskon</h3><p>Atur diskon sesuai tanggal</p></div></a>
    <a href="kelola_booking.php" class="card"><div class="card-body"><h3>📋 Data Booking</h3><p>Lihat semua booking</p></div></a>
  </div>

  <h3 style="margin-top:32px;">Booking Terbaru</h3>
  <table class="table">
    <thead><tr><th>Kode</th><th>Sarana</th><th>Penyewa</th><th>Tanggal</th><th>Total</th><th>Status</th></tr></thead>
    <tbody>
      <?php while($r = $recent->fetch_assoc()): ?>
        <tr>
          <td><?= e($r['kode_booking']) ?></td>
          <td><?= e($r['sarana']) ?></td>
          <td><?= e($r['nama_penyewa']) ?></td>
          <td><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
          <td>Rp <?= number_format($r['total_bayar'],0,',','.') ?></td>
          <td><span class="badge badge-<?= $r['status']==='terverifikasi'?'success':($r['status']==='pending'?'warning':'danger') ?>"><?= e($r['status']) ?></span></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
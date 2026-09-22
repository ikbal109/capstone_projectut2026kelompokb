<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['petugas','admin']);

// Proses verifikasi / tolak
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $aksi = $_POST['aksi'];
    $catatan = trim($_POST['catatan'] ?? '');
    $status = $aksi === 'verifikasi' ? 'terverifikasi' : 'ditolak';
    $stmt = $conn->prepare("UPDATE booking SET status=?, catatan_petugas=?, verified_by=?, verified_at=NOW() WHERE id=?");
    $stmt->bind_param('ssii', $status, $catatan, $_SESSION['user_id'], $id);
    $stmt->execute();
    flash('msg', "Booking berhasil di-$status.");
    redirect(BASE_URL . '/pages/petugas/verifikasi.php');
}

// Cari berdasarkan kode booking
$kode = trim($_GET['kode'] ?? '');
$data = null;
if ($kode) {
    $stmt = $conn->prepare("SELECT b.*, s.nama sarana FROM booking b JOIN sarana s ON s.id=b.sarana_id WHERE b.kode_booking=?");
    $stmt->bind_param('s', $kode); $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
}

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="container page">
  <h2>Verifikasi Booking</h2>
  <?php if ($m = flash('msg')): ?><div class="alert alert-success"><?= e($m) ?></div><?php endif; ?>

  <form method="GET" style="background:#fff;padding:20px;border-radius:12px;margin-bottom:20px;box-shadow:0 2px 12px rgba(0,0,0,.06);">
    <div class="form-group"><label>Masukkan Kode Booking</label>
      <input name="kode" class="form-control" placeholder="BKG-xxxxx-xxxxx" value="<?= e($kode) ?>" required></div>
    <button class="btn btn-primary">Cari</button>
  </form>

  <?php if ($kode && !$data): ?>
    <div class="alert alert-danger">Kode booking tidak ditemukan.</div>
  <?php endif; ?>

  <?php if ($data): ?>
    <div class="card" style="padding:24px;">
      <h3><?= e($data['kode_booking']) ?></h3>
      <?php if ($data['kode_gambar']): ?>
        <img src="<?= BASE_URL . '/' . e($data['kode_gambar']) ?>" style="max-width:400px;margin:12px 0;border-radius:8px;">
      <?php endif; ?>
      <table style="width:100%;font-size:.95rem;margin:12px 0;">
        <tr><td>Sarana</td><td>: <?= e($data['sarana']) ?></td></tr>
        <tr><td>Penyewa</td><td>: <?= e($data['nama_penyewa']) ?> (<?= e($data['no_hp']) ?>)</td></tr>
        <tr><td>Tanggal</td><td>: <?= date('d M Y', strtotime($data['tanggal'])) ?></td></tr>
        <tr><td>Waktu</td><td>: <?= substr($data['jam_mulai'],0,5) ?> - <?= substr($data['jam_selesai'],0,5) ?></td></tr>
        <tr><td>Total</td><td>: Rp <?= number_format($data['total_bayar'],0,',','.') ?></td></tr>
        <tr><td>Status</td><td>: <span class="badge badge-warning"><?= e($data['status']) ?></span></td></tr>
      </table>

      <?php if ($data['status'] === 'pending'): ?>
        <form method="POST" style="margin-top:16px;">
          <input type="hidden" name="id" value="<?= $data['id'] ?>">
          <div class="form-group"><label>Catatan (opsional)</label><textarea name="catatan" class="form-control"></textarea></div>
          <button name="aksi" value="verifikasi" class="btn btn-primary">✅ Verifikasi</button>
          <button name="aksi" value="tolak" class="btn btn-danger" onclick="return confirm('Yakin menolak?')">❌ Tolak</button>
        </form>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
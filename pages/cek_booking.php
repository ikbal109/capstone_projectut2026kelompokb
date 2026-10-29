<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth.php';

$keyword = trim($_GET['q'] ?? '');
$hasil   = [];
$error   = '';

// Hanya mencari berdasarkan Kode Booking (Nomor HP dihilangkan)
if ($keyword !== '') {
    $stmt = $conn->prepare("
        SELECT b.*, s.nama AS sarana, s.jenis AS jenis_sarana
        FROM booking b
        JOIN sarana s ON s.id = b.sarana_id
        WHERE b.kode_booking = ?
        ORDER BY b.created_at DESC
        LIMIT 1
    ");
    $stmt->bind_param('s', $keyword);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $hasil[] = $row;

    if (empty($hasil)) $error = 'Kode Reservasi tidak ditemukan. Periksa kembali kode Anda.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cek Reservasi — SIPASTRA</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<style>
  /* CSS Khusus Halaman Cek Booking */
  body { font-family: 'Poppins', sans-serif; background: #f8fafc; margin: 0; padding: 0; color: #334155; }
  
  /* Navbar Publik */
  .public-nav { background: #fff; padding: 16px 0; border-bottom: 1px solid #e2e8f0; }
  .nav-container { max-width: 1000px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; }
  .brand-logo { font-size: 1.5rem; font-weight: 800; color: #0f172a; text-decoration: none; letter-spacing: 1px; }
  .brand-logo span { color: #166534; }
  .nav-links { display: flex; align-items: center; gap: 20px; }
  .nav-links a { text-decoration: none; color: #334155; font-weight: 500; font-size: 0.95rem; transition: 0.2s; }
  .nav-links a:hover { color: #166534; }
  .btn-nav { background-color: #0f766e; color: #fff !important; padding: 10px 20px; border-radius: 6px; font-weight: 600; }
  .btn-nav:hover { background-color: #115e59; }

  /* Konten Utama */
  .main-content { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
  .page-title h1 { font-size: 2rem; color: #1e293b; margin: 0 0 8px 0; font-weight: 700; }
  .page-title p { color: #64748b; font-size: 1rem; margin: 0 0 24px 0; }

  /* Kotak Pencarian */
  .search-box { background: #fff; border-radius: 12px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid #e2e8f0; margin-bottom: 30px; }
  .search-box h2 { font-size: 1.25rem; color: #1e293b; margin: 0 0 8px 0; font-weight: 700; }
  .search-box p { font-size: 0.9rem; color: #64748b; margin: 0 0 20px 0; }
  
  .form-group { margin-bottom: 20px; }
  .form-group label { display: block; font-size: 0.9rem; font-weight: 600; color: #1e293b; margin-bottom: 8px; }
  .form-control { width: 100%; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; font-family: inherit; box-sizing: border-box; }
  .form-control:focus { outline: none; border-color: #0f766e; box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1); }
  
  .btn-search { background: #0f766e; color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-size: 0.95rem; font-weight: 600; cursor: pointer; transition: 0.2s; font-family: inherit; }
  .btn-search:hover { background: #115e59; }

  /* Alert */
  .alert-danger { background: #fef2f2; border-left: 4px solid #ef4444; color: #991b1b; padding: 16px; border-radius: 8px; margin-bottom: 24px; font-size: 0.9rem; }
  .alert-success { background: #f0fdf4; border-left: 4px solid #22c55e; color: #166534; padding: 16px; border-radius: 8px; margin-bottom: 24px; font-size: 0.9rem; }

  /* Responsive */
  @media (max-width: 768px) {
    .nav-links a:not(.btn-nav) { display: none; }
    .page-title h1 { font-size: 1.5rem; }
    .search-box { padding: 20px; }
  }
</style>
</head>
<body>

<!-- NAVBAR PUBLIK -->
<nav class="public-nav">
  <div class="nav-container">
    <a href="<?= BASE_URL ?>/index.php" class="brand-logo">SIPASTRA<span>.</span></a>
    <div class="nav-links">
      <a href="<?= BASE_URL ?>/index.php">Beranda</a>
      <a href="<?= BASE_URL ?>/pages/cek_booking.php" class="btn-nav">Cek Reservasi</a>
    </div>
  </div>
</nav>

<!-- MAIN CONTENT -->
<div class="main-content">
  
  <div class="page-title">
    <h1>Cek Reservasi</h1>
    <p>Masukkan kode reservasi untuk melihat detail reservasi dan mengunggah bukti pembayaran.</p>
  </div>

  <!-- FORM PENCARIAN -->
  <div class="search-box">
    <h2>Cari Reservasi</h2>
    <p>Silakan masukkan kode yang didapatkan saat melakukan reservasi.</p>
    
    <form method="GET">
      <div class="form-group">
        <label for="kode">Kode Reservasi</label>
        <input type="text" id="kode" name="q" class="form-control" 
               placeholder="Contoh: BKG-261017-A1B2C" 
               value="<?= e($keyword) ?>" required autofocus>
      </div>
      <button type="submit" class="btn-search">Cek Reservasi</button>
    </form>
  </div>

  <!-- NOTIFIKASI / ALERT -->
  <?php if ($m = flash('msg')): ?>
    <div class="alert alert-success"><?= e($m) ?></div>
  <?php endif; ?>
  <?php if ($m = flash('err')): ?>
    <div class="alert alert-danger"><?= e($m) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
  <?php endif; ?>

  <!-- HASIL PENCARIAN -->
  <?php if (!empty($hasil)): ?>
    <?php foreach ($hasil as $b): ?>
      <?php
        $statusMap = [
          'pending'       => ['#fef3c7', '#92400e', '⏳ Menunggu Verifikasi'],
          'terverifikasi' => ['#dcfce7', '#166534', '✅ Terverifikasi'],
          'ditolak'       => ['#fee2e2', '#991b1b', '❌ Ditolak'],
          'selesai'       => ['#dbeafe', '#1e40af', '🎉 Selesai'],
          'batal'         => ['#f1f5f9', '#64748b', '🚫 Dibatalkan'],
        ];
        [$sbg, $scol, $slabel] = $statusMap[$b['status']] ?? ['#f1f5f9', '#64748b', $b['status']];

        [$pbg, $pcolor, $plabel] = labelPembayaran($b['status_pembayaran'] ?? 'belum_bayar');
        $isTransfer = ($b['metode_pembayaran'] ?? '') === 'transfer';
        $isQris = ($b['metode_pembayaran'] ?? '') === 'qris';
        $perluUpload = $isTransfer || $isQris;
      ?>

      <div style="background:#fff; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,0.03); border:1px solid #e2e8f0; overflow:hidden; margin-bottom:24px;">
        
        <!-- Header Card -->
        <div style="background:#166534; color:#fff; padding:20px 24px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
          <div>
            <div style="font-size:0.8rem; opacity:0.8; text-transform:uppercase; letter-spacing:1px;">Kode Reservasi</div>
            <div style="font-size:1.4rem; font-weight:700; letter-spacing:1px;"><?= e($b['kode_booking']) ?></div>
          </div>
          <span style="background:<?= $sbg ?>; color:<?= $scol ?>; padding:6px 14px; border-radius:20px; font-size:0.85rem; font-weight:600;">
            <?= $slabel ?>
          </span>
        </div>

        <!-- Body Card -->
        <div style="padding:24px;">
          <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:20px; margin-bottom:24px;">
            <div>
              <div style="font-size:0.8rem; color:#64748b; margin-bottom:4px;">Sarana Olahraga</div>
              <div style="font-weight:600; font-size:1.05rem; color:#1e293b;"><?= e($b['sarana']) ?></div>
              <span style="font-size:0.75rem; background:#e0f2fe; color:#0369a1; padding:2px 8px; border-radius:10px;"><?= e($b['jenis_sarana']) ?></span>
            </div>
            <div>
              <div style="font-size:0.8rem; color:#64748b; margin-bottom:4px;">Tanggal Sewa</div>
              <div style="font-weight:600; font-size:1.05rem; color:#1e293b;"><?= date('d F Y', strtotime($b['tanggal'])) ?></div>
            </div>
            <div>
              <div style="font-size:0.8rem; color:#64748b; margin-bottom:4px;">Waktu</div>
              <div style="font-weight:600; font-size:1.05rem; color:#1e293b;">
                <?= substr($b['jam_mulai'],0,5) ?> - <?= substr($b['jam_selesai'],0,5) ?> 
                <span style="font-size:0.85rem; font-weight:400; color:#64748b;">(<?= $b['durasi_jam'] ?> jam)</span>
              </div>
            </div>
            <div>
              <div style="font-size:0.8rem; color:#64748b; margin-bottom:4px;">Atas Nama</div>
              <div style="font-weight:600; font-size:1.05rem; color:#1e293b;"><?= e($b['nama_penyewa']) ?></div>
            </div>
          </div>

          <!-- Rincian Biaya -->
          <div style="background:#f8fafc; border-radius:10px; padding:20px; margin-bottom:24px; border:1px solid #f1f5f9;">
            <table style="width:100%; font-size:0.95rem; border-collapse:collapse;">
              <tr>
                <td style="padding:6px 0; color:#64748b;">Harga per jam</td>
                <td style="text-align:right; padding:6px 0;">Rp <?= number_format($b['harga_per_jam'],0,',','.') ?></td>
              </tr>
              <tr>
                <td style="padding:6px 0; color:#64748b;">Subtotal (<?= $b['durasi_jam'] ?> jam)</td>
                <td style="text-align:right; padding:6px 0;">Rp <?= number_format($b['subtotal'],0,',','.') ?></td>
              </tr>
              <tr style="border-top:1px solid #e2e8f0;">
                <td style="padding:12px 0 0 0; font-weight:700; font-size:1.05rem; color:#1e293b;">Total Bayar</td>
                <td style="text-align:right; padding:12px 0 0 0; font-weight:700; font-size:1.2rem; color:#166534;">Rp <?= number_format($b['total_bayar'],0,',','.') ?></td>
              </tr>
            </table>
          </div>

          <!-- Status Pembayaran & Upload -->
          <div style="border:1px solid #e2e8f0; border-radius:10px; padding:20px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
              <div>
                <span style="font-size:0.85rem; color:#64748b;">Status Pembayaran:</span>
                <strong style="color:<?= $pcolor ?>; font-size:1rem; margin-left:8px;"><?= $plabel ?></strong>
              </div>
              <div style="font-size:0.85rem; font-weight:600; background:#f1f5f9; padding:4px 10px; border-radius:6px;">
                Metode: <?= $isQris ? '📱 QRIS' : ($isTransfer ? '🏦 Transfer Bank' : '—') ?>
              </div>
            </div>

            <?php if ($b['catatan_pembayaran']): ?>
              <div style="background:#f8fafc; padding:12px; border-radius:6px; font-size:0.88rem; margin-bottom:16px; border-left:3px solid #3b82f6;">
                <strong>Catatan Admin:</strong> <?= nl2br(e($b['catatan_pembayaran'])) ?>
              </div>
            <?php endif; ?>

            <?php if ($perluUpload && in_array($b['status_pembayaran'], ['belum_bayar','gagal'])): ?>
              <div style="margin-top:16px; padding-top:16px; border-top:1px dashed #e2e8f0;">
                <div style="font-weight:600; margin-bottom:12px; color:#1e293b;">📤 Unggah Bukti Pembayaran (QRIS/Transfer)</div>
                <form action="<?= BASE_URL ?>/api/upload_bukti.php" method="POST" enctype="multipart/form-data">
                  <input type="hidden" name="kode_booking" value="<?= e($b['kode_booking']) ?>">
                  <!-- Parameter no_hp dikosongkan agar tidak error di backend -->
                  <input type="hidden" name="no_hp" value="<?= e($b['no_hp']) ?>">
                  
                  <input type="file" name="bukti" class="form-control" accept="image/*,.pdf" required style="margin-bottom:12px; background:#fff;">
                  <small style="color:#64748b; display:block; margin-bottom:12px;">Format: JPG, PNG, WEBP, atau PDF. Maks 5 MB.</small>
                  <button type="submit" class="btn-search">Unggah Bukti</button>
                </form>
              </div>
            <?php endif; ?>

            <?php if ($isTransfer && $b['status_pembayaran'] === 'menunggu_verifikasi' && !empty($b['bukti_transfer'])): ?>
              <div style="margin-top:16px; padding-top:16px; border-top:1px dashed #e2e8f0;">
                <div style="font-weight:600; margin-bottom:12px; color:#1e293b;">🧾 Bukti Transfer Terkirim</div>
                <img src="<?= BASE_URL . '/' . e($b['bukti_transfer']) ?>" style="max-width:200px; border-radius:8px; border:1px solid #e2e8f0;">
                <div style="font-size:0.85rem; color:#64748b; margin-top:8px;">Menunggu verifikasi admin (1x24 jam).</div>
              </div>
            <?php endif; ?>
            
            <?php if ($b['status_pembayaran'] === 'berhasil'): ?>
              <div style="margin-top:16px; font-size:0.9rem; color:#166534; background:#f0fdf4; padding:12px; border-radius:6px; font-weight:600;">
                ✅ Pembayaran Lunas.
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</div>

</body>
</html>
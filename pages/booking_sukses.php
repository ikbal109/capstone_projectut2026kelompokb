<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
$b = $conn->query("SELECT b.*, s.nama AS sarana FROM booking b JOIN sarana s ON s.id=b.sarana_id WHERE b.id=$id")->fetch_assoc();
if (!$b) die('Booking tidak ditemukan');

// Ambil info QRIS & Bank
$qrisImage  = getPengaturan($conn, 'qris_image', '');
$namaBank   = getPengaturan($conn, 'nama_bank', 'Bank Kalsel');
$noRekening = getPengaturan($conn, 'nomor_rekening', '-');
$pemilik    = getPengaturan($conn, 'nama_pemilik_rekening', '-');

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container page">
  <div class="kode-box" style="max-width: 650px;">
    <h2 style="color:#166534;">✅ Booking Berhasil!</h2>
    <p style="color:#64748b;margin-bottom:20px;">Simpan gambar kode booking di bawah ini dan tunjukkan ke petugas saat check-in.</p>

    <!-- ALERT JIKA BARU SAJA UPLOAD BUKTI -->
    <?php if ($m = flash('msg')): ?>
      <div class="alert alert-success" style="margin-bottom:20px; text-align:left;"><?= e($m) ?></div>
    <?php endif; ?>
    <?php if ($m = flash('err')): ?>
      <div class="alert alert-danger" style="margin-bottom:20px; text-align:left;"><?= e($m) ?></div>
    <?php endif; ?>

    <img src="<?= BASE_URL . '/' . e($b['kode_gambar']) ?>" alt="Kode Booking" style="max-width:100%;border-radius:8px;">
    <p style="margin-top:16px;">
      <a href="<?= BASE_URL . '/' . e($b['kode_gambar']) ?>" download="kode-<?= e($b['kode_booking']) ?>.svg" class="btn btn-accent">⬇️ Download Kode</a>
      <a href="<?= BASE_URL ?>/index.php" class="btn btn-primary">Selesai</a>
    </p>

    <hr style="margin:24px 0;border:none;border-top:1px solid #e2e8f0;">
    <table style="width:100%;text-align:left;font-size:.95rem;">
      <tr><td>Kode Booking</td><td><strong><?= e($b['kode_booking']) ?></strong></td></tr>
      <tr><td>Sarana</td><td><?= e($b['sarana']) ?></td></tr>
      <tr><td>Tanggal</td><td><?= date('d M Y', strtotime($b['tanggal'])) ?></td></tr>
      <tr><td>Total Bayar</td><td><strong style="color:#166534;">Rp <?= number_format($b['total_bayar'],0,',','.') ?></strong></td></tr>
    </table>

    <!-- INSTRUKSI PEMBAYARAN -->
    <div style="margin-top:24px;text-align:left;">
      <h3 style="color:#166534;margin-bottom:12px;">💳 Instruksi Pembayaran</h3>

      <?php if ($b['metode_pembayaran'] === 'qris'): ?>
        <div style="background:#f0fdf4;border-left:4px solid #166534;border-radius:8px;padding:16px; text-align:center;">
          <div style="font-weight:700;color:#166534;margin-bottom:12px;">📱 Pembayaran QRIS</div>
          
          <?php if ($b['status_pembayaran'] === 'belum_bayar' || $b['status_pembayaran'] === 'gagal'): ?>
            <p style="color:#475569;font-size:.92rem;margin-bottom:16px;">Silakan scan gambar QRIS di bawah ini menggunakan aplikasi M-Banking atau E-Wallet Anda.</p>
            <?php if ($qrisImage): ?>
              <img src="<?= BASE_URL ?>/assets/img/<?= e($qrisImage) ?>" style="max-width:250px; border-radius:8px; margin-bottom:16px; box-shadow:0 4px 12px rgba(0,0,0,0.1);">
            <?php else: ?>
              <div style="padding:20px; background:#fee2e2; color:#991b1b; border-radius:8px; margin-bottom:16px;">Gambar QRIS belum diatur oleh Admin.</div>
            <?php endif; ?>
          <?php endif; ?>

          <div style="padding:10px;background:#fff;border-radius:6px; display:inline-block; margin-bottom:16px;">
            <strong>Total yang harus dibayar:</strong>
            <span style="color:#166534;font-size:1.15rem;font-weight:800;">Rp <?= number_format($b['total_bayar'],0,',','.') ?></span>
          </div>

        </div>

      <?php elseif ($b['metode_pembayaran'] === 'transfer'): ?>
        <div style="background:#dbeafe;border-left:4px solid #3b82f6;border-radius:8px;padding:16px;">
          <div style="font-weight:700;color:#1e40af;margin-bottom:10px;">🏦 Transfer Bank</div>
          <table style="width:100%;font-size:.95rem; margin-bottom:12px;">
            <tr><td style="color:#64748b;padding:4px 0;width:150px;">Bank</td><td>: <strong><?= e($namaBank) ?></strong></td></tr>
            <tr><td style="color:#64748b;padding:4px 0;">No. Rekening</td><td>: <strong style="font-size:1.1rem;color:#1e40af;letter-spacing:1px;"><?= e($noRekening) ?></strong></td></tr>
            <tr><td style="color:#64748b;padding:4px 0;">Atas Nama</td><td>: <strong><?= e($pemilik) ?></strong></td></tr>
            <tr><td style="color:#64748b;padding:4px 0;">Jumlah</td><td>: <strong style="color:#166534;">Rp <?= number_format($b['total_bayar'],0,',','.') ?></strong></td></tr>
          </table>
        </div>
      <?php endif; ?>

      <!-- ========================================== -->
      <!-- AREA UPLOAD BUKTI PEMBAYARAN (QRIS/TRANSFER) -->
      <!-- ========================================== -->
      <div style="margin-top:20px; padding-top:20px; border-top:1px dashed #cbd5e1;">
        
        <?php if ($b['status_pembayaran'] === 'berhasil'): ?>
          <div style="text-align:center; color:#166534; font-weight:700; font-size:1.1rem;">
            ✅ Pembayaran telah diverifikasi dan Lunas.
          </div>
        
        <?php elseif ($b['status_pembayaran'] === 'menunggu_verifikasi'): ?>
          <div style="text-align:center; background:#eff6ff; padding:20px; border-radius:8px; border:1px solid #bfdbfe;">
            <h4 style="color:#1e40af; margin:0 0 12px 0; font-size:1.1rem;">⏳ Bukti Pembayaran Sedang Diverifikasi</h4>
            <p style="color:#3b82f6; font-size:0.9rem; margin-bottom:16px;">Admin akan memverifikasi pembayaran Anda dalam 1x24 jam.</p>
            <?php if ($b['bukti_transfer']): ?>
              <img src="<?= BASE_URL . '/' . e($b['bukti_transfer']) ?>" style="max-width:200px; border-radius:8px; border:2px solid #fff; box-shadow:0 2px 8px rgba(0,0,0,.1);">
            <?php endif; ?>
          </div>

        <?php else: ?>
          <div style="background:#fffbeb; padding:16px; border-radius:8px; border:1px solid #fde68a;">
            <h4 style="color:#92400e; margin:0 0 8px 0; font-size:1rem;">📤 Unggah Bukti Pembayaran</h4>
            <p style="color:#b45309; font-size:0.85rem; margin-bottom:12px;">Silakan unggah screenshot/foto bukti QRIS atau Transfer Anda di sini.</p>
            
            <form action="<?= BASE_URL ?>/api/upload_bukti.php" method="POST" enctype="multipart/form-data">
              <input type="hidden" name="kode_booking" value="<?= e($b['kode_booking']) ?>">
              <input type="hidden" name="no_hp" value="<?= e($b['no_hp']) ?>">
              <input type="hidden" name="redirect_sukses" value="1"> <!-- Flag agar redirect kembali ke sini -->
              
              <input type="file" name="bukti" class="form-control" accept="image/*,.pdf" required style="margin-bottom:12px; background:#fff; border:1px solid #cbd5e1;">
              <small style="color:#64748b; display:block; margin-bottom:12px;">Format: JPG, PNG, atau PDF. Maks 5 MB.</small>
              <button type="submit" class="btn btn-primary" style="width:100%;">Unggah Bukti Sekarang</button>
            </form>
          </div>
        <?php endif; ?>

      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
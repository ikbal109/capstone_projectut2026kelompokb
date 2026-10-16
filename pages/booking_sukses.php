<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
$b = $conn->query("SELECT b.*, s.nama AS sarana FROM booking b JOIN sarana s ON s.id=b.sarana_id WHERE b.id=$id")->fetch_assoc();
if (!$b) die('Booking tidak ditemukan');

// Info rekening
$namaBank     = getPengaturan($conn, 'nama_bank', 'Bank Kalsel');
$noRekening   = getPengaturan($conn, 'nomor_rekening', '-');
$pemilik      = getPengaturan($conn, 'nama_pemilik_rekening', '-');
$catatanTunai = getPengaturan($conn, 'catatan_tunai', '');

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container page">
  <div class="kode-box">
    <h2 style="color:#166534;">✅ Booking Berhasil!</h2>
    <p style="color:#64748b;margin-bottom:20px;">Simpan gambar kode booking di bawah ini dan tunjukkan ke petugas saat check-in.</p>

    <img src="<?= BASE_URL . '/' . e($b['kode_gambar']) ?>" alt="Kode Booking" style="max-width:100%;border-radius:8px;">
    <p style="margin-top:16px;">
      <a href="<?= BASE_URL . '/' . e($b['kode_gambar']) ?>" download="kode-<?= e($b['kode_booking']) ?>.svg" class="btn btn-accent">⬇️ Download Kode</a>
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
      <tr><td><strong>Total Bayar</strong></td><td><strong style="color:#166534;">Rp <?= number_format($b['total_bayar'],0,',','.') ?></strong></td></tr>
      <tr><td>Status</td><td><span class="badge badge-warning"><?= e($b['status']) ?></span></td></tr>
    </table>

    <!-- ====== INSTRUKSI PEMBAYARAN ====== -->
    <div style="margin-top:24px;text-align:left;">
      <h3 style="color:#166534;margin-bottom:12px;">💳 Instruksi Pembayaran</h3>

      <?php if ($b['metode_pembayaran'] === 'tunai'): ?>
        <div style="background:#fef3c7;border-left:4px solid #f59e0b;border-radius:8px;padding:16px;">
          <div style="font-weight:700;color:#92400e;margin-bottom:6px;">💵 Pembayaran Tunai</div>
          <p style="color:#78350f;font-size:.92rem;line-height:1.6;">
            <?= nl2br(e($catatanTunai)) ?>
          </p>
          <div style="margin-top:10px;padding:10px;background:#fff;border-radius:6px;">
            <strong>Total yang harus dibayar:</strong>
            <span style="color:#166534;font-size:1.15rem;font-weight:800;">
              Rp <?= number_format($b['total_bayar'],0,',','.') ?>
            </span>
          </div>
        </div>

      <?php elseif ($b['metode_pembayaran'] === 'transfer'): ?>
        <div style="background:#dbeafe;border-left:4px solid #3b82f6;border-radius:8px;padding:16px;">
          <div style="font-weight:700;color:#1e40af;margin-bottom:10px;">🏦 Transfer Bank</div>
          <table style="width:100%;font-size:.95rem;">
            <tr><td style="color:#64748b;padding:4px 0;width:150px;">Bank</td><td>: <strong><?= e($namaBank) ?></strong></td></tr>
            <tr><td style="color:#64748b;padding:4px 0;">No. Rekening</td>
                <td>: <strong style="font-size:1.1rem;color:#1e40af;letter-spacing:1px;"><?= e($noRekening) ?></strong></td></tr>
            <tr><td style="color:#64748b;padding:4px 0;">Atas Nama</td><td>: <strong><?= e($pemilik) ?></strong></td></tr>
            <tr><td style="color:#64748b;padding:4px 0;">Jumlah</td>
                <td>: <strong style="color:#166534;">Rp <?= number_format($b['total_bayar'],0,',','.') ?></strong></td></tr>
          </table>
          <div style="margin-top:14px;padding:12px;background:#fff;border-radius:6px;font-size:.88rem;">
            <strong>📌 Langkah selanjutnya:</strong>
            <ol style="margin:8px 0 0 20px;color:#475569;line-height:1.7;">
              <li>Lakukan transfer sesuai nominal di atas</li>
              <li>Simpan bukti transfer (screenshot / foto struk)</li>
              <li>Buka halaman 
                <a href="<?= BASE_URL ?>/pages/cek_booking.php?q=<?= urlencode($b['kode_booking']) ?>" 
                   style="color:#166534;font-weight:700;">Cek Booking</a> 
                dan upload bukti transfer
              </li>
              <li>Admin akan memverifikasi dalam 1x24 jam</li>
            </ol>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth.php';

$keyword = trim($_GET['q'] ?? '');
$hasil   = [];
$error   = '';

if ($keyword !== '') {
    // Cari berdasarkan kode booking ATAU no HP
    $stmt = $conn->prepare("
        SELECT b.*, s.nama AS sarana, s.jenis AS jenis_sarana, s.gambar AS gambar_sarana
        FROM booking b
        JOIN sarana s ON s.id = b.sarana_id
        WHERE b.kode_booking = ? OR b.no_hp = ?
        ORDER BY b.created_at DESC
        LIMIT 20
    ");
    $stmt->bind_param('ss', $keyword, $keyword);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $hasil[] = $row;

    if (empty($hasil)) $error = 'Booking tidak ditemukan. Periksa kembali kode booking atau nomor HP kamu.';
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container page" style="max-width:900px;">
  <div style="text-align:center;margin-bottom:32px;">
    <h1 style="color:#166534;margin-bottom:8px;">🔍 Cek Status Booking</h1>
    <p style="color:#64748b;">Masukkan kode booking atau nomor HP yang kamu gunakan saat memesan.</p>
  </div>

  <form method="GET" style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.06);">
    <div class="form-group">
      <label>Kode Booking / No. HP</label>
      <input type="text" name="q" class="form-control" 
             placeholder="Contoh: BKG-240922-A1B2C  atau  081234567890"
             value="<?= e($keyword) ?>" required autofocus>
    </div>
    <button class="btn btn-primary btn-block">Cari Booking</button>
  </form>

  <?php if ($error): ?>
    <div class="alert alert-danger" style="margin-top:20px;"><?= e($error) ?></div>
  <?php endif; ?>

  <?php if (!empty($hasil)): ?>
    <h3 style="margin-top:32px;">Hasil Pencarian (<?= count($hasil) ?> booking)</h3>

    <?php foreach ($hasil as $b): ?>
      <?php
        // Tentukan warna & label status
        $statusMap = [
          'pending'       => ['badge-warning', '⏳ Menunggu Verifikasi'],
          'terverifikasi' => ['badge-success', '✅ Terverifikasi'],
          'ditolak'       => ['badge-danger',  '❌ Ditolak'],
          'selesai'       => ['badge-info',    '🎉 Selesai'],
          'batal'         => ['badge-danger',  '🚫 Dibatalkan'],
        ];
        [$badgeClass, $badgeLabel] = $statusMap[$b['status']] ?? ['badge-warning', $b['status']];
      ?>

      <div class="card" style="margin-top:20px;padding:0;">
        <div style="display:grid;grid-template-columns:1fr;gap:0;">

          <!-- Header Card -->
          <div style="background:#166534;color:#fff;padding:16px 20px;border-radius:12px 12px 0 0;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
            <div>
              <div style="font-size:.85rem;opacity:.85;">Kode Booking</div>
              <div style="font-size:1.2rem;font-weight:700;letter-spacing:1px;"><?= e($b['kode_booking']) ?></div>
            </div>
            <span class="badge <?= $badgeClass ?>" style="font-size:.9rem;padding:6px 14px;"><?= $badgeLabel ?></span>
          </div>

          <!-- Body -->
          <div style="padding:20px;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

              <div>
                <div style="font-size:.8rem;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Sarana</div>
                <div style="font-weight:600;"><?= e($b['sarana']) ?></div>
                <span class="badge badge-info" style="margin-top:4px;"><?= e($b['jenis_sarana']) ?></span>
              </div>

              <div>
                <div style="font-size:.8rem;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Tanggal</div>
                <div style="font-weight:600;"><?= date('d F Y', strtotime($b['tanggal'])) ?></div>
              </div>

              <div>
                <div style="font-size:.8rem;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Waktu</div>
                <div style="font-weight:600;">
                  <?= substr($b['jam_mulai'],0,5) ?> - <?= substr($b['jam_selesai'],0,5) ?>
                  <span style="color:#64748b;font-weight:400;">(<?= $b['durasi_jam'] ?> jam)</span>
                </div>
              </div>

              <div>
                <div style="font-size:.8rem;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Atas Nama</div>
                <div style="font-weight:600;"><?= e($b['nama_penyewa']) ?></div>
              </div>
            </div>

            <!-- Rincian Biaya -->
            <div style="margin-top:20px;padding-top:16px;border-top:1px dashed #e2e8f0;">
              <table style="width:100%;font-size:.92rem;">
                <tr>
                  <td style="padding:4px 0;color:#64748b;">Harga per jam</td>
                  <td style="text-align:right;">Rp <?= number_format($b['harga_per_jam'],0,',','.') ?></td>
                </tr>
                <tr>
                  <td style="padding:4px 0;color:#64748b;">Subtotal (<?= $b['durasi_jam'] ?> jam)</td>
                  <td style="text-align:right;">Rp <?= number_format($b['subtotal'],0,',','.') ?></td>
                </tr>
                <?php if ($b['diskon_persen'] > 0): ?>
                <tr>
                  <td style="padding:4px 0;color:#16a34a;">Diskon (<?= (int)$b['diskon_persen'] ?>%)</td>
                  <td style="text-align:right;color:#16a34a;">
                    - Rp <?= number_format($b['subtotal'] * $b['diskon_persen'] / 100, 0, ',', '.') ?>
                  </td>
                </tr>
                <?php endif; ?>
                <tr style="border-top:1px solid #e2e8f0;">
                  <td style="padding:8px 0;font-weight:700;font-size:1rem;">Total Bayar</td>
                  <td style="text-align:right;font-weight:700;font-size:1.1rem;color:#166534;">
                    Rp <?= number_format($b['total_bayar'],0,',','.') ?>
                  </td>
                </tr>
              </table>
            </div>

            <!-- Catatan Petugas -->
            <?php if (!empty($b['catatan_petugas'])): ?>
              <div class="alert alert-info" style="margin-top:16px;margin-bottom:0;">
                <strong>📝 Catatan Petugas:</strong><br>
                <?= nl2br(e($b['catatan_petugas'])) ?>
              </div>
            <?php endif; ?>

            <!-- Kode Booking Gambar -->
            <?php if ($b['status'] === 'terverifikasi' && $b['kode_gambar']): ?>
              <div style="margin-top:20px;text-align:center;background:#f0fdf4;padding:16px;border-radius:8px;">
                <p style="margin-bottom:12px;color:#166534;font-weight:600;">
                  ✅ Booking terverifikasi — tunjukkan gambar ini saat check-in
                </p>
                <img src="<?= BASE_URL . '/' . e($b['kode_gambar']) ?>" 
                     style="max-width:300px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.1);">
                <div style="margin-top:12px;">
                  <a href="<?= BASE_URL . '/' . e($b['kode_gambar']) ?>" 
                     download="kode-<?= e($b['kode_booking']) ?>.png" 
                     class="btn btn-accent btn-sm">⬇️ Download Gambar</a>
                </div>
              </div>
            <?php endif; ?>

            <!-- Info untuk status pending -->
            <?php if ($b['status'] === 'pending'): ?>
              <div class="alert alert-info" style="margin-top:16px;margin-bottom:0;font-size:.9rem;">
                ℹ️ Booking kamu sedang menunggu verifikasi petugas. Simpan kode booking 
                <strong><?= e($b['kode_booking']) ?></strong> dan tunjukkan ke petugas saat datang.
              </div>
            <?php endif; ?>

            <?php if ($b['status'] === 'ditolak'): ?>
              <div class="alert alert-danger" style="margin-top:16px;margin-bottom:0;font-size:.9rem;">
                ❌ Booking ditolak. Silakan hubungi petugas untuk informasi lebih lanjut.
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

    <div style="text-align:center;margin-top:24px;">
      <a href="cek_booking.php" class="btn btn-primary">🔄 Cari Lagi</a>
    </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
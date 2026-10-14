<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth.php';

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') {
    // Coba fallback ke id
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
    http_response_code(404);
    require_once __DIR__ . '/../includes/header.php';
    echo '<div class="container page"><div class="alert alert-danger">Sarana tidak ditemukan.</div>';
    echo '<a href="'.BASE_URL.'/index.php" class="btn btn-primary">← Kembali</a></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Cek diskon aktif
$diskon = getDiskonByTanggal($conn, date('Y-m-d'));

// Parse kelengkapan jadi list
$kelengkapanList = [];
if (!empty($s['kelengkapan'])) {
    foreach (preg_split('/\r\n|\r|\n/', $s['kelengkapan']) as $line) {
        $line = trim($line);
        if ($line !== '') $kelengkapanList[] = $line;
    }
}
$galeri = getGaleri($s['galeri']);

// Ambil booking terbaru untuk sarana ini (5 data, untuk info jadwal)
$bookings = $conn->query("
    SELECT tanggal, jam_mulai, jam_selesai, status 
    FROM booking 
    WHERE sarana_id = {$s['id']} 
      AND status IN ('pending','terverifikasi')
      AND tanggal >= CURDATE()
    ORDER BY tanggal ASC, jam_mulai ASC
    LIMIT 5
");

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container page">
  <a href="<?= BASE_URL ?>/index.php" style="color:#64748b;font-size:.9rem;">← Kembali ke Beranda</a>

  <div style="display:grid;grid-template-columns:1.5fr 1fr;gap:32px;margin-top:20px;" class="detail-grid">
    
    <!-- KIRI: FOTO & INFO -->
    <div>
      <!-- Foto Utama -->
      <div style="border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);">
        <?php if ($s['gambar']): ?>
          <img src="<?= BASE_URL ?>/assets/img/sarana/<?= e($s['gambar']) ?>"
               style="width:100%;height:360px;object-fit:cover;display:block;">
        <?php else: ?>
          <div style="height:360px;background:#e2e8f0;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:1.1rem;">
            📷 Belum ada foto
          </div>
        <?php endif; ?>
      </div>

      <!-- Galeri -->
      <?php if ($galeri): ?>
        <h3 style="margin-top:24px;">📸 Galeri Foto</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;margin-top:12px;">
          <?php foreach ($galeri as $g): ?>
            <a href="<?= BASE_URL ?>/assets/img/sarana/<?= e($g) ?>" target="_blank">
              <img src="<?= BASE_URL ?>/assets/img/sarana/<?= e($g) ?>"
                   style="width:100%;height:110px;object-fit:cover;border-radius:8px;cursor:pointer;transition:.2s;"
                   onmouseover="this.style.transform='scale(1.05)'"
                   onmouseout="this.style.transform='scale(1)'">
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Deskripsi -->
      <?php if (!empty($s['deskripsi'])): ?>
        <h3 style="margin-top:28px;">📝 Tentang Sarana</h3>
        <p style="color:#475569;line-height:1.7;"><?= nl2br(e($s['deskripsi'])) ?></p>
      <?php endif; ?>

      <!-- Kelengkapan -->
      <?php if ($kelengkapanList): ?>
        <h3 style="margin-top:28px;">✅ Kelengkapan & Fasilitas</h3>
        <div style="background:#f0fdf4;border-radius:12px;padding:20px;margin-top:12px;">
          <ul style="list-style:none;padding:0;display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <?php foreach ($kelengkapanList as $k): ?>
              <li style="display:flex;align-items:flex-start;gap:8px;color:#166534;">
                <span style="color:#16a34a;font-weight:700;">✓</span>
                <span><?= e($k) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    </div>

    <!-- KANAN: SIDEBAR BOOKING -->
    <div>
      <div style="position:sticky;top:20px;">
        <div class="card" style="padding:24px;">
          <span class="badge badge-info"><?= e($s['jenis']) ?></span>
          <h1 style="margin:12px 0 8px 0;font-size:1.6rem;"><?= e($s['nama']) ?></h1>

          <div style="margin:16px 0;padding:16px;background:#f0fdf4;border-radius:10px;">
            <div style="font-size:.85rem;color:#64748b;">Harga Sewa</div>
            <div style="font-size:1.5rem;font-weight:700;color:#166534;">
              Rp <?= number_format($s['harga_per_jam'],0,',','.') ?>
              <span style="font-size:.9rem;color:#64748b;font-weight:400;">/ jam</span>
            </div>

            <?php if ($diskon): ?>
              <div style="margin-top:10px;padding-top:10px;border-top:1px dashed #86efac;">
                <span class="badge badge-success">🎉 Diskon <?= (int)$diskon['persen'] ?>%</span>
                <div style="font-size:.85rem;color:#166534;margin-top:4px;">
                  <?= e($diskon['nama']) ?><br>
                  Berlaku s/d <?= date('d M Y', strtotime($diskon['tanggal_selesai'])) ?>
                </div>
              </div>
            <?php endif; ?>
          </div>

          <div style="margin:12px 0;">
            <div style="font-size:.9rem;color:#64748b;margin-bottom:4px;">Status</div>
            <span class="badge badge-<?= $s['status']==='tersedia'?'success':'warning' ?>">
              <?= $s['status']==='tersedia' ? '✅ Tersedia' : '🔧 Sedang Maintenance' ?>
            </span>
          </div>

          <?php if ($s['status'] === 'tersedia'): ?>
            <a href="booking.php?sarana_id=<?= $s['id'] ?>" class="btn btn-primary btn-block" style="margin-top:16px;padding:14px;font-size:1rem;">
              📅 Booking Sekarang
            </a>
          <?php else: ?>
            <button class="btn btn-block" style="margin-top:16px;padding:14px;background:#e2e8f0;color:#94a3b8;cursor:not-allowed;" disabled>
              🔧 Tidak Tersedia
            </button>
          <?php endif; ?>
        </div>

        <!-- Jadwal Terdekat -->
        <?php if ($bookings && $bookings->num_rows > 0): ?>
          <div class="card" style="padding:20px;margin-top:16px;">
            <h4 style="margin-bottom:12px;">🗓️ Jadwal Terdekat</h4>
            <table style="width:100%;font-size:.88rem;">
              <?php while ($b = $bookings->fetch_assoc()): ?>
                <tr style="border-bottom:1px solid #f1f5f9;">
                  <td style="padding:6px 0;">
                    <?= date('d M', strtotime($b['tanggal'])) ?><br>
                    <small style="color:#64748b;">
                      <?= substr($b['jam_mulai'],0,5) ?> - <?= substr($b['jam_selesai'],0,5) ?>
                    </small>
                  </td>
                  <td style="text-align:right;padding:6px 0;">
                    <span class="badge badge-<?= $b['status']==='terverifikasi'?'success':'warning' ?>">
                      <?= $b['status']==='terverifikasi' ? 'Terisi' : 'Pending' ?>
                    </span>
                  </td>
                </tr>
              <?php endwhile; ?>
            </table>
            <small style="color:#94a3b8;display:block;margin-top:8px;">*Jadwal dapat berubah</small>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Responsive untuk layar kecil -->
<style>
@media (max-width: 768px) {
  .detail-grid { grid-template-columns: 1fr !important; }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
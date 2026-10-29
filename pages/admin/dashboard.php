<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

// ============ 1. QUERY STATISTIK ============
$totalReservasi   = $conn->query("SELECT COUNT(*) c FROM booking")->fetch_assoc()['c'];
$menungguBayar    = $conn->query("SELECT COUNT(*) c FROM booking WHERE status_pembayaran='menunggu_verifikasi'")->fetch_assoc()['c'];
$menungguVerif    = $conn->query("SELECT COUNT(*) c FROM booking WHERE status='pending'")->fetch_assoc()['c'];
$disetujui        = $conn->query("SELECT COUNT(*) c FROM booking WHERE status='terverifikasi' OR status='selesai'")->fetch_assoc()['c'];
$totalPendapatan  = $conn->query("SELECT COALESCE(SUM(total_bayar),0) s FROM booking WHERE status IN ('terverifikasi','selesai') AND status_pembayaran='berhasil'")->fetch_assoc()['s'];

// ============ 2. GRAFIK BULANAN ============
$monthlyRaw = $conn->query("SELECT MONTH(tanggal) AS bulan, COUNT(*) AS total FROM booking WHERE YEAR(tanggal) = YEAR(CURDATE()) GROUP BY MONTH(tanggal)");
$monthly = array_fill(1, 12, 0);
while ($r = $monthlyRaw->fetch_assoc()) $monthly[(int)$r['bulan']] = (int)$r['total'];
$maxMonthly = max(1, max($monthly));
$bulanLabels = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

// ============ 3. KOMPOSISI FASILITAS ============
$komposisi = [];
$kompRaw = $conn->query("SELECT s.jenis, COUNT(*) AS total FROM booking b JOIN sarana s ON s.id = b.sarana_id GROUP BY s.jenis ORDER BY total DESC");
while ($r = $kompRaw->fetch_assoc()) $komposisi[] = $r;
$totalKomp = array_sum(array_column($komposisi, 'total'));

$palette = ['#0f766e', '#ec4899', '#3b82f6', '#f59e0b'];
$donutSegments = [];
if ($totalKomp > 0) {
    foreach ($komposisi as $i => $k) {
        $pct = ($k['total'] / $totalKomp) * 100;
        $donutSegments[] = ['nama' => $k['jenis'], 'total' => $k['total'], 'pct' => $pct, 'warna' => $palette[$i % count($palette)]];
    }
}

// ============ 4. RESERVASI TERBARU ============
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
if (!in_array($limit, [10, 50, 100, 500])) $limit = 10;

$recentBookings = $conn->query("SELECT b.kode_booking, b.nama_penyewa, b.tanggal, b.total_bayar, b.status FROM booking b ORDER BY b.created_at DESC LIMIT $limit");

ob_start();
?>

<div class="welcome">
  <h1>Selamat Datang, Admin 👋</h1>
  <p>Ringkasan informasi pengelolaan dan reservasi sarana olahraga SIPASTRA.</p>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <div class="label">Total Reservasi</div>
    <div class="value"><?= $totalReservasi ?></div>
    <div class="desc">Seluruh data reservasi</div>
  </div>
  <div class="stat-card">
    <div class="label">Menunggu Pembayaran</div>
    <div class="value"><?= $menungguBayar ?></div>
    <div class="desc">Menunggu pembayaran pemesan</div>
  </div>
  <div class="stat-card">
    <div class="label">Menunggu Verifikasi</div>
    <div class="value"><?= $menungguVerif ?></div>
    <div class="desc">Menunggu pemeriksaan bukti</div>
  </div>
  <div class="stat-card">
    <div class="label">Disetujui</div>
    <div class="value"><?= $disetujui ?></div>
    <div class="desc">Reservasi terverifikasi</div>
  </div>
</div>

<!-- Total Pendapatan (Kotak Hijau) -->
<div class="income-card">
  <div>
    <div class="label">TOTAL PENDAPATAN</div>
    <div class="value">Rp <?= number_format($totalPendapatan,0,',','.') ?></div>
  </div>
  <div class="sub">
    Pendapatan dari reservasi yang<br>telah terverifikasi & lunas
  </div>
</div>

<!-- Chart Grid -->
<div class="chart-grid">
  <div class="chart-card">
    <h3>Grafik Reservasi Bulanan</h3>
    <div class="bar-chart">
      <?php foreach ($monthly as $bulan => $jumlah): 
        $height = $jumlah > 0 ? max(8, ($jumlah / $maxMonthly) * 160) : 2;
      ?>
        <div class="bar-col">
          <div class="bar-val"><?= $jumlah ?></div>
          <div class="bar-fill <?= $jumlah==0?'zero':'' ?>" style="height: <?= $height ?>px;"></div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="bar-labels">
      <?php foreach ($bulanLabels as $b): ?><span><?= $b ?></span><?php endforeach; ?>
    </div>
  </div>

  <div class="chart-card">
    <h3>Komposisi Fasilitas</h3>
    <?php if ($totalKomp === 0): ?>
      <div style="text-align:center; padding:40px; color:#94a3b8;">Belum ada data</div>
    <?php else: ?>
      <div class="donut-wrap">
        <svg class="donut-svg" viewBox="0 0 42 42">
          <circle cx="21" cy="21" r="15.915" fill="transparent" stroke="#f1f5f9" stroke-width="8"></circle>
          <?php $cumOffset = 25;
          foreach ($donutSegments as $seg):
            $dashLen = $seg['pct'];
            $gapLen  = 100 - $dashLen; ?>
            <circle cx="21" cy="21" r="15.915" fill="transparent"
                    stroke="<?= $seg['warna'] ?>" stroke-width="8"
                    stroke-dasharray="<?= $dashLen ?> <?= $gapLen ?>"
                    stroke-dashoffset="<?= $cumOffset ?>"></circle>
          <?php $cumOffset -= $dashLen; endforeach; ?>
        </svg>
        <div class="donut-legend">
          <?php foreach ($donutSegments as $seg): ?>
            <div class="legend-item">
              <span class="dot" style="background:<?= $seg['warna'] ?>"></span>
              <span class="legend-name"><?= e($seg['nama']) ?></span>
              <span class="legend-val"><?= $seg['total'] ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Tabel Reservasi -->
<div class="chart-card" style="margin-bottom:30px;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <h3 style="margin:0;">Reservasi Terbaru</h3>
    <form method="GET" style="margin:0; display:flex; align-items:center; gap:8px;">
      <label style="font-size:.85rem; color:#64748b; white-space:nowrap;">Tampilkan:</label>
      <select name="limit" class="form-control" style="width:auto; padding:6px 30px 6px 12px; font-size:.85rem; border-radius:6px; border:1px solid #cbd5e1;" onchange="this.form.submit()">
        <option value="10" <?= $limit==10?'selected':'' ?>>10 Baris</option>
        <option value="50" <?= $limit==50?'selected':'' ?>>50 Baris</option>
        <option value="100" <?= $limit==100?'selected':'' ?>>100 Baris</option>
        <option value="500" <?= $limit==500?'selected':'' ?>>500 Baris</option>
      </select>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table-custom">
      <thead>
        <tr>
          <th>Kode Reservasi</th>
          <th>Nama Pemesan</th>
          <th>Tanggal Sewa</th>
          <th>Total Biaya</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($recentBookings->num_rows === 0): ?>
          <tr><td colspan="5" style="text-align:center; color:#94a3b8; padding:30px;">Belum ada data reservasi</td></tr>
        <?php endif; ?>
        
        <?php while ($b = $recentBookings->fetch_assoc()): 
          $statusLabel = 'Disetujui';
          $badgeStyle = '';
          if ($b['status'] == 'pending') { $statusLabel = 'Pending'; $badgeStyle = 'background:#fef3c7; color:#92400e;'; }
          elseif ($b['status'] == 'ditolak') { $statusLabel = 'Ditolak'; $badgeStyle = 'background:#fee2e2; color:#991b1b;'; }
          elseif ($b['status'] == 'batal') { $statusLabel = 'Batal'; $badgeStyle = 'background:#f1f5f9; color:#64748b;'; }
        ?>
          <tr>
            <td><strong><?= e($b['kode_booking']) ?></strong></td>
            <td><?= e($b['nama_penyewa']) ?></td>
            <td><?= date('d-m-Y', strtotime($b['tanggal'])) ?></td>
            <td>Rp <?= number_format($b['total_bayar'],0,',','.') ?></td>
            <td>
              <span class="badge-status" style="<?= $badgeStyle ?>">
                <?= $statusLabel ?>
              </span>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$content = ob_get_clean();
$title = 'Dashboard';
$pageTitle = 'Dashboard';
$activeMenu = 'dashboard';
require_once __DIR__ . '/../../includes/dashboard_layout.php';
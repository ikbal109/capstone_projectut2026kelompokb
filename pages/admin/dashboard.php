<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

// ============ QUERY STATISTIK ============
$totalReservasi   = $conn->query("SELECT COUNT(*) c FROM booking")->fetch_assoc()['c'];
$pendingCount     = $conn->query("SELECT COUNT(*) c FROM booking WHERE status='pending'")->fetch_assoc()['c'];
$terverifikasi    = $conn->query("SELECT COUNT(*) c FROM booking WHERE status='terverifikasi'")->fetch_assoc()['c'];
$selesaiCount     = $conn->query("SELECT COUNT(*) c FROM booking WHERE status='selesai'")->fetch_assoc()['c'];
$totalPendapatan  = $conn->query("SELECT COALESCE(SUM(total_bayar),0) s FROM booking WHERE status IN ('terverifikasi','selesai')")->fetch_assoc()['s'];

// ============ GRAFIK BULANAN (tahun ini) ============
$monthlyRaw = $conn->query("
    SELECT MONTH(tanggal) AS bulan, COUNT(*) AS total
    FROM booking
    WHERE YEAR(tanggal) = YEAR(CURDATE())
    GROUP BY MONTH(tanggal)
");
$monthly = array_fill(1, 12, 0);
while ($r = $monthlyRaw->fetch_assoc()) $monthly[(int)$r['bulan']] = (int)$r['total'];
$maxMonthly = max(1, max($monthly));
$bulanLabels = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

// ============ KOMPOSISI FASILITAS ============
$komposisi = [];
$kompRaw = $conn->query("
    SELECT s.jenis, COUNT(*) AS total
    FROM booking b
    JOIN sarana s ON s.id = b.sarana_id
    GROUP BY s.jenis
    ORDER BY total DESC
");
while ($r = $kompRaw->fetch_assoc()) $komposisi[] = $r;
$totalKomp = array_sum(array_column($komposisi, 'total'));

// Warna untuk donut chart
$palette = ['#166534', '#22c55e', '#facc15', '#f59e0b', '#3b82f6', '#8b5cf6', '#ef4444', '#ec4899'];

// Hitung posisi donut (SVG stroke-dasharray)
$donutSegments = [];
if ($totalKomp > 0) {
    $offset = 0;
    foreach ($komposisi as $i => $k) {
        $pct = ($k['total'] / $totalKomp) * 100;
        $donutSegments[] = [
            'nama'  => $k['jenis'],
            'total' => $k['total'],
            'pct'   => $pct,
            'warna' => $palette[$i % count($palette)],
            'dash'  => ($pct * 100 / 100) . ' ' . (100 - $pct),
        ];
    }
}

// ============ BOOKING PENDING (untuk notif) ============
$pendingList = $conn->query("SELECT COUNT(*) c FROM booking WHERE status='pending'")->fetch_assoc()['c'];

// ============ BUILD CONTENT ============
ob_start();
?>

<div class="welcome">
  <h1>Selamat Datang, <?= e($_SESSION['nama']) ?> 👋</h1>
  <p>Ringkasan informasi pengelolaan dan reservasi sarana olahraga</p>
</div>

<!-- ============ 4 STAT CARDS ============ -->
<div class="stat-grid">
  <div class="stat-card green">
    <div class="stat-icon">📊</div>
    <div class="label">Total Reservasi</div>
    <div class="value"><?= number_format($totalReservasi,0,',','.') ?></div>
    <div class="desc">Seluruh data reservasi</div>
  </div>

  <div class="stat-card yellow">
    <div class="stat-icon">⏳</div>
    <div class="label">Menunggu Verifikasi</div>
    <div class="value"><?= number_format($pendingCount,0,',','.') ?></div>
    <div class="desc">Booking perlu ditindak lanjut</div>
  </div>

  <div class="stat-card blue">
    <div class="stat-icon">✅</div>
    <div class="label">Terverifikasi</div>
    <div class="value"><?= number_format($terverifikasi,0,',','.') ?></div>
    <div class="desc">Reservasi terverifikasi</div>
  </div>

  <div class="stat-card red">
    <div class="stat-icon">🎉</div>
    <div class="label">Selesai</div>
    <div class="value"><?= number_format($selesaiCount,0,',','.') ?></div>
    <div class="desc">Reservasi telah selesai</div>
  </div>
</div>

<!-- ============ TOTAL PENDAPATAN ============ -->
<div class="income-card">
  <div>
    <div class="label">Total Pendapatan</div>
    <div class="value">Rp <?= number_format($totalPendapatan,0,',','.') ?></div>
    <div class="sub">Pendapatan dari reservasi terverifikasi & selesai</div>
  </div>
  <div class="income-icon">💰</div>
</div>

<!-- ============ NOTIF PENDING ============ -->
<?php if ($pendingList > 0): ?>
<div class="notif-card">
  <div class="notif-content">
    <div class="notif-icon">⚠️</div>
    <div>
      <div class="notif-title">Reservasi Menunggu Verifikasi</div>
      <div class="notif-desc">
        Terdapat reservasi yang belum diverifikasi petugas.
        <a href="<?= BASE_URL ?>/pages/admin/kelola_booking.php?status=pending" class="notif-link">Lihat Reservasi →</a>
      </div>
    </div>
  </div>
  <div class="notif-count"><?= $pendingList ?></div>
</div>
<?php endif; ?>

<!-- ============ CHART GRID ============ -->
<div class="chart-grid">
  <!-- Bar Chart: Reservasi Bulanan -->
  <div class="chart-card">
    <h3>📊 Grafik Reservasi Bulanan (<?= date('Y') ?>)</h3>
    <div style="position:relative;padding-left:20px;">
      <span class="chart-y-title"></span>
      <div class="bar-chart">
        <?php foreach ($monthly as $bulan => $jumlah): 
          $height = $jumlah > 0 ? max(6, ($jumlah / $maxMonthly) * 160) : 3;
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
  </div>

  <!-- Donut Chart: Komposisi Fasilitas -->
  <div class="chart-card">
    <h3>🥧 Komposisi Fasilitas</h3>
    <?php if ($totalKomp === 0): ?>
      <div class="donut-empty">Belum ada data reservasi</div>
    <?php else: ?>
      <div class="donut-wrap">
        <svg class="donut-svg" viewBox="0 0 42 42">
          <circle cx="21" cy="21" r="15.915" fill="transparent" stroke="#f1f5f9" stroke-width="6"></circle>
          <?php 
          $cumOffset = 25; // start di atas
          foreach ($donutSegments as $seg):
            $dashLen = $seg['pct'];
            $gapLen  = 100 - $dashLen;
          ?>
            <circle cx="21" cy="21" r="15.915" fill="transparent"
                    stroke="<?= $seg['warna'] ?>" stroke-width="6"
                    stroke-dasharray="<?= $dashLen ?> <?= $gapLen ?>"
                    stroke-dashoffset="<?= $cumOffset ?>"></circle>
          <?php 
            $cumOffset -= $dashLen;
          endforeach; ?>
          <text x="21" y="20" text-anchor="middle" font-size="4" font-weight="700" fill="#0f172a"><?= $totalKomp ?></text>
          <text x="21" y="25" text-anchor="middle" font-size="2" fill="#64748b">Total</text>
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

<?php
$content = ob_get_clean();
$title = 'Dashboard';
$pageTitle = 'Dashboard';
$activeMenu = 'dashboard';
require_once __DIR__ . '/../../includes/dashboard_layout.php';
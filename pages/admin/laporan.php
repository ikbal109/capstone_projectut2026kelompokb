<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

// ============ FILTER TAHUN ============
$tahun = (int)($_GET['tahun'] ?? date('Y'));

// Daftar tahun yang ada booking
$listTahun = [];
$t = $conn->query("SELECT DISTINCT YEAR(tanggal) y FROM booking ORDER BY y DESC");
while ($r = $t->fetch_assoc()) $listTahun[] = (int)$r['y'];
if (!in_array((int)date('Y'), $listTahun)) $listTahun[] = (int)date('Y');
rsort($listTahun);

// ============ 1) PENDAPATAN PER BULAN ============
$bulanLabels = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

$pendapatanBulanan = array_fill(1, 12, 0);
$jumlahBulanan     = array_fill(1, 12, 0);

$stmt = $conn->prepare("
    SELECT MONTH(tanggal) AS bulan, 
           COUNT(*) AS jumlah,
           COALESCE(SUM(total_bayar),0) AS total
    FROM booking
    WHERE YEAR(tanggal) = ?
      AND status IN ('terverifikasi','selesai')
    GROUP BY MONTH(tanggal)
");
$stmt->bind_param('i', $tahun);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    $pendapatanBulanan[(int)$r['bulan']] = (float)$r['total'];
    $jumlahBulanan[(int)$r['bulan']]     = (int)$r['jumlah'];
}

$totalSetahun = array_sum($pendapatanBulanan);
$totalBookingSetahun = array_sum($jumlahBulanan);
$maxPendapatan = max(1, max($pendapatanBulanan));

// ============ 2) PENGGUNAAN FASILITAS ============
$fasilitas = [];
$fs = $conn->query("
    SELECT s.id, s.nama, s.jenis, s.harga_per_jam,
           COUNT(b.id) AS total_booking,
           COALESCE(SUM(b.durasi_jam),0) AS total_jam,
           COALESCE(SUM(CASE WHEN b.status IN ('terverifikasi','selesai') THEN b.total_bayar ELSE 0 END),0) AS total_pendapatan
    FROM sarana s
    LEFT JOIN booking b ON b.sarana_id = s.id 
        AND YEAR(b.tanggal) = $tahun
        AND b.status IN ('terverifikasi','selesai')
    GROUP BY s.id
    ORDER BY total_booking DESC, s.nama ASC
");
while ($r = $fs->fetch_assoc()) $fasilitas[] = $r;

$totalJamSemua = array_sum(array_column($fasilitas, 'total_jam'));
$maxBookingFasilitas = max(1, max(array_column($fasilitas, 'total_booking') ?: [0]));

// Warna palet
$palette = ['#166534', '#22c55e', '#facc15', '#f59e0b', '#3b82f6', '#8b5cf6', '#ef4444', '#ec4899'];

ob_start();
?>

<div class="welcome">
  <h1>Laporan</h1>
  <p>Ringkasan pendapatan dan penggunaan fasilitas</p>
</div>

<!-- FILTER TAHUN + EXPORT -->
<div class="chart-card" style="margin-bottom:20px;">
  <form method="GET" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;">
    <div class="form-group" style="margin:0;min-width:180px;">
      <label>Tahun</label>
      <select name="tahun" class="form-control" onchange="this.form.submit()">
        <?php foreach ($listTahun as $y): ?>
          <option value="<?= $y ?>" <?= $y===$tahun?'selected':'' ?>><?= $y ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="button" class="btn btn-primary" onclick="window.print()">🖨️ Cetak Laporan</button>
    <a href="?tahun=<?= $tahun ?>&export=csv" class="btn btn-accent">⬇️ Export CSV</a>
  </form>
</div>

<!-- EXPORT CSV -->
<?php
if (($_GET['export'] ?? '') === 'csv') {
    // Buffer sudah start, jadi kita bersihkan dan kirim file
    ob_end_clean();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan-' . $tahun . '.csv"');
    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF"); // BOM UTF-8

    fputcsv($out, ['LAPORAN RESERVASI BPSDMD KALSEL']);
    fputcsv($out, ['Tahun', $tahun]);
    fputcsv($out, ['Dicetak', date('d/m/Y H:i')]);
    fputcsv($out, []);

    fputcsv($out, ['PENDAPATAN BULANAN']);
    fputcsv($out, ['Bulan', 'Jumlah Booking', 'Pendapatan (Rp)']);
    foreach ($pendapatanBulanan as $b => $val) {
        fputcsv($out, [$bulanLabels[$b-1], $jumlahBulanan[$b], $val]);
    }
    fputcsv($out, ['TOTAL', $totalBookingSetahun, $totalSetahun]);
    fputcsv($out, []);

    fputcsv($out, ['PENGGUNAAN FASILITAS']);
    fputcsv($out, ['Nama Sarana', 'Jenis', 'Jumlah Booking', 'Total Jam', 'Pendapatan (Rp)']);
    foreach ($fasilitas as $f) {
        fputcsv($out, [
            $f['nama'], $f['jenis'], $f['total_booking'], 
            $f['total_jam'], $f['total_pendapatan']
        ]);
    }
    fputcsv($out, ['TOTAL', '', '', $totalJamSemua, $totalSetahun]);

    fclose($out);
    exit;
}
?>

<!-- STAT RINGKAS -->
<div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));">
  <div class="stat-card green">
    <div class="stat-icon">💰</div>
    <div class="label">Total Pendapatan <?= $tahun ?></div>
    <div class="value" style="font-size:1.5rem;">Rp <?= number_format($totalSetahun,0,',','.') ?></div>
    <div class="desc">Booking terverifikasi & selesai</div>
  </div>
  <div class="stat-card blue">
    <div class="stat-icon">📅</div>
    <div class="label">Total Booking <?= $tahun ?></div>
    <div class="value"><?= number_format($totalBookingSetahun,0,',','.') ?></div>
    <div class="desc">Semua booking lunas</div>
  </div>
  <div class="stat-card yellow">
    <div class="stat-icon">⏱️</div>
    <div class="label">Total Jam Pemakaian</div>
    <div class="value"><?= number_format($totalJamSemua,0,',','.') ?> jam</div>
    <div class="desc">Akumulasi seluruh sarana</div>
  </div>
  <div class="stat-card red">
    <div class="stat-icon">🏟️</div>
    <div class="label">Sarana Aktif</div>
    <div class="value"><?= count(array_filter($fasilitas, fn($f) => $f['total_booking'] > 0)) ?> / <?= count($fasilitas) ?></div>
    <div class="desc">Sarana yang pernah disewa</div>
  </div>
</div>

<!-- GRAFIK PENDAPATAN BULANAN -->
<div class="chart-card" style="margin-bottom:20px;">
  <h3 style="margin-top:0;">💰 Pendapatan Bulanan <?= $tahun ?></h3>
  <div style="position:relative;padding-left:20px;">
    <div class="bar-chart">
      <?php foreach ($pendapatanBulanan as $bulan => $val): 
        $height = $val > 0 ? max(6, ($val / $maxPendapatan) * 160) : 3;
        $short = number_format($val/1000, 0, ',', '.');
      ?>
        <div class="bar-col">
          <div class="bar-val" style="font-size:.7rem;"><?= $val > 0 ? $short.'k' : '0' ?></div>
          <div class="bar-fill <?= $val==0?'zero':'' ?>" 
               style="height: <?= $height ?>px;"
               title="Rp <?= number_format($val,0,',','.') ?>"></div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="bar-labels">
      <?php foreach (['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'] as $b): ?>
        <span><?= $b ?></span>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- TABEL PENDAPATAN BULANAN -->
<div class="chart-card" style="margin-bottom:20px;">
  <h3 style="margin-top:0;">📋 Rincian Pendapatan per Bulan</h3>
  <table class="table" style="box-shadow:none;border:1px solid #e2e8f0;">
    <thead>
      <tr>
        <th>Bulan</th>
        <th style="text-align:right;">Jumlah Booking</th>
        <th style="text-align:right;">Pendapatan</th>
        <th style="text-align:right;">Persentase</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($pendapatanBulanan as $bulan => $val): 
        $pct = $totalSetahun > 0 ? ($val / $totalSetahun) * 100 : 0;
      ?>
        <tr>
          <td><?= $bulanLabels[$bulan-1] ?></td>
          <td style="text-align:right;"><?= $jumlahBulanan[$bulan] ?></td>
          <td style="text-align:right;"><strong style="color:#166534;">
            Rp <?= number_format($val,0,',','.') ?>
          </strong></td>
          <td style="text-align:right;color:#64748b;"><?= number_format($pct,1,',','.') ?>%</td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr style="background:#f0fdf4;font-weight:700;">
        <td>TOTAL <?= $tahun ?></td>
        <td style="text-align:right;"><?= $totalBookingSetahun ?></td>
        <td style="text-align:right;color:#166534;font-size:1.05rem;">
          Rp <?= number_format($totalSetahun,0,',','.') ?>
        </td>
        <td style="text-align:right;">100%</td>
      </tr>
    </tfoot>
  </table>
</div>

<!-- PENGGUNAAN FASILITAS -->
<div class="chart-card">
  <h3 style="margin-top:0;">🏢 Penggunaan Fasilitas <?= $tahun ?></h3>

  <?php if (empty($fasilitas)): ?>
    <div class="donut-empty">Belum ada data sarana</div>
  <?php else: ?>

    <!-- Bar horizontal -->
    <div style="margin-bottom:24px;">
      <?php foreach ($fasilitas as $i => $f): 
        $pct = ($f['total_booking'] / $maxBookingFasilitas) * 100;
        $warna = $palette[$i % count($palette)];
      ?>
        <div style="margin-bottom:14px;">
          <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:.88rem;">
            <span><strong><?= e($f['nama']) ?></strong> 
              <span class="badge badge-info" style="font-size:.7rem;"><?= e($f['jenis']) ?></span>
            </span>
            <span style="color:#64748b;">
              <?= $f['total_booking'] ?> booking · <?= $f['total_jam'] ?> jam
            </span>
          </div>
          <div style="height:10px;background:#f1f5f9;border-radius:6px;overflow:hidden;">
            <div style="height:100%;width:<?= $pct ?>%;background:<?= $warna ?>;border-radius:6px;transition:.3s;"></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Tabel detail -->
    <div style="overflow-x:auto;">
      <table class="table" style="box-shadow:none;border:1px solid #e2e8f0;font-size:.88rem;">
        <thead>
          <tr>
            <th>Sarana</th>
            <th>Jenis</th>
            <th style="text-align:right;">Booking</th>
            <th style="text-align:right;">Total Jam</th>
            <th style="text-align:right;">Pendapatan</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($fasilitas as $f): ?>
            <tr>
              <td><strong><?= e($f['nama']) ?></strong></td>
              <td><span class="badge badge-info" style="font-size:.7rem;"><?= e($f['jenis']) ?></span></td>
              <td style="text-align:right;"><?= $f['total_booking'] ?>x</td>
              <td style="text-align:right;"><?= $f['total_jam'] ?> jam</td>
              <td style="text-align:right;"><strong style="color:#166534;">
                Rp <?= number_format($f['total_pendapatan'],0,',','.') ?>
              </strong></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr style="background:#f0fdf4;font-weight:700;">
            <td colspan="2">TOTAL</td>
            <td style="text-align:right;"><?= array_sum(array_column($fasilitas, 'total_booking')) ?>x</td>
            <td style="text-align:right;"><?= $totalJamSemua ?> jam</td>
            <td style="text-align:right;color:#166534;font-size:1.05rem;">
              Rp <?= number_format($totalSetahun,0,',','.') ?>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>

  <?php endif; ?>
</div>

<style>
@media print {
  .sidebar, .topbar, .btn, button { display: none !important; }
  .main-area { margin-left: 0 !important; }
  .content { padding: 0 !important; }
  .chart-card { box-shadow: none !important; border: 1px solid #ccc !important; }
}
</style>

<?php
$content = ob_get_clean();
$title = 'Laporan';
$pageTitle = 'Laporan';
$activeMenu = 'laporan';
require_once __DIR__ . '/../../includes/dashboard_layout.php';
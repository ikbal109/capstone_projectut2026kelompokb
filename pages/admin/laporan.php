<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

// ============ FILTER ============
$jenis = $_GET['jenis'] ?? 'tahunan';
$tahun = (int)($_GET['tahun'] ?? date('Y'));
$bulan = (int)($_GET['bulan'] ?? date('m'));

// Daftar tahun untuk dropdown
$listTahun = [];
$t = $conn->query("SELECT DISTINCT YEAR(tanggal) y FROM booking ORDER BY y DESC");
while ($r = $t->fetch_assoc()) $listTahun[] = (int)$r['y'];
if (!in_array((int)date('Y'), $listTahun)) $listTahun[] = (int)date('Y');
rsort($listTahun);

$bulanLabels = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

// ============ QUERY DASAR (Menggunakan Alias b. agar tidak ambigu) ============
$where = "YEAR(b.tanggal) = $tahun AND b.status IN ('terverifikasi','selesai')";
if ($jenis === 'bulanan') {
    $where .= " AND MONTH(b.tanggal) = $bulan";
}

// 1. STATISTIK RINGKAS
$q_stat = $conn->query("
    SELECT COUNT(*) as total_booking, 
           COALESCE(SUM(b.total_bayar),0) as total_pendapatan,
           COALESCE(SUM(b.durasi_jam),0) as total_jam
    FROM booking b 
    WHERE $where
");
$stat = $q_stat ? $q_stat->fetch_assoc() : ['total_booking'=>0, 'total_pendapatan'=>0, 'total_jam'=>0];

// 2. DATA GRAFIK & TABEL
$chartData = [];
$chartLabels = [];

if ($jenis === 'tahunan') {
    // Jika Tahunan, ambil data per bulan
    for ($i = 1; $i <= 12; $i++) {
        $chartLabels[] = substr($bulanLabels[$i-1], 0, 3);
        $chartData[$i] = ['booking' => 0, 'pendapatan' => 0];
    }
    $q_chart = $conn->query("
        SELECT MONTH(b.tanggal) as bln, COUNT(*) as booking, COALESCE(SUM(b.total_bayar),0) as pendapatan 
        FROM booking b WHERE $where GROUP BY MONTH(b.tanggal)
    ");
    if ($q_chart) {
        while ($r = $q_chart->fetch_assoc()) {
            $chartData[(int)$r['bln']]['booking'] = (int)$r['booking'];
            $chartData[(int)$r['bln']]['pendapatan'] = (float)$r['pendapatan'];
        }
    }
} else {
    // Jika Bulanan, ambil data per hari
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
    for ($i = 1; $i <= $daysInMonth; $i++) {
        $chartLabels[] = $i; // Label tanggal 1, 2, 3...
        $chartData[$i] = ['booking' => 0, 'pendapatan' => 0];
    }
    $q_chart = $conn->query("
        SELECT DAY(b.tanggal) as tgl, COUNT(*) as booking, COALESCE(SUM(b.total_bayar),0) as pendapatan 
        FROM booking b WHERE $where GROUP BY DAY(b.tanggal)
    ");
    if ($q_chart) {
        while ($r = $q_chart->fetch_assoc()) {
            $chartData[(int)$r['tgl']]['booking'] = (int)$r['booking'];
            $chartData[(int)$r['tgl']]['pendapatan'] = (float)$r['pendapatan'];
        }
    }
}

$maxPendapatan = max(1, max(array_column($chartData, 'pendapatan') ?: [0]));

// 3. DATA FASILITAS
$fasilitas = [];
$fs = $conn->query("
    SELECT s.id, s.nama, s.jenis, 
           COUNT(b.id) AS total_booking,
           COALESCE(SUM(b.durasi_jam),0) AS total_jam,
           COALESCE(SUM(b.total_bayar),0) AS total_pendapatan
    FROM sarana s
    LEFT JOIN booking b ON b.sarana_id = s.id AND $where
    GROUP BY s.id
    ORDER BY total_booking DESC, s.nama ASC
");
if ($fs) {
    while ($r = $fs->fetch_assoc()) $fasilitas[] = $r;
}

$maxBookingFasilitas = max(1, max(array_column($fasilitas, 'total_booking') ?: [0]));
$palette = ['#166534', '#22c55e', '#facc15', '#f59e0b', '#3b82f6', '#8b5cf6', '#ef4444', '#ec4899'];

// ============ EXPORT CSV ============
if (($_GET['export'] ?? '') === 'csv') {
    ob_end_clean();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan-' . $jenis . '-' . $tahun . ($jenis==='bulanan'?'-'.$bulan:'') . '.csv"');
    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF"); // BOM UTF-8

    fputcsv($out, ['LAPORAN RESERVASI SIPASTRA']);
    fputcsv($out, ['Jenis Laporan', ucfirst($jenis)]);
    
    // Perbaikan Parse Error tanda minus
    $labelBulan = $bulanLabels[$bulan-1];
    $periode = $jenis === 'bulanan' ? "$labelBulan $tahun" : "Tahun $tahun";
    fputcsv($out, ['Periode', $periode]);
    
    fputcsv($out, ['Dicetak', date('d/m/Y H:i')]);
    fputcsv($out, []);

    fputcsv($out, ['RINGKASAN']);
    fputcsv($out, ['Total Booking', $stat['total_booking']]);
    fputcsv($out, ['Total Jam Pemakaian', $stat['total_jam']]);
    fputcsv($out, ['Total Pendapatan (Rp)', $stat['total_pendapatan']]);
    fputcsv($out, []);

    fputcsv($out, ['RINCIAN PENDAPATAN']);
    fputcsv($out, [$jenis==='bulanan' ? 'Tanggal' : 'Bulan', 'Jumlah Booking', 'Pendapatan (Rp)']);
    foreach ($chartData as $key => $val) {
        if ($jenis === 'bulanan' && $val['booking'] == 0 && $val['pendapatan'] == 0) continue;
        $label = $jenis==='bulanan' ? $key : $bulanLabels[$key-1];
        fputcsv($out, [$label, $val['booking'], $val['pendapatan']]);
    }
    fputcsv($out, []);

    fputcsv($out, ['PENGGUNAAN FASILITAS']);
    fputcsv($out, ['Nama Sarana', 'Jenis', 'Jumlah Booking', 'Total Jam', 'Pendapatan (Rp)']);
    foreach ($fasilitas as $f) {
        fputcsv($out, [$f['nama'], $f['jenis'], $f['total_booking'], $f['total_jam'], $f['total_pendapatan']]);
    }
    fclose($out);
    exit;
}

ob_start();
?>

<div class="welcome">
  <h1>Laporan</h1>
  <p>Ringkasan pendapatan dan penggunaan fasilitas</p>
</div>

<!-- FILTER & EXPORT -->
<div class="chart-card" style="margin-bottom:20px;">
  <form method="GET" style="display:flex; gap:12px; align-items:end; flex-wrap:wrap;">
    
    <div class="form-group" style="margin:0; min-width:150px;">
      <label>Jenis Laporan</label>
      <select name="jenis" class="form-control" onchange="toggleBulan()">
        <option value="tahunan" <?= $jenis==='tahunan'?'selected':'' ?>>Tahunan</option>
        <option value="bulanan" <?= $jenis==='bulanan'?'selected':'' ?>>Bulanan</option>
      </select>
    </div>

    <div class="form-group" style="margin:0; min-width:120px;">
      <label>Tahun</label>
      <select name="tahun" class="form-control" onchange="this.form.submit()">
        <?php foreach ($listTahun as $y): ?>
          <option value="<?= $y ?>" <?= $y==$tahun?'selected':'' ?>><?= $y ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Dropdown Bulan (Hanya muncul jika pilih Bulanan) -->
    <div class="form-group" id="filter-bulan" style="margin:0; min-width:150px; display: <?= $jenis==='bulanan'?'block':'none' ?>;">
      <label>Bulan</label>
      <select name="bulan" class="form-control" onchange="this.form.submit()">
        <?php foreach ($bulanLabels as $i => $bln): ?>
          <option value="<?= $i+1 ?>" <?= ($i+1)==$bulan?'selected':'' ?>><?= $bln ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <button type="submit" class="btn btn-primary" style="height:42px;">🔍 Tampilkan</button>
    <button type="button" class="btn btn-accent" onclick="window.print()" style="height:42px;">🖨️ Cetak</button>
    <a href="?jenis=<?= $jenis ?>&tahun=<?= $tahun ?>&bulan=<?= $bulan ?>&export=csv" class="btn btn-accent" style="height:42px; display:flex; align-items:center;">⬇️ Export CSV</a>
  </form>
</div>

<!-- STAT RINGKAS -->
<div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));">
  <div class="stat-card green">
    <div class="stat-icon">💰</div>
    <div class="label">Total Pendapatan</div>
    <div class="value" style="font-size:1.5rem;">Rp <?= number_format($stat['total_pendapatan'],0,',','.') ?></div>
    <div class="desc">Booking terverifikasi & selesai</div>
  </div>
  <div class="stat-card blue">
    <div class="stat-icon">📅</div>
    <div class="label">Total Booking</div>
    <div class="value"><?= number_format($stat['total_booking'],0,',','.') ?></div>
    <div class="desc">Semua booking lunas</div>
  </div>
  <div class="stat-card yellow">
    <div class="stat-icon">⏱️</div>
    <div class="label">Total Jam Pemakaian</div>
    <div class="value"><?= number_format($stat['total_jam'],0,',','.') ?> jam</div>
    <div class="desc">Akumulasi seluruh sarana</div>
  </div>
  <div class="stat-card red">
    <div class="stat-icon">🏟️</div>
    <div class="label">Sarana Aktif</div>
    <div class="value"><?= count(array_filter($fasilitas, fn($f) => $f['total_booking'] > 0)) ?> / <?= count($fasilitas) ?></div>
    <div class="desc">Sarana yang pernah disewa</div>
  </div>
</div>

<!-- GRAFIK PENDAPATAN -->
<div class="chart-card" style="margin-bottom:20px;">
  <h3 style="margin-top:0;">💰 Pendapatan <?= $jenis==='bulanan' ? 'Harian (Bulan '.$bulanLabels[$bulan-1].')' : 'Bulanan' ?> <?= $tahun ?></h3>
  <div style="position:relative;padding-left:20px; overflow-x:auto;">
    <div class="bar-chart" style="min-width: <?= $jenis==='bulanan' ? '800px' : 'auto' ?>;">
      <?php foreach ($chartData as $key => $val): 
        $height = $val['pendapatan'] > 0 ? max(6, ($val['pendapatan'] / $maxPendapatan) * 160) : 3;
        $short = number_format($val['pendapatan']/1000, 0, ',', '.');
      ?>
        <div class="bar-col">
          <div class="bar-val" style="font-size:.7rem;"><?= $val['pendapatan'] > 0 ? $short.'k' : '0' ?></div>
          <div class="bar-fill <?= $val['pendapatan']==0?'zero':'' ?>" style="height: <?= $height ?>px;" title="Rp <?= number_format($val['pendapatan'],0,',','.') ?>"></div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="bar-labels" style="min-width: <?= $jenis==='bulanan' ? '800px' : 'auto' ?>;">
      <?php foreach ($chartLabels as $b): ?><span><?= $b ?></span><?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Page Break Sebelum Tabel Rincian -->
<div class="page-break"></div>

<!-- TABEL RINCIAN PENDAPATAN -->
<div class="chart-card" style="margin-bottom:20px;">
  <h3 style="margin-top:0;">📋 Rincian Pendapatan per <?= $jenis==='bulanan' ? 'Tanggal' : 'Bulan' ?></h3>
  <table class="table" style="box-shadow:none;border:1px solid #e2e8f0;">
    <thead>
      <tr>
        <th><?= $jenis==='bulanan' ? 'Tanggal' : 'Bulan' ?></th>
        <th style="text-align:right;">Jumlah Booking</th>
        <th style="text-align:right;">Pendapatan</th>
        <th style="text-align:right;">Persentase</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($chartData as $key => $val): 
        $pct = $stat['total_pendapatan'] > 0 ? ($val['pendapatan'] / $stat['total_pendapatan']) * 100 : 0;
        if ($jenis === 'bulanan' && $val['booking'] == 0 && $val['pendapatan'] == 0) continue; // Skip hari kosong jika bulanan
      ?>
        <tr>
          <td><?= $jenis==='bulanan' ? 'Tanggal '.$key : $bulanLabels[$key-1] ?></td>
          <td style="text-align:right;"><?= $val['booking'] ?></td>
          <td style="text-align:right;"><strong style="color:#166534;">Rp <?= number_format($val['pendapatan'],0,',','.') ?></strong></td>
          <td style="text-align:right;color:#64748b;"><?= number_format($pct,1,',','.') ?>%</td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr style="background:#f0fdf4;font-weight:700;">
        <td>TOTAL</td>
        <td style="text-align:right;"><?= $stat['total_booking'] ?></td>
        <td style="text-align:right;color:#166534;font-size:1.05rem;">Rp <?= number_format($stat['total_pendapatan'],0,',','.') ?></td>
        <td style="text-align:right;">100%</td>
      </tr>
    </tfoot>
  </table>
</div>

<!-- Page Break Sebelum Penggunaan Fasilitas -->
<div class="page-break"></div>

<!-- PENGGUNAAN FASILITAS -->
<div class="chart-card">
  <h3 style="margin-top:0;">🏢 Penggunaan Fasilitas <?= $jenis==='bulanan' ? $bulanLabels[$bulan-1].' '.$tahun : $tahun ?></h3>

  <?php if (empty($fasilitas)): ?>
    <div class="donut-empty">Belum ada data sarana</div>
  <?php else: ?>
    <div style="margin-bottom:24px;">
      <?php foreach ($fasilitas as $i => $f): 
        $pct = ($f['total_booking'] / $maxBookingFasilitas) * 100;
        $warna = $palette[$i % count($palette)];
      ?>
        <div style="margin-bottom:14px;">
          <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:.88rem;">
            <span><strong><?= e($f['nama']) ?></strong> <span class="badge badge-info" style="font-size:.7rem;"><?= e($f['jenis']) ?></span></span>
            <span style="color:#64748b;"><?= $f['total_booking'] ?> booking · <?= $f['total_jam'] ?> jam</span>
          </div>
          <div style="height:10px;background:#f1f5f9;border-radius:6px;overflow:hidden;">
            <div style="height:100%;width:<?= $pct ?>%;background:<?= $warna ?>;border-radius:6px;"></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

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
              <td style="text-align:right;"><strong style="color:#166534;">Rp <?= number_format($f['total_pendapatan'],0,',','.') ?></strong></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<script>
  function toggleBulan() {
    const jenis = document.querySelector('select[name="jenis"]').value;
    const bulanBox = document.getElementById('filter-bulan');
    if (jenis === 'bulanan') {
      bulanBox.style.display = 'block';
    } else {
      bulanBox.style.display = 'none';
    }
    // Auto submit form saat ganti jenis laporan
    document.querySelector('form').submit();
  }
</script>

<style>
@media print {
  /* 1. Sembunyikan elemen UI yang tidak perlu dicetak */
  .sidebar, .topbar, .btn, button, form, footer, nav { 
    display: none !important; 
  }

  /* 2. RESET LAYOUT (Penting: Menghilangkan ruang kosong raksasa) */
  html, body, .dash-body, .main-area, .content, .page {
    display: block !important;
    background: #ffffff !important;
    margin: 0 !important;
    padding: 0 !important;
    height: auto !important;
    min-height: 0 !important;
  }

  /* 3. Styling Card saat di-print */
  .chart-card, .stat-card {
    background: #ffffff !important;
    box-shadow: none !important;
    border: 1px solid #cbd5e1 !important;
    margin-bottom: 20px !important;
    padding: 15px !important;
    page-break-inside: auto !important; /* Biarkan tabel terpotong jika panjang */
  }

  /* 4. Aturan Page Break (Pindah Halaman) */
  .page-break {
    page-break-before: always !important;
    height: 1px;
  }

  /* 5. Mencegah baris tabel terpotong di tengah */
  tr, .stat-card {
    page-break-inside: avoid !important;
  }

  /* 6. Warna teks menjadi hitam agar kontras */
  h1, h2, h3, h4, p, span, td, th, strong {
    color: #000000 !important;
  }

  /* 7. Warna Grafik */
  .bar-fill {
    background-color: #333333 !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
  
  .badge, .stat-icon {
    background: transparent !important;
    border: 1px solid #999 !important;
  }
}
</style>

<?php
$content = ob_get_clean();
$title = 'Laporan';
$pageTitle = 'Laporan';
$activeMenu = 'laporan';
require_once __DIR__ . '/../../includes/dashboard_layout.php';
?>
<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

$user_id = $_SESSION['user_id'];

// ============ PROSES AKSI ============
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    if ($id > 0) {
        // Verifikasi booking
        if ($action === 'verifikasi') {
            $catatan = trim($_POST['catatan'] ?? '');
            $stmt = $conn->prepare("UPDATE booking SET status='terverifikasi', catatan_petugas=?, verified_by=?, verified_at=NOW() WHERE id=?");
            $stmt->bind_param('sii', $catatan, $user_id, $id);
            $stmt->execute();
            flash('msg', 'Booking berhasil diverifikasi.');
        }

        // Tolak booking
        if ($action === 'tolak') {
            $catatan = trim($_POST['catatan'] ?? '');
            $stmt = $conn->prepare("UPDATE booking SET status='ditolak', catatan_petugas=?, verified_by=?, verified_at=NOW() WHERE id=?");
            $stmt->bind_param('sii', $catatan, $user_id, $id);
            $stmt->execute();
            flash('msg', 'Booking ditolak.');
        }

        // Tandai selesai
        if ($action === 'selesai') {
            $stmt = $conn->prepare("UPDATE booking SET status='selesai' WHERE id=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            flash('msg', 'Booking ditandai selesai.');
        }

        // Verifikasi pembayaran
        if ($action === 'verifikasi_bayar') {
            $catatan = trim($_POST['catatan_pembayaran'] ?? '');
            $stmt = $conn->prepare("UPDATE booking SET status_pembayaran='berhasil', catatan_pembayaran=?, verified_payment_by=?, verified_payment_at=NOW() WHERE id=?");
            $stmt->bind_param('sii', $catatan, $user_id, $id);
            $stmt->execute();
            flash('msg', 'Pembayaran berhasil diverifikasi.');
        }

        // Tolak pembayaran
        if ($action === 'tolak_bayar') {
            $catatan = trim($_POST['catatan_pembayaran'] ?? '');
            $stmt = $conn->prepare("UPDATE booking SET status_pembayaran='gagal', catatan_pembayaran=?, verified_payment_by=?, verified_payment_at=NOW() WHERE id=?");
            $stmt->bind_param('sii', $catatan, $user_id, $id);
            $stmt->execute();
            flash('msg', 'Pembayaran ditolak.');
        }

        // Tandai tunai lunas
        if ($action === 'tandai_lunas') {
            $stmt = $conn->prepare("UPDATE booking SET status_pembayaran='berhasil', verified_payment_by=?, verified_payment_at=NOW() WHERE id=?");
            $stmt->bind_param('ii', $user_id, $id);
            $stmt->execute();
            flash('msg', 'Pembayaran tunai ditandai lunas.');
        }

        // Hapus
        if ($action === 'hapus') {
            $row = $conn->query("SELECT kode_gambar, bukti_transfer FROM booking WHERE id=$id")->fetch_assoc();
            if ($row) {
                if (!empty($row['kode_gambar'])) {
                    $p = __DIR__ . '/../../' . $row['kode_gambar'];
                    if (file_exists($p)) @unlink($p);
                }
                if (!empty($row['bukti_transfer'])) {
                    $p = __DIR__ . '/../../' . $row['bukti_transfer'];
                    if (file_exists($p)) @unlink($p);
                }
            }
            $conn->query("DELETE FROM booking WHERE id=$id");
            flash('msg', 'Booking berhasil dihapus.');
        }
    }
    redirect(BASE_URL . '/pages/admin/kelola_booking.php' . (!empty($_POST['return_qs']) ? '?' . $_POST['return_qs'] : ''));
}

// ============ FILTER ============
$status       = $_GET['status']       ?? '';
$status_bayar = $_GET['status_bayar'] ?? '';
$sarana       = (int)($_GET['sarana'] ?? 0);
$tanggal      = $_GET['tanggal']      ?? '';
$q            = trim($_GET['q']       ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 15;
$offset       = ($page - 1) * $perPage;

$where  = [];
$params = [];
$types  = '';

if ($status !== '' && in_array($status, ['pending','terverifikasi','ditolak','selesai','batal'])) {
    $where[] = "b.status = ?"; $params[] = $status; $types .= 's';
}
if ($status_bayar !== '' && in_array($status_bayar, ['belum_bayar','menunggu_verifikasi','berhasil','gagal'])) {
    $where[] = "b.status_pembayaran = ?"; $params[] = $status_bayar; $types .= 's';
}
if ($sarana > 0) {
    $where[] = "b.sarana_id = ?"; $params[] = $sarana; $types .= 'i';
}
if ($tanggal !== '') {
    $where[] = "b.tanggal = ?"; $params[] = $tanggal; $types .= 's';
}
if ($q !== '') {
    $where[] = "(b.kode_booking LIKE ? OR b.nama_penyewa LIKE ? OR b.no_hp LIKE ?)";
    $like = "%$q%"; $params[] = $like; $params[] = $like; $params[] = $like; $types .= 'sss';
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count
$stmt = $conn->prepare("SELECT COUNT(*) c FROM booking b $whereSQL");
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$totalData = (int)$stmt->get_result()->fetch_assoc()['c'];
$totalPages = max(1, (int)ceil($totalData / $perPage));

// Data
$stmt = $conn->prepare("SELECT b.*, s.nama AS sarana_nama, s.jenis AS sarana_jenis 
        FROM booking b JOIN sarana s ON s.id = b.sarana_id 
        $whereSQL ORDER BY b.created_at DESC LIMIT $perPage OFFSET $offset");
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$list = $stmt->get_result();

// Statistik ringkas
$stat = [
    'total'         => (int)$conn->query("SELECT COUNT(*) c FROM booking")->fetch_assoc()['c'],
    'pending'       => (int)$conn->query("SELECT COUNT(*) c FROM booking WHERE status='pending'")->fetch_assoc()['c'],
    'terverifikasi' => (int)$conn->query("SELECT COUNT(*) c FROM booking WHERE status='terverifikasi'")->fetch_assoc()['c'],
    'selesai'       => (int)$conn->query("SELECT COUNT(*) c FROM booking WHERE status='selesai'")->fetch_assoc()['c'],
    'bayar_pending' => (int)$conn->query("SELECT COUNT(*) c FROM booking WHERE status_pembayaran='menunggu_verifikasi'")->fetch_assoc()['c'],
];

$listSarana = $conn->query("SELECT id, nama FROM sarana ORDER BY nama");

$returnQs = http_build_query(array_filter([
    'status' => $status, 'sarana' => $sarana ?: null,
    'tanggal' => $tanggal, 'q' => $q ?: null,
    'status_bayar' => $status_bayar, 'page' => $page > 1 ? $page : null,
]));

ob_start();
?>

<div class="welcome">
  <h1>Kelola Reservasi</h1>
  <p>Lihat, filter, dan verifikasi seluruh pemesanan sarana olahraga</p>
</div>

<?php if ($m = flash('msg')): ?>
  <div class="alert alert-success"><?= e($m) ?></div>
<?php endif; ?>

<!-- STAT -->
<div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
  <div class="stat-card blue">
    <div class="stat-icon">📊</div>
    <div class="label">Total</div>
    <div class="value"><?= $stat['total'] ?></div>
    <div class="desc">Semua booking</div>
  </div>
  <div class="stat-card yellow">
    <div class="stat-icon">⏳</div>
    <div class="label">Pending Booking</div>
    <div class="value"><?= $stat['pending'] ?></div>
    <div class="desc">Perlu diverifikasi</div>
  </div>
  <div class="stat-card green">
    <div class="stat-icon">✅</div>
    <div class="label">Terverifikasi</div>
    <div class="value"><?= $stat['terverifikasi'] ?></div>
    <div class="desc">Sudah disetujui</div>
  </div>
  <div class="stat-card red">
    <div class="stat-icon">💳</div>
    <div class="label">Bayar Pending</div>
    <div class="value"><?= $stat['bayar_pending'] ?></div>
    <div class="desc">Bukti transfer baru</div>
  </div>
</div>

<!-- FILTER -->
<div class="chart-card" style="margin-bottom:20px;">
  <form method="GET" style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr 1fr auto;gap:12px;align-items:end;">
    <div class="form-group" style="margin:0;">
      <label>Cari</label>
      <input type="text" name="q" class="form-control" value="<?= e($q) ?>" 
             placeholder="Kode / Nama / No HP">
    </div>
    <div class="form-group" style="margin:0;">
      <label>Status</label>
      <select name="status" class="form-control">
        <option value="">Semua</option>
        <option value="pending"       <?= $status==='pending'?'selected':'' ?>>Pending</option>
        <option value="terverifikasi" <?= $status==='terverifikasi'?'selected':'' ?>>Terverifikasi</option>
        <option value="ditolak"       <?= $status==='ditolak'?'selected':'' ?>>Ditolak</option>
        <option value="selesai"       <?= $status==='selesai'?'selected':'' ?>>Selesai</option>
      </select>
    </div>
    <div class="form-group" style="margin:0;">
      <label>Pembayaran</label>
      <select name="status_bayar" class="form-control">
        <option value="">Semua</option>
        <option value="belum_bayar"         <?= $status_bayar==='belum_bayar'?'selected':'' ?>>Belum Bayar</option>
        <option value="menunggu_verifikasi" <?= $status_bayar==='menunggu_verifikasi'?'selected':'' ?>>Menunggu Verifikasi</option>
        <option value="berhasil"            <?= $status_bayar==='berhasil'?'selected':'' ?>>Berhasil</option>
        <option value="gagal"               <?= $status_bayar==='gagal'?'selected':'' ?>>Gagal</option>
      </select>
    </div>
    <div class="form-group" style="margin:0;">
      <label>Sarana</label>
      <select name="sarana" class="form-control">
        <option value="">Semua</option>
        <?php while ($s = $listSarana->fetch_assoc()): ?>
          <option value="<?= $s['id'] ?>" <?= $sarana==$s['id']?'selected':'' ?>>
            <?= e($s['nama']) ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="form-group" style="margin:0;">
      <label>Tanggal</label>
      <input type="date" name="tanggal" class="form-control" value="<?= e($tanggal) ?>">
    </div>
    <div style="display:flex;gap:8px;">
      <button class="btn btn-primary">🔍 Filter</button>
      <?php if ($status || $sarana || $tanggal || $q || $status_bayar): ?>
        <a href="kelola_booking.php" class="btn btn-danger">Reset</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- TABEL -->
<div class="chart-card">
  <h3 style="margin-top:0;">
    📋 Daftar Reservasi 
    <span style="font-weight:400;color:#64748b;font-size:.9rem;">(<?= $totalData ?> data)</span>
  </h3>

  <?php if ($totalData === 0): ?>
    <div class="donut-empty">Tidak ada data booking yang cocok</div>
  <?php else: ?>
    <div style="overflow-x:auto;">
      <table class="table" style="box-shadow:none;border:1px solid #e2e8f0;font-size:.88rem;">
        <thead>
          <tr>
            <th>Kode</th>
            <th>Penyewa</th>
            <th>Sarana</th>
            <th>Jadwal</th>
            <th>Total</th>
            <th>Status</th>
            <th>Pembayaran</th>
            <th style="text-align:right;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($b = $list->fetch_assoc()): ?>
            <?php
              $statusMap = [
                'pending'       => ['warning', '⏳ Pending'],
                'terverifikasi' => ['success', '✅ Terverifikasi'],
                'ditolak'       => ['danger',  '❌ Ditolak'],
                'selesai'       => ['info',    '🎉 Selesai'],
                'batal'         => ['danger',  '🚫 Batal'],
              ];
              [$badgeClass, $badgeLabel] = $statusMap[$b['status']] ?? ['warning', $b['status']];
              [$pbg, $pcol, $plbl] = labelPembayaran($b['status_pembayaran'] ?? 'belum_bayar');
            ?>
            <tr>
              <td><code style="font-size:.82rem;color:#166534;font-weight:700;"><?= e($b['kode_booking']) ?></code></td>
              <td>
                <strong><?= e($b['nama_penyewa']) ?></strong><br>
                <small style="color:#64748b;"><?= e($b['no_hp']) ?></small>
              </td>
              <td>
                <?= e($b['sarana_nama']) ?><br>
                <span class="badge badge-info" style="font-size:.7rem;"><?= e($b['sarana_jenis']) ?></span>
              </td>
              <td>
                <strong><?= date('d M Y', strtotime($b['tanggal'])) ?></strong><br>
                <small style="color:#64748b;">
                  <?= substr($b['jam_mulai'],0,5) ?> - <?= substr($b['jam_selesai'],0,5) ?>
                </small>
              </td>
              <td><strong style="color:#166534;">Rp <?= number_format($b['total_bayar'],0,',','.') ?></strong></td>
              <td><span class="badge badge-<?= $badgeClass ?>"><?= $badgeLabel ?></span></td>
              <td>
                <span style="display:inline-block;padding:3px 10px;border-radius:20px;font-size:.78rem;font-weight:600;background:<?= $pbg ?>;color:<?= $pcol ?>;">
                  <?= $plbl ?>
                </span>
                <?php if (!empty($b['metode_pembayaran'])): ?>
                  <br><small style="color:#64748b;font-size:.72rem;">
                    <?= $b['metode_pembayaran'] === 'transfer' ? '🏦 Transfer' : '💵 Tunai' ?>
                  </small>
                <?php endif; ?>
              </td>
              <td style="text-align:right;white-space:nowrap;">
                <button type="button" class="btn btn-sm btn-primary"
                        onclick="bukaDetail(<?= $b['id'] ?>)">👁️ Detail</button>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
      <div style="display:flex;justify-content:center;gap:6px;margin-top:20px;flex-wrap:wrap;">
        <?php $qsBase = $_GET;
        for ($i = 1; $i <= $totalPages; $i++):
          $qsBase['page'] = $i;
          $link = '?' . http_build_query($qsBase);
          $active = $i == $page; ?>
          <a href="<?= $link ?>" 
             style="padding:8px 14px;border-radius:6px;text-decoration:none;font-weight:600;font-size:.88rem;
                    background:<?= $active?'#166534':'#fff' ?>;color:<?= $active?'#fff':'#475569' ?>;
                    border:1px solid <?= $active?'#166534':'#e2e8f0' ?>;">
            <?= $i ?>
          </a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<!-- MODAL -->
<div id="modalDetail" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;align-items:center;justify-content:center;padding:20px;">
  <div style="background:#fff;border-radius:16px;max-width:720px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.3);">
    <div id="modalContent" style="padding:28px;"></div>
  </div>
</div>

<script>
async function bukaDetail(id) {
  const modal = document.getElementById('modalDetail');
  const content = document.getElementById('modalContent');
  content.innerHTML = '<p style="text-align:center;padding:40px;color:#64748b;">Memuat data...</p>';
  modal.style.display = 'flex';

  try {
    const res = await fetch('<?= BASE_URL ?>/api/detail_booking.php?id=' + id);
    const b = await res.json();

    if (b.error) {
      content.innerHTML = '<div class="alert alert-danger">' + b.error + '</div>';
      return;
    }

    const statusMap = {
      pending:       ['warning', '⏳ Pending'],
      terverifikasi: ['success', '✅ Terverifikasi'],
      ditolak:       ['danger',  '❌ Ditolak'],
      selesai:       ['info',    '🎉 Selesai'],
      batal:         ['danger',  '🚫 Batal'],
    };
    const [bc, bl] = statusMap[b.status] || ['warning', b.status];

    let html = `
      <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;">
        <div>
          <div style="font-size:.8rem;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Kode Booking</div>
          <div style="font-size:1.4rem;font-weight:800;color:#166534;letter-spacing:1px;">${b.kode_booking}</div>
        </div>
        <div style="text-align:right;">
          <span class="badge badge-${bc}" style="font-size:.85rem;padding:6px 14px;">${bl}</span>
          <div style="margin-top:8px;">
            <button onclick="document.getElementById('modalDetail').style.display='none'"
                    style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:#94a3b8;">✕</button>
          </div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;padding:16px;background:#f8fafc;border-radius:10px;margin-bottom:16px;">
        <div>
          <div style="font-size:.78rem;color:#64748b;">Sarana</div>
          <div style="font-weight:600;">${b.sarana_nama}</div>
        </div>
        <div>
          <div style="font-size:.78rem;color:#64748b;">Tanggal</div>
          <div style="font-weight:600;">${b.tanggal_formatted}</div>
        </div>
        <div>
          <div style="font-size:.78rem;color:#64748b;">Waktu</div>
          <div style="font-weight:600;">${b.jam_mulai.substring(0,5)} - ${b.jam_selesai.substring(0,5)} (${b.durasi_jam} jam)</div>
        </div>
        <div>
          <div style="font-size:.78rem;color:#64748b;">Dibuat</div>
          <div style="font-weight:600;">${b.created_at}</div>
        </div>
      </div>

      <h4 style="margin-bottom:8px;">👤 Data Penyewa</h4>
      <table style="width:100%;font-size:.9rem;margin-bottom:20px;">
        <tr><td style="color:#64748b;padding:4px 0;width:130px;">Nama</td><td>: <strong>${b.nama_penyewa}</strong></td></tr>
        <tr><td style="color:#64748b;padding:4px 0;">No. HP</td><td>: ${b.no_hp}</td></tr>
        <tr><td style="color:#64748b;padding:4px 0;">Email</td><td>: ${b.email || '—'}</td></tr>
        <tr><td style="color:#64748b;padding:4px 0;">Instansi</td><td>: ${b.instansi || '—'}</td></tr>
      </table>

      <h4 style="margin-bottom:8px;">💰 Rincian Biaya</h4>
      <table style="width:100%;font-size:.9rem;margin-bottom:20px;background:#f0fdf4;padding:12px;border-radius:8px;">
        <tr><td style="padding:4px 8px;color:#64748b;">Harga per jam</td>
            <td style="text-align:right;padding:4px 8px;">Rp ${b.harga_per_jam_fmt}</td></tr>
        <tr><td style="padding:4px 8px;color:#64748b;">Subtotal (${b.durasi_jam} jam)</td>
            <td style="text-align:right;padding:4px 8px;">Rp ${b.subtotal_fmt}</td></tr>
        <tr style="border-top:1px solid #86efac;">
          <td style="padding:8px;font-weight:700;font-size:1rem;">Total Bayar</td>
          <td style="text-align:right;padding:8px;font-weight:800;font-size:1.1rem;color:#166534;">Rp ${b.total_bayar_fmt}</td>
        </tr>
      </table>
    `;

    // Pembayaran
    html += `
      <h4 style="margin-bottom:8px;">💳 Informasi Pembayaran</h4>
      <div style="background:#f8fafc;border-radius:10px;padding:14px;margin-bottom:20px;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
          <div>
            <div style="font-size:.78rem;color:#64748b;">Metode</div>
            <div style="font-weight:700;">
              ${b.metode_pembayaran === 'transfer' ? '🏦 Transfer Bank' : (b.metode_pembayaran === 'tunai' ? '💵 Tunai' : '—')}
            </div>
          </div>
          <div>
            <div style="font-size:.78rem;color:#64748b;">Status</div>
            <div style="font-weight:700;">${b.status_pembayaran_label}</div>
          </div>
        </div>
    `;

    if (b.bukti_transfer_url) {
      const isPdf = b.bukti_transfer_ext === 'pdf';
      html += `
        <div style="margin-bottom:12px;">
          <div style="font-size:.78rem;color:#64748b;margin-bottom:6px;">Bukti Transfer</div>
          ${isPdf 
            ? `<a href="${b.bukti_transfer_url}" target="_blank" class="btn btn-accent btn-sm">📄 Lihat PDF</a>`
            : `<a href="${b.bukti_transfer_url}" target="_blank">
                 <img src="${b.bukti_transfer_url}" style="max-width:100%;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.1);">
               </a>`
          }
        </div>`;
    }

    if (b.catatan_pembayaran) {
      html += `<div class="alert alert-info" style="margin-bottom:12px;">
        <strong>📝 Catatan Pembayaran:</strong> ${b.catatan_pembayaran}
      </div>`;
    }

    if (b.status_pembayaran !== 'berhasil') {
      html += `
        <form method="POST" action="" style="margin-top:8px;">
          <input type="hidden" name="id" value="${b.id}">
          <input type="hidden" name="return_qs" value="<?= e($returnQs) ?>">
          <div class="form-group" style="margin-bottom:10px;">
            <label style="font-size:.85rem;">Catatan Pembayaran (opsional)</label>
            <input type="text" name="catatan_pembayaran" class="form-control" 
                   placeholder="cth: Pembayaran sudah masuk">
          </div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;">
      `;

      if (b.metode_pembayaran === 'transfer') {
        html += `
            <button name="action" value="verifikasi_bayar" class="btn btn-primary btn-sm">✅ Verifikasi Pembayaran</button>
            <button name="action" value="tolak_bayar" class="btn btn-danger btn-sm"
                    onclick="return confirm('Tolak pembayaran ini?')">❌ Tolak Bukti</button>`;
      } else if (b.metode_pembayaran === 'tunai') {
        html += `
            <button name="action" value="tandai_lunas" class="btn btn-primary btn-sm">💵 Tandai Sudah Bayar</button>`;
      }

      html += `</div></form>`;
    } else {
      html += `<div style="color:#16a34a;font-weight:600;font-size:.9rem;">✅ Pembayaran sudah lunas</div>`;
    }

    html += `</div>`;

    // Gambar kode booking
    if (b.kode_gambar) {
      html += `
        <h4 style="margin-bottom:8px;">🖼️ Kode Booking</h4>
        <div style="text-align:center;background:#f8fafc;padding:16px;border-radius:10px;margin-bottom:20px;">
          <img src="<?= BASE_URL ?>/${b.kode_gambar}" style="max-width:400px;width:100%;border-radius:8px;">
        </div>`;
    }

    if (b.catatan_petugas) {
      html += `
        <div class="alert alert-info" style="margin-bottom:20px;">
          <strong>📝 Catatan:</strong><br>${b.catatan_petugas}
        </div>`;
    }

    // Form aksi booking
    html += `<div style="border-top:1px solid #e2e8f0;padding-top:20px;">`;

    if (b.status === 'pending') {
      html += `
        <form method="POST" action="">
          <input type="hidden" name="id" value="${b.id}">
          <input type="hidden" name="return_qs" value="<?= e($returnQs) ?>">
          <div class="form-group">
            <label>Catatan Booking (opsional)</label>
            <textarea name="catatan" class="form-control" rows="2"></textarea>
          </div>
          <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button name="action" value="verifikasi" class="btn btn-primary">✅ Verifikasi Booking</button>
            <button name="action" value="tolak" class="btn btn-danger" 
                    onclick="return confirm('Yakin tolak booking ini?')">❌ Tolak Booking</button>
          </div>
        </form>`;
    } else if (b.status === 'terverifikasi') {
      html += `
        <form method="POST" action="" onsubmit="return confirm('Tandai booking sebagai SELESAI?')">
          <input type="hidden" name="id" value="${b.id}">
          <input type="hidden" name="return_qs" value="<?= e($returnQs) ?>">
          <button name="action" value="selesai" class="btn btn-primary">🎉 Tandai Selesai</button>
        </form>`;
    } else {
      html += `
        <form method="POST" action="" onsubmit="return confirm('Hapus booking ini secara permanen?')">
          <input type="hidden" name="id" value="${b.id}">
          <input type="hidden" name="return_qs" value="<?= e($returnQs) ?>">
          <button name="action" value="hapus" class="btn btn-danger">🗑️ Hapus Permanen</button>
        </form>`;
    }

    html += `</div>`;
    content.innerHTML = html;

  } catch (e) {
    content.innerHTML = '<div class="alert alert-danger">Gagal memuat data: ' + e.message + '</div>';
  }
}

document.getElementById('modalDetail').addEventListener('click', (e) => {
  if (e.target.id === 'modalDetail') e.target.style.display = 'none';
});
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') document.getElementById('modalDetail').style.display = 'none';
});
</script>

<?php
$content = ob_get_clean();
$title = 'Kelola Reservasi';
$pageTitle = 'Kelola Reservasi';
$activeMenu = 'reservasi';
require_once __DIR__ . '/../../includes/dashboard_layout.php';
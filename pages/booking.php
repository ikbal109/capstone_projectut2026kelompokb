<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth.php';

$sarana_id = (int)($_GET['sarana_id'] ?? 0);
$sarana = $conn->query("SELECT * FROM sarana WHERE id=$sarana_id")->fetch_assoc();
if (!$sarana) die('Sarana tidak ditemukan');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama        = trim($_POST['nama_penyewa']);
    $no_hp       = trim($_POST['no_hp']);
    $email       = trim($_POST['email']);
    $instansi    = trim($_POST['instansi']);
    $tanggal     = $_POST['tanggal'];
    $jam_mulai   = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];
    $metode      = $_POST['metode_pembayaran'] ?? '';

    if (!$nama || !$no_hp || !$tanggal || !$jam_mulai || !$jam_selesai) $errors[] = 'Semua field bertanda * wajib diisi.';
    if (strtotime($tanggal) < strtotime(date('Y-m-d'))) $errors[] = 'Tanggal tidak boleh di masa lalu.';
    if (strtotime($jam_selesai) <= strtotime($jam_mulai)) $errors[] = 'Jam selesai harus lebih dari jam mulai.';
    if (!in_array($metode, ['qris','transfer'])) $errors[] = 'Pilih metode pembayaran.';

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM booking WHERE sarana_id=? AND tanggal=? AND status IN ('pending','terverifikasi') AND (jam_mulai < ? AND jam_selesai > ?)");
        $stmt->bind_param('isss', $sarana_id, $tanggal, $jam_selesai, $jam_mulai);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) $errors[] = 'Jadwal bentrok dengan booking lain. Silakan pilih jam lain.';
    }

    if (empty($errors)) {
        $h = hitungTotal($conn, $sarana_id, $tanggal, $jam_mulai, $jam_selesai);
        $kode = generateKodeBooking();
        $status_bayar = 'belum_bayar';

        $stmt = $conn->prepare("INSERT INTO booking 
          (kode_booking,sarana_id,nama_penyewa,no_hp,email,instansi,tanggal,jam_mulai,jam_selesai,durasi_jam,harga_per_jam,subtotal,total_bayar,metode_pembayaran,status_pembayaran) 
          VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('sisssssssidddss',
            $kode, $sarana_id, $nama, $no_hp, $email, $instansi, $tanggal,
            $jam_mulai, $jam_selesai, $h['durasi'], $h['harga_per_jam'],
            $h['subtotal'], $h['total'],
            $metode, $status_bayar
        );
        $stmt->execute();
        $booking_id = $conn->insert_id;

        $kode_gambar = generateKodeGambar($kode);
        $conn->query("UPDATE booking SET kode_gambar='" . $conn->real_escape_string($kode_gambar) . "' WHERE id=$booking_id");

        redirect(BASE_URL . '/pages/booking_sukses.php?id=' . $booking_id);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Form Booking — SIPASTRA</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/landing.css">
<style>
  /* CSS KHUSUS HALAMAN BOOKING */
  .booking-wrapper {
    padding: 100px 20px 60px 20px;
    max-width: 900px;
    margin: 0 auto;
  }
  .page-title {
    margin-bottom: 24px;
  }
  .page-title h1 {
    font-size: 2rem;
    font-weight: 800;
    color: #1e293b;
    margin: 0 0 8px 0;
  }
  .page-title p {
    font-size: 1rem;
    color: #64748b;
    margin: 0;
  }
  .page-title p strong {
    color: #166534;
  }

  .card {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,.04);
    border: 1px solid #e2e8f0;
    padding: 28px;
    margin-bottom: 24px;
  }
  .card h3 {
    font-size: 1.2rem;
    color: #1e293b;
    margin: 0 0 8px 0;
    font-weight: 700;
  }
  .card p.sub {
    font-size: 0.9rem;
    color: #64748b;
    margin: 0 0 20px 0;
  }

  /* Form Inputs */
  .form-group {
    margin-bottom: 20px;
  }
  .form-group label {
    display: block;
    font-size: 0.9rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 8px;
  }
  .form-control {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 0.95rem;
    font-family: inherit;
    transition: 0.2s;
    background: #f8fafc;
    box-sizing: border-box;
  }
  .form-control:focus {
    outline: none;
    border-color: #166534;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(22,101,52,.1);
  }

  /* Metode Pembayaran */
  .metode-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-top: 8px;
  }
  .metode-card {
    cursor: pointer;
    padding: 16px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    transition: .2s;
    display: flex;
    flex-direction: column;
    gap: 4px;
  }
  .metode-card input {
    margin-right: 8px;
  }
  .metode-card strong {
    font-size: 1rem;
    color: #1e293b;
  }
  .metode-card span {
    font-size: .85rem;
    color: #64748b;
    margin-left: 22px;
  }
  .metode-card:has(input:checked) {
    border-color: #166534 !important;
    background: #f0fdf4;
  }

  .btn-submit {
    display: block;
    width: 100%;
    background: #166534;
    color: #ffffff;
    padding: 16px;
    font-size: 1.05rem;
    font-weight: 700;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    transition: 0.2s;
    box-shadow: 0 4px 12px rgba(22,101,52,.2);
    margin-top: 10px;
  }
  .btn-submit:hover {
    background: #14532d;
    transform: translateY(-2px);
  }

  /* Jam & Tanggal Info Box */
  .info-box {
    background: #f8fafc; 
    padding: 16px; 
    border-radius: 8px; 
    margin-bottom: 24px; 
    border: 1px solid #e2e8f0;
  }
  .info-box strong { color: #1e293b; font-size: 0.95rem; }
  .info-box span { color: #166534; font-weight: 600; font-size: 0.95rem; }

  @media (max-width: 768px) {
    .metode-grid { grid-template-columns: 1fr; }
    .booking-wrapper { padding: 90px 16px 40px 16px; }
  }
</style>
</head>
<body class="landing-body">

<!-- NAVBAR KUSTOM -->
<nav class="landing-navbar">
  <div class="landing-container nav-inner">
    <a href="<?= BASE_URL ?>/index.php" class="brand-logo">
      SIPASTRA<span>.</span>
    </a>

    <!-- Tombol Hamburger untuk HP -->
    <button class="mobile-menu-btn" onclick="toggleMenu()">☰</button>

    <!-- Menu Links -->
    <div class="nav-links" id="navLinks">
      <a href="<?= BASE_URL ?>/index.php#beranda">Beranda</a>
      <a href="<?= BASE_URL ?>/pages/login.php">Admin</a>
      <a href="<?= BASE_URL ?>/pages/informasi.php">Informasi</a>
      <a href="<?= BASE_URL ?>/pages/cek_booking.php" class="btn-cek-reservasi">Cek Reservasi</a>
    </div>
  </div>
</nav>

<div class="booking-wrapper">
  <a href="<?= BASE_URL ?>/pages/detail_sarana.php?id=<?= $sarana['id'] ?>" style="color:#64748b; font-size:0.9rem; text-decoration:none; display:inline-block; margin-bottom:16px;">← Kembali ke Detail Sarana</a>

  <div class="page-title">
    <h1>Form Booking</h1>
    <p><strong><?= e($sarana['nama']) ?></strong> — Rp <?= number_format($sarana['harga_per_jam'],0,',','.') ?> / jam</p>
  </div>

  <?php if (!empty($errors)): ?>
    <?php foreach ($errors as $err): ?>
      <div style="background:#fef2f2; border-left:4px solid #ef4444; color:#991b1b; padding:16px; border-radius:8px; margin-bottom:20px; font-size:0.95rem;">
        <?= e($err) ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <!-- KALENDER -->
  <div class="card">
    <h3>📅 Ketersediaan 30 Hari ke Depan</h3>
    <p class="sub">Klik tanggal untuk melihat jam yang tersedia.</p>
    <div id="kalender" style="display:grid;grid-template-columns:repeat(7,1fr);gap:8px;">
      <div style="text-align:center;font-size:.8rem;font-weight:700;color:#64748b;padding:6px;">Sen</div>
      <div style="text-align:center;font-size:.8rem;font-weight:700;color:#64748b;padding:6px;">Sel</div>
      <div style="text-align:center;font-size:.8rem;font-weight:700;color:#64748b;padding:6px;">Rab</div>
      <div style="text-align:center;font-size:.8rem;font-weight:700;color:#64748b;padding:6px;">Kam</div>
      <div style="text-align:center;font-size:.8rem;font-weight:700;color:#64748b;padding:6px;">Jum</div>
      <div style="text-align:center;font-size:.8rem;font-weight:700;color:#64748b;padding:6px;">Sab</div>
      <div style="text-align:center;font-size:.8rem;font-weight:700;color:#64748b;padding:6px;">Min</div>
      <div id="kalender-hari" style="display:contents;"></div>
    </div>
  </div>

  <!-- SLOT JAM -->
  <div class="card">
    <h3>🕐 Slot Jam</h3>
    <p class="sub" id="slot-info">Pilih tanggal di atas untuk melihat jam yang tersedia.</p>
    <div id="slot-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(80px,1fr));gap:8px;"></div>

    <div id="range-info" style="display:none; margin-top:20px; padding:16px; background:#f0fdf4; border-left:4px solid #166534; border-radius:8px;">
      <div style="font-size:.9rem; color:#166534;">
        <strong>📌 Pilihan Anda:</strong> <span id="range-text"></span>
      </div>
    </div>
  </div>

  <!-- FORM BOOKING -->
  <form method="POST" class="card">
    <h3>📝 Data Penyewa</h3>
    <p class="sub" style="margin-bottom:24px;">Lengkapi data diri Anda di bawah ini.</p>

    <div class="form-group">
      <label>Nama Penyewa *</label>
      <input type="text" name="nama_penyewa" class="form-control" required value="<?= e($_POST['nama_penyewa']??'') ?>" placeholder="Masukkan nama lengkap">
    </div>
    <div class="form-group">
      <label>No. HP *</label>
      <input type="text" name="no_hp" class="form-control" required value="<?= e($_POST['no_hp']??'') ?>" placeholder="Contoh: 081234567890">
    </div>
    <div class="form-group">
      <label>Email (Opsional)</label>
      <input type="email" name="email" class="form-control" value="<?= e($_POST['email']??'') ?>" placeholder="Masukkan alamat email">
    </div>
    <div class="form-group">
      <label>Instansi / Asal (Opsional)</label>
      <input type="text" name="instansi" class="form-control" value="<?= e($_POST['instansi']??'') ?>" placeholder="Contoh: Universitas Lambung Mangkurat">
    </div>
    
    <!-- INPUT TANGGAL & JAM DISEMBUNYIKAN -->
    <div style="display:none;">
      <input type="date" name="tanggal" id="input-tanggal" required min="<?= date('Y-m-d') ?>" value="<?= e($_POST['tanggal']??date('Y-m-d')) ?>">
      <input type="time" name="jam_mulai" id="input-mulai" required value="<?= e($_POST['jam_mulai']??'08:00') ?>">
      <input type="time" name="jam_selesai" id="input-selesai" required value="<?= e($_POST['jam_selesai']??'10:00') ?>">
    </div>
    
    <!-- TAMPILAN INFO JADWAL TERPILIH -->
    <div class="info-box">
      <div style="margin-bottom:8px;">
        <strong>Jadwal Terpilih:</strong><br>
        <span id="info-tanggal"></span>
      </div>
      <div>
        <strong>Waktu:</strong><br>
        <span id="info-jam"></span>
      </div>
    </div>

    <!-- METODE PEMBAYARAN -->
    <h3 style="margin-top:32px;">💳 Metode Pembayaran</h3>
    <p class="sub">Pilih metode pembayaran yang Anda inginkan.</p>
    
    <div class="metode-grid">
      <label class="metode-card">
        <div style="display:flex; align-items:center;">
          <input type="radio" name="metode_pembayaran" value="qris" required>
          <strong>📱 QRIS</strong>
        </div>
        <span>Scan QRIS & upload bukti</span>
      </label>
      <label class="metode-card">
        <div style="display:flex; align-items:center;">
          <input type="radio" name="metode_pembayaran" value="transfer">
          <strong>🏦 Transfer Bank</strong>
        </div>
        <span>Transfer & upload bukti</span>
      </label>
    </div>

    <button class="btn-submit" style="margin-top:32px;">📅 Booking Sekarang</button>
  </form>
</div>

<footer style="text-align:center; padding:20px; background:#f8fafc; color:#64748b; font-size:.85rem; border-top:1px solid #e2e8f0;">
  &copy; <?= date('Y') ?> SIPASTRA - BPSDM Provinsi Kalimantan Selatan.
</footer>

<script>
const SARANA_ID = <?= (int)$sarana_id ?>;
const BASE = '<?= BASE_URL ?>';

function tanggalKeISO(d) {
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

function formatTanggal(iso) {
  const d = new Date(iso + 'T00:00:00');
  const bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
  return `${d.getDate()} ${bulan[d.getMonth()]} ${d.getFullYear()}`;
}

async function loadKalender() {
  const res = await fetch(`${BASE}/api/ketersediaan.php?sarana_id=${SARANA_ID}&mode=bulan`);
  const data = await res.json();
  const container = document.getElementById('kalender-hari');
  container.innerHTML = '';

  const today = new Date();
  today.setHours(0, 0, 0, 0);
  let firstDay = new Date(today);
  const offset = (today.getDay() + 6) % 7;
  firstDay.setDate(today.getDate() - offset);

  for (let i = 0; i < offset; i++) container.innerHTML += '<div></div>';

  for (let i = 0; i < 42; i++) {
    const d = new Date(firstDay);
    d.setDate(firstDay.getDate() + i);
    d.setHours(0, 0, 0, 0);

    const iso = tanggalKeISO(d);
    const isPast = d < today;
    const jumlah = data[iso] || 0;

    const bg = isPast ? '#f8fafc' : (jumlah === 0 ? '#dcfce7' : (jumlah < 5 ? '#fef3c7' : '#fee2e2'));
    const color = isPast ? '#cbd5e1' : (jumlah === 0 ? '#166534' : (jumlah < 5 ? '#92400e' : '#991b1b'));

    const div = document.createElement('div');
    div.dataset.tanggal = iso;
    div.style.cssText = `padding:10px 4px;text-align:center;border-radius:8px;background:${bg};color:${color};font-size:.85rem;cursor:${isPast?'not-allowed':'pointer'};border:2px solid transparent;transition:.2s;`;
    div.innerHTML = `<div style="font-weight:700;">${d.getDate()}</div><div style="font-size:.7rem;">${isPast?'-':(jumlah===0?'kosong':jumlah+' jam')}</div>`;

    if (!isPast) {
      div.onmouseover = () => div.style.borderColor = '#166534';
      div.onmouseout = () => { if (div.dataset.tanggal !== document.getElementById('input-tanggal').value) div.style.borderColor = 'transparent'; else div.style.borderColor = '#166534'; };
      div.onclick = () => pilihTanggal(iso);
    }
    container.appendChild(div);
  }
  tandaiTanggalAktif(document.getElementById('input-tanggal').value);
}

function tandaiTanggalAktif(iso) {
  document.querySelectorAll('#kalender-hari > div').forEach(el => {
    if (el.dataset.tanggal === iso) {
      el.style.borderColor = '#166534'; el.style.boxShadow = '0 0 0 2px rgba(22,101,52,.25)';
    } else if (!el.style.cursor || el.style.cursor !== 'not-allowed') {
      el.style.borderColor = 'transparent'; el.style.boxShadow = 'none';
    }
  });
  document.getElementById('info-tanggal').textContent = formatTanggal(iso);
}

const slotState = { start: null, end: null, tombol: [] };

function resetSlotVisual() {
  slotState.tombol.forEach(btn => {
    if (btn.disabled) return;
    btn.style.background = '#f8fafc'; btn.style.color = '#334155';
    btn.style.borderColor = '#cbd5e1'; btn.style.fontWeight = '500'; btn.style.boxShadow = 'none';
  });
}

function terapkanRange() {
  resetSlotVisual();
  const s = slotState.start;
  const e = slotState.end;

  if (!s) { document.getElementById('range-info').style.display = 'none'; return; }

  slotState.tombol.forEach(btn => {
    if (btn.disabled) return;
    const j = btn.dataset.jam;

    if (e) {
      if (j >= s && j <= e) {
        btn.style.background = '#166534'; btn.style.color = '#fff'; btn.style.borderColor = '#166534'; btn.style.fontWeight = '600';
      }
      if (j === s) btn.style.boxShadow = '0 0 0 2px #facc15';
    } else {
      if (j === s) {
        btn.style.background = '#166534'; btn.style.color = '#fff'; btn.style.borderColor = '#166534'; btn.style.fontWeight = '600'; btn.style.boxShadow = '0 0 0 2px #facc15';
      }
    }
  });

  document.getElementById('input-mulai').value = s;
  if (e) document.getElementById('input-selesai').value = e; 

  const info = document.getElementById('range-info');
  const text = document.getElementById('range-text');
  info.style.display = 'block';

  if (e) {
    const durasi = parseInt(e.split(':')[0]) - parseInt(s.split(':')[0]);
    text.textContent = `${s} - ${e} (${durasi} jam)`; 
    document.getElementById('info-jam').textContent = `${s} - ${e} (${durasi} jam)`;
  } else {
    text.textContent = `Mulai ${s} — silakan pilih jam selesai`;
    document.getElementById('info-jam').textContent = `Mulai ${s} — silakan pilih jam selesai`;
  }
}

function onSlotClick(jam) {
  if (!slotState.start) { slotState.start = jam; slotState.end = null; }
  else if (slotState.end) { slotState.start = jam; slotState.end = null; }
  else {
    if (jam === slotState.start) { slotState.start = null; slotState.end = null; }
    else if (jam < slotState.start) { slotState.end = slotState.start; slotState.start = jam; }
    else { slotState.end = jam; }
  }
  terapkanRange();
}

async function pilihTanggal(tanggal) {
  document.getElementById('input-tanggal').value = tanggal;
  tandaiTanggalAktif(tanggal);

  const info = document.getElementById('slot-info');
  info.textContent = 'Memuat slot jam...';

  const res = await fetch(`${BASE}/api/ketersediaan.php?sarana_id=${SARANA_ID}&mode=hari&tanggal=${tanggal}`);
  const data = await res.json();

  const grid = document.getElementById('slot-grid');
  grid.innerHTML = ''; slotState.start = null; slotState.end = null; slotState.tombol = [];

  const bookedList = data.booked || [];
  info.innerHTML = `Klik jam mulai, lalu klik jam selesai.`;

  for (let jam = 7; jam <= 21; jam++) {
    const slotMulai = String(jam).padStart(2, '0') + ':00';
    const terisi = bookedList.includes(slotMulai);

    const btn = document.createElement('button');
    btn.type = 'button'; btn.textContent = slotMulai; btn.dataset.jam = slotMulai; btn.disabled = terisi;
    btn.style.cssText = `padding:10px;border-radius:8px;border:1px solid ${terisi?'#fecaca':'#cbd5e1'};background:${terisi?'#fee2e2':'#f8fafc'};color:${terisi?'#991b1b':'#334155'};font-weight:500;cursor:${terisi?'not-allowed':'pointer'};transition:.15s;`;

    if (!terisi) btn.onclick = () => onSlotClick(slotMulai);
    grid.appendChild(btn); slotState.tombol.push(btn);
  }
}

document.getElementById('input-tanggal').addEventListener('change', e => {
  if (e.target.value) pilihTanggal(e.target.value);
});

function toggleMenu() {
  var menu = document.getElementById("navLinks");
  menu.classList.toggle("active");
}

loadKalender();
pilihTanggal(document.getElementById('input-tanggal').value);
</script>

</body>
</html>
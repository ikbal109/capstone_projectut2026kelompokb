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

    if (!$nama || !$no_hp || !$tanggal || !$jam_mulai || !$jam_selesai) $errors[] = 'Semua field wajib diisi.';
    if (strtotime($tanggal) < strtotime(date('Y-m-d'))) $errors[] = 'Tanggal tidak boleh di masa lalu.';
    if (strtotime($jam_selesai) <= strtotime($jam_mulai)) $errors[] = 'Jam selesai harus lebih dari jam mulai.';

    // Cek bentrok
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM booking WHERE sarana_id=? AND tanggal=? AND status IN ('pending','terverifikasi') AND (jam_mulai < ? AND jam_selesai > ?)");
        $stmt->bind_param('isss', $sarana_id, $tanggal, $jam_selesai, $jam_mulai);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) $errors[] = 'Jadwal bentrok dengan booking lain. Silakan pilih jam lain.';
    }

    if (empty($errors)) {
        $h = hitungTotal($conn, $sarana_id, $tanggal, $jam_mulai, $jam_selesai);
        $kode = generateKodeBooking();
        $diskon_id = $h['diskon']['id'] ?? null;

        // 1) Simpan DULU (tanpa kode_gambar)
        $stmt = $conn->prepare("INSERT INTO booking 
          (kode_booking,sarana_id,nama_penyewa,no_hp,email,instansi,tanggal,jam_mulai,jam_selesai,durasi_jam,harga_per_jam,subtotal,diskon_id,diskon_persen,total_bayar) 
          VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('sisssssssidddid',
            $kode, $sarana_id, $nama, $no_hp, $email, $instansi, $tanggal,
            $jam_mulai, $jam_selesai, $h['durasi'], $h['harga_per_jam'],
            $h['subtotal'], $diskon_id, $h['persen'], $h['total']
        );
        $stmt->execute();
        $booking_id = $conn->insert_id;

        // 2) Baru generate gambar & update
        $kode_gambar = generateKodeGambar($kode);
        $conn->query("UPDATE booking SET kode_gambar='" . $conn->real_escape_string($kode_gambar) . "' WHERE id=$booking_id");

        redirect(BASE_URL . '/pages/booking_sukses.php?id=' . $booking_id);
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container page" style="max-width:900px;">
  <a href="<?= BASE_URL ?>/pages/detail_sarana.php?id=<?= $sarana['id'] ?>" style="color:#64748b;font-size:.9rem;">← Kembali ke Detail Sarana</a>

  <h2 style="margin-top:12px;">Form Booking</h2>
  <p style="color:#64748b;margin-bottom:20px;">
    <strong><?= e($sarana['nama']) ?></strong> — 
    Rp <?= number_format($sarana['harga_per_jam'],0,',','.') ?>/jam
  </p>

  <?php foreach ($errors as $err): ?>
    <div class="alert alert-danger"><?= e($err) ?></div>
  <?php endforeach; ?>

  <!-- ================= KALENDER KETERSEDIAAN 30 HARI ================= -->
  <div class="card" style="padding:20px;margin-bottom:20px;">
    <h3 style="margin-bottom:6px;">📅 Ketersediaan 30 Hari ke Depan</h3>
    <p style="color:#64748b;font-size:.9rem;margin-bottom:16px;">
      Klik tanggal untuk melihat jam yang tersedia. Angka = jumlah jam terisi.
    </p>
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

  <!-- ================= SLOT JAM PER TANGGAL ================= -->
  <div class="card" style="padding:20px;margin-bottom:20px;" id="slot-container">
    <h3 style="margin-bottom:6px;">🕐 Slot Jam</h3>
    <p style="color:#64748b;font-size:.9rem;margin-bottom:16px;" id="slot-info">
      Pilih tanggal di atas untuk melihat jam yang tersedia.
    </p>
    <div id="slot-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(90px,1fr));gap:8px;"></div>
  </div>

  <!-- ================= FORM BOOKING ================= -->
  <form method="POST" style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.06);">
    <div class="form-group"><label>Nama Penyewa *</label>
      <input type="text" name="nama_penyewa" class="form-control" required value="<?= e($_POST['nama_penyewa']??'') ?>"></div>
    <div class="form-group"><label>No. HP *</label>
      <input type="text" name="no_hp" class="form-control" required value="<?= e($_POST['no_hp']??'') ?>"></div>
    <div class="form-group"><label>Email</label>
      <input type="email" name="email" class="form-control" value="<?= e($_POST['email']??'') ?>"></div>
    <div class="form-group"><label>Instansi/Asal</label>
      <input type="text" name="instansi" class="form-control" value="<?= e($_POST['instansi']??'') ?>"></div>
    <div class="form-group"><label>Tanggal *</label>
      <input type="date" name="tanggal" id="input-tanggal" class="form-control" required 
             min="<?= date('Y-m-d') ?>" value="<?= e($_POST['tanggal']??date('Y-m-d')) ?>"></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
      <div class="form-group"><label>Jam Mulai *</label>
        <input type="time" name="jam_mulai" id="input-mulai" class="form-control" required value="<?= e($_POST['jam_mulai']??'08:00') ?>"></div>
      <div class="form-group"><label>Jam Selesai *</label>
        <input type="time" name="jam_selesai" id="input-selesai" class="form-control" required value="<?= e($_POST['jam_selesai']??'10:00') ?>"></div>
    </div>
    <button class="btn btn-primary btn-block">📅 Booking Sekarang</button>
  </form>
</div>

<script>
const SARANA_ID = <?= (int)$sarana_id ?>;
const BASE = '<?= BASE_URL ?>';

// ============ LOAD KALENDER 30 HARI ============
async function loadKalender() {
  const res = await fetch(`${BASE}/api/ketersediaan.php?sarana_id=${SARANA_ID}&mode=bulan`);
  const data = await res.json();

  const container = document.getElementById('kalender-hari');
  container.innerHTML = '';

  // Cari hari pertama (offset dari hari Senin)
  const today = new Date();
  let firstDay = new Date(today);
  let offset = (today.getDay() + 6) % 7; // Senin = 0
  firstDay.setDate(today.getDate() - offset);

  // Kosongkan hari sebelum hari ini
  for (let i = 0; i < offset; i++) {
    container.innerHTML += '<div></div>';
  }

  // Loop 30 hari ke depan
  for (let i = 0; i < 42; i++) {
    const d = new Date(firstDay);
    d.setDate(firstDay.getDate() + i);
    const iso = d.toISOString().slice(0,10);
    const isPast = d < new Date(today.toDateString());
    const jumlah = data[iso] || 0;

    const bg = isPast ? '#f8fafc' : (jumlah === 0 ? '#dcfce7' : (jumlah < 5 ? '#fef3c7' : '#fee2e2'));
    const color = isPast ? '#cbd5e1' : (jumlah === 0 ? '#166534' : (jumlah < 5 ? '#92400e' : '#991b1b'));

    const div = document.createElement('div');
    div.style.cssText = `padding:10px 4px;text-align:center;border-radius:8px;background:${bg};color:${color};font-size:.85rem;cursor:${isPast?'not-allowed':'pointer'};border:2px solid transparent;transition:.2s;`;
    div.innerHTML = `<div style="font-weight:700;">${d.getDate()}</div>
                     <div style="font-size:.7rem;">${isPast?'-':(jumlah===0?'kosong':jumlah+' jam')}</div>`;
    if (!isPast) {
      div.onmouseover = () => div.style.borderColor = '#166534';
      div.onmouseout = () => div.style.borderColor = 'transparent';
      div.onclick = () => pilihTanggal(iso);
    }
    container.appendChild(div);
  }
}

// ============ PILIH TANGGAL → LOAD SLOT JAM ============
async function pilihTanggal(tanggal) {
  document.getElementById('input-tanggal').value = tanggal;
  const info = document.getElementById('slot-info');
  info.textContent = 'Memuat slot jam...';

  const res = await fetch(`${BASE}/api/ketersediaan.php?sarana_id=${SARANA_ID}&mode=hari&tanggal=${tanggal}`);
  const data = await res.json();

  const grid = document.getElementById('slot-grid');
  grid.innerHTML = '';

  info.innerHTML = `<strong>${formatTanggal(tanggal)}</strong> — Klik jam mulai, lalu klik jam selesai untuk auto-isi.`;

  // Slot 07:00 - 22:00
  let selectedStart = null;
  for (let jam = 7; jam <= 21; jam++) {
    const slotMulai = String(jam).padStart(2,'0') + ':00';
    const slotAkhir = String(jam+1).padStart(2,'0') + ':00';
    const terisi = data.booked && data.booked.includes(slotMulai);

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.textContent = slotMulai;
    btn.dataset.jam = slotMulai;
    btn.disabled = terisi;
    btn.style.cssText = `padding:10px;border-radius:8px;border:2px solid ${terisi?'#fecaca':'#bbf7d0'};
      background:${terisi?'#fee2e2':'#f0fdf4'};color:${terisi?'#991b1b':'#166534'};
      font-weight:600;cursor:${terisi?'not-allowed':'pointer'};transition:.15s;`;

    if (!terisi) {
      btn.onclick = () => {
        if (!selectedStart) {
          selectedStart = slotMulai;
          btn.style.background = '#166534';
          btn.style.color = '#fff';
          btn.dataset.selected = '1';
          document.getElementById('input-mulai').value = slotMulai;
        } else {
          // Pilih akhir
          const end = slotMulai > selectedStart ? slotMulai : selectedStart;
          const start = slotMulai > selectedStart ? selectedStart : slotMulai;
          document.getElementById('input-mulai').value = start;
          document.getElementById('input-selesai').value = hitungJamPlus(end, 1);
          selectedStart = null;
          // Reset visual
          grid.querySelectorAll('button').forEach(b => {
            if (!b.disabled) {
              b.style.background = '#f0fdf4';
              b.style.color = '#166534';
            }
          });
          // Highlight range
          grid.querySelectorAll('button').forEach(b => {
            const j = b.dataset.jam;
            if (j >= start && j < end) {
              b.style.background = '#166534';
              b.style.color = '#fff';
            }
          });
        }
      };
    }
    grid.appendChild(btn);
  }
}

function hitungJamPlus(jam, plus) {
  const h = parseInt(jam.split(':')[0]) + plus;
  return String(h).padStart(2,'0') + ':00';
}

function formatTanggal(iso) {
  const d = new Date(iso + 'T00:00:00');
  const hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
  const bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
  return `${hari[d.getDay()]}, ${d.getDate()} ${bulan[d.getMonth()]} ${d.getFullYear()}`;
}

// Auto-load saat ganti input tanggal manual
document.getElementById('input-tanggal').addEventListener('change', e => {
  if (e.target.value) pilihTanggal(e.target.value);
});

// Init
loadKalender();
pilihTanggal(document.getElementById('input-tanggal').value);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
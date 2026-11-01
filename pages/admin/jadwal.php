<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

ob_start();
?>

<style>
  .kalender-wrapper {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 8px;
  }
  .kalender-header {
    text-align: center;
    font-size: 0.8rem;
    font-weight: 700;
    color: #64748b;
    padding: 6px;
  }
  .tgl-box {
    padding: 10px 4px;
    text-align: center;
    border-radius: 8px;
    font-size: 0.85rem;
    cursor: pointer;
    border: 2px solid transparent;
    transition: 0.2s;
  }
  .tgl-box.past { cursor: not-allowed; }
  .tgl-box:hover:not(.past) { border-color: #166534; }
  .tgl-box.aktif { border-color: #166534 !important; box-shadow: 0 0 0 2px rgba(22,101,52,.25); }

  .jadwal-sarana-card {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    background: #fff;
  }
  .jadwal-sarana-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .slot-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(85px, 1fr));
    gap: 10px;
  }
  .slot-item {
    padding: 10px;
    border-radius: 6px;
    text-align: center;
    font-size: 0.85rem;
    font-weight: 600;
    border: 1px solid transparent;
  }
  .slot-kosong { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }
  .slot-terisi { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
</style>

<div class="welcome">
  <h1>Jadwal Fasilitas</h1>
  <p>Pantau ketersediaan jam seluruh sarana olahraga berdasarkan tanggal.</p>
</div>

<div class="chart-card" style="margin-bottom:20px;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <button type="button" id="btn-prev" class="btn btn-sm" onclick="ubahBulan(-1)" style="background:#f1f5f9; border:1px solid #e2e8f0; padding:8px 16px;">&laquo; Sebelumnya</button>
    <h3 id="judul-bulan" style="margin:0; font-weight:700; color:#1e293b;">Bulan</h3>
    <button type="button" id="btn-next" class="btn btn-sm" onclick="ubahBulan(1)" style="background:#f1f5f9; border:1px solid #e2e8f0; padding:8px 16px;">Berikutnya &raquo;</button>
  </div>

  <div class="kalender-wrapper">
    <div class="kalender-header">Sen</div>
    <div class="kalender-header">Sel</div>
    <div class="kalender-header">Rab</div>
    <div class="kalender-header">Kam</div>
    <div class="kalender-header">Jum</div>
    <div class="kalender-header">Sab</div>
    <div class="kalender-header">Min</div>
    <div id="kalender-hari" style="display:contents;"></div>
  </div>
</div>

<div id="hasil-jadwal">
  <div class="chart-card" style="text-align:center; color:#94a3b8; padding:40px;">
    Silakan pilih tanggal pada kalender di atas untuk melihat jadwal.
  </div>
</div>

<script>
let viewDate = new Date();
let tanggalTerpilih = "";

function formatBulanTahun(date) {
  const bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
  return `${bulan[date.getMonth()]} ${date.getFullYear()}`;
}

function tanggalKeISO(d) {
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

function ubahBulan(step) {
  viewDate.setMonth(viewDate.getMonth() + step);
  loadKalender();
}

async function loadKalender() {
  const year = viewDate.getFullYear();
  const month = viewDate.getMonth();
  const bulanParam = `${year}-${String(month + 1).padStart(2, '0')}`;
  
  document.getElementById('judul-bulan').textContent = formatBulanTahun(viewDate);
  
  const today = new Date();
  today.setHours(0,0,0,0);
  const isCurrentMonth = (year === today.getFullYear() && month === today.getMonth());
  
  const btnPrev = document.getElementById('btn-prev');
  btnPrev.style.opacity = isCurrentMonth ? '0.5' : '1';
  btnPrev.style.pointerEvents = isCurrentMonth ? 'none' : 'auto';

  const container = document.getElementById('kalender-hari');
  container.innerHTML = '<div style="grid-column:span 7; text-align:center; color:#94a3b8; padding:20px;">Memuat data kalender...</div>';

  // Fetch ke API
  try {
    const res = await fetch(`<?= BASE_URL ?>/api/ketersediaan.php?sarana_id=0&mode=bulan&bulan=${bulanParam}&t=` + Date.now());
    const data = await res.json();
    
    container.innerHTML = '';
    const firstDay = new Date(year, month, 1);
    let startOffset = firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1; 
    const lastDay = new Date(year, month + 1, 0).getDate();

    for (let i = 0; i < startOffset; i++) container.innerHTML += '<div></div>';

    for (let day = 1; day <= lastDay; day++) {
      const d = new Date(year, month, day);
      d.setHours(0,0,0,0);
      const iso = tanggalKeISO(d);
      const isPast = d < today;
      const jumlah = data[iso] || 0; 

      const bg = isPast ? '#f8fafc' : (jumlah === 0 ? '#dcfce7' : (jumlah < 5 ? '#fef3c7' : '#fee2e2'));
      const color = isPast ? '#cbd5e1' : (jumlah === 0 ? '#166534' : (jumlah < 5 ? '#92400e' : '#991b1b'));

      const div = document.createElement('div');
      div.className = 'tgl-box' + (isPast ? ' past' : '') + (tanggalTerpilih === iso ? ' aktif' : '');
      div.style.cssText = `background:${bg}; color:${color};`;
      div.innerHTML = `<div style="font-weight:700;">${day}</div>
                       <div style="font-size:.7rem;">${isPast?'-':(jumlah===0?'kosong':jumlah+' jam')}</div>`;
      
      if (!isPast) {
        div.onclick = () => pilihTanggal(iso, div);
      }
      container.appendChild(div);
    }
  } catch (err) {
    container.innerHTML = '<div style="grid-column:span 7; color:#ef4444; text-align:center;">Gagal memuat kalender.</div>';
  }
}

async function pilihTanggal(iso, el) {
  tanggalTerpilih = iso;
  document.querySelectorAll('.tgl-box').forEach(b => b.classList.remove('aktif'));
  el.classList.add('aktif');

  const container = document.getElementById('hasil-jadwal');
  container.innerHTML = '<div class="chart-card" style="text-align:center; color:#64748b;">Memuat jadwal...</div>';

  try {
    const res = await fetch(`<?= BASE_URL ?>/api/jadwal_harian.php?tanggal=${iso}`);
    const data = await res.json();

    container.innerHTML = '';

    if (data.length === 0) {
      container.innerHTML = '<div class="chart-card" style="text-align:center; color:#94a3b8;">Belum ada data sarana.</div>';
      return;
    }

    data.forEach(sarana => { // <-- Typo 'sarama' sudah diperbaiki jadi 'sarana' di sini
      let slotsHtml = '';
      for (let jam = 7; jam <= 21; jam++) {
        const slot = String(jam).padStart(2, '0') + ':00';
        const isBooked = sarana.booked.includes(slot);
        
        if (isBooked) {
          slotsHtml += `<div class="slot-item slot-terisi">${slot}<br><span style="font-size:0.7rem;font-weight:400;">Terisi</span></div>`;
        } else {
          slotsHtml += `<div class="slot-item slot-kosong">${slot}<br><span style="font-size:0.7rem;font-weight:400;">Kosong</span></div>`;
        }
      }

      container.innerHTML += `
        <div class="jadwal-sarana-card">
          <div class="jadwal-sarana-title">
            🏟️ ${sarana.nama} 
            <span style="font-size:0.75rem; background:#e0f2fe; color:#0369a1; padding:4px 12px; border-radius:20px; font-weight:600;">${sarana.jenis}</span>
          </div>
          <div class="slot-grid">
            ${slotsHtml}
          </div>
        </div>
      `;
    });
  } catch (err) {
    container.innerHTML = '<div class="chart-card" style="text-align:center; color:#ef4444;">Gagal memuat jadwal harian.</div>';
  }
}

loadKalender();
</script>

<?php
$content = ob_get_clean();
$title = 'Jadwal Fasilitas';
$pageTitle = 'Jadwal Fasilitas';
$activeMenu = 'jadwal';
require_once __DIR__ . '/../../includes/dashboard_layout.php';
?>
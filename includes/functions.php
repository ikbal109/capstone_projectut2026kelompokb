<?php
require_once __DIR__ . '/../config/database.php';

function e($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

function generateKodeBooking() {
    return 'BKG-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
}

// Cek diskon yang berlaku pada tanggal tertentu
function getDiskonByTanggal($conn, $tanggal) {
    $stmt = $conn->prepare("SELECT * FROM diskon 
        WHERE aktif=1 AND ? BETWEEN tanggal_mulai AND tanggal_selesai 
        ORDER BY persen DESC LIMIT 1");
    $stmt->bind_param('s', $tanggal);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Generate gambar kode booking (SVG, tidak butuh GD extension)
function generateKodeGambar($kode, $simpanDir = null) {
    if (!$simpanDir) $simpanDir = __DIR__ . '/../assets/booking_codes/';
    if (!is_dir($simpanDir)) mkdir($simpanDir, 0777, true);

    $filename = 'kode_' . $kode . '.svg';
    $filepath = $simpanDir . $filename;

    $kodeEsc = htmlspecialchars($kode, ENT_XML1, 'UTF-8');

    $svg = '<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="500" height="220" viewBox="0 0 500 220">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#166534"/>
      <stop offset="100%" stop-color="#14532d"/>
    </linearGradient>
  </defs>
  <rect width="500" height="220" fill="url(#bg)" rx="12"/>
  <rect x="10" y="10" width="480" height="200" fill="none" stroke="#facc15" stroke-width="2" rx="8"/>

  <text x="30" y="52" fill="#ffffff" font-family="Segoe UI, Arial, sans-serif" font-size="20" font-weight="bold">KODE BOOKING</text>
  <text x="30" y="74" fill="#facc15" font-family="Segoe UI, Arial, sans-serif" font-size="11">Reservasi Sarana Olahraga BPSDMD Kalsel</text>

  <rect x="30" y="95" width="440" height="60" fill="#ffffff" rx="8"/>
  <text x="250" y="135" fill="#166534" font-family="Courier New, monospace" font-size="30" font-weight="bold" text-anchor="middle">' . $kodeEsc . '</text>

  <text x="250" y="185" fill="#facc15" font-family="Segoe UI, Arial, sans-serif" font-size="11" text-anchor="middle">Tunjukkan gambar ini ke petugas saat check-in</text>
</svg>';

    file_put_contents($filepath, $svg);
    return 'assets/booking_codes/' . $filename;
}

// Hitung harga total
function hitungTotal($conn, $sarana_id, $tanggal, $jam_mulai, $jam_selesai) {
    $s = $conn->query("SELECT harga_per_jam FROM sarana WHERE id=" . (int)$sarana_id)->fetch_assoc();
    $harga = (float)$s['harga_per_jam'];

    $t1 = strtotime($jam_mulai);
    $t2 = strtotime($jam_selesai);
    $durasi = max(1, (int)round(($t2 - $t1) / 3600));

    $subtotal = $harga * $durasi;

    return [
        'harga_per_jam' => $harga,
        'durasi'        => $durasi,
        'subtotal'      => $subtotal,
        'total'         => $subtotal,
    ];
}

function redirect($url) { header("Location: $url"); exit; }

function flash($key, $msg = null) {
    if ($msg === null) {
        $v = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $v;
    }
    $_SESSION['flash'][$key] = $msg;
}

// ============ HELPER SARANA ============

// Buat slug dari nama
function buatSlug($str) {
    $str = strtolower(trim($str));
    $str = preg_replace('/[^a-z0-9]+/', '-', $str);
    return trim($str, '-');
}

// Buat slug unik (kalau sudah ada, tambah -1, -2, dst)
function buatSlugUnik($conn, $nama, $exclude_id = null) {
    $base = buatSlug($nama);
    $slug = $base;
    $i = 1;
    while (true) {
        $sql = "SELECT id FROM sarana WHERE slug = ?";
        if ($exclude_id) $sql .= " AND id != " . (int)$exclude_id;
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $slug);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) break;
        $slug = $base . '-' . $i++;
    }
    return $slug;
}

// Upload gambar, return nama file atau null
function uploadGambar($file, $prefix = 'sarana') {
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) return null;

    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return null;

    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) return null;

    $dir = __DIR__ . '/../assets/img/sarana/';
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    $filename = $prefix . '-' . time() . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $dir . $filename)) return $filename;
    return null;
}

// Hapus file gambar
function hapusGambar($filename) {
    if (!$filename) return;
    $path = __DIR__ . '/../assets/img/sarana/' . $filename;
    if (file_exists($path)) unlink($path);
}

// Ambil galeri sebagai array
function getGaleri($json) {
    if (empty($json)) return [];
    $arr = json_decode($json, true);
    return is_array($arr) ? $arr : [];
}

// ============ HELPER PENGATURAN ============
function getPengaturan($conn, $kunci, $default = '') {
    $stmt = $conn->prepare("SELECT nilai FROM pengaturan WHERE kunci=?");
    $stmt->bind_param('s', $kunci);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    return $r ? $r['nilai'] : $default;
}

function setPengaturan($conn, $kunci, $nilai) {
    $stmt = $conn->prepare("INSERT INTO pengaturan (kunci, nilai) VALUES (?, ?) 
                            ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)");
    $stmt->bind_param('ss', $kunci, $nilai);
    return $stmt->execute();
}

// Label status pembayaran
function labelPembayaran($status) {
    $map = [
        'belum_bayar'         => ['#fef3c7', '#92400e', '💵 Belum Bayar'],
        'menunggu_verifikasi' => ['#dbeafe', '#1e40af', '⏳ Menunggu Verifikasi'],
        'berhasil'            => ['#dcfce7', '#166534', '✅ Berhasil'],
        'gagal'               => ['#fee2e2', '#991b1b', '❌ Gagal'],
    ];
    return $map[$status] ?? ['#f1f5f9', '#64748b', $status];
}
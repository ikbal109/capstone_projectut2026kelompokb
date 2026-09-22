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

// Generate gambar kode booking (PNG) menggunakan GD
function generateKodeGambar($kode, $simpanDir = null) {
    if (!$simpanDir) $simpanDir = __DIR__ . '/../assets/booking_codes/';
    if (!is_dir($simpanDir)) mkdir($simpanDir, 0777, true);

    $w = 500; $h = 220;
    $img = imagecreatetruecolor($w, $h);

    $bg     = imagecolorallocate($img, 20, 83, 45);      // hijau tua
    $white  = imagecolorallocate($img, 255, 255, 255);
    $accent = imagecolorallocate($img, 250, 204, 21);    // kuning

    imagefilledrectangle($img, 0, 0, $w, $h, $bg);
    imagerectangle($img, 8, 8, $w-9, $h-9, $accent);

    // Header
    imagestring($img, 5, 30, 30, 'KODE BOOKING', $white);
    imagestring($img, 3, 30, 55, 'Reservasi Sarana Olahraga BPSDMD Kalsel', $accent);

    // Kode besar
    $fontSize = 5;
    $textWidth = imagefontwidth($fontSize) * strlen($kode);
    $x = ($w - $textWidth) / 2;
    imagestring($img, $fontSize, $x, 100, $kode, $white);

    // Footer
    $footer = 'Tunjukkan gambar ini ke petugas saat check-in';
    $fw = imagefontwidth(3) * strlen($footer);
    imagestring($img, 3, ($w - $fw)/2, 170, $footer, $accent);

    $filename = 'kode_' . $kode . '.png';
    imagepng($img, $simpanDir . $filename);
    imagedestroy($img);

    return 'assets/booking_codes/' . $filename;
}

// Hitung harga + diskon
function hitungTotal($conn, $sarana_id, $tanggal, $jam_mulai, $jam_selesai) {
    $s = $conn->query("SELECT harga_per_jam FROM sarana WHERE id=" . (int)$sarana_id)->fetch_assoc();
    $harga = (float)$s['harga_per_jam'];

    $t1 = strtotime($jam_mulai);
    $t2 = strtotime($jam_selesai);
    $durasi = max(1, (int)round(($t2 - $t1) / 3600));

    $subtotal = $harga * $durasi;
    $diskon = getDiskonByTanggal($conn, $tanggal);
    $persen = $diskon ? (float)$diskon['persen'] : 0;
    $total = $subtotal - ($subtotal * $persen / 100);

    return [
        'harga_per_jam' => $harga,
        'durasi'        => $durasi,
        'subtotal'      => $subtotal,
        'diskon'        => $diskon,
        'persen'        => $persen,
        'total'         => $total,
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
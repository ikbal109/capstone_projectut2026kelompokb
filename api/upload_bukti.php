<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/pages/cek_booking.php');
}

$kode  = trim($_POST['kode_booking'] ?? '');
$no_hp = trim($_POST['no_hp'] ?? '');

$stmt = $conn->prepare("SELECT id, metode_pembayaran, status_pembayaran FROM booking WHERE kode_booking=? AND no_hp=?");
$stmt->bind_param('ss', $kode, $no_hp);
$stmt->execute();
$b = $stmt->get_result()->fetch_assoc();

if (!$b) {
    flash('err', 'Kode booking atau No. HP tidak cocok.');
    redirect(BASE_URL . '/pages/cek_booking.php?q=' . urlencode($kode));
}

if (!in_array($b['metode_pembayaran'], ['transfer', 'qris'])) {
    flash('err', 'Booking ini tidak menggunakan metode pembayaran digital (QRIS/Transfer).');
    redirect(BASE_URL . '/pages/cek_booking.php?q=' . urlencode($kode));
}

if ($b['status_pembayaran'] === 'berhasil') {
    flash('err', 'Pembayaran sudah diverifikasi, tidak perlu upload lagi.');
    redirect(BASE_URL . '/pages/cek_booking.php?q=' . urlencode($kode));
}

$file = $_FILES['bukti'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    flash('err', 'File tidak valid atau gagal diupload.');
    redirect(BASE_URL . '/pages/cek_booking.php?q=' . urlencode($kode));
}

$allowed = ['jpg','jpeg','png','webp','pdf'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowed)) {
    flash('err', 'Format file harus JPG, PNG, WEBP, atau PDF.');
    redirect(BASE_URL . '/pages/cek_booking.php?q=' . urlencode($kode));
}
if ($file['size'] > 5 * 1024 * 1024) {
    flash('err', 'Ukuran file maksimal 5 MB.');
    redirect(BASE_URL . '/pages/cek_booking.php?q=' . urlencode($kode));
}

$dir = __DIR__ . '/../assets/bukti_transfer/';
if (!is_dir($dir)) mkdir($dir, 0777, true);

$filename = 'bukti-' . $b['id'] . '-' . time() . '.' . $ext;
if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
    flash('err', 'Gagal menyimpan file.');
    redirect(BASE_URL . '/pages/cek_booking.php?q=' . urlencode($kode));
}

// Hapus file lama
$old = $conn->query("SELECT bukti_transfer FROM booking WHERE id={$b['id']}")->fetch_assoc();
if (!empty($old['bukti_transfer'])) {
    $oldPath = __DIR__ . '/../' . $old['bukti_transfer'];
    if (file_exists($oldPath)) @unlink($oldPath);
}

$path = 'assets/bukti_transfer/' . $filename;
$stmt = $conn->prepare("UPDATE booking SET bukti_transfer=?, status_pembayaran='menunggu_verifikasi' WHERE id=?");
$stmt->bind_param('si', $path, $b['id']);
$stmt->execute();

flash('msg', '✅ Bukti transfer berhasil diupload. Menunggu verifikasi admin.');
redirect(BASE_URL . '/pages/cek_booking.php?q=' . urlencode($kode));
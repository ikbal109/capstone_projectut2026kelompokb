<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/pages/cek_booking.php');
}

$kode            = trim($_POST['kode_booking'] ?? '');
$no_hp           = trim($_POST['no_hp'] ?? '');
$redirect_sukses = isset($_POST['redirect_sukses']) && $_POST['redirect_sukses'] == '1';

// Cari data booking
$stmt = $conn->prepare("SELECT id, metode_pembayaran, status_pembayaran FROM booking WHERE kode_booking=? AND no_hp=?");
$stmt->bind_param('ss', $kode, $no_hp);
$stmt->execute();
$b = $stmt->get_result()->fetch_assoc();

// Tentukan URL Redirect (Jika dari halaman sukses, kembalikan ke sukses, jika tidak ke cek booking)
$url_kembali = ($redirect_sukses && $b) 
    ? BASE_URL . '/pages/booking_sukses.php?id=' . $b['id'] 
    : BASE_URL . '/pages/cek_booking.php?q=' . urlencode($kode);

// Validasi 1: Booking tidak ditemukan
if (!$b) {
    flash('err', 'Kode booking atau No. HP tidak cocok.');
    redirect(BASE_URL . '/pages/cek_booking.php?q=' . urlencode($kode));
}

// Validasi 2: Metode bukan QRIS/Transfer
if (!in_array($b['metode_pembayaran'], ['qris', 'transfer'])) {
    flash('err', 'Booking ini tidak menggunakan metode pembayaran QRIS/Transfer.');
    redirect($url_kembali);
}

// Validasi 3: Pembayaran sudah lunas
if ($b['status_pembayaran'] === 'berhasil') {
    flash('err', 'Pembayaran sudah diverifikasi, tidak perlu upload lagi.');
    redirect($url_kembali);
}

// Validasi 4: File tidak ada / Error upload
$file = $_FILES['bukti'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    flash('err', 'File tidak valid atau gagal diupload.');
    redirect($url_kembali);
}

// Validasi 5: Ekstensi File
$allowed = ['jpg','jpeg','png','webp','pdf'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowed)) {
    flash('err', 'Format file harus JPG, PNG, WEBP, atau PDF.');
    redirect($url_kembali);
}

// Validasi 6: Ukuran File (Maks 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    flash('err', 'Ukuran file maksimal 5 MB.');
    redirect($url_kembali);
}

// Proses Upload File
$dir = __DIR__ . '/../assets/bukti_transfer/';
if (!is_dir($dir)) mkdir($dir, 0777, true);

$filename = 'bukti-' . $b['id'] . '-' . time() . '.' . $ext;
if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
    flash('err', 'Gagal menyimpan file ke server.');
    redirect($url_kembali);
}

// Hapus file lama jika ada
$old = $conn->query("SELECT bukti_transfer FROM booking WHERE id={$b['id']}")->fetch_assoc();
if (!empty($old['bukti_transfer'])) {
    $oldPath = __DIR__ . '/../' . $old['bukti_transfer'];
    if (file_exists($oldPath)) @unlink($oldPath);
}

// Update Database
$path = 'assets/bukti_transfer/' . $filename;
$stmt = $conn->prepare("UPDATE booking SET bukti_transfer=?, status_pembayaran='menunggu_verifikasi' WHERE id=?");
$stmt->bind_param('si', $path, $b['id']);
$stmt->execute();

// Berhasil
flash('msg', '✅ Bukti pembayaran berhasil diunggah. Menunggu verifikasi admin.');
redirect($url_kembali);
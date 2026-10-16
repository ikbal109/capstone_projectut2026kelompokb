<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode(['error' => 'ID tidak valid']);
    exit;
}

$stmt = $conn->prepare("
    SELECT b.*, s.nama AS sarana_nama, s.jenis AS sarana_jenis
    FROM booking b
    JOIN sarana s ON s.id = b.sarana_id
    WHERE b.id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$b = $stmt->get_result()->fetch_assoc();

if (!$b) {
    echo json_encode(['error' => 'Booking tidak ditemukan']);
    exit;
}

$b['tanggal_formatted'] = date('d F Y', strtotime($b['tanggal']));
$b['created_at']        = date('d M Y H:i', strtotime($b['created_at']));
$b['harga_per_jam_fmt'] = number_format($b['harga_per_jam'], 0, ',', '.');
$b['subtotal_fmt']      = number_format($b['subtotal'], 0, ',', '.');
$b['total_bayar_fmt']   = number_format($b['total_bayar'], 0, ',', '.');

// Pembayaran
$b['status_pembayaran']  = $b['status_pembayaran'] ?? 'belum_bayar';
$b['metode_pembayaran']  = $b['metode_pembayaran'] ?? '';
$b['bukti_transfer_url'] = !empty($b['bukti_transfer']) ? BASE_URL . '/' . $b['bukti_transfer'] : null;
$b['bukti_transfer_ext'] = !empty($b['bukti_transfer']) ? strtolower(pathinfo($b['bukti_transfer'], PATHINFO_EXTENSION)) : null;
$b['catatan_pembayaran'] = !empty($b['catatan_pembayaran']) ? htmlspecialchars($b['catatan_pembayaran'], ENT_QUOTES) : null;
$b['status_pembayaran_label'] = labelPembayaran($b['status_pembayaran'])[2];

// Escape
$b['catatan_petugas'] = $b['catatan_petugas'] ? htmlspecialchars($b['catatan_petugas'], ENT_QUOTES) : null;
$b['nama_penyewa']    = htmlspecialchars($b['nama_penyewa'], ENT_QUOTES);
$b['no_hp']           = htmlspecialchars($b['no_hp'], ENT_QUOTES);
$b['email']           = $b['email'] ? htmlspecialchars($b['email'], ENT_QUOTES) : null;
$b['instansi']        = $b['instansi'] ? htmlspecialchars($b['instansi'], ENT_QUOTES) : null;
$b['sarana_nama']     = htmlspecialchars($b['sarana_nama'], ENT_QUOTES);
$b['sarana_jenis']    = htmlspecialchars($b['sarana_jenis'], ENT_QUOTES);

echo json_encode($b);
<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$sarana_id   = (int)($_GET['sarana_id'] ?? 0);
$mode        = $_GET['mode'] ?? 'hari';
$bulan_param = $_GET['bulan'] ?? date('Y-m');

if (!$sarana_id && $mode === 'hari') {
    echo json_encode(['error' => 'sarana_id required for mode=hari']);
    exit;
}

// ---------- MODE BULAN: hitung jam terisi per hari ----------
if ($mode === 'bulan') {
    $startDate = $bulan_param . '-01';
    $endDate   = date('Y-m-t', strtotime($startDate)); // Akhir bulan

    // Jika sarana_id = 0, ambil SEMUA sarana. Jika > 0, filter per sarana.
    if ($sarana_id > 0) {
        $stmt = $conn->prepare("
            SELECT tanggal, jam_mulai, jam_selesai
            FROM booking
            WHERE sarana_id = ? 
              AND status IN ('pending','terverifikasi')
              AND tanggal BETWEEN ? AND ?
        ");
        $stmt->bind_param('iss', $sarana_id, $startDate, $endDate);
    } else {
        $stmt = $conn->prepare("
            SELECT tanggal, jam_mulai, jam_selesai
            FROM booking
            WHERE status IN ('pending','terverifikasi')
              AND tanggal BETWEEN ? AND ?
        ");
        $stmt->bind_param('ss', $startDate, $endDate);
    }

    $stmt->execute();
    $res = $stmt->get_result();

    // Hitung manual pakai PHP (Aman dari error MySQL)
    $out = [];
    while ($row = $res->fetch_assoc()) {
        $tgl = $row['tanggal'];
        $start = (int)substr($row['jam_mulai'], 0, 2);
        $end   = (int)substr($row['jam_selesai'], 0, 2);
        
        $durasi = $end - $start;
        if ($durasi <= 0) $durasi = 1; // Jaga-jaga jika format jam aneh

        if (!isset($out[$tgl])) $out[$tgl] = 0;
        $out[$tgl] += $durasi;
    }
    
    // Paksa jadi objek (bukan array kosong) agar JS mudah membacanya
    echo json_encode((object)$out);
    exit;
}

// ---------- MODE HARI: list jam yang terisi untuk 1 sarana ----------
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

$stmt = $conn->prepare("
    SELECT jam_mulai, jam_selesai
    FROM booking
    WHERE sarana_id = ? AND tanggal = ?
      AND status IN ('pending','terverifikasi')
");
$stmt->bind_param('is', $sarana_id, $tanggal);
$stmt->execute();
$res = $stmt->get_result();

$bookedSlots = [];
while ($row = $res->fetch_assoc()) {
    $start = (int)substr($row['jam_mulai'], 0, 2);
    $end   = (int)substr($row['jam_selesai'], 0, 2);
    for ($h = $start; $h < $end; $h++) {
        $bookedSlots[] = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
    }
}

echo json_encode([
    'tanggal' => $tanggal,
    'booked'  => array_values(array_unique($bookedSlots)),
]);
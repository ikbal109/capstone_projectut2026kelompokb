<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$sarana_id = (int)($_GET['sarana_id'] ?? 0);
$mode      = $_GET['mode'] ?? 'hari';

if (!$sarana_id) {
    echo json_encode(['error' => 'sarana_id required']);
    exit;
}

// ---------- MODE BULAN: hitung jam terisi per hari (30 hari) ----------
if ($mode === 'bulan') {
    $stmt = $conn->prepare("
        SELECT tanggal, SUM(TIME_TO_SEC(TIMEDIFF(jam_selesai, jam_mulai))/3600) AS jam
        FROM booking
        WHERE sarana_id = ? 
          AND status IN ('pending','terverifikasi')
          AND tanggal BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        GROUP BY tanggal
    ");
    $stmt->bind_param('i', $sarana_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $out = [];
    while ($row = $res->fetch_assoc()) {
        $out[$row['tanggal']] = (int)$row['jam'];
    }
    echo json_encode($out);
    exit;
}

// ---------- MODE HARI: list jam yang terisi ----------
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
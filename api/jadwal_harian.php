<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

// 1. Ambil semua data sarana
$sarana = [];
$q = $conn->query("SELECT id, nama, jenis FROM sarana ORDER BY id ASC");
while($r = $q->fetch_assoc()) {
    $r['booked'] = [];
    $sarana[$r['id']] = $r;
}

// 2. Ambil data booking pada tanggal tersebut
$stmt = $conn->prepare("
    SELECT sarana_id, jam_mulai, jam_selesai 
    FROM booking 
    WHERE tanggal = ? AND status IN ('pending','terverifikasi')
");
$stmt->bind_param('s', $tanggal);
$stmt->execute();
$res = $stmt->get_result();

// 3. Petakan jam yang terisi ke masing-masing sarana
while($row = $res->fetch_assoc()) {
    $sid = $row['sarana_id'];
    if (isset($sarana[$sid])) {
        $start = (int)substr($row['jam_mulai'], 0, 2);
        $end = (int)substr($row['jam_selesai'], 0, 2);
        
        for($h = $start; $h < $end; $h++) {
            $sarana[$sid]['booked'][] = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
        }
    }
}

echo json_encode(array_values($sarana));
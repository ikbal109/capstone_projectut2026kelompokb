<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'db_reservasi_olahraga';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

date_default_timezone_set('Asia/Makassar');
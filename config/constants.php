<?php
// Deteksi otomatis BASE_URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host     = $_SERVER['HTTP_HOST']; // Ini akan otomatis jadi localhost atau 192.168.18.10

// Deteksi nama folder project secara otomatis
$root    = str_replace('\\', '/', dirname(__DIR__));
$docroot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '/'));
$base    = str_replace($docroot, '', $root);

define('BASE_URL', $protocol . '://' . $host . rtrim($base, '/'));
?>
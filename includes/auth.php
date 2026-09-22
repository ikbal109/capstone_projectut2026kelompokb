<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/functions.php';

function requireLogin() {
    if (empty($_SESSION['user_id'])) redirect(BASE_URL . '/pages/login.php');
}
function requireRole($roles) {
    requireLogin();
    if (!in_array($_SESSION['role'], (array)$roles)) {
        http_response_code(403);
        die('Akses ditolak.');
    }
}
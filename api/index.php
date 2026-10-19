<?php
// Set session save path to /tmp for Vercel Serverless environment
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.save_path', '/tmp');
}

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH);

// Bersihkan path awal
$path = ltrim($path, '/');

// Rute default admin
if ($path === 'admin' || $path === 'admin/') {
    $path = 'admin/index.php';
}

$targetFile = dirname(__DIR__) . '/' . $path;

if (file_exists($targetFile) && is_file($targetFile)) {
    // Ubah working directory agar require_once relatif berjalan lancar
    chdir(dirname($targetFile));
    require $targetFile;
    exit;
}

http_response_code(404);
header('Content-Type: application/json');
echo json_encode([
    'status' => 'error',
    'message' => 'Endpoint PHP tidak ditemukan: ' . htmlspecialchars($path)
]);

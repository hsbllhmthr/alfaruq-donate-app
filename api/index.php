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

// Cegah infinite recursion jika mengakses api/index.php langsung
if ($path === 'api/index.php' || $path === 'api' || empty($path)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Alfaruq Donate API Gateway is running.']);
    exit;
}

$targetFile = dirname(__DIR__) . '/' . $path;

// Jangan jalankan jika targetnya adalah file ini sendiri
if ($targetFile !== __FILE__ && file_exists($targetFile) && is_file($targetFile)) {
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

<?php
// Konfigurasi Database
$host = 'sql110.infinityfree.com';
$db_name = 'if0_42421651_alfaruq_donate';
$username = 'if0_42421651';
$password = 'vKXmkUiUub'; // Default XAMPP biasanya kosong. Silakan ganti sesuai setup Anda.

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Jika koneksi gagal, kembalikan response JSON error
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Koneksi ke database gagal: ' . $e->getMessage()
    ]);
    exit;
}
?>

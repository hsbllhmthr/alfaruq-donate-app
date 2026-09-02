<?php
// Konfigurasi Database - Mendukung Environment Variables, SQLite (Otomatis/Gratis), & Fallback InfinityFree/Lokal
$db_driver = getenv('DB_DRIVER') ?: (getenv('DB_HOST') ? 'mysql' : 'sqlite');
$host     = getenv('DB_HOST')     ?: 'sql110.infinityfree.com';
$port     = getenv('DB_PORT')     ?: '3306';
$db_name  = getenv('DB_NAME')     ?: 'if0_42421651_alfaruq_donate';
$username = getenv('DB_USER')     ?: 'if0_42421651';
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'vKXmkUiUub';

try {
    if ($db_driver === 'sqlite' || getenv('DB_HOST') === 'sqlite') {
        $sqlite_file = getenv('SQLITE_PATH') ?: __DIR__ . '/database.sqlite';
        $pdo = new PDO("sqlite:" . $sqlite_file);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec("PRAGMA journal_mode=WAL;");
    } else {
        $dsn = "mysql:host=$host;port=$port;dbname=$db_name;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        if (getenv('DB_SSL') === 'true') {
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }
        $pdo = new PDO($dsn, $username, $password, $options);
    }
} catch (PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Koneksi ke database gagal: ' . $e->getMessage()
    ]);
    exit;
}

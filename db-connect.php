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

// Otomatis pastikan tabel admins terbuat dan password admin = admin123
try {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $hash = password_hash('admin123', PASSWORD_DEFAULT);

    if ($driver === 'sqlite') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `donations` (
          `id` INTEGER PRIMARY KEY AUTOINCREMENT,
          `name` VARCHAR(255) NOT NULL,
          `fullName` VARCHAR(255) DEFAULT NULL,
          `isAnonymous` TINYINT(1) DEFAULT 0,
          `contact` VARCHAR(255) NOT NULL,
          `prayer` TEXT DEFAULT NULL,
          `amount` INT NOT NULL,
          `campaignId` VARCHAR(50) NOT NULL,
          `status` TEXT DEFAULT 'PENDING',
          `createdAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `admins` (
          `id` INTEGER PRIMARY KEY AUTOINCREMENT,
          `username` VARCHAR(50) NOT NULL UNIQUE,
          `password` VARCHAR(255) NOT NULL,
          `createdAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $stmt = $pdo->prepare("INSERT INTO `admins` (`username`, `password`) VALUES ('admin', :password) 
                               ON CONFLICT(`username`) DO UPDATE SET `password` = :password2");
        $stmt->execute([':password' => $hash, ':password2' => $hash]);
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `admins` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `username` VARCHAR(50) NOT NULL UNIQUE,
          `password` VARCHAR(255) NOT NULL,
          `createdAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $stmt = $pdo->prepare("INSERT INTO `admins` (`username`, `password`) VALUES ('admin', :password)
                               ON DUPLICATE KEY UPDATE `password` = :password2");
        $stmt->execute([':password' => $hash, ':password2' => $hash]);
    }
} catch (Exception $ex) {
    // Ignore migration error silently
}


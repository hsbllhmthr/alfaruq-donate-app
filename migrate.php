<?php
require_once 'db-connect.php';

try {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'sqlite') {
        // Buat tabel donations untuk SQLite
        $donationsTableSql = "CREATE TABLE IF NOT EXISTS `donations` (
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
        )";
        $pdo->exec($donationsTableSql);
        echo "Tabel 'donations' (SQLite) dipastikan.\n";

        // Buat tabel admins untuk SQLite
        $adminsTableSql = "CREATE TABLE IF NOT EXISTS `admins` (
          `id` INTEGER PRIMARY KEY AUTOINCREMENT,
          `username` VARCHAR(50) NOT NULL UNIQUE,
          `password` VARCHAR(255) NOT NULL,
          `createdAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($adminsTableSql);
        echo "Tabel 'admins' (SQLite) dipastikan.\n";

        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO `admins` (`username`, `password`) VALUES ('admin', :password)");
        $stmt->execute([':password' => $hash]);
        echo "Akun admin default dipastikan (username: admin, password: admin123).\n";

    } else {
        // MySQL Migration
        $adminsTableSql = "CREATE TABLE IF NOT EXISTS `admins` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `username` VARCHAR(50) NOT NULL UNIQUE,
          `password` VARCHAR(255) NOT NULL,
          `createdAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $pdo->exec($adminsTableSql);
        echo "Tabel 'admins' dipastikan.\n";

        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $insertAdminSql = "INSERT INTO `admins` (`username`, `password`) VALUES ('admin', :password)
                           ON DUPLICATE KEY UPDATE `username`=`username`";
        $stmt = $pdo->prepare($insertAdminSql);
        $stmt->execute([':password' => $hash]);
        echo "Akun admin default dipastikan (username: admin, password: admin123).\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

-- Membuat database (Anda bisa me-run query ini atau membuatnya langsung di phpMyAdmin)
CREATE DATABASE IF NOT EXISTS `alfaruq_donate` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `alfaruq_donate`;

-- Membuat tabel donations
CREATE TABLE IF NOT EXISTS `donations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `fullName` VARCHAR(255) DEFAULT NULL,
  `isAnonymous` TINYINT(1) DEFAULT 0,
  `contact` VARCHAR(255) NOT NULL,
  `prayer` TEXT DEFAULT NULL,
  `amount` INT NOT NULL,
  `campaignId` VARCHAR(50) NOT NULL,
  `status` ENUM('PENDING', 'SUCCESS', 'FAILED') DEFAULT 'PENDING',
  `createdAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Membuat tabel admins
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `createdAt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Memasukkan default admin (username: admin, password: admin123)
-- Hash didapat dari password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO `admins` (`username`, `password`) 
VALUES ('admin', '$2y$10$tMh4E/P.wN0q8mN2cKx7ee.qM70m3.xVwQoD.3oT56zO9ZtE.Zg0S')
ON DUPLICATE KEY UPDATE `username`=`username`;


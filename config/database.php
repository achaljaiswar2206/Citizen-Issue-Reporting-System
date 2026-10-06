<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Database Connection using PDO
 */

require_once __DIR__ . '/config.php';

$db_host = getenv('DB_HOST') ?: 'localhost';
$db_port = getenv('DB_PORT') ?: '3306';
$db_name = getenv('DB_NAME') ?: 'cirs_db';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : ''; // Default XAMPP password is empty

$dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => true,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    
    // Auto-create contact_inquiries table if it does not exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS `contact_inquiries` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `name` VARCHAR(100) NOT NULL,
      `email` VARCHAR(100) NOT NULL,
      `subject` VARCHAR(200) NOT NULL,
      `message` TEXT NOT NULL,
      `status` ENUM('NEW', 'READ', 'REPLIED') DEFAULT 'NEW',
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {
    // If the database does not exist yet or MySQL service is down, display a clean helpful screen
    if ($e->getCode() == 1049) {
        die("<div style='font-family:sans-serif; max-width:600px; margin:50px auto; padding:25px; border-left:4px solid #ef4444; background:#fef2f2; border-radius:8px;'>
            <h2 style='color:#991b1b; margin-top:0;'>Database 'cirs_db' not found!</h2>
            <p>Please import <strong>database/cirs_db.sql</strong> into your phpMyAdmin or MySQL server first.</p>
            <p><strong>Steps:</strong><br>1. Open <a href='http://localhost/phpmyadmin' target='_blank'>phpMyAdmin</a><br>2. Click <strong>Import</strong> tab<br>3. Choose <code>database/cirs_db.sql</code><br>4. Click <strong>Import</strong></p>
        </div>");
    } else {
        die("<div style='font-family:sans-serif; max-width:600px; margin:50px auto; padding:25px; border-left:4px solid #ef4444; background:#fef2f2; border-radius:8px;'>
            <h2 style='color:#991b1b; margin-top:0;'>Database Connection Error</h2>
            <p>Could not connect to MySQL server. Please ensure MySQL is running in XAMPP Control Panel.</p>
            <p><small style='color:#666;'>Error detail: " . htmlspecialchars($e->getMessage()) . "</small></p>
        </div>");
    }
}

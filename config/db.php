<?php
// Minimal PDO database connection for XAMPP / phpMyAdmin
// Place this file at c:\xampp\htdocs\EIS_2526-G03_MEGI\config\db.php
// Update credentials below if your setup differs.

declare(strict_types=1);

$DB_HOST = '127.0.0.1';
$DB_NAME = 'sie_db';
$DB_USER = 'root';
$DB_PASS = '';

$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4";
$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
];

try {
  $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
  http_response_code(500);
  echo 'Database connection failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  exit;
}

/**
 * Return the PDO instance.
 * Usage: $pdo = get_db();
 */
function get_db(): PDO
{
  global $pdo;
  return $pdo;
}

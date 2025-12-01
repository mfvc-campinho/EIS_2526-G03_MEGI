<?php
// Simple DB config for XAMPP + MySQL (adjust credentials if needed)
$DB_HOST = '127.0.0.1';
$DB_NAME = 'sie_db';
$DB_USER = 'root';
$DB_PASS = '';

// Create mysqli connection
$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_errno) {
  header('Content-Type: application/json');
  http_response_code(500);
  echo json_encode(['error' => 'Database connection failed', 'details' => $mysqli->connect_error]);
  exit;
}
// Set charset
$mysqli->set_charset('utf8mb4');

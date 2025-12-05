<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/includes/data_loader.php';

$out = load_app_data($mysqli);

echo json_encode($out, JSON_UNESCAPED_UNICODE);

$mysqli->close();

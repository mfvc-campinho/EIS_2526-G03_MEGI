<?php
session_start();
header('Content-Type: application/javascript; charset=utf-8');
require_once __DIR__ . '/includes/data_loader.php';

$data = load_app_data($mysqli);
$currentUser = isset($_SESSION['user']) ? $_SESSION['user'] : null;

// Use HEX_* flags to avoid breaking out of the script context.
$encodedData = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$encodedUser = json_encode($currentUser, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$timestamp = time();

echo "window.SERVER_APP_DATA = {$encodedData};";
echo "window.SERVER_AUTH_USER = {$encodedUser};";
echo "window.SERVER_APP_DATA_TS = {$timestamp};";

$mysqli->close();

<?php
session_start();
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../config/db.php';

$userId = $_SESSION['user']['id'] ?? null;
$targetId = $_POST['target_id'] ?? null;
if (!$userId || !$targetId || $userId === $targetId) {
  flash_set('error', 'Operação inválida.');
  header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'user_page.php'));
  exit;
}

// toggle follow
$existsStmt = $mysqli->prepare('SELECT 1 FROM user_followers WHERE follower_id = ? AND following_id = ? LIMIT 1');
$existsStmt->bind_param('ss', $userId, $targetId);
$existsStmt->execute();
$res = $existsStmt->get_result();
$exists = $res && $res->num_rows > 0;
$existsStmt->close();

if ($exists) {
  $del = $mysqli->prepare('DELETE FROM user_followers WHERE follower_id = ? AND following_id = ?');
  $del->bind_param('ss', $userId, $targetId);
  $del->execute();
  $del->close();
  flash_set('success', 'Deixou de seguir.');
} else {
  $ins = $mysqli->prepare('INSERT INTO user_followers (follower_id,following_id) VALUES (?,?)');
  $ins->bind_param('ss', $userId, $targetId);
  $ins->execute();
  $ins->close();
  flash_set('success', 'Agora está a seguir.');
}

$mysqli->close();
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'user_page.php?id=' . urlencode($targetId)));
exit;


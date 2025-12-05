<?php
session_start();
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../config/db.php';

$userId = $_SESSION['user']['id'] ?? null;
if (!$userId) {
  flash_set('error', 'Precisa de iniciar sessão.');
  header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'home_page.php'));
  exit;
}

$type = $_POST['type'] ?? null; // collection|item
$id = $_POST['id'] ?? null;
if (!$type || !$id) {
  flash_set('error', 'Dados em falta.');
  header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'home_page.php'));
  exit;
}

function toggle_like($mysqli, $table, $col, $id, $userId)
{
  // check existing
  $stmt = $mysqli->prepare("SELECT 1 FROM {$table} WHERE {$col} = ? AND user_id = ? LIMIT 1");
  $stmt->bind_param('ss', $id, $userId);
  $stmt->execute();
  $res = $stmt->get_result();
  $exists = $res && $res->num_rows > 0;
  $stmt->close();

  if ($exists) {
    $del = $mysqli->prepare("DELETE FROM {$table} WHERE {$col} = ? AND user_id = ?");
    $del->bind_param('ss', $id, $userId);
    $del->execute();
    $del->close();
    return false; // now unliked
  } else {
    $ins = $mysqli->prepare("INSERT INTO {$table} ({$col}, user_id, last_updated) VALUES (?, ?, NOW())");
    $ins->bind_param('ss', $id, $userId);
    $ins->execute();
    $ins->close();
    return true; // now liked
  }
}

if ($type === 'collection') {
  toggle_like($mysqli, 'user_liked_collections', 'liked_collection_id', $id, $userId);
} elseif ($type === 'item') {
  toggle_like($mysqli, 'user_liked_items', 'liked_item_id', $id, $userId);
}

$mysqli->close();
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'home_page.php'));
exit;


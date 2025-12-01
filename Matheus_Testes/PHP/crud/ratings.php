<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

$action = $_POST['action'] ?? null;
$userId = $_POST['userId'] ?? null;

function respond($data) {
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function operateLike($mysqli, $table, $field, $userId, $targetId, $add) {
  if (!$userId || !$targetId) return ['error' => 'missing params'];

  // Fetch existing
  $selSql = "SELECT {$field} FROM {$table} WHERE user_id = ? LIMIT 1";
  $sel = $mysqli->prepare($selSql);
  if ($sel === false) return ['error' => 'db error'];
  $sel->bind_param('s', $userId);
  $sel->execute();
  $res = $sel->get_result();
  $row = $res->fetch_assoc();
  $sel->close();

  $current = [];
  if ($row && !empty($row[$field])) {
    $decoded = json_decode($row[$field], true);
    if (is_array($decoded)) $current = $decoded;
  }

  if ($add) {
    if (!in_array($targetId, $current, true)) $current[] = $targetId;
  } else {
    $current = array_values(array_filter($current, function($v) use ($targetId) { return $v !== $targetId; }));
  }

  $json = !empty($current) ? json_encode(array_values(array_unique($current)), JSON_UNESCAPED_UNICODE) : null;
  $now = date('Y-m-d H:i:s');

  if ($row) {
    $upSql = "UPDATE {$table} SET {$field} = ?, last_updated = ? WHERE user_id = ?";
    $up = $mysqli->prepare($upSql);
    if ($up === false) return ['error' => 'db error'];
    $up->bind_param('sss', $json, $now, $userId);
    $ok = $up->execute();
    $up->close();
    return ['success' => (bool)$ok, 'likes' => $current];
  } else {
    $insSql = "INSERT INTO {$table} (user_id,last_updated,{$field}) VALUES (?,?,?)";
    $ins = $mysqli->prepare($insSql);
    if ($ins === false) return ['error' => 'db error'];
    $ins->bind_param('sss', $userId, $now, $json);
    $ok = $ins->execute();
    $ins->close();
    return ['success' => (bool)$ok, 'likes' => $current];
  }
}

if ($action === 'likeCollection' || $action === 'unlikeCollection') {
  $collectionId = $_POST['collectionId'] ?? null;
  if (!$userId || !$collectionId) { http_response_code(400); respond(['error'=>'missing params']); }
  $res = operateLike($mysqli, 'user_ratings_collections', 'liked_collections', $userId, $collectionId, $action === 'likeCollection');
  respond($res);
}

if ($action === 'likeItem' || $action === 'unlikeItem') {
  $itemId = $_POST['itemId'] ?? null;
  if (!$userId || !$itemId) { http_response_code(400); respond(['error'=>'missing params']); }
  $res = operateLike($mysqli, 'user_ratings_items', 'liked_items', $userId, $itemId, $action === 'likeItem');
  respond($res);
}

if ($action === 'likeEvent' || $action === 'unlikeEvent') {
  $eventId = $_POST['eventId'] ?? null;
  if (!$userId || !$eventId) { http_response_code(400); respond(['error'=>'missing params']); }
  $res = operateLike($mysqli, 'user_ratings_events', 'liked_events', $userId, $eventId, $action === 'likeEvent');
  respond($res);
}

// Return user's likes across the three tables
if ($action === 'getUserLikes') {
  if (!$userId) { http_response_code(400); respond(['error'=>'missing userId']); }
  $out = ['likedCollections'=>[], 'likedItems'=>[], 'likedEvents'=>[]];
  $r1 = $mysqli->prepare('SELECT liked_collections FROM user_ratings_collections WHERE user_id = ? LIMIT 1');
  if ($r1) { $r1->bind_param('s',$userId); $r1->execute(); $res = $r1->get_result(); if ($row = $res->fetch_assoc()) { $dec = json_decode($row['liked_collections'] ?? '[]', true); if (is_array($dec)) $out['likedCollections'] = $dec; } $r1->close(); }
  $r2 = $mysqli->prepare('SELECT liked_items FROM user_ratings_items WHERE user_id = ? LIMIT 1');
  if ($r2) { $r2->bind_param('s',$userId); $r2->execute(); $res = $r2->get_result(); if ($row = $res->fetch_assoc()) { $dec = json_decode($row['liked_items'] ?? '[]', true); if (is_array($dec)) $out['likedItems'] = $dec; } $r2->close(); }
  $r3 = $mysqli->prepare('SELECT liked_events FROM user_ratings_events WHERE user_id = ? LIMIT 1');
  if ($r3) { $r3->bind_param('s',$userId); $r3->execute(); $res = $r3->get_result(); if ($row = $res->fetch_assoc()) { $dec = json_decode($row['liked_events'] ?? '[]', true); if (is_array($dec)) $out['likedEvents'] = $dec; } $r3->close(); }
  respond(['success'=>true,'likes'=>$out]);
}

http_response_code(400);
respond(['error' => 'unknown action']);

$mysqli->close();

?>

<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

$action = $_POST['action'] ?? null;
// Require authenticated session user; do not accept client-supplied userId for security
if (empty($_SESSION['user']) || empty($_SESSION['user']['id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'Unauthorized']);
  exit;
}
$userId = $_SESSION['user']['id'];

function respond($data)
{
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function operateLike($mysqli, $table, $field, $userId, $targetId, $add)
{
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
    $current = array_values(array_filter($current, function ($v) use ($targetId) {
      return $v !== $targetId;
    }));
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
  if (!$userId || !$collectionId) {
    http_response_code(400);
    respond(['error' => 'missing params']);
  }
  $res = operateLike($mysqli, 'user_ratings_collections', 'liked_collections', $userId, $collectionId, $action === 'likeCollection');
  respond($res);
}

if ($action === 'likeItem' || $action === 'unlikeItem') {
  $itemId = $_POST['itemId'] ?? null;
  if (!$userId || !$itemId) {
    http_response_code(400);
    respond(['error' => 'missing params']);
  }
  $res = operateLike($mysqli, 'user_ratings_items', 'liked_items', $userId, $itemId, $action === 'likeItem');
  respond($res);
}

if ($action === 'likeEvent' || $action === 'unlikeEvent') {
  $eventId = $_POST['eventId'] ?? null;
  if (!$userId || !$eventId) {
    http_response_code(400);
    respond(['error' => 'missing params']);
  }
  $res = operateLike($mysqli, 'user_ratings_events', 'liked_events', $userId, $eventId, $action === 'likeEvent');
  respond($res);
}

// RSVP: use the event_ratings table to record attendance (rating may be NULL)
if ($action === 'rsvp' || $action === 'unrsvp') {
  $eventId = $_POST['eventId'] ?? null;
  if (!$userId || !$eventId) {
    http_response_code(400);
    respond(['error' => 'missing params']);
  }

  if ($action === 'rsvp') {
    // Insert or update a row for this user/event preserving any existing rating
    $stmt = $mysqli->prepare('SELECT rating FROM event_ratings WHERE event_id = ? AND user_id = ? LIMIT 1');
    $stmt->bind_param('ss', $eventId, $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $existing = $res->fetch_assoc();
    $stmt->close();

    if ($existing) {
      // already exists - nothing to do
      respond(['success' => true, 'rsvp' => true]);
    }

    $ins = $mysqli->prepare('INSERT INTO event_ratings (event_id,user_id,rating) VALUES (?,?,NULL)');
    if ($ins === false) respond(['error' => 'db prepare failed']);
    $ins->bind_param('ss', $eventId, $userId);
    $ok = $ins->execute();
    $ins->close();
    respond(['success' => (bool)$ok, 'rsvp' => (bool)$ok]);
  } else {
    // unrsvp -> remove any event_ratings row for this user/event
    $del = $mysqli->prepare('DELETE FROM event_ratings WHERE event_id = ? AND user_id = ?');
    if ($del === false) respond(['error' => 'db prepare failed']);
    $del->bind_param('ss', $eventId, $userId);
    $ok = $del->execute();
    $del->close();
    respond(['success' => (bool)$ok, 'rsvp' => false]);
  }
}

// Unrate an event (remove rating but keep RSVP if present)
if ($action === 'unrateEvent') {
  $eventId = $_POST['eventId'] ?? null;
  if (!$userId || !$eventId) {
    http_response_code(400);
    respond(['error' => 'missing params']);
  }

  // Only clear rating (set to NULL) if a row exists
  $stmt = $mysqli->prepare('SELECT rating FROM event_ratings WHERE event_id = ? AND user_id = ? LIMIT 1');
  if ($stmt === false) respond(['error' => 'db prepare failed']);
  $stmt->bind_param('ss', $eventId, $userId);
  $stmt->execute();
  $res = $stmt->get_result();
  $existing = $res->fetch_assoc();
  $stmt->close();

  if ($existing) {
    $up = $mysqli->prepare('UPDATE event_ratings SET rating = NULL WHERE event_id = ? AND user_id = ?');
    if ($up === false) respond(['error' => 'db prepare failed']);
    $up->bind_param('ss', $eventId, $userId);
    $ok = $up->execute();
    $up->close();
  } else {
    // nothing to unrate
    respond(['success' => true, 'avg' => null, 'count' => 0]);
  }

  // Return updated aggregate for this event
  $agg = $mysqli->prepare('SELECT AVG(rating) as avg_rating, COUNT(rating) as count_rating FROM event_ratings WHERE event_id = ? AND rating IS NOT NULL');
  if ($agg === false) respond(['error' => 'db prepare failed']);
  $agg->bind_param('s', $eventId);
  $agg->execute();
  $r = $agg->get_result()->fetch_assoc();
  $agg->close();
  $avg = $r['avg_rating'] !== null ? floatval($r['avg_rating']) : null;
  $count = intval($r['count_rating']);
  respond(['success' => true, 'avg' => $avg, 'count' => $count]);
}

// Rate an event (1-5)
if ($action === 'rateEvent') {
  $eventId = $_POST['eventId'] ?? null;
  $rating = isset($_POST['rating']) ? intval($_POST['rating']) : null;
  if (!$userId || !$eventId || $rating === null) {
    http_response_code(400);
    respond(['error' => 'missing params']);
  }
  if ($rating < 1 || $rating > 5) {
    http_response_code(400);
    respond(['error' => 'invalid rating']);
  }

  // Upsert rating in event_ratings
  $stmt = $mysqli->prepare('SELECT rating FROM event_ratings WHERE event_id = ? AND user_id = ? LIMIT 1');
  if ($stmt === false) respond(['error' => 'db prepare failed']);
  $stmt->bind_param('ss', $eventId, $userId);
  $stmt->execute();
  $res = $stmt->get_result();
  $existing = $res->fetch_assoc();
  $stmt->close();

  if ($existing) {
    $up = $mysqli->prepare('UPDATE event_ratings SET rating = ? WHERE event_id = ? AND user_id = ?');
    if ($up === false) respond(['error' => 'db prepare failed']);
    $up->bind_param('iss', $rating, $eventId, $userId);
    $ok = $up->execute();
    $up->close();
  } else {
    $ins = $mysqli->prepare('INSERT INTO event_ratings (event_id,user_id,rating) VALUES (?,?,?)');
    if ($ins === false) respond(['error' => 'db prepare failed']);
    $ins->bind_param('ssi', $eventId, $userId, $rating);
    $ok = $ins->execute();
    $ins->close();
  }

  // Return updated aggregate for this event
  $agg = $mysqli->prepare('SELECT AVG(rating) as avg_rating, COUNT(rating) as count_rating FROM event_ratings WHERE event_id = ? AND rating IS NOT NULL');
  if ($agg === false) respond(['error' => 'db prepare failed']);
  $agg->bind_param('s', $eventId);
  $agg->execute();
  $r = $agg->get_result()->fetch_assoc();
  $agg->close();
  $avg = $r['avg_rating'] !== null ? floatval($r['avg_rating']) : null;
  $count = intval($r['count_rating']);
  respond(['success' => true, 'avg' => $avg, 'count' => $count]);
}

// Return user's likes across the three tables
if ($action === 'getUserLikes') {
  if (!$userId) {
    http_response_code(400);
    respond(['error' => 'missing userId']);
  }
  $out = ['likedCollections' => [], 'likedItems' => [], 'likedEvents' => []];
  $r1 = $mysqli->prepare('SELECT liked_collections FROM user_ratings_collections WHERE user_id = ? LIMIT 1');
  if ($r1) {
    $r1->bind_param('s', $userId);
    $r1->execute();
    $res = $r1->get_result();
    if ($row = $res->fetch_assoc()) {
      $dec = json_decode($row['liked_collections'] ?? '[]', true);
      if (is_array($dec)) $out['likedCollections'] = $dec;
    }
    $r1->close();
  }
  $r2 = $mysqli->prepare('SELECT liked_items FROM user_ratings_items WHERE user_id = ? LIMIT 1');
  if ($r2) {
    $r2->bind_param('s', $userId);
    $r2->execute();
    $res = $r2->get_result();
    if ($row = $res->fetch_assoc()) {
      $dec = json_decode($row['liked_items'] ?? '[]', true);
      if (is_array($dec)) $out['likedItems'] = $dec;
    }
    $r2->close();
  }
  $r3 = $mysqli->prepare('SELECT liked_events FROM user_ratings_events WHERE user_id = ? LIMIT 1');
  if ($r3) {
    $r3->bind_param('s', $userId);
    $r3->execute();
    $res = $r3->get_result();
    if ($row = $res->fetch_assoc()) {
      $dec = json_decode($row['liked_events'] ?? '[]', true);
      if (is_array($dec)) $out['likedEvents'] = $dec;
    }
    $r3->close();
  }
  respond(['success' => true, 'likes' => $out]);
}

http_response_code(400);
respond(['error' => 'unknown action']);

$mysqli->close();

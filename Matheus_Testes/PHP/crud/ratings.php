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

function tableExists($mysqli, $table)
{
  if (!$mysqli || !$table) return false;
  $tableEsc = $mysqli->real_escape_string($table);
  $sql = "SHOW TABLES LIKE '{$tableEsc}'";
  $res = $mysqli->query($sql);
  return $res && $res->num_rows > 0;
}

/**
 * Aggregate all liked IDs for a user from a table where each row stores a single liked value
 * (or legacy JSON arrays). Returns a de-duplicated array of strings.
 */
function fetchUserLikes($mysqli, $table, $field, $userId)
{
  $all = [];
  $sel = $mysqli->prepare("SELECT {$field} FROM {$table} WHERE user_id = ?");
  if (!$sel) return [];
  $sel->bind_param('s', $userId);
  $sel->execute();
  $res = $sel->get_result();
  while ($row = $res->fetch_assoc()) {
    $raw = $row[$field] ?? null;
    if (!$raw) continue;
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
      foreach ($decoded as $val) {
        if ($val !== null && $val !== '') $all[] = $val;
      }
    } else {
      $all[] = $raw;
    }
  }
  $sel->close();
  return array_values(array_unique($all));
}

/**
 * Fetch likes from dedicated per-row table (returns null if table missing).
 */
function fetchUserLikesAlt($mysqli, $table, $field, $userId)
{
  if (!tableExists($mysqli, $table)) return null;
  $likes = [];
  $sel = $mysqli->prepare("SELECT {$field} FROM {$table} WHERE user_id = ?");
  if (!$sel) return null;
  $sel->bind_param('s', $userId);
  $sel->execute();
  $res = $sel->get_result();
  while ($row = $res->fetch_assoc()) {
    $val = $row[$field] ?? null;
    if ($val !== null && $val !== '') $likes[] = $val;
  }
  $sel->close();
  return array_values(array_unique($likes));
}

/**
 * Try to infer storage mode: multi-row or JSON-per-user.
 */
function detectLikesMode($mysqli, $table, $field, $userId)
{
  $sel = $mysqli->prepare("SELECT {$field} FROM {$table} WHERE user_id = ?");
  if (!$sel) return 'json';
  $sel->bind_param('s', $userId);
  $sel->execute();
  $res = $sel->get_result();
  $rows = $res->fetch_all(MYSQLI_ASSOC);
  $sel->close();

  $count = count($rows);
  if ($count > 1) return 'multi';
  if ($count === 1) {
    $val = $rows[0][$field] ?? '';
    $trim = trim((string)$val);
    if (strlen($trim) && $trim[0] === '[') return 'json';
    return 'json';
  }
  return 'json';
}

/**
 * Replace all rows for this user with one row per liked target.
 */
function persistUserLikes($mysqli, $table, $field, $userId, $likes)
{
  $likes = array_values(array_unique(array_filter($likes, function ($v) {
    return $v !== null && $v !== '';
  })));
  // Remove any existing rows for the user (including legacy JSON)
  $del = $mysqli->prepare("DELETE FROM {$table} WHERE user_id = ?");
  if ($del === false) return false;
  $del->bind_param('s', $userId);
  $del->execute();
  $del->close();

  if (empty($likes)) return true;

  $now = date('Y-m-d H:i:s');
  $ins = $mysqli->prepare("INSERT INTO {$table} (user_id,last_updated,{$field}) VALUES (?,?,?)");
  if ($ins === false) return false;
  foreach ($likes as $like) {
    $ins->bind_param('sss', $userId, $now, $like);
    $ok = $ins->execute();
    if (!$ok) {
      $ins->close();
      return false;
    }
  }
  $ins->close();
  return true;
}

/**
 * Persist likes into a dedicated per-row table (if it exists).
 */
function persistUserLikesAlt($mysqli, $table, $field, $userId, $likes)
{
  if (!tableExists($mysqli, $table)) return false;
  $likes = array_values(array_unique(array_filter($likes, function ($v) {
    return $v !== null && $v !== '';
  })));

  $del = $mysqli->prepare("DELETE FROM {$table} WHERE user_id = ?");
  if ($del === false) return false;
  $del->bind_param('s', $userId);
  $del->execute();
  $del->close();

  if (empty($likes)) return true;

  $now = date('Y-m-d H:i:s');
  $ins = $mysqli->prepare("INSERT INTO {$table} (user_id, {$field}, last_updated) VALUES (?, ?, ?)");
  if ($ins === false) return false;
  foreach ($likes as $like) {
    $ins->bind_param('sss', $userId, $like, $now);
    if (!$ins->execute()) {
      $ins->close();
      return false;
    }
  }
  $ins->close();
  return true;
}

/**
 * Persist likes in a single row (legacy JSON array).
 */
function persistUserLikesJson($mysqli, $table, $field, $userId, $likes)
{
  $likes = array_values(array_unique(array_filter($likes, function ($v) {
    return $v !== null && $v !== '';
  })));
  $json = !empty($likes) ? json_encode($likes, JSON_UNESCAPED_UNICODE) : null;
  $now = date('Y-m-d H:i:s');

  // check if row exists
  $sel = $mysqli->prepare("SELECT 1 FROM {$table} WHERE user_id = ? LIMIT 1");
  if (!$sel) return false;
  $sel->bind_param('s', $userId);
  $sel->execute();
  $exists = $sel->get_result()->fetch_assoc();
  $sel->close();

  if ($exists) {
    $up = $mysqli->prepare("UPDATE {$table} SET {$field} = ?, last_updated = ? WHERE user_id = ?");
    if ($up === false) return false;
    $up->bind_param('sss', $json, $now, $userId);
    $ok = $up->execute();
    $up->close();
    return (bool)$ok;
  }

  $ins = $mysqli->prepare("INSERT INTO {$table} (user_id,last_updated,{$field}) VALUES (?,?,?)");
  if ($ins === false) return false;
  $ins->bind_param('sss', $userId, $now, $json);
  $ok = $ins->execute();
  $ins->close();
  return (bool)$ok;
}

/**
 * Insert/delete one liked target per row (legacy JSON row is still supported by reading it).
 */
function operateLike($mysqli, $table, $field, $userId, $targetId, $add, $altTable = null, $altField = null)
{
  if (!$userId || !$targetId) return ['error' => 'missing params'];

  // Choose active table/field: prefer alt if available
  $useAlt = $altTable && $altField && tableExists($mysqli, $altTable);
  $activeTable = $useAlt ? $altTable : $table;
  $activeField = $useAlt ? $altField : $field;

  // Get all likes (including legacy list if using base table)
  $likes = $useAlt
    ? fetchUserLikesAlt($mysqli, $activeTable, $activeField, $userId)
    : fetchUserLikes($mysqli, $activeTable, $activeField, $userId);

  if ($add) {
    if (!in_array($targetId, $likes, true)) $likes[] = $targetId;
  } else {
    $likes = array_values(array_filter($likes, function ($v) use ($targetId) {
      return $v !== $targetId;
    }));
  }

  $ok = $useAlt
    ? persistUserLikesAlt($mysqli, $activeTable, $activeField, $userId, $likes)
    : (detectLikesMode($mysqli, $activeTable, $activeField, $userId) === 'multi'
        ? persistUserLikes($mysqli, $activeTable, $activeField, $userId, $likes)
        : persistUserLikesJson($mysqli, $activeTable, $activeField, $userId, $likes));
  return ['success' => (bool)$ok, 'likes' => $likes];
}

if ($action === 'likeCollection' || $action === 'unlikeCollection') {
  $collectionId = $_POST['collectionId'] ?? null;
  if (!$userId || !$collectionId) {
    http_response_code(400);
    respond(['error' => 'missing params']);
  }
  $res = operateLike(
    $mysqli,
    'user_ratings_collections',
    'liked_collections',
    $userId,
    $collectionId,
    $action === 'likeCollection',
    'user_liked_collections',
    'liked_collection_id'
  );
  respond($res);
}

if ($action === 'likeItem' || $action === 'unlikeItem') {
  $itemId = $_POST['itemId'] ?? null;
  if (!$userId || !$itemId) {
    http_response_code(400);
    respond(['error' => 'missing params']);
  }
  $res = operateLike(
    $mysqli,
    'user_ratings_items',
    'liked_items',
    $userId,
    $itemId,
    $action === 'likeItem',
    'user_liked_items',
    'liked_item_id'
  );
  respond($res);
}

if ($action === 'likeEvent' || $action === 'unlikeEvent') {
  $eventId = $_POST['eventId'] ?? null;
  if (!$userId || !$eventId) {
    http_response_code(400);
    respond(['error' => 'missing params']);
  }
  $res = operateLike(
    $mysqli,
    'user_ratings_events',
    'liked_events',
    $userId,
    $eventId,
    $action === 'likeEvent',
    'user_liked_events',
    'liked_event_id'
  );
  respond($res);
}

// RSVP: use the event_ratings table to record attendance (rating may be NULL)
if ($action === 'rsvp' || $action === 'unrsvp') {
  $eventId = $_POST['eventId'] ?? null;
  if (!$userId || !$eventId) {
    http_response_code(400);
    respond(['error' => 'missing params']);
  }

  $ratingsTable = tableExists($mysqli, 'user_event_ratings') ? 'user_event_ratings' : 'event_ratings';

  if ($action === 'rsvp') {
    // Insert or update a row for this user/event preserving any existing rating
    $stmt = $mysqli->prepare("SELECT rating FROM {$ratingsTable} WHERE event_id = ? AND user_id = ? LIMIT 1");
    $stmt->bind_param('ss', $eventId, $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $existing = $res->fetch_assoc();
    $stmt->close();

    if ($existing) {
      // already exists - nothing to do
      respond(['success' => true, 'rsvp' => true]);
    }

    $ins = $mysqli->prepare("INSERT INTO {$ratingsTable} (event_id,user_id,rating) VALUES (?,?,NULL)");
    if ($ins === false) respond(['error' => 'db prepare failed']);
    $ins->bind_param('ss', $eventId, $userId);
    $ok = $ins->execute();
    $ins->close();
    respond(['success' => (bool)$ok, 'rsvp' => (bool)$ok]);
  } else {
    // unrsvp -> remove any event_ratings row for this user/event
    $del = $mysqli->prepare("DELETE FROM {$ratingsTable} WHERE event_id = ? AND user_id = ?");
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

  $ratingsTable = tableExists($mysqli, 'user_event_ratings') ? 'user_event_ratings' : 'event_ratings';

  // Only clear rating (set to NULL) if a row exists
  $stmt = $mysqli->prepare("SELECT rating FROM {$ratingsTable} WHERE event_id = ? AND user_id = ? LIMIT 1");
  if ($stmt === false) respond(['error' => 'db prepare failed']);
  $stmt->bind_param('ss', $eventId, $userId);
  $stmt->execute();
  $res = $stmt->get_result();
  $existing = $res->fetch_assoc();
  $stmt->close();

  if ($existing) {
    $up = $mysqli->prepare("UPDATE {$ratingsTable} SET rating = NULL WHERE event_id = ? AND user_id = ?");
    if ($up === false) respond(['error' => 'db prepare failed']);
    $up->bind_param('ss', $eventId, $userId);
    $ok = $up->execute();
    $up->close();
  } else {
    // nothing to unrate
    respond(['success' => true, 'avg' => null, 'count' => 0]);
  }

  // Return updated aggregate for this event
  $agg = $mysqli->prepare("SELECT AVG(rating) as avg_rating, COUNT(rating) as count_rating FROM {$ratingsTable} WHERE event_id = ? AND rating IS NOT NULL");
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

  // Choose table (prefer dedicated per-row table if exists)
  $ratingsTable = tableExists($mysqli, 'user_event_ratings') ? 'user_event_ratings' : 'event_ratings';

  // Upsert rating
  $stmt = $mysqli->prepare("SELECT rating FROM {$ratingsTable} WHERE event_id = ? AND user_id = ? LIMIT 1");
  if ($stmt === false) respond(['error' => 'db prepare failed']);
  $stmt->bind_param('ss', $eventId, $userId);
  $stmt->execute();
  $res = $stmt->get_result();
  $existing = $res->fetch_assoc();
  $stmt->close();

  if ($existing) {
    $up = $mysqli->prepare("UPDATE {$ratingsTable} SET rating = ? WHERE event_id = ? AND user_id = ?");
    if ($up === false) respond(['error' => 'db prepare failed']);
    $up->bind_param('iss', $rating, $eventId, $userId);
    $ok = $up->execute();
    $up->close();
  } else {
    $ins = $mysqli->prepare("INSERT INTO {$ratingsTable} (event_id,user_id,rating) VALUES (?,?,?)");
    if ($ins === false) respond(['error' => 'db prepare failed']);
    $ins->bind_param('ssi', $eventId, $userId, $rating);
    $ok = $ins->execute();
    $ins->close();
  }

  // Return updated aggregate for this event
  $agg = $mysqli->prepare("SELECT AVG(rating) as avg_rating, COUNT(rating) as count_rating FROM {$ratingsTable} WHERE event_id = ? AND rating IS NOT NULL");
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
  $out = [
    'likedCollections' => (fetchUserLikesAlt($mysqli, 'user_liked_collections', 'liked_collection_id', $userId) ?? fetchUserLikes($mysqli, 'user_ratings_collections', 'liked_collections', $userId)),
    'likedItems' => (fetchUserLikesAlt($mysqli, 'user_liked_items', 'liked_item_id', $userId) ?? fetchUserLikes($mysqli, 'user_ratings_items', 'liked_items', $userId)),
    'likedEvents' => (fetchUserLikesAlt($mysqli, 'user_liked_events', 'liked_event_id', $userId) ?? fetchUserLikes($mysqli, 'user_ratings_events', 'liked_events', $userId))
  ];
  respond(['success' => true, 'likes' => $out]);
}

http_response_code(400);
respond(['error' => 'unknown action']);

$mysqli->close();

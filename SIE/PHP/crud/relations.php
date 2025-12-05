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

if ($action === 'linkItem') {
  $itemId = $_POST['itemId'] ?? null;
  $collectionId = $_POST['collectionId'] ?? null;
  if (!$itemId || !$collectionId) {
    http_response_code(400);
    echo json_encode(['error' => 'missing params']);
    exit;
  }
  $stmt = $mysqli->prepare('INSERT IGNORE INTO collection_items (collection_id,item_id) VALUES (?,?)');
  $stmt->bind_param('ss', $collectionId, $itemId);
  $ok = $stmt->execute();
  $stmt->close();
  echo json_encode(['success' => $ok]);
  exit;
}

if ($action === 'unlinkItem') {
  $itemId = $_POST['itemId'] ?? null;
  $collectionId = $_POST['collectionId'] ?? null;
  if (!$itemId || !$collectionId) {
    http_response_code(400);
    echo json_encode(['error' => 'missing params']);
    exit;
  }
  $stmt = $mysqli->prepare('DELETE FROM collection_items WHERE collection_id = ? AND item_id = ?');
  $stmt->bind_param('ss', $collectionId, $itemId);
  $ok = $stmt->execute();
  $stmt->close();
  echo json_encode(['success' => $ok]);
  exit;
}

if ($action === 'linkEvent') {
  $eventId = $_POST['eventId'] ?? null;
  $collectionId = $_POST['collectionId'] ?? null;
  if (!$eventId || !$collectionId) {
    http_response_code(400);
    echo json_encode(['error' => 'missing params']);
    exit;
  }
  $stmt = $mysqli->prepare('INSERT IGNORE INTO collection_events (collection_id,event_id) VALUES (?,?)');
  $stmt->bind_param('ss', $collectionId, $eventId);
  $ok = $stmt->execute();
  $stmt->close();
  echo json_encode(['success' => $ok]);
  exit;
}

if ($action === 'unlinkEvent') {
  $eventId = $_POST['eventId'] ?? null;
  $collectionId = $_POST['collectionId'] ?? null;
  if (!$eventId || !$collectionId) {
    http_response_code(400);
    echo json_encode(['error' => 'missing params']);
    exit;
  }
  $stmt = $mysqli->prepare('DELETE FROM collection_events WHERE collection_id = ? AND event_id = ?');
  $stmt->bind_param('ss', $collectionId, $eventId);
  $ok = $stmt->execute();
  $stmt->close();
  echo json_encode(['success' => $ok]);
  exit;
}

if ($action === 'setEventCollections') {
  $eventId = $_POST['eventId'] ?? null;
  $collections = $_POST['collectionIds'] ?? null; // expected JSON array or comma-separated
  if (!$eventId) {
    http_response_code(400);
    echo json_encode(['error' => 'missing eventId']);
    exit;
  }
  // normalize collection ids
  if (!$collections) $ids = [];
  else {
    // try JSON
    $decoded = json_decode($collections, true);
    if (is_array($decoded)) $ids = $decoded;
    else $ids = array_filter(array_map('trim', explode(',', $collections)));
  }
  // delete existing
  $del = $mysqli->prepare('DELETE FROM collection_events WHERE event_id = ?');
  $del->bind_param('s', $eventId);
  $del->execute();
  $del->close();
  // insert new links
  $ins = $mysqli->prepare('INSERT INTO collection_events (collection_id,event_id) VALUES (?,?)');
  foreach ($ids as $cid) {
    $ins->bind_param('ss', $cid, $eventId);
    $ins->execute();
  }
  $ins->close();
  echo json_encode(['success' => true]);
  exit;
}

http_response_code(400);
echo json_encode(['error' => 'unknown action']);

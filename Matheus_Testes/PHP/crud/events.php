<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $mysqli->prepare('SELECT event_id,name,localization,event_date,type,summary,description,created_at,updated_at,host_user_id,collection_id FROM events WHERE event_id = ? LIMIT 1');
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    if ($row) {
      echo json_encode(['id' => $row['event_id'], 'name' => $row['name'], 'localization' => $row['localization'], 'date' => $row['event_date'], 'type' => $row['type'], 'summary' => $row['summary'], 'description' => $row['description'], 'createdAt' => $row['created_at'], 'updatedAt' => $row['updated_at'], 'hostUserId' => $row['host_user_id'], 'collectionId' => $row['collection_id']]);
      exit;
    }
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    exit;
  }

  $res = $mysqli->query('SELECT event_id,name,localization,event_date,type,summary,description,created_at,updated_at,host_user_id,collection_id FROM events ORDER BY event_date DESC');
  $out = [];
  while ($row = $res->fetch_assoc()) {
    $out[] = ['id' => $row['event_id'], 'name' => $row['name'], 'localization' => $row['localization'], 'date' => $row['event_date'], 'type' => $row['type'], 'summary' => $row['summary'], 'description' => $row['description'], 'createdAt' => $row['created_at'], 'updatedAt' => $row['updated_at'], 'hostUserId' => $row['host_user_id'], 'collectionId' => $row['collection_id']];
  }
  echo json_encode($out);
  exit;
}

if ($method === 'POST') {
  $action = $_POST['action'] ?? null;
  // Require authenticated user for mutating actions
  $currentUser = $_SESSION['user']['id'] ?? null;

  if ($action === 'create') {
    if (!$currentUser) {
      http_response_code(401);
      echo json_encode(['error' => 'Not authenticated']);
      exit;
    }
    $id = $_POST['id'] ?? uniqid('event-');
    $name = $_POST['name'] ?? '';
    $loc = $_POST['localization'] ?? null;
    $date = $_POST['date'] ?? null;
    $type = $_POST['type'] ?? null;
    $summary = $_POST['summary'] ?? null;
    $description = $_POST['description'] ?? null;
    // assign host to session user if not explicitly provided
    $host = $_POST['host_user_id'] ?? $currentUser;
    $collection = $_POST['collection_id'] ?? null;
    $stmt = $mysqli->prepare('INSERT INTO events (event_id,name,localization,event_date,type,summary,description,created_at,updated_at,host_user_id,collection_id) VALUES (?,?,?,?,?,?,?,NOW(),NOW(),?,?)');
    $stmt->bind_param('sssssssss', $id, $name, $loc, $date, $type, $summary, $description, $host, $collection);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok, 'id' => $id]);
    exit;
  } elseif ($action === 'update') {
    if (!$currentUser) {
      http_response_code(401);
      echo json_encode(['error' => 'Not authenticated']);
      exit;
    }
    $id = $_POST['id'] ?? null;
    if (!$id) {
      http_response_code(400);
      echo json_encode(['error' => 'missing id']);
      exit;
    }
    // Check ownership: only host_user_id may update
    $check = $mysqli->prepare('SELECT host_user_id FROM events WHERE event_id = ? LIMIT 1');
    $check->bind_param('s', $id);
    $check->execute();
    $cres = $check->get_result();
    $crow = $cres->fetch_assoc();
    $check->close();
    $owner = $crow['host_user_id'] ?? null;
    if ($owner && $owner !== $currentUser) {
      http_response_code(403);
      echo json_encode(['error' => 'Forbidden']);
      exit;
    }

    $name = $_POST['name'] ?? null;
    $loc = $_POST['localization'] ?? null;
    $date = $_POST['date'] ?? null;
    $type = $_POST['type'] ?? null;
    $summary = $_POST['summary'] ?? null;
    $description = $_POST['description'] ?? null;
    // keep host as existing or session user
    $host = $_POST['host_user_id'] ?? $currentUser;
    $collection = $_POST['collection_id'] ?? null;
    $stmt = $mysqli->prepare('UPDATE events SET name=?, localization=?, event_date=?, type=?, summary=?, description=?, host_user_id=?, collection_id=?, updated_at=NOW() WHERE event_id=?');
    $stmt->bind_param('sssssssss', $name, $loc, $date, $type, $summary, $description, $host, $collection, $id);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok]);
    exit;
  } elseif ($action === 'delete') {
    if (!$currentUser) {
      http_response_code(401);
      echo json_encode(['error' => 'Not authenticated']);
      exit;
    }
    $id = $_POST['id'] ?? null;
    if (!$id) {
      http_response_code(400);
      echo json_encode(['error' => 'missing id']);
      exit;
    }
    // Check ownership before delete
    $check = $mysqli->prepare('SELECT host_user_id FROM events WHERE event_id = ? LIMIT 1');
    $check->bind_param('s', $id);
    $check->execute();
    $cres = $check->get_result();
    $crow = $cres->fetch_assoc();
    $check->close();
    $owner = $crow['host_user_id'] ?? null;
    if ($owner && $owner !== $currentUser) {
      http_response_code(403);
      echo json_encode(['error' => 'Forbidden']);
      exit;
    }
    $stmt = $mysqli->prepare('DELETE FROM events WHERE event_id = ?');
    $stmt->bind_param('s', $id);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok]);
    exit;
  }
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

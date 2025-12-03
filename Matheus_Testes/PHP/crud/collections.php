<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  // GET all or single by id
  if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $mysqli->prepare('SELECT collection_id,name,type,cover_image,summary,description,created_at,user_id FROM collections WHERE collection_id = ? LIMIT 1');
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    if ($row) {
      echo json_encode([
        'id' => $row['collection_id'],
        'name' => $row['name'],
        'type' => $row['type'],
        'coverImage' => $row['cover_image'],
        'summary' => $row['summary'],
        'description' => $row['description'],
        'createdAt' => $row['created_at'],
        'ownerId' => $row['user_id']
      ]);
      exit;
    }
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    exit;
  }

  $res = $mysqli->query('SELECT collection_id,name,type,cover_image,summary,description,created_at,user_id FROM collections ORDER BY created_at DESC');
  $out = [];
  while ($row = $res->fetch_assoc()) {
    $out[] = ['id' => $row['collection_id'], 'name' => $row['name'], 'type' => $row['type'], 'coverImage' => $row['cover_image'], 'summary' => $row['summary'], 'description' => $row['description'], 'createdAt' => $row['created_at'], 'ownerId' => $row['user_id']];
  }
  echo json_encode($out);
  exit;
}

if ($method === 'POST') {
  // Minimal create/update/delete via action param
  $action = isset($_POST['action']) ? $_POST['action'] : null;
  if ($action === 'create') {
    $currentUser = $_SESSION['user']['id'] ?? null;
    if (!$currentUser) {
      http_response_code(401);
      echo json_encode(['error' => 'Not authenticated']);
      exit;
    }
    $id = isset($_POST['id']) ? $_POST['id'] : uniqid('col-');
    $name = $_POST['name'] ?? '';
    $summary = $_POST['summary'] ?? '';
    $description = $_POST['description'] ?? '';
    $image = $_POST['image'] ?? '';
    $type = $_POST['type'] ?? '';
    $owner = $currentUser;
    $stmt = $mysqli->prepare('INSERT INTO collections (collection_id,name,type,cover_image,summary,description,created_at,user_id) VALUES (?,?,?,?,?,?,NOW(),?)');
    $stmt->bind_param('sssssss', $id, $name, $type, $image, $summary, $description, $owner);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok, 'id' => $id]);
    exit;
  } elseif ($action === 'update') {
    $currentUser = $_SESSION['user']['id'] ?? null;
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
    // Ownership check
    $check = $mysqli->prepare('SELECT user_id FROM collections WHERE collection_id = ? LIMIT 1');
    $check->bind_param('s', $id);
    $check->execute();
    $cres = $check->get_result();
    $crow = $cres->fetch_assoc();
    $check->close();
    if (!$crow || ($crow['user_id'] ?? null) !== $currentUser) {
      http_response_code(403);
      echo json_encode(['error' => 'Forbidden']);
      exit;
    }
    $name = $_POST['name'] ?? null;
    $summary = $_POST['summary'] ?? null;
    $description = $_POST['description'] ?? null;
    $image = $_POST['image'] ?? null;
    $type = $_POST['type'] ?? null;
    $stmt = $mysqli->prepare('UPDATE collections SET name=?, type=?, cover_image=?, summary=?, description=? WHERE collection_id=?');
    $stmt->bind_param('ssssss', $name, $type, $image, $summary, $description, $id);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok]);
    exit;
  } elseif ($action === 'delete') {
    $currentUser = $_SESSION['user']['id'] ?? null;
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
    // Ownership check
    $check = $mysqli->prepare('SELECT user_id FROM collections WHERE collection_id = ? LIMIT 1');
    $check->bind_param('s', $id);
    $check->execute();
    $cres = $check->get_result();
    $crow = $cres->fetch_assoc();
    $check->close();
    if (!$crow || ($crow['user_id'] ?? null) !== $currentUser) {
      http_response_code(403);
      echo json_encode(['error' => 'Forbidden']);
      exit;
    }
    $stmt = $mysqli->prepare('DELETE FROM collections WHERE collection_id = ?');
    $stmt->bind_param('s', $id);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok]);
    exit;
  }
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

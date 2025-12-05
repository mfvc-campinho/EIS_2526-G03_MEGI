<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

function normalize_collection_ids($raw) {
  if (is_array($raw)) {
    $ids = $raw;
  } else {
    $decoded = json_decode($raw ?? '', true);
    if (is_array($decoded)) $ids = $decoded;
    else $ids = array_filter(array_map('trim', explode(',', $raw ?? '')));
  }
  $ids = array_values(array_unique(array_filter($ids)));
  return $ids;
}

function collections_owned_by_user($mysqli, $ids, $userId) {
  if (!$ids || !$userId) return false;
  foreach ($ids as $cid) {
    $chk = $mysqli->prepare('SELECT user_id FROM collections WHERE collection_id = ? LIMIT 1');
    $chk->bind_param('s', $cid);
    $chk->execute();
    $res = $chk->get_result();
    $row = $res->fetch_assoc();
    $chk->close();
    if (!$row || ($row['user_id'] ?? null) !== $userId) {
      return false;
    }
  }
  return true;
}

if ($method === 'GET') {
  if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $mysqli->prepare('SELECT item_id,name,importance,weight,price,acquisition_date,created_at,updated_at,image,collection_id FROM items WHERE item_id = ? LIMIT 1');
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    if ($row) {
      echo json_encode(['id' => $row['item_id'], 'name' => $row['name'], 'importance' => $row['importance'], 'weight' => $row['weight'], 'price' => $row['price'], 'acquisitionDate' => $row['acquisition_date'], 'createdAt' => $row['created_at'], 'updatedAt' => $row['updated_at'], 'image' => $row['image'], 'collectionId' => $row['collection_id']]);
      exit;
    }
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    exit;
  }

  $res = $mysqli->query('SELECT item_id,name,importance,weight,price,acquisition_date,created_at,updated_at,image,collection_id FROM items ORDER BY created_at DESC');
  $out = [];
  while ($row = $res->fetch_assoc()) {
    $out[] = ['id' => $row['item_id'], 'name' => $row['name'], 'importance' => $row['importance'], 'weight' => $row['weight'], 'price' => $row['price'], 'acquisitionDate' => $row['acquisition_date'], 'createdAt' => $row['created_at'], 'updatedAt' => $row['updated_at'], 'image' => $row['image'], 'collectionId' => $row['collection_id']];
  }
  echo json_encode($out);
  exit;
}

if ($method === 'POST') {
  $action = $_POST['action'] ?? null;
  if ($action === 'create') {
    $currentUser = $_SESSION['user']['id'] ?? null;
    if (!$currentUser) {
      http_response_code(401);
      echo json_encode(['error' => 'Not authenticated']);
      exit;
    }
    $collectionIds = normalize_collection_ids($_POST['collection_ids'] ?? $_POST['collection_id'] ?? '');
    if (!$collectionIds) {
      http_response_code(400);
      echo json_encode(['error' => 'missing collection_id']);
      exit;
    }
    if (!collections_owned_by_user($mysqli, $collectionIds, $currentUser)) {
      http_response_code(403);
      echo json_encode(['error' => 'Forbidden']);
      exit;
    }
    $id = $_POST['id'] ?? uniqid('item-');
    $name = $_POST['name'] ?? '';
    $importance = $_POST['importance'] ?? null;
    $weight = $_POST['weight'] ?? null;
    $price = $_POST['price'] ?? null;
    // send empty string when not provided so SQL can convert '' -> NULL
    $acq = $_POST['acquisition_date'] ?? '';
    $image = $_POST['image'] ?? null;
    $collection = $collectionIds[0];
    // Use NULLIF for acquisition_date so empty string becomes NULL (no '0000-00-00')
    $stmt = $mysqli->prepare('INSERT INTO items (item_id,name,importance,weight,price,acquisition_date,created_at,updated_at,image,collection_id) VALUES (?,?,?,?,?,NULLIF(?, \'\'),NOW(),NOW(),?,?)');
    // types: id(s), name(s), importance(s), weight(d), price(d), acquisition_date(s), image(s), collection_id(s)
    $stmt->bind_param('sssddsss', $id, $name, $importance, $weight, $price, $acq, $image, $collection);
    $ok = $stmt->execute();
    $stmt->close();
    // Sync collection_items links
    $del = $mysqli->prepare('DELETE FROM collection_items WHERE item_id = ?');
    $del->bind_param('s', $id);
    $del->execute();
    $del->close();
    $ins = $mysqli->prepare('INSERT IGNORE INTO collection_items (collection_id,item_id) VALUES (?,?)');
    foreach ($collectionIds as $cid) {
      $ins->bind_param('ss', $cid, $id);
      $ins->execute();
    }
    $ins->close();
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
    // Verify item exists and belongs to a collection owned by user
    $check = $mysqli->prepare('SELECT i.collection_id, c.user_id FROM items i JOIN collections c ON i.collection_id = c.collection_id WHERE i.item_id = ? LIMIT 1');
    $check->bind_param('s', $id);
    $check->execute();
    $ires = $check->get_result();
    $irow = $ires->fetch_assoc();
    $check->close();
    if (!$irow || ($irow['user_id'] ?? null) !== $currentUser) {
      http_response_code(403);
      echo json_encode(['error' => 'Forbidden']);
      exit;
    }
    $collectionIds = normalize_collection_ids($_POST['collection_ids'] ?? $_POST['collection_id'] ?? '');
    if (!$collectionIds) {
      $collectionIds = [$irow['collection_id']];
    }
    if (!collections_owned_by_user($mysqli, $collectionIds, $currentUser)) {
      http_response_code(403);
      echo json_encode(['error' => 'Forbidden']);
      exit;
    }
    $name = $_POST['name'] ?? null;
    $importance = $_POST['importance'] ?? null;
    $weight = $_POST['weight'] ?? null;
    $price = $_POST['price'] ?? null;
    // send empty string when not provided so SQL can convert '' -> NULL
    $acq = $_POST['acquisition_date'] ?? '';
    $image = $_POST['image'] ?? null;
    $collection = $collectionIds[0];
    // Use NULLIF so empty acquisition_date values are saved as NULL instead of '0000-00-00'
    $stmt = $mysqli->prepare('UPDATE items SET name=?, importance=?, weight=?, price=?, acquisition_date=NULLIF(?, \'\'), image=?, collection_id=? WHERE item_id=?');
    // types: name(s), importance(s), weight(d), price(d), acquisition_date(s), image(s), collection_id(s), id(s)
    $stmt->bind_param('ssddssss', $name, $importance, $weight, $price, $acq, $image, $collection, $id);
    $ok = $stmt->execute();
    $stmt->close();
    // Sync collection_items links
    $del = $mysqli->prepare('DELETE FROM collection_items WHERE item_id = ?');
    $del->bind_param('s', $id);
    $del->execute();
    $del->close();
    $ins = $mysqli->prepare('INSERT IGNORE INTO collection_items (collection_id,item_id) VALUES (?,?)');
    foreach ($collectionIds as $cid) {
      $ins->bind_param('ss', $cid, $id);
      $ins->execute();
    }
    $ins->close();
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
    // Ownership check via collection
    $check = $mysqli->prepare('SELECT c.user_id FROM items i JOIN collections c ON i.collection_id = c.collection_id WHERE i.item_id = ? LIMIT 1');
    $check->bind_param('s', $id);
    $check->execute();
    $ires = $check->get_result();
    $irow = $ires->fetch_assoc();
    $check->close();
    if (!$irow || ($irow['user_id'] ?? null) !== $currentUser) {
      http_response_code(403);
      echo json_encode(['error' => 'Forbidden']);
      exit;
    }
    $stmt = $mysqli->prepare('DELETE FROM items WHERE item_id = ?');
    $stmt->bind_param('s', $id);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok]);
    exit;
  }
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

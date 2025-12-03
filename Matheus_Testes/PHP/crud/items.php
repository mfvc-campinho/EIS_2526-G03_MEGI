<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

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
    $id = $_POST['id'] ?? uniqid('item-');
    $name = $_POST['name'] ?? '';
    $importance = $_POST['importance'] ?? null;
    $weight = $_POST['weight'] ?? null;
    $price = $_POST['price'] ?? null;
    // send empty string when not provided so SQL can convert '' -> NULL
    $acq = $_POST['acquisition_date'] ?? '';
    $image = $_POST['image'] ?? null;
    $collection = $_POST['collection_id'] ?? null;
    // Ensure collection belongs to current user
    if ($collection) {
      $chk = $mysqli->prepare('SELECT user_id FROM collections WHERE collection_id = ? LIMIT 1');
      $chk->bind_param('s', $collection);
      $chk->execute();
      $cRes = $chk->get_result();
      $cRow = $cRes->fetch_assoc();
      $chk->close();
      if (!$cRow || ($cRow['user_id'] ?? null) !== $currentUser) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
      }
    } else {
      http_response_code(400);
      echo json_encode(['error' => 'missing collection_id']);
      exit;
    }
    // Use NULLIF for acquisition_date so empty string becomes NULL (no '0000-00-00')
    $stmt = $mysqli->prepare('INSERT INTO items (item_id,name,importance,weight,price,acquisition_date,created_at,updated_at,image,collection_id) VALUES (?,?,?,?,?,NULLIF(?, \'\'),NOW(),NOW(),?,?)');
    // types: id(s), name(s), importance(s), weight(d), price(d), acquisition_date(s), image(s), collection_id(s)
    $stmt->bind_param('sssddsss', $id, $name, $importance, $weight, $price, $acq, $image, $collection);
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
    $name = $_POST['name'] ?? null;
    $importance = $_POST['importance'] ?? null;
    $weight = $_POST['weight'] ?? null;
    $price = $_POST['price'] ?? null;
    // send empty string when not provided so SQL can convert '' -> NULL
    $acq = $_POST['acquisition_date'] ?? '';
    $image = $_POST['image'] ?? null;
    $collection = $_POST['collection_id'] ?? null;
    // If collection is being changed, ensure new collection also belongs to the user
    if ($collection && $collection !== $irow['collection_id']) {
      $chk = $mysqli->prepare('SELECT user_id FROM collections WHERE collection_id = ? LIMIT 1');
      $chk->bind_param('s', $collection);
      $chk->execute();
      $cres = $chk->get_result();
      $crow = $cres->fetch_assoc();
      $chk->close();
      if (!$crow || ($crow['user_id'] ?? null) !== $currentUser) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
      }
    } else {
      $collection = $irow['collection_id'];
    }
    // Use NULLIF so empty acquisition_date values are saved as NULL instead of '0000-00-00'
    $stmt = $mysqli->prepare('UPDATE items SET name=?, importance=?, weight=?, price=?, acquisition_date=NULLIF(?, \'\'), image=?, collection_id=? WHERE item_id=?');
    // types: name(s), importance(s), weight(d), price(d), acquisition_date(s), image(s), collection_id(s), id(s)
    $stmt->bind_param('ssddssss', $name, $importance, $weight, $price, $acq, $image, $collection, $id);
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

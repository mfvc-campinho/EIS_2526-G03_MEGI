<?php
session_start();
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../config/db.php';

$action      = $_POST['action'] ?? null;
$currentUser = $_SESSION['user']['id'] ?? null;

if (!$currentUser) {
  flash_set('error', 'Precisa de iniciar sessão.');
  header('Location: home_page.php');
  exit;
}

// helper: confirmar se o user é dono de uma coleção
function user_owns_collection($mysqli, $collectionId, $userId) {
  $chk = $mysqli->prepare('SELECT user_id FROM collections WHERE collection_id = ? LIMIT 1');
  $chk->bind_param('s', $collectionId);
  $chk->execute();
  $res = $chk->get_result();
  $row = $res->fetch_assoc();
  $chk->close();

  return $row && ($row['user_id'] ?? null) === $userId;
}

// helper: guardar links item–coleções (apaga antigos e grava novos)
function replace_item_links($mysqli, $itemId, array $collectionIds) {
  // apagar links antigos
  $del = $mysqli->prepare('DELETE FROM collection_items WHERE item_id = ?');
  $del->bind_param('s', $itemId);
  $del->execute();
  $del->close();

  if (!$collectionIds) {
    return;
  }

  // inserir novos links
  $ins = $mysqli->prepare('INSERT INTO collection_items (collection_id,item_id) VALUES (?,?)');
  foreach ($collectionIds as $cid) {
    $cid = (string) $cid;
    $ins->bind_param('ss', $cid, $itemId);
    $ins->execute();
  }
  $ins->close();
}

// helper: confirmar se o user tem acesso ao item através de alguma coleção sua
function user_has_item_access($mysqli, $itemId, $userId) {
  $stmt = $mysqli->prepare('SELECT 1 FROM collection_items ci INNER JOIN collections c ON c.collection_id = ci.collection_id WHERE ci.item_id = ? AND c.user_id = ? LIMIT 1');
  if ($stmt === false) {
    return false;
  }
  $stmt->bind_param('ss', $itemId, $userId);
  $stmt->execute();
  $res = $stmt->get_result();
  $has = $res && $res->num_rows > 0;
  $stmt->close();
  return $has;
}

// normalizar collectionIds vindos do formulário
$collectionIds = $_POST['collectionIds'] ?? [];
$collectionIds = is_array($collectionIds) ? array_values(array_unique(array_filter($collectionIds))) : [];

if ($action === 'create') {
  if (!$collectionIds) {
    $mysqli->close();
    redirect_error('Selecione pelo menos uma coleção.');
  }

  // todas as coleções escolhidas têm de pertencer ao utilizador
  foreach ($collectionIds as $cid) {
    if (!user_owns_collection($mysqli, $cid, $currentUser)) {
      $mysqli->close();
      redirect_error('You can only create items in your own collections.');
    }
  }

  $id          = uniqid('item-');
  $name        = $_POST['name']            ?? '';
  $importance  = $_POST['importance']      ?? '';
  $weight      = $_POST['weight']          ?? '';
  $price       = $_POST['price']           ?? '';
  $acq         = $_POST['acquisitionDate'] ?? null;
  $image       = handle_upload('imageFile', 'items', '');

  // INSERT no items
  $stmt = $mysqli->prepare(
     'INSERT INTO items (item_id,name,importance,weight,price,acquisition_date,created_at,updated_at,image) 
      VALUES (?,?,?,?,?,?,NOW(),NOW(),?)'
  );
    $stmt->bind_param('sssssss', $id, $name, $importance, $weight, $price, $acq, $image);
  $ok = $stmt->execute();
  $stmt->close();

  // links item–coleções
  replace_item_links($mysqli, $id, $collectionIds);

  $mysqli->close();
  if ($ok) {
    redirect_success('Item created successfully.', 'item_page.php?id=' . urlencode($id));
  }
  redirect_error('Failed to create item.');
}

if ($action === 'update') {
  $id = $_POST['id'] ?? null;
  if (!$id) {
    $mysqli->close();
    redirect_error('ID missing.');
  }
  if (!$collectionIds) {
    $mysqli->close();
    redirect_error('Choose at least one collection.');
  }

  // buscar item atual (para verificar owner e imagem)
  $chkItem = $mysqli->prepare('SELECT image FROM items WHERE item_id = ? LIMIT 1');
  $chkItem->bind_param('s', $id);
  $chkItem->execute();
  $resItem  = $chkItem->get_result();
  $existing = $resItem->fetch_assoc();
  $chkItem->close();

  if (!$existing) {
    $mysqli->close();
    redirect_error('Item not found.');
  }

  if (!user_has_item_access($mysqli, $id, $currentUser)) {
    $mysqli->close();
    redirect_error('You do not have permission to update this item.');
  }

  // todas as coleções selecionadas têm de ser do utilizador
  foreach ($collectionIds as $cid) {
    if (!user_owns_collection($mysqli, $cid, $currentUser)) {
      $mysqli->close();
      redirect_error('You can only associate this item with your own collections.');
    }
  }

  $name       = $_POST['name']            ?? '';
  $importance = $_POST['importance']      ?? '';
  $weight     = $_POST['weight']          ?? '';
  $price      = $_POST['price']           ?? '';
  $acq        = $_POST['acquisitionDate'] ?? null;
  $image      = handle_upload('imageFile', 'items', $existing['image'] ?? '');

  // UPDATE do item
  $stmt = $mysqli->prepare(
    'UPDATE items 
        SET name = ?, 
            importance = ?, 
            weight = ?, 
            price = ?, 
            acquisition_date = ?, 
            image = ?, 
            updated_at = NOW() 
      WHERE item_id = ?'
  );
  $stmt->bind_param('sssssss', $name, $importance, $weight, $price, $acq, $image, $id);
  $ok = $stmt->execute();
  $stmt->close();

  // atualizar links item–coleções
  replace_item_links($mysqli, $id, $collectionIds);

  $mysqli->close();
  if ($ok) {
    redirect_success('Item updated.', 'item_page.php?id=' . urlencode($id));
  }
  redirect_error('Failed to update item.');
}

if ($action === 'delete') {
  $id = $_POST['id'] ?? null;
  if (!$id) {
    $mysqli->close();
    redirect_error('ID missing.');
  }

  $returnTo = sanitize_redirect_target($_POST['return_to'] ?? '');

  if (!user_has_item_access($mysqli, $id, $currentUser)) {
    $mysqli->close();
    redirect_error('You do not have permission to delete this item.');
  }

  // apagar o item
  $stmt = $mysqli->prepare('DELETE FROM items WHERE item_id = ?');
  $stmt->bind_param('s', $id);
  $ok = $stmt->execute();
  $stmt->close();

  // apagar links item–coleções
  replace_item_links($mysqli, $id, []);

  $mysqli->close();
  if ($ok) {
    redirect_success('Item deleted.', $returnTo);
  }
  redirect_error('Failed to delete item.');
}

// se chegou aqui, ação inválida
$mysqli->close();
redirect_error('Invalid action.');

// -----------------------------------------------------------------
// helpers adicionais
// -----------------------------------------------------------------

function sanitize_redirect_target($value) {
  $value = trim((string) $value);
  if ($value === '') {
    return 'home_page.php';
  }

  if (preg_match('#^https?://#i', $value)) {
    $parsed = parse_url($value);
    if (!$parsed) {
      return 'home_page.php';
    }
    $currentHost = $_SERVER['HTTP_HOST'] ?? '';
    if (!empty($parsed['host']) && $currentHost && strcasecmp($parsed['host'], $currentHost) !== 0) {
      return 'home_page.php';
    }
    $path = $parsed['path'] ?? '';
    if ($path === '' || strpos($path, '..') !== false) {
      return 'home_page.php';
    }
    $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
    $value = $path . $query;
  }

  if ($value === '' || strpos($value, '..') !== false) {
    return 'home_page.php';
  }

  return $value;
}

function redirect_success($msg, $location = 'home_page.php') {
  flash_set('success', $msg);
  header('Location: ' . $location);
  exit;
}

function redirect_error($msg) {
  flash_set('error', $msg);
  header('Location: home_page.php');
  exit;
}

function handle_upload($field, $folder, $keep = '') {
  if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
    return $keep;
  }

  if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
    return $keep;
  }

  $tmpName = $_FILES[$field]['tmp_name'];
  $name    = basename($_FILES[$field]['name']);

  $safeName = uniqid($folder . '-') . '-' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $name);
  $destRel  = 'uploads/' . $folder . '/' . $safeName;
  $destAbs  = __DIR__ . '/../../' . $destRel;

  @mkdir(dirname($destAbs), 0777, true);

  if (!move_uploaded_file($tmpName, $destAbs)) {
    return $keep;
  }

  return $destRel;
}

// helper para buscar coleções de um item (se precisares noutro lado)
function fetch_item_collections($mysqli, $itemId) {
  $out  = [];
  $stmt = $mysqli->prepare('SELECT collection_id FROM collection_items WHERE item_id = ?');
  $stmt->bind_param('s', $itemId);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) {
    $out[] = $row['collection_id'];
  }
  $stmt->close();
  return $out;
}

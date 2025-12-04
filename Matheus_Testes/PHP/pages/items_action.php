<?php
session_start();
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../config/db.php';

$action = $_POST['action'] ?? null;
$currentUser = $_SESSION['user']['id'] ?? null;
if (!$currentUser) {
  flash_set('error', 'Precisa de iniciar sessão.');
  header('Location: home_page.php');
  exit;
}

// helper to verify ownership via collection
function user_owns_collection($mysqli, $collectionId, $userId) {
  $chk = $mysqli->prepare('SELECT user_id FROM collections WHERE collection_id = ? LIMIT 1');
  $chk->bind_param('s', $collectionId);
  $chk->execute();
  $res = $chk->get_result();
  $row = $res->fetch_assoc();
  $chk->close();
  return $row && ($row['user_id'] ?? null) === $userId;
}

function redirect_success($msg)
{
  flash_set('success', $msg);
  header('Location: home_page.php');
  exit;
}

function redirect_error($msg)
{
  flash_set('error', $msg);
  header('Location: home_page.php');
  exit;
}

function handle_upload($field, $folder, $keep = '')
{
  if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    return $keep;
  }
  $file = $_FILES[$field];
  if ($file['error'] !== UPLOAD_ERR_OK) {
    redirect_error('Falha no upload da imagem.');
  }
  $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
    redirect_error('Formato de imagem inválido.');
  }
  $dir = __DIR__ . '/../uploads/' . $folder;
  if (!is_dir($dir)) {
    @mkdir($dir, 0777, true);
  }
  $filename = uniqid('img_') . '.' . $ext;
  $target = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
  if (!move_uploaded_file($file['tmp_name'], $target)) {
    redirect_error('Não foi possível guardar a imagem.');
  }
  return 'uploads/' . $folder . '/' . $filename;
}

if ($action === 'create') {
  $collectionId = $_POST['collectionId'] ?? null;
  if (!$collectionId || !user_owns_collection($mysqli, $collectionId, $currentUser)) {
    $mysqli->close();
    redirect_error('Só pode criar itens nas suas coleções.');
  }
  $id = uniqid('item-');
  $name = $_POST['name'] ?? '';
  $importance = $_POST['importance'] ?? '';
  $weight = $_POST['weight'] ?? '';
  $price = $_POST['price'] ?? '';
  $acq = $_POST['acquisitionDate'] ?? null;
  $image = handle_upload('imageFile', 'items', '');

  $stmt = $mysqli->prepare('INSERT INTO items (item_id,name,importance,weight,price,acquisition_date,created_at,collection_id,image) VALUES (?,?,?,?,?,?,NOW(),?,?)');
  $stmt->bind_param('ssssssss', $id, $name, $importance, $weight, $price, $acq, $collectionId, $image);
  $ok = $stmt->execute();
  $stmt->close();
  $mysqli->close();
  if ($ok) redirect_success('Item criado.');
  redirect_error('Falha ao criar item.');
}

if ($action === 'update') {
  $id = $_POST['id'] ?? null;
  if (!$id) redirect_error('ID em falta.');

  // fetch existing item
  $chkItem = $mysqli->prepare('SELECT collection_id,image FROM items WHERE item_id = ? LIMIT 1');
  $chkItem->bind_param('s', $id);
  $chkItem->execute();
  $resItem = $chkItem->get_result();
  $existing = $resItem->fetch_assoc();
  $chkItem->close();
  if (!$existing) {
    $mysqli->close();
    redirect_error('Item não encontrado.');
  }

  $collectionId = $_POST['collectionId'] ?? $existing['collection_id'];
  if (!$collectionId || !user_owns_collection($mysqli, $collectionId, $currentUser)) {
    $mysqli->close();
    redirect_error('Sem permissão para editar este item.');
  }

  $name = $_POST['name'] ?? '';
  $importance = $_POST['importance'] ?? '';
  $weight = $_POST['weight'] ?? '';
  $price = $_POST['price'] ?? '';
  $acq = $_POST['acquisitionDate'] ?? null;
  $image = handle_upload('imageFile', 'items', $existing['image'] ?? '');

  $stmt = $mysqli->prepare('UPDATE items SET name=?, importance=?, weight=?, price=?, acquisition_date=?, collection_id=?, image=? WHERE item_id=?');
  $stmt->bind_param('ssssssss', $name, $importance, $weight, $price, $acq, $collectionId, $image, $id);
  $ok = $stmt->execute();
  $stmt->close();
  $mysqli->close();
  if ($ok) redirect_success('Item atualizado.');
  redirect_error('Falha ao atualizar item.');
}

if ($action === 'delete') {
  $id = $_POST['id'] ?? null;
  if (!$id) redirect_error('ID em falta.');

  // find item's collection and verify ownership
  $chk = $mysqli->prepare('SELECT collection_id FROM items WHERE item_id = ? LIMIT 1');
  $chk->bind_param('s', $id);
  $chk->execute();
  $res = $chk->get_result();
  $row = $res->fetch_assoc();
  $chk->close();
  $collectionId = $row['collection_id'] ?? null;
  if (!$collectionId || !user_owns_collection($mysqli, $collectionId, $currentUser)) {
    $mysqli->close();
    redirect_error('Sem permissão para apagar este item.');
  }

  $stmt = $mysqli->prepare('DELETE FROM items WHERE item_id = ?');
  $stmt->bind_param('s', $id);
  $ok = $stmt->execute();
  $stmt->close();
  $mysqli->close();
  if ($ok) redirect_success('Item apagado.');
  redirect_error('Falha ao apagar item.');
}

$mysqli->close();
redirect_error('Ação inválida.');



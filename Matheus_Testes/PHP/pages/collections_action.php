<?php
session_start();
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../config/db.php';

$action = $_POST['action'] ?? null;
$currentUser = $_SESSION['user']['id'] ?? null;
if (!$currentUser) {
  flash_set('error', 'Precisa de iniciar sessão.');
  header('Location: all_collections.php');
  exit;
}

function redirect_success($msg)
{
  flash_set('success', $msg);
  header('Location: all_collections.php');
  exit;
}

function redirect_error($msg)
{
  flash_set('error', $msg);
  header('Location: all_collections.php');
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
  $dir = dirname(__DIR__, 2) . '/uploads/' . $folder;
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
  $id = uniqid('col-');
  $name = $_POST['name'] ?? '';
  $type = $_POST['type'] ?? '';
  $summary = $_POST['summary'] ?? '';
  $description = $_POST['description'] ?? '';
  $cover = handle_upload('coverImageFile', 'collections', '');
  $stmt = $mysqli->prepare('INSERT INTO collections (collection_id,name,type,cover_image,summary,description,created_at,user_id) VALUES (?,?,?,?,?,?,NOW(),?)');
  $stmt->bind_param('sssssss', $id, $name, $type, $cover, $summary, $description, $currentUser);
  $ok = $stmt->execute();
  $stmt->close();
  $mysqli->close();
  if ($ok) redirect_success('Coleção criada.');
  redirect_error('Falha ao criar coleção.');
}

if ($action === 'update') {
  $id = $_POST['id'] ?? null;
  if (!$id) redirect_error('ID em falta.');

  // ownership check + current cover
  $chk = $mysqli->prepare('SELECT user_id, cover_image FROM collections WHERE collection_id = ? LIMIT 1');
  $chk->bind_param('s', $id);
  $chk->execute();
  $res = $chk->get_result();
  $row = $res->fetch_assoc();
  $chk->close();
  if (!$row || ($row['user_id'] ?? null) !== $currentUser) {
    $mysqli->close();
    redirect_error('Sem permissão para editar esta coleção.');
  }

  $name = $_POST['name'] ?? '';
  $type = $_POST['type'] ?? '';
  $summary = $_POST['summary'] ?? '';
  $description = $_POST['description'] ?? '';
  $existingCover = $row['cover_image'] ?? '';
  $cover = handle_upload('coverImageFile', 'collections', $existingCover);
  $stmt = $mysqli->prepare('UPDATE collections SET name=?, type=?, cover_image=?, summary=?, description=? WHERE collection_id=?');
  $stmt->bind_param('ssssss', $name, $type, $cover, $summary, $description, $id);
  $ok = $stmt->execute();
  $stmt->close();
  $mysqli->close();
  if ($ok) redirect_success('Coleção atualizada.');
  redirect_error('Falha ao atualizar coleção.');
}

if ($action === 'delete') {
  $id = $_POST['id'] ?? null;
  if (!$id) redirect_error('ID em falta.');

  // ownership check
  $chk = $mysqli->prepare('SELECT user_id FROM collections WHERE collection_id = ? LIMIT 1');
  $chk->bind_param('s', $id);
  $chk->execute();
  $res = $chk->get_result();
  $row = $res->fetch_assoc();
  $chk->close();
  if (!$row || ($row['user_id'] ?? null) !== $currentUser) {
    $mysqli->close();
    redirect_error('Sem permissão para apagar esta coleção.');
  }

  $stmt = $mysqli->prepare('DELETE FROM collections WHERE collection_id = ?');
  $stmt->bind_param('s', $id);
  $ok = $stmt->execute();
  $stmt->close();
  $mysqli->close();
  if ($ok) redirect_success('Coleção apagada.');
  redirect_error('Falha ao apagar coleção.');
}

$mysqli->close();
redirect_error('Ação inválida.');


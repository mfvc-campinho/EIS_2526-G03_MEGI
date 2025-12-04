<?php
session_start();
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../config/db.php';

$action = $_POST['action'] ?? null;
$currentUser = $_SESSION['user']['id'] ?? null;
if (!$currentUser) {
  flash_set('error', 'Precisa de iniciar sessÃ£o.');
  header('Location: event_page.php');
  exit;
}

function redirect_success($msg)
{
  flash_set('success', $msg);
  header('Location: event_page.php');
  exit;
}

function redirect_error($msg)
{
  flash_set('error', $msg);
  header('Location: event_page.php');
  exit;
}

if ($action === 'create') {
  $id = uniqid('evt-');
  $name = $_POST['name'] ?? '';
  $summary = $_POST['summary'] ?? '';
  $description = $_POST['description'] ?? '';
  $type = $_POST['type'] ?? '';
  $localization = $_POST['localization'] ?? '';
  $date = $_POST['date'] ?? null;
  $collectionId = $_POST['collectionId'] ?? null;

  $stmt = $mysqli->prepare('INSERT INTO events (event_id,name,localization,event_date,type,summary,description,created_at,host_user_id,collection_id) VALUES (?,?,?,?,?,?,?,NOW(),?,?)');
  $stmt->bind_param('sssssssss', $id, $name, $localization, $date, $type, $summary, $description, $currentUser, $collectionId);
  $ok = $stmt->execute();
  $stmt->close();
  $mysqli->close();
  if ($ok) redirect_success('Evento criado.');
  redirect_error('Falha ao criar evento.');
}

if ($action === 'update') {
  $id = $_POST['id'] ?? null;
  if (!$id) redirect_error('ID em falta.');

  // ownership check
  $chk = $mysqli->prepare('SELECT host_user_id FROM events WHERE event_id = ? LIMIT 1');
  $chk->bind_param('s', $id);
  $chk->execute();
  $res = $chk->get_result();
  $row = $res->fetch_assoc();
  $chk->close();
  if (!$row || ($row['host_user_id'] ?? null) !== $currentUser) {
    $mysqli->close();
    redirect_error('Sem permissÃ£o para editar este evento.');
  }

  $name = $_POST['name'] ?? '';
  $summary = $_POST['summary'] ?? '';
  $description = $_POST['description'] ?? '';
  $type = $_POST['type'] ?? '';
  $localization = $_POST['localization'] ?? '';
  $date = $_POST['date'] ?? null;
  $collectionId = $_POST['collectionId'] ?? null;

  $stmt = $mysqli->prepare('UPDATE events SET name=?, localization=?, event_date=?, type=?, summary=?, description=?, collection_id=? WHERE event_id=?');
  $stmt->bind_param('ssssssss', $name, $localization, $date, $type, $summary, $description, $collectionId, $id);
  $ok = $stmt->execute();
  $stmt->close();
  $mysqli->close();
  if ($ok) redirect_success('Evento atualizado.');
  redirect_error('Falha ao atualizar evento.');
}

if ($action === 'delete') {
  $id = $_POST['id'] ?? null;
  if (!$id) redirect_error('ID em falta.');

  // ownership check
  $chk = $mysqli->prepare('SELECT host_user_id FROM events WHERE event_id = ? LIMIT 1');
  $chk->bind_param('s', $id);
  $chk->execute();
  $res = $chk->get_result();
  $row = $res->fetch_assoc();
  $chk->close();
  if (!$row || ($row['host_user_id'] ?? null) !== $currentUser) {
    $mysqli->close();
    redirect_error('Sem permissÃ£o para apagar este evento.');
  }

  $stmt = $mysqli->prepare('DELETE FROM events WHERE event_id = ?');
  $stmt->bind_param('s', $id);
  $ok = $stmt->execute();
  $stmt->close();
  $mysqli->close();
  if ($ok) redirect_success('Evento apagado.');
  redirect_error('Falha ao apagar evento.');
}

$mysqli->close();
redirect_error('AÃ§Ã£o invÃ¡lida.');




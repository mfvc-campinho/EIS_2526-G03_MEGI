<?php
session_start();
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../config/db.php';

$action = $_POST['action'] ?? null;
$currentUser = $_SESSION['user']['id'] ?? null;
if (!$currentUser) {
  flash_set('error', 'Precisa de iniciar sessão.');
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

// Normalize posted collection ids
$collectionIds = $_POST['collectionIds'] ?? [];
$collectionIds = is_array($collectionIds) ? array_values(array_unique(array_filter($collectionIds))) : [];

// ownership check: collections must belong to user
function user_owns_collection($mysqli, $collectionId, $userId) {
  $chk = $mysqli->prepare('SELECT user_id FROM collections WHERE collection_id = ? LIMIT 1');
  $chk->bind_param('s', $collectionId);
  $chk->execute();
  $res = $chk->get_result();
  $row = $res->fetch_assoc();
  $chk->close();
  return $row && ($row['user_id'] ?? null) === $userId;
}

function replace_event_links($mysqli, $eventId, $collectionIds)
{
  $del = $mysqli->prepare('DELETE FROM collection_events WHERE event_id = ?');
  $del->bind_param('s', $eventId);
  $del->execute();
  $del->close();

  $ins = $mysqli->prepare('INSERT INTO collection_events (collection_id,event_id) VALUES (?,?)');
  foreach ($collectionIds as $cid) {
    $ins->bind_param('ss', $cid, $eventId);
    $ins->execute();
  }
  $ins->close();
}

if ($action === 'create') {
  if (!$collectionIds) redirect_error('Selecione pelo menos uma coleção.');
  foreach ($collectionIds as $cid) {
    if (!user_owns_collection($mysqli, $cid, $currentUser)) {
      $mysqli->close();
      redirect_error('Só pode associar eventos às suas coleções.');
    }
  }

  $id = uniqid('evt-');
  $name = $_POST['name'] ?? '';
  $summary = $_POST['summary'] ?? '';
  $description = $_POST['description'] ?? '';
  $type = $_POST['type'] ?? '';
  $localization = $_POST['localization'] ?? '';
  $date = $_POST['date'] ?? null;
  $primaryCol = $collectionIds[0];

  $stmt = $mysqli->prepare('INSERT INTO events (event_id,name,localization,event_date,type,summary,description,created_at,host_user_id,collection_id) VALUES (?,?,?,?,?,?,?,NOW(),?,?)');
  $stmt->bind_param('sssssssss', $id, $name, $localization, $date, $type, $summary, $description, $currentUser, $primaryCol);
  $ok = $stmt->execute();
  $stmt->close();

  replace_event_links($mysqli, $id, $collectionIds);
  $mysqli->close();
  if ($ok) redirect_success('Evento criado.');
  redirect_error('Falha ao criar evento.');
}

if ($action === 'update') {
  $id = $_POST['id'] ?? null;
  if (!$id) redirect_error('ID em falta.');
  if (!$collectionIds) redirect_error('Selecione pelo menos uma coleção.');

  // ownership check
  foreach ($collectionIds as $cid) {
    if (!user_owns_collection($mysqli, $cid, $currentUser)) {
      $mysqli->close();
      redirect_error('Sem permissão para editar este evento.');
    }
  }

  $name = $_POST['name'] ?? '';
  $summary = $_POST['summary'] ?? '';
  $description = $_POST['description'] ?? '';
  $type = $_POST['type'] ?? '';
  $localization = $_POST['localization'] ?? '';
  $date = $_POST['date'] ?? null;
  $primaryCol = $collectionIds[0];

  $stmt = $mysqli->prepare('UPDATE events SET name=?, localization=?, event_date=?, type=?, summary=?, description=?, collection_id=? WHERE event_id=?');
  $stmt->bind_param('ssssssss', $name, $localization, $date, $type, $summary, $description, $primaryCol, $id);
  $ok = $stmt->execute();
  $stmt->close();

  replace_event_links($mysqli, $id, $collectionIds);
  $mysqli->close();
  if ($ok) redirect_success('Evento atualizado.');
  redirect_error('Falha ao atualizar evento.');
}

if ($action === 'delete') {
  $id = $_POST['id'] ?? null;
  if (!$id) redirect_error('ID em falta.');

  // ownership check via event host
  $chk = $mysqli->prepare('SELECT host_user_id FROM events WHERE event_id = ? LIMIT 1');
  $chk->bind_param('s', $id);
  $chk->execute();
  $res = $chk->get_result();
  $row = $res->fetch_assoc();
  $chk->close();
  if (!$row || ($row['host_user_id'] ?? null) !== $currentUser) {
    $mysqli->close();
    redirect_error('Sem permissão para apagar este evento.');
  }

  $stmt = $mysqli->prepare('DELETE FROM events WHERE event_id = ?');
  $stmt->bind_param('s', $id);
  $ok = $stmt->execute();
  $stmt->close();
  replace_event_links($mysqli, $id, []);
  $mysqli->close();
  if ($ok) redirect_success('Evento apagado.');
  redirect_error('Falha ao apagar evento.');
}

$mysqli->close();
redirect_error('Ação inválida.');


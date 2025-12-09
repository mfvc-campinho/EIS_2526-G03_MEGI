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

  if ($collectionIds) {
    $ins = $mysqli->prepare('INSERT INTO collection_events (collection_id,event_id) VALUES (?,?)');
    foreach ($collectionIds as $cid) {
      $ins->bind_param('ss', $cid, $eventId);
      $ins->execute();
    }
    $ins->close();
  }
}

if ($action === 'create') {
  if (!$collectionIds) redirect_error('Selecione pelo menos uma coleção.');
  foreach ($collectionIds as $cid) {
    if (!user_owns_collection($mysqli, $cid, $currentUser)) {
      $mysqli->close();
      redirect_error('You can only associate events to your own collections.');
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
  redirect_error('Failed to create event.');
}

if ($action === 'update') {
  $id = $_POST['id'] ?? null;
  if (!$id) redirect_error('ID em falta.');
  if (!$collectionIds) redirect_error('Selecione pelo menos uma coleção.');

  // ownership check
  foreach ($collectionIds as $cid) {
    if (!user_owns_collection($mysqli, $cid, $currentUser)) {
      $mysqli->close();
      redirect_error('You do not have permission to edit this event.');
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
    redirect_error('You do not have permission to delete this event.');
  }

  $stmt = $mysqli->prepare('DELETE FROM events WHERE event_id = ?');
  $stmt->bind_param('s', $id);
  $ok = $stmt->execute();
  $stmt->close();
  replace_event_links($mysqli, $id, []);
  $mysqli->close();
  if ($ok) redirect_success('Evento apagado.');
  redirect_error('Failed to delete event.');
}

// Fetch helper
$fetchEvent = function ($mysqli, $eventId) {
  $stmt = $mysqli->prepare('SELECT event_id, host_user_id, collection_id FROM events WHERE event_id = ? LIMIT 1');
  $stmt->bind_param('s', $eventId);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();
  $stmt->close();
  return $row;
};

if ($action === 'rsvp') {
  $id = $_POST['id'] ?? null;
  if (!$id) redirect_error('ID em falta.');
  $row = $fetchEvent($mysqli, $id);
  if (!$row) {
    $mysqli->close();
    redirect_error('Evento não encontrado.');
  }
  $stmt = $mysqli->prepare('REPLACE INTO event_rsvps (event_id,user_id) VALUES (?,?)');
  $stmt->bind_param('ss', $id, $currentUser);
  $ok = $stmt->execute();
  $stmt->close();
  $mysqli->close();
  if ($ok) redirect_success('RSVP registado.');
  redirect_error('Falha ao registar RSVP.');
}

if ($action === 'rate') {
  $id = $_POST['id'] ?? null;
  $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : null;
  if (!$id || !$rating || $rating < 1 || $rating > 5) redirect_error('Dados de rating inválidos.');
  $row = $fetchEvent($mysqli, $id);
  if (!$row) {
    $mysqli->close();
    redirect_error('Evento não encontrado.');
  }
  $collectionId = $row['collection_id'] ?? null;
  $stmt = $mysqli->prepare('REPLACE INTO event_ratings (event_id,user_id,rating,collection_id) VALUES (?,?,?,?)');
  $stmt->bind_param('ssis', $id, $currentUser, $rating, $collectionId);
  $ok = $stmt->execute();
  $stmt->close();
  $mysqli->close();
  if ($ok) redirect_success('Rating registado.');
  redirect_error('Falha ao registar rating.');
}

$mysqli->close();
redirect_error('Ação inválida.');


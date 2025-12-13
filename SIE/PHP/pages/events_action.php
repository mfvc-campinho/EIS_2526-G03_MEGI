<?php
session_start();
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../config/db.php';

$action = $_POST['action'] ?? null;
$currentUser = $_SESSION['user']['id'] ?? null;
if (!$currentUser) {
  flash_set('error', 'You need to be logged in.');
  header('Location: event_page.php');
  exit;
}

$appTimezone = new DateTimeZone(date_default_timezone_get());
$now = new DateTime('now', $appTimezone);
$today = $now->format('Y-m-d');
$tomorrowDate = (clone $now)->modify('+1 day')->format('Y-m-d');

if (!function_exists('parse_event_datetime_helper')) {
  function parse_event_datetime_helper($raw, DateTimeZone $tz)
  {
    $result = ['date' => null, 'hasTime' => false];
    if (!$raw) return $result;
    $trim = trim((string)$raw);
    if ($trim === '') return $result;

    $formats = [
      ['Y-m-d H:i:s', true],
      ['Y-m-d H:i', true],
      ['Y-m-d\TH:i:s', true],
      ['Y-m-d\TH:i', true],
      [DateTime::ATOM, true],
      ['Y-m-d', false]
    ];

    foreach ($formats as [$format, $hasTime]) {
      $dt = DateTime::createFromFormat($format, $trim, $tz);
      if ($dt instanceof DateTime) {
        return ['date' => $dt, 'hasTime' => $hasTime];
      }
    }

    try {
      $dt = new DateTime($trim, $tz);
      $hasTime = (bool)preg_match('/\d{1,2}:\d{2}/', $trim);
      return ['date' => $dt, 'hasTime' => $hasTime];
    } catch (Exception $e) {
      return $result;
    }
  }
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
  if (!$collectionIds) redirect_error('Choose at least one collection.');
  foreach ($collectionIds as $cid) {
    if (!user_owns_collection($mysqli, $cid, $currentUser)) {
      $mysqli->close();
      redirect_error('You can only associate events to your own collections.');
    }
  }

  $id = uniqid('evt-');
  $name = trim($_POST['name'] ?? '');
  $summary = trim($_POST['summary'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $type = trim($_POST['type'] ?? '');
  $localization = trim($_POST['localization'] ?? '');
  $rawDate = trim($_POST['date'] ?? '');
  if ($localization === '' || $rawDate === '') {
    $mysqli->close();
    redirect_error('Provide event location and date.');
  }
  $parsedInput = parse_event_datetime_helper($rawDate, $appTimezone);
  $dateObj = $parsedInput['date'];
  $inputHasTime = $parsedInput['hasTime'];
  if (!$dateObj) {
    $mysqli->close();
    redirect_error('Invalid event date/time.');
  }
  $isBeforeAllowed = $dateObj->format('Y-m-d') < $tomorrowDate;
  if ($isBeforeAllowed) {
    $mysqli->close();
    redirect_error('Events must be scheduled at least one day in advance.');
  }
  if (!$inputHasTime) {
    $dateObj->setTime(0, 0, 0);
  }
  $dateForSql = $dateObj->format('Y-m-d H:i:s');
  $primaryCol = $collectionIds[0];

  $stmt = $mysqli->prepare('INSERT INTO events (event_id,name,localization,event_date,type,summary,description,created_at,collection_id) VALUES (?,?,?,?,?,?,?,NOW(),?)');
  $stmt->bind_param('ssssssss', $id, $name, $localization, $dateForSql, $type, $summary, $description, $primaryCol);
  $ok = $stmt->execute();
  $stmt->close();

  replace_event_links($mysqli, $id, $collectionIds);
  $mysqli->close();
  if ($ok) redirect_success('Event created.');
  redirect_error('Failed to create event.');
}

if ($action === 'update') {
  $id = $_POST['id'] ?? null;
  if (!$id) redirect_error('ID missing.');
  if (!$collectionIds) redirect_error('Choose at least one collection.');

  $currentEventStmt = $mysqli->prepare('SELECT e.event_date, e.collection_id, c.user_id AS owner_id FROM events e LEFT JOIN collections c ON c.collection_id = e.collection_id WHERE e.event_id = ? LIMIT 1');
  $currentEventStmt->bind_param('s', $id);
  $currentEventStmt->execute();
  $currentEvent = $currentEventStmt->get_result()->fetch_assoc();
  $currentEventStmt->close();
  if (!$currentEvent) {
    $mysqli->close();
    redirect_error('Event not found.');
  }
  if (($currentEvent['owner_id'] ?? null) !== $currentUser) {
    $mysqli->close();
    redirect_error('You do not have permission to edit this event.');
  }
  $parsedEventDate = parse_event_datetime_helper($currentEvent['event_date'] ?? null, $appTimezone);
  $eventDateObj = $parsedEventDate['date'];
  $hasTime = $parsedEventDate['hasTime'];
  if ($eventDateObj) {
    $eventHasEnded = $hasTime
      ? ($eventDateObj <= $now)
      : ($eventDateObj->format('Y-m-d') < $today);
    if ($eventHasEnded) {
      $mysqli->close();
      redirect_error('Events that already happen cannot be altered.');
    }
  }

  // ownership check
  foreach ($collectionIds as $cid) {
    if (!user_owns_collection($mysqli, $cid, $currentUser)) {
      $mysqli->close();
      redirect_error('You do not have permission to edit this event.');
    }
  }

  $name = trim($_POST['name'] ?? '');
  $summary = trim($_POST['summary'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $type = trim($_POST['type'] ?? '');
  $localization = trim($_POST['localization'] ?? '');
  $rawDate = trim($_POST['date'] ?? '');
  if ($localization === '' || $rawDate === '') {
    $mysqli->close();
    redirect_error('Provide event location and date.');
  }
  $parsedInput = parse_event_datetime_helper($rawDate, $appTimezone);
  $dateObj = $parsedInput['date'];
  $inputHasTime = $parsedInput['hasTime'];
  if (!$dateObj) {
    $mysqli->close();
    redirect_error('Invalid event date/time.');
  }
  $isBeforeAllowed = $dateObj->format('Y-m-d') < $tomorrowDate;
  if ($isBeforeAllowed) {
    $mysqli->close();
    redirect_error('Events must be scheduled at least one day in advance.');
  }
  if (!$inputHasTime) {
    $dateObj->setTime(0, 0, 0);
  }
  $dateForSql = $dateObj->format('Y-m-d H:i:s');
  $primaryCol = $collectionIds[0];

  $stmt = $mysqli->prepare('UPDATE events SET name=?, localization=?, event_date=?, type=?, summary=?, description=?, collection_id=? WHERE event_id=?');
  $stmt->bind_param('ssssssss', $name, $localization, $dateForSql, $type, $summary, $description, $primaryCol, $id);
  $ok = $stmt->execute();
  $stmt->close();

  replace_event_links($mysqli, $id, $collectionIds);
  $mysqli->close();
  if ($ok) redirect_success('Event updated.');
  redirect_error('Failed to update event.');
}

if ($action === 'delete') {
  $id = $_POST['id'] ?? null;
  if (!$id) redirect_error('ID missing.');

  // ownership check via event host
  $chk = $mysqli->prepare('SELECT e.event_date, c.user_id AS owner_id FROM events e LEFT JOIN collections c ON c.collection_id = e.collection_id WHERE e.event_id = ? LIMIT 1');
  $chk->bind_param('s', $id);
  $chk->execute();
  $res = $chk->get_result();
  $row = $res->fetch_assoc();
  $chk->close();
  if (!$row || ($row['owner_id'] ?? null) !== $currentUser) {
    $mysqli->close();
    redirect_error('You do not have permission to delete this event.');
  }

  $parsedEventDate = parse_event_datetime_helper($row['event_date'] ?? null, $appTimezone);
  $eventDateObj = $parsedEventDate['date'];
  $hasTime = $parsedEventDate['hasTime'];
  if ($eventDateObj) {
    $eventHasEnded = $hasTime
      ? ($eventDateObj <= $now)
      : ($eventDateObj->format('Y-m-d') < $today);
    if ($eventHasEnded) {
      $mysqli->close();
      redirect_error('Past events cannot be deleted.');
    }
  }

  $stmt = $mysqli->prepare('DELETE FROM events WHERE event_id = ?');
  $stmt->bind_param('s', $id);
  $ok = $stmt->execute();
  $stmt->close();
  replace_event_links($mysqli, $id, []);
  $mysqli->close();
  if ($ok) redirect_success('Event deleted.');
  redirect_error('Failed to delete event.');
}

// Fetch helper
$fetchEvent = function ($mysqli, $eventId) {
  $stmt = $mysqli->prepare('SELECT event_id, collection_id, event_date FROM events WHERE event_id = ? LIMIT 1');
  $stmt->bind_param('s', $eventId);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();
  $stmt->close();
  return $row;
};

if ($action === 'rsvp') {
  $id = $_POST['id'] ?? null;
  if (!$id) redirect_error('ID missing.');
  $row = $fetchEvent($mysqli, $id);
  if (!$row) {
    $mysqli->close();
    redirect_error('Event not found.');
  }
  
  // Check if user already has RSVP
  $chk = $mysqli->prepare('SELECT user_id FROM event_rsvps WHERE event_id = ? AND user_id = ? LIMIT 1');
  $chk->bind_param('ss', $id, $currentUser);
  $chk->execute();
  $hasRsvp = $chk->get_result()->fetch_assoc();
  $chk->close();
  
  if ($hasRsvp) {
    // Remove RSVP
    $stmt = $mysqli->prepare('DELETE FROM event_rsvps WHERE event_id = ? AND user_id = ?');
    $stmt->bind_param('ss', $id, $currentUser);
    $ok = $stmt->execute();
    $stmt->close();
    $mysqli->close();
    if ($ok) {
      flash_set('success', 'RSVP removed.');
      $_SESSION['rsvp_removed'] = true;
      header('Location: event_page.php');
      exit;
    }
    redirect_error('Failed to remove RSVP.');
  } else {
    // Add RSVP
    $stmt = $mysqli->prepare('INSERT INTO event_rsvps (event_id,user_id) VALUES (?,?)');
    $stmt->bind_param('ss', $id, $currentUser);
    $ok = $stmt->execute();
    $stmt->close();
    $mysqli->close();
    if ($ok) {
      flash_set('success', 'RSVP registered.');
      $_SESSION['rsvp_removed'] = false;
      header('Location: event_page.php');
      exit;
    }
    redirect_error('Failed to register RSVP.');
  }
}

if ($action === 'rate') {
  $eventId = $_POST['id'] ?? null;
  $collectionId = $_POST['collection_id'] ?? null;
  $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : null;
  if (!$eventId || !$collectionId || !$rating || $rating < 1 || $rating > 5) {
    $mysqli->close();
    redirect_error('Invalid rating data.');
  }

  $row = $fetchEvent($mysqli, $eventId);
  if (!$row) {
    $mysqli->close();
    redirect_error('Event not found.');
  }

  // Apenas permitir avaliação após o evento
  $parsedEventDate = parse_event_datetime_helper($row['event_date'] ?? null, $appTimezone);
  $eventDateObj = $parsedEventDate['date'];
  $hasTime = $parsedEventDate['hasTime'];
  if (!$eventDateObj) {
    $mysqli->close();
    redirect_error('Invalid event date.');
  }
  $eventHasEnded = $hasTime
    ? ($eventDateObj <= $now)
    : ($eventDateObj->format('Y-m-d') < $today);
  if (!$eventHasEnded) {
    $mysqli->close();
    redirect_error('You can only rate collections after the event has occurred.');
  }

  // Verificar RSVP
  $chkRsvp = $mysqli->prepare('SELECT 1 FROM event_rsvps WHERE event_id = ? AND user_id = ? LIMIT 1');
  $chkRsvp->bind_param('ss', $eventId, $currentUser);
  $chkRsvp->execute();
  $hasRsvp = $chkRsvp->get_result()->fetch_assoc();
  $chkRsvp->close();
  if (!$hasRsvp) {
    $mysqli->close();
    redirect_error('Only participants can rate collections.');
  }

  // Garantir que a coleção pertence ao evento
  $linked = false;
  $linkStmt = $mysqli->prepare('SELECT 1 FROM collection_events WHERE event_id = ? AND collection_id = ? LIMIT 1');
  $linkStmt->bind_param('ss', $eventId, $collectionId);
  $linkStmt->execute();
  if ($linkStmt->get_result()->fetch_assoc()) {
    $linked = true;
  }
  $linkStmt->close();
  if (!$linked && !empty($row['collection_id']) && $row['collection_id'] === $collectionId) {
    $linked = true;
  }
  if (!$linked) {
    $mysqli->close();
    redirect_error('Colection is not associated with this event.');
  }

  $stmt = $mysqli->prepare('REPLACE INTO event_ratings (event_id,user_id,collection_id,rating) VALUES (?,?,?,?)');
  $stmt->bind_param('sssi', $eventId, $currentUser, $collectionId, $rating);
  $ok = $stmt->execute();
  $stmt->close();
  $mysqli->close();
  if ($ok) redirect_success('Rating registered.');
  redirect_error('Failed to register rating.');
}

$mysqli->close();
redirect_error('Invalid action.');


<?php
ob_start();
session_start();
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../config/db.php';

$action = $_POST['action'] ?? null;
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$currentUser = $_SESSION['user']['id'] ?? null;
if (!$currentUser) {
  if ($isAjax) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'You need to log in.']);
    exit;
  } else {
    flash_set('error', 'Precisa de iniciar sessão.');
    header('Location: event_page.php');
    exit;
  }
}

// Sanitize a return URL so we only redirect locally
function sanitize_local_return($raw)
{
  $raw = trim((string)$raw);
  if ($raw === '') return '';
  // Allow same-host absolute URLs
  if (preg_match('#^https?://#i', $raw) || strpos($raw, '//') === 0) {
    $parsed = parse_url($raw);
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($parsed && !empty($parsed['host']) && $host && strcasecmp($parsed['host'], $host) === 0) {
      $path = $parsed['path'] ?? '';
      $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
      $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';
      return $path . $query . $fragment;
    }
    return '';
  }
  // Allow relative php targets with optional query/anchor
  if (preg_match('/^[A-Za-z0-9_./-]+\.php(?:\?[A-Za-z0-9_=&%\\-]+)?(?:#[A-Za-z0-9_\\-]+)?$/', $raw)) {
    return $raw;
  }
  return '';
}

$appTimezone = new DateTimeZone(date_default_timezone_get());
$now = new DateTime('now', $appTimezone);
$today = $now->format('Y-m-d');

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
  if ($GLOBALS['isAjax']) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
  } else {
    flash_set('error', $msg);
    header('Location: event_page.php');
    exit;
  }
}

function normalize_cost_input($raw)
{
  $raw = trim((string)$raw);
  if ($raw === '') return 0.0;
  $normalized = str_replace(',', '.', $raw);
  if (!is_numeric($normalized)) return null;
  $cost = (float)$normalized;
  if ($cost < 0) return null;
  return round($cost, 2);
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

function fetch_event_collections($mysqli, $eventId)
{
  $stmt = $mysqli->prepare('SELECT collection_id FROM collection_events WHERE event_id = ?');
  if (!$stmt) return [];
  $stmt->bind_param('s', $eventId);
  $stmt->execute();
  $res = $stmt->get_result();
  $collections = [];
  while ($row = $res->fetch_assoc()) {
    $cid = $row['collection_id'] ?? null;
    if ($cid && !in_array($cid, $collections, true)) {
      $collections[] = $cid;
    }
  }
  $stmt->close();
  return $collections;
}

if ($action === 'create') {
  if (!$collectionIds) redirect_error('Select at least one collection.');
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
  $costInput = $_POST['cost'] ?? '';
  $rawDate = trim($_POST['date'] ?? '');
  if ($localization === '' || $rawDate === '') {
    $mysqli->close();
    redirect_error('Indicate the location and date of the event.');
  }
  $costValue = normalize_cost_input($costInput);
  if ($costValue === null) {
    $mysqli->close();
    redirect_error('Invalid event cost.');
  }
  $parsedInput = parse_event_datetime_helper($rawDate, $appTimezone);
  $dateObj = $parsedInput['date'];
  $inputHasTime = $parsedInput['hasTime'];
  if (!$dateObj) {
    $mysqli->close();
    redirect_error('Invalid event date/time.');
  }
  $isPast = $inputHasTime
    ? ($dateObj <= $now)
    : ($dateObj->format('Y-m-d') < $today);
  if ($isPast) {
    $mysqli->close();
    redirect_error('You cannot schedule events for past dates or times.');
  }
  if (!$inputHasTime) {
    $dateObj->setTime(0, 0, 0);
  }
  $dateForSql = $dateObj->format('Y-m-d H:i:s');
  $costForSql = (float)number_format($costValue, 2, '.', '');
  $stmt = $mysqli->prepare('INSERT INTO events (event_id,name,localization,event_date,type,summary,description,cost,created_at) VALUES (?,?,?,?,?,?,?,?,NOW())');
  $stmt->bind_param('sssssssd', $id, $name, $localization, $dateForSql, $type, $summary, $description, $costForSql);
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
  if (!$collectionIds) redirect_error('Select at least one collection.');

  $currentEventStmt = $mysqli->prepare('SELECT event_date FROM events WHERE event_id = ? LIMIT 1');
  $currentEventStmt->bind_param('s', $id);
  $currentEventStmt->execute();
  $currentEvent = $currentEventStmt->get_result()->fetch_assoc();
  $currentEventStmt->close();
  if (!$currentEvent) {
    $mysqli->close();
    redirect_error('Event not found.');
  }
  $linkedCollections = fetch_event_collections($mysqli, $id);
  $ownsEvent = false;
  foreach ($linkedCollections as $cid) {
    if (user_owns_collection($mysqli, $cid, $currentUser)) {
      $ownsEvent = true;
      break;
    }
  }
  if (!$ownsEvent) {
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
      redirect_error('Events that have already occurred cannot be edited.');
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
  $costInput = $_POST['cost'] ?? '';
  $rawDate = trim($_POST['date'] ?? '');
  if ($localization === '' || $rawDate === '') {
    $mysqli->close();
    redirect_error('Indicate the location and date of the event.');
  }
  $costValue = normalize_cost_input($costInput);
  if ($costValue === null) {
    $mysqli->close();
    redirect_error('Invalid event cost.');
  }
  $parsedInput = parse_event_datetime_helper($rawDate, $appTimezone);
  $dateObj = $parsedInput['date'];
  $inputHasTime = $parsedInput['hasTime'];
  if (!$dateObj) {
    $mysqli->close();
    redirect_error('Invalid event date/time.');
  }
  $isPast = $inputHasTime
    ? ($dateObj <= $now)
    : ($dateObj->format('Y-m-d') < $today);
  if ($isPast) {
    $mysqli->close();
    redirect_error('You cannot schedule events for past dates or times.');
  }
  if (!$inputHasTime) {
    $dateObj->setTime(0, 0, 0);
  }
  $dateForSql = $dateObj->format('Y-m-d H:i:s');
  $costForSql = (float)number_format($costValue, 2, '.', '');

  $stmt = $mysqli->prepare('UPDATE events SET name=?, localization=?, event_date=?, type=?, summary=?, description=?, cost=? WHERE event_id=?');
  $stmt->bind_param('ssssssds', $name, $localization, $dateForSql, $type, $summary, $description, $costForSql, $id);
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

  $existsStmt = $mysqli->prepare('SELECT event_id FROM events WHERE event_id = ? LIMIT 1');
  $existsStmt->bind_param('s', $id);
  $existsStmt->execute();
  $existsRes = $existsStmt->get_result();
  $existsRow = $existsRes->fetch_assoc();
  $existsStmt->close();
  if (!$existsRow) {
    $mysqli->close();
    redirect_error('Event not found.');
  }

  $linkedCollections = fetch_event_collections($mysqli, $id);
  $ownsEvent = false;
  foreach ($linkedCollections as $cid) {
    if (user_owns_collection($mysqli, $cid, $currentUser)) {
      $ownsEvent = true;
      break;
    }
  }
  if (!$ownsEvent) {
    $mysqli->close();
    redirect_error('You do not have permission to delete this event.');
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
  $stmt = $mysqli->prepare('SELECT event_id, event_date FROM events WHERE event_id = ? LIMIT 1');
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
  // Determine redirect destination (allow specific page anchors when provided).
  $returnUrlRaw = trim($_POST['return_url'] ?? '');
  $redirectUrl = sanitize_local_return($returnUrlRaw);
  if ($redirectUrl === '') {
    $returnTarget = trim($_POST['return_target'] ?? '');
    if ($returnTarget !== '' && !preg_match('/^#[A-Za-z0-9_-]+$/', $returnTarget)) {
      $returnTarget = '';
    }
    $fallback = sanitize_local_return($_SERVER['HTTP_REFERER'] ?? '');
    $redirectUrl = $fallback !== '' ? $fallback : 'event_page.php';
    if ($returnTarget !== '') {
      $redirectUrl .= $returnTarget;
    }
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
      if ($isAjax) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'hasRsvp' => false]);
        exit;
      }
      header('Location: ' . $redirectUrl);
      exit;
    } else {
      if ($isAjax) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to remove RSVP.']);
        exit;
      } else {
        redirect_error('Failed to remove RSVP.');
      }
    }
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
      if ($isAjax) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'hasRsvp' => true]);
        exit;
      }
      header('Location: ' . $redirectUrl);
      exit;
    } else {
      if ($isAjax) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to register RSVP.']);
        exit;
      } else {
        redirect_error('Failed to register RSVP.');
      }
    }
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
    redirect_error('Inv.');
  }
  $eventHasEnded = $hasTime
    ? ($eventDateObj <= $now)
    : ($eventDateObj->format('Y-m-d') < $today);
  if (!$eventHasEnded) {
    $mysqli->close();
    redirect_error('Event has not ended yet.');
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
  if (!$linked) {
    $mysqli->close();
    redirect_error('Collection is not associated with this event.');
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

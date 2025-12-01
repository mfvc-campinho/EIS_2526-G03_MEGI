<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config/db.php';

// Helper to fetch all rows from a table with optional mapping
function fetch_all($mysqli, $sql, $types = null, $params = [])
{
  $stmt = $mysqli->prepare($sql);
  if ($stmt === false) return [];
  if ($types && $params) {
    $stmt->bind_param($types, ...$params);
  }
  $stmt->execute();
  $res = $stmt->get_result();
  $rows = $res->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
  return $rows;
}

// 1) Collections
$cols = fetch_all($mysqli, "SELECT collection_id,name,type,cover_image,summary,description,created_at,user_id FROM collections ORDER BY created_at DESC");
$collections = array_map(function ($r) {
  $owner = $r['user_id'] ?? null;
  return [
    'id' => $r['collection_id'] ?? null,
    'name' => $r['name'] ?? null,
    'type' => $r['type'] ?? null,
    'coverImage' => $r['cover_image'] ?: '../images/default.jpg',
    'summary' => $r['summary'] ?? null,
    'description' => $r['description'] ?? null,
    'createdAt' => $r['created_at'] ?? null,
    'ownerId' => $owner,
    // legacy client compatibility
    'owner-id' => $owner
  ];
}, $cols);

// 2) Users
$usersRows = fetch_all($mysqli, "SELECT user_id,user_name,user_photo,date_of_birth,email,member_since FROM users");
$users = array_map(function ($r) {
  $uid = $r['user_id'] ?? null;
  $uname = $r['user_name'] ?? null;
  return [
    'id' => $uid,
    'user_id' => $uid,
    'user_name' => $uname,
    'user_photo' => $r['user_photo'] ?? null,
    'date_of_birth' => $r['date_of_birth'] ?? null,
    'email' => $r['email'] ?? null,
    'member_since' => $r['member_since'] ?? null,
    // legacy keys expected by client
    'owner-id' => $uid,
    'owner-name' => $uname,
    'owner-photo' => $r['user_photo'] ?? null,
    'date-of-birth' => $r['date_of_birth'] ?? null,
    'member-since' => $r['member_since'] ?? null
  ];
}, $usersRows);

// 3) Items
$itemsRows = fetch_all($mysqli, "SELECT item_id,name,importance,weight,price,acquisition_date,created_at,updated_at,image,collection_id FROM items");
$items = array_map(function ($r) {
  return [
    'id' => $r['item_id'] ?? null,
    'name' => $r['name'] ?? null,
    'importance' => $r['importance'] ?? null,
    'weight' => $r['weight'] ?? null,
    'price' => $r['price'] ?? null,
    'acquisitionDate' => $r['acquisition_date'] ?? null,
    'createdAt' => $r['created_at'] ?? null,
    'updatedAt' => $r['updated_at'] ?? null,
    'image' => $r['image'] ?? null,
    'collectionId' => $r['collection_id'] ?? null,
    // legacy alias
    'collection_id' => $r['collection_id'] ?? null
  ];
}, $itemsRows);

// 4) Events
$eventsRows = fetch_all($mysqli, "SELECT event_id,name,localization,event_date,type,summary,description,created_at,updated_at,host_user_id,collection_id FROM events");
$events = array_map(function ($r) {
  $host = $r['host_user_id'] ?? null;
  return [
    'id' => $r['event_id'] ?? null,
    'name' => $r['name'] ?? null,
    'localization' => $r['localization'] ?? null,
    'date' => $r['event_date'] ?? null,
    'type' => $r['type'] ?? null,
    'summary' => $r['summary'] ?? null,
    'description' => $r['description'] ?? null,
    'createdAt' => $r['created_at'] ?? null,
    'updatedAt' => $r['updated_at'] ?? null,
    'hostUserId' => $host,
    'collectionId' => $r['collection_id'] ?? null,
    // legacy aliases
    'host_user_id' => $host
  ];
}, $eventsRows);

// 5) collection_items relation
$ciRows = fetch_all($mysqli, "SELECT collection_id,item_id FROM collection_items");
$collectionItems = array_map(function ($r) {
  return ['collectionId' => $r['collection_id'], 'itemId' => $r['item_id']];
}, $ciRows);

// 6) collection_events relation
$ceRows = fetch_all($mysqli, "SELECT collection_id,event_id FROM collection_events");
$collectionEvents = array_map(function ($r) {
  return ['collectionId' => $r['collection_id'], 'eventId' => $r['event_id']];
}, $ceRows);

// 7) collectionsUsers mapping (derived from collections.user_id)
$collectionsUsers = array_map(function ($c) {
  return ['collectionId' => $c['id'], 'ownerId' => $c['ownerId']];
}, $collections);

// 8) eventsUsers (from event_ratings)
$erRows = fetch_all($mysqli, "SELECT event_id,user_id,rating,collection_id FROM event_ratings");
$eventsUsers = array_map(function ($r) {
  return ['eventId' => $r['event_id'], 'userId' => $r['user_id'], 'rating' => $r['rating'], 'collectionId' => $r['collection_id']];
}, $erRows);

// 9) userShowcases (migrated to separate tables)
// Combine data from user_ratings_collections, user_ratings_events, user_ratings_items
$userShowcases = [];
$showcaseMap = []; // ownerId => ['ownerId'=>..., 'lastUpdated'=>..., 'picks'=>[], 'likes'=>[], 'likedItems'=>[], 'likedEvents'=>[]]

// collections
$urc = fetch_all($mysqli, "SELECT user_id,last_updated,liked_collections FROM user_ratings_collections");
foreach ($urc as $r) {
  $uid = $r['user_id'] ?? null;
  if (!$uid) continue;
  if (!isset($showcaseMap[$uid])) {
    $showcaseMap[$uid] = ['ownerId' => $uid, 'lastUpdated' => $r['last_updated'] ?? null, 'picks' => [], 'likes' => [], 'likedItems' => [], 'likedEvents' => []];
  }
  // prefer the most recent last_updated
  if (!empty($r['last_updated']) && (empty($showcaseMap[$uid]['lastUpdated']) || strtotime($r['last_updated']) > strtotime($showcaseMap[$uid]['lastUpdated']))) {
    $showcaseMap[$uid]['lastUpdated'] = $r['last_updated'];
  }
  if (!empty($r['liked_collections'])) {
    $decoded = json_decode($r['liked_collections'], true);
    if (is_array($decoded)) $showcaseMap[$uid]['likes'] = array_values(array_unique(array_merge($showcaseMap[$uid]['likes'], $decoded)));
  }
}

// events
$ure = fetch_all($mysqli, "SELECT user_id,last_updated,liked_events FROM user_ratings_events");
foreach ($ure as $r) {
  $uid = $r['user_id'] ?? null;
  if (!$uid) continue;
  if (!isset($showcaseMap[$uid])) {
    $showcaseMap[$uid] = ['ownerId' => $uid, 'lastUpdated' => $r['last_updated'] ?? null, 'picks' => [], 'likes' => [], 'likedItems' => [], 'likedEvents' => []];
  }
  if (!empty($r['last_updated']) && (empty($showcaseMap[$uid]['lastUpdated']) || strtotime($r['last_updated']) > strtotime($showcaseMap[$uid]['lastUpdated']))) {
    $showcaseMap[$uid]['lastUpdated'] = $r['last_updated'];
  }
  if (!empty($r['liked_events'])) {
    $decoded = json_decode($r['liked_events'], true);
    if (is_array($decoded)) $showcaseMap[$uid]['likedEvents'] = array_values(array_unique(array_merge($showcaseMap[$uid]['likedEvents'], $decoded)));
  }
}

// items
$uri = fetch_all($mysqli, "SELECT user_id,last_updated,liked_items FROM user_ratings_items");
foreach ($uri as $r) {
  $uid = $r['user_id'] ?? null;
  if (!$uid) continue;
  if (!isset($showcaseMap[$uid])) {
    $showcaseMap[$uid] = ['ownerId' => $uid, 'lastUpdated' => $r['last_updated'] ?? null, 'picks' => [], 'likes' => [], 'likedItems' => [], 'likedEvents' => []];
  }
  if (!empty($r['last_updated']) && (empty($showcaseMap[$uid]['lastUpdated']) || strtotime($r['last_updated']) > strtotime($showcaseMap[$uid]['lastUpdated']))) {
    $showcaseMap[$uid]['lastUpdated'] = $r['last_updated'];
  }
  if (!empty($r['liked_items'])) {
    $decoded = json_decode($r['liked_items'], true);
    if (is_array($decoded)) $showcaseMap[$uid]['likedItems'] = array_values(array_unique(array_merge($showcaseMap[$uid]['likedItems'], $decoded)));
  }
}

// Convert map to indexed array
foreach ($showcaseMap as $entry) {
  $userShowcases[] = $entry;
}

// 10) collectionRatings and itemRatings: DB lacks dedicated tables; return empty arrays for now
$collectionRatings = [];
$itemRatings = [];

// Final assembly
$out = [
  'collections' => $collections,
  'users' => $users,
  'items' => $items,
  'events' => $events,
  'collectionItems' => $collectionItems,
  'collectionEvents' => $collectionEvents,
  'collectionsUsers' => $collectionsUsers,
  'eventsUsers' => $eventsUsers,
  'userShowcases' => $userShowcases,
  'collectionRatings' => $collectionRatings,
  'itemRatings' => $itemRatings
];

echo json_encode($out, JSON_UNESCAPED_UNICODE);

$mysqli->close();

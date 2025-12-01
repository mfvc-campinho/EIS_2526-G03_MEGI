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
  return [
    'id' => $r['collection_id'],
    'name' => $r['name'],
    'type' => $r['type'],
    'coverImage' => $r['cover_image'] ?: '../images/default.jpg',
    'summary' => $r['summary'],
    'description' => $r['description'],
    'createdAt' => $r['created_at'],
    'ownerId' => $r['user_id']
  ];
}, $cols);

// 2) Users
$usersRows = fetch_all($mysqli, "SELECT user_id,user_name,user_photo,date_of_birth,email,member_since FROM users");
$users = array_map(function ($r) {
  return [
    'id' => $r['user_id'],
    'user_name' => $r['user_name'],
    'user_photo' => $r['user_photo'],
    'date_of_birth' => $r['date_of_birth'],
    'email' => $r['email'],
    'member_since' => $r['member_since']
  ];
}, $usersRows);

// 3) Items
$itemsRows = fetch_all($mysqli, "SELECT item_id,name,importance,weight,price,acquisition_date,created_at,updated_at,image,collection_id FROM items");
$items = array_map(function ($r) {
  return [
    'id' => $r['item_id'],
    'name' => $r['name'],
    'importance' => $r['importance'],
    'weight' => $r['weight'],
    'price' => $r['price'],
    'acquisitionDate' => $r['acquisition_date'],
    'createdAt' => $r['created_at'],
    'updatedAt' => $r['updated_at'],
    'image' => $r['image'],
    'collectionId' => $r['collection_id']
  ];
}, $itemsRows);

// 4) Events
$eventsRows = fetch_all($mysqli, "SELECT event_id,name,localization,event_date,type,summary,description,created_at,updated_at,host_user_id,collection_id FROM events");
$events = array_map(function ($r) {
  return [
    'id' => $r['event_id'],
    'name' => $r['name'],
    'localization' => $r['localization'],
    'date' => $r['event_date'],
    'type' => $r['type'],
    'summary' => $r['summary'],
    'description' => $r['description'],
    'createdAt' => $r['created_at'],
    'updatedAt' => $r['updated_at'],
    'hostUserId' => $r['host_user_id'],
    'collectionId' => $r['collection_id']
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

// 9) userShowcases (from user_ratings JSON columns if available)
$urRows = fetch_all($mysqli, "SELECT user_id,last_updated,picks,liked_collections,liked_items,liked_events FROM user_ratings");
$userShowcases = [];
foreach ($urRows as $r) {
  $ownerId = $r['user_id'];
  $picks = [];
  $likes = [];
  $likedItems = [];
  $likedEvents = [];
  if ($r['picks']) {
    $decoded = json_decode($r['picks'], true);
    if (is_array($decoded)) $picks = $decoded;
  }
  if ($r['liked_collections']) {
    $decoded = json_decode($r['liked_collections'], true);
    if (is_array($decoded)) $likes = $decoded;
  }
  if ($r['liked_items']) {
    $decoded = json_decode($r['liked_items'], true);
    if (is_array($decoded)) $likedItems = $decoded;
  }
  if ($r['liked_events']) {
    $decoded = json_decode($r['liked_events'], true);
    if (is_array($decoded)) $likedEvents = $decoded;
  }
  $userShowcases[] = [
    'ownerId' => $ownerId,
    'lastUpdated' => $r['last_updated'],
    'picks' => $picks,
    'likes' => $likes,
    'likedItems' => $likedItems,
    'likedEvents' => $likedEvents
  ];
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

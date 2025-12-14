<?php
// Define Lisbon timezone globally for consistent date/time handling
if (function_exists('date_default_timezone_set')) {
  @date_default_timezone_set('Europe/Lisbon');
}

require_once __DIR__ . '/../config/db.php';

// Shared helpers so both JSON endpoints and bootstrap scripts can reuse the same logic.
function table_exists($mysqli, $tableName)
{
  $t = $mysqli->real_escape_string($tableName);
  $res = $mysqli->query("SHOW TABLES LIKE '{$t}'");
  return $res && $res->num_rows > 0;
}

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

function column_exists($mysqli, $table, $column)
{
  $t = $mysqli->real_escape_string($table);
  $c = $mysqli->real_escape_string($column);
  $res = $mysqli->query("SHOW COLUMNS FROM `{$t}` LIKE '{$c}'");
  return $res && $res->num_rows > 0;
}

// Resolve an image path preferring uploads/; if not found, return the stored value (no default fallback).
function resolve_image_path($rawPath, $folder, $default = '')
{
  $trim = trim((string)$rawPath);
  if ($trim === '') return '';
  if (preg_match('#^https?://#i', $trim)) return $trim; // absolute URL

  $fileName = basename($trim);
  $root = dirname(__DIR__, 2); // Matheus_Testes

  // If path already points to uploads and exists, keep it.
  $uploadsRel = 'uploads/' . $folder . '/' . $fileName;
  $uploadsAbs = $root . '/' . $uploadsRel;
  if (is_file($uploadsAbs)) {
    return $uploadsRel;
  }

  // Legacy images folder fallback
  $legacyRel = 'images/' . $fileName;
  $legacyAbs = $root . '/' . $legacyRel;
  if (is_file($legacyAbs)) {
    return $legacyRel;
  }

  // If stored path already points to a readable file, keep it
  $candidate = $root . '/' . ltrim($trim, './');
  if (is_file($candidate)) {
    return ltrim($trim, './');
  }

  // If nothing is found, return the cleaned original (no default).
  return ltrim($trim, './');
}

/**
 * Load the complete application dataset from MySQL.
 * Returns a PHP array ready to be JSON-encoded or embedded in a script tag.
 */
function load_app_data($mysqli)
{
  // 1) Collections
  $cols = fetch_all($mysqli, "SELECT collection_id,name,type,cover_image,summary,description,created_at,user_id FROM collections ORDER BY created_at DESC");
  $collections = array_map(function ($r) {
    $owner = $r['user_id'] ?? null;
    $cover = resolve_image_path($r['cover_image'] ?? '', 'collections');
    return [
      'id' => $r['collection_id'] ?? null,
      'name' => $r['name'] ?? null,
      'type' => $r['type'] ?? null,
      'coverImage' => $cover,
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
    $photo = resolve_image_path($r['user_photo'] ?? '', 'users');
    return [
      'id' => $uid,
      'user_id' => $uid,
      'user_name' => $uname,
      'user_photo' => $photo,
      'date_of_birth' => $r['date_of_birth'] ?? null,
      'email' => $r['email'] ?? null,
      'member_since' => $r['member_since'] ?? null,
      // legacy keys expected by client
      'owner-id' => $uid,
      'owner-name' => $uname,
      'owner-photo' => $photo,
      'date-of-birth' => $r['date_of_birth'] ?? null,
      'member-since' => $r['member_since'] ?? null
    ];
  }, $usersRows);

  // 3) Items
  $itemsRows = fetch_all($mysqli, "SELECT item_id,name,importance,weight,price,acquisition_date,created_at,updated_at,image FROM items");
  $items = array_map(function ($r) {
    $img = resolve_image_path($r['image'] ?? '', 'items');
    return [
      'id' => $r['item_id'] ?? null,
      'name' => $r['name'] ?? null,
      'importance' => $r['importance'] ?? null,
      'weight' => $r['weight'] ?? null,
      'price' => $r['price'] ?? null,
      'acquisitionDate' => $r['acquisition_date'] ?? null,
      'createdAt' => $r['created_at'] ?? null,
      'updatedAt' => $r['updated_at'] ?? null,
      'image' => $img,
    ];
  }, $itemsRows);

  // 4) Events
  $eventsSelectCost = column_exists($mysqli, 'events', 'cost');
  $eventsSelectHost = column_exists($mysqli, 'events', 'host_user_id');

  $eventFields = [
    'event_id',
    'name',
    'localization',
    'event_date',
    'type',
    'summary',
    'description',
    'created_at',
    'updated_at',
  ];
  if ($eventsSelectCost) $eventFields[] = 'cost';
  if ($eventsSelectHost) $eventFields[] = 'host_user_id';

  $eventsQuery = 'SELECT ' . implode(',', $eventFields) . ' FROM events';
  $eventsRows = fetch_all($mysqli, $eventsQuery);
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
      'cost' => $r['cost'] ?? null,
      'createdAt' => $r['created_at'] ?? null,
      'updatedAt' => $r['updated_at'] ?? null,
      'hostUserId' => $host,
      'collectionId' => null,
      'collection_id' => null,
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

  $eventCollectionMap = [];
  foreach ($collectionEvents as $link) {
    $eid = $link['eventId'] ?? null;
    $cid = $link['collectionId'] ?? null;
    if (!$eid || !$cid) continue;
    if (!isset($eventCollectionMap[$eid])) {
      $eventCollectionMap[$eid] = [];
    }
    if (!in_array($cid, $eventCollectionMap[$eid], true)) {
      $eventCollectionMap[$eid][] = $cid;
    }
  }
  foreach ($events as &$evt) {
    $eid = $evt['id'] ?? null;
    if ($eid && !empty($eventCollectionMap[$eid])) {
      $primary = $eventCollectionMap[$eid][0];
      if (empty($evt['collectionId']) && empty($evt['collection_id'])) {
        $evt['collectionId'] = $primary;
        $evt['collection_id'] = $primary;
      } else {
        $evt['collectionId'] = $evt['collectionId'] ?? $primary;
        $evt['collection_id'] = $evt['collection_id'] ?? $primary;
      }
    }
  }
  unset($evt);

  // 7) collectionsUsers mapping (derived from collections.user_id)
  $collectionsUsers = array_map(function ($c) {
    return ['collectionId' => $c['id'], 'ownerId' => $c['ownerId']];
  }, $collections);

  // 8) eventsUsers (ratings + RSVPs) with table existence guards
  $erRows = table_exists($mysqli, 'event_ratings')
    ? fetch_all($mysqli, "SELECT event_id,user_id,rating,collection_id FROM event_ratings")
    : [];
  $rsvpRows = table_exists($mysqli, 'event_rsvps')
    ? fetch_all($mysqli, "SELECT event_id,user_id FROM event_rsvps")
    : [];

  $rsvpMap = [];
  foreach ($rsvpRows as $r) {
    $eid = $r['event_id'] ?? null;
    $uid = $r['user_id'] ?? null;
    if ($eid && $uid) {
      $rsvpMap["{$eid}|{$uid}"] = true;
    }
  }

  $eventsUsers = [];
  foreach ($erRows as $r) {
    $eid = $r['event_id'] ?? null;
    $uid = $r['user_id'] ?? null;
    if (!$eid || !$uid) continue;
    $key = "{$eid}|{$uid}";
    $eventsUsers[] = [
      'eventId' => $eid,
      'userId' => $uid,
      'rating' => $r['rating'],
      'collectionId' => $r['collection_id'] ?? null,
      'rsvp' => isset($rsvpMap[$key]) ? 1 : 0,
      'type' => 'rating'
    ];
  }
  // Add RSVPs (even if the user has rated specific collections)
  foreach ($rsvpMap as $key => $_) {
    [$eid, $uid] = explode('|', $key, 2);
    $eventsUsers[] = [
      'eventId' => $eid,
      'userId' => $uid,
      'rating' => null,
      'collectionId' => null,
      'rsvp' => 1,
      'type' => 'rsvp'
    ];
  }

  // 9) userShowcases (migrated to separate tables)
  // Combine data from user_ratings_collections, user_ratings_events, user_ratings_items
  // If dedicated per-like tables exist (user_liked_*), prefer those.
  $userShowcases = [];
  $showcaseMap = []; // ownerId => ['ownerId'=>..., 'lastUpdated'=>..., 'picks'=>[], 'likes'=>[], 'likedItems'=>[], 'likedEvents'=>[]]

  // Prefer per-row tables when available
  $hasLikedCollectionsTable = table_exists($mysqli, 'user_liked_collections');
  $hasLikedItemsTable = table_exists($mysqli, 'user_liked_items');
  $hasLikedEventsTable = false;

  // collections (prefer per-like table; only fall back to legacy if needed)
  $urc = $hasLikedCollectionsTable
    ? fetch_all($mysqli, "SELECT user_id,last_updated,liked_collection_id as liked_collections FROM user_liked_collections")
    : fetch_all($mysqli, "SELECT user_id,last_updated,liked_collections FROM user_ratings_collections");
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
      $raw = $r['liked_collections'];
      $decoded = json_decode($raw, true);
      if (is_array($decoded)) {
        $showcaseMap[$uid]['likes'] = array_values(array_unique(array_merge($showcaseMap[$uid]['likes'], $decoded)));
      } else {
        $showcaseMap[$uid]['likes'] = array_values(array_unique(array_merge($showcaseMap[$uid]['likes'], [$raw])));
      }
    }
  }

  // events -> ignore liked events (deprecated)
  $ure = [];

  // items
  $uri = $hasLikedItemsTable
    ? fetch_all($mysqli, "SELECT user_id,last_updated,liked_item_id as liked_items FROM user_liked_items")
    : fetch_all($mysqli, "SELECT user_id,last_updated,liked_items FROM user_ratings_items");
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
      $raw = $r['liked_items'];
      $decoded = json_decode($raw, true);
      if (is_array($decoded)) {
        $showcaseMap[$uid]['likedItems'] = array_values(array_unique(array_merge($showcaseMap[$uid]['likedItems'], $decoded)));
      } else {
        $showcaseMap[$uid]['likedItems'] = array_values(array_unique(array_merge($showcaseMap[$uid]['likedItems'], [$raw])));
      }
    }
  }

  // Convert map to indexed array
  foreach ($showcaseMap as $entry) {
    $userShowcases[] = $entry;
  }

  // 11) user_follows (map follower -> [following])
  $ufRows = fetch_all($mysqli, "SELECT follower_id,following_id FROM user_followers");
  $userFollows = [];
  foreach ($ufRows as $r) {
    $f = $r['follower_id'] ?? null;
    $t = $r['following_id'] ?? null;
    if (!$f || !$t) continue;
    if (!isset($userFollows[$f])) $userFollows[$f] = [];
    if (!in_array($t, $userFollows[$f], true)) $userFollows[$f][] = $t;
  }

  // 10) Ratings (items/collections) not stored server-side; keep empty arrays
  $itemRatings = [];
  $collectionRatings = [];

  // Final assembly
  return [
    'collections' => $collections,
    'users' => $users,
    'items' => $items,
    'events' => $events,
    'collectionItems' => $collectionItems,
    'collectionEvents' => $collectionEvents,
    'collectionsUsers' => $collectionsUsers,
    'eventsUsers' => $eventsUsers,
    'userShowcases' => $userShowcases,
    'userFollows' => $userFollows,
    'collectionRatings' => $collectionRatings,
    'itemRatings' => $itemRatings
  ];
}

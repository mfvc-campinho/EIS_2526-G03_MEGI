<?php
session_start();
require_once __DIR__ . '/../includes/data_loader.php';
require_once __DIR__ . '/../includes/flash.php';

$data = load_app_data($mysqli);
$mysqli->close();
$events = $data['events'] ?? [];
$eventsUsers = $data['eventsUsers'] ?? [];
$collectionEvents = $data['collectionEvents'] ?? [];
$collections = $data['collections'] ?? [];

$appTimezone = new DateTimeZone(date_default_timezone_get());

if (!function_exists('parse_event_datetime')) {
  function parse_event_datetime($raw, DateTimeZone $tz)
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

$isAuth = !empty($_SESSION['user']);
$currentUserId = $_SESSION['user']['id'] ?? null;

$status = $_GET['status'] ?? 'upcoming'; // upcoming | past
$typeFilter = $_GET['type'] ?? 'all';
$locFilter = $_GET['loc'] ?? 'all';
$sort = $_GET['sort'] ?? 'date_old'; // date_old | date_new | name
$perPage = max(1, (int)($_GET['perPage'] ?? 10));
$page = max(1, (int)($_GET['page'] ?? 1));
$now = new DateTime('now', $appTimezone);
$today = $now->format('Y-m-d');

// Build filter options
$types = [];
$locations = [];
foreach ($events as $ev) {
  if (!empty($ev['type'])) $types[$ev['type']] = true;
  if (!empty($ev['localization'])) $locations[$ev['localization']] = true;
}
$types = array_keys($types);
$locations = array_keys($locations);

// Upcoming / past counts
$upcomingCount = 0;
$pastCount = 0;
foreach ($events as $ev) {
  $parsed = parse_event_datetime($ev['date'] ?? null, $appTimezone);
  $eventDateObj = $parsed['date'];
  $hasTime = $parsed['hasTime'];
  $isUpcoming = false;
  if ($eventDateObj) {
    if ($hasTime) {
      $isUpcoming = $eventDateObj > $now;
    } else {
      $isUpcoming = $eventDateObj->format('Y-m-d') >= $today;
    }
  }
  if ($isUpcoming) $upcomingCount++;
  else $pastCount++;
}

// Filter
$filtered = array_filter($events, function ($ev) use ($status, $now, $appTimezone, $today, $typeFilter, $locFilter) {
  $parsed = parse_event_datetime($ev['date'] ?? null, $appTimezone);
  $eventDateObj = $parsed['date'];
  $hasTime = $parsed['hasTime'];
  $isUpcoming = false;
  if ($eventDateObj) {
    if ($hasTime) {
      $isUpcoming = $eventDateObj > $now;
    } else {
      $isUpcoming = $eventDateObj->format('Y-m-d') >= $today;
    }
  }
  if ($status === 'upcoming' && !$isUpcoming) return false;
  if ($status === 'past' && $isUpcoming) return false;
  if ($typeFilter !== 'all' && ($ev['type'] ?? null) !== $typeFilter) return false;
  if ($locFilter !== 'all' && ($ev['localization'] ?? null) !== $locFilter) return false;
  return true;
});

// Sort
usort($filtered, function ($a, $b) use ($sort) {
  if ($sort === 'name') {
    return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
  }
  $aDate = $a['date'] ?? '';
  $bDate = $b['date'] ?? '';
  if ($sort === 'date_new') {
    return strcmp($bDate, $aDate);
  }
  // date_old default
  return strcmp($aDate, $bDate);
});

$total = count($filtered);
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;
$eventsPage = array_slice($filtered, $offset, $perPage);

// Month label: always show current month
$monthLabel = date('F Y');

// Pre-compute lookup maps for events, collections and user interactions
$collectionMap = [];
foreach ($collections as $col) {
  if (!empty($col['id'])) {
    $collectionMap[$col['id']] = $col;
  }
}

$eventCollections = [];
foreach ($collectionEvents as $link) {
  $eid = $link['eventId'] ?? null;
  $cid = $link['collectionId'] ?? null;
  if (!$eid || !$cid) continue;
  if (!isset($eventCollections[$eid])) $eventCollections[$eid] = [];
  if (!in_array($cid, $eventCollections[$eid], true)) $eventCollections[$eid][] = $cid;
}

$eventRsvpMap = [];
$userCollectionRatings = [];
$eventAvgMap = [];
$eventCountMap = [];
$collectionEventStats = [];
foreach ($eventsUsers as $entry) {
  $eid = $entry['eventId'] ?? null;
  $uid = $entry['userId'] ?? null;
  if (!$eid || !$uid) continue;
  $type = $entry['type'] ?? '';
  $hasRating = isset($entry['rating']) && $entry['rating'] !== null;
  $hasRsvpFlag = !empty($entry['rsvp']);

  if ($type === 'rsvp' || ($type === '' && !$hasRating && $hasRsvpFlag)) {
    $eventRsvpMap["{$eid}|{$uid}"] = true;
  }

  if ($type === 'rating' || ($type === '' && $hasRating)) {
    $cid = $entry['collectionId'] ?? null;
    if ($cid) {
      $userCollectionRatings["{$eid}|{$uid}|{$cid}"] = $entry['rating'];
      if (!isset($collectionEventStats[$eid])) {
        $collectionEventStats[$eid] = [];
      }
      if (!isset($collectionEventStats[$eid][$cid])) {
        $collectionEventStats[$eid][$cid] = ['sum' => 0, 'count' => 0];
      }
      $collectionEventStats[$eid][$cid]['sum'] += (float)($entry['rating'] ?? 0);
      $collectionEventStats[$eid][$cid]['count'] += 1;
    }
    if (!isset($eventAvgMap[$eid])) {
      $eventAvgMap[$eid] = 0;
      $eventCountMap[$eid] = 0;
    }
    $eventAvgMap[$eid] += (float)($entry['rating'] ?? 0);
    $eventCountMap[$eid] += 1;
    $eventRsvpMap["{$eid}|{$uid}"] = true;
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Events • PHP</title>
  <link rel="stylesheet" href="../../CSS/general.css">
  <link rel="stylesheet" href="../../CSS/events.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../../CSS/christmas.css">
  <script src="../../JS/theme-toggle.js"></script>
  <script src="../../JS/christmas-theme.js"></script>
  <style>
    body { background: #f5f6f8; }
    /* Subtitle spacing */
    .collections-hero p {
      margin: 4px auto 0;
      max-width: 620px;
      font-size: 0.96rem;
      color: #4b5563;
    }
    .pill-toggle { display: flex; justify-content: center; gap: 14px; margin: 26px auto 18px; max-width: 400px; background: #eef0ff; padding: 10px; border-radius: 24px; box-shadow: inset 0 0 0 1px rgba(99,102,241,.12); }
    .pill-toggle a { flex: 1; text-align: center; padding: 10px 12px; border-radius: 18px; font-weight: 600; color: #6b6e82; text-decoration: none; }
    .pill-toggle a.active { background: #fff; box-shadow: 0 8px 16px rgba(99,102,241,.15); color: #2f2f3f; }
    .month-bar { display: flex; justify-content: center; margin: 28px auto 24px; }
    .month-chip { padding: 14px 28px; background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 12px; font-size: 1.1rem; font-weight: 600; color: #374151; letter-spacing: 0.5px; }
    .controls { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; align-items: center; margin: 18px auto 10px; max-width: 1100px; }
    .controls label { display: block; color: #6b7280; font-weight: 600; margin-bottom: 6px; }
    .controls select { width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid #d0d5dd; background: #fff; font-weight: 600; }
    .actions-row { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; padding: 16px 0; max-width: 1100px; margin: 0 auto; color: #555; }
    .paginate { display: flex; align-items: center; gap: 10px; }
    .paginate button { border: none; background: #e9ecf2; padding: 10px 12px; border-radius: 12px; cursor: pointer; }
    .paginate button:disabled { opacity: .35; cursor: default; }
    .events-grid { max-width: 1200px; margin: 0 auto 40px; display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
    .event-card { 
      background: #fff; 
      border-radius: 12px; 
      padding: 16px; 
      box-shadow: 0 1px 4px rgba(15, 23, 42, 0.05); 
      border: 1px solid #e5e7eb;
      display: flex; 
      flex-direction: column; 
      gap: 10px;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .event-card:hover {
      border-color: #94a3b8;
      box-shadow: 0 6px 18px rgba(15, 23, 42, 0.12);
    }
    .event-card-top { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
    .event-card h3 { margin: 0; font-size: 1.08rem; color: #0f172a; letter-spacing: -0.01em; }
    .status-chip { padding: 4px 10px; border-radius: 999px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; }
    .status-chip.upcoming { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
    .status-chip.past { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
    .pill { margin: 0; }
    .avg-stars { display:flex; gap:4px; color:#f5b301; align-items:center; font-weight:600; }
    .avg-stars .count { color:#6b7280; font-size:0.85rem; margin-left:4px; }
    .event-actions { display: flex; gap: 6px; flex-wrap: wrap; align-items:center; margin-top: 2px; }
    .event-actions .explore-btn { padding: 6px 12px; font-size: 0.8rem; border-radius: 8px; }
    .event-actions .explore-btn.small { padding: 6px 12px; font-size: 0.8rem; }
    .event-actions .explore-btn.ghost { padding: 6px 10px; }
    .badge-muted { color: #6b7280; font-weight: 600; font-size: 0.8rem; }
    .hero-subtle { font-size: 0.95rem; margin-top: -4px; }
    .clear-btn { background: #fff0f0; color: #c53030; border: 1px solid #f5c2c0; border-radius: 12px; padding: 10px 16px; font-weight: 600; cursor: pointer; }
    .stars { display:flex; gap:4px; }
    .star-btn { border:none; background:transparent; cursor:pointer; padding:4px; color:#f5b301; font-size:18px; }
    .star-btn i { pointer-events:none; }
    .star-btn.active i { color:#f59e0b; }
    .user-stars { display:flex; gap:4px; color:#f5b301; align-items:center; font-weight:600; }
    .user-stars .label { color:#374151; font-size:0.9rem; margin-right:6px; }
    .collection-chip { background: #dee3ff; color: #1e1b4b; padding: 6px 14px; border-radius: 999px; font-weight: 600; font-size: 0.85rem; }
    .modal-ratings { margin-top: 28px; display: flex; flex-direction: column; gap: 16px; }
    .modal-rating-overview { background: #eef2ff; color: #1e1b4b; padding: 12px 16px; border-radius: 12px; font-weight: 600; font-size: 0.95rem; display: flex; justify-content: space-between; align-items: center; }
    .modal-rating-overview .count { color: #4338ca; font-size: 0.85rem; font-weight: 500; }
    .modal-rating-overview .avg-value { margin-left: 6px; font-weight: 700; color: #1d4ed8; }
    .modal-rating-stats { display: flex; flex-direction: column; gap: 14px; }
    .modal-rating-card { background: #f8fafc; border: 1px solid #dbeafe; border-radius: 14px; padding: 16px; display: flex; flex-direction: column; gap: 12px; }
    .modal-rating-card-header { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
    .modal-rating-card-header .collection-name { font-weight: 700; color: #1e293b; font-size: 0.95rem; }
    .modal-rating-card-header .rating-badge { color: #1d4ed8; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 4px; }
    .modal-rating-card-header .rating-badge .avg-value { margin-left: 6px; font-weight: 600; color: #1e3a8a; }
    .modal-rating-card-header .rating-badge .count { color: #475569; font-size: 0.8rem; font-weight: 500; }
    .modal-rating-message { background: #f1f5f9; color: #334155; padding: 10px 14px; border-radius: 10px; font-size: 0.9rem; }
    .modal-rating-form { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
    .modal-rating-form select { padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.9rem; }
    .modal-rating-form button { padding: 6px 14px; }
    .modal-rating-user { display: flex; align-items: center; gap: 6px; color: #475569; font-size: 0.85rem; }
    .modal-rating-user i { color: #f59e0b; }
    /* Modal */
    .modal-backdrop { position:fixed; inset:0; background:rgba(0,0,0,0.5); display:none; align-items:center; justify-content:center; z-index:1000; backdrop-filter: blur(4px); }
    .modal-backdrop.open { display:flex; animation: fadeIn 0.2s ease; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    .modal-card { background:#fff; border-radius:20px; padding:0; max-width:600px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.3); position:relative; animation: slideUp 0.3s ease; overflow:hidden; }
    @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    .modal-header { background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%); padding: 32px 28px; color: white; position: relative; text-align: center; }
    .modal-close { position: absolute; top: 16px; right: 16px; border:none; background: rgba(255,255,255,0.2); color: white; width: 36px; height: 36px; border-radius: 50%; font-size:20px; cursor:pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease; }
    .modal-close:hover { background: rgba(255,255,255,0.3); transform: rotate(90deg); }
    .modal-header h3 { margin:0 0 12px 0; font-size: 2.25rem; font-weight: 900 !important; color: white !important; line-height: 1.2; }
    .modal-type-badge { display: inline-block; background: rgba(255,255,255,0.25); padding: 4px 12px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 8px; }
    .modal-body { padding: 28px; }
    .modal-summary { font-size: 1.05rem; color: #374151; line-height: 1.6; margin: 0 0 24px 0; font-weight: 500; }
    .modal-description { color: #6b7280; line-height: 1.7; margin: 0 0 24px 0; }
    .modal-info-grid { display: grid; gap: 16px; }
    .modal-info-item { display: flex; align-items: flex-start; gap: 12px; padding: 14px; background: #f9fafb; border-radius: 12px; border: 1px solid #e5e7eb; }
    .modal-info-icon { width: 40px; height: 40px; min-width: 40px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; }
    .modal-info-content { flex: 1; }
    .modal-info-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af; margin-bottom: 4px; }
    .modal-info-value { font-size: 1rem; font-weight: 600; color: #1f2937; }
    /* Calendar Styles */
    .calendar-toggle-btn { padding: 10px 18px; background: #3b82f6; color: white; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: background 0.2s; }
    .calendar-toggle-btn:hover { background: #2563eb; }
    .calendar-toggle-btn.active { background: #1e40af; }
    .calendar-view { max-width: 1200px; margin: 0 auto 40px; display: none; background: white; border-radius: 16px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
    .calendar-view.show { display: block; }
    .calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .calendar-header h2 { margin: 0; font-size: 1.5rem; color: #1f2937; }
    .calendar-nav { display: flex; gap: 8px; }
    .calendar-nav button { padding: 8px 16px; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 8px; cursor: pointer; font-weight: 600; }
    .calendar-nav button:hover { background: #e5e7eb; }
    .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 1px; background: #e5e7eb; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; }
    .calendar-day-header { background: #f9fafb; padding: 12px 8px; text-align: center; font-weight: 700; font-size: 0.85rem; color: #6b7280; text-transform: uppercase; }
    .calendar-day { background: white; min-height: 100px; padding: 8px; position: relative; }
    .calendar-day.empty { background: #fafafa; }
    .calendar-day.today { background: #eff6ff; border: 2px solid #3b82f6; }
    .calendar-day-number { font-weight: 700; color: #374151; margin-bottom: 6px; font-size: 0.9rem; }
    .calendar-day.empty .calendar-day-number { color: #9ca3af; }
    .calendar-event-item { background: #dbeafe; border-left: 3px solid #3b82f6; padding: 4px 6px; margin-bottom: 4px; border-radius: 4px; font-size: 0.75rem; cursor: pointer; transition: background 0.2s; }
    .calendar-event-item:hover { background: #bfdbfe; }
    .calendar-event-time { font-weight: 600; color: #1e40af; display: block; }
    .calendar-event-name { color: #1f2937; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  </style>
</head>

<body>
  <?php include __DIR__ . '/../includes/nav.php'; ?>

  <main class="page-shell">
    <?php flash_render(); ?>

    <nav class="breadcrumb-nav" aria-label="Breadcrumb">
      <ol class="breadcrumb-list">
        <li class="breadcrumb-item"><a href="home_page.php">Home</a></li>
        <li class="breadcrumb-item" aria-current="page">Events</li>
      </ol>
    </nav>

    <section class="collections-hero">
      <h1>Events</h1>
      <div class="collections-hero-underline"></div>
      <p>Browse upcoming and past events related to your collections. View details, RSVP, and keep track of important dates.</p>
      <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
      </div>
    </section>

    <div class="month-bar">
      <div class="month-chip"><?php echo htmlspecialchars($monthLabel); ?></div>
    </div>

    <div class="pill-toggle">
      <a class="<?php echo $status==='upcoming'?'active':''; ?>" href="?<?php echo http_build_query(['status'=>'upcoming','type'=>$typeFilter,'loc'=>$locFilter,'sort'=>$sort,'perPage'=>$perPage]); ?>">Upcoming <?php echo $upcomingCount; ?></a>
      <a class="<?php echo $status==='past'?'active':''; ?>" href="?<?php echo http_build_query(['status'=>'past','type'=>$typeFilter,'loc'=>$locFilter,'sort'=>$sort,'perPage'=>$perPage]); ?>">Past <?php echo $pastCount; ?></a>
    </div>

    <div style="text-align:center; margin-bottom: 12px; display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;">
      <?php if ($isAuth): ?>
        <a class="explore-btn success" href="events_form.php">+ New Event</a>
      <?php endif; ?>
      <button type="button" class="calendar-toggle-btn" id="calendar-toggle-btn">
        <i class="bi bi-calendar3"></i>
        <span id="calendar-toggle-text">Show Calendar</span>
      </button>
    </div>

    <div class="calendar-view" id="calendar-view">
      <div class="calendar-header">
        <h2 id="calendar-month-year">December 2024</h2>
        <div class="calendar-nav">
          <button type="button" id="calendar-prev"><i class="bi bi-chevron-left"></i> Prev</button>
          <button type="button" id="calendar-today">Today</button>
          <button type="button" id="calendar-next">Next <i class="bi bi-chevron-right"></i></button>
        </div>
      </div>
      <div class="calendar-grid" id="calendar-grid">
        <!-- Calendar will be rendered by JavaScript -->
      </div>
    </div>

    <form class="controls" method="GET">
      <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
      <div>
        <label for="type">Type</label>
        <select id="type" name="type" onchange="this.form.submit()">
          <option value="all">All Types</option>
          <?php foreach ($types as $t): ?>
            <option value="<?php echo htmlspecialchars($t); ?>" <?php echo $typeFilter===$t?'selected':''; ?>><?php echo htmlspecialchars($t); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label for="loc">Location</label>
        <select id="loc" name="loc" onchange="this.form.submit()">
          <option value="all">All Locations</option>
          <?php foreach ($locations as $loc): ?>
            <option value="<?php echo htmlspecialchars($loc); ?>" <?php echo $locFilter===$loc?'selected':''; ?>><?php echo htmlspecialchars($loc); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label for="sort">Sort by</label>
        <select id="sort" name="sort" onchange="this.form.submit()">
          <option value="date_old" <?php echo $sort==='date_old'?'selected':''; ?>>Date · Oldest first</option>
          <option value="date_new" <?php echo $sort==='date_new'?'selected':''; ?>>Date · Newest first</option>
          <option value="name" <?php echo $sort==='name'?'selected':''; ?>>Name A-Z</option>
        </select>
      </div>
      <div>
        <label for="perPage">View per page</label>
        <select id="perPage" name="perPage" onchange="this.form.submit()">
          <?php foreach ([5,10,20] as $opt): ?>
            <option value="<?php echo $opt; ?>" <?php echo $perPage==$opt?'selected':''; ?>><?php echo $opt; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <input type="hidden" name="page" value="1">
    </form>

    <div class="actions-row">
      <span>Filter using the tabs above. Logged-in collectors can propose new events.</span>
      <div class="paginate">
        <button <?php echo $page<=1?'disabled':''; ?> onclick="window.location='?<?php echo http_build_query(['status'=>$status,'type'=>$typeFilter,'loc'=>$locFilter,'sort'=>$sort,'perPage'=>$perPage,'page'=>max(1,$page-1)]); ?>'"><i class="bi bi-chevron-left"></i></button>
        <span>Showing <?php echo $total?($offset+1):0; ?>-<?php echo min($offset+$perPage, $total); ?> of <?php echo $total; ?></span>
        <button <?php echo $page>=$pages?'disabled':''; ?> onclick="window.location='?<?php echo http_build_query(['status'=>$status,'type'=>$typeFilter,'loc'=>$locFilter,'sort'=>$sort,'perPage'=>$perPage,'page'=>min($pages,$page+1)]); ?>'"><i class="bi bi-chevron-right"></i></button>
      </div>
    </div>

    <div class="events-grid">
      <?php if ($eventsPage): ?>
        <?php foreach ($eventsPage as $evt): ?>
          <?php
          $eventId = $evt['id'];
          $hostId = $evt['hostUserId'] ?? $evt['host_user_id'] ?? null;
          $isOwner = $isAuth && $hostId && $hostId === $currentUserId;
          $parsedDate = parse_event_datetime($evt['date'] ?? null, $appTimezone);
          $eventDateObj = $parsedDate['date'];
          $hasExplicitTime = $parsedDate['hasTime'];
          $eventHasEnded = false;
          if ($eventDateObj) {
            if ($hasExplicitTime) {
              $eventHasEnded = $eventDateObj <= $now;
            } else {
              $eventHasEnded = $eventDateObj->format('Y-m-d') < $today;
            }
          }
          $eventDateDisplay = '';
          $eventTimeDisplay = '';
          if ($eventDateObj) {
            $eventDateDisplay = $eventDateObj->format('d/m/Y');
            if ($hasExplicitTime) {
              $eventTimeDisplay = $eventDateObj->format('H:i');
            }
          } else {
            $fallbackRaw = trim((string)($evt['date'] ?? ''));
            if ($fallbackRaw !== '') {
              $eventDateDisplay = str_replace('T', ' ', substr($fallbackRaw, 0, 16));
            }
          }
          $canEditEvent = $isOwner && !$eventHasEnded;
          $keyBase = $currentUserId ? (strval($eventId) . '|' . strval($currentUserId)) : null;
          $hasRsvp = $keyBase ? !empty($eventRsvpMap[$keyBase]) : false;
          $collectionsForEvent = $eventCollections[$eventId] ?? [];
          $primaryCollection = $evt['collectionId'] ?? null;
          if ($primaryCollection && !in_array($primaryCollection, $collectionsForEvent, true)) {
            $collectionsForEvent[] = $primaryCollection;
          }
          $userRatingsForEvent = [];
          if ($keyBase) {
            foreach ($collectionsForEvent as $cid) {
              $ratingKey = "{$eventId}|{$currentUserId}|{$cid}";
              if (isset($userCollectionRatings[$ratingKey])) {
                $userRatingsForEvent[$cid] = (int)$userCollectionRatings[$ratingKey];
              }
            }
          }

          $statusClass = $eventHasEnded ? 'past' : 'upcoming';
          $statusLabel = $eventHasEnded ? 'Terminado' : 'Em breve';

          $collectionDetails = [];
          foreach ($collectionsForEvent as $cid) {
            $collection = $collectionMap[$cid] ?? null;
            if (!$collection) continue;
            $stats = $collectionEventStats[$eventId][$cid] ?? null;
            $avgCollection = ($stats && ($stats['count'] ?? 0) > 0)
              ? round($stats['sum'] / $stats['count'], 1)
              : null;
            $collectionDetails[] = [
              'id' => $cid,
              'name' => $collection['name'] ?? 'Coleção',
              'average' => $avgCollection,
              'count' => $stats['count'] ?? 0,
              'userRating' => $userRatingsForEvent[$cid] ?? null
            ];
          }

          $canRate = $hasRsvp && $eventHasEnded && !empty($collectionDetails);
          $eventRatingAverage = ($eventCountMap[$eventId] ?? 0)
            ? round($eventAvgMap[$eventId] / $eventCountMap[$eventId], 1)
            : null;
          $eventRatingCount = $eventCountMap[$eventId] ?? 0;
          $modalData = [
            'eventId' => $eventId,
            'isPast' => (bool)$eventHasEnded,
            'hasRsvp' => (bool)$hasRsvp,
            'canRate' => (bool)$canRate,
            'collections' => $collectionDetails,
            'eventAverage' => $eventRatingAverage,
            'eventAverageCount' => $eventRatingCount,
            'hasExplicitTime' => (bool)$hasExplicitTime
          ];
          $modalDataJson = htmlspecialchars(json_encode($modalData), ENT_QUOTES, 'UTF-8');
          ?>
          <article class="event-card">
            <div class="event-card-top">
              <p class="pill"><?php echo htmlspecialchars($evt['type'] ?? 'Evento'); ?></p>
              <span class="status-chip <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
            </div>
            <h3><?php echo htmlspecialchars($evt['name']); ?></h3>
            <div class="event-actions">
              <button type="button"
                      class="explore-btn small js-view-event"
                      data-name="<?php echo htmlspecialchars($evt['name']); ?>"
                      data-summary="<?php echo htmlspecialchars($evt['summary']); ?>"
                      data-description="<?php echo htmlspecialchars($evt['description']); ?>"
                      data-date="<?php echo htmlspecialchars($eventDateDisplay); ?>"
                      data-time="<?php echo htmlspecialchars($eventTimeDisplay); ?>"
                      data-datetime="<?php echo htmlspecialchars($eventTimeDisplay ? ($eventDateDisplay . ' · ' . $eventTimeDisplay) : $eventDateDisplay); ?>"
                      data-location="<?php echo htmlspecialchars($evt['localization']); ?>"
                      data-type="<?php echo htmlspecialchars($evt['type']); ?>"
                      data-rating="<?php echo $modalDataJson; ?>">
                View Details
              </button>
              <?php if ($isOwner): ?>
                <?php if ($canEditEvent): ?>
                  <a class="explore-btn ghost small" href="events_form.php?id=<?php echo urlencode($evt['id']); ?>">Edit</a>
                <?php endif; ?>
                <form action="events_action.php" method="POST" style="display:inline;">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?php echo htmlspecialchars($evt['id']); ?>">
                  <button type="submit" class="explore-btn ghost danger small" onclick="return confirm('Delete this event?');">Delete</button>
                </form>
              <?php endif; ?>
              <?php if ($eventRatingAverage): ?>
                <div class="avg-stars" title="Rating médio">
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="bi <?php echo ($eventRatingAverage >= $i) ? 'bi-star-fill' : (($eventRatingAverage >= $i-0.5) ? 'bi-star-half' : 'bi-star'); ?>"></i>
                  <?php endfor; ?>
                  <span class="count">(<?php echo $eventRatingCount; ?>)</span>
                </div>
              <?php endif; ?>
              <?php if ($isAuth && !$eventHasEnded): ?>
                <form action="events_action.php" method="POST" style="display:inline;">
                  <input type="hidden" name="action" value="rsvp">
                  <input type="hidden" name="id" value="<?php echo htmlspecialchars($evt['id']); ?>">
                  <button type="submit" class="explore-btn small<?php echo $hasRsvp ? ' success' : ''; ?>">
                    <i class="bi bi-check2-circle"></i> <?php echo $hasRsvp ? 'RSVP Feito' : 'RSVP'; ?>
                  </button>
                </form>
              <?php endif; ?>
              <?php if ($isAuth && $eventHasEnded && !$hasRsvp): ?>
                <span class="badge-muted">Não assististe a este evento.</span>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="muted" style="grid-column:1/-1;">Sem eventos disponíveis.</p>
      <?php endif; ?>
    </div>
  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>
  <script src="../../JS/search-toggle.js"></script>
  <div class="modal-backdrop" id="event-modal">
    <div class="modal-card">
      <div class="modal-header">
        <button class="modal-close" aria-label="Close" onclick="document.getElementById('event-modal').classList.remove('open')">
          <i class="bi bi-x"></i>
        </button>
        <h3 id="modal-title"></h3>
        <span class="modal-type-badge" id="modal-type"></span>
      </div>
      <div class="modal-body">
        <p class="modal-summary" id="modal-summary"></p>
        <p class="modal-description" id="modal-description"></p>
        <div class="modal-info-grid">
          <div class="modal-info-item">
            <div class="modal-info-icon">
              <i class="bi bi-calendar-event"></i>
            </div>
            <div class="modal-info-content">
              <div class="modal-info-label">Date</div>
              <div class="modal-info-value" id="modal-date"></div>
            </div>
          </div>
          <div class="modal-info-item" id="modal-time-row" hidden>
            <div class="modal-info-icon">
              <i class="bi bi-clock-history"></i>
            </div>
            <div class="modal-info-content">
              <div class="modal-info-label">Time</div>
              <div class="modal-info-value" id="modal-time"></div>
            </div>
          </div>
          <div class="modal-info-item">
            <div class="modal-info-icon">
              <i class="bi bi-geo-alt-fill"></i>
            </div>
            <div class="modal-info-content">
              <div class="modal-info-label">Place</div>
              <div class="modal-info-value" id="modal-location"></div>
            </div>
          </div>
        </div>
        <div class="modal-ratings" id="modal-ratings"></div>
      </div>
    </div>
  </div>
  </script>
  <script>
    // Modal de detalhes do evento
    (function() {
      const modal = document.getElementById('event-modal');
      if (!modal) return;

      const ratingsContainer = document.getElementById('modal-ratings');

      function appendStars(container, value) {
        if (typeof value !== 'number' || Number.isNaN(value)) return;
        for (let i = 1; i <= 5; i++) {
          const icon = document.createElement('i');
          if (value >= i) {
            icon.className = 'bi bi-star-fill';
          } else if (value >= i - 0.5) {
            icon.className = 'bi bi-star-half';
          } else {
            icon.className = 'bi bi-star';
          }
          container.appendChild(icon);
        }
      }

      function addMessage(text) {
        if (!ratingsContainer) return;
        const message = document.createElement('div');
        message.className = 'modal-rating-message';
        message.textContent = text;
        ratingsContainer.appendChild(message);
      }

      function renderRatings(payload) {
        if (!ratingsContainer) return;
        ratingsContainer.innerHTML = '';

        if (!payload) {
          addMessage('Este evento ainda não tem dados de avaliação.');
          return;
        }

        const eventAverage = typeof payload.eventAverage === 'number' ? payload.eventAverage : null;
        const eventAverageCount = typeof payload.eventAverageCount === 'number' ? payload.eventAverageCount : 0;
        const collections = Array.isArray(payload.collections) ? payload.collections : [];
        const isPast = !!payload.isPast;
        const hasRsvp = !!payload.hasRsvp;
        const canRate = !!payload.canRate;

        if (eventAverage) {
          const overview = document.createElement('div');
          overview.className = 'modal-rating-overview';
          const left = document.createElement('span');
          left.textContent = 'Rating médio do evento';
          overview.appendChild(left);
          const right = document.createElement('span');
          right.className = 'rating-badge';
          appendStars(right, eventAverage);
          const avgValue = document.createElement('span');
          avgValue.className = 'avg-value';
          avgValue.textContent = `${eventAverage.toFixed(1)}/5`;
          right.appendChild(avgValue);
          const count = document.createElement('span');
          count.className = 'count';
          count.textContent = `(${eventAverageCount})`;
          right.appendChild(count);
          overview.appendChild(right);
          ratingsContainer.appendChild(overview);
        }

        const messages = [];
        if (!isPast) {
          messages.push('Este evento ainda não aconteceu. As avaliações ficam disponíveis após a data.');
        }
        if (isPast && !hasRsvp) {
          messages.push('Só participantes que fizeram RSVP podem avaliar as coleções.');
        }
        if (collections.length === 0) {
          messages.push('Este evento não tem coleções associadas para avaliação.');
        }
        messages.forEach(addMessage);

        if (collections.length === 0) {
          return;
        }

        const statsWrapper = document.createElement('div');
        statsWrapper.className = 'modal-rating-stats';

        collections.forEach(function(item) {
          const card = document.createElement('div');
          card.className = 'modal-rating-card';

          const header = document.createElement('div');
          header.className = 'modal-rating-card-header';

          const name = document.createElement('span');
          name.className = 'collection-name';
          name.textContent = item.name || 'Coleção';
          header.appendChild(name);

          const badge = document.createElement('span');
          badge.className = 'rating-badge';
          if (typeof item.average === 'number' && !Number.isNaN(item.average) && (item.count || 0) > 0) {
            appendStars(badge, item.average);
            const avgValue = document.createElement('span');
            avgValue.className = 'avg-value';
            avgValue.textContent = `${item.average.toFixed(1)}/5`;
            badge.appendChild(avgValue);
            const badgeCount = document.createElement('span');
            badgeCount.className = 'count';
            badgeCount.textContent = `(${item.count})`;
            badge.appendChild(badgeCount);
          } else {
            badge.textContent = 'Sem avaliações';
          }
          header.appendChild(badge);
          card.appendChild(header);

          const userHasRating = typeof item.userRating === 'number' && !Number.isNaN(item.userRating);

          if (canRate) {
            const form = document.createElement('form');
            form.className = 'modal-rating-form';
            form.method = 'POST';
            form.action = 'events_action.php';

            const actionField = document.createElement('input');
            actionField.type = 'hidden';
            actionField.name = 'action';
            actionField.value = 'rate';
            form.appendChild(actionField);

            const eventField = document.createElement('input');
            eventField.type = 'hidden';
            eventField.name = 'id';
            eventField.value = payload.eventId || '';
            form.appendChild(eventField);

            const collectionField = document.createElement('input');
            collectionField.type = 'hidden';
            collectionField.name = 'collection_id';
            collectionField.value = item.id || '';
            form.appendChild(collectionField);

            const label = document.createElement('label');
            label.textContent = 'Avaliar:';
            label.style.fontWeight = '600';
            label.style.fontSize = '0.9rem';
            label.style.color = '#374151';
            form.appendChild(label);

            const select = document.createElement('select');
            select.name = 'rating';
            select.setAttribute('aria-label', `Classificação para ${item.name || 'a coleção'}`);

            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Selecionar...';
            select.appendChild(placeholder);

            for (let i = 1; i <= 5; i++) {
              const option = document.createElement('option');
              option.value = String(i);
              option.textContent = `${i} estrelas`;
              if (userHasRating && item.userRating === i) {
                option.selected = true;
              }
              select.appendChild(option);
            }

            form.appendChild(select);

            const button = document.createElement('button');
            button.type = 'submit';
            button.className = 'explore-btn small';
            button.textContent = 'Guardar';
            form.appendChild(button);

            card.appendChild(form);
          }

          if (userHasRating) {
            const userRow = document.createElement('div');
            userRow.className = 'modal-rating-user';
            const label = document.createElement('span');
            label.textContent = 'A sua avaliação:';
            userRow.appendChild(label);
            appendStars(userRow, item.userRating);
            card.appendChild(userRow);
          }

          statsWrapper.appendChild(card);
        });

        ratingsContainer.appendChild(statsWrapper);
      }

      const buttons = document.querySelectorAll('.js-view-event');

      buttons.forEach(function(btn) {
        btn.addEventListener('click', function() {
          const name = btn.getAttribute('data-name') || '';
          const summary = btn.getAttribute('data-summary') || '';
          const description = btn.getAttribute('data-description') || '';
          const date = btn.getAttribute('data-date') || '';
          const time = btn.getAttribute('data-time') || '';
          const combined = btn.getAttribute('data-datetime') || '';
          const location = btn.getAttribute('data-location') || '';
          const type = btn.getAttribute('data-type') || '';
          const ratingRaw = btn.getAttribute('data-rating') || '';

          document.getElementById('modal-title').textContent = name;
          document.getElementById('modal-type').textContent = type;
          document.getElementById('modal-summary').textContent = summary;
          document.getElementById('modal-description').textContent = description;
          const modalDate = document.getElementById('modal-date');
          if (modalDate) {
            modalDate.textContent = date || combined;
          }
          const modalTimeRow = document.getElementById('modal-time-row');
          const modalTime = document.getElementById('modal-time');
          if (modalTimeRow && modalTime) {
            if (time) {
              modalTimeRow.hidden = false;
              modalTime.textContent = time;
            } else {
              modalTimeRow.hidden = true;
              modalTime.textContent = '';
              if (modalDate && combined && !date) {
                modalDate.textContent = combined;
              }
            }
          }
          document.getElementById('modal-location').textContent = location;

          let payload = null;
          if (ratingRaw) {
            try {
              payload = JSON.parse(ratingRaw);
            } catch (err) {
              payload = null;
            }
          }

          renderRatings(payload);
          modal.classList.add('open');
        });
      });

      // Fechar ao clicar fora do modal
      modal.addEventListener('click', function(e) {
        if (e.target === modal) {
          modal.classList.remove('open');
        }
      });

      // Fechar com ESC
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('open')) {
          modal.classList.remove('open');
        }
      });
    })();

    // Calendar functionality
    (function() {
      const calendarView = document.getElementById('calendar-view');
      const calendarGrid = document.getElementById('calendar-grid');
      const calendarMonthYear = document.getElementById('calendar-month-year');
      const calendarToggleBtn = document.getElementById('calendar-toggle-btn');
      const calendarToggleText = document.getElementById('calendar-toggle-text');
      const calendarPrevBtn = document.getElementById('calendar-prev');
      const calendarTodayBtn = document.getElementById('calendar-today');
      const calendarNextBtn = document.getElementById('calendar-next');

      const monthsPT = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
      const daysPT = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

      let currentDate = new Date();
      let allEvents = <?php echo json_encode(array_values($events)); ?>;

      function parseEventDate(dateStr) {
        if (!dateStr) return null;
        const date = new Date(dateStr.replace(' ', 'T'));
        return isNaN(date.getTime()) ? null : date;
      }

      function isFutureEvent(dateStr) {
        const eventDate = parseEventDate(dateStr);
        if (!eventDate) return false;
        const now = new Date();
        return eventDate >= now;
      }

      function renderCalendar(year, month) {
        calendarGrid.innerHTML = '';
        calendarMonthYear.textContent = `${monthsPT[month]} ${year}`;

        // Day headers
        daysPT.forEach(day => {
          const header = document.createElement('div');
          header.className = 'calendar-day-header';
          header.textContent = day;
          calendarGrid.appendChild(header);
        });

        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const firstDayOfWeek = firstDay.getDay();
        const daysInMonth = lastDay.getDate();

        const today = new Date();
        const isCurrentMonth = today.getFullYear() === year && today.getMonth() === month;

        // Empty cells for days before month starts
        for (let i = 0; i < firstDayOfWeek; i++) {
          const emptyDay = document.createElement('div');
          emptyDay.className = 'calendar-day empty';
          calendarGrid.appendChild(emptyDay);
        }

        // Days of the month
        for (let day = 1; day <= daysInMonth; day++) {
          const dayCell = document.createElement('div');
          dayCell.className = 'calendar-day';
          
          if (isCurrentMonth && day === today.getDate()) {
            dayCell.classList.add('today');
          }

          const dayNumber = document.createElement('div');
          dayNumber.className = 'calendar-day-number';
          dayNumber.textContent = day;
          dayCell.appendChild(dayNumber);

          // Find events for this day
          const dayDate = new Date(year, month, day);
          const dayDateStr = dayDate.toISOString().split('T')[0];

          const dayEvents = allEvents.filter(evt => {
            if (!isFutureEvent(evt.date)) return false;
            const evtDate = parseEventDate(evt.date);
            if (!evtDate) return false;
            const evtDateStr = evtDate.toISOString().split('T')[0];
            return evtDateStr === dayDateStr;
          });

          dayEvents.forEach(evt => {
            const evtDate = parseEventDate(evt.date);
            const eventItem = document.createElement('div');
            eventItem.className = 'calendar-event-item';
            
            const timeSpan = document.createElement('span');
            timeSpan.className = 'calendar-event-time';
            timeSpan.textContent = evtDate.toLocaleTimeString('pt-PT', { hour: '2-digit', minute: '2-digit' });
            eventItem.appendChild(timeSpan);

            const nameSpan = document.createElement('span');
            nameSpan.className = 'calendar-event-name';
            nameSpan.textContent = evt.name || 'Evento';
            nameSpan.title = evt.name || 'Evento';
            eventItem.appendChild(nameSpan);

            eventItem.addEventListener('click', function() {
              // Find the corresponding button in the grid and trigger click
              const buttons = document.querySelectorAll('.js-view-event');
              buttons.forEach(btn => {
                if (btn.getAttribute('data-name') === evt.name) {
                  btn.click();
                }
              });
            });

            dayCell.appendChild(eventItem);
          });

          calendarGrid.appendChild(dayCell);
        }
      }

      calendarToggleBtn.addEventListener('click', function() {
        const isVisible = calendarView.classList.contains('show');
        if (isVisible) {
          calendarView.classList.remove('show');
          calendarToggleBtn.classList.remove('active');
          calendarToggleText.textContent = 'Show Calendar';
        } else {
          calendarView.classList.add('show');
          calendarToggleBtn.classList.add('active');
          calendarToggleText.textContent = 'Hide Calendar';
          renderCalendar(currentDate.getFullYear(), currentDate.getMonth());
        }
      });

      calendarPrevBtn.addEventListener('click', function() {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar(currentDate.getFullYear(), currentDate.getMonth());
      });

      calendarTodayBtn.addEventListener('click', function() {
        currentDate = new Date();
        renderCalendar(currentDate.getFullYear(), currentDate.getMonth());
      });

      calendarNextBtn.addEventListener('click', function() {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar(currentDate.getFullYear(), currentDate.getMonth());
      });
    })();
  </script>
</body>

</html>

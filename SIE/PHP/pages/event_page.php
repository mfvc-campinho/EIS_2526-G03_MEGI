<?php
session_start();
require_once __DIR__ . '/../includes/data_loader.php';
require_once __DIR__ . '/../includes/flash.php';

$data = load_app_data($mysqli);
$mysqli->close();
$events = $data['events'] ?? [];
$eventsUsers = $data['eventsUsers'] ?? [];
$collections = $data['collections'] ?? [];
$collectionEvents = $data['collectionEvents'] ?? [];

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
$createAllowedFromDate = (clone $now)->modify('+1 day')->format('Y-m-d');

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

$eventsForCalendar = array_map(function ($evt) use ($currentUserId, $eventRsvpMap) {
  $eid = $evt['id'] ?? null;
  $key = ($eid && $currentUserId) ? "{$eid}|{$currentUserId}" : null;
  $evt['hasUserRsvp'] = $key ? !empty($eventRsvpMap[$key]) : false;
  return $evt;
}, $events);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Events • GoodCollections</title>
  <link rel="stylesheet" href="../../CSS/general.css">
  <link rel="stylesheet" href="../../CSS/events.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../../CSS/christmas.css">
  <script src="../../JS/theme-toggle.js"></script>
  <script src="../../JS/christmas-theme.js"></script>
  <link rel="stylesheet" href="../../CSS/event_page_inline.css">

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

    

    <div style="text-align:center; margin-bottom: 12px; display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; align-items: center;">
      <?php if ($isAuth): ?>
        <a class="explore-btn success" href="events_form.php"><i class="bi bi-calendar-plus"></i> New Event</a>
      <?php endif; ?>
      <button type="button" class="calendar-toggle-btn active" id="calendar-toggle-btn" aria-expanded="true" aria-controls="calendar-view">
        <i class="bi bi-calendar3"></i>
        <span id="calendar-toggle-text">Hide Calendar</span>
      </button>
    </div>

    <div class="calendar-view show" id="calendar-view">
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

    <section class="event-options-panel">
      <div class="event-options-head">
        <div>
          <h3>Event options</h3>
          <p>Switch between upcoming or past events before applying the filters below.</p>
        </div>
      </div>
      <div class="pill-toggle">
        <a class="<?php echo $status==='upcoming'?'active':''; ?>" href="?<?php echo http_build_query(['status'=>'upcoming','type'=>$typeFilter,'loc'=>$locFilter,'sort'=>$sort,'perPage'=>$perPage]); ?>">Upcoming (<?php echo $upcomingCount; ?>)</a>
        <a class="<?php echo $status==='past'?'active':''; ?>" href="?<?php echo http_build_query(['status'=>'past','type'=>$typeFilter,'loc'=>$locFilter,'sort'=>$sort,'perPage'=>$perPage]); ?>">Past (<?php echo $pastCount; ?>)</a>
      </div>
    </section>

    <div class="top-controls events-filters">
      <div class="left">
        <form id="events-filters-form" class="filters-form" method="GET">
          <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">

          <div class="filter-chip filter-chip--select">
            <label class="filter-chip__label" for="events-type-select">
              <i class="bi bi-tags"></i>
              <span>Type</span>
            </label>
            <select id="events-type-select" name="type" class="filter-chip__select" onchange="gcSubmitWithScroll(this.form)">
              <option value="all">All Types</option>
              <?php foreach ($types as $t): ?>
                <option value="<?php echo htmlspecialchars($t); ?>" <?php echo $typeFilter===$t?'selected':''; ?>><?php echo htmlspecialchars($t); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="filter-chip filter-chip--select">
            <label class="filter-chip__label" for="events-loc-select">
              <i class="bi bi-geo-alt"></i>
              <span>Location</span>
            </label>
            <select id="events-loc-select" name="loc" class="filter-chip__select" onchange="gcSubmitWithScroll(this.form)">
              <option value="all">All Locations</option>
              <?php foreach ($locations as $loc): ?>
                <option value="<?php echo htmlspecialchars($loc); ?>" <?php echo $locFilter===$loc?'selected':''; ?>><?php echo htmlspecialchars($loc); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="filter-chip filter-chip--select">
            <label class="filter-chip__label" for="events-sort-select">
              <i class="bi bi-funnel"></i>
              <span>Sort by</span>
            </label>
            <select id="events-sort-select" name="sort" class="filter-chip__select" onchange="gcSubmitWithScroll(this.form)">
              <option value="date_old" <?php echo $sort==='date_old'?'selected':''; ?>>Date · Nearest</option>
              <option value="date_new" <?php echo $sort==='date_new'?'selected':''; ?>>Date · Farthest</option>
              <option value="name" <?php echo $sort==='name'?'selected':''; ?>>Name A-Z</option>
            </select>
          </div>

          <div class="filter-chip filter-chip--compact filter-chip--select">
            <label class="filter-chip__label" for="events-per-page-select">
              <i class="bi bi-eye"></i>
              <span>Show</span>
            </label>
            <select id="events-per-page-select" name="perPage" class="filter-chip__select" onchange="gcSubmitWithScroll(this.form)">
              <?php foreach ([5,10,20] as $opt): ?>
                <option value="<?php echo $opt; ?>" <?php echo $perPage==$opt?'selected':''; ?>><?php echo $opt; ?></option>
              <?php endforeach; ?>
            </select>
            <span class="filter-chip__hint">per page</span>
          </div>

          <input type="hidden" name="page" value="1">
        </form>
        <p class="events-filters__note">Filter using the tabs above. Logged-in collectors can propose new events.</p>
      </div>

      <div class="paginate">
        <button <?php echo $page<=1?'disabled':''; ?> onclick="gcRememberScroll('?<?php echo http_build_query(['status'=>$status,'type'=>$typeFilter,'loc'=>$locFilter,'sort'=>$sort,'perPage'=>$perPage,'page'=>max(1,$page-1)]); ?>')"><i class="bi bi-chevron-left"></i></button>
        <span>Showing <?php echo $total?($offset+1):0; ?>-<?php echo min($offset+$perPage, $total); ?> of <?php echo $total; ?></span>
        <button <?php echo $page>=$pages?'disabled':''; ?> onclick="gcRememberScroll('?<?php echo http_build_query(['status'=>$status,'type'=>$typeFilter,'loc'=>$locFilter,'sort'=>$sort,'perPage'=>$perPage,'page'=>min($pages,$page+1)]); ?>')"><i class="bi bi-chevron-right"></i></button>
      </div>
    </div>

    <div class="events-grid" id="eventsList">
      <?php if ($eventsPage): ?>
        <?php foreach ($eventsPage as $evt): ?>
          <?php
          $eventId = $evt['id'];
          $hostId = $evt['hostUserId'] ?? $evt['host_user_id'] ?? null;
          if (!$hostId && !empty($evt['collectionId'])) {
            $hostId = $collectionMap[$evt['collectionId']]['ownerId'] ?? null;
          }
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
          $rawCost = $evt['cost'] ?? null;
          $costValue = ($rawCost === '' || $rawCost === null) ? null : (float)$rawCost;
          $costLabel = ($costValue !== null && $costValue > 0)
            ? '€' . number_format($costValue, 2, ',', '.')
            : 'Free entrance';
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
          $statusLabel = $eventHasEnded ? 'Ended' : 'Soon';

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
              'name' => $collection['name'] ?? 'Collection',
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
          <?php // Atribui um id único ao cartão para conseguirmos regressar ao mesmo ponto após ações como RSVP. ?>
          <article id="event-card-<?php echo htmlspecialchars($eventId); ?>" class="event-card js-event-card"
                   data-name="<?php echo htmlspecialchars($evt['name']); ?>"
                   data-summary="<?php echo htmlspecialchars($evt['summary']); ?>"
                   data-description="<?php echo htmlspecialchars($evt['description']); ?>"
                   data-date="<?php echo htmlspecialchars($eventDateDisplay); ?>"
                   data-time="<?php echo htmlspecialchars($eventTimeDisplay); ?>"
                   data-datetime="<?php echo htmlspecialchars($eventTimeDisplay ? ($eventDateDisplay . ' · ' . $eventTimeDisplay) : $eventDateDisplay); ?>"
                   data-location="<?php echo htmlspecialchars($evt['localization']); ?>"
                   data-type="<?php echo htmlspecialchars($evt['type']); ?>"
                   data-cost="<?php echo htmlspecialchars($costLabel); ?>"
                   data-rating="<?php echo $modalDataJson; ?>">
            <div class="event-card-top">
              <p class="pill"><?php echo htmlspecialchars($evt['type'] ?? 'Event'); ?></p>
              <span class="status-chip <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
            </div>
            <h3><?php echo htmlspecialchars($evt['name']); ?></h3>
            <?php if ($eventDateDisplay): ?>
              <div class="event-meta-row">
                <i class="bi bi-calendar-event"></i>
                <span>
                  <?php echo htmlspecialchars($eventDateDisplay); ?>
                  <?php if ($eventTimeDisplay): ?>
                    · <span class="event-meta-time"><?php echo htmlspecialchars($eventTimeDisplay); ?></span>
                  <?php endif; ?>
                </span>
              </div>
            <?php endif; ?>
            <?php if (!empty($evt['localization'])): ?>
              <div class="event-meta-row">
                <i class="bi bi-geo-alt"></i>
                <?php $locText = trim($evt['localization']); ?>
                <?php if ($locText !== ''): ?>
                  <a class="event-location-link"
                     href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($locText); ?>"
                     target="_blank"
                     rel="noopener noreferrer"
                     aria-label="Open <?php echo htmlspecialchars($locText); ?> on Google Maps">
                    <?php echo htmlspecialchars($locText); ?>
                  </a>
                <?php else: ?>
                  <span><?php echo htmlspecialchars($locText); ?></span>
                <?php endif; ?>
              </div>
            <?php endif; ?>
            <div class="event-meta-row">
              <i class="bi bi-cash-coin"></i>
              <span><?php echo htmlspecialchars($costLabel); ?></span>
            </div>
            <div class="event-actions">
              <?php if ($isOwner): ?>
                <?php if ($canEditEvent): ?>
                  <a class="explore-btn ghost small" href="events_form.php?id=<?php echo urlencode($evt['id']); ?>">Edit</a>
                  <form action="events_action.php" method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($evt['id']); ?>">
                    <button type="submit" class="explore-btn ghost danger small" onclick="return confirm('Delete this event?');">Delete</button>
                  </form>
                
                <?php endif; ?>
              <?php endif; ?>
              <?php if ($eventRatingAverage): ?>
                <div class="avg-stars" title="Average rating">
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
                  <?php // Envia a âncora de retorno para que o servidor saiba para onde voltar após o POST. ?>
                  <input type="hidden" name="return_target" value="#event-card-<?php echo htmlspecialchars($eventId); ?>">
                  <button type="submit" class="explore-btn small<?php echo $hasRsvp ? ' success' : ''; ?>">
                    <i class="bi bi-check2-circle"></i> <?php echo $hasRsvp ? 'RSVP confirmed' : 'RSVP'; ?>
                  </button>
                </form>
              <?php endif; ?>
              <?php if ($isAuth && $eventHasEnded && !$hasRsvp): ?>
                <span class="badge-muted">You did not attend this event.</span>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="muted" style="grid-column:1/-1;">No available events.</p>
      <?php endif; ?>
    </div>
  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>
  <script src="../../JS/search-toggle.js"></script>
  <script src="../../JS/gc-scroll-restore.js"></script>
  <script>
    window.eventPageData = {
      events: <?php echo json_encode(array_values($eventsForCalendar)); ?>,
      canCreateEvents: <?php echo $isAuth ? 'true' : 'false'; ?>,
      createAllowedFromDate: <?php echo json_encode($createAllowedFromDate); ?>,
      eventsFormUrl: 'events_form.php'
    };
  </script>
  <script src="../../JS/event_page.js"></script>

</body>

</html>

<?php
session_start();
require_once __DIR__ . '/../includes/data_loader.php';
require_once __DIR__ . '/../includes/flash.php';

$data = load_app_data($mysqli);
$mysqli->close();
$events = $data['events'] ?? [];
$eventsUsers = $data['eventsUsers'] ?? [];
$eventsUsers = $data['eventsUsers'] ?? [];

$isAuth = !empty($_SESSION['user']);
$currentUserId = $_SESSION['user']['id'] ?? null;

$status = $_GET['status'] ?? 'upcoming'; // upcoming | past
$typeFilter = $_GET['type'] ?? 'all';
$locFilter = $_GET['loc'] ?? 'all';
$sort = $_GET['sort'] ?? 'date_old'; // date_old | date_new | name
$perPage = max(1, (int)($_GET['perPage'] ?? 10));
$page = max(1, (int)($_GET['page'] ?? 1));
$today = date('Y-m-d');

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
  $date = substr($ev['date'] ?? '', 0, 10);
  if ($date && $date >= $today) $upcomingCount++;
  else $pastCount++;
}

// Filter
$filtered = array_filter($events, function ($ev) use ($status, $today, $typeFilter, $locFilter) {
  $date = substr($ev['date'] ?? '', 0, 10);
  $isUpcoming = $date && $date >= $today;
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

// Map eventId+userId => entry (rating / rsvp)
$eventUserMap = [];
$eventAvgMap = [];
$eventCountMap = [];
foreach ($eventsUsers as $eu) {
  $eid = $eu['eventId'] ?? null;
  $uid = $eu['userId'] ?? null;
  if (!$eid || !$uid) continue;
  $key = strval($eid) . '|' . strval($uid);
  $eventUserMap[$key] = $eu;
  if (isset($eu['rating']) && $eu['rating'] !== null) {
    if (!isset($eventAvgMap[$eid])) { $eventAvgMap[$eid] = 0; $eventCountMap[$eid] = 0; }
    $eventAvgMap[$eid] += (float)$eu['rating'];
    $eventCountMap[$eid] += 1;
  }
}

// Map eventId+userId => entry (rating / rsvp)
$eventUserMap = [];
foreach ($eventsUsers as $eu) {
  $eid = $eu['eventId'] ?? null;
  $uid = $eu['userId'] ?? null;
  if (!$eid || !$uid) continue;
  $eventUserMap["{$eid}|{$uid}"] = $eu;
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
  <script src="../../JS/theme-toggle.js"></script>
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
    .events-grid { max-width: 1100px; margin: 0 auto 40px; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px; }
    .event-card { background: #fff; border-radius: 18px; padding: 16px 18px; box-shadow: 0 14px 30px rgba(0,0,0,0.08); display: flex; flex-direction: column; gap: 10px; }
    .event-card h3 { margin: 0; }
    .event-meta { list-style: none; padding: 0; margin: 0; color: #6b7280; }
    .event-meta li { margin-bottom: 6px; display: flex; align-items: center; gap: 6px; }
    .event-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 4px; align-items:center; }
    .badge-muted { color: #6b7280; font-weight: 600; }
    .hero-subtle { font-size: 0.95rem; margin-top: -4px; }
    .clear-btn { background: #fff0f0; color: #c53030; border: 1px solid #f5c2c0; border-radius: 12px; padding: 10px 16px; font-weight: 600; cursor: pointer; }
    .stars { display:flex; gap:4px; }
    .star-btn { border:none; background:transparent; cursor:pointer; padding:4px; color:#f5b301; font-size:18px; }
    .star-btn i { pointer-events:none; }
    .star-btn.active i { color:#f59e0b; }
    .avg-stars { display:flex; gap:4px; color:#f5b301; align-items:center; font-weight:600; }
    .avg-stars .count { color:#6b7280; font-size:0.9rem; margin-left:4px; }
    .user-stars { display:flex; gap:4px; color:#f5b301; align-items:center; font-weight:600; }
    .user-stars .label { color:#374151; font-size:0.9rem; margin-right:6px; }
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

    <div style="text-align:center; margin-bottom: 12px;">
      <?php if ($isAuth): ?>
        <a class="explore-btn success" href="events_form.php">+ New Event</a>
      <?php endif; ?>
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
          $hostId = $evt['hostUserId'] ?? $evt['host_user_id'] ?? null;
          $isOwner = $isAuth && $hostId && $hostId === $currentUserId;
          $eventDate = substr($evt['date'] ?? '', 0, 10);
          $isPast = $eventDate && $eventDate <= date('Y-m-d'); // Event is today or in the past
          $today = date('Y-m-d');
          $eventHasStarted = $eventDate && $eventDate <= $today; // Event has started (today or before)
          $key = $currentUserId ? (strval($evt['id']) . '|' . strval($currentUserId)) : null;
          $userEntry = $currentUserId && $key ? ($eventUserMap[$key] ?? null) : null;
          $hasRsvp = $userEntry && !empty($userEntry['rsvp']);
          $rating = $userEntry['rating'] ?? null;
          // Can only rate PAST events (not just started, must be fully past) AND must have RSVP'd
          $canRate = $hasRsvp && $isPast;
          ?>
          <article class="event-card">
            <p class="pill"><?php echo htmlspecialchars($evt['type'] ?? 'Evento'); ?></p>
            <h3><?php echo htmlspecialchars($evt['name']); ?></h3>
            <p class="muted"><?php echo htmlspecialchars($evt['summary']); ?></p>
            <ul class="event-meta">
              <li><i class="bi bi-calendar-event"></i> <?php echo htmlspecialchars(substr($evt['date'], 0, 10)); ?></li>
              <li><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($evt['localization']); ?></li>
            </ul>
            <?php if ($isAuth && $rating !== null && $canRate): ?>
              <div class="user-stars" title="O seu rating">
                <span class="label">O seu rating:</span>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <i class="bi <?php echo ($rating >= $i) ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                <?php endfor; ?>
              </div>
            <?php endif; ?>
            <div class="event-actions">
              <button type="button"
                      class="explore-btn small js-view-event"
                      data-name="<?php echo htmlspecialchars($evt['name']); ?>"
                      data-summary="<?php echo htmlspecialchars($evt['summary']); ?>"
                      data-description="<?php echo htmlspecialchars($evt['description']); ?>"
                      data-date="<?php echo htmlspecialchars(substr($evt['date'], 0, 16)); ?>"
                      data-location="<?php echo htmlspecialchars($evt['localization']); ?>"
                      data-type="<?php echo htmlspecialchars($evt['type']); ?>">
                View Details
              </button>
              <?php if ($isOwner): ?>
                <a class="explore-btn ghost small" href="events_form.php?id=<?php echo urlencode($evt['id']); ?>">Edit</a>
                <form action="events_action.php" method="POST" style="display:inline;">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?php echo htmlspecialchars($evt['id']); ?>">
                  <button type="submit" class="explore-btn ghost danger small" onclick="return confirm('Delete this event?');">Delete</button>
                </form>
              <?php endif; ?>
              <?php
                $avg = ($eventCountMap[$evt['id']] ?? 0) ? ($eventAvgMap[$evt['id']] / $eventCountMap[$evt['id']]) : 0;
                $count = $eventCountMap[$evt['id']] ?? 0;
              ?>
              <?php if ($avg > 0): ?>
                <div class="avg-stars" title="Rating médio">
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="bi <?php echo ($avg >= $i) ? 'bi-star-fill' : (($avg >= $i-0.5) ? 'bi-star-half' : 'bi-star'); ?>"></i>
                  <?php endfor; ?>
                  <span class="count">(<?php echo $count; ?>)</span>
                </div>
              <?php endif; ?>
              <?php if ($isAuth): ?>
                <?php if ($isPast): ?>
                  <!-- Past event: Show star rating form if RSVP'd -->
                  <?php if ($hasRsvp): ?>
                    <form method="POST" action="events_action.php" style="display: inline;">
                      <input type="hidden" name="action" value="rate">
                      <input type="hidden" name="id" value="<?php echo htmlspecialchars($evt['id'], ENT_QUOTES); ?>">
                      <div style="display: flex; align-items: center; gap: 12px;">
                        <label for="rating-<?php echo htmlspecialchars($evt['id'], ENT_QUOTES); ?>" style="font-weight: 600; font-size: 0.9rem; color: #4b5563;">Avaliar:</label>
                        <select name="rating" id="rating-<?php echo htmlspecialchars($evt['id'], ENT_QUOTES); ?>" style="padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem;">
                          <option value="">Selecionar...</option>
                          <option value="1" <?php echo $rating == 1 ? 'selected' : ''; ?>>1 ⭐</option>
                          <option value="2" <?php echo $rating == 2 ? 'selected' : ''; ?>>2 ⭐⭐</option>
                          <option value="3" <?php echo $rating == 3 ? 'selected' : ''; ?>>3 ⭐⭐⭐</option>
                          <option value="4" <?php echo $rating == 4 ? 'selected' : ''; ?>>4 ⭐⭐⭐⭐</option>
                          <option value="5" <?php echo $rating == 5 ? 'selected' : ''; ?>>5 ⭐⭐⭐⭐⭐</option>
                        </select>
                        <button type="submit" class="explore-btn small" style="padding: 6px 12px;">Guardar</button>
                      </div>
                    </form>
                  <?php else: ?>
                    <span class="badge-muted">Não assististe a este evento.</span>
                  <?php endif; ?>
                  <!-- Show current user rating if they've already rated -->
                  <?php if ($isAuth && $rating !== null): ?>
                    <div class="user-stars" title="O seu rating">
                      <span class="label">O seu rating:</span>
                      <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="bi <?php echo ($rating >= $i) ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                      <?php endfor; ?>
                    </div>
                  <?php endif; ?>
                <?php else: ?>
                  <!-- Upcoming event: Show RSVP button only, no rating UI -->
                  <form action="events_action.php" method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="rsvp">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($evt['id']); ?>">
                    <button type="submit" class="explore-btn small<?php echo $hasRsvp ? ' success' : ''; ?>">
                      <i class="bi bi-check2-circle"></i> <?php echo $hasRsvp ? 'RSVP Feito' : 'RSVP'; ?>
                    </button>
                  </form>
                <?php endif; ?>
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
      </div>
    </div>
  </div>
  </script>
  <script>
  </script>

  <script>
  // torna a data do evento disponível em JS
  window.EVENT_DATE = "<?php echo htmlspecialchars($event['date']); ?>";
  window.EVENT_NAME = "<?php echo htmlspecialchars($event['name']); ?>";
</script>

  <script>
  (function () {
    // só corre se estivermos numa página com EVENT_DATE definido
    if (!window.EVENT_DATE) return;

    const navEventsLink = document.getElementById("nav-events-link");
    if (!navEventsLink) return;

    const eventDate = new Date(window.EVENT_DATE);        // YYYY-MM-DD
    const today = new Date();

    // limpar horas (para evitar diferenças por fuso horário)
    eventDate.setHours(0, 0, 0, 0);
    today.setHours(0, 0, 0, 0);

    const MS_PER_DAY = 24 * 60 * 60 * 1000;
    const diffDays = Math.round((eventDate - today) / MS_PER_DAY);

    // se faltar entre 0 e 6 dias (inclui hoje), alerta + cor laranja
    if (diffDays >= 0 && diffDays <= 6) {
      // alerta
      const eventName = window.EVENT_NAME || "este evento";
      const daysText = diffDays === 1 ? "dia" : "dias";
      alert("Atenção! Faltam " + diffDays + " " + daysText + " para " + eventName + ".");

      // mudar cor do link de eventos no nav para laranja
      navEventsLink.style.color = "#f97316";
      navEventsLink.style.fontWeight = "700";
    }
  })();
</script>

</body>

</html>

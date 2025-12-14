<?php
session_start();
require_once __DIR__ . '/../includes/data_loader.php';
require_once __DIR__ . '/../includes/flash.php';
$data = load_app_data($mysqli);
$users = $data['users'] ?? [];
$collections = $data['collections'] ?? [];
$items = $data['items'] ?? [];
$events = $data['events'] ?? [];
$eventsUsers = $data['eventsUsers'] ?? [];

$statsUsers = count($users);
$statsCollections = count($collections);
$statsItems = count($items);
$statsEvents = count($events);

$mysqli->close();

$collections = $data['collections'] ?? [];
$events = $data['events'] ?? [];
$users = $data['users'] ?? [];
$collectionItems = $data['collectionItems'] ?? [];
$items = $data['items'] ?? [];
$isAuthenticated = !empty($_SESSION['user']);
$currentUserId = $isAuthenticated ? ($_SESSION['user']['id'] ?? null) : null;
$likedCollections = [];
$collectionLikeCounts = [];

// Fetch like counts + user likes for collections
require __DIR__ . '/../config/db.php';
if ($mysqli && !$mysqli->connect_error) {
    $res = $mysqli->query("SELECT liked_collection_id, COUNT(*) as cnt FROM user_liked_collections GROUP BY liked_collection_id");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $cid = $row['liked_collection_id'] ?? null;
            if ($cid) {
                $collectionLikeCounts[$cid] = (int)($row['cnt'] ?? 0);
            }
        }
        $res->close();
    }
    if ($isAuthenticated && $currentUserId) {
        $stmt = $mysqli->prepare("SELECT liked_collection_id FROM user_liked_collections WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param('s', $currentUserId);
            $stmt->execute();
            $res2 = $stmt->get_result();
            while ($row = $res2->fetch_assoc()) {
                $cid = $row['liked_collection_id'] ?? null;
                if ($cid) $likedCollections[$cid] = true;
            }
            $stmt->close();
        }
    }
    $mysqli->close();
}

$eventRsvpMap = [];
foreach ($eventsUsers as $entry) {
    $eid = $entry['eventId'] ?? $entry['event_id'] ?? null;
    $uid = $entry['userId'] ?? $entry['user_id'] ?? null;
    $hasRsvp = !empty($entry['rsvp']) || (($entry['type'] ?? '') === 'rsvp');
    if ($eid && $uid && $hasRsvp) {
        $eventRsvpMap["{$eid}|{$uid}"] = true;
    }
}

// Build usersById lookup
$usersById = [];
foreach ($users as $u) {
    $uid = $u['id'] ?? $u['user_id'] ?? null;
    if ($uid) {
        $usersById[$uid] = $u;
    }
}

// Build lookup for items by id and collection
$itemsById = [];
foreach ($items as $it) {
    if (!empty($it['id']))
        $itemsById[$it['id']] = $it;
}
$itemsByCollection = [];
foreach ($collectionItems as $link) {
    $cid = $link['collectionId'] ?? null;
    $iid = $link['itemId'] ?? null;
    if ($cid && $iid && isset($itemsById[$iid])) {
        $itemsByCollection[$cid][] = $itemsById[$iid];
    }
}

$sort = $_GET['sort'] ?? 'newest';
// Home: force first page with 5 results (no pagination UI)
$perPage = 5;
$page = 1;

usort($collections, function ($a, $b) use ($sort) {
    $aDate = $a['createdAt'] ?? '';
    $bDate = $b['createdAt'] ?? '';
    if ($sort === 'oldest') {
        return strcmp($aDate, $bDate);
    }
    if ($sort === 'name') {
        return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
    }
    return strcmp($bDate, $aDate);
});

$totalCollections = count($collections);
$collectionsPage = array_slice($collections, 0, $perPage);

$appTimezone = new DateTimeZone(date_default_timezone_get());
$now = new DateTime('now', $appTimezone);
$today = $now->format('Y-m-d');

if (!function_exists('parse_event_datetime_home')) {
    function parse_event_datetime_home($raw, DateTimeZone $tz)
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

$upcomingEvents = array_filter($events, function ($e) use ($appTimezone, $now, $today) {
    $parsed = parse_event_datetime_home($e['date'] ?? null, $appTimezone);
    $eventDateObj = $parsed['date'];
    $hasTime = $parsed['hasTime'];
    if (!$eventDateObj) return false;

    if ($hasTime) {
        return $eventDateObj > $now;
    } else {
        return $eventDateObj->format('Y-m-d') >= $today;
    }
});
$upcomingEvents = array_slice($upcomingEvents, 0, 4);
?>
<!DOCTYPE html>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Home • GoodCollections</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Estilos globais -->
    <link rel="stylesheet" href="../../CSS/general.css">

    <!-- Estilos específicos da home -->
    <link rel="stylesheet" href="../../CSS/home_page.css?v=7">

    <!-- Eventos + likes -->
    <link rel="stylesheet" href="../../CSS/events.css">
    <link rel="stylesheet" href="../../CSS/likes.css">

    <!-- Ícones -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Christmas Theme -->
    <link rel="stylesheet" href="../../CSS/christmas.css">

    <script src="../../JS/theme-toggle.js"></script>
    <script src="../../JS/christmas-theme.js"></script>
</head>

<body>
    <div id="content" class="page-container">
        <?php include __DIR__ . '/../includes/nav.php'; ?>

        <main class="page-main">
            <?php flash_render(); ?>

            <section class="welcome-hero">
                <div class="welcome-card">
                    <div class="welcome-text">
                        <h1 class="welcome-title"><span class="welcome-title__icon"><i class="bi bi-stars"></i></span>Welcome to GoodCollections</h1>
                        <p>Manage all your collections effortlessly — from books and vinyl records to coins, stamps, and miniatures.</p>
                        <div class="welcome-pills">
                            <span class="pill pill--outline"><strong>Manage</strong> Multiple Collections</span>
                            <span class="pill pill--outline"><strong>Track</strong> Items &amp; Events</span>
                            <span class="pill pill--outline"><strong>Focus</strong> Collectors Experience</span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="quick-actions">
                <div class="quick-actions__grid">
                    <a href="collections_form.php" class="explore-btn success">
                        <i class="bi bi-plus-circle"></i>
                        Add Collection
                    </a>
                    <a href="events_form.php" class="explore-btn success">
                        <i class="bi bi-calendar-plus"></i>
                        New Event
                    </a>
                </div>
            </section>

            <section class="collections-hero">
                <h1>Top Collections</h1>
                <div class="collections-hero-underline"></div>
                <p>
                    Explore the most popular and recently added collections curated by our community.
                </p>
            </section>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="bi bi-people-fill stat-icon"></i>
                    <div class="stat-value"><?php echo $statsUsers; ?></div>
                    <div class="stat-label">Users</div>
                </div>
                <div class="stat-card">
                    <i class="bi bi-collection-fill stat-icon"></i>
                    <div class="stat-value"><?php echo $statsCollections; ?></div>
                    <div class="stat-label">Collections</div>
                </div>
                <div class="stat-card">
                    <i class="bi bi-box-fill stat-icon"></i>
                    <div class="stat-value"><?php echo $statsItems; ?></div>
                    <div class="stat-label">Items</div>
                </div>
                <div class="stat-card">
                    <i class="bi bi-calendar-event-fill stat-icon"></i>
                    <div class="stat-value"><?php echo $statsEvents; ?></div>
                    <div class="stat-label">Events</div>
                </div>
            </div>

            <section class="ranking-section" id="ranking-section">
                <div class="top-controls">
                    <div class="left">
                        <form id="filters" class="filters-form" method="GET">
                            <div class="filter-chip filter-chip--select">
                                <label class="filter-chip__label" for="sort-select">
                                    <i class="bi bi-funnel"></i>
                                    <span>Sort by</span>
                                </label>
                                <select name="sort" id="sort-select" class="filter-chip__select" onchange="gcSubmitWithScroll(this.form)">
                                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Last Added</option>
                                    <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                                    <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="collection-container" data-limit="5">
                    <?php if ($collectionsPage): ?>
                        <?php foreach ($collectionsPage as $col): ?>
                            <?php
                            $img = $col['coverImage'] ?? '';
                            if ($img && !preg_match('#^https?://#', $img)) {
                                $img = '../../' . ltrim($img, './');
                            }
                            $previewItems = array_slice($itemsByCollection[$col['id']] ?? [], 0, 2);
                            $previewId = 'preview-' . htmlspecialchars($col['id']);
                            $collectionHref = 'specific_collection.php?id=' . urlencode($col['id']);
                            ?>

                            <article class="card collection-card home-card collection-card-link" role="link" tabindex="0" data-collection-link="<?php echo htmlspecialchars($collectionHref); ?>">
                                <input type="checkbox" id="<?php echo $previewId; ?>" class="preview-toggle">

                                <div class="card-image">
                                    <?php if (!empty($img)): ?>
                                        <a href="<?php echo htmlspecialchars($collectionHref); ?>">
                                            <img src="<?php echo htmlspecialchars($img); ?>"
                                                alt="<?php echo htmlspecialchars($col['name']); ?>">
                                        </a>
                                    <?php else: ?>
                                        <div class="card-image-placeholder">
                                            No cover image
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="card-info">
                                    <p class="pill">
                                        <?php echo htmlspecialchars($col['type'] ?? ''); ?>
                                    </p>

                                    <h3 class="card-title">
                                        <a href="<?php echo htmlspecialchars($collectionHref); ?>">
                                            <?php echo htmlspecialchars($col['name']); ?>
                                        </a>
                                    </h3>

                                    <?php if (!empty($col['summary'])): ?>
                                        <p class="muted">
                                            <?php echo htmlspecialchars($col['summary']); ?>
                                        </p>
                                    <?php endif; ?>
                                    <div class="collection-card__meta collection-card__meta--center">
                                        <div class="collection-card__owner">
                                            <i class="bi bi-people"></i>
                                            <a href="user_page.php?id=<?php echo urlencode($col['ownerId']); ?>">
                                                <?php
                                                $ownerId = $col['ownerId'] ?? null;
                                                $ownerName = $ownerId && isset($usersById[$ownerId])
                                                    ? ($usersById[$ownerId]['user_name'] ?? $usersById[$ownerId]['username'] ?? 'Unknown')
                                                    : 'Unknown';
                                                echo htmlspecialchars($ownerName);
                                                ?>
                                            </a>
                                        </div>

                                        <?php if (!empty($col['createdAt'])): ?>
                                            <div class="collection-card__date">
                                                <i class="bi bi-calendar3"></i>
                                                <?php echo htmlspecialchars(substr($col['createdAt'], 0, 10)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php $isOwner = $isAuthenticated && (($col['ownerId'] ?? null) === $currentUserId); ?>
                                    <div class="collection-card__actions card-actions">
                                        <label class="action-icon" for="<?php echo $previewId; ?>" title="Expand">
                                            <i class="bi bi-plus-lg"></i>
                                        </label>

                                        <?php if ($isAuthenticated): ?>
                                            <form action="likes_action.php" method="POST" class="action-icon-form like-form">
                                                <input type="hidden" name="type" value="collection">
                                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($col['id']); ?>">
                                                <?php $likeCount = $collectionLikeCounts[$col['id']] ?? 0; ?>
                                                <button type="submit" class="action-icon<?php echo isset($likedCollections[$col['id']]) ? ' is-liked' : ''; ?>" title="Like">
                                                    <i class="bi <?php echo isset($likedCollections[$col['id']]) ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
                                                    <span class="like-count<?php echo $likeCount === 0 ? ' is-zero' : ''; ?>"><?php echo $likeCount; ?></span>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button type="button" class="action-icon" data-action="login-popup" data-login-url="auth.php" title="Like">
                                                <i class="bi bi-heart"></i>
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($isOwner): ?>
                                            <a class="action-icon" href="collections_form.php?id=<?php echo urlencode($col['id']); ?>&from=home_page" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="collections_action.php" method="POST" class="action-icon-form">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($col['id']); ?>">
                                                <button type="submit" class="action-icon is-danger" title="Delete" onclick="return confirm('Delete this collection?');">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>

                                    <div class="card-buttons">
                                        <label class="explore-btn ghost preview-show"
                                            for="<?php echo $previewId; ?>">
                                            Show Preview
                                        </label>
                                        <label class="explore-btn ghost preview-hide"
                                            for="<?php echo $previewId; ?>">
                                            Hide Preview
                                        </label>
                                    </div>

                                    <div class="preview-items">
                                        <?php if ($previewItems): ?>
                                            <?php foreach ($previewItems as $it): ?>
                                                <?php
                                                $thumb = $it['image'] ?? '';
                                                if ($thumb && !preg_match('#^https?://#', $thumb)) {
                                                    $thumb = '../../' . ltrim($thumb, './');
                                                }
                                                ?>
                                                <a class="preview-item"
                                                    href="item_page.php?id=<?php echo urlencode($it['id']); ?>">
                                                    <?php if ($thumb): ?>
                                                        <img src="<?php echo htmlspecialchars($thumb); ?>"
                                                            alt="<?php echo htmlspecialchars($it['name'] ?? 'Item'); ?>">
                                                    <?php endif; ?>
                                                    <span><?php echo htmlspecialchars($it['name'] ?? 'Item'); ?></span>
                                                </a>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="muted small">No items yet.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </article>

                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="muted">No collections yet.</p>
                    <?php endif; ?>
                </div>
            </section>
        </main>

        <!-- BLOCOS DE EVENTOS (mantive exatamente a tua lógica) -->
        <section class="upcoming-events inner-block">
            <div class="upcoming-inner">
                <h2 class="upcoming-title">
                    <i class="bi bi-calendar-event-fill me-2" aria-hidden="true"></i>
                    Upcoming Events
                </h2>
                <p class="upcoming-sub">
                    Don't miss the next exhibitions, fairs and meetups curated by our community.
                </p>

                <div class="events-grid" id="eventsList">
                    <?php if ($upcomingEvents): ?>
                        <?php foreach ($upcomingEvents as $evt): ?>
                            <?php
                            $parsed = parse_event_datetime_home($evt['date'] ?? null, $appTimezone);
                            $eventDateObj = $parsed['date'];
                            $hasTime = $parsed['hasTime'];

                            $monthNames = [
                                1 => 'Janeiro',
                                2 => 'Fevereiro',
                                3 => 'Março',
                                4 => 'Abril',
                                5 => 'Maio',
                                6 => 'Junho',
                                7 => 'Julho',
                                8 => 'Agosto',
                                9 => 'Setembro',
                                10 => 'Outubro',
                                11 => 'Novembro',
                                12 => 'Dezembro'
                            ];

                            $dateDisplay = '';
                            if ($eventDateObj) {
                                $day = (int)$eventDateObj->format('d');
                                $month = (int)$eventDateObj->format('m');
                                $year = $eventDateObj->format('Y');
                                $dateDisplay = "{$day} de {$monthNames[$month]}";

                                if ($hasTime) {
                                    $time = $eventDateObj->format('H:i');
                                    $dateDisplay .= " às {$time}";
                                }
                            }
                            $eventDateDisplay = '';
                            $eventTimeDisplay = '';
                            if ($eventDateObj) {
                                $eventDateDisplay = $eventDateObj->format('d/m/Y');
                                if ($hasTime) {
                                    $eventTimeDisplay = $eventDateObj->format('H:i');
                                }
                            }
                            $modalPrimaryDate = $eventDateDisplay ?: $dateDisplay;
                            $modalCombinedDisplay = $eventTimeDisplay ? ($modalPrimaryDate . ' · ' . $eventTimeDisplay) : $modalPrimaryDate;
                            $rawCost = $evt['cost'] ?? null;
                            $costValue = ($rawCost === '' || $rawCost === null) ? null : (float)$rawCost;
                            $costLabel = ($costValue !== null && $costValue > 0)
                                ? '€' . number_format($costValue, 2, ',', '.')
                                : 'Free entrance';
                            ?>
                                     <?php
                                     $eventId = $evt['id'] ?? null;
                                     $cardDomId = $eventId ? 'upcoming-event-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $eventId) : 'upcoming-event';
                                     $hasRsvp = $currentUserId && $eventId ? !empty($eventRsvpMap["{$eventId}|{$currentUserId}"]) : false;
                                     ?>
                                     <article id="<?php echo htmlspecialchars($cardDomId); ?>"
                                         class="event-card js-event-card"
                                         tabindex="0"
                                         data-name="<?php echo htmlspecialchars($evt['name'] ?? ''); ?>"
                                         data-summary="<?php echo htmlspecialchars($evt['summary'] ?? ''); ?>"
                                         data-description="<?php echo htmlspecialchars($evt['description'] ?? ''); ?>"
                                         data-date="<?php echo htmlspecialchars($modalPrimaryDate); ?>"
                                         data-time="<?php echo htmlspecialchars($eventTimeDisplay); ?>"
                                         data-datetime="<?php echo htmlspecialchars($modalCombinedDisplay); ?>"
                                         data-location="<?php echo htmlspecialchars($evt['localization'] ?? ''); ?>"
                                         data-type="<?php echo htmlspecialchars($evt['type'] ?? 'event'); ?>"
                                         data-cost="<?php echo htmlspecialchars($costLabel); ?>"
                                         data-has-rsvp="<?php echo $hasRsvp ? '1' : '0'; ?>"
                                         data-event-id="<?php echo htmlspecialchars($eventId); ?>">

                                        <?php
                                            $isOwner = $isAuthenticated && (($evt['host_user_id'] ?? $evt['hostUserId'] ?? null) === $currentUserId);
                                            $statusIsUpcoming = true;
                                            $parsed = parse_event_datetime_home($evt['date'] ?? null, $appTimezone);
                                            if ($parsed['date'] instanceof DateTime) {
                                                $statusIsUpcoming = $parsed['date'] > $now;
                                            }
                                        ?>
                                        <div class="event-card-top">
                                            <p class="pill"><?php echo htmlspecialchars($evt['type'] ?? ''); ?></p>
                                            <span class="status-chip <?php echo $statusIsUpcoming ? 'upcoming' : 'past'; ?>">
                                                <?php echo $statusIsUpcoming ? 'Soon' : 'Past'; ?>
                                            </span>
                                        </div>
                                        <h3><?php echo htmlspecialchars($evt['name'] ?? 'Event'); ?></h3>
                                        <div class="event-meta-row">
                                            <i class="bi bi-calendar-event"></i>
                                            <span><?php echo htmlspecialchars($eventDateDisplay ?: $modalCombinedDisplay); ?></span>
                                        </div>
                                        <?php if (!empty($evt['localization'])): ?>
                                        <div class="event-meta-row">
                                            <i class="bi bi-geo-alt"></i>
                                            <a class="event-location-link" href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($evt['localization']); ?>" target="_blank" rel="noopener noreferrer" aria-label="Open <?php echo htmlspecialchars($evt['localization']); ?> on Google Maps">
                                                <?php echo htmlspecialchars($evt['localization']); ?>
                                            </a>
                                        </div>
                                        <?php endif; ?>
                                        <div class="event-meta-row">
                                            <i class="bi bi-cash-coin"></i>
                                            <span><?php echo htmlspecialchars($costLabel); ?></span>
                                        </div>
                                        <?php if (!empty($evt['summary'])): ?>
                                            <p class="muted" style="margin:8px 0 0;"><?php echo htmlspecialchars($evt['summary']); ?></p>
                                        <?php endif; ?>
                                        <div class="event-actions">
                                            <?php if ($isOwner): ?>
                                                <a class="explore-btn ghost small" href="events_form.php?id=<?php echo urlencode($eventId); ?>">Edit</a>
                                                <form action="events_action.php" method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($eventId); ?>">
                                                    <button type="submit" class="explore-btn ghost danger small" onclick="return confirm('Delete this event?');">Delete</button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if ($isAuthenticated && $eventId): ?>
                                                <form action="events_action.php" method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="rsvp">
                                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($eventId); ?>">
                                                    <input type="hidden" name="return_url" value="home_page.php">
                                                    <input type="hidden" name="return_target" value="#<?php echo htmlspecialchars($cardDomId); ?>">
                                                    <button type="submit" class="explore-btn small<?php echo $hasRsvp ? ' success' : ''; ?>">
                                                        <i class="bi bi-check2-circle"></i> <?php echo $hasRsvp ? 'RSVP confirmed' : 'RSVP'; ?>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="muted">No upcoming events scheduled</p>
                    <?php endif; ?>
                </div>

                <div class="events-actions">
                    <a href="event_page.php" class="explore-btn ghost">View Events</a>
                </div>
            </div>
        </section>
        <!-- ===============================
                 Everything You Need...
                 =============================== -->
        <section class="features-section">
            <h2 class="features-title">
                Everything You Need to <span>Manage Your Collections</span>
            </h2>
            <p class="features-subtitle">
                From automotive miniatures to rare stamps, GoodCollections gives you the tools
                to catalog, organize, and showcase every item with precision.
            </p>

            <div class="features-grid">
                <article class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-grid-3x3-gap-fill"></i>
                    </div>
                    <h3>Multiple Collections</h3>
                    <p>
                        Create unlimited collections for different item types. Keep coin separate
                        from trading cards, all in one place.
                    </p>
                </article>

                <article class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-card-checklist"></i>
                    </div>
                    <h3>Detailed Item Tracking</h3>
                    <p>
                        Record every detail — name, importance rating, acquisition date, weight and price.
                        Never forget what you paid or when you got it.
                    </p>
                </article>

                <article class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-calendar2-week"></i>
                    </div>
                    <h3>Event Management</h3>
                    <p>
                        Track exhibitions and collector events. Add descriptions, dates, and rate your
                        experience after attending each event.
                    </p>
                </article>

                <article class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <h3>Easy Editing</h3>
                    <p>
                        Update collection details, add or remove items, and modify information anytime.
                        Your catalog evolves with your collection.
                    </p>
                </article>

                <article class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-compass"></i>
                    </div>
                    <h3>Intuitive Interface</h3>
                    <p>
                        Clean, modern design that's easy to navigate. View your top collections at a
                        glance on the homepage.
                    </p>
                </article>

                <article class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-person-badge-fill"></i>
                    </div>
                    <h3>Personal Profile</h3>
                    <p>
                        Your collector profile keeps track of your personal information and preferences
                        in one central location.
                    </p>
                </article>
            </div>
        </section>


        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>

    <div class="modal-backdrop" id="event-modal">
        <div class="modal-card">
            <div class="modal-header">
                <button type="button" class="modal-close" aria-label="Close event details">
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
                            <div class="modal-info-value">
                                <a id="modal-location" class="modal-location-link" href="#" rel="noopener noreferrer"></a>
                            </div>
                        </div>
                    </div>
                    <div class="modal-info-item">
                        <div class="modal-info-icon">
                            <i class="bi bi-cash-coin"></i>
                        </div>
                        <div class="modal-info-content">
                            <div class="modal-info-label">Cost</div>
                            <div class="modal-info-value" id="modal-cost"></div>
                        </div>
                    </div>
                </div>
                <div id="modal-rsvp-container" style="display:none;">
                    <!-- RSVP removed to match event page -->
                </div>
            </div>
        </div>
    </div>

    <!-- Back to Top Button -->
    <button id="backToTop" class="back-to-top" aria-label="Voltar ao topo">
        <i class="bi bi-arrow-up"></i>
    </button>

    <script src="../../JS/search-toggle.js"></script>
    <script src="../../JS/gc-scroll-restore.js"></script>
    <script src="../../JS/back-to-top.js"></script>
    <script src="../../JS/home_page.js?v=2"></script>
</body>

</html>

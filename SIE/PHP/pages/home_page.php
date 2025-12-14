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
$perPage = max(1, (int) ($_GET['perPage'] ?? 5));
$page = max(1, (int) ($_GET['page'] ?? 1));

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
$pages = max(1, (int) ceil($totalCollections / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;
$collectionsPage = array_slice($collections, $offset, $perPage);
$startDisplay = $totalCollections ? $offset + 1 : 0;
$endDisplay = $totalCollections ? min($offset + $perPage, $totalCollections) : 0;

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
    <link rel="stylesheet" href="home_page.css">

    <!-- Eventos + likes -->
    <link rel="stylesheet" href="../../CSS/events.css">
    <link rel="stylesheet" href="../../CSS/likes.css">

    <!-- Ícones -->
    <link rel="stylesheet"
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Christmas Theme -->
    <link rel="stylesheet" href="../../CSS/christmas.css">

    <style>
        /* Home events cards styled like events page */
        .upcoming-events .event-card {
            cursor: pointer;
            position: relative;
            display: block;
            background: #ffffff;
            border-radius: 22px;
            padding: 18px 18px 16px;
            box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08);
            border: 1px solid #e5e7eb;
            transition: transform 0.18s ease, box-shadow 0.2s ease;
            color: inherit;
            text-decoration: none;
        }

        .upcoming-events .event-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 42px rgba(15, 23, 42, 0.12);
        }

        .upcoming-events .event-card:focus {
            outline: 2px solid #6366f1;
            outline-offset: 4px;
        }

        .upcoming-events .event-card:active {
            transform: translateY(0);
        }

        .home-event-rsvp {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 18px;
            border-radius: 999px;
            border: 1px solid #e5e7eb;
            color: #1e3a8a;
            font-weight: 700;
            background: #ffffff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
        }

        form.home-event-rsvp {
            margin: 0;
        }

        .home-event-rsvp__button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 0;
            background: transparent;
            border: none;
            color: inherit;
            font: inherit;
            cursor: pointer;
        }

        .home-event-rsvp:hover,
        .home-event-rsvp:focus-within {
            background: #f8fafc;
            color: #2563eb;
            transform: translateY(-1px);
        }

        .home-event-rsvp.is-active {
            border-color: #bbf7d0;
            background: #f0fdf4;
            color: #15803d;
            box-shadow: 0 8px 20px rgba(22, 163, 74, 0.12);
        }

        .home-event-rsvp.is-active:hover,
        .home-event-rsvp.is-active:focus-within {
            background: #dcfce7;
            color: #166534;
        }

        .home-event-rsvp--login {
            justify-content: center;
        }
            font-weight: 700;
            border: 1px solid #bbf7d0;
            font-size: 0.9rem;
        }

        .home-event-title {
            margin: 0 0 12px;
            font-size: 1.4rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.3;
        }

        .home-event-meta {
            display: grid;
            gap: 10px;
            margin: 0 0 16px;
            color: #334155;
            font-weight: 700;
        }

        .home-event-meta .meta-row {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.98rem;
            color: #1f2937;
        }

        .home-event-meta .meta-row i {
            font-size: 1.1rem;
            color: #475569;
        }

        .home-event-rsvp {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 18px;
            border-radius: 999px;
            border: 1px solid #e5e7eb;
            color: #1e3a8a;
            font-weight: 700;
            background: #ffffff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }

        .home-event-rsvp:hover {
            background: #f8fafc;
            transform: translateY(-1px);
        }

        /* Shared modal styling reused from Events page */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            backdrop-filter: blur(4px);
        }

        .modal-backdrop.open {
            display: flex;
            animation: fadeIn 0.2s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .modal-card {
            background: #fff;
            border-radius: 20px;
            padding: 0;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            position: relative;
            animation: slideUp 0.3s ease;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        @keyframes slideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
            padding: 32px 28px;
            color: white;
            position: relative;
            text-align: center;
        }

        .modal-close {
            position: absolute;
            top: 16px;
            right: 16px;
            border: none;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .modal-header h3 {
            margin: 0 0 12px 0;
            font-size: 2.25rem;
            font-weight: 900 !important;
            color: white !important;
            line-height: 1.2;
        }

        .modal-type-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.25);
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 8px;
        }

        .modal-body {
            padding: 28px;
            overflow-y: auto;
        }

        .modal-summary {
            font-size: 1.05rem;
            color: #374151;
            line-height: 1.6;
            margin: 0 0 24px 0;
            font-weight: 500;
        }

        .modal-description {
            color: #6b7280;
            line-height: 1.7;
            margin: 0 0 24px 0;
        }

        .modal-info-grid {
            display: grid;
            gap: 16px;
        }

        .modal-info-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px;
            background: #f9fafb;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
        }

        .modal-info-icon {
            width: 40px;
            height: 40px;
            min-width: 40px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }

        .modal-info-content {
            flex: 1;
        }

        .modal-info-label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #9ca3af;
            margin-bottom: 4px;
        }

        .modal-info-value {
            font-size: 1rem;
            font-weight: 600;
            color: #1f2937;
        }

        .modal-location-link {
            color: inherit;
            text-decoration: none;
            font-weight: 600;
        }

        .modal-location-link:hover {
            text-decoration: none;
        }

        .modal-location-link.disabled {
            color: #9ca3af;
            pointer-events: none;
            text-decoration: none;
        }

        .quick-actions {
            margin: 40px 0;
            text-align: center;
        }

        .quick-actions__grid {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }
    </style>

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

                            <div class="filter-chip filter-chip--compact filter-chip--select">
                                <label class="filter-chip__label" for="per-page-select">
                                    <i class="bi bi-collection"></i>
                                    <span>Show</span>
                                </label>
                                <select name="perPage" id="per-page-select" class="filter-chip__select" onchange="gcSubmitWithScroll(this.form)">
                                    <?php foreach ([5, 10, 20] as $opt): ?>
                                        <option value="<?php echo $opt; ?>" <?php echo $perPage == $opt ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="filter-chip__hint">per page</span>
                            </div>

                            <input type="hidden" name="page" value="1">
                        </form>
                    </div>
                    <div class="paginate">
                        
                        <button <?php echo $page <= 1 ? 'disabled' : ''; ?> onclick="gcRememberScroll('?<?php echo http_build_query(['sort' => $sort, 'perPage' => $perPage, 'page' => max(1, $page - 1)]); ?>')"><i class="bi bi-chevron-left"></i></button>
                        <span>Showing <?php echo $startDisplay; ?>-<?php echo $endDisplay; ?> of <?php echo $totalCollections; ?></span>
                        <button <?php echo $page >= $pages ? 'disabled' : ''; ?> onclick="gcRememberScroll('?<?php echo http_build_query(['sort' => $sort, 'perPage' => $perPage, 'page' => min($pages, $page + 1)]); ?>')"><i class="bi bi-chevron-right"></i></button>
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

                <div class="events-grid">
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
                                         role="button"
                                         aria-label="View details for <?php echo htmlspecialchars($evt['name'] ?? 'event'); ?>"
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
                                
                                <h3 class="home-event-title"><?php echo htmlspecialchars($evt['name']); ?></h3>
                                <div class="home-event-meta">
                                    <div class="meta-row">
                                        <i class="bi bi-calendar-event"></i>
                                        <span><?php echo htmlspecialchars($eventDateDisplay ?: $modalCombinedDisplay); ?></span>
                                    </div>
                                    <div class="meta-row">
                                        <i class="bi bi-geo-alt"></i>
                                        <span><?php echo htmlspecialchars($evt['localization']); ?></span>
                                    </div>
                                    <div class="meta-row">
                                        <i class="bi bi-cash-coin"></i>
                                        <span><?php echo htmlspecialchars($costLabel); ?></span>
                                    </div>
                                </div>
                                <?php if ($isAuthenticated && $eventId): ?>
                                    <form action="events_action.php" method="POST" class="home-event-rsvp<?php echo $hasRsvp ? ' is-active' : ''; ?>">
                                        <input type="hidden" name="action" value="rsvp">
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($eventId); ?>">
                                        <input type="hidden" name="return_url" value="event_page.php">
                                        <button type="submit" class="home-event-rsvp__button">
                                            <i class="bi bi-check2-circle"></i>
                                            <span><?php echo $hasRsvp ? 'RSVP confirmed' : 'RSVP'; ?></span>
                                        </button>
                                    </form>
                                <?php endif; ?>
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
    <script>
        var currentUserId = <?php echo $currentUserId ? json_encode($currentUserId) : 'null'; ?>;
        gcInitScrollRestore({
            key: 'gc-scroll-home',
            formSelector: '#filters',
            reapplyFrames: 3,
            reinforceMs: 800,
            reinforceInterval: 80,
            stabilizeMs: 1200
        });
    </script>

        // Back to Top functionality
        (function() {
            var backToTopBtn = document.getElementById('backToTop');
            if (!backToTopBtn) return;

            function toggleBackToTop() {
                var scrollTop = window.scrollY || document.documentElement.scrollTop;
                if (scrollTop > 300) {
                    backToTopBtn.classList.add('show');
                } else {
                    backToTopBtn.classList.remove('show');
                }
            }

            backToTopBtn.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });

            window.addEventListener('scroll', toggleBackToTop);
            toggleBackToTop(); // Check initial state
        })();
    </script>
    <script>
        (function() {
            var interactiveSelector = 'a, button, label, input, textarea, select, form, [role="button"]';

            function enhanceCard(card) {
                var href = card.getAttribute('data-collection-link');
                if (!href) {
                    return;
                }
                card.addEventListener('click', function(event) {
                    if (event.target.closest(interactiveSelector)) {
                        return;
                    }
                    window.location.href = href;
                });
                card.addEventListener('keydown', function(event) {
                    if (event.target !== card) {
                        return;
                    }
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        window.location.href = href;
                    }
                });
            }
            document.querySelectorAll('.collection-card-link').forEach(enhanceCard);
        })();
    </script>
    <script>
        (function() {
            var modal = document.getElementById('event-modal');
            if (!modal) {
                return;
            }

            var cardInteractiveSelector = '[data-home-rsvp-form], .home-event-rsvp, button, input, select, textarea, form, a';

            var closeButton = modal.querySelector('.modal-close');
            var titleEl = document.getElementById('modal-title');
            var typeEl = document.getElementById('modal-type');
            var summaryEl = document.getElementById('modal-summary');
            var descriptionEl = document.getElementById('modal-description');
            var dateEl = document.getElementById('modal-date');
            var timeRow = document.getElementById('modal-time-row');
            var timeEl = document.getElementById('modal-time');
            var locationLink = document.getElementById('modal-location');
            var costEl = document.getElementById('modal-cost');

            function setText(target, value) {
                if (!target) {
                    return;
                }
                target.textContent = value || '';
            }

            function openModal(payload, card) {
                setText(titleEl, payload.name);
                setText(typeEl, payload.type);
                setText(summaryEl, payload.summary);
                setText(descriptionEl, payload.description);
                if (dateEl) {
                    setText(dateEl, payload.date || payload.datetime || '');
                }
                if (timeRow && timeEl) {
                    if (payload.time) {
                        timeRow.hidden = false;
                        setText(timeEl, payload.time);
                    } else {
                        timeRow.hidden = true;
                        setText(timeEl, '');
                    }
                }
                if (locationLink) {
                    var cleanLocation = (payload.location || '').trim();
                    if (cleanLocation) {
                        locationLink.textContent = cleanLocation;
                        locationLink.href = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(cleanLocation);
                        locationLink.classList.remove('disabled');
                        locationLink.setAttribute('target', '_blank');
                        locationLink.setAttribute('rel', 'noopener noreferrer');
                        locationLink.setAttribute('aria-label', 'Open ' + cleanLocation + ' on Google Maps');
                    } else {
                        locationLink.textContent = 'Location unavailable';
                        locationLink.removeAttribute('href');
                        locationLink.removeAttribute('target');
                        locationLink.removeAttribute('rel');
                        locationLink.removeAttribute('aria-label');
                        locationLink.classList.add('disabled');
                    }
                }
                if (costEl) {
                    setText(costEl, payload.cost || 'Free entrance');
                }
                modal.classList.add('open');
            }

            function closeModal() {
                modal.classList.remove('open');
            }

            function bindEventCard(card) {
                if (!card) {
                    return;
                }

                function launchModal() {
                    openModal({
                        name: card.getAttribute('data-name') || '',
                        summary: card.getAttribute('data-summary') || '',
                        description: card.getAttribute('data-description') || '',
                        date: card.getAttribute('data-date') || '',
                        time: card.getAttribute('data-time') || '',
                        datetime: card.getAttribute('data-datetime') || '',
                        location: card.getAttribute('data-location') || '',
                        type: card.getAttribute('data-type') || '',
                        cost: card.getAttribute('data-cost') || ''
                    }, card);
                }

                card.addEventListener('click', function(event) {
                    if (event.target.closest(cardInteractiveSelector)) {
                        return;
                    }
                    event.preventDefault();
                    launchModal();
                });

                card.addEventListener('keydown', function(event) {
                    if (event.target !== card) {
                        return;
                    }
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        launchModal();
                    }
                });
            }

            document.querySelectorAll('.js-event-card').forEach(bindEventCard);

            if (closeButton) {
                closeButton.addEventListener('click', closeModal);
            }

            modal.addEventListener('click', function(event) {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && modal.classList.contains('open')) {
                    closeModal();
                }
            });
        })();
    </script>
</body>

</html>

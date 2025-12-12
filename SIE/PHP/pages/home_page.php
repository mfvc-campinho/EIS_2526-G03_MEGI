<?php
session_start();
require_once __DIR__ . '/../includes/data_loader.php';
require_once __DIR__ . '/../includes/flash.php';
$data = load_app_data($mysqli);
$users = $data['users'] ?? [];
$collections = $data['collections'] ?? [];
$items = $data['items'] ?? [];
$events = $data['events'] ?? [];

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
    function parse_event_datetime_home($raw, DateTimeZone $tz) {
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
        <title>HomePage • GoodCollections (PHP)</title>

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

        <script src="../../JS/theme-toggle.js"></script>
        <script src="../../JS/christmas-theme.js"></script>
    </head>

    <body>
        <div id="content" class="page-container">
            <?php include __DIR__ . '/../includes/nav.php'; ?>

            <main class="page-main">
                <?php flash_render(); ?>
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
                            <form id="filters" method="GET" style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                                <label for="sort-select"><i class="bi bi-funnel"></i> Sort by</label>
                                <select name="sort" id="sort-select" onchange="gcSubmitWithScroll(this.form)">
                                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Last Added</option>
                                    <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                                    <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                                </select>
                                <label>Show
                                    <select name="perPage" onchange="gcSubmitWithScroll(this.form)">
                                        <?php foreach ([5, 10, 20] as $opt): ?>
                                            <option value="<?php echo $opt; ?>" <?php echo $perPage == $opt ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    collections per page
                                </label>
                                <input type="hidden" name="page" value="1">
                            </form>
                        </div>
                        <div class="paginate">
                            <?php if ($isAuthenticated): ?>
                                <a class="explore-btn success" href="collections_form.php">+ Add Collection</a>
                            <?php endif; ?>
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
                                ?>

                                <article class="card collection-card home-card">
                                    <input type="checkbox" id="<?php echo $previewId; ?>" class="preview-toggle">

                                    <div class="card-image">
                                        <?php if (!empty($img)): ?>
                                            <a href="specific_collection.php?id=<?php echo urlencode($col['id']); ?>">
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
                                            <a href="specific_collection.php?id=<?php echo urlencode($col['id']); ?>">
                                                <?php echo htmlspecialchars($col['name']); ?>
                                            </a>
                                        </h3>
                                        <div class="product-card__meta">
                                            <div class="product-card__owner">
                                                <i class="bi bi-people"></i>
                                                <a href="user_page.php?id=<?php echo urlencode($col['ownerId']); ?>" style="color: inherit; text-decoration: none;">
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
                                                <div class="product-card__date">
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

                                            <a class="explore-btn"
                                               href="specific_collection.php?id=<?php echo urlencode($col['id']); ?>">
                                                Explore More
                                            </a>
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
                                    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                                    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                                    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
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
                                ?>
                                <article class="event-card">
                                    <p class="pill"><?php echo htmlspecialchars($evt['type'] ?? 'Evento'); ?></p>
                                    <h3><?php echo htmlspecialchars($evt['name']); ?></h3>
                                    <ul class="event-meta">
                                        <li>
                                            <i class="bi bi-calendar-event"></i>
                                            <?php echo htmlspecialchars($dateDisplay); ?>
                                        </li>
                                        <li>
                                            <i class="bi bi-geo-alt-fill"></i>
                                            <?php echo htmlspecialchars($evt['localization']); ?>
                                        </li>
                                    </ul>
                                    <a class="explore-btn small"
                                       href="event_page.php">
                                         See event
                                    </a>
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
     GoodCollections Statistics
     =============================== -->
            <section class="stats-section">
                <h2 class="stats-title">GoodCollections Statistics</h2>

                <div class="stats-grid">
                    <article class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="stat-body">
                            <div class="stat-value"><?php echo $statsUsers; ?></div>
                            <div class="stat-label">Registered users</div>
                        </div>
                    </article>

                    <article class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-calendar-event-fill"></i>
                        </div>
                        <div class="stat-body">
                            <div class="stat-value"><?php echo $statsEvents; ?></div>
                            <div class="stat-label">Total events</div>
                        </div>
                    </article>

                    <article class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-collection"></i>
                        </div>
                        <div class="stat-body">
                            <div class="stat-value"><?php echo $statsCollections; ?></div>
                            <div class="stat-label">Collections</div>
                        </div>
                    </article>

                    <article class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div class="stat-body">
                            <div class="stat-value"><?php echo $statsItems; ?></div>
                            <div class="stat-label">Items</div>
                        </div>
                    </article>
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

        <!-- Back to Top Button -->
        <button id="backToTop" class="back-to-top" aria-label="Voltar ao topo">
            <i class="bi bi-arrow-up"></i>
        </button>

        <script src="../../JS/search-toggle.js"></script>
        <script>
            (function () {
                var scrollKey = 'gc-scroll-home';
                var hasStorage = false;
                try {
                    sessionStorage.setItem('__gc_test', '1');
                    sessionStorage.removeItem('__gc_test');
                    hasStorage = true;
                } catch (err) {
                    hasStorage = false;
                }

                function saveScroll() {
                    if (!hasStorage) {
                        return;
                    }
                    var top = window.scrollY || document.documentElement.scrollTop || 0;
                    sessionStorage.setItem(scrollKey, String(top));
                }

                window.gcSubmitWithScroll = function (form) {
                    saveScroll();
                    form.submit();
                };

                window.gcRememberScroll = function (url) {
                    saveScroll();
                    window.location = url;
                };

                window.addEventListener('pageshow', function () {
                    if (!hasStorage) {
                        return;
                    }
                    var stored = sessionStorage.getItem(scrollKey);
                    if (stored !== null) {
                        window.scrollTo(0, parseFloat(stored));
                        sessionStorage.removeItem(scrollKey);
                    }
                });

                var filtersForm = document.getElementById('filters');
                if (filtersForm) {
                    filtersForm.addEventListener('submit', saveScroll);
                }
            })();

            // Back to Top functionality
            (function () {
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

                backToTopBtn.addEventListener('click', function () {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                });

                window.addEventListener('scroll', toggleBackToTop);
                toggleBackToTop(); // Check initial state
            })();
        </script>
    </body>

</html>

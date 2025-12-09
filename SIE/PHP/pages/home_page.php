<?php
session_start();
require_once __DIR__ . '/../includes/data_loader.php';
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

$upcomingEvents = array_filter($events, function ($e) {
    return empty($e['date']) ? false : (strtotime($e['date']) >= strtotime('today'));
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
              href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

        <script src="../../JS/theme-toggle.js"></script>
    </head>

    <body>
        <div id="content" class="page-container">
            <?php include __DIR__ . '/../includes/nav.php'; ?>

            <main class="page-main">
                <header class="home-hero">
                    <h1 class="page-title">Top Collections</h1>
                    <p class="page-subtitle">
                        Explore the most popular and recently added collections curated by our community.
                    </p>


                </header>
                <section class="ranking-section">
                    <div class="top-controls">
                        <div class="left">
                            <form id="filters" method="GET" style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                                <label for="sort-select"><i class="bi bi-funnel"></i> Sort by</label>
                                <select name="sort" id="sort-select" onchange="this.form.submit()">
                                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Last Added</option>
                                    <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                                    <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                                </select>
                                <label>Show
                                    <select name="perPage" onchange="this.form.submit()">
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
                            <button <?php echo $page <= 1 ? 'disabled' : ''; ?> onclick="window.location = '?<?php echo http_build_query(['sort' => $sort, 'perPage' => $perPage, 'page' => max(1, $page - 1)]); ?>'"><i class="bi bi-chevron-left"></i></button>
                            <span>Showing <?php echo $startDisplay; ?>-<?php echo $endDisplay; ?> of <?php echo $totalCollections; ?></span>
                            <button <?php echo $page >= $pages ? 'disabled' : ''; ?> onclick="window.location = '?<?php echo http_build_query(['sort' => $sort, 'perPage' => $perPage, 'page' => min($pages, $page + 1)]); ?>'"><i class="bi bi-chevron-right"></i></button>
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
                                            <span>
                                                <i class="bi bi-people"></i>
                                                <a href="user_page.php?id=<?php echo urlencode($col['ownerId']); ?>" style="color: inherit; text-decoration: none;">
                                                    <?php echo htmlspecialchars($col['ownerId']); ?>
                                                </a>
                                            </span>

                                            <?php if (!empty($col['createdAt'])): ?>
                                                <span>
                                                    <i class="bi bi-calendar3"></i>
                                                    <?php echo htmlspecialchars(substr($col['createdAt'], 0, 10)); ?>
                                                </span>
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
                            <p class="muted">Ainda não existem coleções.</p>
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
                                <article class="event-card">
                                    <p class="pill"><?php echo htmlspecialchars($evt['type'] ?? 'Evento'); ?></p>
                                    <h3><?php echo htmlspecialchars($evt['name']); ?></h3>
                                    <p class="muted"><?php echo htmlspecialchars($evt['summary']); ?></p>
                                    <ul class="event-meta">
                                        <li>
                                            <i class="bi bi-calendar-event"></i>
                                            <?php echo htmlspecialchars(substr($evt['date'], 0, 16)); ?>
                                        </li>
                                        <li>
                                            <i class="bi bi-geo-alt"></i>
                                            <?php echo htmlspecialchars($evt['localization']); ?>
                                        </li>
                                    </ul>
                                    <a class="explore-btn small"
                                       href="event_page.php?id=<?php echo urlencode($evt['id']); ?>">
                                        Ver evento
                                    </a>
                                </article>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="muted">Sem eventos futuros registados.</p>
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

        <script src="../../JS/search-toggle.js"></script>
    </body>

</html>

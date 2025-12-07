<?php
session_start();
require_once __DIR__ . '/../includes/data_loader.php';
$data = load_app_data($mysqli);
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

$topCollections = array_slice($collections, 0, 5);
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
                <?php if ($isAuthenticated): ?>
                    <div class="collection-actions">
                        <a href="collections_form.php" class="explore-btn">
                            + Adicionar coleção
                        </a>
                    </div>
                <?php endif; ?>


                <section class="ranking-section">
                    <div class="collection-container" data-limit="5">
                        <?php if ($topCollections): ?>
                            <?php foreach ($topCollections as $col): ?>
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

                                        <p class="muted card-description">
                                            <?php echo htmlspecialchars($col['summary']); ?>
                                        </p>

                                        <div class="card-meta">
                                            <span>
                                                <i class="bi bi-people"></i>
                                                <?php echo htmlspecialchars($col['ownerId']); ?>
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

            <?php include __DIR__ . '/../includes/footer.php'; ?>
        </div>

        <script src="../../JS/search-toggle.js"></script>
    </body>

</html>

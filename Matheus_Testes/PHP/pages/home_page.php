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
  if (!empty($it['id'])) $itemsById[$it['id']] = $it;
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
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>HomePage â€” GoodCollections (PHP)</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

  <!-- CSS relative to /PHP/pages -->
  <link rel="stylesheet" href="../../CSS/general.css">
  <link rel="stylesheet" href="../../CSS/home_page.css">
  <link rel="stylesheet" href="../../CSS/events.css">
  <link rel="stylesheet" href="../../CSS/likes.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <!-- Small tweak preserved from HTML -->
  <style>
    .upcoming-events .events-actions { display: flex; justify-content: center; align-items: center; }
  </style>

  <script src="../../JS/theme-toggle.js"></script>
  <style>
    /* Layout helpers to mimic original container and card sizing */
    .page-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
    .collection-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; }
    .product-card__media { width: 100%; height: 220px; border-radius: 12px; overflow: hidden; background: #f5f5f5; }
    .product-card__media img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .card-actions { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 10px; }
    .preview-toggle { display: none; }
    .preview-items { display: none; padding: 12px 4px 4px; }
    .preview-item { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
    .preview-item img { width: 44px; height: 44px; border-radius: 8px; object-fit: cover; }
    .preview-toggle:not(:checked) + .product-card__wrapper .preview-hide { display: none; }
    .preview-toggle:checked + .product-card__wrapper .preview-show { display: none; }
    .preview-toggle:checked + .product-card__wrapper .preview-hide { display: inline-flex; }
    .preview-toggle:checked ~ .preview-items { display: block; }
  </style>
</head>

<body>
  <div id="content" class="page-container">
    <?php include __DIR__ . '/../includes/nav.php'; ?>

    <main>
      <nav class="breadcrumb-nav" aria-label="Breadcrumb">
        <ol class="breadcrumb-list">
          <li class="breadcrumb-item" aria-current="page">Home</li>
        </ol>
      </nav>

      <!-- Welcome hero -->
      <section class="welcome-hero">
        <div class="welcome-inner">
          <div class="welcome-text">
            <h2 class="welcome-title">
              <i class="bi bi-stars me-2" aria-hidden="true"></i>
              Welcome to GoodCollections
            </h2>
            <p class="welcome-subtitle">
              Manage all your collections effortlessly â€” from books and vinyl records to coins, stamps, and miniatures.
            </p>
          </div>
          <div class="welcome-stats">
            <div class="stat">
              <span class="stat-label">ColeÃ§Ãµes</span>
              <span class="stat-value"><?php echo count($collections); ?></span>
            </div>
            <div class="stat">
              <span class="stat-label">Eventos</span>
              <span class="stat-value"><?php echo count($events); ?></span>
            </div>
            <div class="stat">
              <span class="stat-label">Utilizadores</span>
              <span class="stat-value"><?php echo count($users); ?></span>
            </div>
          </div>
        </div>

        <div class="collection-actions">
          <?php if ($isAuthenticated): ?>
            <a href="collections_form.php" class="explore-btn success">
              <i class="bi bi-plus-circle me-1" aria-hidden="true"></i> New Collection
            </a>
            <a href="events_form.php" class="explore-btn success events-new-btn">
              <i class="bi bi-calendar-plus me-1" aria-hidden="true"></i> New Event
            </a>
          <?php else: ?>
            <button class="explore-btn success" disabled title="Sign in to add collections">New Collection</button>
            <button class="explore-btn success events-new-btn" disabled title="Sign in to add events">New Event</button>
          <?php endif; ?>
        </div>
      </section>

      <h1 class="page-title">Top 5 Collections</h1>
      <p class="page-subtitle">Explore the most popular and recently added collections curated by our community.</p>

      <section class="filter-section">
        <div class="filter-control">
          <label for="rankingFilter" class="filter-label"><i class="bi bi-funnel me-1" aria-hidden="true"></i>Sort by</label>
          <div class="filter-input">
            <select id="rankingFilter" class="filter-select" disabled>
              <option selected>Last Added</option>
            </select>
          </div>
        </div>
      </section>

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
              <article class="product-card">
                <input type="checkbox" id="<?php echo $previewId; ?>" class="preview-toggle">
                <div class="product-card__wrapper">
                  <a href="specific_collection.php?id=<?php echo urlencode($col['id']); ?>" class="product-card__media">
                    <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($col['name']); ?>">
                  </a>
                  <div class="product-card__body">
                    <p class="pill"><?php echo htmlspecialchars($col['type'] ?? ''); ?></p>
                    <h3><a href="specific_collection.php?id=<?php echo urlencode($col['id']); ?>"><?php echo htmlspecialchars($col['name']); ?></a></h3>
                    <p class="muted"><?php echo htmlspecialchars($col['summary']); ?></p>
                    <div class="product-card__meta">
                      <span><i class="bi bi-people"></i> <?php echo htmlspecialchars($col['ownerId']); ?></span>
                      <span><i class="bi bi-calendar3"></i> <?php echo htmlspecialchars(substr($col['createdAt'], 0, 10)); ?></span>
                    </div>
                    <div class="card-actions">
                      <label class="explore-btn ghost preview-show" for="<?php echo $previewId; ?>">Show Preview</label>
                      <label class="explore-btn ghost preview-hide" for="<?php echo $previewId; ?>">Hide Preview</label>
                      <a class="explore-btn" href="specific_collection.php?id=<?php echo urlencode($col['id']); ?>">Explore More</a>
                    </div>
                  </div>
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
                      <a class="preview-item" href="item_page.php?id=<?php echo urlencode($it['id']); ?>">
                        <?php if ($thumb): ?><img src="<?php echo htmlspecialchars($thumb); ?>" alt="<?php echo htmlspecialchars($it['name'] ?? 'Item'); ?>"><?php endif; ?>
                        <span><?php echo htmlspecialchars($it['name'] ?? 'Item'); ?></span>
                      </a>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <p class="muted">No items yet.</p>
                  <?php endif; ?>
                </div>
              </article>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="muted">Ainda nÃ£o existem coleÃ§Ãµes.</p>
          <?php endif; ?>
        </div>
      </section>
    </main>

    <section class="upcoming-events">
      <div class="upcoming-inner">
        <h2 class="upcoming-title">
          <i class="bi bi-calendar-event-fill me-2" aria-hidden="true"></i>
          Upcoming Events
        </h2>
        <p class="upcoming-sub">Don't miss the next exhibitions, fairs and meetups curated by our community.</p>
        <div class="events-grid">
          <?php if ($upcomingEvents): ?>
            <?php foreach ($upcomingEvents as $evt): ?>
              <article class="event-card">
                <p class="pill"><?php echo htmlspecialchars($evt['type'] ?? 'Evento'); ?></p>
                <h3><?php echo htmlspecialchars($evt['name']); ?></h3>
                <p class="muted"><?php echo htmlspecialchars($evt['summary']); ?></p>
                <ul class="event-meta">
                  <li><i class="bi bi-calendar-event"></i> <?php echo htmlspecialchars(substr($evt['date'], 0, 16)); ?></li>
                  <li><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($evt['localization']); ?></li>
                </ul>
                <a class="explore-btn small" href="event_page.php?id=<?php echo urlencode($evt['id']); ?>">Ver evento</a>
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


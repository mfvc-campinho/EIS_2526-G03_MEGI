<?php
session_start();
require_once __DIR__ . '/../includes/data_loader.php';
$data = load_app_data($mysqli);
$mysqli->close();

$collections = $data['collections'] ?? [];
$items = $data['items'] ?? [];
$events = $data['events'] ?? [];
$collectionItems = $data['collectionItems'] ?? [];
$collectionEvents = $data['collectionEvents'] ?? [];
$userShowcases = $data['userShowcases'] ?? [];
$isAuth = !empty($_SESSION['user']);
$currentUserId = $isAuth ? ($_SESSION['user']['id'] ?? null) : null;

$collectionId = $_GET['id'] ?? null;
$collection = null;
foreach ($collections as $c) {
  if (!empty($c['id']) && $c['id'] === $collectionId) {
    $collection = $c;
    break;
  }
}

// helpers
$itemsById = [];
foreach ($items as $it) {
  if (!empty($it['id'])) $itemsById[$it['id']] = $it;
}
$eventsById = [];
foreach ($events as $ev) {
  if (!empty($ev['id'])) $eventsById[$ev['id']] = $ev;
}
$itemsForCollection = [];
foreach ($collectionItems as $link) {
  if (($link['collectionId'] ?? null) === $collectionId && isset($itemsById[$link['itemId']])) {
    $itemsForCollection[] = $itemsById[$link['itemId']];
  }
}
$eventsForCollection = [];
foreach ($collectionEvents as $link) {
  if (($link['collectionId'] ?? null) === $collectionId && isset($eventsById[$link['eventId']])) {
    $eventsForCollection[] = $eventsById[$link['eventId']];
  }
}

$img = $collection['coverImage'] ?? '../../images/default.jpg';
if ($img && !preg_match('#^https?://#', $img)) {
  $img = '../../' . ltrim($img, './');
}
$isOwner = $isAuth && $collection && ($collection['ownerId'] ?? null) === $currentUserId;

$likedItems = [];
$itemLikeCount = [];
foreach ($userShowcases as $sc) {
  $uid = $sc['ownerId'] ?? null;
  $likes = $sc['likedItems'] ?? [];
  foreach ($likes as $iid) {
    $itemLikeCount[$iid] = ($itemLikeCount[$iid] ?? 0) + 1;
    if ($uid === $currentUserId) $likedItems[$iid] = true;
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Collection — GoodCollections</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../CSS/general.css">
  <link rel="stylesheet" href="../../CSS/specific_collection.css">
  <link rel="stylesheet" href="../../CSS/likes.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <script src="../../JS/theme-toggle.js"></script>
  <style>
    /* Align items grid visually with all_collections cards */
    body { background: #f5f6f8; }
    .page-shell { max-width: 1200px; margin: 0 auto; padding: 20px 20px 60px; }
    .collection-hero {
      margin: 20px 0;
      border-radius: 18px;
      overflow: hidden;
      box-shadow: 0 18px 36px rgba(0,0,0,0.1);
    }
    .collection-hero img { width: 100%; max-height: 360px; object-fit: cover; display: block; }
    .collection-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 360px));
      gap: 24px;
      margin-top: 16px;
      justify-content: center;
    }
    .product-card {
      background: #fff;
      border-radius: 18px;
      box-shadow: 0 14px 30px rgba(0,0,0,0.08);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      transition: transform .15s ease, box-shadow .2s ease;
      min-height: 420px;
    }
    .product-card:hover { transform: translateY(-4px); box-shadow: 0 18px 36px rgba(0,0,0,0.1); }
    .product-card__media img { width: 100%; height: 210px; object-fit: cover; display: block; }
    .product-card__body { padding: 16px 18px 14px; flex: 1; display: flex; flex-direction: column; gap: 10px; }
    .product-card h3 { margin: 0; font-size: 1.05rem; }
    .product-card__meta { display: flex; justify-content: space-between; color: #6b7280; font-size: 0.9rem; }
    .card-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: auto; padding-top: 6px; }
  </style>
</head>

<body>
  <?php include __DIR__ . '/../includes/nav.php'; ?>

  <main class="page-shell">
    <nav class="breadcrumb-nav" aria-label="Breadcrumb">
      <ol class="breadcrumb-list">
        <li class="breadcrumb-item"><a href="home_page.php">Home</a></li>
        <li class="breadcrumb-item"><a href="all_collections.php">Collections</a></li>
        <li class="breadcrumb-item" aria-current="page">Collection</li>
      </ol>
    </nav>

    <?php if (!$collection): ?>
      <section class="collection-header">
        <p class="muted">Collection not found.</p>
      </section>
    <?php else: ?>
      <section class="collection-header">
        <div class="collection-hero">
          <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($collection['name'] ?? ''); ?>">
        </div>
        <div class="collection-info">
          <p class="pill"><?php echo htmlspecialchars($collection['type'] ?? ''); ?></p>
          <h1><?php echo htmlspecialchars($collection['name'] ?? ''); ?></h1>
          <p class="muted"><?php echo htmlspecialchars($collection['summary'] ?? ''); ?></p>
          <p><?php echo htmlspecialchars($collection['description'] ?? ''); ?></p>
          <?php if ($isOwner): ?>
            <div class="actions" style="margin-top:12px; gap:10px;">
              <a class="explore-btn ghost" href="collections_form.php?id=<?php echo urlencode($collection['id']); ?>">Editar</a>
              <form action="collections_action.php" method="POST" style="display:inline;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($collection['id']); ?>">
                <button type="submit" class="explore-btn danger" onclick="return confirm('Apagar coleção?');">Delete</button>
              </form>
            </div>
          <?php endif; ?>
        </div>
      </section>

      <section class="items-section">
        <h2>Items</h2>
        <div class="collection-grid">
          <?php if ($itemsForCollection): ?>
            <?php foreach ($itemsForCollection as $it): ?>
              <?php
                $thumb = $it['image'] ?? '';
                if ($thumb && !preg_match('#^https?://#', $thumb)) {
                  $thumb = '../../' . ltrim($thumb, './');
                }
                $importance = $it['importance'] ?? 'Item';
                $price = $it['price'] ?? '-';
                $date = substr($it['acquisitionDate'] ?? '', 0, 10);
                $isItemOwner = $isOwner; // same owner as collection
              ?>
              <article class="product-card">
                <a href="item_page.php?id=<?php echo urlencode($it['id']); ?>" class="product-card__media">
                  <img src="<?php echo htmlspecialchars($thumb ?: '../../images/default.jpg'); ?>" alt="<?php echo htmlspecialchars($it['name'] ?? ''); ?>">
                </a>
                <div class="product-card__body">
                  <p class="pill"><?php echo htmlspecialchars($importance); ?></p>
                  <h3><a href="item_page.php?id=<?php echo urlencode($it['id']); ?>"><?php echo htmlspecialchars($it['name'] ?? ''); ?></a></h3>
                  <p class="muted">Price: <?php echo htmlspecialchars($price); ?></p>
                  <div class="product-card__meta">
                    <span><i class="bi bi-calendar3"></i> <?php echo htmlspecialchars($date ?: ''); ?></span>
                    <span><i class="bi bi-box-seam"></i> <?php echo htmlspecialchars($collection['name'] ?? ''); ?></span>
                  </div>
                  <div class="card-actions">
                    <a class="explore-btn" href="item_page.php?id=<?php echo urlencode($it['id']); ?>">Explore More</a>
                    <form action="likes_action.php" method="POST" style="display:inline;">
                      <input type="hidden" name="type" value="item">
                      <input type="hidden" name="id" value="<?php echo htmlspecialchars($it['id']); ?>">
                      <button type="submit" class="explore-btn ghost<?php echo isset($likedItems[$it['id']]) ? ' success' : ''; ?>">
                        <i class="bi <?php echo isset($likedItems[$it['id']]) ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
                        <?php echo $itemLikeCount[$it['id']] ?? 0; ?>
                      </button>
                    </form>
                    <?php if ($isItemOwner): ?>
                      <a class="explore-btn ghost" href="items_form.php?id=<?php echo urlencode($it['id']); ?>">Edit</a>
                      <form action="items_action.php" method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($it['id']); ?>">
                        <button type="submit" class="explore-btn ghost danger" onclick="return confirm('Delete this item?');">Delete</button>
                      </form>
                    <?php endif; ?>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="muted">No items in this collection.</p>
          <?php endif; ?>
        </div>
      </section>

      <section class="events-section">
        <h2>Events</h2>
        <div class="events-grid">
          <?php if ($eventsForCollection): ?>
            <?php foreach ($eventsForCollection as $ev): ?>
              <article class="event-card">
                <p class="pill"><?php echo htmlspecialchars($ev['type'] ?? 'Evento'); ?></p>
                <h3><?php echo htmlspecialchars($ev['name'] ?? ''); ?></h3>
                <p class="muted"><?php echo htmlspecialchars($ev['summary'] ?? ''); ?></p>
                <ul class="event-meta">
                  <li><i class="bi bi-calendar-event"></i> <?php echo htmlspecialchars(substr($ev['date'] ?? '', 0, 16)); ?></li>
                  <li><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($ev['localization'] ?? ''); ?></li>
                </ul>
                <a class="explore-btn ghost" href="event_page.php?id=<?php echo urlencode($ev['id']); ?>">Ver evento</a>
              </article>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="muted">No events linked to this collection.</p>
          <?php endif; ?>
        </div>
      </section>
    <?php endif; ?>
  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>

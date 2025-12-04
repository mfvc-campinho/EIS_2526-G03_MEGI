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
</head>

<body>
  <?php include __DIR__ . '/../includes/nav.php'; ?>

  <main>
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
        <div class="collection-cover">
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
        <div class="items-grid">
          <?php if ($itemsForCollection): ?>
            <?php foreach ($itemsForCollection as $it): ?>
              <?php
                $thumb = $it['image'] ?? '../../images/default.jpg';
                if ($thumb && !preg_match('#^https?://#', $thumb)) {
                  $thumb = '../../' . ltrim($thumb, './');
                }
              ?>
              <article class="item-card">
                <div class="item-thumb">
                  <img src="<?php echo htmlspecialchars($thumb); ?>" alt="<?php echo htmlspecialchars($it['name'] ?? ''); ?>">
                </div>
                <div class="item-body">
                  <h3><a href="item_page.php?id=<?php echo urlencode($it['id']); ?>"><?php echo htmlspecialchars($it['name'] ?? ''); ?></a></h3>
                  <p class="muted">Importance: <?php echo htmlspecialchars($it['importance'] ?? '-'); ?></p>
                  <p class="muted">Price: <?php echo htmlspecialchars($it['price'] ?? '-'); ?></p>
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

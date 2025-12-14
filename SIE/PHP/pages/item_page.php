<?php
session_start();
require_once __DIR__ . '/../includes/data_loader.php';
$data = load_app_data($mysqli);
$mysqli->close();

$items = $data['items'] ?? [];
$collections = $data['collections'] ?? [];
$collectionItems = $data['collectionItems'] ?? [];

$collectionMap = [];
foreach ($collections as $c) {
  if (!empty($c['id'])) $collectionMap[$c['id']] = $c;
}

$itemId = $_GET['id'] ?? null;
$item = null;
foreach ($items as $it) {
  if (!empty($it['id']) && $it['id'] === $itemId) {
    $item = $it;
    break;
  }
}

// Find collection through collectionItems relationship
$col = null;
if ($item) {
  foreach ($collectionItems as $link) {
    if (($link['itemId'] ?? null) === $itemId) {
      $cid = $link['collectionId'] ?? null;
      if ($cid && isset($collectionMap[$cid])) {
        $col = $collectionMap[$cid];
        break;
      }
    }
  }
}

$isAuth = !empty($_SESSION['user']);
$currentUserId = $isAuth ? ($_SESSION['user']['id'] ?? null) : null;

$img = $item['image'] ?? '../../images/default.jpg';
if ($img && !preg_match('#^https?://#', $img)) {
  $img = '../../' . ltrim($img, './');
}
$isOwner = $isAuth && $col && ($col['ownerId'] ?? null) === $currentUserId;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Item Details • GoodCollections</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../CSS/general.css">
  <link rel="stylesheet" href="../../CSS/navbar.css">
  <link rel="stylesheet" href="../../CSS/item_page.css">
  <link rel="stylesheet" href="../../CSS/likes.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../../CSS/christmas.css">
  <script src="../../JS/theme-toggle.js"></script>
  <script src="../../JS/christmas-theme.js"></script>
</head>

<body>
  <?php include __DIR__ . '/../includes/nav.php'; ?>

  <main>
    <nav id="page-breadcrumb" class="breadcrumb-nav" aria-label="Breadcrumb">
      <ol class="breadcrumb-list">
        <li class="breadcrumb-item"><a href="home_page.php">Home</a></li>
        <li class="breadcrumb-item"><a href="all_collections.php">Collections</a></li>
        <?php if ($col): ?>
          <li class="breadcrumb-item"><a href="specific_collection.php?id=<?php echo urlencode($col['id']); ?>"><?php echo htmlspecialchars($col['name'] ?? 'Collection'); ?></a></li>
        <?php endif; ?>
        <li class="breadcrumb-item" aria-current="page"><?php echo htmlspecialchars($item['name'] ?? 'Item'); ?></li>
      </ol>
    </nav>

    <?php if (!$item): ?>
      <section class="item-details-card">
        <p class="muted">Item not found.</p>
      </section>
    <?php else: ?>
      <section id="item-details" class="item-details-card">
        <?php if (!empty($item['image'])): ?>
          <img id="item-image-display" src="<?php echo htmlspecialchars($img); ?>" alt="Item image">
        <?php else: ?>
          <div class="card-image-placeholder">
            No image available
          </div>
        <?php endif; ?>
        <div class="item-details-text">
          <h1 id="item-name-display"><?php echo htmlspecialchars($item['name'] ?? ''); ?></h1>
          <p><strong>Importance:</strong> <span id="item-importance-display"><?php echo htmlspecialchars($item['importance'] ?? '-'); ?></span></p>
          <p><strong>Weight:</strong> <span id="item-weight-display"><?php echo htmlspecialchars($item['weight'] ?? '-'); ?></span> g</p>
          <p><strong>Price:</strong> €<span id="item-price-display"><?php echo htmlspecialchars($item['price'] ?? '-'); ?></span></p>
          <p><strong>Acquisition Date:</strong> <span id="item-date-display"><?php echo htmlspecialchars($item['acquisitionDate'] ?? '-'); ?></span></p>
          <?php if ($col): ?>
            <p><strong>Collection:</strong> <a href="specific_collection.php?id=<?php echo urlencode($col['id']); ?>"><?php echo htmlspecialchars($col['name'] ?? ''); ?></a></p>
          <?php endif; ?>
        </div>
      </section>

      <div id="buttons-bar">
        <a href="javascript:history.back()" class="explore-btn ghost">
          <i class="bi bi-arrow-left"></i>
          Back
        </a>
        <?php if ($isOwner): ?>
          <a class="action-icon" href="items_form.php?id=<?php echo urlencode($item['id']); ?>" title="Edit">
            <i class="bi bi-pencil"></i>
          </a>
          <form action="items_action.php" method="POST" class="action-icon-form" style="margin:0;">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($item['id']); ?>">
            <input type="hidden" name="return_to" value="specific_collection.php?id=<?php echo urlencode($col['id'] ?? ''); ?>">
            <button type="submit" class="action-icon is-danger" title="Delete" onclick="return confirm('Delete this item?');">
              <i class="bi bi-trash"></i>
            </button>
          </form>
        <?php endif; ?>
      </div>

      <section class="item-relations">
        <h2 class="sr-only">Item relations</h2>
        <article class="item-relations-card">
          <div class="card-header">
            <i class="bi bi-box-seam-fill" aria-hidden="true"></i>
            <div>
              <p class="eyebrow-label">Collections</p>
              <h2>Where this item lives</h2>
            </div>
          </div>
          <div class="related-list">
            <?php if ($col): ?>
              <div class="related-chip">
                <i class="bi bi-box"></i>
                <a href="specific_collection.php?id=<?php echo urlencode($col['id']); ?>"><?php echo htmlspecialchars($col['name'] ?? ''); ?></a>
              </div>
            <?php else: ?>
              <p class="muted">No related collections.</p>
            <?php endif; ?>
          </div>
        </article>
      </section>
    <?php endif; ?>
  </main>

  <!-- Back to Top Button -->
  <button id="backToTop" class="back-to-top" aria-label="Back to top">
    <i class="bi bi-arrow-up"></i>
  </button>

  <?php include __DIR__ . '/../includes/footer.php'; ?>
  <script src="../../JS/back-to-top.js"></script>
</body>

</html>

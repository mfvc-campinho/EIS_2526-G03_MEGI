<?php
session_start();
require_once __DIR__ . '/../includes/data_loader.php';
$data = load_app_data($mysqli);
$mysqli->close();
$collections = $data['collections'] ?? [];
$items = $data['items'] ?? [];
$collectionItems = $data['collectionItems'] ?? [];

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

$isAuth = !empty($_SESSION['user']);
$currentUserId = $_SESSION['user']['id'] ?? null;

// Controls
$sort = $_GET['sort'] ?? 'newest';
$perPage = max(1, (int)($_GET['perPage'] ?? 10));
$page = max(1, (int)($_GET['page'] ?? 1));

usort($collections, function ($a, $b) use ($sort) {
  $aDate = $a['createdAt'] ?? '';
  $bDate = $b['createdAt'] ?? '';
  if ($sort === 'oldest') {
    return strcmp($aDate, $bDate);
  }
  if ($sort === 'name') {
    return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
  }
  // newest (default)
  return strcmp($bDate, $aDate);
});

$total = count($collections);
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;
$collectionsPage = array_slice($collections, $offset, $perPage);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Collections - GoodCollections</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../CSS/general.css">
  <link rel="stylesheet" href="../../CSS/likes.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <script src="../../JS/theme-toggle.js"></script>
  <style>
    body { background: #f5f6f8; }
    .page-shell { max-width: 1200px; margin: 0 auto; padding: 20px 20px 60px; }
    .top-controls { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin: 12px 0 24px; flex-wrap: wrap; }
    .top-controls .left { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; color: #555; font-weight: 500; }
    .top-controls select { padding: 8px 10px; border-radius: 12px; border: 1px solid #d0d5dd; background: #fff; font-weight: 600; }
    .paginate { display: flex; align-items: center; gap: 10px; color: #666; font-weight: 500; }
    .paginate button { border: none; background: #e9ecf2; padding: 10px 12px; border-radius: 12px; cursor: pointer; }
    .paginate button:disabled { opacity: .35; cursor: default; }
    .collection-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; }
    .product-card { background: #fff; border-radius: 18px; box-shadow: 0 14px 30px rgba(0,0,0,0.08); overflow: hidden; display: flex; flex-direction: column; transition: transform .15s ease, box-shadow .2s ease; min-height: 460px; }
    .product-card:hover { transform: translateY(-4px); box-shadow: 0 18px 36px rgba(0,0,0,0.1); }
    .product-card__media img { width: 100%; height: 210px; object-fit: cover; display: block; }
    .product-card__body { padding: 16px 18px 10px; flex: 1; display: flex; flex-direction: column; gap: 8px; }
    .product-card h3 { margin: 0; font-size: 1.05rem; }
    .muted { color: #6b7280; }
    .product-card__meta { display: flex; justify-content: space-between; color: #6b7280; font-size: 0.9rem; }
    .card-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 6px; }
    .preview-toggle { display: none; }
    .preview-items { display: none; padding: 0 18px 16px; }
    .preview-item { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; color: #374151; text-decoration: none; }
    .preview-item img { width: 44px; height: 44px; border-radius: 8px; object-fit: cover; }
    .preview-toggle:not(:checked) + .product-card__body .preview-hide { display: none; }
    .preview-toggle:checked + .product-card__body .preview-show { display: none; }
    .preview-toggle:checked + .product-card__body .preview-hide { display: inline-flex; }
    .preview-toggle:checked ~ .preview-items { display: block; }
    .owner-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px; }
  </style>
</head>

<body>
  <div id="content">
    <?php include __DIR__ . '/../includes/nav.php'; ?>

    <main class="page-shell">
      <nav class="breadcrumb-nav" aria-label="Breadcrumb">
        <ol class="breadcrumb-list">
          <li class="breadcrumb-item"><a href="home_page.php">Home</a></li>
          <li class="breadcrumb-item" aria-current="page">Collections</li>
        </ol>
      </nav>

      <div class="top-controls">
        <div class="left">
          <form id="filters" method="GET" style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <label for="sort-select"><i class="bi bi-funnel"></i> Sort by</label>
            <select name="sort" id="sort-select" onchange="this.form.submit()">
              <option value="newest" <?php echo $sort==='newest'?'selected':''; ?>>Last Added</option>
              <option value="oldest" <?php echo $sort==='oldest'?'selected':''; ?>>Oldest First</option>
              <option value="name" <?php echo $sort==='name'?'selected':''; ?>>Name A-Z</option>
            </select>
            <label>Show
              <select name="perPage" onchange="this.form.submit()">
                <?php foreach ([5,10,20] as $opt): ?>
                  <option value="<?php echo $opt; ?>" <?php echo $perPage==$opt?'selected':''; ?>><?php echo $opt; ?></option>
                <?php endforeach; ?>
              </select>
              collections per page
            </label>
            <input type="hidden" name="page" value="1">
          </form>
        </div>
        <div class="paginate">
          <?php if ($isAuth): ?>
            <a class="explore-btn success" href="collections_form.php">+ Add Collection</a>
          <?php endif; ?>
          <button <?php echo $page<=1?'disabled':''; ?> onclick="window.location='?<?php echo http_build_query(['sort'=>$sort,'perPage'=>$perPage,'page'=>max(1,$page-1)]); ?>'"><i class="bi bi-chevron-left"></i></button>
          <span>Showing <?php echo $offset+1; ?>-<?php echo min($offset+$perPage, $total); ?> of <?php echo $total; ?></span>
          <button <?php echo $page>=$pages?'disabled':''; ?> onclick="window.location='?<?php echo http_build_query(['sort'=>$sort,'perPage'=>$perPage,'page'=>min($pages,$page+1)]); ?>'"><i class="bi bi-chevron-right"></i></button>
        </div>
      </div>

      <section class="collection-grid">
        <?php if ($collectionsPage): ?>
          <?php foreach ($collectionsPage as $col): ?>
            <?php
              $img = $col['coverImage'] ?? '';
              if ($img && !preg_match('#^https?://#', $img)) {
                $img = '../../' . ltrim($img, './');
              }
              $previewItems = array_slice($itemsByCollection[$col['id']] ?? [], 0, 2);
              $previewId = 'preview-' . htmlspecialchars($col['id']);
              $isOwner = $isAuth && !empty($col['ownerId']) && $col['ownerId'] === $currentUserId;
            ?>
            <article class="product-card">
              <a href="specific_collection.php?id=<?php echo urlencode($col['id']); ?>" class="product-card__media">
                <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($col['name']); ?>">
              </a>
              <input type="checkbox" id="<?php echo $previewId; ?>" class="preview-toggle">
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
                <?php if ($isOwner): ?>
                  <div class="owner-actions">
                    <a class="explore-btn ghost" href="collections_form.php?id=<?php echo urlencode($col['id']); ?>"><i class="bi bi-pencil"></i> Edit</a>
                    <form action="collections_action.php" method="POST">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?php echo htmlspecialchars($col['id']); ?>">
                      <button type="submit" class="explore-btn ghost danger" onclick="return confirm('Apagar esta coleção?');"><i class="bi bi-trash"></i> Delete</button>
                    </form>
                  </div>
                <?php endif; ?>
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
          <p class="muted">Sem coleções registadas.</p>
        <?php endif; ?>
      </section>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
  </div>
</body>

</html>

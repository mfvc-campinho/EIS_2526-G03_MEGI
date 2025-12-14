<?php
session_start();
require_once __DIR__ . '/../includes/data_loader.php';
require_once __DIR__ . '/../includes/flash.php';
$data = load_app_data($mysqli);
$mysqli->close();
$collections = $data['collections'] ?? [];
$items = $data['items'] ?? [];
$collectionItems = $data['collectionItems'] ?? [];
$userShowcases = $data['userShowcases'] ?? [];
$users = $data['users'] ?? [];

// Build usersById lookup
$usersById = [];
foreach ($users as $u) {
    $uid = $u['id'] ?? $u['user_id'] ?? null;
    if ($uid) {
        $usersById[$uid] = $u;
    }
}

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

// Collection types (same as in collections_form.php)
$baseCollectionTypes = [
    'Collectible Cards',
    'Coins',
    'Stamps',
    'Board Games',
    'Toys & Figures',
    'Comics',
    'Memorabilia',
    'Other'
];
$extraTypes = [];
foreach ($collections as $col) {
    $typeValue = trim($col['type'] ?? '');
    if ($typeValue === '') {
        continue;
    }
    if (!in_array($typeValue, $baseCollectionTypes, true) && !in_array($typeValue, $extraTypes, true)) {
        $extraTypes[] = $typeValue;
    }
}
sort($extraTypes, SORT_NATURAL | SORT_FLAG_CASE);
$collectionTypes = array_merge($baseCollectionTypes, $extraTypes);

$isAuth = !empty($_SESSION['user']);
$currentUserId = $_SESSION['user']['id'] ?? null;
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
    if ($isAuth) {
        $stmt = $mysqli->prepare("SELECT liked_collection_id FROM user_liked_collections WHERE user_id = ?");
        $stmt->bind_param('s', $currentUserId);
        $stmt->execute();
        $res2 = $stmt->get_result();
        while ($row = $res2->fetch_assoc()) {
            $cid = $row['liked_collection_id'] ?? null;
            if ($cid) $likedCollections[$cid] = true;
        }
        $stmt->close();
    }
    $mysqli->close();
}
// Controls
$sort = $_GET['sort'] ?? 'newest';
$perPage = max(1, (int) ($_GET['perPage'] ?? 10));
$page = max(1, (int) ($_GET['page'] ?? 1));
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$typeFilter = isset($_GET['type']) ? trim($_GET['type']) : '';
$myCollections = isset($_GET['mine']) && $_GET['mine'] === '1';

// Apply search and type filters BEFORE sorting
$filteredCollections = $collections;

// Filter by search query
if ($searchQuery !== '') {
    $temp = [];
    foreach ($filteredCollections as $col) {
        $name = strtolower($col['name'] ?? '');
        $summary = strtolower($col['summary'] ?? '');
        $type = strtolower($col['type'] ?? '');
        $query = strtolower($searchQuery);
        
        if (strpos($name, $query) !== false || 
            strpos($summary, $query) !== false || 
            strpos($type, $query) !== false) {
            $temp[] = $col;
        }
    }
    $filteredCollections = $temp;
}

// Filter by type/category
if ($typeFilter !== '' && $typeFilter !== 'all') {
    $temp = [];
    foreach ($filteredCollections as $col) {
        if (($col['type'] ?? '') === $typeFilter) {
            $temp[] = $col;
        }
    }
    $filteredCollections = $temp;
}

// Filter by ownership (My Collections)
if ($myCollections && $isAuth && $currentUserId) {
    $temp = [];
    foreach ($filteredCollections as $col) {
        if (($col['ownerId'] ?? null) === $currentUserId) {
            $temp[] = $col;
        }
    }
    $filteredCollections = $temp;
}

// Sort the filtered collections
usort($filteredCollections, function ($a, $b) use ($sort) {
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

$total = count($filteredCollections);
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;
$collectionsPage = array_slice($filteredCollections, $offset, $perPage);
?>
<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Collections • GoodCollections</title>

        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

        <!-- estilos globais -->
        <link rel="stylesheet" href="../../CSS/general.css">
        <link rel="stylesheet" href="././CSS/likes.css">

        <!-- estilos específicos desta página -->
        <link rel="stylesheet" href="../../CSS/all_collections.css">

        <!-- ícones + tema -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="../../CSS/christmas.css">
        <script src="././JS/theme-toggle.js"></script>
        <script src="../../JS/christmas-theme.js"></script>

    </head>

    <body>
        <div id="content">
            <?php include __DIR__ . '/../includes/nav.php'; ?>

            <main class="page-shell">
                <?php flash_render(); ?>
                <nav class="breadcrumb-nav" aria-label="Breadcrumb">
                    <ol class="breadcrumb-list">
                        <li class="breadcrumb-item"><a href="home_page.php">Home</a></li>
                        <li class="breadcrumb-item" aria-current="page">Collections</li>
                    </ol>
                </nav>

                <section class="collections-hero">
                    <h1>All Collections</h1>
                    <div class="collections-hero-underline"></div>
                    <p>Browse and manage all available collections.</p>
                </section>


                <div class="top-controls">
                    <div class="left">
                        <form id="filters" class="filters-form" method="GET">
                            <div class="filter-chip filter-chip--search">
                                <label class="filter-chip__label" for="search-input">
                                    <i class="bi bi-search"></i>
                                    <span>Search</span>
                                </label>
                                <input id="search-input" type="search" name="search" class="filter-chip__input" placeholder="Search collections..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                            </div>
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

                            <div class="filter-chip filter-chip--select">
                                <label class="filter-chip__label" for="type-select">
                                    <i class="bi bi-tag"></i>
                                    <span>Type</span>
                                </label>
                                <select name="type" id="type-select" class="filter-chip__select" onchange="gcSubmitWithScroll(this.form)">
                                    <option value="all" <?php echo $typeFilter === '' || $typeFilter === 'all' ? 'selected' : ''; ?>>All Types</option>
                                    <?php foreach ($collectionTypes as $typeOption): ?>
                                        <option value="<?php echo htmlspecialchars($typeOption); ?>" 
                                            <?php echo $typeFilter === $typeOption ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($typeOption); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <?php if ($isAuth): ?>
                                <?php 
                                    $myCollectionsCount = 0;
                                    foreach ($collections as $col) {
                                        if (($col['ownerId'] ?? null) === $currentUserId) {
                                            $myCollectionsCount++;
                                        }
                                    }
                                ?>
                                <button type="button" class="filter-chip filter-toggle <?php echo $myCollections ? 'is-active' : ''; ?>" 
                                    onclick="toggleMyCollections(this.form, <?php echo $myCollections ? 'false' : 'true'; ?>)">
                                    <?php if ($myCollections): ?>
                                        <i class="bi bi-grid-3x3-gap"></i>
                                        <span>All Collections</span>
                                    <?php else: ?>
                                        <i class="bi bi-person-circle"></i>
                                        <span>My Collections</span>
                                        <span class="filter-toggle__badge"><?php echo $myCollectionsCount; ?></span>
                                    <?php endif; ?>
                                </button>
                                <input type="hidden" name="mine" value="<?php echo $myCollections ? '1' : '0'; ?>">
                            <?php endif; ?>

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
                        <?php if ($isAuth): ?>
                            <a class="explore-btn success" href="collections_form.php">+ Add Collection</a>
                        <?php endif; ?>
                        <button <?php echo $page <= 1 ? 'disabled' : ''; ?> onclick="gcRememberScroll('?<?php echo http_build_query(array_merge($_GET, ['page' => max(1, $page - 1)])); ?>')"><i class="bi bi-chevron-left"></i></button>
                        <span>Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $perPage, $total); ?> of <?php echo $total; ?></span>
                        <button <?php echo $page >= $pages ? 'disabled' : ''; ?> onclick="gcRememberScroll('?<?php echo http_build_query(array_merge($_GET, ['page' => min($pages, $page + 1)])); ?>')"><i class="bi bi-chevron-right"></i></button>
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
                            $collectionHref = 'specific_collection.php?id=' . urlencode($col['id']);
                            ?>
                            <article class="collection-card collection-card-link" role="link" tabindex="0" data-collection-link="<?php echo htmlspecialchars($collectionHref); ?>">
                                <a href="<?php echo htmlspecialchars($collectionHref); ?>" class="collection-card__media">
                                    <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($col['name']); ?>">
                                </a>
                                <input type="checkbox" id="<?php echo $previewId; ?>" class="preview-toggle">
                                <div class="collection-card__body">
                                    <p class="pill"><?php echo htmlspecialchars($col['type'] ?? ''); ?></p>
                                    <h3><a href="<?php echo htmlspecialchars($collectionHref); ?>"><?php echo htmlspecialchars($col['name']); ?></a></h3>
                                    <?php if (!empty($col['summary'])): ?>
                                        <p class="muted"><?php echo htmlspecialchars($col['summary']); ?></p>
                                    <?php endif; ?>
                                    <div class="collection-card__meta collection-card__meta--center">
                                        <div class="collection-card__owner">
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
                                        <div class="collection-card__date">
                                            <i class="bi bi-calendar3"></i>
                                            <?php echo htmlspecialchars(substr($col['createdAt'], 0, 10)); ?>
                                        </div>
                                    </div>
                                    <div class="collection-card__actions card-actions">
                                        <!-- Expand/Collapse Preview -->
                                        <label class="action-icon" for="<?php echo $previewId; ?>" title="Expand">
                                            <i class="bi bi-plus-lg"></i>
                                        </label>

                                        <!-- Like -->
                                        <?php if ($isAuth): ?>
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
                                            <a class="action-icon" href="collections_form.php?id=<?php echo urlencode($col['id']); ?>&from=all_collections" title="Edit">
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
                        <p class="muted">No registered collections.</p>
                    <?php endif; ?>
                </section>
            </main>

            <?php include __DIR__ . '/../includes/footer.php'; ?>
        </div>
        <script src="../../JS/gc-scroll-restore.js"></script>

      <script src="../../JS/all_collections.js"></script>
</body>

</html>

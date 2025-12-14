<?php
session_start();

require_once __DIR__ . '/../includes/data_loader.php';
require_once __DIR__ . '/../includes/flash.php';

$data = load_app_data($mysqli);
$mysqli->close();

// Dados vindos do data_loader
$collections = $data['collections'] ?? [];
$items = $data['items'] ?? [];
$events = $data['events'] ?? [];
$collectionItems = $data['collectionItems'] ?? [];
$collectionEvents = $data['collectionEvents'] ?? [];
$userShowcases = $data['userShowcases'] ?? [];
$users = $data['users'] ?? [];

// Build user lookup by ID
$usersById = [];
foreach ($users as $u) {
    $uid = $u['id'] ?? $u['user_id'] ?? null;
    if ($uid) {
        $usersById[$uid] = $u;
    }
}

// Utilizador autenticado
$isAuthenticated = !empty($_SESSION['user']);
$currentUserId = $isAuthenticated ? ($_SESSION['user']['id'] ?? null) : null;

// -----------------------------
//   Obter coleção atual
// -----------------------------
$collectionId = $_GET['id'] ?? null;
$collection = null;

foreach ($collections as $c) {
    if (!empty($c['id']) && (string) $c['id'] === (string) $collectionId) {
        $collection = $c;
        break;
    }
}

// Se não houver coleção, mostramos apenas mensagem mais abaixo
// Owner da coleção (tolerante a nomes diferentes de coluna)
$collectionOwnerId = $collection['owner_id'] ?? $collection['user_id'] ?? $collection['ownerId'] ?? null;

$isOwner = $isAuthenticated && $collection && ((string) $collectionOwnerId === (string) $currentUserId);

// -----------------------------
//   Mapas auxiliares
// -----------------------------
$itemsById = [];
foreach ($items as $it) {
    if (!empty($it['id'])) {
        $itemsById[$it['id']] = $it;
    }
}

$eventsById = [];
foreach ($events as $ev) {
    if (!empty($ev['id'])) {
        $eventsById[$ev['id']] = $ev;
    }
}

// -----------------------------
//   Itens da coleção
// -----------------------------
$itemsForCollection = [];

// 1) via tabela de ligação collectionItems
foreach ($collectionItems as $link) {
    $cid = $link['collectionId'] ?? $link['collection_id'] ?? null;
    $iid = $link['itemId'] ?? $link['item_id'] ?? null;
    if ($cid === $collectionId && $iid && isset($itemsById[$iid])) {
        $itemsForCollection[] = $itemsById[$iid];
    }
}

// 2) fallback: itens que têm a collection associada diretamente
if (!$itemsForCollection && $collectionId) {
    foreach ($items as $it) {
        $cid = $it['collectionId'] ?? $it['collection_id'] ?? null;
        if ($cid === $collectionId) {
            $itemsForCollection[] = $it;
        }
    }
}

// -----------------------------
//   Eventos da coleção (apenas futuros)
// -----------------------------
$eventsForCollection = [];
$today = date('Y-m-d');

foreach ($collectionEvents as $link) {
    $cid = $link['collectionId'] ?? $link['collection_id'] ?? null;
    $eid = $link['eventId'] ?? $link['event_id'] ?? null;

    if ($cid === $collectionId && $eid && isset($eventsById[$eid])) {
        $event = $eventsById[$eid];
        $eventDate = substr($event['date'] ?? '', 0, 10);
        
        // Apenas eventos futuros (data >= hoje)
        if ($eventDate >= $today) {
            $eventsForCollection[] = $event;
        }
    }
}

// -----------------------------
//   Likes em items (se existir userShowcases)
// -----------------------------
$likedItems = [];
$itemLikeCount = [];

foreach ($userShowcases as $sc) {
    $uid = $sc['user_id'] ?? $sc['ownerId'] ?? null;
    $liked = $sc['likedItems'] ?? $sc['liked_items'] ?? [];

    if (!is_array($liked)) {
        continue;
    }

    foreach ($liked as $iid) {
        $itemLikeCount[$iid] = ($itemLikeCount[$iid] ?? 0) + 1;
        if ($uid && (string) $uid === (string) $currentUserId) {
            $likedItems[$iid] = true;
        }
    }
}

// -----------------------------
//   Sorting for items (match All Collections style)
// -----------------------------
$sort = $_GET['sort'] ?? 'newest';
$perPage = (int)($_GET['perPage'] ?? 10);
if (!in_array($perPage, [5,10,20], true)) { $perPage = 10; }
$page = max(1, (int)($_GET['page'] ?? 1));

if (!empty($itemsForCollection)) {
    usort($itemsForCollection, function ($a, $b) use ($sort, $itemLikeCount) {
        // Map item fields: use acquisitionDate/acquisition_date for date comparisons
        $dateA = strtotime($a['acquisitionDate'] ?? $a['acquisition_date'] ?? '') ?: 0;
        $dateB = strtotime($b['acquisitionDate'] ?? $b['acquisition_date'] ?? '') ?: 0;

        if ($sort === 'oldest') {
            return $dateA <=> $dateB;
        }
        if ($sort === 'name') {
            return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
        }
        // default: newest
        return $dateB <=> $dateA;
    });
}

// paginate items
$totalItems = count($itemsForCollection);
$pages = max(1, (int)ceil($totalItems / $perPage));
if ($page > $pages) { $page = $pages; }
$offset = ($page - 1) * $perPage;
$itemsPage = array_slice($itemsForCollection, $offset, $perPage);

// Hero / avatar (fallbacks)
$ownerAvatar = $collection['owner_avatar'] ?? $collection['user_image'] ?? '../../images/default.jpg';

// Get owner name from users array if available
$ownerName = 'Collection owner';
if ($collectionOwnerId && isset($usersById[$collectionOwnerId])) {
    $ownerName = $usersById[$collectionOwnerId]['user_name'] ?? $usersById[$collectionOwnerId]['username'] ?? 'Collection owner';
} else {
    $ownerName = $collection['owner_name'] ?? $collection['username'] ?? $collection['user_name'] ?? 'Collection owner';
}

$collectionStats = [];

if ($collection) {
    $coverImage = $collection['coverImage'] ?? $collection['cover_image'] ?? $collection['cover'] ?? '';
    if ($coverImage) {
        if (!preg_match('#^https?://#', $coverImage) && strpos($coverImage, '../../') !== 0) {
            $coverImage = '../../' . ltrim($coverImage, './');
        }
    }

    if ($ownerAvatar && !preg_match('#^https?://#', $ownerAvatar) && strpos($ownerAvatar, '../../') !== 0) {
        $ownerAvatar = '../../' . ltrim($ownerAvatar, './');
    }

    $itemsCount = count($itemsForCollection);
    $eventsCount = count($eventsForCollection);
    $totalValue = 0.0;
    $totalLikes = 0;

    foreach ($itemsForCollection as $it) {
        $priceRaw = $it['price'] ?? $it['value'] ?? 0;
        if (is_numeric($priceRaw)) {
            $totalValue += (float) $priceRaw;
        }

        $itemId = $it['id'] ?? null;
        if ($itemId && isset($itemLikeCount[$itemId])) {
            $totalLikes += (int) $itemLikeCount[$itemId];
        }
    }

    $createdAtRaw = $collection['createdAt'] ?? $collection['created_at'] ?? null;
    $createdLabel = 'Not available';
    if ($createdAtRaw) {
        $timestamp = strtotime($createdAtRaw);
        if ($timestamp) {
            $createdLabel = date('M d, Y', $timestamp);
        }
    }

    $collectionStats = [
        [
            'icon' => 'bi-box-seam',
            'value' => number_format($itemsCount),
            'label' => 'Items in collection',
        ],
        [
            'icon' => 'bi-heart',
            'value' => number_format($totalLikes),
            'label' => 'Total item likes',
        ],
        [
            'icon' => 'bi-currency-euro',
            'value' => $totalValue > 0 ? '€' . number_format($totalValue, 2, '.', ',') : '—',
            'label' => 'Estimated value',
        ],
        [
            'icon' => 'bi-calendar-event',
            'value' => number_format($eventsCount),
            'label' => 'Linked events',
        ],
        [
            'icon' => 'bi-clock-history',
            'value' => $createdLabel,
            'label' => 'Created on',
        ],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>
<?php echo htmlspecialchars($collection['name'] ?? 'Collection'); ?> — GoodCollections
        </title>

        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../../CSS/general.css">
        <link rel="stylesheet" href="../../CSS/specific_collection.css">
        <link rel="stylesheet" href="../../CSS/likes.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="../../CSS/christmas.css">
        <script src="././JS/theme-toggle.js"></script>
        <script src="../../JS/christmas-theme.js"></script>
    </head>

    <body data-collection-id="<?php echo htmlspecialchars($collectionId); ?>">
<?php include __DIR__ . '/../includes/nav.php'; ?>

        <main class="page-shell">
<?php flash_render(); ?>

            <nav class="breadcrumb-nav" aria-label="Breadcrumb">
                <ol class="breadcrumb-list">
                    <li class="breadcrumb-item"><a href="home_page.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="all_collections.php">Collections</a></li>
                    <li class="breadcrumb-item" aria-current="page">
        <?php echo htmlspecialchars($collection['name'] ?? 'Collection'); ?>
                    </li>
                </ol>
            </nav>

<?php if (!$collection): ?>
                <section class="collection-hero">
                    <p class="muted">Collection not found.</p>
                </section>
<?php else: ?>

                <!-- =========================
                     HERO / OVERVIEW DA COLEÇÃO
                     ========================= -->
                <section class="collection-hero">
                    <div id="collection-meta">
                        <div id="meta-text">
                            <div class="collection-type-line">
                                <span class="collection-type">
        <?php echo htmlspecialchars($collection['type'] ?? ''); ?>
                                </span>
                                <span class="dot">·</span>
                                <span class="collection-category">
                                    Collection
                                </span>
                            </div>

                            <h1 class="collection-title">
        <?php echo htmlspecialchars($collection['name'] ?? ''); ?>
                            </h1>

        <?php if (!empty($collection['summary'])): ?>
                                <p class="collection-summary">
            <?php echo htmlspecialchars($collection['summary']); ?>
                                </p>
        <?php endif; ?>

        <?php if (!empty($collection['description'])): ?>
                                <p id="description-line">
                                    <?php echo htmlspecialchars($collection['description']); ?>
                                </p>
        <?php endif; ?>

                            <p class="collection-owner-line">
                                Collection owner:
                                <a href="user_page.php?id=<?php echo urlencode($collectionOwnerId); ?>" class="owner-link">
            <?php echo htmlspecialchars($ownerName); ?>
                                </a>
                            </p>
                        </div>
                    </div>
                </section>

    <?php if (!empty($collectionStats)): ?>
                <section id="collection-stats" aria-labelledby="collection-stats-heading">
                    <h2 id="collection-stats-heading">Collection statistics</h2>
                    <div class="stats-grid">
        <?php foreach ($collectionStats as $stat): ?>
                        <article class="stat-card">
                            <div class="stat-icon" aria-hidden="true">
                                <i class="bi <?php echo htmlspecialchars($stat['icon']); ?>"></i>
                            </div>
                            <div class="stat-body">
                                <span class="stat-value"><?php echo htmlspecialchars($stat['value']); ?></span>
                                <span class="stat-label"><?php echo htmlspecialchars($stat['label']); ?></span>
                            </div>
                        </article>
        <?php endforeach; ?>
                    </div>
                </section>
    <?php endif; ?>


                <!-- BOTÕES PRINCIPAIS DA COLEÇÃO -->
                <div id="buttons-bar">
                    <a href="all_collections.php" class="explore-btn ghost">
                        <i class="bi bi-arrow-left"></i>
                        Back to collections
                    </a>

    <?php if ($isOwner): ?>
                        <a class="explore-btn"
                           href="collections_form.php?id=<?php echo urlencode($collection['id']); ?>">
                            <i class="bi bi-pencil-square"></i>
                            Edit Collection
                        </a>

                        <a class="explore-btn"
                           href="items_form.php?collectionId=<?php echo urlencode($collection['id']); ?>">
                            <i class="bi bi-plus-circle"></i>
                            Add Item
                        </a>

                        <form action="collections_action.php" method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id"
                                   value="<?php echo htmlspecialchars($collection['id']); ?>">
                            <button type="submit"
                                    class="explore-btn danger"
                                    onclick="return confirm('Delete collection?');">
                                <i class="bi bi-trash"></i>
                                Delete
                            </button>
                        </form>
    <?php endif; ?>
                </div>

                <!-- =========================
                     ITEMS
                     ========================= -->
                <section class="items-section" id="items-section">
                    <h2>Items</h2>

                    <div class="top-controls">
                        <div class="left">
                            <form id="filters" method="GET" action="specific_collection.php" style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                                <label for="sort-select"><i class="bi bi-funnel"></i> Sort by</label>
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($collectionId); ?>">
                                <input type="hidden" name="page" value="1">
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
                                    items per page
                                </label>
                            </form>
                        </div>
                        <div class="paginate">
                            <button <?php echo $page <= 1 ? 'disabled' : ''; ?> onclick="gcRememberScroll('specific_collection.php?<?php echo http_build_query(['id' => $collectionId, 'sort' => $sort, 'perPage' => $perPage, 'page' => max(1, $page - 1)]); ?>')"><i class="bi bi-chevron-left"></i></button>
                            <span>Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $perPage, $totalItems); ?> of <?php echo $totalItems; ?></span>
                            <button <?php echo $page >= $pages ? 'disabled' : ''; ?> onclick="gcRememberScroll('specific_collection.php?<?php echo http_build_query(['id' => $collectionId, 'sort' => $sort, 'perPage' => $perPage, 'page' => min($pages, $page + 1)]); ?>')"><i class="bi bi-chevron-right"></i></button>
                        </div>
                    </div>

    <?php if (!$itemsForCollection): ?>
                        <p class="muted">No items in this collection.</p>
                    <?php else: ?>
                        <div class="collection-container">
        <?php foreach ($itemsPage as $it): ?>
            <?php
            $itemId = $it['id'] ?? null;
            $priceRaw = $it['price'] ?? 0;
            $price = is_numeric($priceRaw) ? (float) $priceRaw : 0.0;
            $date = substr($it['acquisitionDate'] ?? $it['acquisition_date'] ?? '', 0, 10);
            $likes = $itemLikeCount[$itemId] ?? 0;
            $isLiked = isset($likedItems[$itemId]);

            $thumb = $it['image'] ?? '';
            if ($thumb && !preg_match('#^https?://#', $thumb)) {
                $thumb = '../../' . ltrim($thumb, './');
            }
            ?>
                                <article class="card item-card">
                                    <div class="item-image-wrapper">
                                <?php if ($thumb): ?>
                                            <a href="item_page.php?id=<?php echo urlencode($itemId); ?>">
                                                <img class="item-image"
                                                     src="<?php echo htmlspecialchars($thumb); ?>"
                                                     alt="<?php echo htmlspecialchars($it['name'] ?? ''); ?>">
                                            </a>
                                <?php else: ?>
                                            <div class="item-image-placeholder">No image available</div>
                                <?php endif; ?>
                                    </div>

                                    <div class="item-info">
                                        <h3>
                                            <a href="item_page.php?id=<?php echo urlencode($itemId); ?>">
                                        <?php echo htmlspecialchars($it['name'] ?? ''); ?>
                                            </a>
                                        </h3>

                                        <div class="item-price">
                                            €<?php echo number_format($price, 2, '.', ''); ?>
                                        </div>
                                    </div>

                                    <div class="card-buttons item-buttons">
                                        <a class="explore-btn"
                                           href="item_page.php?id=<?php echo urlencode($itemId); ?>">
                                            Explore More
                                        </a>

                                            <?php if ($isAuthenticated): ?>
                                            <form action="likes_action.php" method="POST" class="like-form" style="display:inline;">
                                                <input type="hidden" name="type" value="item">
                                                <input type="hidden" name="id"
                                                       value="<?php echo htmlspecialchars($itemId); ?>">
                                                <button type="submit"
                                                        class="explore-btn ghost<?php echo $isLiked ? ' success' : ''; ?>">
                                                    <i class="bi <?php echo $isLiked ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
				<?php echo $likes; ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>            <?php if ($isOwner): ?>
                                            <a class="explore-btn ghost"
                                               href="items_form.php?id=<?php echo urlencode($itemId); ?>">
                                                Edit
                                            </a>

                                            <form action="items_action.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id"
                                                       value="<?php echo htmlspecialchars($itemId); ?>">
                                                <button type="submit"
                                                        class="explore-btn danger"
                                                        onclick="return confirm('Delete this item?');">
                                                    Delete
                                                </button>
                                            </form>
            <?php endif; ?>
                                    </div>
                                </article>
        <?php endforeach; ?>
                        </div>
    <?php endif; ?>
                </section>

                <!-- =========================
                     EVENTS
                     ========================= -->
                <section class="collection-events" style="margin-top:32px;">
                    <h2 class="section-subtitle">Events</h2>

                        <?php if (!$eventsForCollection): ?>
                        <p class="muted">No events linked to this collection.</p>
                    <?php else: ?>
                        <div class="collection-events-list">
        <?php foreach ($eventsForCollection as $ev): ?>
            <?php
            $priceRaw = $ev['price'] ?? $ev['ticket_price'] ?? $ev['cost'] ?? null;
            $price = is_numeric($priceRaw) ? (float) $priceRaw : null;
            $category = $ev['category'] ?? $ev['type'] ?? 'Event';
            $eventDate = substr($ev['date'] ?? '', 0, 16);
            $location = $ev['localization'] ?? $ev['location'] ?? '';
            ?>
                                <article class="collection-event-card">
                                    <div class="event-card-header">
                                        <span class="event-type-badge"><?php echo htmlspecialchars($category); ?></span>
                                    </div>
                                    <h3><?php echo htmlspecialchars($ev['name'] ?? ''); ?></h3>
                                    <p class="event-summary"><?php echo htmlspecialchars($ev['summary'] ?? ''); ?></p>
                                    <div class="event-info-grid">
                                        <div class="event-info-item">
                                            <i class="bi bi-calendar-event"></i>
                                            <span><?php echo htmlspecialchars($eventDate); ?></span>
                                        </div>
                                        <?php if ($location): ?>
                                        <div class="event-info-item">
                                            <i class="bi bi-geo-alt"></i>
                                            <span><?php echo htmlspecialchars($location); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <div class="event-info-item">
                                            <i class="bi bi-cash-coin"></i>
                                            <span><?php echo $price !== null ? '€' . number_format($price, 2, '.', '') : 'Free'; ?></span>
                                        </div>
                                    </div>
                                </article>
        <?php endforeach; ?>
                        </div>
                                    <?php endif; ?>
                </section>

<?php endif; ?>
        </main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
        <script src="../../JS/specific_collection.js"></script>
    
    </body>

</html>

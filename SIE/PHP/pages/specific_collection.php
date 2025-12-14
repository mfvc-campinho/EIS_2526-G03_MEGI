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
$eventsUsers = $data['eventsUsers'] ?? [];
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
//   Eventos da coleção (todos os associados)
// -----------------------------
$eventsForCollection = [];

foreach ($collectionEvents as $link) {
    $cid = $link['collectionId'] ?? $link['collection_id'] ?? null;
    $eid = $link['eventId'] ?? $link['event_id'] ?? null;

    if ($cid === $collectionId && $eid && isset($eventsById[$eid])) {
        $eventsForCollection[] = $eventsById[$eid];
    }
}

// Ordenar eventos por data (mais próximos primeiro)
if (!empty($eventsForCollection)) {
    usort($eventsForCollection, function($a, $b) {
        $da = strtotime($a['date'] ?? $a['event_date'] ?? '') ?: 0;
        $db = strtotime($b['date'] ?? $b['event_date'] ?? '') ?: 0;
        return $da <=> $db;
    });
}

// =========================
// Eventos do utilizador (via RSVP)
// =========================
$userRsvpMap = [];
if ($currentUserId) {
    foreach ($eventsUsers as $eu) {
        $uid = $eu['userId'] ?? $eu['user_id'] ?? null;
        $eid = $eu['eventId'] ?? $eu['event_id'] ?? null;
        $rsvp = !empty($eu['rsvp']);
        if ($uid == $currentUserId && $eid && $rsvp) {
            $userRsvpMap[$eid] = true;
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
        <link rel="stylesheet" href="../../CSS/navbar.css">
        <link rel="stylesheet" href="../../CSS/specific_collection.css">
        <link rel="stylesheet" href="../../CSS/events.css">
        <link rel="stylesheet" href="../../CSS/likes.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="../../CSS/christmas.css">
        <link rel="stylesheet" href="../../CSS/specific_collection_events.css">
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
                            <form id="filters" class="filters-form" method="GET" action="specific_collection.php">
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
                                    <span class="filter-chip__hint">items per page</span>
                                </div>

                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($collectionId); ?>">
                                <input type="hidden" name="page" value="1">
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
                                            <div class="card-image-placeholder">No image available</div>
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

                                    <div class="collection-card__actions card-actions">
                                        <a class="action-icon" href="item_page.php?id=<?php echo urlencode($itemId); ?>" title="Explore">
                                            <i class="bi bi-plus-lg"></i>
                                        </a>

                                        <?php if ($isAuthenticated): ?>
                                            <form action="likes_action.php" method="POST" class="action-icon-form like-form">
                                                <input type="hidden" name="type" value="item">
                                                <input type="hidden" name="id"
                                                       value="<?php echo htmlspecialchars($itemId); ?>">
                                                <button type="submit"
                                                        class="action-icon<?php echo $isLiked ? ' is-liked' : ''; ?>"
                                                        title="Like">
                                                    <i class="bi <?php echo $isLiked ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
                                                    <span class="like-count<?php echo $likes === 0 ? ' is-zero' : ''; ?>"><?php echo $likes; ?></span>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($isOwner): ?>
                                            <a class="action-icon"
                                               href="items_form.php?id=<?php echo urlencode($itemId); ?>"
                                               title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>

                                            <form action="items_action.php" method="POST" class="action-icon-form">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id"
                                                       value="<?php echo htmlspecialchars($itemId); ?>">
                                                <button type="submit"
                                                        class="action-icon is-danger"
                                                        title="Delete"
                                                        onclick="return confirm('Delete this item?');">
                                                    <i class="bi bi-trash"></i>
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
                        <div class="events-grid js-events-grid">
                            <?php
                            // Helper to format date as ordinal and month name lowercase
                            if (!function_exists('gc_format_ordinal_date')) {
                                function gc_format_ordinal_date(?DateTime $dt): string {
                                    if (!$dt) return '';
                                    $day = (int)$dt->format('j');
                                    $year = $dt->format('Y');
                                    $monthNum = (int)$dt->format('n');
                                    // Ordinal suffix
                                    $suffix = 'th';
                                    if (!in_array($day % 100, [11,12,13], true)) {
                                        $last = $day % 10;
                                        if ($last === 1) $suffix = 'st';
                                        elseif ($last === 2) $suffix = 'nd';
                                        elseif ($last === 3) $suffix = 'rd';
                                    }
                                    // Month names lowercase (with "dezember" spelling for December as requested)
                                    $months = [
                                        1=>'january',2=>'february',3=>'march',4=>'april',5=>'may',6=>'june',
                                        7=>'july',8=>'august',9=>'september',10=>'october',11=>'november',12=>'dezember'
                                    ];
                                    $monthName = $months[$monthNum] ?? strtolower($dt->format('F'));
                                    return $day . $suffix . ' of ' . $monthName . ' of ' . $year;
                                }
                            }
                            ?>
                            <?php foreach ($eventsForCollection as $ev): ?>
                                <?php
                                $eventRaw = $ev['date'] ?? $ev['event_date'] ?? '';
                                $eventDateObj = $eventRaw ? new DateTime($eventRaw) : null;
                                $eventDateText = gc_format_ordinal_date($eventDateObj);
                                // derive card-only date and time from the original raw value (keeps time when present)
                                $cardDateOnly = '';
                                $cardTimeOnly = '';
                                if ($eventRaw !== '') {
                                    $normalizedRaw = str_replace('T', ' ', $eventRaw);
                                    $rawHasTime = preg_match('/\d{2}:\d{2}/', $normalizedRaw) === 1;
                                    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $normalizedRaw)
                                        ?: DateTime::createFromFormat('Y-m-d H:i', $normalizedRaw)
                                        ?: DateTime::createFromFormat('Y-m-d', $normalizedRaw);
                                    if ($dt instanceof DateTime) {
                                        $cardDateOnly = $dt->format('d/m/Y');
                                        $cardTimeOnly = $rawHasTime ? $dt->format('H:i') : '';
                                    } else {
                                        $cardDateOnly = substr($normalizedRaw, 0, 10);
                                        $cardTimeOnly = $rawHasTime ? substr($normalizedRaw, 11, 5) : '';
                                    }
                                }

                                $priceRaw = $ev['price'] ?? $ev['ticket_price'] ?? $ev['cost'] ?? null;
                                $price = is_numeric($priceRaw) ? (float) $priceRaw : null;
                                $costLabel = ($price !== null && $price > 0) ? '€' . number_format($price, 2, ',', '.') : 'Free entrance';
                                $location = $ev['localization'] ?? $ev['location'] ?? '';
                                $eventId = $ev['id'] ?? $ev['event_id'] ?? null;
                                $category = $ev['category'] ?? $ev['type'] ?? 'Event';
                                $statusIsUpcoming = ($eventDateObj instanceof DateTime) ? ($eventDateObj >= new DateTime()) : false;
                                ?>
                                <article class="event-card js-event-card" tabindex="0"
                                         data-event-id="<?php echo htmlspecialchars($eventId); ?>"
                                         data-name="<?php echo htmlspecialchars($ev['name'] ?? ''); ?>"
                                         data-summary="<?php echo htmlspecialchars($ev['summary'] ?? ''); ?>"
                                         data-description="<?php echo htmlspecialchars($ev['description'] ?? ''); ?>"
                                         data-date="<?php echo htmlspecialchars($cardDateOnly ?: $eventDateText); ?>"
                                         data-time="<?php echo htmlspecialchars($cardTimeOnly); ?>"
                                         data-datetime="<?php echo htmlspecialchars(($cardDateOnly ?: $eventDateText) . ($cardTimeOnly ? ' · ' . $cardTimeOnly : '')); ?>"
                                         data-location="<?php echo htmlspecialchars($location); ?>"
                                         data-type="<?php echo htmlspecialchars($category); ?>"
                                         data-cost="<?php echo htmlspecialchars($costLabel); ?>">
                                    <div class="user-event-card__top">
                                        <span class="pill pill--event"><?php echo htmlspecialchars($category); ?></span>
                                        <span class="user-event-badge <?php echo $statusIsUpcoming ? 'upcoming' : 'past'; ?>"><?php echo $statusIsUpcoming ? 'UPCOMING' : 'PAST'; ?></span>
                                    </div>
                                    <h3><?php echo htmlspecialchars($ev['name'] ?? 'Event'); ?></h3>
                                    <ul class="user-event-meta">
                                        <?php if (!empty($cardDateOnly) || !empty($eventDateText)): ?>
                                            <li>
                                                <i class="bi bi-calendar-event"></i>
                                                <?php echo htmlspecialchars($cardDateOnly ?: $eventDateText); ?>
                                            </li>
                                        <?php endif; ?>
                                        <?php if (!empty($location)): ?>
                                            <li>
                                                <i class="bi bi-geo-alt"></i>
                                                <?php echo htmlspecialchars($location); ?>
                                            </li>
                                        <?php endif; ?>
                                        <li>
                                            <i class="bi bi-ticket-perforated"></i>
                                            <?php echo htmlspecialchars($costLabel); ?>
                                        </li>
                                    </ul>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

<?php endif; ?>
        </main>

    <div class="modal-backdrop" id="event-modal" style="display:none;">
        <div class="modal-card">
            <div class="modal-header">
                <button type="button" class="modal-close" aria-label="Close event details" onclick="document.getElementById('event-modal')?.classList.remove('open')">
                    <i class="bi bi-x"></i>
                </button>
                <h3 id="modal-title"></h3>
                <span class="modal-type-badge" id="modal-type"></span>
            </div>
            <div class="modal-body">
                <p class="modal-summary" id="modal-summary"></p>
                <p class="modal-description" id="modal-description"></p>
                <div class="modal-info-grid">
                    <div class="modal-info-item">
                        <div class="modal-info-icon">
                            <i class="bi bi-calendar-event"></i>
                        </div>
                        <div class="modal-info-content">
                            <div class="modal-info-label">Date</div>
                            <div class="modal-info-value" id="modal-date"></div>
                        </div>
                    </div>
                    <div class="modal-info-item" id="modal-time-row" hidden>
                        <div class="modal-info-icon">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div class="modal-info-content">
                            <div class="modal-info-label">Time</div>
                            <div class="modal-info-value" id="modal-time"></div>
                        </div>
                    </div>
                    <div class="modal-info-item">
                        <div class="modal-info-icon">
                            <i class="bi bi-geo-alt-fill"></i>
                        </div>
                        <div class="modal-info-content">
                            <div class="modal-info-label">Place</div>
                            <div class="modal-info-value">
                                <a id="modal-location" class="modal-location-link" href="#" rel="noopener noreferrer"></a>
                            </div>
                        </div>
                    </div>
                    <div class="modal-info-item">
                        <div class="modal-info-icon">
                            <i class="bi bi-cash-coin"></i>
                        </div>
                        <div class="modal-info-content">
                            <div class="modal-info-label">Cost</div>
                            <div class="modal-info-value" id="modal-cost"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-rsvp-form" id="modal-rsvp-container" style="display:none;">
                    <?php if ($isAuthenticated): ?>
                        <form action="events_action.php" method="POST">
                            <input type="hidden" name="action" value="rsvp">
                            <input type="hidden" name="id" value="" id="modal-rsvp-event-id">
                            <input type="hidden" name="return_url" value="specific_collection.php?id=<?php echo htmlspecialchars($collectionId); ?>">
                            <button type="submit" class="modal-rsvp-btn" id="modal-rsvp-btn">
                                <i class="bi bi-check2-circle"></i>
                                <span>RSVP</span>
                            </button>
                        </form>
                    <?php else: ?>
                        <a class="modal-rsvp-btn" href="auth.php">
                            <i class="bi bi-lock"></i>
                            <span>Sign in to RSVP</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
        <script src="../../JS/specific_collection.js"></script>
        <script src="../../JS/collection_card.js"></script>
        <script>
        <?php
        // Passar o mapa de RSVPs para o JavaScript
        echo "var userRsvpMap = " . json_encode($userRsvpMap) . ";\n";
        ?>
        (function() {
            var modal = document.getElementById('event-modal');
            if (!modal) return;

            var closeButton = modal.querySelector('.modal-close');
            var titleEl = document.getElementById('modal-title');
            var typeEl = document.getElementById('modal-type');
            var summaryEl = document.getElementById('modal-summary');
            var descriptionEl = document.getElementById('modal-description');
            var dateEl = document.getElementById('modal-date');
            var timeRow = document.getElementById('modal-time-row');
            var timeEl = document.getElementById('modal-time');
            var locationLink = document.getElementById('modal-location');
            var costEl = document.getElementById('modal-cost');
            var rsvpContainer = document.getElementById('modal-rsvp-container');
            var rsvpForm = rsvpContainer ? rsvpContainer.querySelector('form') : null;
            var rsvpEventIdInput = document.getElementById('modal-rsvp-event-id');
            var rsvpButton = document.getElementById('modal-rsvp-btn');

            function setText(target, value) {
                if (target) target.textContent = value || '';
            }

            function openModal(payload) {
                setText(titleEl, payload.name);
                setText(typeEl, payload.type);
                setText(summaryEl, payload.summary);
                setText(descriptionEl, payload.description);
                setText(dateEl, payload.date || payload.datetime || '');

                if (timeRow && timeEl) {
                    if (payload.time) {
                        timeRow.hidden = false;
                        setText(timeEl, payload.time);
                    } else {
                        timeRow.hidden = true;
                        setText(timeEl, '');
                    }
                }

                if (locationLink) {
                    var cleanLocation = (payload.location || '').trim();
                    if (cleanLocation) {
                        locationLink.textContent = cleanLocation;
                        locationLink.href = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(cleanLocation);
                        locationLink.classList.remove('disabled');
                        locationLink.setAttribute('target', '_blank');
                    } else {
                        locationLink.textContent = 'Location unavailable';
                        locationLink.removeAttribute('href');
                        locationLink.classList.add('disabled');
                    }
                }

                setText(costEl, payload.cost || 'Free entrance');

                if (rsvpContainer) {
                    if (payload.eventId && rsvpForm && rsvpEventIdInput && rsvpButton) {
                        rsvpContainer.style.display = 'block';
                        rsvpEventIdInput.value = payload.eventId;
                        const hasRsvp = userRsvpMap[payload.eventId] === true;
                        const rsvpText = rsvpButton.querySelector('span');
                        if (rsvpText) {
                            rsvpText.textContent = hasRsvp ? 'RSVP Confirmed' : 'RSVP';
                        }
                        rsvpButton.classList.toggle('is-active', hasRsvp);
                    } else {
                        rsvpContainer.style.display = 'none';
                    }
                }

                modal.style.display = 'flex'; // Use flex to align center
                setTimeout(() => modal.classList.add('open'), 10);
            }

            function closeModal() {
                modal.classList.remove('open');
                // wait for animation to finish before hiding
                setTimeout(() => { modal.style.display = 'none'; }, 200);
            }

            // Open modal populated from card data when an event card is activated
            (function() {
                var eventsGrid = document.querySelector('.js-events-grid');
                if (!eventsGrid) return;

                function buildPayloadFromCard(card) {
                    if (!card) return {};
                    return {
                        eventId: card.getAttribute('data-event-id') || card.dataset.eventId,
                        name: card.getAttribute('data-name') || card.dataset.name,
                        summary: card.getAttribute('data-summary') || card.dataset.summary,
                        description: card.getAttribute('data-description') || card.dataset.description,
                        date: card.getAttribute('data-date') || card.dataset.date,
                        time: card.getAttribute('data-time') || card.dataset.time,
                        datetime: card.getAttribute('data-datetime') || card.dataset.datetime,
                        location: card.getAttribute('data-location') || card.dataset.location,
                        type: card.getAttribute('data-type') || card.dataset.type,
                        cost: card.getAttribute('data-cost') || card.dataset.cost,
                    };
                }

                eventsGrid.addEventListener('click', function(e) {
                    var card = e.target.closest('.js-event-card');
                    if (!card) return;
                    e.preventDefault();
                    e.stopPropagation();
                    var payload = buildPayloadFromCard(card);
                    openModal(payload);
                });

                eventsGrid.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        var card = e.target.closest('.js-event-card');
                        if (!card) return;
                        e.preventDefault();
                        var payload = buildPayloadFromCard(card);
                        openModal(payload);
                    }
                });
            })();

            if (closeButton) closeButton.addEventListener('click', closeModal);
            modal.addEventListener('click', function(e) { if (e.target === modal) closeModal(); });
            document.addEventListener('keydown', function(e) { if (e.key === 'Escape' && modal.classList.contains('open')) closeModal(); });

        })();
        </script>
        <script src="../../JS/gc-scroll-restore.js"></script>
    
    </body>

</html>

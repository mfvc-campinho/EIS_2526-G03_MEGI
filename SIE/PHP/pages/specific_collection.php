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
//   Eventos da coleção
// -----------------------------
$eventsForCollection = [];

foreach ($collectionEvents as $link) {
    $cid = $link['collectionId'] ?? $link['collection_id'] ?? null;
    $eid = $link['eventId'] ?? $link['event_id'] ?? null;

    if ($cid === $collectionId && $eid && isset($eventsById[$eid])) {
        $eventsForCollection[] = $eventsById[$eid];
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

// Hero / avatar (fallbacks)
$ownerAvatar = $collection['owner_avatar'] ?? $collection['user_image'] ?? '../../images/default-avatar.png';

$ownerName = $collection['owner_name'] ?? $collection['username'] ?? $collection['user_name'] ?? 'Collection owner';
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
        <script src="././JS/theme-toggle.js"></script>
    </head>

    <body>
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
                    <div id="collection-meta" class="collection-meta--compact">
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
                            <span class="owner-link">
                        <?php echo htmlspecialchars($ownerName); ?>
                            </span>
                        </p>
                    </div>
                </section>


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
                            Editar coleção
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
                                    onclick="return confirm('Apagar coleção?');">
                                <i class="bi bi-trash"></i>
                                Delete
                            </button>
                        </form>
    <?php endif; ?>
                </div>

                <!-- =========================
                     ITEMS
                     ========================= -->
                <section class="items-section">
                    <h2>Items</h2>

    <?php if (!$itemsForCollection): ?>
                        <p class="muted">No items in this collection.</p>
                    <?php else: ?>
                        <div class="collection-container">
        <?php foreach ($itemsForCollection as $it): ?>
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
                                <div class="item-card">
                                    <div class="item-info">
                                <?php if ($thumb): ?>
                                            <a href="item_page.php?id=<?php echo urlencode($itemId); ?>">
                                                <img src="<?php echo htmlspecialchars($thumb); ?>"
                                                     alt="<?php echo htmlspecialchars($it['name'] ?? ''); ?>"
                                                     style="width:100%; height:180px; object-fit:cover; border-radius:12px; margin-bottom:10px;">
                                            </a>
                                <?php endif; ?>

                                        <h3>
                                            <a href="item_page.php?id=<?php echo urlencode($itemId); ?>">
                                        <?php echo htmlspecialchars($it['name'] ?? ''); ?>
                                            </a>
                                        </h3>

                                        <p class="muted">
                                            Price:
                                        <?php echo number_format($price, 2, '.', ''); ?>
                                        </p>

                                        <p class="muted">
                                            <i class="bi bi-calendar3"></i>
                                                <?php echo htmlspecialchars($date ?: ''); ?>
                                            &nbsp;
                                            <i class="bi bi-box-seam"></i>
            <?php echo htmlspecialchars($collection['name'] ?? ''); ?>
                                        </p>
                                    </div>

                                    <div class="item-buttons">
                                        <a class="explore-btn"
                                           href="item_page.php?id=<?php echo urlencode($itemId); ?>">
                                            Explore More
                                        </a>

                                            <?php if ($isAuthenticated): ?>
                                            <form action="likes_action.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="type" value="item">
                                                <input type="hidden" name="id"
                                                       value="<?php echo htmlspecialchars($itemId); ?>">
                                                <button type="submit"
                                                        class="explore-btn ghost<?php echo $isLiked ? ' success' : ''; ?>">
                                                    <i class="bi <?php echo $isLiked ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
                <?php echo $likes; ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>

            <?php if ($isOwner): ?>
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
                                </div>
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
            $evDate = substr($ev['date'] ?? $ev['start_date'] ?? '', 0, 16);
            ?>
                                <article class="collection-event-card">
                                    <div>
                                        <h3><?php echo htmlspecialchars($ev['name'] ?? ''); ?></h3>
                                        <p><?php echo htmlspecialchars($ev['summary'] ?? ''); ?></p>
                                    </div>
                                    <div class="event-meta">
                                        <div>
                                            <i class="bi bi-calendar-event"></i>
                                <?php echo htmlspecialchars($evDate); ?>
                                        </div>
                                        <div>
                                            <i class="bi bi-geo-alt"></i>
            <?php echo htmlspecialchars($ev['localization'] ?? $ev['location'] ?? ''); ?>
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
    </body>

</html>

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

$isAuth = !empty($_SESSION['user']);
$currentUserId = $_SESSION['user']['id'] ?? null;
$likedCollections = [];
$collectionLikeCount = [];
foreach ($userShowcases as $sc) {
    $uid = $sc['ownerId'] ?? null;
    $likes = $sc['likes'] ?? [];
    foreach ($likes as $cid) {
        $collectionLikeCount[$cid] = ($collectionLikeCount[$cid] ?? 0) + 1;
        if ($uid === $currentUserId) {
            $likedCollections[$cid] = true;
        }
    }
}

// Controls
$sort = $_GET['sort'] ?? 'newest';
$perPage = max(1, (int) ($_GET['perPage'] ?? 10));
$page = max(1, (int) ($_GET['page'] ?? 1));

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
$pages = max(1, (int) ceil($total / $perPage));
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

        <!-- estilos globais -->
        <link rel="stylesheet" href="../../CSS/general.css">
        <link rel="stylesheet" href="././CSS/likes.css">

        <!-- estilos específicos desta página -->
        <link rel="stylesheet" href="all_collections.css">

        <!-- ícones + tema -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <script src="././JS/theme-toggle.js"></script>

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
                        <form id="filters" method="GET" style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                            <label for="sort-select"><i class="bi bi-funnel"></i> Sort by</label>
                            <select name="sort" id="sort-select" onchange="this.form.submit()">
                                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Last Added</option>
                                <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                                <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                            </select>
                            <label>Show
                                <select name="perPage" onchange="this.form.submit()">
                                    <?php foreach ([5, 10, 20] as $opt): ?>
                                        <option value="<?php echo $opt; ?>" <?php echo $perPage == $opt ? 'selected' : ''; ?>><?php echo $opt; ?></option>
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
                        <button <?php echo $page <= 1 ? 'disabled' : ''; ?> onclick="window.location = '?<?php echo http_build_query(['sort' => $sort, 'perPage' => $perPage, 'page' => max(1, $page - 1)]); ?>'"><i class="bi bi-chevron-left"></i></button>
                        <span>Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $perPage, $total); ?> of <?php echo $total; ?></span>
                        <button <?php echo $page >= $pages ? 'disabled' : ''; ?> onclick="window.location = '?<?php echo http_build_query(['sort' => $sort, 'perPage' => $perPage, 'page' => min($pages, $page + 1)]); ?>'"><i class="bi bi-chevron-right"></i></button>
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
                                        <div class="product-card__owner">
                                            <i class="bi bi-people"></i>
                                            <?php echo htmlspecialchars($col['ownerId']); ?>
                                        </div>
                                        <div class="product-card__date">
                                            <i class="bi bi-calendar3"></i>
                                            <?php echo htmlspecialchars(substr($col['createdAt'], 0, 7)); ?>
                                        </div>
                                    </div>
                                    <div class="card-actions">
                                        <!-- Show Preview -->
                                        <label class="explore-btn preview-show" for="<?php echo $previewId; ?>">
                                            Show Preview
                                        </label>

                                        <!-- Hide Preview (mesmo estilo que Show/Explore) -->
                                        <label class="explore-btn preview-hide" for="<?php echo $previewId; ?>">
                                            Hide Preview
                                        </label>

                                        <!-- Explore More -->
                                        <a class="explore-btn" href="specific_collection.php?id=<?php echo urlencode($col['id']); ?>">
                                            Explore More
                                        </a>

                                        <!-- Like -->
                                        <?php if ($isAuth): ?>
                                            <form action="likes_action.php" method="POST" class="card-like-form">
                                                <input type="hidden" name="type" value="collection">
                                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($col['id']); ?>">
                                                <button type="submit" class="explore-btn ghost<?php echo isset($likedCollections[$col['id']]) ? ' success' : ''; ?>">
                                                    <i class="bi <?php echo isset($likedCollections[$col['id']]) ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
                                                    <?php $count = (int) ($collectionLikeCount[$col['id']] ?? 0); ?>
                                                    <span class="like-count<?php echo $count === 0 ? ' is-zero' : ''; ?>"><?php echo $count; ?></span>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="explore-btn ghost" type="button" data-action="login-popup" data-login-url="auth.php">
                                                <i class="bi bi-heart"></i>
                                                <?php $count = (int) ($collectionLikeCount[$col['id']] ?? 0); ?>
                                                <span class="like-count<?php echo $count === 0 ? ' is-zero' : ''; ?>"><?php echo $count; ?></span>
                                            </button>
                                        <?php endif; ?>
                                    </div>

                                    <div class="owner-actions<?php echo $isOwner ? '' : ' owner-actions--placeholder'; ?>"<?php echo $isOwner ? '' : ' aria-hidden="true"'; ?>>
                                        <?php if ($isOwner): ?>
                                            <a class="explore-btn ghost" href="collections_form.php?id=<?php echo urlencode($col['id']); ?>"><i class="bi bi-pencil"></i> Edit</a>
                                            <form action="collections_action.php" method="POST">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($col['id']); ?>">
                                                <button type="submit" class="explore-btn ghost danger" onclick="return confirm('Delete this collection?');"><i class="bi bi-trash"></i> Delete</button>
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
        <?php if (!$isAuth): ?>
            <script>
                (function () {
                    var triggers = document.querySelectorAll('[data-action="login-popup"]');
                    if (!triggers.length) return;

                    function escapeHtml(str) {
                        return String(str)
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;');
                    }

                    function createFlash(detail) {
                        detail = detail || {};
                        var type = detail.type || 'info';
                        var title = detail.title || (type === 'success' ? 'Success' : type === 'error' ? 'Oops!' : 'Heads up');
                        var message = detail.message || '';
                        var htmlMessage = detail.htmlMessage || null;

                        document.querySelectorAll('.flash-modal[data-dynamic="true"]').forEach(function (node) {
                            if (node && node.parentNode) {
                                node.parentNode.removeChild(node);
                            }
                        });

                        var modal = document.createElement('div');
                        modal.className = 'flash-modal flash-modal--' + type;
                        modal.setAttribute('role', 'alertdialog');
                        modal.setAttribute('aria-live', 'assertive');
                        modal.setAttribute('aria-modal', 'true');
                        modal.setAttribute('aria-label', title);
                        modal.dataset.flashType = type;
                        modal.setAttribute('data-dynamic', 'true');
                        modal.innerHTML = '' +
                            '<div class="flash-modal__backdrop"></div>' +
                            '<div class="flash-modal__card" tabindex="-1">' +
                            '  <button class="flash-modal__close" type="button" aria-label="Close notification">&times;</button>' +
                            '  <div class="flash-modal__icon" aria-hidden="true">' + (type === 'success' ? '&#10003;' : '&#9888;') + '</div>' +
                            '  <div class="flash-modal__content">' +
                            '    <h3>' + escapeHtml(title) + '</h3>' +
                            '    <p></p>' +
                            '  </div>' +
                            '</div>';

                        document.body.appendChild(modal);

                        var messageNode = modal.querySelector('.flash-modal__content p');
                        if (htmlMessage) {
                            messageNode.innerHTML = htmlMessage;
                        } else {
                            messageNode.textContent = message;
                        }

                        var card = modal.querySelector('.flash-modal__card');
                        var closeBtn = modal.querySelector('.flash-modal__close');
                        var delay = type === 'error' ? 8000 : 5000;
                        var timer;

                        function remove() {
                            if (timer) {
                                clearTimeout(timer);
                            }
                            modal.classList.add('is-closing');
                            setTimeout(function () {
                                if (modal && modal.parentNode) {
                                    modal.parentNode.removeChild(modal);
                                }
                                document.removeEventListener('keydown', onKey);
                            }, 220);
                        }

                        function onKey(ev) {
                            if (ev.key === 'Escape') {
                                remove();
                            }
                        }

                        modal.addEventListener('click', function (ev) {
                            if (ev.target === modal || ev.target.classList.contains('flash-modal__backdrop')) {
                                remove();
                            }
                        });

                        if (closeBtn) {
                            closeBtn.addEventListener('click', remove);
                        }

                        document.addEventListener('keydown', onKey);
                        timer = setTimeout(remove, delay);

                        if (card && typeof card.focus === 'function') {
                            requestAnimationFrame(function () {
                                card.focus();
                            });
                        }
                    }

                    window.appShowFlash = createFlash;

                    triggers.forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            createFlash({
                                type: 'error',
                                title: 'Oops!',
                                message: 'Log in to like this collection.',
                            });
                        });
                    });
                })();
            </script>
        <?php endif; ?>
    </body>

</html>

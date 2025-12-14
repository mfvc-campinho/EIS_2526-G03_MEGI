<?php
session_start();
require_once __DIR__ . '/../includes/data_loader.php';
require_once __DIR__ . '/../includes/flash.php';

$data = load_app_data($mysqli);
$mysqli->close();
$users = $data['users'] ?? [];
$collections = $data['collections'] ?? [];
$items = $data['items'] ?? [];
$events = $data['events'] ?? [];
$collectionItems = $data['collectionItems'] ?? [];
$collectionEvents = $data['collectionEvents'] ?? [];
$eventsUsers = $data['eventsUsers'] ?? [];

$isAuthenticated = !empty($_SESSION['user']);
$currentUserId = $isAuthenticated ? ($_SESSION['user']['id'] ?? null) : null;
$profileUserId = $_GET['id'] ?? $currentUserId;
$profileUser = null;
foreach ($users as $u) {
    if ($profileUserId && (($u['id'] ?? $u['user_id']) == $profileUserId)) {
        $profileUser = $u;
        break;
    }
}

$currentUser = null;
foreach ($users as $u) {
    if ($currentUserId && (($u['id'] ?? $u['user_id']) == $currentUserId)) {
        $currentUser = $u;
        break;
    }
}

$ownedCollections = array_filter($collections, function ($c) use ($profileUserId) {
    return ($c['ownerId'] ?? null) === $profileUserId;
});

// Map items per owned collection
$itemsByCollection = [];
foreach ($collectionItems as $link) {
    $cid = $link['collectionId'] ?? null;
    if (!$cid)
        continue;
    $itemsByCollection[$cid][] = $link['itemId'];
}

// Calcula as métricas de cada coleção para suportar o top 3 filtrável.
$itemsById = [];
foreach ($items as $item) {
    $iid = $item['id'] ?? $item['item_id'] ?? null;
    if ($iid) {
        $itemsById[$iid] = $item;
    }
}

$topSortOptions = ['likes', 'value', 'oldest', 'items'];
$topSort = $_GET['topSort'] ?? 'likes';
if (!in_array($topSort, $topSortOptions, true)) {
    $topSort = 'likes';
}

$collectionLikeCounts = [];
if ($ownedCollections) {
    require __DIR__ . '/../config/db.php';
    if ($mysqli instanceof mysqli && !$mysqli->connect_error) {
        $likesResult = $mysqli->query('SELECT liked_collection_id, COUNT(*) AS cnt FROM user_liked_collections GROUP BY liked_collection_id');
        if ($likesResult instanceof mysqli_result) {
            while ($row = $likesResult->fetch_assoc()) {
                $cid = $row['liked_collection_id'] ?? null;
                if ($cid) {
                    $collectionLikeCounts[$cid] = (int)($row['cnt'] ?? 0);
                }
            }
            $likesResult->close();
        }
        $mysqli->close();
    }
}

$topCollections = [];
foreach ($ownedCollections as $col) {
    $cid = $col['id'] ?? null;
    if (!$cid) continue;
    $itemIds = $itemsByCollection[$cid] ?? [];
    $itemsTotal = 0;
    $valueTotal = 0.0;
    foreach ($itemIds as $iid) {
        if (!isset($itemsById[$iid])) continue;
        $itemsTotal++;
        $valueTotal += (float)($itemsById[$iid]['price'] ?? 0);
    }
    $topCollections[] = [
        'data' => $col,
        'likes' => $collectionLikeCounts[$cid] ?? 0,
        'items' => $itemsTotal,
        'value' => $valueTotal,
        'createdAt' => $col['createdAt'] ?? '',
        'name' => $col['name'] ?? ''
    ];
}

usort($topCollections, function ($a, $b) use ($topSort) {
    if ($topSort === 'value') {
        return ($b['value'] <=> $a['value']) ?: ($b['likes'] <=> $a['likes']) ?: strcasecmp($a['name'], $b['name']);
    }
    if ($topSort === 'items') {
        return ($b['items'] <=> $a['items']) ?: ($b['likes'] <=> $a['likes']) ?: strcasecmp($a['name'], $b['name']);
    }
    if ($topSort === 'oldest') {
        $aDate = $a['createdAt'] ? strtotime($a['createdAt']) : PHP_INT_MAX;
        $bDate = $b['createdAt'] ? strtotime($b['createdAt']) : PHP_INT_MAX;
        return ($aDate <=> $bDate) ?: ($b['likes'] <=> $a['likes']) ?: strcasecmp($a['name'], $b['name']);
    }
    // likes (default)
    return ($b['likes'] <=> $a['likes']) ?: ($b['value'] <=> $a['value']) ?: strcasecmp($a['name'], $b['name']);
});

$topCollections = array_slice($topCollections, 0, 3);


// Map events per owned collection
$eventsByCollection = [];
foreach ($collectionEvents as $link) {
    $cid = $link['collectionId'] ?? null;
    if (!$cid)
        continue;
    $eventsByCollection[$cid][] = $link['eventId'];
}

// =========================
// Eventos do utilizador (via RSVP, não apenas via coleções)
// =========================
// índice rápido de eventos por id
$eventsById = [];
foreach ($events as $e) {
    if (!empty($e['id'])) {
        $eventsById[$e['id']] = $e;
    }
}

// Build RSVP map for the profile user: get all events where user RSVP'd
$userRsvpMap = [];
foreach ($eventsUsers as $eu) {
    $uid = $eu['userId'] ?? $eu['user_id'] ?? null;
    $eid = $eu['eventId'] ?? $eu['event_id'] ?? null;
    $rsvp = $eu['rsvp'] ?? null;
    if ($uid == $profileUserId && $eid && $rsvp) {
        $userRsvpMap[$eid] = $rsvp;
    }
}

// array final de eventos do utilizador (based on RSVP, not collection ownership)
$today = date('Y-m-d');

if (!function_exists('format_user_event_date')) {
    function format_user_event_date($raw)
    {
        if (!$raw) return '';
        $trimmed = trim((string)$raw);
        if ($trimmed === '') return '';

        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d\TH:i:s',
            'Y-m-d H:i',
            'Y-m-d\TH:i',
            'Y-m-d'
        ];

        foreach ($formats as $format) {
            $dt = DateTime::createFromFormat($format, $trimmed);
            if ($dt instanceof DateTime) {
                return $format === 'Y-m-d'
                    ? $dt->format('d/m/Y')
                    : $dt->format('d/m/Y H:i');
            }
        }

        $timestamp = strtotime($trimmed);
        if ($timestamp !== false) {
            return date('d/m/Y H:i', $timestamp);
        }

        return $trimmed;
    }
}
$upcomingEvents = [];
$pastEvents = [];

foreach (array_keys($userRsvpMap) as $eid) {
    if (isset($eventsById[$eid])) {
        $evt = $eventsById[$eid];
        $eventDate = substr($evt['date'] ?? '', 0, 10);
        
        if ($eventDate >= $today) {
            // Upcoming: include if RSVP exists
            $upcomingEvents[] = $evt;
        } else {
            // Past: include if RSVP exists
            $pastEvents[] = $evt;
        }
    }
}

// Sort both by date
usort($upcomingEvents, function ($a, $b) {
    return strcmp($a['date'] ?? '', $b['date'] ?? '');
});
usort($pastEvents, function ($a, $b) {
    return strcmp($b['date'] ?? '', $a['date'] ?? ''); // descending for past
});

// Followers count and following map
$userFollows = $data['userFollows'] ?? [];
$followingList = $userFollows[$currentUserId] ?? [];
$followersCount = 0;
$followersList = [];
foreach ($userFollows as $followerId => $list) {
    if (in_array($profileUserId, $list ?? [], true)) {
        $followersCount++;
        // Find follower user data
        foreach ($users as $u) {
            if ((string)($u['id'] ?? $u['user_id']) === (string)$followerId) {
                $followersList[] = $u;
                break;
            }
        }
    }
}
$isOwnerProfile = $currentUserId && $profileUserId && $currentUserId === $profileUserId;
$isFollowingProfile = $isAuthenticated && !$isOwnerProfile && in_array($profileUserId, $followingList, true);

?>
<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>User Profile • GoodCollections</title>

        <!-- Fonte -->
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

        <!-- Estilos globais -->
        <link rel="stylesheet" href="../../CSS/general.css">
        <!-- Navbar dedicated styles -->
        <link rel="stylesheet" href="../../CSS/navbar.css">

        <!-- Estilos específicos da página de perfil -->
        <link rel="stylesheet" href="../../CSS/user_page.css">

        <!-- Ícones -->
        <link rel="stylesheet"
              href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

        <!-- Christmas Theme CSS -->
        <link rel="stylesheet" href="../../CSS/christmas.css">

        <script src="../../JS/theme-toggle.js"></script>
        <script src="../../JS/christmas-theme.js"></script>
        <link rel="stylesheet" href="../../CSS/user_page.css">
    </head>


    <body>
        <?php include __DIR__ . '/../includes/nav.php'; ?>

        <?php if (!$isAuthenticated): ?>
            <main class="page-shell signin-shell">
                <?php flash_render(); ?>
                <nav class="breadcrumb-nav" aria-label="Breadcrumb">
                    <ol class="breadcrumb-list">
                        <li class="breadcrumb-item"><a href="home_page.php">Home</a></li>
                        <li class="breadcrumb-item" aria-current="page">User Profile</li>
                    </ol>
                </nav>

                <section class="signin-hero">
                    <h1>Sign In To Your Profile</h1>
                    <div class="signin-underline"></div>
                    <p>To see your profile and manage collections, please sign in or create an account.</p>

                    <div class="signin-actions">
                        <a class="signin-btn signin-btn--primary" href="login.php">Log In</a>
                        <a class="signin-btn signin-btn--accent" href="user_create.php">Create Account</a>
                        <a class="signin-btn" href="home_page.php">Browse As Guest</a>
                    </div>
                </section>
            </main>
            <?php include __DIR__ . '/../includes/footer.php'; ?>
        </body>

        </html>
        <?php exit; ?>
        <?php endif; ?>

        <main class="page-shell">
            <?php flash_render(); ?>

            <nav class="breadcrumb-nav" aria-label="Breadcrumb">
                <ol class="breadcrumb-list">
                    <li class="breadcrumb-item"><a href="home_page.php">Home</a></li>
                    <li class="breadcrumb-item" aria-current="page">User Profile</li>
                </ol>
            </nav>

            <section class="collections-hero">
                <h1>User Profile</h1>
                <div class="collections-hero-underline"></div>
            </section>

                <?php if (!$profileUser): ?>
                    <section class="profile-hero">
                        <?php if (!$isAuthenticated): ?>
                            <p class="muted">Please <a href="login.php">login</a> to view your profile.</p>
                        <?php else: ?>
                            <p class="muted">User not found.</p>
                        <?php endif; ?>
                    </section>
                <?php else: ?>
                    <!-- Photo + Name Section -->
                    <section class="profile-hero">
                        <div class="profile-card profile-card--hero">
                            <div class="profile-avatar">
                                <?php
                                $avatar = $profileUser['user_photo'] ?? '';
                                if ($avatar && !preg_match('#^https?://#', $avatar)) {
                                    // Stored in /PHP/uploads/..., so from /PHP/pages go one level up
                                    $avatar = '../' . ltrim($avatar, './');
                                }
                                ?>
                                <?php if (!empty($avatar)): ?>
                                    <img src="<?php echo htmlspecialchars($avatar); ?>" alt="<?php echo htmlspecialchars($profileUser['user_name']); ?>">
                                <?php else: ?>
                                    <div class="avatar-placeholder"><?php echo strtoupper(substr($profileUser['user_name'] ?? 'U', 0, 1)); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="profile-body">
                                <h1><?php echo htmlspecialchars($profileUser['user_name']); ?></h1>
                                <p class="muted"><?php echo $isOwnerProfile ? 'This is your personal space to manage collections, items, and events.' : 'View ' . htmlspecialchars($profileUser['user_name']) . '\'s public profile.'; ?></p>
                            </div>
                        </div>
                    </section>

                    <!-- Info Section (Meta Grid + Actions) -->
                    <section class="profile-info">
                        <div class="profile-card profile-card--info">
                            <div class="profile-meta-grid">
                                <?php if ($isOwnerProfile): ?>
                                <div>
                                    <p class="eyebrow-label"><i class="bi bi-envelope"></i> EMAIL</p>
                                    <p class="muted" style="font-size: 0.92rem;"><?php echo htmlspecialchars($profileUser['email'] ?? ''); ?></p>
                                </div>
                                <div>
                                    <p class="eyebrow-label"><i class="bi bi-calendar3"></i> DATE OF BIRTH</p>
                                    <p class="muted"><?php echo htmlspecialchars($profileUser['date_of_birth'] ?? '-'); ?></p>
                                </div>
                                <?php endif; ?>
                                <div>
                                    <p class="eyebrow-label"><i class="bi bi-clock-history"></i> MEMBER SINCE</p>
                                    <p class="muted"><?php echo htmlspecialchars(substr($profileUser['member_since'] ?? '', 0, 4)); ?></p>
                                </div>
                                <div>
                                    <p class="eyebrow-label"><i class="bi bi-collection"></i> COLLECTIONS</p>
                                    <p class="muted">
                                        <?php
                                        $collectionsCount = count($ownedCollections);
                                        echo $collectionsCount > 0 ? $collectionsCount : 'No collections yet';
                                        ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="eyebrow-label"><i class="bi bi-heart"></i> FOLLOWERS</p>
                                    <p class="muted">
                                        <?php echo $followersCount; ?>
                                        <?php if ($followersCount > 0): ?>
                                            <button type="button" class="explore-btn small ghost" onclick="document.getElementById('followers-modal').classList.add('open')" style="margin-left:8px; padding:4px 10px; font-size:0.85rem;">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>

                            <div class="profile-actions">
                                <?php if ($isOwnerProfile): ?>
                                    <a class="explore-btn ghost" href="users_form.php">Edit Profile</a>
                                    <button id="christmas-toggle" class="explore-btn" style="background: linear-gradient(135deg, #c41e3a, #165b33); border-color: #d4af37; color: white;">
                                        🎄 <span class="btn-text">Natal</span>
                                    </button>
                                <?php elseif ($isAuthenticated): ?>
                                    <form action="follow_action.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="target_id" value="<?php echo htmlspecialchars($profileUserId); ?>">
                                        <button type="submit" class="explore-btn <?php echo $isFollowingProfile ? 'success' : 'ghost'; ?>">
                                            <?php echo $isFollowingProfile ? 'Following' : 'Follow'; ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>

                    <?php if (!empty($topCollections)): ?>
                        <div class="section-header">
                            <h3 class="section-title">Your Top 3 Collections</h3>
                            <?php if ($isOwnerProfile): ?>
                                <a class="explore-btn success" href="collections_form.php">Add Collection</a>
                            <?php endif; ?>
                        </div>
                        <div class="user-top-collections-controls">
                            <form id="user-top-filters" class="filters-form" method="GET">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($profileUserId); ?>">
                                <div class="filter-chip filter-chip--select">
                                    <label class="filter-chip__label" for="top-sort-select">
                                        <i class="bi bi-funnel"></i>
                                        <span>Top por</span>
                                    </label>
                                    <select id="top-sort-select" name="topSort" class="filter-chip__select" onchange="gcSubmitWithScroll(this.form)">
                                        <option value="likes" <?php echo $topSort==='likes'?'selected':''; ?>>More likes</option>
                                        <option value="value" <?php echo $topSort==='value'?'selected':''; ?>>Higher value</option>
                                        <option value="oldest" <?php echo $topSort==='oldest'?'selected':''; ?>>Oldest</option>
                                        <option value="items" <?php echo $topSort==='items'?'selected':''; ?>>Most items</option>
                                    </select>
                                </div>
                            </form>
                        </div>
                        <div class="cards-grid cards-grid--spaced">
                            <?php foreach ($topCollections as $entry): ?>
                                <?php
                                $col = $entry['data'];
                                $img = $col['coverImage'] ?? '';
                                if ($img && !preg_match('#^https?://#', $img)) {
                                    $img = '../../' . ltrim($img, './');
                                }
                                $collectionLink = 'specific_collection.php?id=' . urlencode($col['id']);
                                $typeFilterLink = !empty($col['type'])
                                    ? 'all_collections.php?' . http_build_query(['type' => $col['type']])
                                    : '';
                                $itemsTotal = $entry['items'];
                                $likesTotal = $entry['likes'];
                                $valueTotal = $entry['value'];
                                ?>
                                <article class="collection-card collection-card-link" role="link" tabindex="0" data-collection-link="<?php echo htmlspecialchars($collectionLink); ?>">
                                    <a href="<?php echo htmlspecialchars($collectionLink); ?>" class="collection-card__media">
                                        <img src="<?php echo htmlspecialchars($img ?: '../../images/default.jpg'); ?>" alt="<?php echo htmlspecialchars($col['name']); ?>">
                                    </a>
                                    <div class="collection-card__body">
                                        <?php if (!empty($typeFilterLink)): ?>
                                            <a class="pill pill--link" href="<?php echo htmlspecialchars($typeFilterLink); ?>">
                                                <?php echo htmlspecialchars($col['type']); ?>
                                            </a>
                                        <?php else: ?>
                                            <p class="pill"><?php echo htmlspecialchars($col['type'] ?? ''); ?></p>
                                        <?php endif; ?>
                                        <h3>
                                            <a href="<?php echo htmlspecialchars($collectionLink); ?>">
                                                <?php echo htmlspecialchars($col['name']); ?>
                                            </a>
                                        </h3>
                                        <p class="muted"><?php echo htmlspecialchars($col['summary'] ?? ''); ?></p>
                                        <div class="collection-card__facts">
                                            <div class="collection-card__date">
                                                <i class="bi bi-calendar3"></i>
                                                <span><?php echo htmlspecialchars(substr($col['createdAt'] ?? '', 0, 10)); ?></span>
                                            </div>
                                            <div class="collection-card__meta">
                                                <span>
                                                    <i class="bi bi-box-seam"></i>
                                                    <?php echo $itemsTotal; ?> items
                                                </span>
                                                <span>
                                                    <i class="bi bi-heart-fill"></i>
                                                    <?php echo $likesTotal; ?> likes
                                                </span>
                                                <span>
                                                    <i class="bi bi-currency-euro"></i>
                                                    <?php echo $valueTotal > 0 ? number_format($valueTotal, 2, ',', '.') : '0,00'; ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="collection-card__actions card-actions">
                                            <?php if ($isAuthenticated): ?>
                                                <form action="likes_action.php" method="POST" class="action-icon-form like-form">
                                                    <input type="hidden" name="type" value="collection">
                                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($col['id']); ?>">
                                                    <button type="submit" class="action-icon<?php echo $likesTotal > 0 ? ' is-liked' : ''; ?>" title="Like">
                                                        <i class="bi <?php echo $likesTotal > 0 ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
                                                        <span class="like-count<?php echo $likesTotal === 0 ? ' is-zero' : ''; ?>"><?php echo $likesTotal; ?></span>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button type="button" class="action-icon" data-action="login-popup" data-login-url="auth.php" title="Like">
                                                    <i class="bi bi-heart"></i>
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($isOwnerProfile): ?>
                                                <a class="action-icon" href="events_form.php?collectionId=<?php echo urlencode($col['id']); ?>" title="Add Event">
                                                    <i class="bi bi-calendar-plus"></i>
                                                </a>
                                                <a class="action-icon" href="collections_form.php?id=<?php echo urlencode($col['id']); ?>&from=user_page" title="Edit">
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
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <h3 class="section-title">Your Collections</h3>
                        <p class="muted">
                            You don't have any collections yet.
                        </p>
                        <?php if ($isOwnerProfile): ?>
                            <div class="empty-state-actions">
                                <a class="explore-btn success" href="collections_form.php">Add Collection</a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php
                    // Secção de eventos: mostra sempre o título, e ou a lista ou a mensagem de vazio
                    $collectorName = htmlspecialchars($profileUser['user_name'] ?? $profileUser['username'] ?? 'este colecionador');
                    ?>
                    
                    <!-- Upcoming Events Section -->
                    <section class="user-events-section">
                        <h2 class="section-title">
                            Upcoming Events you're attending
                        </h2>

                        <?php if (!empty($upcomingEvents)): ?>
                            <div class="user-events-grid">
                        <?php foreach ($upcomingEvents as $evt): ?>
                            <?php
                                $eventId = $evt['id'] ?? $evt['event_id'] ?? '';
                                $costValue = $evt['cost'] ?? null;
                                $costLabel = ($costValue === '' || $costValue === null) ? 'Free entrance' : '€' . number_format((float)$costValue, 2, ',', '.');
                                $eventDateRaw = $evt['date'] ?? '';
                                // Formata a data do evento garantindo que a hora aparece sempre que exista no valor original.
                                $eventDateDisplay = '';
                                if ($eventDateRaw !== '') {
                                    $normalizedRaw = str_replace('T', ' ', $eventDateRaw);
                                    $rawHasTime = preg_match('/\d{2}:\d{2}/', $normalizedRaw) === 1;
                                    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $normalizedRaw)
                                        ?: DateTime::createFromFormat('Y-m-d H:i', $normalizedRaw)
                                        ?: DateTime::createFromFormat('Y-m-d', $normalizedRaw);
                                    if ($dt instanceof DateTime) {
                                        $eventDateDisplay = $dt->format($rawHasTime ? 'd/m/Y H:i' : 'd/m/Y');
                                    } else {
                                        $eventDateDisplay = $rawHasTime
                                            ? substr($normalizedRaw, 0, 16)
                                            : substr($normalizedRaw, 0, 10);
                                    }
                                }
                            ?>
                            <a class="user-event-card user-event-card--link" href="event_page.php?id=<?php echo urlencode($eventId); ?>">
                                <div class="user-event-card__top">
                                    <span class="pill pill--event"><?php echo htmlspecialchars($evt['type'] ?? 'Evento'); ?></span>
                                    <span class="user-event-badge soon">SOON</span>
                                </div>
                                <h3><?php echo htmlspecialchars($evt['name'] ?? 'Evento sem nome'); ?></h3>
                                <ul class="user-event-meta">
                                    <?php if (!empty($eventDateDisplay)): ?>
                                        <li>
                                            <i class="bi bi-calendar-event"></i>
                                            <?php echo htmlspecialchars($eventDateDisplay); ?>
                                        </li>
                                    <?php endif; ?>
                                    <?php if (!empty($evt['localization'])): ?>
                                        <li>
                                            <i class="bi bi-geo-alt"></i>
                                            <?php echo htmlspecialchars($evt['localization']); ?>
                                        </li>
                                    <?php endif; ?>
                                    <?php if ($costLabel): ?>
                                        <li>
                                            <i class="bi bi-cash-coin"></i>
                                            <?php echo htmlspecialchars($costLabel); ?>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                                <div class="user-event-cta">
                                    <i class="bi bi-check2-circle"></i>
                                    <span>RSVP</span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="muted">
                                No upcoming events with RSVP confirmed.
                            </p>
                        <?php endif; ?>
                    </section>

                    <!-- Past Events Section -->
                    <section class="user-events-section">
                        <h2 class="section-title">
                            Past Events
                        </h2>

                        <?php if (!empty($pastEvents)): ?>
                            <div class="user-events-grid">
                        <?php foreach ($pastEvents as $evt): ?>
                            <?php
                                $eventId = $evt['id'] ?? $evt['event_id'] ?? '';
                                $costValue = $evt['cost'] ?? null;
                                $costLabel = ($costValue === '' || $costValue === null) ? ' Free entrance' : '€' . number_format((float)$costValue, 2, ',', '.');
                                $eventDateRaw = $evt['date'] ?? '';
                                // Formata a data do evento garantindo que a hora aparece sempre que exista no valor original.
                                $eventDateDisplay = '';
                                if ($eventDateRaw !== '') {
                                    $normalizedRaw = str_replace('T', ' ', $eventDateRaw);
                                    $rawHasTime = preg_match('/\d{2}:\d{2}/', $normalizedRaw) === 1;
                                    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $normalizedRaw)
                                        ?: DateTime::createFromFormat('Y-m-d H:i', $normalizedRaw)
                                        ?: DateTime::createFromFormat('Y-m-d', $normalizedRaw);
                                    if ($dt instanceof DateTime) {
                                        $eventDateDisplay = $dt->format($rawHasTime ? 'd/m/Y H:i' : 'd/m/Y');
                                    } else {
                                        $eventDateDisplay = $rawHasTime
                                            ? substr($normalizedRaw, 0, 16)
                                            : substr($normalizedRaw, 0, 10);
                                    }
                                }
                            ?>
                            <a class="user-event-card user-event-card--link" href="event_page.php?id=<?php echo urlencode($eventId); ?>">
                                <div class="user-event-card__top">
                                    <span class="pill pill--event"><?php echo htmlspecialchars($evt['type'] ?? 'Evento'); ?></span>
                                    <span class="user-event-badge past">PAST</span>
                                </div>
                                <h3><?php echo htmlspecialchars($evt['name'] ?? 'Evento sem nome'); ?></h3>
                                <ul class="user-event-meta">
                                    <?php if (!empty($eventDateDisplay)): ?>
                                        <li>
                                            <i class="bi bi-calendar-event"></i>
                                            <?php echo htmlspecialchars($eventDateDisplay); ?>
                                        </li>
                                    <?php endif; ?>
                                    <?php if (!empty($evt['localization'])): ?>
                                        <li>
                                            <i class="bi bi-geo-alt"></i>
                                            <?php echo htmlspecialchars($evt['localization']); ?>
                                        </li>
                                    <?php endif; ?>
                                    <?php if ($costLabel): ?>
                                        <li>
                                            <i class="bi bi-cash-coin"></i>
                                            <?php echo htmlspecialchars($costLabel); ?>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                                <div class="user-event-cta past">
                                    <i class="bi bi-eye"></i>
                                    <span>View</span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="muted">
                                No past events attended.
                            </p>
                        <?php endif; ?>
                    </section>

                <?php endif; ?>  <!-- fecha if ($currentUser) -->
        </main>

        <!-- Followers Modal -->
        <div class="modal-backdrop" id="followers-modal">
            <div class="modal-card" style="max-width:500px;">
                <div class="modal-header" style="background: linear-gradient(135deg, #0b3b70 0%, #1e40af 100%);">
                    <button class="modal-close" aria-label="Close" onclick="document.getElementById('followers-modal').classList.remove('open')">
                        <i class="bi bi-x"></i>
                    </button>
                    <h3 style="font-size:1.8rem; margin:0; color:white;">Followers</h3>
                </div>
                <div class="modal-body" style="padding:24px; max-height:400px; overflow-y:auto;">
                    <?php if (!empty($followersList)): ?>
                        <ul style="list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:12px;">
                            <?php foreach ($followersList as $follower): ?>
                                <?php
                                $followerId = $follower['id'] ?? $follower['user_id'] ?? null;
                                $followerName = $follower['user_name'] ?? $follower['username'] ?? 'Unknown';
                                $followerPhoto = $follower['user_photo'] ?? '';
                                if ($followerPhoto && !preg_match('#^https?://#', $followerPhoto)) {
                                    // Stored in /PHP/uploads/... so from /PHP/pages go one level up
                                    $followerPhoto = '../' . ltrim($followerPhoto, './');
                                }
                                ?>
                                <li style="display:flex; align-items:center; gap:12px; padding:12px; background:#f9fafb; border-radius:12px; border:1px solid #e5e7eb;">
                                    <div style="width:48px; height:48px; border-radius:50%; overflow:hidden; background:#e5e7eb; flex-shrink:0;">
                                        <?php if (!empty($followerPhoto)): ?>
                                            <img src="<?php echo htmlspecialchars($followerPhoto); ?>" alt="<?php echo htmlspecialchars($followerName); ?>" style="width:100%; height:100%; object-fit:cover;">
                                        <?php else: ?>
                                            <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-weight:700; color:#0b3b70; font-size:1.2rem;">
                                                <?php echo strtoupper(substr($followerName, 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <a href="user_page.php?id=<?php echo urlencode($followerId); ?>" style="flex:1; font-weight:600; color:#0b3b70; text-decoration:none; font-size:1rem;" onmouseover="this.style.color='#0d3f7a';" onmouseout="this.style.color='#0b3b70';">
                                        <?php echo htmlspecialchars($followerName); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="muted" style="text-align:center; color:#6b7280;">No followers yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <link rel="stylesheet" href="../../CSS/user_page_inline.css">


        <script src="../../JS/gc-scroll-restore.js"></script>
        <script src="../../JS/user_page.js"></script>


        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </body>
</html>

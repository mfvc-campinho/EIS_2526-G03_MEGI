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


// Map events per owned collection
$eventsByCollection = [];
foreach ($collectionEvents as $link) {
    $cid = $link['collectionId'] ?? null;
    if (!$cid)
        continue;
    $eventsByCollection[$cid][] = $link['eventId'];
}

// =========================
// Eventos do utilizador (via coleções que lhe pertencem)
// =========================
// índice rápido de eventos por id
$eventsById = [];
foreach ($events as $e) {
    if (!empty($e['id'])) {
        $eventsById[$e['id']] = $e;
    }
}

// ids de eventos únicos onde este utilizador tem coleções inscritas
$userEventIds = [];
foreach ($ownedCollections as $c) {
    $cid = $c['id'] ?? null;
    if (!$cid)
        continue;
    foreach ($eventsByCollection[$cid] ?? [] as $eid) {
        $userEventIds[$eid] = true;
    }
}

// Build RSVP map for the profile user
$userRsvpMap = [];
foreach ($eventsUsers as $eu) {
    $uid = $eu['userId'] ?? $eu['user_id'] ?? null;
    $eid = $eu['eventId'] ?? $eu['event_id'] ?? null;
    $rsvp = $eu['rsvp'] ?? null;
    if ($uid == $profileUserId && $eid && $rsvp) {
        $userRsvpMap[$eid] = $rsvp;
    }
}

// array final de eventos do utilizador
$today = date('Y-m-d');
$upcomingEvents = [];
$pastEvents = [];

foreach (array_keys($userEventIds) as $eid) {
    if (isset($eventsById[$eid])) {
        $evt = $eventsById[$eid];
        $eventDate = substr($evt['date'] ?? '', 0, 10);
        $rsvp = $userRsvpMap[$eid] ?? null;
        
        if ($eventDate >= $today) {
            // Upcoming: only include if RSVP exists
            if ($rsvp) {
                $upcomingEvents[] = $evt;
            }
        } else {
            // Past: only include if RSVP exists
            if ($rsvp) {
                $pastEvents[] = $evt;
            }
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
foreach ($userFollows as $follower => $list) {
    if (in_array($profileUserId, $list ?? [], true))
        $followersCount++;
}
$isOwnerProfile = $currentUserId && $profileUserId && $currentUserId === $profileUserId;
$isFollowingProfile = $isAuthenticated && !$isOwnerProfile && in_array($profileUserId, $followingList, true);

// Exemplo: depois de obter $user e $userCollections
?>
<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>User Profile — PHP</title>

        <!-- Fonte -->
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

        <!-- Estilos globais -->
        <link rel="stylesheet" href="../../CSS/general.css">

        <!-- Estilos específicos da página de perfil -->
        <link rel="stylesheet" href="user_page.css">

        <!-- Ícones -->
        <link rel="stylesheet"
              href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

        <script src="././JS/theme-toggle.js"></script>
    </head>


    <body>
        <?php include __DIR__ . '/../includes/nav.php'; ?>

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

                <?php if (!$currentUser): ?>
                    <section class="profile-hero">
                        <p class="muted">Please log in to see your profile.</p>
                    </section>
                <?php else: ?>
                    <section class="profile-hero">
                        <div class="profile-card profile-card--hero">
                            <div class="profile-avatar">
                                <?php
                                $avatar = $currentUser['user_photo'] ?? '';
                                if ($avatar && !preg_match('#^https?://#', $avatar)) {
                                    $avatar = '../../' . ltrim($avatar, './');
                                }
                                ?>
                                <?php if (!empty($avatar)): ?>
                                    <img src="<?php echo htmlspecialchars($avatar); ?>" alt="<?php echo htmlspecialchars($currentUser['user_name']); ?>">
                                <?php else: ?>
                                    <div class="avatar-placeholder"><?php echo strtoupper(substr($currentUser['user_name'] ?? 'U', 0, 1)); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="profile-body">
                                <h1><?php echo htmlspecialchars($profileUser['user_name']); ?></h1>
                                <p class="muted">This is your personal space to manage collections, items, and events.</p>

                                <div class="profile-meta-grid">
                                    <div>
                                        <p class="eyebrow-label"><i class="bi bi-envelope"></i> Email</p>
                                        <p class="muted"><?php echo htmlspecialchars($profileUser['email'] ?? ''); ?></p>
                                    </div>
                                    <div>
                                        <p class="eyebrow-label"><i class="bi bi-calendar3"></i> Date of Birth</p>
                                        <p class="muted"><?php echo htmlspecialchars($profileUser['date_of_birth'] ?? '-'); ?></p>
                                    </div>
                                    <div>
                                        <p class="eyebrow-label"><i class="bi bi-clock-history"></i> Member Since</p>
                                        <p class="muted"><?php echo htmlspecialchars(substr($profileUser['member_since'] ?? '', 0, 10)); ?></p>
                                    </div>
                                    <div>
                                        <p class="eyebrow-label"><i class="bi bi-collection"></i> Collections</p>
                                        <p class="muted">
                                            <?php
                                            $collectionsCount = count($ownedCollections);
                                            echo $collectionsCount > 0 ? $collectionsCount : 'No collections yet';
                                            ?>
                                        </p>

                                    </div>
                                    <div>
                                        <p class="eyebrow-label"><i class="bi bi-heart"></i> Followers</p>
                                        <p class="muted"><?php echo $followersCount; ?></p>
                                    </div>
                                </div>

                                <div class="profile-actions">
                                    <?php if ($isOwnerProfile): ?>
                                        <a class="explore-btn ghost" href="users_form.php">Edit Profile</a>
                                        <a class="explore-btn success" href="collections_form.php">Add Collection</a>
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
                        </div>
                    </section>

                    <?php if ($ownedCollections): ?>
                        <h3 class="section-title">Your Collections</h3>
                        <div class="cards-grid">
                            <?php foreach ($ownedCollections as $col): ?>
                                <?php
                                $img = $col['coverImage'] ?? '';
                                if ($img && !preg_match('#^https?://#', $img)) {
                                    $img = '../../' . ltrim($img, './');
                                }
                                ?>
                                <article class="product-card">
                                    <a href="specific_collection.php?id=<?php echo urlencode($col['id']); ?>" class="product-card__media">
                                        <img src="<?php echo htmlspecialchars($img ?: '../../images/default.jpg'); ?>" alt="<?php echo htmlspecialchars($col['name']); ?>">
                                    </a>
                                    <div class="product-card__body">
                                        <p class="pill"><?php echo htmlspecialchars($col['type'] ?? ''); ?></p>
                                        <h3>
                                            <a href="specific_collection.php?id=<?php echo urlencode($col['id']); ?>">
                                                <?php echo htmlspecialchars($col['name']); ?>
                                            </a>
                                        </h3>
                                        <p class="muted"><?php echo htmlspecialchars($col['summary'] ?? ''); ?></p>
                                        <div class="product-card__meta">
                                            <span>
                                                <i class="bi bi-calendar3"></i>
                                                <?php echo htmlspecialchars(substr($col['createdAt'] ?? '', 0, 10)); ?>
                                            </span>
                                            <span>
                                                <i class="bi bi-box-seam"></i>
                                                <?php echo count($itemsByCollection[$col['id']] ?? []); ?> items
                                            </span>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <h3 class="section-title">Your Collections</h3>
                        <p class="muted">
                            You don't have any collections yet. Click
                            <strong>Add Collection</strong> to create your first one.
                        </p>
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
                                    <article class="user-event-card">
                                        <p class="pill pill--event">
                                            <?php echo htmlspecialchars($evt['type'] ?? 'Evento'); ?>
                                        </p>

                                        <h3><?php echo htmlspecialchars($evt['name'] ?? 'Evento sem nome'); ?></h3>

                                        <ul class="user-event-meta">
                                            <?php if (!empty($evt['date'])): ?>
                                                <li>
                                                    <i class="bi bi-calendar-event"></i>
                                                    <?php echo htmlspecialchars($evt['date']); ?>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </article>
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
                                    <article class="user-event-card">
                                        <p class="pill pill--event">
                                            <?php echo htmlspecialchars($evt['type'] ?? 'Evento'); ?>
                                        </p>

                                        <h3><?php echo htmlspecialchars($evt['name'] ?? 'Evento sem nome'); ?></h3>

                                        <ul class="user-event-meta">
                                            <?php if (!empty($evt['date'])): ?>
                                                <li>
                                                    <i class="bi bi-calendar-event"></i>
                                                    <?php echo htmlspecialchars($evt['date']); ?>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </article>
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

        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </body>
</html>


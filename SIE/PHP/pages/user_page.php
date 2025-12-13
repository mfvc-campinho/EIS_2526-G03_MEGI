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

        <!-- Christmas Theme CSS -->
        <link rel="stylesheet" href="../../CSS/christmas.css">

        <script src="././JS/theme-toggle.js"></script>
        <script src="../../JS/christmas-theme.js"></script>
        <style>
            .collection-card-link { cursor: pointer; position: relative; }
            .collection-card-link:focus { outline: 2px solid #6366f1; outline-offset: 4px; }
            .collection-card-link:focus-visible { outline: 2px solid #6366f1; outline-offset: 4px; }
            .pill--link { display: inline-flex; align-items: center; gap: 4px; text-decoration: none; color: inherit; }
            .pill--link:hover { text-decoration: none; box-shadow: 0 0 0 2px rgba(99,102,241,0.18); }
        </style>
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

                <?php if (!$profileUser): ?>
                    <section class="profile-hero">
                        <?php if (!$isAuthenticated): ?>
                            <p class="muted">Please <a href="user_create.php">login</a> to view your profile.</p>
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
                                    $avatar = '../../' . ltrim($avatar, './');
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

                    <?php if ($ownedCollections): ?>
                        <div class="section-header">
                            <h3 class="section-title">Your Collections</h3>
                            <?php if ($isOwnerProfile): ?>
                                <a class="explore-btn success" href="collections_form.php">Add Collection</a>
                            <?php endif; ?>
                        </div>
                        <div class="cards-grid cards-grid--spaced">
                            <?php foreach ($ownedCollections as $col): ?>
                                <?php
                                $img = $col['coverImage'] ?? '';
                                if ($img && !preg_match('#^https?://#', $img)) {
                                    $img = '../../' . ltrim($img, './');
                                }
                                $collectionLink = 'specific_collection.php?id=' . urlencode($col['id']);
                                $typeFilterLink = !empty($col['type'])
                                    ? 'all_collections.php?' . http_build_query(['type' => $col['type']])
                                    : '';
                                ?>
                                <article class="product-card collection-card-link" role="link" tabindex="0" data-collection-link="<?php echo htmlspecialchars($collectionLink); ?>">
                                    <a href="<?php echo htmlspecialchars($collectionLink); ?>" class="product-card__media">
                                        <img src="<?php echo htmlspecialchars($img ?: '../../images/default.jpg'); ?>" alt="<?php echo htmlspecialchars($col['name']); ?>">
                                    </a>
                                    <div class="product-card__body">
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
                                    $followerPhoto = '../../' . ltrim($followerPhoto, './');
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

        <style>
            .modal-backdrop { position:fixed; inset:0; background:rgba(0,0,0,0.5); display:none; align-items:center; justify-content:center; z-index:1000; backdrop-filter: blur(4px); }
            .modal-backdrop.open { display:flex; animation: fadeIn 0.2s ease; }
            @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
            .modal-card { background:#fff; border-radius:18px; padding:0; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.3); position:relative; animation: slideUp 0.3s ease; overflow:hidden; }
            @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
            .modal-header { padding: 24px 28px; position: relative; text-align: center; }
            .modal-close { position: absolute; top: 12px; right: 12px; border:none; background: rgba(255,255,255,0.2); color: white; width: 36px; height: 36px; border-radius: 50%; font-size:20px; cursor:pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease; }
            .modal-close:hover { background: rgba(255,255,255,0.3); transform: rotate(90deg); }
            .modal-body { padding: 24px; }
            .section-header { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
            .section-header .section-title { margin-bottom:0; }
            .cards-grid--spaced { margin-top: 18px; }
            .empty-state-actions { margin: 12px 0; }
        </style>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var cards = document.querySelectorAll('.collection-card-link');
                cards.forEach(function(card) {
                    var href = card.getAttribute('data-collection-link');
                    if (!href) return;
                    card.addEventListener('click', function(e) {
                        if (e.target.closest('a, button')) {
                            return;
                        }
                        window.location.href = href;
                    });
                    card.addEventListener('keydown', function(e) {
                        if (e.target !== card) return;
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            window.location.href = href;
                        }
                    });
                });
            });
        </script>

        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </body>
</html>


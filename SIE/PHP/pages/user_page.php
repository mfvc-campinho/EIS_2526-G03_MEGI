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
  if (!$cid) continue;
  $itemsByCollection[$cid][] = $link['itemId'];
}


// Map events per owned collection
$eventsByCollection = [];
foreach ($collectionEvents as $link) {
  $cid = $link['collectionId'] ?? null;
  if (!$cid) continue;
  $eventsByCollection[$cid][] = $link['eventId'];
  
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
  if (!$cid) continue;
  foreach ($eventsByCollection[$cid] ?? [] as $eid) {
    $userEventIds[$eid] = true;
  }
}

// array final de eventos do utilizador
$userEvents = [];
foreach (array_keys($userEventIds) as $eid) {
  if (isset($eventsById[$eid])) {
    $userEvents[] = $eventsById[$eid];
  }
}

// ordenar por data (se o campo 'date' existir)
usort($userEvents, function ($a, $b) {
  return strcmp($a['date'] ?? '', $b['date'] ?? '');
});
}
// Followers count and following map
$userFollows = $data['userFollows'] ?? [];
$followingList = $userFollows[$currentUserId] ?? [];
$followersCount = 0;
foreach ($userFollows as $follower => $list) {
  if (in_array($profileUserId, $list ?? [], true)) $followersCount++;
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

  <main class="page">
    <?php flash_render(); ?>

    <div class="page-shell">
      <nav class="breadcrumb-nav" aria-label="Breadcrumb">
        <ol class="breadcrumb-list">
          <li class="breadcrumb-item"><a href="home_page.php">Home</a></li>
          <li class="breadcrumb-item" aria-current="page">User Profile</li>
        </ol>
      </nav>

      <div class="hero-title">
        <h1>User Profile</h1>
        <div class="hero-underline"></div>
      </div>

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
                  <p class="eyebrow-label">Email</p>
                  <p class="muted"><?php echo htmlspecialchars($profileUser['email'] ?? ''); ?></p>
                </div>
                <div>
                  <p class="eyebrow-label">Date of Birth</p>
                  <p class="muted"><?php echo htmlspecialchars($profileUser['date_of_birth'] ?? '-'); ?></p>
                </div>
                <div>
                  <p class="eyebrow-label">Member Since</p>
                  <p class="muted"><?php echo htmlspecialchars(substr($profileUser['member_since'] ?? '', 0, 10)); ?></p>
                </div>
                <div>
                  <p class="eyebrow-label">Collections</p>
                  <p class="muted"><?php echo count($ownedCollections); ?></p>
                </div>
                <div>
                  <p class="eyebrow-label">Followers</p>
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
                if ($img && !preg_match('#^https?://#', $img)) $img = '../../' . ltrim($img, './');
              ?>
              <article class="product-card">
                <a href="specific_collection.php?id=<?php echo urlencode($col['id']); ?>" class="product-card__media">
                  <img src="<?php echo htmlspecialchars($img ?: '../../images/default.jpg'); ?>" alt="<?php echo htmlspecialchars($col['name']); ?>">
                </a>
                <div class="product-card__body">
                  <p class="pill"><?php echo htmlspecialchars($col['type'] ?? ''); ?></p>
                  <h3><a href="specific_collection.php?id=<?php echo urlencode($col['id']); ?>"><?php echo htmlspecialchars($col['name']); ?></a></h3>
                  <p class="muted"><?php echo htmlspecialchars($col['summary'] ?? ''); ?></p>
                  <div class="product-card__meta">
                    <span><i class="bi bi-calendar3"></i> <?php echo htmlspecialchars(substr($col['createdAt'] ?? '', 0, 10)); ?></span>
                    <span><i class="bi bi-box-seam"></i> <?php echo count($itemsByCollection[$col['id']] ?? []); ?> items</span>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
          <?php if (!empty($userEvents)): ?>
  <section class="user-events">
    <h2 class="section-title">
      Eventos em que <?php echo htmlspecialchars($user['username']); ?> está inscrito
    </h2>

    <div class="events-list">
      <?php foreach ($userEvents as $event): ?>
        <article class="event-card">
          <h3><?php echo htmlspecialchars($event['name']); ?></h3>

          <div class="event-card-meta">
            <?php if (!empty($event['date'])): ?>
              <span>
                <i class="bi bi-calendar-event"></i>
                <?php echo htmlspecialchars($event['date']); ?>
              </span>
            <?php endif; ?>

            <?php if (!empty($event['location'])): ?>
              <span>
                <i class="bi bi-geo-alt"></i>
                <?php echo htmlspecialchars($event['location']); ?>
              </span>
            <?php endif; ?>

            <?php if (!empty($event['status'])): ?>
              <span>
                <i class="bi bi-check-circle"></i>
                <?php echo htmlspecialchars($event['status']); ?>
              </span>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>
        <?php endif; ?>

      <?php endif; ?>
    </div>
  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>

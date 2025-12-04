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

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Profile — PHP</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../CSS/general.css">
  <link rel="stylesheet" href="../../CSS/profile.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    .flash {padding:12px 14px;border-radius:6px;margin:12px 0;}
    .flash-success{background:#d1e7dd;color:#0f5132;}
    .flash-error{background:#f8d7da;color:#842029;}
    body { background:#f5f6f8; }
    .page-shell { max-width: 1200px; margin: 0 auto; padding: 20px 20px 60px; }
    .hero-title { text-align:center; margin: 30px 0 16px; }
    .hero-title h1 { margin:0; font-size:2.2rem; }
    .hero-underline { width:120px; height:4px; background:#4f9cf9; margin:10px auto 0; border-radius:999px; }
    .cards-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:20px; }
    .product-card { background:#fff; border-radius:18px; box-shadow:0 14px 30px rgba(0,0,0,0.08); overflow:hidden; display:flex; flex-direction:column; }
    .product-card__media img { width:100%; height:180px; object-fit:cover; }
    .product-card__body { padding:14px 16px; display:flex; flex-direction:column; gap:6px; }
    .pill { display:inline-flex; padding:4px 10px; background:#eef2ff; color:#3b4cca; border-radius:999px; font-weight:600; font-size:0.85rem; }
    .product-card__meta { display:flex; justify-content:space-between; color:#6b7280; font-size:0.9rem; }
    .section-title { margin:26px 0 12px; font-size:1.3rem; }
  </style>
  <script src="../../JS/theme-toggle.js"></script>
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
        <?php endif; ?>

      <?php endif; ?>
    </div>
  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>

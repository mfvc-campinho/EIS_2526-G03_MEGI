<?php
session_start();
require_once __DIR__ . '/../includes/data_loader.php';
require_once __DIR__ . '/../includes/flash.php';

$data = load_app_data($mysqli);
$mysqli->close();
$users = $data['users'] ?? [];
$collections = $data['collections'] ?? [];

$isAuthenticated = !empty($_SESSION['user']);
$currentUserId = $isAuthenticated ? ($_SESSION['user']['id'] ?? null) : null;
$currentUser = null;
foreach ($users as $u) {
  if ($currentUserId && (($u['id'] ?? $u['user_id']) == $currentUserId)) {
    $currentUser = $u;
    break;
  }
}
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
  </style>
  <script src="../../JS/theme-toggle.js"></script>
</head>

<body>
  <?php include __DIR__ . '/../includes/nav.php'; ?>

  <main class="page">
    <?php flash_render(); ?>

    <nav class="breadcrumb-nav" aria-label="Breadcrumb">
      <ol class="breadcrumb-list">
        <li class="breadcrumb-item"><a href="home_page.php">Home</a></li>
        <li class="breadcrumb-item" aria-current="page">User Profile</li>
      </ol>
    </nav>

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
            <h1><?php echo htmlspecialchars($currentUser['user_name']); ?></h1>
            <p class="muted">This is your personal space to manage collections, items, and events.</p>

            <div class="profile-meta-grid">
              <div>
                <p class="eyebrow-label">Email</p>
                <p class="muted"><?php echo htmlspecialchars($currentUser['email'] ?? ''); ?></p>
              </div>
              <div>
                <p class="eyebrow-label">Date of Birth</p>
                <p class="muted"><?php echo htmlspecialchars($currentUser['date_of_birth'] ?? '-'); ?></p>
              </div>
              <div>
                <p class="eyebrow-label">Member Since</p>
                <p class="muted"><?php echo htmlspecialchars(substr($currentUser['member_since'] ?? '', 0, 10)); ?></p>
              </div>
              <div>
                <p class="eyebrow-label">Collections</p>
                <p class="muted"><?php echo count(array_filter($collections, fn($c) => ($c['ownerId'] ?? null) == $currentUserId)); ?></p>
              </div>
            </div>

            <div class="profile-actions">
              <a class="explore-btn ghost" href="users_form.php">Edit Profile</a>
              <a class="explore-btn success" href="collections_form.php">Add Collection</a>
            </div>
          </div>
        </div>
      </section>
    <?php endif; ?>
  </main>

  <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>

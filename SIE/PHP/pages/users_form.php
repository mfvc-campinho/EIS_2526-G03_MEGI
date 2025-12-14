<?php
session_start();
require_once __DIR__ . '/../includes/data_loader.php';
require_once __DIR__ . '/../includes/flash.php';

if (empty($_SESSION['user'])) {
  flash_set('error', 'You need to log in to edit your profile.');
  header('Location: user_page.php');
  exit;
}
$currentUserId = $_SESSION['user']['id'] ?? null;

$data = load_app_data($mysqli);
$mysqli->close();
$users = $data['users'] ?? [];
$user = null;
foreach ($users as $u) {
  if (($u['id'] ?? $u['user_id']) == $currentUserId) {
    $user = $u;
    break;
  }
}
if (!$user) {
  flash_set('error', 'User not found.');
  header('Location: user_page.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Profile • GoodCollections</title>
  <link rel="stylesheet" href="../../CSS/general.css">
  <link rel="stylesheet" href="../../CSS/forms.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../../CSS/christmas.css">
  <script src="../../JS/theme-toggle.js"></script>
  <script src="../../JS/christmas-theme.js"></script>
</head>

<body>
  <?php include __DIR__ . '/../includes/nav.php'; ?>

  <main class="page">
    <?php flash_render(); ?>
    <header class="page__header">
      <h1>Edit Profile</h1>
      <a href="user_page.php" class="text-link">Back</a>
    </header>

    <form class="form-card" action="users_action.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="update">
      <label>Name <span class="required-badge">R</span></label>
      <input type="text" name="name" required value="<?php echo htmlspecialchars($user['user_name']); ?>">

      <label>Email <span class="required-badge">R</span></label>
      <input type="email" name="email" required value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">

      <label>Photo (upload)</label>
      <input type="file" name="photoFile" accept="image/*">
      <?php if (!empty($user['user_photo'])): ?>
        <p class="muted" style="margin-top:4px;">Current photo: <?php echo htmlspecialchars($user['user_photo']); ?> (leave empty to keep)</p>
      <?php endif; ?>

      <label>Date of birth</label>
      <input type="date" name="dob" value="<?php echo htmlspecialchars($user['date_of_birth'] ?? ''); ?>">

      <label>Password (leave empty to keep)</label>
      <input type="password" name="password" value="">

      <div class="actions">
        <button type="submit" class="explore-btn">Save</button>
        <a class="explore-btn ghost" href="user_page.php">Cancel</a>
      </div>
    </form>
  </main>
</body>

</html>



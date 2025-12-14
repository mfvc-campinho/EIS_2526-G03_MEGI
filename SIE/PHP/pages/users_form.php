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
  <link rel="stylesheet" href="../../CSS/navbar.css">
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
        <?php
          $avatarPreview = $user['user_photo'];
          if ($avatarPreview && !preg_match('#^https?://#', $avatarPreview)) {
            // Files are stored under /PHP/uploads/... so from /PHP/pages go one level up
            $avatarPreview = '../' . ltrim($avatarPreview, './');
          }
        ?>
        <div class="muted" style="margin-top:8px; display:flex; align-items:flex-start; gap:10px;">
          <div style="flex:0 0 auto; width:96px; height:96px; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; background:#f8fafc; display:flex; align-items:center; justify-content:center;">
            <img src="<?php echo htmlspecialchars($avatarPreview); ?>" alt="Current photo" style="max-width:100%; max-height:100%; object-fit:cover;">
          </div>
          <p style="margin:0; line-height:1.5;">Current photo (leave empty to keep).</p>
        </div>
      <?php endif; ?>

      <label>Date of birth <span class="required-badge">R</span></label>
      <input type="date" name="dob" id="edit-dob" required value="<?php echo htmlspecialchars($user['date_of_birth'] ?? ''); ?>">

      <label>Password (leave empty to keep)</label>
      <input type="password" name="password" value="">

      <div class="actions">
        <button type="submit" class="explore-btn">Save</button>
        <a class="explore-btn ghost" href="user_page.php">Cancel</a>
      </div>
    </form>
    <script>
      (function(){
        var dob = document.getElementById('edit-dob');
        var form = dob ? dob.closest('form') : null;
        if (dob) {
          var today = new Date();
          var minAge = 14;
          var maxAge = 90;
          function toISO(d){
            var y = d.getFullYear();
            var m = String(d.getMonth()+1).padStart(2,'0');
            var day = String(d.getDate()).padStart(2,'0');
            return y+'-'+m+'-'+day;
          }
          var maxDate = new Date(today.getFullYear()-minAge, today.getMonth(), today.getDate());
          var minDate = new Date(today.getFullYear()-maxAge, today.getMonth(), today.getDate());
          dob.setAttribute('max', toISO(maxDate));
          dob.setAttribute('min', toISO(minDate));
        }
        if (form) {
          form.addEventListener('submit', function(e){
            if (dob && dob.value) {
              try {
                var d = new Date(dob.value);
                var today = new Date();
                var age = today.getFullYear() - d.getFullYear();
                var m = today.getMonth() - d.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < d.getDate())) { age--; }
                if (age < 14 || age > 90) {
                  e.preventDefault();
                  alert('Age must be between 14 and 90 years.');
                  dob.focus();
                  return;
                }
              } catch(err) {}
            }
          });
        }
      })();
    </script>
  </main>
</body>

</html>

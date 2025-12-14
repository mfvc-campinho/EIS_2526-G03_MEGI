<?php
session_start();
require_once __DIR__ . '/../includes/flash.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Log In • GoodCollections</title>
  <link rel="stylesheet" href="../../CSS/general.css">
  <link rel="stylesheet" href="../../CSS/navbar.css">
  <link rel="stylesheet" href="../../CSS/forms.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
  <div class="page-container">
    <?php include __DIR__ . '/../includes/nav.php'; ?>
    <main class="page-main">
      <?php flash_render(); ?>
      <section class="form-section">
        <h1 class="form-title">Log In</h1>
        <p class="form-subtitle">Enter your credentials to access your profile.</p>
        <form class="gc-form" action="../auth.php" method="POST" novalidate>
          <input type="hidden" name="redirect" value="pages/user_page.php">
          <div class="form-row">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required placeholder="you@example.com">
          </div>
          <div class="form-row">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required placeholder="••••••••">
          </div>
          <div class="form-actions">
            <button type="submit" class="explore-btn success"><i class="bi bi-box-arrow-in-right"></i> Log In</button>
            <a href="user_create.php" class="explore-btn ghost"><i class="bi bi-person-plus"></i> Create Account</a>
          </div>
        </form>
      </section>
    </main>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
  </div>
</body>
</html>

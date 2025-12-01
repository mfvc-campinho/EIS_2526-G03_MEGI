<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <title><?= htmlspecialchars($pageTitle ?? 'GoodCollections') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

  <!-- 🔹 CSS -->
  <link rel="stylesheet" href="CSS/general.css" />
  <link rel="stylesheet" href="CSS/home_page.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="CSS/grid.css" />
  <link rel="stylesheet" href="CSS/likes.css" />
  <script src="JS/theme-toggle.js"></script>
</head>

<body>
  <div id="content">
    <!-- ===============================
    🔹 NAVIGATION BAR 
    =============================== -->
    <header>
      <nav class="navbar">
        <div class="nav-left">
          <a href="home_page.php" class="logo" aria-current="page">
            <i class="bi bi-collection me-2"></i> GoodCollections
          </a>
        </div>

        <div class="nav-right">
          <a href="all_collections.php" class="nav-link">
            <i class="bi bi-box-seam-fill me-1"></i> Collections
          </a>

          <a href="event_page.php" class="nav-link">
            <i class="bi bi-calendar-event-fill me-1"></i> Events
          </a>

          <a href="team_page.php" class="nav-link">
            <i class="bi bi-people-fill me-1"></i> About Us
          </a>

          <a href="user_page.php" class="nav-link">
            <i class="bi bi-person-fill me-1"></i> Profile
          </a>

          <!-- Dark mode toggle -->
          <button id="theme-toggle" class="toggle-pill" aria-pressed="false" aria-label="Toggle theme">
            <i class="bi bi-brightness-high-fill" aria-hidden="true"></i>
          </button>

          <!-- Login / Profile -->
          <?php if (!empty($_SESSION['user_id'])): ?>
            <div class="nav-item profile-inline">
              <a href="user_page.php" class="nav-link profile-link">
                <img src="<?php echo htmlspecialchars($_SESSION['user_photo'] ?? '../images/default_user.jpg'); ?>" alt="Profile" class="profile-photo" />
                <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>
              </a>
              <a href="../PHP_SIE/login_action.php?action=logout" class="nav-link">Log out</a>
            </div>
          <?php else: ?>
            <div class="dropdown profile-dropdown">
              <button class="dropbtn profile-btn">
                <i class="bi bi-person-circle me-1"></i> Log In ▾
              </button>
              <div class="dropdown-content profile-menu">
                <form class="login-inline-form" action="../PHP_SIE/login_action.php" method="post">
                  <input type="email" name="email" placeholder="Email" required />
                  <input type="password" name="password" placeholder="Password" required />
                  <button type="submit" class="explore-btn">Log in</button>
                </form>
                <a href="#" id="open-account-modal" class="nav-link" data-open-modal="add-account-modal">Create account</a>
              </div>
            </div>
          <?php endif; ?>

          <!-- Search -->
          <div class="search-wrapper">
            <i class="bi bi-search search-icon"></i>
            <input type="search" class="search-bar" placeholder="Search..." />
          </div>
        </div>
      </nav>
    </header>

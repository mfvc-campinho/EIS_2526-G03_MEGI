<?php
// public_html/includes/header.php

// Descobrir a página atual para marcar o link ativo
$currentPage = basename($_SERVER['PHP_SELF']);

function isActive(string $page, string $currentPage): string {
    return $page === $currentPage ? ' aria-current="page"' : '';
}
?>
<header>
  <nav class="navbar">
    <div class="nav-left">
      <a href="home_page.php" class="logo"<?php echo $currentPage === 'home_page.php' ? ' aria-current="page"' : ''; ?>>
        <i class="bi bi-collection me-2"></i> GoodCollections
      </a>
    </div>

    <div class="nav-right">
      <a href="all_collections.php" class="nav-link"<?php echo isActive('all_collections.php', $currentPage); ?>>
        <i class="bi bi-box-seam-fill me-1"></i> Collections
      </a>

      <a href="event_page.php" class="nav-link"<?php echo isActive('event_page.php', $currentPage); ?>>
        <i class="bi bi-calendar-event-fill me-1"></i> Events
      </a>

      <a href="team_page.php" class="nav-link"<?php echo isActive('team_page.php', $currentPage); ?>>
        <i class="bi bi-people-fill me-1"></i> About Us
      </a>

      <a href="user_page.php" class="nav-link"<?php echo isActive('user_page.php', $currentPage); ?>>
        <i class="bi bi-person-fill me-1"></i> Profile
      </a>

      <!-- Dark mode toggle -->
      <button id="theme-toggle" class="toggle-pill" aria-pressed="false" aria-label="Toggle theme">
        <i class="bi bi-brightness-high-fill" aria-hidden="true"></i>
      </button>

      <!-- Login dropdown (preenchido por JS / localStorage ou PHP no futuro) -->
      <div class="dropdown profile-dropdown">
        <button class="dropbtn profile-btn">
          <i class="bi bi-person-circle me-1"></i> Log In ▾
        </button>
        <div class="dropdown-content profile-menu"></div>
      </div>

      <!-- Search -->
      <div class="search-wrapper">
        <i class="bi bi-search search-icon"></i>
        <input type="search" class="search-bar" placeholder="Search...">
      </div>
    </div>
  </nav>
</header>

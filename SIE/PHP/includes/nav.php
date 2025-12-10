<?php
if (!isset($_SESSION))
    session_start();
$isAuth = !empty($_SESSION['user']);
$displayName = $isAuth ? ($_SESSION['user']['name'] ?? $_SESSION['user']['user_name'] ?? $_SESSION['user']['id'] ?? 'Profile') : 'Log In';
$current = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));

function nav_active($basename) {
    global $current;
    return $current === $basename ? ' active' : '';
}
?>
<header>
    <nav class="navbar">
        <div class="nav-left">
            <a href="home_page.php" class="logo" aria-current="page">
                <i class="bi bi-collection me-2"></i> GoodCollections
            </a>
        </div>

        <div class="nav-right">
            <a href="all_collections.php" class="nav-link<?php echo nav_active('all_collections.php'); ?>">
                <i class="bi bi-box-seam-fill me-1"></i> Collections
            </a>
            <a href="event_page.php" class="nav-link<?php echo nav_active('event_page.php'); ?>">
                <i class="bi bi-calendar-event-fill me-1"></i> Events
            </a>
            <a href="team_page.php" class="nav-link<?php echo nav_active('team_page.php'); ?>">
                <i class="bi bi-people-fill me-1"></i> About Us
            </a>
            <a href="user_page.php" class="nav-link<?php echo nav_active('user_page.php'); ?>">
                <i class="bi bi-person-fill me-1"></i> Profile
            </a>

            <!-- Dark mode toggle -->
            <button id="theme-toggle" class="toggle-pill" aria-pressed="false" aria-label="Toggle theme">
                <i class="bi bi-brightness-high-fill" aria-hidden="true"></i>
            </button>

            <!-- Login dropdown -->
            <div class="dropdown profile-dropdown">
                <button class="dropbtn profile-btn">
                    <i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($displayName); ?> &#9662;
                </button>
                <div class="dropdown-content profile-menu">
<?php if ($isAuth): ?>
                            <a href="user_page.php">See Profile</a>
                            <a href="../auth.php?action=logout&redirect=pages/home_page.php" class="logout-link">Sign Out</a>
<?php else: ?>
                        <form action="../auth.php" method="POST" class="login-form" style="padding:10px; min-width:220px;">
                            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'home_page.php'); ?>">

                            <label>Email:</label>
                            <input type="email" name="email" placeholder="Enter email" required>

                            <label>Password:</label>
                            <input type="password" name="password" placeholder="Enter password" required>

                            <div style="display:flex; gap:6px; margin-top:8px;">
                                <button type="submit" class="explore-btn" style="flex:1;">Log In</button>
                                <a href="user_create.php" class="explore-btn ghost" style="flex:1; text-align:center; display:inline-flex; align-items:center; justify-content:center;">
                                    Create account
                                </a>
                            </div>
                        </form>
<?php endif; ?>

                </div>
            </div>

            <!-- Search -->
            <div class="search-wrapper">
                <i class="bi bi-search search-icon"></i>
                <input type="search" class="search-bar" placeholder="Search...">
            </div>
        </div>
    </nav>
</header>

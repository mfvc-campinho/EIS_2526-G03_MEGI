<?php
session_start();
?>
<!DOCTYPE html>
<!--
  File: public_html/HTML/all_collections.php
  Purpose: Lists all collections and provides CRUD access (via modals) for logged-in users.
  Major sections: NAVIGATION, COLLECTION LIST, MODALS, FOOTER.
  Notes: Keep #collections-list present for app-collections.js rendering.
-->
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Collections — GoodCollections</title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Styles (CSS) -->
    <link rel="stylesheet" href="../CSS/general.css">
    <link rel="stylesheet" href="../CSS/likes.css">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Scripts -->
    <script src="../JS/theme-toggle.js"></script>
</head>

<body>
<div id="content">
    <!-- NAVIGATION -->
    <header>
        <nav class="navbar">
            <div class="nav-left">
                <a href="home_page.php" class="logo">
                    <i class="bi bi-collection me-2"></i> GoodCollections
                </a>
            </div>

            <div class="nav-right">
                <a href="all_collections.php" class="nav-link" aria-current="page">
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

                <!-- Login dropdown -->
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

    <!-- MAIN CONTENT -->
    <main>

        <!-- Breadcrumb (standardized) -->
        <nav class="breadcrumb-nav" aria-label="Breadcrumb">
            <ol class="breadcrumb-list">
                <li class="breadcrumb-item"><a href="home_page.php">Home</a></li>
                <li class="breadcrumb-item" aria-current="page">Collections</li>
            </ol>
        </nav>

        <h1 class="page-title">All Collections</h1>
        <p class="page-subtitle">Browse and manage all available collections.</p>

        <!-- Mensagens de sessão -->
        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php
                echo htmlspecialchars($_SESSION['error']);
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php
                echo htmlspecialchars($_SESSION['success']);
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <!-- CRUD buttons -->
        <div class="crud-controls collection-actions">
            <button id="open-collection-modal" class="explore-btn success" data-requires-login>
                <i class="bi bi-plus-circle me-1" aria-hidden="true"></i> Add Collection
            </button>
        </div>

        <!-- Filter -->
        <div class="filter-section" role="region" aria-label="Filter collections">
            <div class="filter-control">
                <label for="rankingFilter" class="filter-label">
                    <i class="bi bi-funnel me-1" aria-hidden="true"></i>Sort by
                </label>
                <div class="filter-input" data-filter-for="collections-list">
                    <select id="rankingFilter" class="filter-select">
                        <option value="lastAdded">Last Added</option>
                        <option value="userChosen">Chosen by User</option>
                        <option value="itemCount">Number of Items</option>
                    </select>
                    <button id="clearFilter" class="filter-clear" title="Reset filter">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Lists -->
        <div class="list-pagination" data-pagination-for="collections-list">
            <label class="page-size-label">
                Show
                <select data-page-size>
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="50">50</option>
                </select>
                collections per page
            </label>
            <div class="pagination-actions">
                <button type="button" class="explore-btn ghost icon-only" data-page-prev aria-label="Show previous results">
                    <i class="bi bi-chevron-left" aria-hidden="true"></i>
                </button>
                <span class="pagination-status" data-pagination-status>Showing 0-0 of 0</span>
                <button type="button" class="explore-btn ghost icon-only" data-page-next aria-label="Show next results">
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        <div id="collections-list-wrapper" role="region" aria-label="Collections list wrapper">
            <div class="collection-preview" id="collection-preview-area" style="display: none;">
                <div class="collection-preview-content">
                    <p id="collection-preview-summary"></p>
                    <div class="collection-preview-meta muted">
                        <span id="collection-preview-items" class="meta-item">
                            <i class="bi bi-list-ol me-1"></i>
                            <!-- JS will fill this -->
                        </span>
                        <span id="collection-preview-updated" class="meta-item">
                            <i class="bi bi-clock-history me-1"></i>
                            <!-- JS will fill this -->
                        </span>
                    </div>
                </div>
            </div>
            <div class="collection-container" id="collections-list"></div>
        </div>
    </main>

    <!-- FOOTER -->
    <footer>
        <div class="footer-content">
            <p>
                Made with <span class="heart">❤️</span> by
                <a href="https://github.com/mfvc-campinho/EIS_2526-G03_MEGI" target="_blank" rel="noopener noreferrer">
                    <i class="bi bi-github" aria-hidden="true"></i>
                    EIS_2526-G03_MEGI
                </a>
            </p>

            <div class="footer-links">
                <a href="home_page.php">Home Page</a>
                <a href="all_collections.php">Collections</a>
                <a href="event_page.php">Events</a>
                <a href="team_page.php">About Us</a>
                <a href="user_page.php">User Profile</a>
            </div>
        </div>
    </footer>
</div>

<!-- MODALS -->
<div id="collection-modal" class="modal">
    <div class="modal-content">
        <span class="close-btn" id="close-collection-modal">&times;</span>
        <h2 id="collection-modal-title">New Collection</h2>
        <form id="form-collection"
              method="post"
              action="../PHP/add_collection.php">
            <input type="hidden" id="collection-id" name="collection-id">

            <div class="form-section form-section--required">
                <h3>Required fields</h3>
                <label for="col-name">Name:</label>
                <input type="text" id="col-name" name="col-name" required>

                <label for="col-summary">Summary:</label>
                <textarea id="col-summary" name="col-summary" required></textarea>
            </div>

            <div class="form-section form-section--optional">
                <h3>Optional fields</h3>
                <label for="col-description">Full Description:</label>
                <textarea id="col-description" name="col-description"></textarea>

                <label for="col-image">Image (URL):</label>
                <input type="text" id="col-image" name="col-image" placeholder="../images/default.jpg">

                <label for="col-type">Type:</label>
                <input type="text" id="col-type" name="col-type">
            </div>
            <div class="modal-actions">
                <button type="submit" class="explore-btn success">
                    <i class="bi bi-save me-1"></i>Save
                </button>
                <button type="button" id="cancel-collection-modal" class="explore-btn danger delete-btn">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="forgot-password-modal" class="modal">
    <div class="modal-content">
        <span class="close-btn" id="close-forgot-modal">&times;</span>
        <h2>Forgot Password</h2>
        <form id="form-forgot-password"
              method="post"
              action="../PHP/forgot_password.php">
            <p>Enter your email address and we'll send you a link to reset your password.</p>
            <label for="forgot-email">Email:</label>
            <input type="email" id="forgot-email" name="forgot-email" required>
            <div class="modal-actions">
                <button type="submit" class="save-btn">Send Reset Link</button>
            </div>
        </form>
    </div>
</div>

<div id="add-account-modal" class="modal">
    <div class="modal-content">
        <span class="close-btn" id="close-account-modal">&times;</span>
        <h2>Create New Account</h2>

        <form id="form-add-account"
              method="post"
              action="../PHP/register.php">

            <label for="acc-name">Username:</label>
            <input type="text" id="acc-name" name="acc-name" required>

            <label for="acc-owner-photo">Photo URL:</label>
            <input type="url" id="acc-owner-photo" name="acc-owner-photo" placeholder="https://example.com/photo.jpg">

            <label for="acc-dob">Date of Birth:</label>
            <input type="date" id="acc-dob" name="acc-dob">

            <label for="acc-member-since">Member Since (YYYY):</label>
            <input type="number" id="acc-member-since" name="acc-member-since" min="1900" max="2100" step="1"
                   placeholder="2020" readonly>

            <label for="acc-email">Email:</label>
            <input type="email" id="acc-email" name="acc-email" required>

            <label for="acc-password">Password:</label>
            <input type="password" id="acc-password" name="acc-password" required>

            <label for="acc-password-confirm">Confirm Password:</label>
            <input type="password" id="acc-password-confirm" name="acc-password-confirm" required>

            <div class="modal-actions">
                <button type="submit" class="save-btn">Create Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Scripts -->
<script src="../JS/app-data.js"></script>
<script src="../JS/app-users.js"></script>
<script src="../JS/app-collections.js"></script>
<script src="../JS/app-loader.js"></script>
<script src="../JS/search-toggle.js"></script>
</body>

</html>

<?php
session_start();
?>
<!DOCTYPE html>
<!--
  File: public_html/HTML/home_page.php
  Purpose: Site homepage — displays welcome hero, top collections, and feature highlights.
  Major sections: NAVIGATION (via include), WELCOME HERO, RANKING (Top 5), FEATURES, FOOTER (via include).
-->
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>HomePage — GoodCollections</title>

  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

  <!-- Styles (CSS) -->
  <link rel="stylesheet" href="../CSS/general.css">
  <link rel="stylesheet" href="../CSS/home_page.css">

  <!-- Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <!-- Scripts (que precisam de ir no head) -->
  <script src="../JS/theme-toggle.js"></script>
</head>

<body>
  <div id="content">
    <!-- NAVIGATION (include existente) -->
    <?php include __DIR__ . '../includes/header.php'; ?>

    <!-- MAIN CONTENT -->
    <main>

      <!-- Breadcrumb (standardized) -->
      <nav class="breadcrumb-nav" aria-label="Breadcrumb">
        <ol class="breadcrumb-list">
          <li class="breadcrumb-item" aria-current="page">Home</li>
        </ol>
      </nav>

      <!-- WELCOME HERO -->
      <section class="welcome-hero">
        <div class="welcome-inner">
          <div class="welcome-text">
            <h2 class="welcome-title">
              <i class="bi bi-stars me-2" aria-hidden="true"></i>
              Welcome to GoodCollections
            </h2>
            <p class="welcome-subtitle">
              Manage all your collections effortlessly — from books and vinyl records to coins, stamps, and miniatures.
            </p>
          </div>

          <!-- Small Stats -->
          <div class="welcome-stats">
            <div class="stat">
              <span class="stat-label">Manage</span>
              <span class="stat-value">Multiple Collections</span>
            </div>
            <div class="stat">
              <span class="stat-label">Track</span>
              <span class="stat-value">Items &amp; Events</span>
            </div>
            <div class="stat">
              <span class="stat-label">Focus</span>
              <span class="stat-value">Collectors Experience</span>
            </div>
          </div>
        </div>

        <!-- Global Actions Buttons -->
        <div class="collection-actions">
          <button id="open-collection-modal" class="explore-btn success" data-requires-login>
            <i class="bi bi-plus-circle me-1" aria-hidden="true"></i> New Collection
          </button>

          <button id="restoreDataBtn" class="explore-btn warning"
                  title="Deletes all current data and restores the original data from Data.js">
            <i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i> Restore Data
          </button>
        </div>
      </section>

      <!-- RANKING SECTION -->

      <h1 class="page-title">Top 5 Collections</h1>
      <p class="page-subtitle">Explore the most popular and recently added collections curated by our community.</p>

      <!-- Filter -->
      <section class="filter-section">
        <h2 class="sr-only">Collection filters</h2>
        <div class="filter-control">
          <label for="rankingFilter" class="filter-label">
            <i class="bi bi-funnel me-1" aria-hidden="true"></i>Sort by
          </label>
          <div class="filter-input" data-filter-for="homeCollections">
            <select id="rankingFilter" class="filter-select">
              <option value="lastAdded">Last Added</option>
              <option value="userChosen">Chosen by User</option>
              <option value="itemCount">Number of Items</option>
            </select>
          </div>
        </div>
      </section>

      <!-- Collections Container -->
      <section class="ranking-section">
        <h2 class="sr-only">Collection ranking controls</h2>
        <!-- Hidden pagination control to enforce a page size of 5 for the script -->
        <div class="list-pagination" data-pagination-for="homeCollections" style="display: none;">
          <select data-page-size>
            <option value="5" selected>5</option>
          </select>
        </div>
        <div class="collection-container" id="homeCollections" data-limit="5"></div>
      </section>

    </main>

    <!-- UPCOMING EVENTS -->
    <section class="upcoming-events">
      <div class="upcoming-inner">
        <h2 class="upcoming-title">
          <i class="bi bi-calendar-event-fill me-2" aria-hidden="true"></i>
          Upcoming Events
        </h2>

        <p class="upcoming-sub">
          Don't miss the next exhibitions, fairs and meetups curated by our community. Click an
          event to see details.
        </p>

        <div class="events-grid" id="upcomingEvents">
          <!-- Event cards will be populated here by JS  -->
        </div>

        <div class="events-actions">
          <a href="event_page.php" class="explore-btn ghost">View Events</a>
        </div>
      </div>
    </section>

    <!-- FEATURES HERO -->
    <section class="features-hero">
      <div class="features-inner">
        <h2 class="features-title">Everything You Need to <span class="accent">Manage Your Collections</span></h2>
        <p class="features-sub">
          From automotive miniatures to rare stamps, GoodCollections gives you the tools to
          catalog, organize, and showcase every item with precision.
        </p>

        <div class="features-grid">
          <article class="feature-card">
            <div class="feature-icon"><i class="bi bi-folder me-1" aria-hidden="true"></i></div>
            <h3>Multiple Collections</h3>
            <p>Create unlimited collections for different item types. Keep coins separate from trading cards, all in
              one place.</p>
          </article>

          <article class="feature-card">
            <div class="feature-icon"><i class="bi bi-info-circle me-1" aria-hidden="true"></i></div>
            <h3>Detailed Item Tracking</h3>
            <p>Record every detail — name, importance rating, acquisition date, weight, and price. Never forget what
              you paid or when you got it.</p>
          </article>

          <article class="feature-card">
            <div class="feature-icon"><i class="bi bi-calendar-event me-1" aria-hidden="true"></i></div>
            <h3>Event Management</h3>
            <p>Track exhibitions and collector events. Add descriptions, dates, and rate your experience after
              attending each event.</p>
          </article>

          <article class="feature-card">
            <div class="feature-icon"><i class="bi bi-pencil-square me-1" aria-hidden="true"></i></div>
            <h3>Easy Editing</h3>
            <p>Update collection details, add or remove items, and modify information anytime. Your catalog evolves
              with your collection.</p>
          </article>

          <article class="feature-card">
            <div class="feature-icon"><i class="bi bi-eye me-1" aria-hidden="true"></i></div>
            <h3>Intuitive Interface</h3>
            <p>Clean, modern design that's easy to navigate. View your top collections at a glance on the homepage.
            </p>
          </article>

          <article class="feature-card">
            <div class="feature-icon"><i class="bi bi-person-circle me-1" aria-hidden="true"></i></div>
            <h3>Personal Profile</h3>
            <p>Your collector profile keeps track of your personal information and preferences in one central
              location.</p>
          </article>
        </div>
      </div>
    </section>

    <!-- FOOTER (include existente) -->
    <?php include __DIR__ . '../includes/footer.php'; ?>
  </div>

  <!-- MODALS -->
  <div id="collection-modal" class="modal">
    <div class="modal-content">
      <span class="close-btn" id="close-collection-modal">&times;</span>
      <h2 id="collection-modal-title">New Collection</h2>
      <form id="form-collection"
            method="post"
            action="../PHP/add_collection.php">
        <input type="hidden" id="collection-id" name="collection_id">

        <div class="form-section form-section--required">
          <h3>Required fields</h3>
          <label for="col-name">Name:</label>
          <input type="text" id="col-name" name="col_name" required>

          <label for="col-summary">Summary:</label>
          <textarea id="col-summary" name="col_summary" required></textarea>
        </div>

        <div class="form-section form-section--optional">
          <h3>Optional fields</h3>
          <label for="col-description">Full Description:</label>
          <textarea id="col-description" name="col_description"></textarea>

          <label for="col-image">Image (URL):</label>
          <input type="text" id="col-image" name="col_image" placeholder="../images/default.jpg">

          <label for="col-type">Type:</label>
          <input type="text" id="col-type" name="col_type">
        </div>
        <div class="modal-actions">
          <button type="submit" class="explore-btn success">
            <i class="bi bi-save"></i> Save
          </button>
          <button type="button" id="cancel-collection-modal" class="explore-btn danger">Cancel</button>
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
        <input type="email" id="forgot-email" name="email" required>
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
        <input type="text" id="acc-name" name="username" required>

        <label for="acc-owner-photo">Photo URL:</label>
        <input type="url" id="acc-owner-photo" name="photo_url" placeholder="https://example.com/photo.jpg">

        <label for="acc-dob">Date of Birth:</label>
        <input type="date" id="acc-dob" name="dob">

        <label for="acc-member-since">Member Since (YYYY):</label>
        <input type="number" id="acc-member-since" name="member_since" min="1900" max="2100" step="1"
               placeholder="2024" readonly>

        <label for="acc-email">Email:</label>
        <input type="email" id="acc-email" name="email" required>

        <label for="acc-password">Password:</label>
        <input type="password" id="acc-password" name="password" required>

        <label for="acc-password-confirm">Confirm Password:</label>
        <input type="password" id="acc-password-confirm" name="password_confirm" required>

        <div class="modal-actions">
          <button type="submit" class="save-btn">Create Account</button>
        </div>
      </form>
    </div>
  </div>

  <!-- SCRIPTS -->
  <script src="../JS/Data.js"></script>
  <script src="../JS/app-data.js"></script>
  <script src="../JS/app-users.js"></script>
  <script src="../JS/app-collections.js"></script>
  <script src="../JS/app-events.js"></script>
  <script src="../JS/app-loader.js"></script>
  <script src="../JS/app-upcoming.js"></script>
  <script src="../JS/features.js"></script>
  <script src="../JS/search-toggle.js"></script>

</body>
</html>

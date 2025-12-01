<?php
session_start();
?>
<!DOCTYPE html>
<!--
  File: public_html/HTML/user_page.php
  Purpose: User profile page — shows user info, featured collections, liked collections and user events.
  Major sections: NAVIGATION (via header include), PROFILE OVERVIEW, USER SHOWCASE, LIKED COLLECTIONS, USER EVENTS.
  Notes: Elements with IDs like #user-top-picks, #user-collections and #user-events are used by app-userpage.js.
-->
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>User Profile — GoodCollections</title>

  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

  <!-- Styles (CSS) -->
  <link rel="stylesheet" href="../CSS/general.css">
  <link rel="stylesheet" href="../CSS/user_page.css">
  <link rel="stylesheet" href="../CSS/likes.css">

  <!-- Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <!-- Scripts -->
  <script src="../JS/theme-toggle.js"></script>
</head>

<body>
  <div id="content">
    <!-- NAVIGATION -->
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <!-- MAIN CONTENT -->
    <main>

      <!-- Breadcrumb (standardized) -->
      <nav class="breadcrumb-nav" aria-label="Breadcrumb">
        <ol class="breadcrumb-list">
          <li class="breadcrumb-item"><a href="home_page.php">Home</a></li>
          <li class="breadcrumb-item" aria-current="page">User Profile</li>
        </ol>
      </nav>

      <!-- PROFILE OVERVIEW -->
      <h1 id="user-page-title" class="page-title">User Profile</h1>

      <section class="profile-hero">
        <div class="profile-card">
          <!-- Top row: avatar + welcome + name -->
          <div class="profile-top">
            <img id="user-avatar" class="avatar" src="../images/user.jpg" alt="User avatar" width="88" height="88">
            <div class="profile-heading">
              <h2 id="user-name">Collector</h2>
              <p class="user-sub">
                This is your personal space to manage collections, items, and events.
              </p>
            </div>
          </div>

          <!-- Middle row: key user info (JS can fill these spans) -->
          <div class="profile-info-grid">
            <div class="info-block">
              <span class="info-label">Email</span>
              <span class="info-value" id="user-email">—</span>
            </div>
            <div class="info-block">
              <span class="info-label">Date of Birth</span>
              <span class="info-value" id="user-dob">—</span>
            </div>
            <div class="info-block">
              <span class="info-label">Member Since</span>
              <span class="info-value" id="user-member-since">2025</span>
            </div>
            <div class="info-block">
              <span class="info-label">Collections</span>
              <span class="info-value" id="user-collection-count">0</span>
            </div>
            <div class="info-block">
              <span class="info-label">Followers</span>
              <span class="info-value" id="user-followers">0</span>
            </div>
          </div>

          <!-- Actions -->
          <div class="user-actions collection-actions">
            <button id="edit-profile-btn" class="explore-btn warning" data-requires-login>
              <i class="bi bi-pencil-square-square me-1" aria-hidden="true"></i>
              Edit Profile
            </button>

            <button id="open-collection-modal" class="explore-btn success" data-requires-login>
              <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>
              Add Collection
            </button>

            <button id="follow-user-btn" class="explore-btn success" type="button" data-requires-login hidden>
              <i class="bi bi-person-plus me-1" aria-hidden="true"></i>
              <span class="follow-label">Follow</span>
            </button>
          </div>
        </div>
      </section>

      <!-- USER SHOWCASE -->
      <section class="user-top-picks">
        <div class="section-header">
          <h2 class="section-title">Featured Collections</h2>
          <button type="button" class="mini-reset-btn" id="reset-top-picks-btn">
            Show default order
          </button>
        </div>
        <p class="section-note" id="top-picks-note"></p>
        <div id="user-top-picks" class="top-picks-grid">
          <p class="notice-message">No curated collections yet.</p>
        </div>
      </section>

      <!-- LIKED COLLECTIONS -->
      <h2 id="user-liked-title">Liked Collections</h2>
      <div class="list-pagination" data-pagination-for="user-liked-collections">
        <label class="page-size-label">
          See
          <select data-page-size="">
            <option value="5">5</option>
            <option value="10" selected>10</option>
            <option value="50">50</option>
          </select>
          collections at the same time
        </label>
        <div class="pagination-actions">
          <button type="button" class="explore-btn ghost icon-only disabled" data-page-prev
                  aria-label="Ver anteriores" disabled aria-disabled="true">
            <i class="bi bi-chevron-left" aria-hidden="true"></i>
          </button>
          <span class="pagination-status" data-pagination-status>Showing 0-0 of 0</span>
          <button type="button" class="explore-btn ghost icon-only disabled" data-page-next
                  aria-label="Ver seguintes" disabled aria-disabled="true">
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
          </button>
        </div>
      </div>
      <section id="user-liked-collections" class="liked-collections" aria-labelledby="user-liked-title">
        <p class="notice-message">Sign in to see the collections you've starred.</p>
      </section>

      <!-- LIKED ITEMS -->
      <h2 id="user-liked-items-title">Liked Items</h2>
      <div class="list-pagination" data-pagination-for="user-liked-items">
        <label class="page-size-label">
          See
          <select data-page-size="">
            <option value="5">5</option>
            <option value="10" selected>10</option>
            <option value="50">50</option>
          </select>
          collections at the same time
        </label>
        <div class="pagination-actions">
          <button type="button" class="explore-btn ghost icon-only disabled" data-page-prev
                  aria-label="Ver anteriores" disabled aria-disabled="true">
            <i class="bi bi-chevron-left" aria-hidden="true"></i>
          </button>
          <span class="pagination-status" data-pagination-status>Showing 0-0 of 0</span>
          <button type="button" class="explore-btn ghost icon-only disabled" data-page-next
                  aria-label="Ver seguintes" disabled aria-disabled="true">
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
          </button>
        </div>
      </div>
      <section id="user-liked-items" class="liked-items collection-container"
               data-item-source="liked" data-owner-context="true"
               aria-labelledby="user-liked-items-title">
        <p class="notice-message">You haven't liked any items yet.</p>
      </section>

      <!-- LIKED EVENTS -->
      <h2 id="user-liked-events-title">Liked Events</h2>
      <div class="list-pagination" data-pagination-for="user-liked-events">
        <label class="page-size-label">
          See
          <select data-page-size="">
            <option value="5">5</option>
            <option value="10" selected>10</option>
            <option value="50">50</option>
          </select>
          collections at the same time
        </label>
        <div class="pagination-actions">
          <button type="button" class="explore-btn ghost icon-only disabled" data-page-prev
                  aria-label="Ver anteriores" disabled aria-disabled="true">
            <i class="bi bi-chevron-left" aria-hidden="true"></i>
          </button>
          <span class="pagination-status" data-pagination-status>Showing 0-0 of 0</span>
          <button type="button" class="explore-btn ghost icon-only disabled" data-page-next
                  aria-label="Ver seguintes" disabled aria-disabled="true">
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
          </button>
        </div>
      </div>
      <section id="user-liked-events" class="liked-events collection-container"
               aria-labelledby="user-liked-events-title">
        <p class="notice-message">You haven't liked any events yet.</p>
      </section>

      <!-- USER COLLECTIONS -->
      <h2 id="my-collections-title">Collections</h2>
      <div class="list-pagination" data-pagination-for="user-collections">
        <label class="page-size-label">
          See
          <select data-page-size="">
            <option value="5">5</option>
            <option value="10" selected>10</option>
            <option value="50">50</option>
          </select>
          collections at the same time
        </label>
        <div class="pagination-actions">
          <button type="button" class="explore-btn ghost icon-only disabled" data-page-prev
                  aria-label="Ver anteriores" disabled aria-disabled="true">
            <i class="bi bi-chevron-left" aria-hidden="true"></i>
          </button>
          <span class="pagination-status" data-pagination-status>Showing 0-0 of 0</span>
          <button type="button" class="explore-btn ghost icon-only disabled" data-page-next
                  aria-label="Ver seguintes" disabled aria-disabled="true">
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
          </button>
        </div>
      </div>
      <section id="user-collections" class="collection-container" aria-labelledby="my-collections-title"></section>

      <!-- USER EVENTS -->
      <h2 id="user-events-title">Collection Events</h2>
      <div class="list-pagination" data-pagination-for="user-events">
        <label class="page-size-label">
          See
          <select data-page-size="">
            <option value="5">5</option>
            <option value="10" selected>10</option>
            <option value="50">50</option>
          </select>
          collections at the same time
        </label>
        <div class="pagination-actions">
          <button type="button" class="explore-btn ghost icon-only disabled" data-page-prev
                  aria-label="Ver anteriores" disabled aria-disabled="true">
            <i class="bi bi-chevron-left" aria-hidden="true"></i>
          </button>
          <span class="pagination-status" data-pagination-status>Showing 0-0 of 0</span>
          <button type="button" class="explore-btn ghost icon-only disabled" data-page-next
                  aria-label="Ver seguintes" disabled aria-disabled="true">
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
          </button>
        </div>
      </div>
      <section id="user-events" class="user-events" aria-labelledby="user-events-title">
        <p class="notice-message">Loading events...</p>
      </section>

      <!-- USER RSVPs -->
      <h2 id="user-rsvp-title">Event RSVPs</h2>
      <div class="list-pagination" data-pagination-for="user-rsvp-events">
        <label class="page-size-label">
          See
          <select data-page-size="">
            <option value="5">5</option>
            <option value="10" selected>10</option>
            <option value="50">50</option>
          </select>
          collections at the same time
        </label>
        <div class="pagination-actions">
          <button type="button" class="explore-btn ghost icon-only disabled" data-page-prev
                  aria-label="Ver anteriores" disabled aria-disabled="true">
            <i class="bi bi-chevron-left" aria-hidden="true"></i>
          </button>
          <span class="pagination-status" data-pagination-status>Showing 0-0 of 0</span>
          <button type="button" class="explore-btn ghost icon-only disabled" data-page-next
                  aria-label="Ver seguintes" disabled aria-disabled="true">
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
          </button>
        </div>
      </div>
      <section id="user-rsvp-events" class="user-events" aria-labelledby="user-rsvp-title">
        <p class="notice-message">Loading RSVP events...</p>
      </section>
    </main>
  </div>

  <!-- MODALS -->

  <!-- COLLECTION MODAL (usa handler comum add_collection.php) -->
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

  <!-- MODAL: EDIT USER PROFILE (por agora pode ser tratado em JS; se quiseres backend, apontamos para um PHP) -->
  <div id="user-profile-modal" class="modal">
    <div class="modal-content">
      <span class="close-btn" id="close-user-modal">&times;</span>
      <h2>Edit Profile</h2>
      <form id="form-user-profile">
        <label for="user-form-name">Username:</label>
        <input type="text" id="user-form-name" name="user-form-name" required>

        <label for="user-form-email">Email:</label>
        <input type="email" id="user-form-email" name="user-form-email" required>

        <label for="user-form-dob">Date of Birth:</label>
        <input type="date" id="user-form-dob" name="user-form-dob">

        <label for="user-form-photo">Photo URL:</label>
        <input type="text" id="user-form-photo" name="user-form-photo">

        <div class="modal-actions">
          <button type="submit" class="explore-btn success">
            <i class="bi bi-save me-1"></i>Save Changes
          </button>
          <button type="button" id="cancel-user-modal" class="explore-btn danger delete-btn">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <!-- FORGOT PASSWORD MODAL (handler comum forgot_password.php) -->
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

  <!-- CREATE ACCOUNT MODAL (handler comum register.php) -->
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

  <!-- FOOTER -->
  <?php include __DIR__ . '/../includes/footer.php'; ?>

  <!-- SCRIPTS -->
  <script src="../JS/Data.js"></script>
  <script src="../JS/app-data.js"></script>
  <script src="../JS/app-users.js"></script>
  <script src="../JS/app-collections.js"></script>
  <script src="../JS/app-userpage.js"></script>
  <script src="../JS/app-loader.js"></script>
  <script src="../JS/search-toggle.js"></script>
</body>

</html>

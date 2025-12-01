    <!-- ===============================
         🔹 FOOTER
    =============================== -->
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

    <!-- ===============================
     🔹 MODALS
  =============================== -->
    <div id="collection-modal" class="modal">
      <div class="modal-content">
        <span class="close-btn" id="close-collection-modal">&times;</span>
        <h2 id="collection-modal-title">New Collection</h2>
        <form id="form-collection" action="handle_collection.php" method="post">
          <input type="hidden" id="collection-id" name="collection-id" />

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
            <button type="submit" class="explore-btn success"> <i class="bi bi-save"></i> Save</button>
            <button type="button" id="cancel-collection-modal" class="explore-btn danger">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <div id="forgot-password-modal" class="modal">
      <div class="modal-content">
        <span class="close-btn" id="close-forgot-modal">&times;</span>
        <h2>Forgot Password</h2>
        <form id="form-forgot-password" action="handle_forgot_password.php" method="post">
          <p>Enter your email address and we'll send you a link to reset your password.</p>
          <label for="forgot-email">Email:</label>
          <input type="email" id="forgot-email" name="forgot-email" required />
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

        <form id="form-add-account" action="handle_add_account.php" method="post">
          <label for="acc-name">Username:</label>
          <input type="text" id="acc-name" name="acc-name" required />
          <label for="acc-owner-photo">Photo URL:</label>
          <input type="url" id="acc-owner-photo" name="acc-owner-photo" placeholder="https://example.com/photo.jpg" />
          <label for="acc-dob">Date of Birth:</label>
          <input type="date" id="acc-dob" name="acc-dob" />
          <label for="acc-member-since">Member Since (YYYY):</label>
          <input type="number" id="acc-member-since" name="acc-member-since" min="1900" max="2100" step="1"
            placeholder="2020" readonly />
          <label for="acc-email">Email:</label>
          <input type="email" id="acc-email" name="acc-email" required />
          <label for="acc-password">Password:</label>
          <input type="password" id="acc-password" name="acc-password" required />
          <label for="acc-password-confirm">Confirm Password:</label>
          <input type="password" id="acc-password-confirm" name="acc-password-confirm" required />
          <div class="modal-actions">
            <button type="submit" class="save-btn">Create Account</button>
          </div>
        </form>
      </div>
    </div>

    <!-- ===============================
    🔹 SCRIPTS (required order)
  =============================== -->
    <script src="JS/Data.js"></script>
    <script src="JS/app-data.js"></script>
    <script src="JS/app-users.js"></script>
    <script src="JS/app-collections.js"></script>
    <script src="JS/app-events.js"></script>

    <!-- 🔹 Loader script -->
    <script src="JS/app-loader.js"></script>
    <script src="JS/app-upcoming.js"></script>

    <!-- Script: trigger features entrance animation when in viewport -->
    <script src="JS/features.js"></script>
    <script src="JS/search-toggle.js"></script>

    </body>

    </html>
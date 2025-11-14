// ===============================================
// app-users.js — UI helper for user/profile interactions
// - Renders profile dropdown depending on login state
// - Emits global userStateChange events
// - Handles login/logout, forgot-password and account creation modals (simulated)
// ===============================================
document.addEventListener("DOMContentLoaded", () => {
  let currentUser = JSON.parse(localStorage.getItem("currentUser"));
  const profileMenu = document.querySelector(".profile-dropdown .dropdown-content");
  const profileButton = document.querySelector(".profile-btn");
  const profileDropdown = document.querySelector(".profile-dropdown");
  let dropdownToggleReady = false;

  // =======================================================
  // 1. Render profile menu based on current user state
  // - Updates dropdown content for authenticated or anonymous users
  // - Attaches event handlers for embedded forms and links
  // =======================================================
  function renderProfileMenu() {
    if (!profileMenu) return;
    profileMenu.innerHTML = "";
    profileDropdown?.classList.remove("open");

    if (currentUser && currentUser.active) {
      // Authenticated user
      const displayName = currentUser.ownerName || currentUser.id || "Profile";
      profileButton.innerHTML = `<i class="bi bi-person"></i> ${displayName} ▾`;
      profileMenu.innerHTML = `
        <a href="user_page.html">See Profile</a>
        <a href="#" id="signout-btn">Sign Out</a>
      `;
    } else {
      // Not authenticated -> render inline login form
      profileButton.innerHTML = `<i class="bi bi-person"></i> Log In ▾`;
      profileMenu.innerHTML = `
        <form id="login-form" class="login-form">
          <label>Username:</label>
          <input type="text" id="login-user" placeholder="Enter username">
          <label>Password:</label>
          <input type="password" id="login-pass" placeholder="Enter password">
          <button type="submit" class="login-btn">Enter</button>
        </form>
        <div class="profile-links">
          <a href="#" id="forgot-password-btn">Forgot Password?</a>
          <a href="#" id="add-account-btn">Create New Account</a>
        </div>
      `;
    }

    // Wire up interactions after inserting the menu content
    attachEvents();
    setupDropdownToggle();
    profileMenu.onclick = (e) => e.stopPropagation();
  }

  // Setup the small dropdown toggle behavior (single init)
  function setupDropdownToggle() {
    if (dropdownToggleReady || !profileButton) return;
    dropdownToggleReady = true;

    profileButton.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      profileDropdown?.classList.toggle("open");
    });

    document.addEventListener("click", () => {
      profileDropdown?.classList.remove("open");
    });
  }

  // =======================================================
  // 2. Simulated login (demo only)
  // - For the demo we always use a fixed owner id and mark currentUser as active
  // =======================================================
  function getOwnerProfile(ownerId) {
    if (!ownerId) return null;
    let data = null;
    try {
      if (window.appData?.loadData) {
        data = window.appData.loadData();
      }
    } catch (err) {
      console.warn("Could not load appData during login:", err);
    }
    if (!data && typeof collectionsData !== "undefined") {
      data = collectionsData;
    }
    return data?.users?.find(user => user["owner-id"] === ownerId) || null;
  }

  function loginUser() {
    const ownerId = "collector-main";
    const profile = getOwnerProfile(ownerId);
    currentUser = {
      id: ownerId,
      ownerName: profile?.["owner-name"] || ownerId,
      active: true
    };
    localStorage.setItem("currentUser", JSON.stringify(currentUser));
    notifyUserStateChange();
    renderProfileMenu();
  }

  // =======================================================
  // 3. Logout (simulated)
  // =======================================================
  function logoutUser() {
    if (confirm("Sign out?")) {
      localStorage.setItem("currentUser", JSON.stringify({ active: false }));
      currentUser = null;
      notifyUserStateChange();
      renderProfileMenu();
    }
  }

  // =======================================================
  // 4. Global notification helper
  // - Emits a `userStateChange` event with updated user data
  // =======================================================
  function notifyUserStateChange() {
    const event = new CustomEvent("userStateChange", {
      detail: JSON.parse(localStorage.getItem("currentUser"))
    });
    console.log("userStateChange emitted:", event.detail);
    window.dispatchEvent(event);
  }

  // =======================================================
  // 5. Dynamic event bindings for menu forms and links
  // =======================================================
  function attachEvents() {
    const form = document.getElementById("login-form");
    const signoutBtn = document.getElementById("signout-btn");

    // Links that open demo modals
    const forgotPasswordBtn = document.getElementById("forgot-password-btn");
    const addAccountBtn = document.getElementById("add-account-btn");

    if (form) {
      form.addEventListener("submit", (e) => {
        e.preventDefault();
        const usernameInput = document.getElementById("login-user");
        const passwordInput = document.getElementById("login-pass");
        const username = usernameInput?.value?.trim() || "";
        const password = passwordInput?.value?.trim() || "";
        const hasLetter = (value) => /[A-Za-z]/.test(value);

        if (!hasLetter(username) || !hasLetter(password)) {
          alert("Please enter a username and password (at least one letter each).");
          return;
        }

        loginUser();
      });
    }

    if (signoutBtn) {
      signoutBtn.addEventListener("click", (e) => {
        e.preventDefault();
        logoutUser();
      });
    }

    // Forgot-password modal behavior (demo)
    if (forgotPasswordBtn) {
      const forgotModal = document.getElementById("forgot-password-modal");
      const closeForgot = document.getElementById("close-forgot-modal");
      forgotPasswordBtn.addEventListener("click", (e) => {
        e.preventDefault();
        if (forgotModal) forgotModal.style.display = "flex";
      });
      closeForgot?.addEventListener("click", () => forgotModal.style.display = "none");
      document.getElementById("form-forgot-password")?.addEventListener("submit", (e) => {
        e.preventDefault();
        alert("✅ Password reset link sent!\n\n(This is a simulation. No data was saved.)");
        forgotModal.style.display = "none";
      });
    }

    // Create-account modal behavior (demo)
    if (addAccountBtn) {
      const accountModal = document.getElementById("add-account-modal");
      const closeAccount = document.getElementById("close-account-modal");
      const accountForm = document.getElementById("form-add-account");
      addAccountBtn.addEventListener("click", (e) => {
        e.preventDefault();
        if (accountModal) {
          accountModal.style.display = "flex";
        }
      });
      closeAccount?.addEventListener("click", () => {
        if (accountModal) {
          accountModal.style.display = "none";
        }
      });
      accountForm?.addEventListener("submit", (e) => {
        e.preventDefault();
        const getValue = (id) => document.getElementById(id)?.value?.trim() || "";
        const ownerId = getValue("acc-owner-id");
        const ownerName = getValue("acc-name");
        const email = getValue("acc-email");
        if (!ownerId) {
          alert("Please provide a collector ID.");
          return;
        }
        if (!ownerName) {
          alert("Please provide a username.");
          return;
        }
        if (!email) {
          alert("Please provide an email.");
          return;
        }
        const password = document.getElementById("acc-password")?.value || "";
        const confirmPassword = document.getElementById("acc-password-confirm")?.value || "";
        if (password !== confirmPassword) {
          alert("Passwords do not match!");
          return;
        }
        const ownerPhoto = document.getElementById("acc-owner-photo")?.value?.trim() || "";
        const dateOfBirth = document.getElementById("acc-dob")?.value || "";
        const memberSince = document.getElementById("acc-member-since")?.value?.trim() || "";

        const summaryLines = [
          `Collector ID: ${ownerId}`,
          `Name: ${ownerName}`,
          ownerPhoto ? `Photo URL: ${ownerPhoto}` : "",
          dateOfBirth ? `Date of Birth: ${dateOfBirth}` : "",
          memberSince ? `Member Since: ${memberSince}` : "",
          `Email: ${email}`,
        ].filter(Boolean);

        alert(`✅ Simulation successful. Account details:\n${summaryLines.join("\n")}\n\n(This is a simulation. No data was saved.)`);
        if (accountModal) {
          accountModal.style.display = "none";
        }
        accountForm.reset();
      });
    }

    // Quick search-bar placeholder handler (demo)
    const searchBar = document.querySelector(".search-bar");
    if (searchBar) {
      searchBar.addEventListener("keydown", (e) => {
        if (e.key === "Enter") {
          e.preventDefault(); // Prevent default Enter behavior
          alert("Search functionality is under construction!");
        }
      });
    }
  }

  // =======================================================
  // 6. Initialization
  // =======================================================
  renderProfileMenu();
  window.appUsers = { currentUser };

  // Emit initial userStateChange so other scripts can react
  const initialEvent = new CustomEvent("userStateChange", { detail: currentUser });
  window.dispatchEvent(initialEvent);
});

// =======================================================
// 7. Global listener for userStateChange (updates UI across pages)
// =======================================================
window.addEventListener("userStateChange", (e) => {
  const user = e.detail;
  const isActiveUser = user && user.active;

  console.log("userStateChange received:", user);

  // Show/hide elements that require a logged-in user
  document.querySelectorAll("[data-requires-login]").forEach(btn => {
    btn.style.display = isActiveUser ? "inline-block" : "none";
  });
});

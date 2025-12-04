// ===============================================
// app-users.js — UI helper for user/profile interactions
// - Renders profile dropdown depending on login state
// - Emits global userStateChange events
// - Handles login/logout, forgot-password and account creation modals (simulated)
// ===============================================
document.addEventListener("DOMContentLoaded", () => {
  let currentUser = JSON.parse(localStorage.getItem("currentUser"));
  // Use PHP-bootstrapped session data when available instead of fetching via JS
  try {
    if (!currentUser && window.SERVER_AUTH_USER) {
      const sess = window.SERVER_AUTH_USER;
      const normalized = sess
        ? { id: sess.id || sess.user_id, ownerName: sess.name || sess.user_name, active: true }
        : null;
      if (normalized?.id) {
        localStorage.setItem('currentUser', JSON.stringify(normalized));
        currentUser = normalized;
        window.dispatchEvent(new CustomEvent('userStateChange', { detail: currentUser }));
      }
    }
  } catch (err) {
    // ignore errors (e.g., missing globals)
  }
  const profileMenu = document.querySelector(".profile-dropdown .dropdown-content");
  const profileButton = document.querySelector(".profile-btn");
  const profileDropdown = document.querySelector(".profile-dropdown");
  let dropdownToggleReady = false;
  const formMemberSinceId = "acc-member-since";

  // Always set the member-since inputs to the current year when the DOM is ready or the form is opened.
  const setMemberSinceYear = () => {
    const year = new Date().getFullYear().toString();
    document.querySelectorAll(`#${formMemberSinceId}`).forEach((input) => {
      input.value = year;
      input.defaultValue = year;
    });
  };

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
    return (data?.users || []).find(user => {
      const uid = String(user?.id || user?.user_id || user?.['owner-id'] || '');
      const uname = String(user?.['owner-name'] || user?.user_name || user?.['user_name'] || '');
      return uid === ownerId || uname === ownerId;
    }) || null;
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
    if (!confirm("Sign out?")) return;

    (async () => {
      // Attempt server-side logout when possible so PHP session is cleared
      try {
        await fetch('../PHP/auth.php?action=logout', { method: 'GET', cache: 'no-store' });
      } catch (err) {
        // ignore network errors (e.g., file://) and continue with client-side logout
        console.warn('Server logout failed or unavailable', err);
      }

      // Clear client session state
      try {
        localStorage.setItem("currentUser", JSON.stringify({ active: false }));
      } catch (e) {
        console.warn('Unable to write localStorage during logout', e);
      }
      currentUser = null;
      notifyUserStateChange();
      renderProfileMenu();

      try { notify('Signed out.', 'info'); } catch (e) { }
      // Redirect to homepage after logout
      window.location.href = "home_page.html";
    })();
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
  // Centralized login submit handler so it can be reused by the dropdown
  // login form and by a modal-based login form.
  async function handleLoginSubmit(e) {
    if (e && e.preventDefault) e.preventDefault();
    // Prefer modal inputs when present (they are the active UI). Fall back to
    // the dropdown form inputs if modal is not available. This avoids reading
    // values from a hidden dropdown input when IDs are duplicated.
    const usernameInput = document.querySelector('#modal-login-form #login-user') || document.getElementById("login-user") || document.querySelector('.profile-dropdown #login-user');
    const passwordInput = document.querySelector('#modal-login-form #login-pass') || document.getElementById("login-pass") || document.querySelector('.profile-dropdown #login-pass');
    const username = usernameInput?.value?.trim() || "";
    const password = passwordInput?.value?.trim() || "";
    const hasLetter = (value) => /[A-Za-z]/.test(value);

    if (!hasLetter(username) || !hasLetter(password)) {
      notify("Please enter a username and password (at least one letter each).", "warning");
      return;
    }

    // Resolve username -> email when possible
    let email = username;
    try {
      const data = window.appData?.loadData ? window.appData.loadData() : null;
      if (data && !username.includes('@')) {
        const user = (data.users || []).find(u => u.id === username || u['owner-id'] === username || u.user_name === username);
        if (user && user.email) email = user.email;
      }
    } catch (err) {
      // ignore
    }

    try {
      const payload = new FormData();
      payload.append('email', email);
      payload.append('password', password);
      const resp = await fetch('../PHP/auth.php', { method: 'POST', body: payload });
      const json = await resp.json();
      if (json && json.success && json.user) {
        const userSess = { id: json.user.id, ownerName: json.user.name, active: true };
        localStorage.setItem('currentUser', JSON.stringify(userSess));
        currentUser = userSess;
        notifyUserStateChange();
        renderProfileMenu();
        try { notify('You are now logged in.', 'success'); } catch (e) { }
        // Close modal if present
        const loginModal = document.getElementById('login-modal');
        if (loginModal) loginModal.style.display = 'none';
      } else {
        notify('Login failed: ' + (json && json.error ? json.error : 'invalid credentials'), 'error');
      }
    } catch (err) {
      console.error('Login error', err);
      notify('Login error', 'error');
    }
  }

  // Helper to open a login modal. Re-uses the same input IDs so the handler
  // can find them whether the form is in the dropdown or modal.
  function openLoginModal() {
    if (document.getElementById('login-modal')) {
      const existing = document.getElementById('login-modal');
      existing.style.display = 'flex';
      const userField = document.getElementById('login-user');
      userField?.focus();
      return;
    }

    const modal = document.createElement('div');
    modal.id = 'login-modal';
    modal.className = 'modal';
    modal.style.display = 'flex';
    modal.innerHTML = `
      <div class="modal-content">
        <span class="close-btn" id="close-login-modal">&times;</span>
        <h2>Log In</h2>
        <form id="modal-login-form" class="login-form">
          <label>Username:</label>
          <input type="text" id="login-user" placeholder="Enter username">
          <label>Password:</label>
          <input type="password" id="login-pass" placeholder="Enter password">
          <div style="margin-top:12px;display:flex;gap:8px;align-items:center;">
            <button type="submit" class="login-btn explore-btn">Enter</button>
            <button type="button" id="modal-cancel-login" class="explore-btn ghost">Cancel</button>
          </div>
        </form>
      </div>`;

    document.body.appendChild(modal);

    // Close handlers
    document.getElementById('close-login-modal')?.addEventListener('click', () => { modal.style.display = 'none'; });
    document.getElementById('modal-cancel-login')?.addEventListener('click', () => { modal.style.display = 'none'; });

    // Wire submit to centralized handler
    const modalForm = document.getElementById('modal-login-form');
    modalForm?.addEventListener('submit', handleLoginSubmit);
    // focus the username field shortly after opening
    setTimeout(() => document.getElementById('login-user')?.focus(), 60);
  }

  // Expose helper so other scripts can open the login modal
  window.openLoginModal = openLoginModal;

  function attachEvents() {
    const form = document.getElementById("login-form");
    const signoutBtn = document.getElementById("signout-btn");

    // Links that open demo modals
    const forgotPasswordBtn = document.getElementById("forgot-password-btn");
    const addAccountBtn = document.getElementById("add-account-btn");

    if (form) {
      form.addEventListener('submit', handleLoginSubmit);
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
        notify("Password reset email sent.", "success");
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
        setMemberSinceYear();
      });
      closeAccount?.addEventListener("click", () => {
        if (accountModal) {
          accountModal.style.display = "none";
        }
      });
      accountForm?.addEventListener("submit", async (e) => {
        e.preventDefault();
        const getValue = (id) => document.getElementById(id)?.value?.trim() || "";
        const ownerName = getValue("acc-name");
        const email = getValue("acc-email");

        if (!ownerName) { notify("Please provide a username.", "warning"); return; }
        if (!email) { notify("Please provide an email.", "warning"); return; }
        const password = document.getElementById("acc-password")?.value || "";
        const confirmPassword = document.getElementById("acc-password-confirm")?.value || "";
        if (password !== confirmPassword) { notify("Passwords do not match!", "warning"); return; }
        const ownerPhoto = document.getElementById("acc-owner-photo")?.value?.trim() || "";
        const dateOfBirth = document.getElementById("acc-dob")?.value || "";
        const memberSince = document.getElementById("acc-member-since")?.value?.trim() || "";

        // Prepare payload for server
        try {
          const payload = new FormData();
          payload.append('action', 'create');
          payload.append('name', ownerName);
          payload.append('email', email);
          payload.append('password', password);
          if (ownerPhoto) payload.append('photo', ownerPhoto);
          if (dateOfBirth) payload.append('dob', dateOfBirth);
          if (memberSince) payload.append('member_since', memberSince);

          const res = await fetch('../PHP/crud/users.php', { method: 'POST', body: payload });
          const json = await res.json();
          if (!res.ok || json.error) {
            const msg = json && json.error ? json.error : 'Failed to create account';
            notify('Account creation failed: ' + msg, 'error');
            return;
          }

          // Auto-login the new user (server-side session via auth.php)
          try {
            const loginPayload = new FormData();
            loginPayload.append('email', email);
            loginPayload.append('password', password);
            const authRes = await fetch('../PHP/auth.php', { method: 'POST', body: loginPayload });
            const authJson = await authRes.json();
            if (authRes.ok && authJson && authJson.success && authJson.user) {
              const userSess = { id: authJson.user.id, ownerName: authJson.user.name, active: true };
              localStorage.setItem('currentUser', JSON.stringify(userSess));
              currentUser = userSess;
              notifyUserStateChange();
              renderProfileMenu();
              try { notify('You are now logged in.', 'success'); } catch (e) { }
            } else {
              // Account created but login failed (unlikely). Inform user.
              notify('Account created. Please use the login form to sign in.', 'success');
            }
          } catch (loginErr) {
            console.warn('Auto-login failed', loginErr);
            notify('Account created but automatic login failed. Please sign in.', 'warning');
          }

          if (accountModal) accountModal.style.display = 'none';
          accountForm.reset();
          setMemberSinceYear();

          // Update cached dataset locally instead of re-fetching over JS
          try {
            const data = window.appData?.loadData ? window.appData.loadData() : null;
            if (data) {
              const newUser = {
                id: authJson?.user?.id || email,
                user_id: authJson?.user?.id || email,
                user_name: ownerName,
                email,
                user_photo: ownerPhoto || null,
                member_since: memberSince || new Date().getFullYear().toString()
              };
              const users = Array.isArray(data.users) ? data.users.filter(u => (u.id || u.user_id) !== newUser.id) : [];
              users.push(newUser);
              data.users = users;
              localStorage.setItem('collectionsData', JSON.stringify(data));
              window.SERVER_APP_DATA = data;
            }
          } catch (err) { console.warn('Unable to refresh local dataset locally', err); }

        } catch (err) {
          console.error('Account creation error', err);
          notify('Account creation failed due to network error', 'error');
        }
      });
    }

    // Quick search-bar placeholder handler (demo)
    const searchBar = document.querySelector(".search-bar");
    if (searchBar) {
      searchBar.addEventListener("keydown", (e) => {
        if (e.key === "Enter") {
          e.preventDefault(); // Prevent default Enter behavior
          notify("Search functionality is under construction!", "info");
        }
      });
    }
  }

  // =======================================================
  // 6. Initialization
  // =======================================================
  setMemberSinceYear();
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

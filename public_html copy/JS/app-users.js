// ===============================================
// app-users.js — Gestão visual do utilizador
// ===============================================
document.addEventListener("DOMContentLoaded", () => {
  let currentUser = JSON.parse(localStorage.getItem("currentUser"));
  const profileMenu = document.querySelector(".profile-dropdown .dropdown-content");
  const profileButton = document.querySelector(".profile-btn");

  // =======================================================
  // 1️⃣ Renderizar menu (consoante o estado)
  // =======================================================
  function renderProfileMenu() {
    if (!profileMenu) return;
    profileMenu.innerHTML = "";

    if (currentUser && currentUser.active) {
      // ✅ Utilizador autenticado
      profileButton.innerHTML = `👤 ${currentUser.name} ▾`;
      profileMenu.innerHTML = `
        <a href="user_page.html">See Profile</a>
        <a href="#" id="signout-btn">Sign Out</a>
      `;
    } else {
      // 🚪 Não autenticado → mostra formulário inline
      profileButton.innerHTML = `👤 Enter ▾`;
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

    attachEvents();
  }

  // =======================================================
  // 2️⃣ Login simulado (sempre como 'collector')
  // =======================================================
  function loginUser() {
    currentUser = {
      id: "collector-main",
      name: "collector", // 🔹 sempre o mesmo utilizador
      active: true
    };
    localStorage.setItem("currentUser", JSON.stringify(currentUser));
    notifyUserStateChange();
    renderProfileMenu();
  }

  // =======================================================
  // 3️⃣ Logout
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
  // 4️⃣ Notificação global
  // =======================================================
  function notifyUserStateChange() {
    const event = new CustomEvent("userStateChange", {
      detail: JSON.parse(localStorage.getItem("currentUser"))
    });
    console.log("📣 userStateChange emitted:", event.detail);
    window.dispatchEvent(event);
  }

  // =======================================================
  // 5️⃣ Eventos dinâmicos
  // =======================================================
  function attachEvents() {
    const form = document.getElementById("login-form");
    const signoutBtn = document.getElementById("signout-btn");

    // Links para os novos modais
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

    // Eventos para os novos modais
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

    if (addAccountBtn) {
      const accountModal = document.getElementById("add-account-modal");
      const closeAccount = document.getElementById("close-account-modal");
      addAccountBtn.addEventListener("click", (e) => {
        e.preventDefault();
        if (accountModal) accountModal.style.display = "flex";
      });
      closeAccount?.addEventListener("click", () => accountModal.style.display = "none");
      document.getElementById("form-add-account")?.addEventListener("submit", (e) => {
        e.preventDefault();
        const pass1 = document.getElementById("acc-password").value;
        const pass2 = document.getElementById("acc-password-confirm").value;
        if (pass1 !== pass2) {
          return alert("Passwords do not match!");
        }
        alert("✅ Account created successfully!\n\n(This is a simulation. No data was saved.)");
        accountModal.style.display = "none";
      });
    }

    // 🔹 Alerta para a barra de pesquisa
    const searchBar = document.querySelector(".search-bar");
    if (searchBar) {
      searchBar.addEventListener("keydown", (e) => {
        if (e.key === "Enter") {
          e.preventDefault(); // Previne qualquer ação padrão do 'Enter'
          alert("Search functionality is under construction!");
        }
      });
    }
  }

  // =======================================================
  // 6️⃣ Inicialização
  // =======================================================
  renderProfileMenu();
  window.appUsers = { currentUser };

  // 🔹 Dispara o estado inicial logo ao abrir a página
  const initialEvent = new CustomEvent("userStateChange", { detail: currentUser });
  window.dispatchEvent(initialEvent);
});

// =======================================================
// 7️⃣ Atualização global em todas as páginas
// =======================================================
window.addEventListener("userStateChange", (e) => {
  const user = e.detail;
  const isActiveUser = user && user.active;

  console.log("👂 userStateChange received:", user);

  // Esconde/mostra botões com base no login
  document.querySelectorAll("[data-requires-login]").forEach(btn => {
    btn.style.display = isActiveUser ? "inline-block" : "none";
  });
});

// ===============================================
// app-users.js — Gestão visual do utilizador
// ===============================================
document.addEventListener("DOMContentLoaded", () => {
  let currentUser = JSON.parse(localStorage.getItem("currentUser"));
  const profileMenu = document.querySelector(".profile-menu");
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
          <input type="text" id="login-user" placeholder="Enter username" required>
          <label>Password:</label>
          <input type="password" id="login-pass" placeholder="Enter password" required>
          <button type="submit" class="login-btn">Enter</button>
        </form>
      `;
    }

    attachEvents();
  }

  // =======================================================
  // 2️⃣ Login simulado (sem base de dados)
  // =======================================================
  function loginUser(username) {
    currentUser = {
      id: "user-" + Date.now(),
      name: username || "collector",
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
      localStorage.removeItem("currentUser");
      currentUser = null;
      notifyUserStateChange();
      renderProfileMenu();
    }
  }

  // =======================================================
  // 4️⃣ Emitir evento global
  // =======================================================
  function notifyUserStateChange() {
    const event = new CustomEvent("userStateChange", {
      detail: JSON.parse(localStorage.getItem("currentUser"))
    });
    console.log("📣 userStateChange emitted:", event.detail);
    window.dispatchEvent(event);
  }

  // =======================================================
  // 5️⃣ Eventos dinâmicos (login/logout)
  // =======================================================
  function attachEvents() {
    const form = document.getElementById("login-form");
    const signoutBtn = document.getElementById("signout-btn");

    if (form) {
      form.addEventListener("submit", (e) => {
        e.preventDefault();
        const username = document.getElementById("login-user").value.trim();
        const password = document.getElementById("login-pass").value.trim();
        if (!username || !password) return alert("Fill in both fields");
        loginUser(username);
      });
    }

    if (signoutBtn) {
      signoutBtn.addEventListener("click", (e) => {
        e.preventDefault();
        logoutUser();
      });
    }
  }

  // =======================================================
  // 6️⃣ Inicialização
  // =======================================================
  renderProfileMenu();
  window.appUsers = { currentUser };

  // 🔹 Força a atualização global logo ao carregar a página
  const initialEvent = new CustomEvent("userStateChange", { detail: currentUser });
  window.dispatchEvent(initialEvent);
});

// =======================================================
// 7️⃣ 🔹 Atualizar interface global em todas as páginas
// =======================================================
window.addEventListener("userStateChange", (e) => {
  const user = e.detail;
  const isActiveUser = user && user.active;

  console.log("👂 userStateChange received:", user);

  // Seleciona todos os botões que dependem de login
  document.querySelectorAll("[data-requires-login]").forEach(btn => {
    btn.style.display = isActiveUser ? "inline-block" : "none";
  });
});

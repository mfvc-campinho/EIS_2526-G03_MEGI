// ----------------------------------------------------
// Login Form Validation
// ----------------------------------------------------

document.getElementById("loginForm").addEventListener("submit", function (e) {
  e.preventDefault();

  const email = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value.trim();

  // Basic Validation
  if (email === "" && password === "") {
    alert("Please enter your email and password.");
    return;
  }
  if (email === "") {
    alert("Please enter your email address.");
    return;
  }
  if (password === "") {
    alert("Please enter your password.");
    return;
  }


  // Format E-mail Validation
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    alert("Please enter a valid email address.");
    return;
  }


  // Alert Validation
  alert("Login successful! (Demo version)");

  // Após login normal, não esconder o formulário permanentemente
  // O estado será controlado pelo checkAuthStatus
});

document.getElementById("guestBtn").addEventListener("click", function () {
  alert("You’re browsing as a guest!");
});

// ----------------------------------------------------
// Forgot Password - show  alert
// ----------------------------------------------------

document.getElementById("forgetBtn").addEventListener("click", function () {
  alert("⚠️ SERVICE UNAVAILABLE.\nPlease contact your administrator.");
});

// ----------------------------------------------------
// State Management for Collector Login
// ----------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
  // Ao carregar, define estado inicial se não existir
  if (!sessionStorage.getItem('userRole')) {
    sessionStorage.setItem('userRole', 'none'); // guest
  }
  const pageBody = document.getElementById('page-body');
  const guestButton = document.getElementById('guestBtn');
  const collectorButton = document.getElementById('enter-collector-button');
  const userIndicator = document.getElementById('user-indicator');
  const logoutCollectorButton = document.getElementById('logout-collector-button');
  const loginForm = document.getElementById('loginForm');
  const createAccountBtn = document.querySelector('.new-account');
  const forgetBtn = document.getElementById('forgetBtn');
  const guestSection = document.getElementById('guestSection');

  // Function 1: CHECK AUTH STATUS
  function checkAuthStatus() {
    const isCollector = sessionStorage.getItem('userRole') === 'C';
    if (isCollector) {
      // Collector: Buttons to exclusive to Collector
      if (collectorButton) collectorButton.style.display = 'inline-block';
      if (logoutCollectorButton) logoutCollectorButton.style.display = 'inline-block';
      if (loginForm) loginForm.style.display = 'none';
      if (createAccountBtn) createAccountBtn.style.display = 'none';
      if (forgetBtn) forgetBtn.style.display = 'none';
      if (guestSection) guestSection.style.display = 'none';

      userIndicator.textContent = 'C';
    } else {
      // Guest: Show Forms, create account and other settings
      if (collectorButton) collectorButton.style.display = 'none';
      if (logoutCollectorButton) logoutCollectorButton.style.display = 'none';
      if (loginForm) loginForm.style.display = 'block';
      if (createAccountBtn) createAccountBtn.style.display = 'inline-block';
      if (forgetBtn) forgetBtn.style.display = 'inline-block';
      if (guestSection) guestSection.style.display = 'block';

      userIndicator.textContent = 'G';
    }
  }

  // Function 2: HANDLE COLLECTOR LOGIN

  // Botão login normal muda para collector
  const loginBtn = document.getElementById('login-collector');
  if (loginBtn) {
    loginBtn.addEventListener('click', function (e) {
      // O submit já faz validação, aqui só mudamos o estado
      sessionStorage.setItem('userRole', 'C');
      checkAuthStatus();
    });
  }

  // Collector login
  if (document.getElementById('enter-collector-button')) {
    document.getElementById('enter-collector-button').addEventListener('click', function (e) {
      e.preventDefault();
      // Aqui podes adicionar validação extra se quiseres
      sessionStorage.setItem('userRole', 'C');
      alert("Login como Collector bem-sucedido!");
      window.location.href = 'home_page.html'; // Redireciona para a página definida
    });
  }

  // Collector logout
  if (logoutCollectorButton) {
    logoutCollectorButton.addEventListener('click', function (e) {
      e.preventDefault();
      sessionStorage.setItem('userRole', 'none'); // guest
      alert("Logout Collector realizado!");
      checkAuthStatus();
      window.location.href = 'index.html';
    });
  }

  // Inicia a verificação ao carregar a página
  checkAuthStatus();
});
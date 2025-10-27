// ----------------------------------------------------
// Login Form Validation
// ----------------------------------------------------

document.getElementById("loginForm").addEventListener("submit", function (e) {
  e.preventDefault();

  const email = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value.trim();

  // Validação básica
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


  // Validação de formato de email
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    alert("Please enter a valid email address.");
    return;
  }


  // Validação normal (demo)
  alert("Login successful! (Demo version)");

  // Após login aprovado, mostra botão de collector
  document.getElementById('auth-controls').style.display = 'block';
});

document.getElementById("guestBtn").addEventListener("click", function () {
  alert("You’re browsing as a guest!");
});

// ----------------------------------------------------
// Forgot Password - show system-like alert
// ----------------------------------------------------

document.getElementById("forgetBtn").addEventListener("click", function () {
  alert("⚠️ SERVICE UNAVAILABLE.\nPlease contact your administrator.");
});

// ----------------------------------------------------
// State Management for Collector Login
// ----------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
  const pageBody = document.getElementById('page-body');
  const guestButton = document.getElementById('guestBtn');
  const collectorButton = document.getElementById('login-collector-button');
  const userIndicator = document.getElementById('user-indicator');

  // Function 1: CHECK AUTH STATUS
  function checkAuthStatus() {
    // Verifica se o estado 'collector' está definido no sessionStorage
    const isCollector = sessionStorage.getItem('userRole') === 'collector';

    // 1. Limpa o estado e define os padrões (ESTADO GUEST)
    pageBody.classList.remove('is-collector');
    collectorButton.style.display = 'inline-block';
    userIndicator.textContent = 'G'; // Indicador: G de Guest

    if (isCollector) {
      // 2. Estado COLLECTOR
      pageBody.classList.add('is-collector');
      collectorButton.style.display = 'none'; // Esconde o botão de login
      userIndicator.textContent = 'Collector'; // Indicador: L de Collector
    }
  }

  // Function 2: HANDLE COLLECTOR LOGIN

  // Botão Collector (Login)
  collectorButton.addEventListener('click', () => {
    // Define a chave para "collector"
    sessionStorage.setItem('userRole', 'collector');
    checkAuthStatus();
  });

  // Collector login
  if (document.getElementById('login-collector-button')) {
    document.getElementById('login-collector-button').addEventListener('click', function (e) {
      e.preventDefault();
      // Aqui podes adicionar validação extra se quiseres
      sessionStorage.setItem('userRole', 'collector');
      alert("Login como Collector bem-sucedido!");
      window.location.href = 'home_page.html'; // Redireciona para a página definida
    });
  }

  // Inicia a verificação ao carregar a página
  checkAuthStatus();
});
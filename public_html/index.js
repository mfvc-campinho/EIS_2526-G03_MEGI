// ----------------------------------------------------
// 🌐 LOGIN + ROLE MANAGEMENT SCRIPT
// Everything runs after DOM is fully loaded
// ----------------------------------------------------

document.addEventListener('DOMContentLoaded', () => {

  // ----------------------------------------------------
  // 🧩 LOGIN FORM VALIDATION
  // ----------------------------------------------------
  const loginForm = document.getElementById("loginForm");

  if (loginForm) {
    loginForm.addEventListener("submit", function (e) {
      e.preventDefault(); // Prevent reload

      const email = document.getElementById("email").value.trim();
      const password = document.getElementById("password").value.trim();

      // ✅ Basic validation already handled by HTML (required, type="email")
      // Here we just double-check email format manually if needed
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email)) {
        alert("Please enter a valid email address.");
        return;
      }

      // Demo-only feedback
      alert("Login successful! (Demo version)");
    });
  }

  // ----------------------------------------------------
  // 👥 STATE MANAGEMENT (Collector vs Guest)
  // ----------------------------------------------------

  // Initialize user role if not defined
  if (!sessionStorage.getItem('userRole')) {
    sessionStorage.setItem('userRole', 'none'); // Default: guest
  }

  // Cache DOM elements
  const userIndicator = document.getElementById('user-indicator');
  const guestSection = document.getElementById('guestSection');
  const collectorButton = document.getElementById('enter-collector-button');
  const logoutCollectorButton = document.getElementById('logout-collector-button');
  const createAccountBtn = document.querySelector('.new-account');
  const forgetBtn = document.getElementById('forgetBtn');
  const loginBtn = document.getElementById('login-collector');

  // ----------------------------------------------------
  // 🔸 FUNCTION: Update UI based on current role
  // ----------------------------------------------------
  function checkAuthStatus() {
    const isCollector = sessionStorage.getItem('userRole') === 'C';

    // Toggle visibility based on role
    loginForm.style.display = isCollector ? 'none' : 'block';
    guestSection.style.display = isCollector ? 'none' : 'block';
    collectorButton.style.display = isCollector ? 'inline-block' : 'none';
    logoutCollectorButton.style.display = isCollector ? 'inline-block' : 'none';
    createAccountBtn.style.display = isCollector ? 'none' : 'inline-block';
    forgetBtn.style.display = isCollector ? 'none' : 'inline-block';

    // Update user indicator (C = Collector, G = Guest)
    userIndicator.textContent = isCollector ? 'C' : 'G';
  }

  // ----------------------------------------------------
  // 🔸 EVENT: Manual "login as Collector" (demo)
  // ----------------------------------------------------
  if (loginBtn) {
    loginBtn.addEventListener('click', () => {
      sessionStorage.setItem('userRole', 'C');
      checkAuthStatus();
    });
  }

  // ----------------------------------------------------
  // 🔸 EVENT: Enter Collector Mode (redirect)
  // ----------------------------------------------------
  if (collectorButton) {
    collectorButton.addEventListener('click', (e) => {
      e.preventDefault();
      sessionStorage.setItem('userRole', 'C');
      alert("Collector login successful!");
      window.location.href = 'home_page.html';
    });
  }

  // ----------------------------------------------------
  // 🔸 EVENT: Logout Collector
  // ----------------------------------------------------
  if (logoutCollectorButton) {
    logoutCollectorButton.addEventListener('click', (e) => {
      e.preventDefault();
      sessionStorage.setItem('userRole', 'none'); // Back to guest
      alert("Collector logout successful!");
      checkAuthStatus();
      window.location.href = 'index.html';
    });
  }

  // ----------------------------------------------------
  // 🔹 INITIALIZATION
  // ----------------------------------------------------
  checkAuthStatus(); // Run once when page loads
});
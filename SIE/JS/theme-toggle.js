// Theme toggle logic for GoodCollections
// - toggles .dark-mode on <body> (and on <html> initially to avoid FOUC)
// - persists preference to localStorage
// - updates the navbar icon

(function () {
  const THEME_KEY = 'theme';
  const BTN_ID = 'theme-toggle';

  // Apply saved preference early (if body exists add class, else add to documentElement so variables apply)
  try {
    const saved = localStorage.getItem(THEME_KEY);
    if (saved === 'dark') {
      // Add to both html and body when possible
      document.documentElement.classList.add('dark-mode');
      if (document.body) document.body.classList.add('dark-mode');
    }
  } catch (e) {
    // ignore localStorage errors (privacy mode)
    console.warn('theme-toggle: could not read theme from localStorage', e);
  }

  // Helper to set icon based on mode
  function updateButtonIcon(btn, isDark) {
    if (!btn) return;
    const icon = btn.querySelector('i');
    if (!icon) return;
    if (isDark) {
      icon.classList.remove('bi-brightness-high-fill');
      icon.classList.add('bi-moon-stars-fill');
      btn.setAttribute('aria-pressed', 'true');
      btn.title = 'Switch to light mode';
    } else {
      icon.classList.remove('bi-moon-stars-fill');
      icon.classList.add('bi-brightness-high-fill');
      btn.setAttribute('aria-pressed', 'false');
      btn.title = 'Switch to dark mode';
    }
  }

  // Toggle theme and persist
  function toggleTheme() {
    const isDark = document.body.classList.toggle('dark-mode');
    // also keep on html for immediate variable inheritance
    if (isDark) document.documentElement.classList.add('dark-mode');
    else document.documentElement.classList.remove('dark-mode');

    try {
      localStorage.setItem(THEME_KEY, isDark ? 'dark' : 'light');
    } catch (e) {
      console.warn('theme-toggle: could not write theme to localStorage', e);
    }

    const btn = document.getElementById(BTN_ID);
    updateButtonIcon(btn, isDark);
  }

  // Setup event listeners when DOM is ready
  function init() {
    const btn = document.getElementById(BTN_ID);
    if (!btn) return;

    // Initialize icon based on current state
    const isDark = document.body.classList.contains('dark-mode') || document.documentElement.classList.contains('dark-mode');
    updateButtonIcon(btn, isDark);

    btn.addEventListener('click', function (e) {
      e.preventDefault();
      toggleTheme();
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

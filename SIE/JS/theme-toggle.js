// Dark Mode Toggle System for GoodCollections
(function() {
  const THEME_KEY = 'gc-theme';
  const BTN_ID = 'theme-toggle';
  
  // Apply saved theme immediately to prevent flash
  function applySavedTheme() {
    try {
      const saved = localStorage.getItem(THEME_KEY);
      if (saved === 'dark') {
        document.documentElement.classList.add('dark-mode');
      }
    } catch (e) {
      console.warn('Could not load theme preference');
    }
  }
  
  // Toggle dark mode
  function toggleTheme() {
    const html = document.documentElement;
    const isDark = html.classList.toggle('dark-mode');
    
    // Update button icon
    const btn = document.getElementById(BTN_ID);
    if (btn) {
      const icon = btn.querySelector('i');
      if (icon) {
        icon.className = isDark ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
      }
    }
    
    // Save preference
    try {
      localStorage.setItem(THEME_KEY, isDark ? 'dark' : 'light');
    } catch (e) {
      console.warn('Could not save theme preference');
    }
  }
  
  // Initialize button
  function init() {
    const btn = document.getElementById(BTN_ID);
    if (!btn) return;
    
    // Set correct icon based on current mode
    const isDark = document.documentElement.classList.contains('dark-mode');
    const icon = btn.querySelector('i');
    if (icon) {
      icon.className = isDark ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
    }
    
    // Add click listener
    btn.addEventListener('click', toggleTheme);
  }
  
  // Apply theme ASAP
  applySavedTheme();
  
  // Initialize when ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

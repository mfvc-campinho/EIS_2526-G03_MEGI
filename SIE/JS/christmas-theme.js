// 🎄 Christmas Theme System for GoodCollections
(function () {
  const CHRISTMAS_KEY = 'gc-christmas';
  const BTN_ID = 'christmas-toggle';

  // Apply saved theme immediately to prevent flash
  function applySavedTheme() {
    try {
      const saved = localStorage.getItem(CHRISTMAS_KEY);
      if (saved === 'active') {
        document.documentElement.classList.add('christmas-theme');
        createSnowflakes();
      }
    } catch (e) {
      console.warn('Could not load Christmas theme preference');
    }
  }

  // Create snowflakes
  function createSnowflakes() {
    removeSnowflakes(); // Remove existing first

    const snowflakeCount = 15;
    const snowflakes = '❄';

    for (let i = 0; i < snowflakeCount; i++) {
      const snowflake = document.createElement('div');
      snowflake.className = 'snowflake';
      snowflake.textContent = snowflakes;
      snowflake.style.left = Math.random() * 100 + '%';
      snowflake.style.animationDuration = (Math.random() * 3 + 8) + 's';
      snowflake.style.animationDelay = Math.random() * 5 + 's';
      snowflake.style.fontSize = (Math.random() * 1 + 1) + 'em';
      snowflake.style.opacity = Math.random() * 0.5 + 0.5;
      document.body.appendChild(snowflake);
    }
  }

  // Remove snowflakes
  function removeSnowflakes() {
    const snowflakes = document.querySelectorAll('.snowflake');
    snowflakes.forEach(flake => flake.remove());
  }

  // Toggle Christmas theme
  function toggleChristmas() {
    const html = document.documentElement;
    const isActive = html.classList.toggle('christmas-theme');

    if (isActive) {
      localStorage.setItem(CHRISTMAS_KEY, 'active');
      createSnowflakes();
    } else {
      localStorage.removeItem(CHRISTMAS_KEY);
      removeSnowflakes();
    }

    // Update button text
    const btn = document.getElementById(BTN_ID);
    if (btn) {
      const textSpan = btn.querySelector('.btn-text');
      if (textSpan) {
        textSpan.textContent = isActive ? 'Natal ✓' : 'Natal';
      }
    }
  }

  // Initialize button
  function init() {
    const btn = document.getElementById(BTN_ID);
    if (!btn) return;

    // Set correct text based on current state
    const isActive = document.documentElement.classList.contains('christmas-theme');
    const textSpan = btn.querySelector('.btn-text');
    if (textSpan) {
      textSpan.textContent = isActive ? 'Natal ✓' : 'Natal';
    }

    // Add click listener
    btn.addEventListener('click', toggleChristmas);
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

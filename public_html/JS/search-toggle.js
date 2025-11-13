document.addEventListener('DOMContentLoaded', function () {
  // Toggle mobile search input when the search icon is clicked.
  // Expected HTML structure:
  // <div class="search-wrapper">
  //   <i class="bi bi-search search-icon"></i>
  //   <input class="search-bar" ... />
  // </div>

  function initSearchToggles() {
    var wrappers = document.querySelectorAll('.search-wrapper');
    wrappers.forEach(function (wrap) {
      var icon = wrap.querySelector('.search-icon');
      var input = wrap.querySelector('.search-bar');
      if (!icon || !input) return;

      // Make icon keyboard accessible
      icon.setAttribute('role', 'button');
      icon.setAttribute('tabindex', '0');
      icon.setAttribute('aria-label', 'Toggle search');
      icon.setAttribute('aria-expanded', 'false');

      function openSearch() {
        wrap.classList.add('search-open');
        icon.setAttribute('aria-expanded', 'true');
        // ensure visible (CSS will handle layout) then focus
        setTimeout(function () { input.focus(); }, 50);
      }

      function closeSearch() {
        wrap.classList.remove('search-open');
        icon.setAttribute('aria-expanded', 'false');
      }

      icon.addEventListener('click', function (e) {
        // Toggle only when input is hidden by CSS (small screens)
        var style = window.getComputedStyle(input);
        if (style.display === 'none' || style.visibility === 'hidden') {
          if (wrap.classList.contains('search-open')) closeSearch();
          else openSearch();
          e.stopPropagation();
        }
      });

      icon.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          icon.click();
        }
      });

      // Close when clicking outside
      document.addEventListener('click', function (e) {
        if (!wrap.contains(e.target)) closeSearch();
      });

      // Close on Escape
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeSearch();
      });
    });
  }

  initSearchToggles();
});

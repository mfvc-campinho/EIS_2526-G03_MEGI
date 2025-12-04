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

  // Simple client-side filter for cards (collections/items/events)
  function initSearchFilter() {
    var wrappers = document.querySelectorAll('.search-wrapper');
    var inputs = document.querySelectorAll('.search-bar');
    if (!inputs.length) return;
    var cards = Array.from(document.querySelectorAll('.product-card, .event-card, .item-card'));
    if (!cards.length) return;

    // Precompute searchable text
    var cardText = new Map();
    cards.forEach(function (card) {
      var cached = card.getAttribute('data-search-text');
      if (!cached) {
        cached = card.innerText.toLowerCase();
        card.setAttribute('data-search-text', cached);
      }
      cardText.set(card, cached);
    });

    function applyFilter(term) {
      cards.forEach(function (card) {
        var txt = cardText.get(card) || '';
        var match = term === '' || txt.indexOf(term) !== -1;
        card.style.display = match ? '' : 'none';
      });
    }

    inputs.forEach(function (input) {
      input.addEventListener('input', function () {
        applyFilter((input.value || '').toLowerCase());
      });
      input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          applyFilter((input.value || '').toLowerCase());
        }
      });
    });

    // Allow clicking the search icon to trigger filter
    wrappers.forEach(function (wrap) {
      var icon = wrap.querySelector('.search-icon');
      var input = wrap.querySelector('.search-bar');
      if (!icon || !input) return;
      icon.addEventListener('click', function () {
        applyFilter((input.value || '').toLowerCase());
      });
    });

    // Initial reset
    applyFilter('');
  }

  initSearchToggles();
  initSearchFilter();
});

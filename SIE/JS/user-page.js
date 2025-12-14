(function initScrollRestore() {
  if (typeof gcInitScrollRestore === 'function') {
    gcInitScrollRestore({
      key: 'gc-scroll-user',
      formSelector: '#user-top-filters'
    });
  }
})();

(function initCollectionCards() {
  function enhanceCard(card) {
    var href = card.getAttribute('data-collection-link');
    if (!href) return;

    card.addEventListener('click', function(e) {
      if (e.target.closest('a, button')) {
        return;
      }
      window.location.href = href;
    });

    card.addEventListener('keydown', function(e) {
      if (e.target !== card) return;
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        window.location.href = href;
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.collection-card-link').forEach(enhanceCard);
  });
})();

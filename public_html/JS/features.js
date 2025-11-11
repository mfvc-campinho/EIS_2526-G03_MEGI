// features.js — triggers the features section entrance animation
(function () {
  function onReady(fn) {
    if (document.readyState !== 'loading') fn(); else document.addEventListener('DOMContentLoaded', fn);
  }

  onReady(function () {
    var grid = document.querySelector('.features-grid');
    if (!grid || !window.IntersectionObserver) {
      // If no observer supported, reveal immediately
      if (grid) grid.classList.add('in-view');
      return;
    }

    var obs = new IntersectionObserver(function (entries, observer) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          grid.classList.add('in-view');
          observer.disconnect();
        }
      });
    }, { threshold: 0.18 });

    obs.observe(grid);
  });
})();

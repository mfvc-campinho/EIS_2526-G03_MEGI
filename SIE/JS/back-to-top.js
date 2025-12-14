(function () {
  var backToTopBtn = document.getElementById('backToTop');
  if (!backToTopBtn) return;

  function toggleBackToTop() {
    var scrollTop = window.scrollY || document.documentElement.scrollTop;
    if (scrollTop > 300) {
      backToTopBtn.classList.add('show');
    } else {
      backToTopBtn.classList.remove('show');
    }
  }

  backToTopBtn.addEventListener('click', function () {
    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    });
  });

  window.addEventListener('scroll', toggleBackToTop);
  toggleBackToTop();
})();

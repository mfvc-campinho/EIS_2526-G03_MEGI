<footer>
  <div class="footer-content">
    <p class="footer-made">
      Made with <span class="heart" aria-label="love">❤</span> by
      <i class="bi bi-github" aria-hidden="true"></i>
      <a href="https://github.com/">EIS_2526-G03_MEGI</a>
    </p>
    <nav class="footer-links" aria-label="Footer">
      <a href="home_page.php">Home Page</a>
      <a href="all_collections.php">Collections</a>
      <a href="event_page.php">Events</a>
      <a href="team_page.php">About Us</a>
      <a href="user_page.php">User Profile</a>
    </nav>
  </div>
</footer>


<!-- Back to Top -->
<button id="backToTop" class="back-to-top" aria-label="Voltar ao topo">
  <i class="bi bi-arrow-up"></i>
</button>

<script>
  (function () {
    var btn = document.getElementById('backToTop');
    if (!btn) return;
    var toggleBtn = function () {
      if (window.scrollY > 200) {
        btn.classList.add('show');
      } else {
        btn.classList.remove('show');
      }
    };
    window.addEventListener('scroll', toggleBtn, { passive: true });
    toggleBtn();
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  })();
</script>

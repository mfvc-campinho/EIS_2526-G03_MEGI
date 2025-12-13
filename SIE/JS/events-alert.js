(function () {
  const endpoint = new URL('../events_alert.php', window.location.href).toString();

  function addDot(link) {
    if (!link || link.dataset.alertDotAttached) return;
    link.dataset.alertDotAttached = 'true';

    const dot = document.createElement('span');
    dot.className = 'nav-alert-dot';
    dot.setAttribute('aria-label', 'Upcoming event within 5 days');
    dot.setAttribute('title', 'Upcoming event within 5 days');
    link.prepend(dot);
  }

  function injectStyles() {
    if (document.getElementById('nav-alert-dot-style')) return;
    const style = document.createElement('style');
    style.id = 'nav-alert-dot-style';
    style.textContent = `
      .nav-alert-dot {
        display: inline-block;
        width: 9px;
        height: 9px;
        margin-right: 6px;
        border-radius: 50%;
        background: #f97316;
        vertical-align: middle;
      }
    `;
    document.head.appendChild(style);
  }

  function init() {
    const link = document.querySelector('.nav-link[href*="event_page.php"]');
    if (!link) return;

    fetch(endpoint, { credentials: 'same-origin' })
      .then(res => res.ok ? res.json() : { hasUpcoming: false })
      .then(data => {
        if (data && data.hasUpcoming) {
          injectStyles();
          addDot(link);
        }
      })
      .catch(() => {/* silent */ });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();


/* Atualizar o calendário automaticamente*/

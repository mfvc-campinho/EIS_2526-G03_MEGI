(function initTeamCards() {
  var interactiveSelector = 'a, button, label, input, textarea, select, form, [role=\"button\"]';

  function enhanceMemberCard(card) {
    var href = card.getAttribute('data-member-link');
    if (!href) {
      return;
    }
    card.addEventListener('click', function(event) {
      if (event.target.closest(interactiveSelector)) {
        return;
      }
      window.open(href, '_blank', 'noopener');
    });
    card.addEventListener('keydown', function(event) {
      if (event.target !== card) {
        return;
      }
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        window.open(href, '_blank', 'noopener');
      }
    });
  }

  document.querySelectorAll('.team-card-link').forEach(enhanceMemberCard);
})();

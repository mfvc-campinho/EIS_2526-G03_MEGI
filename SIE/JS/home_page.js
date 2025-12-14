(function () {
  if (typeof gcInitScrollRestore === 'function') {
    gcInitScrollRestore({
      key: 'gc-scroll-home',
      formSelector: '#filters',
      reapplyFrames: 3,
      reinforceMs: 800,
      reinforceInterval: 80,
      stabilizeMs: 1200
    });
  }
})();

(function () {
  var interactiveSelector = 'a, button, label, input, textarea, select, form, [role="button"]';

  function enhanceCard(card) {
    var href = card.getAttribute('data-collection-link');
    if (!href) {
      return;
    }

    card.addEventListener('click', function (event) {
      if (event.target.closest(interactiveSelector)) {
        return;
      }
      window.location.href = href;
    });

    card.addEventListener('keydown', function (event) {
      if (event.target !== card) {
        return;
      }
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        window.location.href = href;
      }
    });
  }

  document.querySelectorAll('.collection-card-link').forEach(enhanceCard);
})();

(function () {
  var modal = document.getElementById('event-modal');
  if (!modal) {
    return;
  }

  var cardInteractiveSelector = '[data-home-rsvp-form], .home-event-rsvp, button, input, select, textarea, form, a';

  var closeButton = modal.querySelector('.modal-close');
  var titleEl = document.getElementById('modal-title');
  var typeEl = document.getElementById('modal-type');
  var summaryEl = document.getElementById('modal-summary');
  var descriptionEl = document.getElementById('modal-description');
  var dateEl = document.getElementById('modal-date');
  var timeRow = document.getElementById('modal-time-row');
  var timeEl = document.getElementById('modal-time');
  var locationLink = document.getElementById('modal-location');
  var costEl = document.getElementById('modal-cost');

  function setText(target, value) {
    if (!target) {
      return;
    }
    target.textContent = value || '';
  }

  function openModal(payload) {
    setText(titleEl, payload.name);
    setText(typeEl, payload.type);
    setText(summaryEl, payload.summary);
    setText(descriptionEl, payload.description);

    if (dateEl) {
      setText(dateEl, payload.date || payload.datetime || '');
    }

    if (timeRow && timeEl) {
      if (payload.time) {
        timeRow.hidden = false;
        setText(timeEl, payload.time);
      } else {
        timeRow.hidden = true;
        setText(timeEl, '');
      }
    }

    if (locationLink) {
      var cleanLocation = (payload.location || '').trim();
      if (cleanLocation) {
        locationLink.textContent = cleanLocation;
        locationLink.href = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(cleanLocation);
        locationLink.classList.remove('disabled');
        locationLink.setAttribute('target', '_blank');
        locationLink.setAttribute('rel', 'noopener noreferrer');
        locationLink.setAttribute('aria-label', 'Open ' + cleanLocation + ' on Google Maps');
      } else {
        locationLink.textContent = 'Location unavailable';
        locationLink.removeAttribute('href');
        locationLink.removeAttribute('target');
        locationLink.removeAttribute('rel');
        locationLink.removeAttribute('aria-label');
        locationLink.classList.add('disabled');
      }
    }

    if (costEl) {
      setText(costEl, payload.cost || 'Free entrance');
    }

    modal.classList.add('open');
  }

  function closeModal() {
    modal.classList.remove('open');
  }

  function bindEventCard(card) {
    if (!card) {
      return;
    }

    function launchModal() {
      openModal({
        name: card.getAttribute('data-name') || '',
        summary: card.getAttribute('data-summary') || '',
        description: card.getAttribute('data-description') || '',
        date: card.getAttribute('data-date') || '',
        time: card.getAttribute('data-time') || '',
        datetime: card.getAttribute('data-datetime') || '',
        location: card.getAttribute('data-location') || '',
        type: card.getAttribute('data-type') || '',
        cost: card.getAttribute('data-cost') || ''
      });
    }

    card.addEventListener('click', function (event) {
      if (event.target.closest(cardInteractiveSelector)) {
        return;
      }
      event.preventDefault();
      launchModal();
    });

    card.addEventListener('keydown', function (event) {
      if (event.target !== card) {
        return;
      }
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        launchModal();
      }
    });
  }

  document.querySelectorAll('.js-event-card').forEach(bindEventCard);

  if (closeButton) {
    closeButton.addEventListener('click', closeModal);
  }

  modal.addEventListener('click', function (event) {
    if (event.target === modal) {
      closeModal();
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && modal.classList.contains('open')) {
      closeModal();
    }
  });
})();

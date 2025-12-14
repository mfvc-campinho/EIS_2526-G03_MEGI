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

// Show “login required” flash for unauthenticated actions (e.g., like)
(function () {
  var triggers = document.querySelectorAll('[data-action="login-popup"]');
  if (!triggers.length) return;

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  function createFlash(detail) {
    detail = detail || {};
    var type = detail.type || 'info';
    var title = detail.title || (type === 'success' ? 'Success' : type === 'error' ? 'Oops!' : 'Heads up');
    var message = detail.message || '';
    var htmlMessage = detail.htmlMessage || null;

    document.querySelectorAll('.flash-modal[data-dynamic="true"]').forEach(function (node) {
      if (node && node.parentNode) {
        node.parentNode.removeChild(node);
      }
    });

    var modal = document.createElement('div');
    modal.className = 'flash-modal flash-modal--' + type;
    modal.setAttribute('role', 'alertdialog');
    modal.setAttribute('aria-live', 'assertive');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-label', title);
    modal.dataset.flashType = type;
    modal.setAttribute('data-dynamic', 'true');
    modal.innerHTML = '' +
      '<div class="flash-modal__backdrop"></div>' +
      '<div class="flash-modal__card" tabindex="-1">' +
      '  <button class="flash-modal__close" type="button" aria-label="Close notification">&times;</button>' +
      '  <div class="flash-modal__icon" aria-hidden="true">' + (type === 'success' ? '&#10003;' : '&#9888;') + '</div>' +
      '  <div class="flash-modal__content">' +
      '    <h3>' + escapeHtml(title) + '</h3>' +
      '    <p></p>' +
      '  </div>' +
      '</div>';

    document.body.appendChild(modal);

    var messageNode = modal.querySelector('.flash-modal__content p');
    if (htmlMessage) {
      messageNode.innerHTML = htmlMessage;
    } else {
      messageNode.textContent = message;
    }

    var card = modal.querySelector('.flash-modal__card');
    var closeBtn = modal.querySelector('.flash-modal__close');
    var delay = type === 'error' ? 8000 : 5000;
    var timer;

    function remove() {
      if (timer) {
        clearTimeout(timer);
      }
      modal.classList.add('is-closing');
      setTimeout(function () {
        if (modal && modal.parentNode) {
          modal.parentNode.removeChild(modal);
        }
        document.removeEventListener('keydown', onKey);
      }, 220);
    }

    function onKey(ev) {
      if (ev.key === 'Escape') {
        remove();
      }
    }

    modal.addEventListener('click', function (ev) {
      if (ev.target === modal || ev.target.classList.contains('flash-modal__backdrop')) {
        remove();
      }
    });

    if (closeBtn) {
      closeBtn.addEventListener('click', remove);
    }

    document.addEventListener('keydown', onKey);
    timer = setTimeout(remove, delay);

    if (card && typeof card.focus === 'function') {
      requestAnimationFrame(function () {
        card.focus();
      });
    }
  }

  window.appShowFlash = window.appShowFlash || createFlash;

  triggers.forEach(function (btn) {
    btn.addEventListener('click', function () {
      (window.appShowFlash || createFlash)({
        type: 'error',
        title: 'Oops!',
        message: 'Log in to like this collection.'
      });
    });
  });
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
  var formatDateOnly = function (value) {
    if (!value) return '';
    var clean = String(value).replace('T', ' ').trim();
    return clean.split(' ')[0];
  };

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
      var displayDate = formatDateOnly(payload.date || payload.datetime || '');
      setText(dateEl, displayDate || payload.date || payload.datetime || '');
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

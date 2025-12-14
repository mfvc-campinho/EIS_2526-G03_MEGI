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
  // Delegated handler to keep likes AJAX and avoid full page reload
  function handleLikeSubmit(form, ev) {
    ev.preventDefault();
    ev.stopPropagation();
    var submitBtn = form.querySelector('button[type="submit"]');
    var countSpan = form.querySelector('.like-count');
    var icon = form.querySelector('i');
    if (!submitBtn) return;

    // Hint return URL for server handlers that support it
    if (!form.querySelector('input[name="return_url"]')) {
      var ret = document.createElement('input');
      ret.type = 'hidden';
      ret.name = 'return_url';
      ret.value = 'home_page.php';
      form.appendChild(ret);
    }

    var formData = new FormData(form);
    submitBtn.disabled = true;

    fetch(form.action || 'likes_action.php', {
      method: 'POST',
      body: formData,
      credentials: 'same-origin'
    }).then(function (res) {
      var contentType = res.headers.get('content-type') || '';
      if (contentType.indexOf('application/json') !== -1) {
        return res.json();
      }
      return res.text().then(function (t) {
        try { return JSON.parse(t); } catch (e) { return { ok: res.ok }; }
      });
    }).then(function (data) {
      submitBtn.classList.toggle('is-liked');
      if (icon) {
        icon.classList.toggle('bi-heart');
        icon.classList.toggle('bi-heart-fill');
      }
      if (countSpan) {
        if (data && typeof data.likeCount === 'number') {
          countSpan.textContent = data.likeCount;
          countSpan.classList.toggle('is-zero', data.likeCount === 0);
        } else {
          var current = parseInt(countSpan.textContent || '0', 10) || 0;
          var liked = submitBtn.classList.contains('is-liked');
          var next = liked ? (current + 1) : Math.max(0, current - 1);
          countSpan.textContent = String(next);
          countSpan.classList.toggle('is-zero', next === 0);
        }
      }
    }).catch(function () {
      if (window.appShowFlash) {
        window.appShowFlash({ type: 'error', title: 'Oops!', message: 'Could not update like. Please try again.' });
      }
    }).finally(function () {
      submitBtn.disabled = false;
    });
  }

  document.addEventListener('submit', function (ev) {
    var form = ev.target;
    if (form && form.classList && form.classList.contains('like-form')) {
      handleLikeSubmit(form, ev);
    }
  }, true);

  document.addEventListener('click', function (ev) {
    var btn = ev.target.closest('.like-form button[type="submit"]');
    if (btn && btn.form) {
      ev.preventDefault();
      ev.stopPropagation();
      handleLikeSubmit(btn.form, ev);
    }
  }, true);

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

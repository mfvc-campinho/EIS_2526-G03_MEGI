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

    window.appShowFlash = createFlash;

    triggers.forEach(function (btn) {
        btn.addEventListener('click', function () {
            createFlash({
                type: 'error',
                title: 'Oops!',
                message: 'Log in to like this collection.',
            });
        });
    });
})();

gcInitScrollRestore({
    key: 'gc-scroll-all-collections',
    formSelector: '#filters',
    reapplyFrames: 3,
    reinforceMs: 800,
    reinforceInterval: 80,
    stabilizeMs: 1200
});

window.toggleMyCollections = function (form, enable) {
    if (!form) {
        return;
    }
    var input = form.querySelector('input[name="mine"]');
    if (input) {
        input.value = enable ? '1' : '0';
    }
    window.gcSubmitWithScroll(form);
};

document.querySelectorAll('.like-form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var formData = new FormData(form);
        fetch('likes_action.php', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) {
            if (!res.ok) throw new Error('failed');
            return res.json();
        }).then(function (payload) {
            if (!payload.ok) throw new Error(payload.error || 'failed');
            var button = form.querySelector('button');
            var icon = button.querySelector('i');
            var countEl = form.querySelector('.like-count');
            var current = countEl ? parseInt(countEl.textContent || '0', 10) : 0;
            var likedNow = payload.liked === true || (payload.liked === null ? !button.classList.contains('is-liked') : payload.liked);
            if (likedNow) {
                button.classList.add('is-liked');
                icon.classList.remove('bi-heart');
                icon.classList.add('bi-heart-fill');
                if (countEl) {
                    countEl.textContent = (current + 1).toString();
                    countEl.classList.toggle('is-zero', false);
                }
            } else {
                button.classList.remove('is-liked');
                icon.classList.remove('bi-heart-fill');
                icon.classList.add('bi-heart');
                if (countEl) {
                    var next = Math.max(0, current - 1);
                    countEl.textContent = next.toString();
                    countEl.classList.toggle('is-zero', next === 0);
                }
            }
        }).catch(function (err) {
            console.error(err);
            window.location = 'likes_action.php';
        });
    });
});

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

(function (global) {
  'use strict';

  function initScrollRestore(options) {
    options = options || {};
    var scrollKey = String(options.key || 'gc-scroll');
    var formSelector = options.formSelector || null;
    var autoHistory = options.disableAutoHistory !== false;
    var reapplyFrames = Math.max(1, parseInt(options.reapplyFrames || 2, 10));
    var hasStorage = true;
    var pendingY = null;
    var reinforceMs = Math.max(0, parseInt(options.reinforceMs || 0, 10));
    var reinforceInterval = Math.max(30, parseInt(options.reinforceInterval || 60, 10));
    var reinforceTimer = null;
    var stabilizeMs = Math.max(0, parseInt(options.stabilizeMs || 0, 10));
    var stabilizer = null;
    var stabilizeTimer = null;

    try {
      sessionStorage.setItem('__gc_scroll_test', '1');
      sessionStorage.removeItem('__gc_scroll_test');
    } catch (err) {
      hasStorage = false;
    }

    if (autoHistory && 'scrollRestoration' in history) {
      history.scrollRestoration = 'manual';
    }

    function currentOffset() {
      return window.pageYOffset || document.documentElement.scrollTop || 0;
    }

    function saveScroll() {
      if (hasStorage) {
        sessionStorage.setItem(scrollKey, String(currentOffset()));
      }
    }

    function applyScrollPosition(y, framesLeft) {
      if (framesLeft <= 0) {
        return;
      }
      requestAnimationFrame(function () {
        window.scrollTo(0, y);
        document.documentElement.scrollTop = y;
        document.body.scrollTop = y;
        applyScrollPosition(y, framesLeft - 1);
      });
    }

    function startReinforcement(y) {
      if (reinforceMs <= 0) {
        return;
      }
      if (reinforceTimer) {
        clearInterval(reinforceTimer);
      }
      var deadline = Date.now() + reinforceMs;
      reinforceTimer = setInterval(function () {
        applyScrollPosition(y, reapplyFrames);
        if (Date.now() >= deadline) {
          clearInterval(reinforceTimer);
          reinforceTimer = null;
        }
      }, reinforceInterval);
    }

    function startStabilizer(y) {
      if (stabilizeMs <= 0 || typeof MutationObserver !== 'function') {
        return;
      }
      if (stabilizer) {
        stabilizer.disconnect();
      }
      if (stabilizeTimer) {
        clearTimeout(stabilizeTimer);
      }
      stabilizer = new MutationObserver(function () {
        applyScrollPosition(y, reapplyFrames);
      });
      var target = document.body || document.documentElement;
      if (target) {
        stabilizer.observe(target, { childList: true, subtree: true, attributes: false });
        stabilizeTimer = setTimeout(function () {
          if (stabilizer) {
            stabilizer.disconnect();
            stabilizer = null;
          }
          stabilizeTimer = null;
        }, stabilizeMs);
      }
    }

    function restoreScroll() {
      if (!hasStorage) {
        return;
      }
      var stored = sessionStorage.getItem(scrollKey);
      if (stored === null) {
        return;
      }
      sessionStorage.removeItem(scrollKey);
      var y = parseFloat(stored);
      if (!isFinite(y)) {
        pendingY = null;
        return;
      }
      pendingY = y;
      applyScrollPosition(y, reapplyFrames);
      startReinforcement(y);
      startStabilizer(y);
    }

    function submitWithScroll(form) {
      saveScroll();
      if (form && typeof form.submit === 'function') {
        form.submit();
      }
    }

    function rememberScroll(url) {
      saveScroll();
      if (url) {
        window.location = url;
      }
    }

    global.gcSubmitWithScroll = submitWithScroll;
    global.gcRememberScroll = rememberScroll;

    if (formSelector) {
      var forms = typeof formSelector === 'string'
        ? document.querySelectorAll(formSelector)
        : formSelector;
      if (forms && typeof forms.forEach === 'function') {
        forms.forEach(function (form) {
          form.addEventListener('submit', saveScroll);
        });
      }
    }

    restoreScroll();

    window.addEventListener('load', function () {
      if (pendingY !== null) {
        applyScrollPosition(pendingY, reapplyFrames);
        startReinforcement(pendingY);
        startStabilizer(pendingY);
      }
    });

    window.addEventListener('pageshow', function (event) {
      if (event && event.persisted) {
        restoreScroll();
      }
    });

    return {
      save: saveScroll,
      restore: restoreScroll,
      submitWithScroll: submitWithScroll,
      rememberScroll: rememberScroll
    };
  }

  global.gcInitScrollRestore = initScrollRestore;
})(window);

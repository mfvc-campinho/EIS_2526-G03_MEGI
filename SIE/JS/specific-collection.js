(function initSpecificCollection() {
  var data = {};
  var dataEl = document.getElementById('specific-collection-data');
  if (dataEl) {
    try {
      data = JSON.parse(dataEl.textContent || '{}') || {};
    } catch (err) {
      data = {};
    }
  }

  var scrollKey = data.scrollKey || 'gc-scroll-specific';
  var filtersForm = document.getElementById('filters');
  var likesAction = data.likesAction || 'likes_action.php';

  var hasStorage = false;
  try {
    sessionStorage.setItem('__gc_test', '1');
    sessionStorage.removeItem('__gc_test');
    hasStorage = true;
  } catch (err) {
    hasStorage = false;
  }

  function saveScroll() {
    if (!hasStorage) return;
    var top = window.scrollY || document.documentElement.scrollTop || 0;
    sessionStorage.setItem(scrollKey, String(top));
  }

  window.gcSubmitWithScroll = function(form) {
    saveScroll();
    form.submit();
  };

  window.gcRememberScroll = function(url) {
    saveScroll();
    window.location = url;
  };

  window.addEventListener('pageshow', function() {
    if (!hasStorage) return;
    var stored = sessionStorage.getItem(scrollKey);
    if (stored !== null) {
      window.scrollTo(0, parseFloat(stored));
      sessionStorage.removeItem(scrollKey);
    }
  });

  if (filtersForm) {
    filtersForm.addEventListener('submit', saveScroll);
  }

  document.querySelectorAll('.like-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      var formData = new FormData(form);
      fetch(likesAction, {
        method: 'POST',
        body: formData
      }).then(function() {
        var button = form.querySelector('button');
        var icon = button.querySelector('i');
        var likeCount = button.textContent.trim();
        var currentCount = parseInt(likeCount, 10) || 0;

        if (button.classList.contains('success')) {
          button.classList.remove('success');
          icon.classList.remove('bi-heart-fill');
          icon.classList.add('bi-heart');
          button.innerHTML = '<i class="bi bi-heart"></i> ' + Math.max(0, currentCount - 1);
        } else {
          button.classList.add('success');
          icon.classList.remove('bi-heart');
          icon.classList.add('bi-heart-fill');
          button.innerHTML = '<i class="bi bi-heart-fill"></i> ' + (currentCount + 1);
        }
      });
    });
  });
})();

// Toggle plus/minus icon for preview expand/collapse
(function () {
  document.querySelectorAll('[data-toggle]').forEach(function (btn) {
    var toggleId = btn.getAttribute('data-toggle');
    var toggle = document.getElementById(toggleId);
    var icon = document.getElementById('icon-' + toggleId);
    if (!toggle || !icon) return;
    function updateIcon() {
      if (toggle.checked) {
        icon.classList.remove('bi-plus-lg');
        icon.classList.add('bi-dash-lg');
      } else {
        icon.classList.remove('bi-dash-lg');
        icon.classList.add('bi-plus-lg');
      }
    }
    btn.addEventListener('click', function () {
      toggle.checked = !toggle.checked;
      updateIcon();
    });
    updateIcon();
  });
})();
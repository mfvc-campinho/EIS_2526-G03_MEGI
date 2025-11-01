(() => {
  const INDICATOR_SELECTOR = '#user-indicator';
  const CHANGE_STATE_ID = 'goto-change-state-button';
  const DEFAULT_LABEL = 'Guest';

  function getStore() {
    return window.usersStore ?? null;
  }

  function getCurrentUser() {
    const store = getStore();
    const userId = sessionStorage.getItem('userId');

    if (!store || !userId) {
      return null;
    }
    return store.getById?.(userId) ?? null;
  }

  function fallbackRoleLabel() {
    const role = sessionStorage.getItem('userRole');
    if (role === 'C') {
      return 'Collector';
    }
    if (role === 'G') {
      return DEFAULT_LABEL;
    }
    return DEFAULT_LABEL;
  }

  function updateIndicator() {
    const indicator = document.querySelector(INDICATOR_SELECTOR);
    if (!indicator) {
      return;
    }

    const user = getCurrentUser();
    if (user?.name) {
      indicator.textContent = user.name;
      return;
    }

    indicator.textContent = fallbackRoleLabel();
  }

  function wireChangeStateButton() {
    const button = document.getElementById(CHANGE_STATE_ID);
    if (!button) {
      return;
    }

    button.addEventListener('click', () => {
      window.location.href = 'index.html';
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    updateIndicator();
    wireChangeStateButton();
  });

  window.userStateUi = {
    refresh: updateIndicator,
    getCurrentUser
  };
})();

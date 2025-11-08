document.addEventListener('DOMContentLoaded', () => {
  const userStateUi = window.userStateUi;
  if (userStateUi?.refresh) {
    userStateUi.refresh();
    return;
  }

  const userIndicator = document.getElementById('user-indicator');
  if (!userIndicator) {
    return;
  }

  const userRole = sessionStorage.getItem('userRole');
  userIndicator.textContent = userRole === 'C' ? 'Collector' : 'Guest';
});

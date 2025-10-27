
document.addEventListener('DOMContentLoaded', () => {
  const userIndicator = document.getElementById('user-indicator');
  const userRole = sessionStorage.getItem('userRole');
  if (userIndicator) {
    if (userRole === 'collector') {
      userIndicator.textContent = 'Collector';
    } else {
      userIndicator.textContent = 'Guest';
    }
  }
});
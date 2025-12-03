// home-stats.js
// Populate the Site Statistics cards on the Home page using appData or localStorage
(function () {
  function fmt(n) {
    if (n === null || n === undefined) return '-';
    try {
      return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    } catch (e) { return String(n); }
  }

  function readDataset() {
    if (window.appData && typeof window.appData.loadData === 'function') {
      try { return window.appData.loadData(); } catch (e) { console.warn('home-stats: appData.loadData failed', e); }
    }
    try {
      const raw = window.localStorage && window.localStorage.getItem('collectionsData');
      return raw ? JSON.parse(raw) : null;
    } catch (e) { console.warn('home-stats: unable to parse localStorage', e); }
    return null;
  }

  function updateStats() {
    const data = readDataset() || {};
    const users = Array.isArray(data.users) ? data.users.length : (data.userCount || 0);
    const events = Array.isArray(data.events) ? data.events.length : (data.eventCount || 0);
    const collections = Array.isArray(data.collections) ? data.collections.length : (data.collectionCount || 0);
    const items = Array.isArray(data.items) ? data.items.length : (data.itemCount || 0);

    const elUsers = document.getElementById('stat-total-users');
    const elEvents = document.getElementById('stat-total-events');
    const elCollections = document.getElementById('stat-total-collections');
    const elItems = document.getElementById('stat-total-items');

    if (elUsers) elUsers.textContent = fmt(users);
    if (elEvents) elEvents.textContent = fmt(events);
    if (elCollections) elCollections.textContent = fmt(collections);
    if (elItems) elItems.textContent = fmt(items);
  }

  // Update on DOMContentLoaded and also on custom events that indicate data changed.
  document.addEventListener('DOMContentLoaded', () => {
    updateStats();

    // Listen to storage changes (if another tab updates data) and update
    window.addEventListener('storage', (e) => {
      if (e.key === 'collectionsData') {
        updateStats();
      }
    });

    // If the app dispatches a custom event after data changes, handle it
    window.addEventListener('appDataChanged', () => { updateStats(); });

    // If appData uses events like 'productRatingChange' or 'userFollowChange', they may indicate changes
    window.addEventListener('productRatingChange', () => { updateStats(); });
    window.addEventListener('userFollowChange', () => { updateStats(); });
  });

})();

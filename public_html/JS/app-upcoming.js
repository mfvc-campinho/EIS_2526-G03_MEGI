// app-upcoming.js
// Populates the #upcomingEvents section with the upcoming events defined in collectionsData.events
(function () {
  const container = document.getElementById('upcomingEvents');
  if (!container) return;

  const sourceData = (window.appData && typeof window.appData.loadData === 'function')
    ? window.appData.loadData()
    : (typeof collectionsData !== 'undefined' ? collectionsData : null);

  const dataEvents = (sourceData && Array.isArray(sourceData.events)) ? sourceData.events.slice() : [];

  if (!dataEvents.length) {
    container.innerHTML = '<p class="no-events">No events available. <a href="event_page.html">See all events</a></p>';
    return;
  }

  // Today's date (midnight) for comparison (date-only)
  const today = new Date();
  const todayMidnight = new Date(today.getFullYear(), today.getMonth(), today.getDate());

  const upcoming = dataEvents
    .map(e => ({ ...e, dateObj: new Date(e.date) }))
    .filter(e => !isNaN(e.dateObj) && e.dateObj >= todayMidnight)
    .sort((a, b) => a.dateObj - b.dateObj)
    .slice(0, 3);

  if (!upcoming.length) {
    container.innerHTML = '<p class="no-events">No upcoming events scheduled. <a href="event_page.html">See all events</a></p>';
    return;
  }

  const frag = document.createDocumentFragment();

  upcoming.forEach(ev => {
    const card = document.createElement('article');
    card.className = 'event-card';

    const title = document.createElement('h3');
    title.className = 'event-title';
    title.textContent = ev.name || 'Untitled Event';

    const meta = document.createElement('div');
    meta.className = 'event-meta';
    meta.textContent = `${formatDate(ev.date)} • ${ev.localization || ev.location || 'TBA'}`;

    const summary = document.createElement('p');
    summary.className = 'event-summary';
    summary.textContent = ev.summary || '';

    const link = document.createElement('a');
    link.className = 'event-link';
    // Open event page anchored by id when available
    link.href = `event_page.html#${ev.id || ''}`;
    link.textContent = 'Details';

    card.appendChild(title);
    card.appendChild(meta);
    card.appendChild(summary);
    card.appendChild(link);

    frag.appendChild(card);
  });

  container.appendChild(frag);

  function formatDate(d) {
    try {
      const dt = new Date(d);
      return dt.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
    } catch (err) {
      return d;
    }
  }
})();

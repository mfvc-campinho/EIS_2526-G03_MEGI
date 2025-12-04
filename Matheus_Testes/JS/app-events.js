// ===============================================
// File: public_html/JS/app-events.js
// Purpose: Manage event rendering, filtering, modals, RSVP and rating UX on the Events page.
// Major blocks: element selectors, helpers (date parsing, filtering), rendering, detail modal & rating, edit/create/delete, RSVP, event listeners, initialization.
// Notes: Uses window.appData where available; keeps data changes simulated or delegated to appData.
// ===============================================

// Log any uncaught errors to help diagnose missing event rendering (also show alert so it's visible)
window.addEventListener("error", (e) => {
  try {
    console.error("Global JS error", e.message, e.filename, e.lineno, e.colno, e.error);
    alert("Erro de JavaScript: " + e.message + " (" + e.filename + ":" + e.lineno + ")");
  } catch (_) { }
});

document.addEventListener("DOMContentLoaded", () => {
  // ---------- ELEMENTS ----------
  const DEFAULT_OWNER_ID = "collector-main";
  const urlParams = new URLSearchParams(window.location.search);
  let deepLinkEventId = urlParams.get("id");
  const deepLinkReturnUrl = urlParams.get("returnUrl");
  const shouldOpenNewEventModal = urlParams.get("newEvent") === "true";
  const collectionIdParam = urlParams.get("collectionId");
  let deepLinkHandled = false;
  const sessionState = window.demoEventsState || (window.demoEventsState = {});
  const voteState = sessionState.voteState || (sessionState.voteState = {});
  let likesByEventMap = {};
  let ownerLikesMap = {};
  let editModalReturnUrl = null;

  const sessionRatings = {};
  let modalReturnUrl = null;
  const eventsList = document.getElementById("eventsList");
  const newEventBtn = document.getElementById("newEventBtn");
  const eventsPaginationControls = Array.from(document.querySelectorAll('[data-pagination-for="eventsList"]'));
  const hasEventsPagination = eventsPaginationControls.length > 0;
  const defaultEventsPageSize = hasEventsPagination ? getInitialPageSizeFromControls(eventsPaginationControls) : null;
  const eventsPaginationState = hasEventsPagination
    ? { pageSize: defaultEventsPageSize, pageIndex: 0 }
    : null;

  // Tabs + counts
  const tabUpcoming = document.getElementById("tabUpcoming");
  const tabPast = document.getElementById("tabPast");
  const upcomingCountEl = document.getElementById("upcomingCount");
  const pastCountEl = document.getElementById("pastCount");

  // Hidden selects used for filtering
  const filterSelect = document.getElementById("eventFilter");
  const typeSelect = document.getElementById("eventType");
  const locationSelect = document.getElementById("eventLocation");
  const sortSelect = document.getElementById("eventSort");

  // Detail modal elements
  const eventDetailModal = document.getElementById("event-modal");
  const eventDetailClose = document.getElementById("close-event-modal");
  const modalTitleEl = document.getElementById("modal-event-title");
  const modalMetaEl = document.getElementById("modal-event-meta");
  const modalDescriptionEl = document.getElementById("modal-event-description");
  const modalHostEl = document.getElementById("modal-event-host");
  const modalAttendeesCountEl = document.getElementById("modal-event-attendees-count");
  const modalRsvpBtn = document.getElementById("modal-rsvpBtn");
  const modalShareBtn = document.getElementById("modal-shareEventBtn");
  const modalCloseBtn = document.getElementById("modal-closeEventBtn");

  const modalRatingInfo = document.getElementById("modal-rating-info");
  const modalRatingLabel = document.getElementById("modal-rating-label");
  const modalRatingStars = document.getElementById("modal-rating-stars");
  const modalAddCalendar = document.getElementById('modal-add-calendar');

  // Calendar day modal elements (shows events for a particular day)
  const calendarDayModal = document.getElementById('calendar-day-modal');
  const calendarDayTitle = document.getElementById('calendar-day-title');
  const calendarDayList = document.getElementById('calendar-day-list');
  const closeCalendarDayModal = document.getElementById('close-calendar-day-modal');
  const calendarDayClose = document.getElementById('calendar-day-close');

  // Edit/Create modal elements
  const eventEditModal = document.getElementById("event-edit-modal");
  const eventEditClose = document.getElementById("close-event-edit-modal");
  const form = document.getElementById("form-event");
  const fieldId = document.getElementById("event-id");
  const fieldName = document.getElementById("evt-name");
  const fieldDate = document.getElementById("evt-date");
  const fieldLocation = document.getElementById("evt-location");
  const fieldSummary = document.getElementById("evt-summary");
  const fieldDescription = document.getElementById("evt-description");
  const fieldType = document.getElementById("evt-type");
  const fieldCollections = document.getElementById("evt-collections");
  const cancelEditBtn = document.getElementById("cancel-event-edit");

  let locationFilterPopulated = false;
  function toLocalDateTimeString(date) {
    const pad = (value) => String(value).padStart(2, "0");
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
  }
  if (fieldDate) {
    const now = new Date();
    fieldDate.setAttribute("min", toLocalDateTimeString(now));
  }

  // ---------- HELPERS ----------

  const DATA_STORAGE_KEY = "collectionsData";
  let inMemoryCollectionsData = null;

  function loadData() {
    if (inMemoryCollectionsData) return inMemoryCollectionsData;
    if (window.appData && typeof window.appData.loadData === "function") {
      try {
        const data = window.appData.loadData();
        inMemoryCollectionsData = data || { events: [], collections: [], users: [] };
        return inMemoryCollectionsData;
      } catch (e) { /* ignore */ }
    }
    inMemoryCollectionsData = { events: [], collections: [], users: [] };
    return inMemoryCollectionsData;
  }

  async function refreshDataFromServer() {
    try {
      const res = await fetch('../PHP/get_all.php', { cache: 'no-store' });
      if (!res.ok) throw new Error('get_all.php returned ' + res.status);
      const json = await res.json().catch(() => null);
      if (!json || typeof json !== "object") throw new Error('Invalid JSON from get_all.php');
      inMemoryCollectionsData = json;
      if (window.appData) {
        try {
          window.appData.loadData = () => inMemoryCollectionsData;
          if (typeof window.appData.saveData === "function") {
            window.appData.saveData(inMemoryCollectionsData);
          }
        } catch (e) { console.warn('appData sync failed', e); }
      }
      return json;
    } catch (e) {
      console.error('Failed to refresh data from server', e);
      throw e;
    }
  }

  function persistCollectionsData(data) {
    if (!data) return false;
    inMemoryCollectionsData = data;
    return true;
  }

  function getInitialPageSizeFromControls(controls) {
    for (const ctrl of controls) {
      const select = ctrl.querySelector("[data-page-size]");
      if (!select) continue;
      const parsed = parseInt(select.value, 10);
      if (!Number.isNaN(parsed) && parsed > 0) {
        return parsed;
      }
    }
    return 10;
  }

  function syncEventsPageSizeSelects(value) {
    if (!hasEventsPagination) return;
    eventsPaginationControls.forEach(ctrl => {
      const select = ctrl.querySelector("[data-page-size]");
      if (select) {
        select.value = String(value);
      }
    });
  }

  function updateEventsPaginationUI(total, start = 0, shown = 0) {
    if (!hasEventsPagination) return;
    const totalSafe = Math.max(total || 0, 0);
    const shownSafe = Math.max(Math.min(shown || 0, totalSafe), 0);
    const startSafe = totalSafe === 0 ? 0 : Math.min(Math.max(start || 0, 0), Math.max(totalSafe - 1, 0));
    const rangeStart = totalSafe === 0 || shownSafe === 0 ? 0 : startSafe + 1;
    const rangeEnd = totalSafe === 0 || shownSafe === 0 ? 0 : startSafe + shownSafe;
    const effectiveSize = eventsPaginationState ? Math.max(eventsPaginationState.pageSize || defaultEventsPageSize || 1, 1) : 1;
    const hasResults = totalSafe > 0;
    const totalPages = totalSafe === 0 ? 0 : Math.ceil(totalSafe / effectiveSize);
    const currentPage = eventsPaginationState ? eventsPaginationState.pageIndex : 0;
    const atStart = !totalSafe || currentPage <= 0;
    const atEnd = !totalSafe || currentPage >= Math.max(totalPages - 1, 0);
    eventsPaginationControls.forEach(ctrl => {
      const status = ctrl.querySelector("[data-pagination-status]");
      if (status) {
        status.textContent = `Showing ${rangeStart}-${rangeEnd} of ${totalSafe}`;
      }
      const prevBtn = ctrl.querySelector("[data-page-prev]");
      if (prevBtn) {
        prevBtn.disabled = atStart;
        prevBtn.setAttribute("aria-disabled", atStart ? "true" : "false");
        prevBtn.classList.toggle("disabled", atStart);
      }
      const nextBtn = ctrl.querySelector("[data-page-next]");
      if (nextBtn) {
        nextBtn.disabled = atEnd;
        nextBtn.setAttribute("aria-disabled", atEnd ? "true" : "false");
        nextBtn.classList.toggle("disabled", atEnd);
      }
      const actions = ctrl.querySelector(".pagination-actions");
      if (actions) {
        actions.hidden = !hasResults;
      }
    });
  }

  function initEventsPaginationControls() {
    if (!hasEventsPagination || !eventsPaginationState) return;
    syncEventsPageSizeSelects(eventsPaginationState.pageSize);
    eventsPaginationControls.forEach(ctrl => {
      const select = ctrl.querySelector("[data-page-size]");
      if (select) {
        select.addEventListener("change", event => {
          const next = parseInt(event.target.value, 10);
          if (Number.isNaN(next) || next <= 0) return;
          eventsPaginationState.pageSize = next;
          eventsPaginationState.pageIndex = 0;
          syncEventsPageSizeSelects(next);
          renderEvents();
        });
      }
      const prevBtn = ctrl.querySelector("[data-page-prev]");
      if (prevBtn) {
        prevBtn.addEventListener("click", () => {
          if (eventsPaginationState.pageIndex > 0) {
            eventsPaginationState.pageIndex -= 1;
            renderEvents();
          }
        });
      }
      const nextBtn = ctrl.querySelector("[data-page-next]");
      if (nextBtn) {
        nextBtn.addEventListener("click", () => {
          eventsPaginationState.pageIndex += 1;
          renderEvents();
        });
      }
    });
  }

  function clearDeepLinkParams() {
    const currentUrl = new URL(window.location.href);
    let modified = false;
    if (currentUrl.searchParams.has("id")) {
      currentUrl.searchParams.delete("id");
      modified = true;
    }
    if (currentUrl.searchParams.has("returnUrl")) {
      currentUrl.searchParams.delete("returnUrl");
      modified = true;
    }
    if (modified) {
      history.replaceState(null, "", currentUrl.toString());
    }
  }

  function decodeReturnUrl(raw) {
    if (!raw) return null;
    try {
      const decoded = decodeURIComponent(raw);
      return decoded.length ? decoded : null;
    } catch {
      return raw;
    }
  }

  function clearNewEventParam() {
    const currentUrl = new URL(window.location.href);
    if (!currentUrl.searchParams.has("newEvent")) return;
    currentUrl.searchParams.delete("newEvent");
    history.replaceState(null, "", currentUrl.toString());
  }

  function handleNewEventParam() {
    if (!shouldOpenNewEventModal) return;
    const user = getCurrentUser();
    if (!user?.active) {
      clearNewEventParam();
      return;
    }

    const candidateReturnUrl =
      decodeReturnUrl(deepLinkReturnUrl) ||
      (document.referrer && document.referrer.length ? document.referrer : null);
    if (candidateReturnUrl) {
      editModalReturnUrl = candidateReturnUrl;
    }
    openEditModal(null);
    clearNewEventParam();
  }

  function saveEntityUpdate(id, patch) {
    if (window.appData && typeof window.appData.updateEntity === "function") {
      window.appData.updateEntity("events", id, patch);
      return true;
    } else {
      // fallback: localStorage direct update (if needed)
      const data = loadData();
      const idx = (data.events || []).findIndex(e => e.id === id);
      if (idx !== -1) {
        data.events[idx] = { ...data.events[idx], ...patch };
        return persistCollectionsData(data);
      }
    }
    return false;
  }

  function addEntity(ev) {
    if (window.appData && typeof window.appData.addEntity === "function") {
      window.appData.addEntity("events", ev);
      return true;
    } else {
      const data = loadData();
      data.events = data.events || [];
      data.events.push(ev);
      return persistCollectionsData(data);
    }
  }

  function deleteEntity(id) {
    if (window.appData && typeof window.appData.deleteEntity === "function") {
      window.appData.deleteEntity("events", id);
      return true;
    } else {
      const data = loadData();
      data.events = (data.events || []).filter(e => e.id !== id);
      return persistCollectionsData(data);
    }
  }

  function todayStart() {
    const d = new Date();
    d.setHours(0, 0, 0, 0);
    return d;
  }

  function parseEventDate(dateStr) {
    if (!dateStr) return null;
    const d = new Date(dateStr);
    if (!isNaN(d)) return d;
    const tryD = new Date(dateStr + "T00:00:00");
    return isNaN(tryD) ? null : tryD;
  }

  function isUpcoming(dateStr) {
    const d = parseEventDate(dateStr);
    if (!d) return false;
    return d >= todayStart();
  }

  function isPastEvent(dateStr) {
    const d = parseEventDate(dateStr);
    if (!d) return false;
    return d < todayStart();
  }

  function formatDate(dateStr) {
    const d = parseEventDate(dateStr);
    if (!d) return "N/A";
    return d.toLocaleString();
  }

  function formatDateShort(dateStr) {
    const d = parseEventDate(dateStr);
    if (!d) return "N/A";
    return d.toLocaleDateString();
  }

  function formatDateHuman(dateStr) {
    const d = parseEventDate(dateStr);
    if (!d) return "Date to be announced";
    return d.toLocaleDateString(undefined, {
      weekday: "long",
      year: "numeric",
      month: "short",
      day: "numeric"
    });
  }

  // -----------------------------
  // Calendar export helper
  // -----------------------------
  function addEventToCalendar(eventData) {
    if (!eventData || !eventData.name || !eventData.date) return;

    const start = parseEventDate(eventData.date);
    if (!start) { notify('Invalid event date', 'error'); return; }

    // determine end time: provided or default 2 hours
    let end = null;
    if (eventData.endDate) {
      end = parseEventDate(eventData.endDate) || null;
    }
    if (!end) end = new Date(start.getTime() + (eventData.durationMinutes ? eventData.durationMinutes * 60000 : 2 * 60 * 60 * 1000));

    function toICSDate(d) {
      // return UTC in YYYYMMDDTHHMMSSZ
      return d.toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z';
    }

    const uid = `${Date.now()}@goodcollections.local`;
    const dtstamp = toICSDate(new Date());
    const dtstart = toICSDate(start);
    const dtend = toICSDate(end);

    const summary = (eventData.name || '').replace(/\n/g, ' ');
    const description = (eventData.description || '').replace(/\n/g, '\\n');
    const location = eventData.location || '';

    const icsLines = [
      'BEGIN:VCALENDAR',
      'VERSION:2.0',
      'PRODID:-//GoodCollections//EN',
      'CALSCALE:GREGORIAN',
      'BEGIN:VEVENT',
      `UID:${uid}`,
      `DTSTAMP:${dtstamp}`,
      `DTSTART:${dtstart}`,
      `DTEND:${dtend}`,
      `SUMMARY:${summary}`,
      `DESCRIPTION:${description}`,
      `LOCATION:${location}`,
      'END:VEVENT',
      'END:VCALENDAR'
    ];

    const icsContent = icsLines.join('\r\n');

    // trigger download
    const blob = new Blob([icsContent], { type: 'text/calendar;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const safeName = (eventData.name || 'event').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    const a = document.createElement('a');
    a.href = url;
    a.download = `${safeName}.ics`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 2000);

    // open Google Calendar link in new tab (user can choose)
    const gStart = dtstart.replace(/Z$/, 'Z');
    const gEnd = dtend.replace(/Z$/, 'Z');
    const gDates = `${gStart}/${gEnd}`;
    const gUrl = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${encodeURIComponent(summary)}&details=${encodeURIComponent(eventData.description || '')}&location=${encodeURIComponent(location)}&dates=${encodeURIComponent(gDates)}`;
    try { window.open(gUrl, '_blank'); } catch (e) { /* ignore */ }
  }

  function populateLocationFilterOptions(events = []) {
    if (!locationSelect || locationFilterPopulated) return;
    const uniqueLocations = new Set();
    events.forEach(ev => {
      if (ev.localization) uniqueLocations.add(ev.localization);
    });
    if (!uniqueLocations.size) return;
    uniqueLocations.forEach(loc => {
      const opt = document.createElement("option");
      opt.value = loc.toLowerCase();
      opt.textContent = loc;
      locationSelect.appendChild(opt);
    });
    locationFilterPopulated = true;
  }

  function escapeHtml(str) {
    if (!str) return "";
    return String(str).replace(/[&<>\"']/g, s => ({
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      "\"": "&quot;",
      "'": "&#39;"
    })[s]);
  }

  function getCurrentUser() {
    try {
      return JSON.parse(localStorage.getItem("currentUser")) || null;
    } catch {
      return null;
    }
  }

  function isLoggedIn() {
    const u = getCurrentUser();
    return !!(u && u.active);
  }

  function getActiveOwnerId(user = getCurrentUser()) {
    if (!user || !user.active) return null;
    return user.id || DEFAULT_OWNER_ID;
  }

  function getEventOwnerIds(eventId, data) {
    if (!eventId || !data) return [];
    const collectionLinks = data.collectionEvents || [];
    if (!collectionLinks.length) return [];

    const collectionIds = collectionLinks
      .filter(link => link.eventId === eventId)
      .map(link => link.collectionId);

    if (!collectionIds.length) return [];

    return collectionIds
      .map(colId => appData.getCollectionOwnerId(colId, data))
      .filter(Boolean);
  }

  function getEventCollectionIds(eventId, data) {
    if (window.appData?.getEventCollectionIds) {
      return window.appData.getEventCollectionIds(eventId, data);
    }
    if (!eventId) return [];
    const dataset = data || loadData();
    const links = dataset.collectionEvents || [];
    return Array.from(new Set(
      links
        .filter(link => link.eventId === eventId)
        .map(link => link.collectionId)
        .filter(Boolean)
    ));
  }

  function getAllCollections(data) {
    const dataset = data || loadData();
    return Array.isArray(dataset?.collections) ? dataset.collections : [];
  }

  function getDefaultCollectionSelection(data) {
    const collections = getAllCollections(data);
    if (!collectionIdParam) return [];
    if (collections.some(col => col.id === collectionIdParam)) {
      return [collectionIdParam];
    }
    return [];
  }

  function filterCollectionsByOwner(collections, ownerId, data) {
    if (!ownerId || !collections.length) return collections;
    const dataset = data || loadData();
    const ownerLookup = window.appData?.getCollectionOwnerId;
    if (typeof ownerLookup !== "function") return collections;
    return collections.filter(col => ownerLookup(col.id, dataset) === ownerId);
  }

  function populateCollectionSelector(selectedIds = [], options = {}) {
    if (!fieldCollections) return;
    const dataset = options.data || loadData();
    const ownerId = options.ownerId;
    let collections = Array.isArray(options.collections)
      ? options.collections.slice()
      : getAllCollections(dataset);
    collections = filterCollectionsByOwner(collections, ownerId, dataset);

    fieldCollections.innerHTML = "";

    if (!collections.length) {
      const emptyState = document.createElement("p");
      emptyState.className = "form-hint";
      emptyState.textContent = ownerId
        ? "No collections available for your profile"
        : "No collections available";
      fieldCollections.appendChild(emptyState);
      return;
    }

    collections.forEach(col => {
      const label = document.createElement("label");
      label.className = "checkbox-pill";

      const input = document.createElement("input");
      input.type = "checkbox";
      input.name = "evt-collections";
      input.value = col.id;
      input.checked = selectedIds.includes(col.id);

      const span = document.createElement("span");
      span.textContent = col.name || col.id;

      label.appendChild(input);
      label.appendChild(span);
      fieldCollections.appendChild(label);
    });
  }

  function getEventOwnerProfiles(event, data) {
    if (!event) return [{ id: null, name: "Community host" }];
    const dataset = data || loadData();
    const users = Array.isArray(dataset?.users) ? dataset.users : [];
    const ownerIds = getEventOwnerIds(event.id, dataset);
    const uniqueOwnerIds = Array.from(new Set(ownerIds));
    const profiles = uniqueOwnerIds
      .map(id => users.find(u => String(u.id || u.user_id) === String(id)))
      .filter(Boolean)
      .map(u => ({ id: u.id || u.user_id, name: u.user_name || u.name || u.id }));
    return profiles.length ? profiles : [{ id: null, name: "Community host" }];
  }


  // ---------- RENDER EVENTS (fresh implementation) ----------
  function getEventUserLinks(eventOrId, data) {
    const dataset = data || loadData();
    const eventId = typeof eventOrId === "string" ? eventOrId : eventOrId?.id;
    const links = Array.isArray(dataset?.eventsUsers) ? dataset.eventsUsers : [];
    if (!eventId) return [];
    return links.filter(link => String(link.eventId) === String(eventId));
  }

  function userHasRsvp(eventLinks, user) {
    if (!user) return false;
    return eventLinks.some(link => String(link.userId) === String(user.id) && Number(link.rsvp || 0) === 1);
  }

  function getUserRatingFromEntries(eventLinks, user) {
    if (!user) return null;
    const entry = eventLinks.find(link => String(link.userId) === String(user.id) && link.rating !== undefined && link.rating !== null);
    return entry ? Number(entry.rating) : null;
  }

  function getEventRatingStats(eventLinks) {
    const ratings = eventLinks
      .map(link => link.rating)
      .filter(val => val !== null && val !== undefined)
      .map(Number)
      .filter(n => !Number.isNaN(n));
    const count = ratings.length;
    const average = count ? ratings.reduce((a, b) => a + b, 0) / count : null;
    return { count, average };
  }

  function canCurrentUserManageEvent(eventId, data, user = getCurrentUser()) {
    if (!user || !user.active) return false;
    const dataset = data || loadData();
    const ev = (dataset.events || []).find(e => String(e.id) === String(eventId));
    if (!ev) return false;
    if (ev.hostUserId && String(ev.hostUserId) === String(user.id)) return true;
    const collectionIds = getEventCollectionIds(eventId, dataset);
    for (const cid of collectionIds) {
      const ownerId = window.appData?.getCollectionOwnerId
        ? window.appData.getCollectionOwnerId(cid, dataset)
        : (dataset.collections || []).find(c => String(c.id) === String(cid))?.ownerId;
      if (ownerId && String(ownerId) === String(user.id)) return true;
    }
    return false;
  }

  async function renderEvents() {
    try {
      let data = loadData();
      const currentUser = getCurrentUser();
      let events = Array.isArray(data?.events) ? data.events.slice() : [];
      if (!eventsList) {
        console.error("renderEvents: #eventsList not found in DOM");
        return;
      }

      // If we somehow have zero events, force a fresh fetch from PHP (XAMPP) to avoid stale empty local data.
      if (!events.length) {
        try {
          data = await refreshDataFromServer();
          events = Array.isArray(data?.events) ? data.events.slice() : [];
          console.info("renderEvents: fetched fresh data from server", { events: events.length, collections: (data.collections || []).length });
        } catch (e) {
          console.error("renderEvents: refresh fallback failed", e);
        }
      }
      console.debug("renderEvents: dataset size", { events: events.length, collections: (data.collections || []).length, users: (data.users || []).length });

      populateLocationFilterOptions(events);

      const filterMode = filterSelect?.value || "upcoming";
      const upcomingCount = events.filter(ev => isUpcoming(ev.date)).length;
      const pastCount = events.filter(ev => isPastEvent(ev.date)).length;
      if (upcomingCountEl) upcomingCountEl.textContent = upcomingCount;
      if (pastCountEl) pastCountEl.textContent = pastCount;

      let filtered = events.filter(ev => {
        if (filterMode === "upcoming") return isUpcoming(ev.date);
        if (filterMode === "past") return isPastEvent(ev.date);
        return true;
      });

      const typeVal = (typeSelect?.value || "").toLowerCase();
      const locVal = (locationSelect?.value || "").toLowerCase();
      if (typeVal && typeVal !== "all") filtered = filtered.filter(ev => (ev.type || "").toLowerCase() === typeVal);
      if (locVal && locVal !== "all") filtered = filtered.filter(ev => (ev.localization || "").toLowerCase() === locVal);

      const sortVal = sortSelect?.value || "date-asc";
      filtered.sort((a, b) => {
        if (sortVal === "date-desc") return (new Date(b.date)) - (new Date(a.date));
        if (sortVal === "name-asc") return (a.name || "").localeCompare(b.name || "");
        if (sortVal === "name-desc") return (b.name || "").localeCompare(a.name || "");
        return (new Date(a.date)) - (new Date(b.date));
      });

      let start = 0;
      let pageSize = filtered.length;
      if (hasEventsPagination && eventsPaginationState) {
        pageSize = Math.max(eventsPaginationState.pageSize || defaultEventsPageSize || 10, 1);
        start = Math.max(eventsPaginationState.pageIndex || 0, 0) * pageSize;
      }
      const pageItems = filtered.slice(start, start + pageSize);
      updateEventsPaginationUI(filtered.length, start, pageItems.length);

      eventsList.innerHTML = "";
      if (!pageItems.length) {
        const empty = document.createElement("p");
        empty.className = "muted empty-state";
        empty.textContent = "No events available.";
        eventsList.appendChild(empty);
        // surface a warning in console so we can see why nothing rendered
        console.warn("renderEvents: zero events after filtering", {
          total: events.length,
          filterMode,
          typeVal,
          locVal,
          sortVal
        });
        return;
      }

      pageItems.forEach(ev => {
        const isPast = isPastEvent(ev.date);
        const card = document.createElement("article");
        card.className = "card event-card";
        const ownerProfiles = getEventOwnerProfiles(ev, data);
        const ownerDisplayText = ownerProfiles.map(p => escapeHtml(p.name)).join(", ") || "Community host";
        const ownerLinkHref = ownerProfiles[0]?.id
          ? `user_page.html?owner=${encodeURIComponent(ownerProfiles[0].id)}`
          : "user_page.html";

        const eventLinks = getEventUserLinks(ev, data);
        const hasRsvp = userHasRsvp(eventLinks, currentUser);
        const userRating = getUserRatingFromEntries(eventLinks, currentUser);
        const { count: ratingCount, average: ratingAvg } = getEventRatingStats(eventLinks);
        const canManage = canCurrentUserManageEvent(ev.id, data, currentUser);

        let ratingHtml = "";
        if (isPast) {
          const stars = [];
          for (let i = 1; i <= 5; i++) {
            const isOn = userRating && i <= userRating;
            const disabledCls = hasRsvp ? "" : " disabled";
            stars.push(`<span class="star${isOn ? " user-rating" : ""}${disabledCls}" data-value="${i}">*</span>`);
          }
          const summary = ratingAvg
            ? `<div class="rating-line"><span class="muted">${ratingAvg.toFixed(1)}</span> <span class="rating-count">(${ratingCount})</span></div>`
            : `<div class="rating-line"><span class="muted">No ratings yet</span></div>`;
          const note = hasRsvp
            ? (userRating ? `<div class="rating-note">You rated this ${userRating}/5</div>` : `<div class="rating-note muted">RSVP required to rate.</div>`)
            : `<div class="rating-note muted">RSVP required to rate.</div>`;
          ratingHtml = `
            <div class="card-rating">
              <div class="rating-stars${hasRsvp ? "" : " disabled"}" data-event-id="${ev.id}">${stars.join("")}</div>
              <div class="rating-summary">${summary}${note}</div>
            </div>
          `;
        }

        const associatedCollection = (data.collections || []).find(c => String(c.id) === String(ev.collectionId || ev.collection_id));
        const collectionHtml = associatedCollection
          ? `
            <div class="event-meta-row event-collection-row">
              <i class="bi bi-box-seam" aria-hidden="true"></i>
              <span class="event-collection-label">Collection:</span>
              <a class="event-collection-link" href="specific_collection.html?id=${encodeURIComponent(associatedCollection.id)}">
                ${escapeHtml(associatedCollection.name || associatedCollection.id || "Collection")}
              </a>
            </div>
          `
          : "";

        card.innerHTML = `
          <h3 class="card-title">${escapeHtml(ev.name)}</h3>
          <p class="card-summary">${escapeHtml(ev.summary || ev.description || "")}</p>
          <div class="event-meta-row">
            <i class="bi bi-calendar-event-fill" aria-hidden="true"></i>
            <span class="event-meta-label">Date:</span>
            <span>${formatDateShort(ev.date)}</span>
          </div>
          <div class="event-meta-row">
            <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
            <span class="event-meta-label">Location:</span>
            <span>${escapeHtml(ev.localization || "To be announced")}</span>
          </div>
          ${collectionHtml}
          <div class="event-meta-row event-owner-row">
            <i class="bi bi-person-circle" aria-hidden="true"></i>
            <span class="event-owner-label">Owner:</span>
            <a class="event-owner-link" href="${ownerLinkHref}" data-event-id="${ev.id}">
              ${ownerDisplayText}
            </a>
          </div>
          ${ratingHtml}
          <div class="card-actions">
            <button class="view-btn explore-btn ghost" data-id="${ev.id}" type="button">
              <i class="bi bi-eye-fill" aria-hidden="true"></i> View
            </button>
            ${isPast ? "" : `
              <button class="explore-btn ghost rsvp-btn ${hasRsvp ? "following" : ""}" data-id="${ev.id}" data-requires-login>
                <i class="bi ${hasRsvp ? "bi-calendar-check" : "bi-calendar-plus"}" aria-hidden="true"></i> ${hasRsvp ? "Going" : "RSVP"}
              </button>
            `}
            ${canManage ? `
              <button class="edit-btn explore-btn warning" data-id="${ev.id}" data-requires-login>
                <i class="bi bi-pencil-square" aria-hidden="true"></i> Edit
              </button>
              <button class="delete-btn explore-btn danger" data-id="${ev.id}" data-requires-login>
                <i class="bi bi-trash3" aria-hidden="true"></i> Delete
              </button>
            ` : ""}
          </div>
        `;

        const viewBtnEl = card.querySelector(".view-btn");
        viewBtnEl?.addEventListener("click", (e) => { e.preventDefault(); e.stopPropagation(); openEventDetail(ev.id); });
        card.addEventListener("click", (e) => {
          if (e.target.closest && e.target.closest(".view-btn")) return;
          openEventDetail(ev.id);
        });

        const rsvpBtnEl = card.querySelector(".rsvp-btn");
        if (rsvpBtnEl) {
          rsvpBtnEl.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (!isLoggedIn()) {
              notify("Please sign in to RSVP.", "warning");
              return;
            }
            rsvpEvent(ev.id);
          });
        }

        const starsContainer = card.querySelector('.rating-stars[data-event-id="' + ev.id + '"]');
        if (starsContainer && hasRsvp && isPast) {
          Array.from(starsContainer.querySelectorAll(".star")).forEach(s => {
            const val = Number(s.dataset.value);
            s.addEventListener("click", () => setRating(ev.id, val));
            s.addEventListener("keydown", (evKey) => {
              if (evKey.key === "Enter" || evKey.key === " ") {
                evKey.preventDefault();
                setRating(ev.id, val);
              }
            });
            s.setAttribute("tabindex", "0");
            s.setAttribute("role", "button");
            s.setAttribute("aria-label", "Rate " + val + " out of 5");
          });
        }

        eventsList.appendChild(card);
      });

      try { renderCalendar(); } catch (e) { /* calendar optional */ }

      if (!deepLinkHandled && deepLinkEventId) {
        const exists = (data.events || []).some(ev => ev.id === deepLinkEventId);
        if (exists) {
          const ref =
            (deepLinkReturnUrl && deepLinkReturnUrl.length) ? decodeURIComponent(deepLinkReturnUrl) :
              (document.referrer && document.referrer.length ? document.referrer : null);
          openEventDetail(deepLinkEventId, { returnUrl: ref });
          if (!ref) clearDeepLinkParams();
        } else {
          clearDeepLinkParams();
        }
        deepLinkHandled = true;
        deepLinkEventId = null;
      }
    } catch (err) {
      console.error("renderEvents failed", err);
      try { alert("Erro a desenhar eventos: " + (err && err.message ? err.message : err)); } catch (_) { }
    }
  }

  // ---------- DETAIL MODAL & RATING ----------

  function openEventDetail(id, options = {}) {
    console.debug('openEventDetail called with id=', id);
    const data = loadData();
    const ev = (data.events || []).find(x => x.id === id);
    console.debug('loaded events count=', (data.events || []).length, 'found ev=', !!ev);
    if (!ev) { notify("Event not found.", "error"); return; }

    if (!eventDetailModal) return;

    modalTitleEl.textContent = ev.name;
    // Show Date and Location as separate labeled lines for clarity
    modalMetaEl.innerHTML = `
      <div><strong>Date:</strong> ${escapeHtml(formatDate(ev.date))}</div>
      <div><strong>Location:</strong> ${escapeHtml(ev.localization || "To be announced")}</div>
    `;
    modalDescriptionEl.textContent = ev.description || ev.summary || "No description provided.";

    // Host
    const modalOwnerProfiles = getEventOwnerProfiles(ev, data);
    const modalOwnerLabel = modalOwnerProfiles
      .map(profile => profile.name)
      .filter(Boolean)
      .join(", ") || ev.host || "Community host";
    const modalOwnerId = modalOwnerProfiles[0]?.id || null;
    const modalOwnerHref = modalOwnerId
      ? `user_page.html?owner=${encodeURIComponent(modalOwnerId)}`
      : null;
    if (modalHostEl) {
      modalHostEl.textContent = modalOwnerLabel;
      if (modalOwnerHref) {
        modalHostEl.href = modalOwnerHref;
        modalHostEl.removeAttribute("aria-disabled");
        modalHostEl.classList.remove("disabled");
        modalHostEl.tabIndex = 0;
      } else {
        modalHostEl.removeAttribute("href");
        modalHostEl.setAttribute("aria-disabled", "true");
        modalHostEl.classList.add("disabled");
        modalHostEl.tabIndex = -1;
      }
    }

    // Attendees
    const eventLinks = getEventUserLinks(ev, data);
    // Current user (needed for RSVP button and rating state)
    const currentUser = getCurrentUser();
    if (modalAttendeesCountEl) {
      modalAttendeesCountEl.textContent = eventLinks.length;
    }
    const isPast = isPastEvent(ev.date);

    // Collections associated with this event
    try {
      const collectionIds = getEventCollectionIds(ev.id, data) || [];
      const allCols = getAllCollections(data) || [];
      const cols = collectionIds.map(id => allCols.find(c => String(c.id) === String(id))).filter(Boolean);
      const collectionHtml = cols.length
        ? cols.map(c => `<a href="specific_collection.html?id=${encodeURIComponent(c.id)}">${escapeHtml(c.name || c.id)}</a>`).join(', ')
        : '<span class="muted">None</span>';

      const modalBody = document.getElementById('modal-event-body');
      if (modalBody) {
        // remove existing placeholder if present
        const existing = document.getElementById('modal-event-collections');
        if (existing) existing.remove();

        const p = document.createElement('p');
        p.id = 'modal-event-collections';
        p.innerHTML = `<strong>Collection:</strong> ${collectionHtml}`;

        // Insert after host element if available, otherwise append to body
        const hostEl = document.getElementById('modal-event-host');
        if (hostEl && hostEl.parentElement) {
          // find the parent <p> that contains the host link
          const hostParent = hostEl.closest('p');
          if (hostParent && hostParent.nextSibling) hostParent.parentNode.insertBefore(p, hostParent.nextSibling);
          else if (hostParent) hostParent.parentNode.appendChild(p);
          else modalBody.appendChild(p);
        } else {
          modalBody.appendChild(p);
        }
      }
    } catch (e) {
      console.warn('Failed to render event collections', e);
    }

    // Modal RSVP button appearance (use ghost style to match View button)
    if (modalRsvpBtn) {
      const isAttending = Boolean(eventLinks && currentUser && eventLinks.some(link => String(link.userId) === String(currentUser.id)));
      if (isPast) {
        modalRsvpBtn.style.display = 'none';
        modalRsvpBtn.onclick = null;
      } else {
        modalRsvpBtn.style.display = '';
        modalRsvpBtn.className = isAttending ? 'explore-btn ghost following' : 'explore-btn ghost';
        modalRsvpBtn.innerHTML = isAttending ? `<i class="bi bi-calendar-check" aria-hidden="true"></i> Going` : `<i class="bi bi-calendar-plus" aria-hidden="true"></i> RSVP`;
        modalRsvpBtn.onclick = () => rsvpEvent(id);
      }
    }

    // Rating (only for past events)
    const { count, average: avg } = getEventRatingStats(eventLinks);
    const sessionValue = currentUser && currentUser.active ? sessionRatings[ev.id] : undefined;
    const storedUserRating = currentUser ? getUserRatingFromEntries(eventLinks, currentUser) : null;
    const userRating = currentUser && currentUser.active
      ? (sessionValue !== undefined ? sessionValue : storedUserRating ?? null)
      : storedUserRating ?? null;

    modalRatingStars.innerHTML = "";

    if (isPast) {
      // Interactive stars if logged in
      for (let i = 1; i <= 5; i++) {
        const star = document.createElement("span");
        star.textContent = "★";
        star.classList.add("star");

        // Only highlight stars that reflect the current user's rating.
        if (userRating && i <= userRating) {
          star.classList.add("user-rating");
        }

        star.classList.add("clickable");
        star.setAttribute("tabindex", "0");
        star.setAttribute("role", "button");
        star.addEventListener("click", () => setRating(ev.id, i));
        star.addEventListener("keydown", (evKey) => {
          if (evKey.key === "Enter" || evKey.key === " ") {
            evKey.preventDefault();
            setRating(ev.id, i);
          }
        });

        modalRatingStars.appendChild(star);
      }

      if (!currentUser || !currentUser.active) {
        modalRatingLabel.textContent =
          avg
            ? `Average rating: ${avg.toFixed(1)} (${count} rating${count !== 1 ? "s" : ""}). Sign in to rate this event.`
            : "No ratings yet. Sign in after attending to rate this event.";
      } else if (sessionValue !== undefined) {
        modalRatingLabel.textContent =
          `Your rating: ${sessionValue}/5. ` +
          (avg ? `Current average: ${avg.toFixed(1)} (${count}).` : "");
      } else if (userRating) {
        modalRatingLabel.textContent =
          `You rated this event ${userRating}/5. ` +
          (avg ? `Average: ${avg.toFixed(1)} (${count}).` : "");
      } else {
        modalRatingLabel.textContent =
          avg
            ? `Click a star to rate this past event. Current average: ${avg.toFixed(1)} (${count}).`
            : "Click a star to be the first to rate this past event.";
      }
      modalRatingInfo.style.display = "block";
    } else {
      modalRatingInfo.style.display = "block";
      modalRatingLabel.textContent = "Rating will be available after the event date.";
      modalRatingStars.innerHTML = "";
    }

    // RSVP button
    if (modalRsvpBtn) {
      modalRsvpBtn.onclick = () => rsvpEvent(id);
    }

    // (Optional) share button: simple alert for now
    if (modalShareBtn) {
      modalShareBtn.onclick = () => {
        notify("Sharing is currently unavailable.", "info");
      };
    }

    // Add to My Calendar button in modal (only for upcoming events)
    if (modalAddCalendar) {
      if (!isPast) {
        modalAddCalendar.style.display = '';
        modalAddCalendar.onclick = () => {
          addEventToCalendar({
            name: ev.name,
            date: ev.date,
            endDate: ev.endDate || null,
            description: ev.description || ev.summary || '',
            location: ev.localization || ''
          });
        };
      } else {
        modalAddCalendar.style.display = 'none';
        modalAddCalendar.onclick = null;
      }
    }

    modalReturnUrl = options.returnUrl || null;
    eventDetailModal.style.display = "flex";
    if (!modalReturnUrl) {
      const url = new URL(window.location.href);
      url.searchParams.set("id", id);
      history.replaceState(null, "", url.toString());
    }
  }
  // Expose opener to other scripts (e.g., homepage upcoming links)
  try {
    window.openEventDetail = openEventDetail;
  } catch (e) {
    console.warn('Unable to expose openEventDetail globally', e);
  }

  function closeEventDetail() {
    if (eventDetailModal) eventDetailModal.style.display = "none";
    if (modalReturnUrl) {
      const target = modalReturnUrl;
      modalReturnUrl = null;
      window.location.href = target;
      return;
    }
    clearDeepLinkParams();
  }

  function setRating(eventId, value) {
    const user = getCurrentUser();
    if (!user || !user.active) {
      notify("Please sign in to rate events.", "warning");
      return;
    }

    const data = loadData();
    const ev = (data.events || []).find(x => x.id === eventId);
    if (!ev) return;

    if (!isPastEvent(ev.date)) {
      notify("You can only rate past events.", "warning");
      return;
    }

    // Persist rating to server. If user clicks the same star again, remove rating (unrate).
    (async () => {
      try {
        const dataset = loadData();
        const eventLinks = getEventUserLinks(ev, dataset);
        const currentUser = getCurrentUser();
        if (!userHasRsvp(eventLinks, currentUser)) {
          notify("RSVP required to rate this event.", "warning");
          return;
        }
        const storedUserRating = currentUser ? getUserRatingFromEntries(eventLinks, currentUser) : null;
        const sessionValue = currentUser && currentUser.active ? sessionRatings[eventId] : undefined;
        const userRating = currentUser && currentUser.active ? (sessionValue !== undefined ? sessionValue : storedUserRating ?? null) : storedUserRating ?? null;

        const isRemoving = userRating !== null && Number(userRating) === Number(value);
        const action = isRemoving ? 'unrateEvent' : 'rateEvent';

        const bodyParams = new URLSearchParams({ action, eventId });
        if (!isRemoving) bodyParams.append('rating', String(value));

        const resp = await fetch('../PHP/crud/ratings.php', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: bodyParams
        });
        if (resp.status === 401) {
          notify('Not authorized. Please sign in to rate events.', 'warning');
          return;
        }
        const json = await resp.json().catch(() => null);
        if (!json || !json.success) {
          console.warn('Rate event failed', json);
          notify('Unable to save rating. Please try again.', 'error');
          return;
        }

        // Optimistically update session rating so modal updates immediately
        try {
          if (action === 'unrateEvent') {
            delete sessionRatings[eventId];
          } else if (action === 'rateEvent') {
            sessionRatings[eventId] = Number(value);
          }
        } catch (e) { }

        // reload server data and re-render (best-effort)
        try {
          const ga = await fetch('../PHP/get_all.php');
          if (ga.status === 200) {
            const serverData = await ga.json().catch(() => null);
            if (serverData) {
              try { window.appData.saveData(serverData); } catch (e) { localStorage.setItem('collectionsData', JSON.stringify(serverData)); }
            }
          }
        } catch (e) {
          console.warn('Failed to reload server data after rating', e);
        }

        const preserveReturnUrl = modalReturnUrl;
        renderEvents();
        if (eventDetailModal && eventDetailModal.style.display === "flex") {
          openEventDetail(eventId, { returnUrl: preserveReturnUrl });
        }
        try { notify(isRemoving ? 'Your rating has been removed.' : 'Your rating has been saved.', isRemoving ? 'info' : 'success'); } catch (e) { }
      } catch (err) {
        console.error('Rating failed', err);
        notify('Network error saving rating.', 'error');
      }
    })();
  }

  // ---------- EDIT / CREATE EVENT ----------

  function openEditModal(id) {
    const data = loadData();
    const currentUser = getCurrentUser();
    const ownerId = getActiveOwnerId(currentUser);
    let selectionIds = [];

    if (id) {
      const ev = (data.events || []).find(x => x.id === id);
      if (!ev) { notify("Event not found.", "error"); return; }
      if (!canCurrentUserManageEvent(ev.id, data, currentUser)) {
        notify("You can only edit events that belong to your collections.", "error");
        return;
      }

      fieldId.value = ev.id;
      fieldName.value = ev.name || "";

      const d = parseEventDate(ev.date);
      fieldDate.value = d ? d.toISOString().substring(0, 16) : "";

      fieldLocation.value = ev.localization || "";
      fieldSummary.value = ev.summary || "";
      fieldDescription.value = ev.description || "";
      fieldType.value = ev.type || "";

      document.getElementById("event-edit-title").textContent = "Edit Event";
      selectionIds = getEventCollectionIds(ev.id, data);
    } else {
      form.reset();
      fieldId.value = "";
      document.getElementById("event-edit-title").textContent = "Create Event";
      selectionIds = getDefaultCollectionSelection(data);
    }

    populateCollectionSelector(selectionIds, { ownerId, data });
    eventEditModal.style.display = "flex";
  }

  function closeEditModal() {
    if (eventEditModal) eventEditModal.style.display = "none";
    if (editModalReturnUrl) {
      const target = editModalReturnUrl;
      editModalReturnUrl = null;
      window.location.href = target;
      return;
    }
  }

  function generateId(name) {
    const slug = (name || "event")
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, "-")
      .replace(/(^-|-$)/g, "");
    return `${slug}-${Date.now()}`;
  }

  function saveEventFromForm(e) {
    e.preventDefault();

    (async () => {
      const id = (fieldId.value || "").trim();
      const name = fieldName.value.trim();
      const dateVal = fieldDate.value;
      const location = fieldLocation.value.trim();
      const summary = fieldSummary.value.trim();
      const description = fieldDescription.value.trim();
      const type = fieldType.value.trim();
      const selectedCollections = fieldCollections
        ? Array.from(fieldCollections.querySelectorAll('input[type="checkbox"]:checked'))
          .map(input => input.value)
          .filter(Boolean)
        : [];
      if (!selectedCollections.length) {
        notify("Select at least one collection.", "warning");
        return;
      }

      if (!name || !dateVal) {
        notify("Please provide at least a name and date.", "warning");
        return;
      }

      const parsedDate = parseEventDate(dateVal);
      if (!parsedDate) {
        notify("Please provide a valid date.", "warning");
        return;
      }

      const now = new Date();
      const tenYearsLater = new Date(now);
      tenYearsLater.setFullYear(tenYearsLater.getFullYear() + 10);
      if (parsedDate < now) {
        notify("Please choose a date from today onwards.", "warning");
        return;
      }
      if (parsedDate > tenYearsLater) {
        notify("Please choose a date within the next 10 years.", "warning");
        return;
      }

      const action = id ? 'update' : 'create';
      const payloadId = id || generateId(name);
      const dateOnly = parsedDate.toISOString().split('T')[0];
      const body = new URLSearchParams({
        action,
        id: payloadId,
        name,
        localization: location,
        date: dateOnly,
        type,
        summary,
        description,
        collection_id: selectedCollections.length ? selectedCollections[0] : ''
      });
      body.append('collection_ids', JSON.stringify(selectedCollections));

      try {
        const resp = await fetch('../PHP/crud/events.php', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body
        });
        if (resp.status === 401) {
          notify('Not authenticated. Please sign in to save events.', 'warning');
          return;
        }
        if (resp.status === 403) {
          notify('Forbidden. You are not allowed to modify this event.', 'error');
          return;
        }
        const json = await resp.json().catch(() => null);
        if (!json || !json.success) {
          console.warn('Save event failed', json);
          notify('Unable to save event. Please try again.', 'error');
          return;
        }

        // Reload server data and re-render
        try {
          const ga = await fetch('../PHP/get_all.php');
          if (ga.status === 200) {
            const serverData = await ga.json().catch(() => null);
            if (serverData) {
              try { window.appData.saveData(serverData); } catch (e) { localStorage.setItem('collectionsData', JSON.stringify(serverData)); }
            }
          }
        } catch (e) {
          console.warn('Failed to reload server data after saving event', e);
        }

        closeEditModal();
        renderEvents();
      } catch (err) {
        console.error('Failed to save event', err);
        notify('Network error while saving event.', 'error');
      }
    })();
  }

  function deleteEventHandler(id) {
    const data = loadData();
    if (!canCurrentUserManageEvent(id, data)) {
      notify("You can only delete events that belong to your collections.", "error");
      return;
    }
    if (!confirm("Delete this event? This action cannot be undone.")) {
      return;
    }

    (async () => {
      try {
        const resp = await fetch('../PHP/crud/events.php', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ action: 'delete', id })
        });
        if (resp.status === 401) {
          notify('Not authenticated. Please sign in to delete events.', 'warning');
          return;
        }
        if (resp.status === 403) {
          notify('Forbidden. You are not allowed to delete this event.', 'error');
          return;
        }
        const json = await resp.json().catch(() => null);
        if (!json || !json.success) {
          console.warn('Delete event failed', json);
          notify('Unable to delete event. Please try again.', 'error');
          return;
        }

        try {
          const ga = await fetch('../PHP/get_all.php');
          if (ga.status === 200) {
            const serverData = await ga.json().catch(() => null);
            if (serverData) {
              try { window.appData.saveData(serverData); } catch (e) { localStorage.setItem('collectionsData', JSON.stringify(serverData)); }
            }
          }
        } catch (e) {
          console.warn('Failed to reload server data after delete', e);
        }

        renderEvents();
      } catch (err) {
        console.error('Delete failed', err);
        notify('Network error while deleting event.', 'error');
      }
    })();
  }

  // ---------- RSVP ----------

  function rsvpEvent(id) {
    const user = getCurrentUser();
    if (!user || !user.active) {
      notify("Please sign in to RSVP.", "warning");
      return;
    }

    const data = loadData();
    const ev = (data.events || []).find(x => x.id === id);
    if (!ev) { notify("Event not found.", "error"); return; }

    (async () => {
      try {
        // Find if current user already has an entry in event_ratings for this event
        const dataset = loadData();
        const existing = Array.isArray(dataset.eventsUsers)
          ? dataset.eventsUsers.find(eu => eu.eventId === id && eu.userId === user.id)
          : null;

        const action = existing ? 'unrsvp' : 'rsvp';
        const resp = await fetch('../PHP/crud/ratings.php', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ action, eventId: id })
        });
        if (resp.status === 401) {
          notify('Not authorized. Please sign in again.', 'warning');
          return;
        }
        const json = await resp.json().catch(() => null);
        if (!json || !json.success) {
          console.warn('RSVP failed', json);
          notify('Unable to update RSVP. Please try again.', 'error');
          return;
        }

        // Optimistically update modal/button UI immediately
        try {
          const rsvpState = action === 'rsvp';
          if (modalRsvpBtn) {
            modalRsvpBtn.className = rsvpState ? 'explore-btn ghost following' : 'explore-btn ghost';
            modalRsvpBtn.innerHTML = rsvpState ? `<i class="bi bi-calendar-check" aria-hidden="true"></i> Going` : `<i class="bi bi-calendar-plus" aria-hidden="true"></i> RSVP`;
          }
          if (modalAttendeesCountEl) {
            const current = Number(modalAttendeesCountEl.textContent || 0) || 0;
            modalAttendeesCountEl.textContent = String(current + (action === 'rsvp' ? 1 : -1));
          }
          // Update any list button that matches this event
          const listBtn = document.querySelector(`.rsvp-btn[data-id="${id}"]`);
          if (listBtn) {
            listBtn.classList.toggle('following', rsvpState);
            listBtn.innerHTML = rsvpState
              ? `<i class="bi bi-calendar-check" aria-hidden="true"></i> Going`
              : `<i class="bi bi-calendar-plus" aria-hidden="true"></i> RSVP`;
          }
        } catch (e) { }

        // reload server data and re-render to sync RSVP state
        try {
          await refreshDataFromServer();
        } catch (e) {
          console.warn('Failed to reload server data after RSVP', e);
        }

        renderEvents();
        if (eventDetailModal && eventDetailModal.style.display === 'flex') {
          openEventDetail(id, {});
        }
        try { notify(action === 'rsvp' ? "You have RSVP'd to this event." : 'Your RSVP has been removed.', action === 'rsvp' ? 'success' : 'info'); } catch (e) { }
      } catch (err) {
        console.error('RSVP failed', err);
        notify('Network error while updating RSVP.', 'error');
      }
    })();
  }

  // ---------- TABS / FILTER HOOKUP ----------

  function setFilter(mode) {
    if (!filterSelect) return;
    filterSelect.value = mode;

    if (tabUpcoming && tabPast) {
      tabUpcoming.classList.toggle("active", mode === "upcoming");
      tabPast.classList.toggle("active", mode === "past");
    }

    renderEvents();
  }

  tabUpcoming?.addEventListener("click", () => setFilter("upcoming"));
  tabPast?.addEventListener("click", () => setFilter("past"));

  // Native selects (still work if changed)
  filterSelect?.addEventListener("change", () => {
    const mode = filterSelect.value;
    if (mode === "upcoming" || mode === "past") {
      setFilter(mode);
    } else {
      // "all" from select only; clear active pill highlight
      tabUpcoming?.classList.remove("active");
      tabPast?.classList.remove("active");
      renderEvents();
    }
  });

  typeSelect?.addEventListener("change", renderEvents);
  locationSelect?.addEventListener("change", renderEvents);
  sortSelect?.addEventListener("change", renderEvents);

  // ---------- GLOBAL LISTENERS ----------

  // New Event button
  newEventBtn?.addEventListener("click", () => openEditModal(null));
  form?.addEventListener("submit", saveEventFromForm);

  // Close modals
  eventDetailClose?.addEventListener("click", closeEventDetail);
  modalCloseBtn?.addEventListener("click", closeEventDetail);
  eventEditClose?.addEventListener("click", closeEditModal);
  cancelEditBtn?.addEventListener("click", closeEditModal);
  closeCalendarDayModal?.addEventListener('click', () => closeCalendarDayModalFn());
  calendarDayClose?.addEventListener('click', () => closeCalendarDayModalFn());

  // Close on backdrop click
  window.addEventListener("click", (ev) => {
    if (ev.target === eventDetailModal) closeEventDetail();
    if (ev.target === eventEditModal) closeEditModal();
    if (ev.target === calendarDayModal) closeCalendarDayModalFn();
  });

  // Re-render when user state changes so data-requires-login buttons reflect state
  window.addEventListener("userStateChange", renderEvents);

  // ---------- INITIAL RENDER ----------
  (async function initEventsPage() {
    try {
      await refreshDataFromServer();
      initEventsPaginationControls();
      try { initCalendar(); } catch (e) { /* ignore if calendar not present */ }
      renderEvents();
      handleNewEventParam();
    } catch (err) {
      console.error('Events init failed:', err);
      try { notify('Erro a carregar eventos. Veja a consola para detalhes.', 'error'); } catch (e) { }
    }
  })();
});

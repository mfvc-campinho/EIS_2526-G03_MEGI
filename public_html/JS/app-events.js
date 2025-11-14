// ===============================================
// File: public_html/JS/app-events.js
// Purpose: Manage event rendering, filtering, modals, RSVP and rating UX on the Events page.
// Major blocks: element selectors, helpers (date parsing, filtering), rendering, detail modal & rating, edit/create/delete, RSVP, event listeners, initialization.
// Notes: Uses window.appData where available; keeps data changes simulated or delegated to appData.
// ===============================================

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
    if (window.appData && typeof window.appData.loadData === "function") {
      return window.appData.loadData();
    }
    if (inMemoryCollectionsData) return inMemoryCollectionsData;
    if (typeof window !== "undefined" && window.localStorage) {
      try {
        const stored = JSON.parse(window.localStorage.getItem(DATA_STORAGE_KEY));
        inMemoryCollectionsData = stored || { events: [] };
      } catch {
        inMemoryCollectionsData = { events: [] };
      }
    } else {
      inMemoryCollectionsData = { events: [] };
    }
    return inMemoryCollectionsData;
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
    if (!start) return alert('Invalid event date');

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

  function populateCollectionSelector(selectedIds = []) {
    if (!fieldCollections) return;
    const collections = getAllCollections();
    fieldCollections.innerHTML = "";

    if (!collections.length) {
    const emptyOpt = document.createElement("option");
    emptyOpt.value = "";
    emptyOpt.textContent = "No collections available";
    emptyOpt.selected = true;
    fieldCollections.appendChild(emptyOpt);
      fieldCollections.disabled = true;
      return;
    }

    fieldCollections.disabled = false;
    collections.forEach(col => {
      const option = document.createElement("option");
      option.value = col.id;
      option.textContent = col.name || col.id;
      if (selectedIds.includes(col.id)) {
        option.selected = true;
      }
      fieldCollections.appendChild(option);
    });
  }

  function getEventOwnerProfiles(event, data) {
    if (!event) return [{ id: null, name: "Community host" }];
    const dataset = data || loadData();
    const users = Array.isArray(dataset?.users) ? dataset.users : [];
    const ownerIds = getEventOwnerIds(event.id, dataset);
    const uniqueOwnerIds = Array.from(new Set(ownerIds));

    const ownerProfiles = uniqueOwnerIds
      .map(ownerId => {
        const user = users.find(u => u.id === ownerId || u["owner-id"] === ownerId);
        const name = user?.["owner-name"] || ownerId;
        return { id: ownerId, name };
      })
      .filter(profile => Boolean(profile?.name));

    if (ownerProfiles.length) return ownerProfiles;

    if (event.hostId) {
      const hostUser = users.find(u => u.id === event.hostId || u["owner-id"] === event.hostId);
      return [{
        id: event.hostId,
        name: hostUser?.["owner-name"] || event.host || event.hostId
      }];
    }

    if (event.host) {
      return [{ id: null, name: event.host }];
    }

    return [{ id: null, name: "Community host" }];
  }

  function canCurrentUserManageEvent(eventId, data, user = getCurrentUser()) {
    const ownerId = getActiveOwnerId(user);
    if (!ownerId) return false;
    const owners = getEventOwnerIds(eventId, data);
    if (!owners.length) return false;
    return owners.includes(ownerId);
  }

  function getEventUserLinks(event, data) {
    if (!event) return [];
    const dataset = data || loadData();
    if (window.appData?.getEventUsers) {
      return window.appData.getEventUsers(event.id, dataset) || [];
    }
    if (Array.isArray(dataset?.eventsUsers)) {
      return dataset.eventsUsers.filter(entry => entry.eventId === event.id);
    }
    const attendees = Array.isArray(event.attendees) ? event.attendees : [];
    return attendees.map(userId => ({
      eventId: event.id,
      userId,
      rating: typeof event.ratings?.[userId] === "number" ? event.ratings[userId] : null
    }));
  }

  function getEventRatingStats(entries) {
    const rated = entries.filter(entry => typeof entry.rating === "number");
    if (!rated.length) {
      return { count: 0, average: null };
    }
    const total = rated.reduce((sum, entry) => sum + entry.rating, 0);
    return {
      count: rated.length,
      average: total / rated.length
    };
  }

  function getUserRatingFromEntries(entries, user) {
    if (!user) return null;
    const userId = user.id || user["owner-id"];
    if (!userId) return null;
    const entry = entries.find(link => link.userId === userId);
    return entry && typeof entry.rating === "number" ? entry.rating : null;
  }

  function buildLikesMaps(data) {
    likesByEventMap = {};
    ownerLikesMap = {};
    (data?.userShowcases || []).forEach(entry => {
      const owner = entry.ownerId;
      const likes = entry.likedEvents || [];
      ownerLikesMap[owner] = new Set(likes);
      likes.forEach(eventId => {
        if (!likesByEventMap[eventId]) likesByEventMap[eventId] = new Set();
        likesByEventMap[eventId].add(owner);
      });
    });
  }

  function getEventLikedBy(eventId) {
    const set = likesByEventMap[eventId];
    return set ? new Set(set) : new Set();
  }

  function getVoteOverride(eventId) {
    return Object.prototype.hasOwnProperty.call(voteState, eventId)
      ? voteState[eventId]
      : undefined;
  }

  function getUserBaseLike(eventId, userId) {
    if (!userId) return false;
    const likedSet = ownerLikesMap[userId];
    return likedSet ? likedSet.has(eventId) : false;
  }

  function getEffectiveUserLike(eventId, userId) {
    if (!userId) return false;
    const override = getVoteOverride(eventId);
    if (override === undefined) return getUserBaseLike(eventId, userId);
    return override;
  }

  function getDisplayLikes(eventId, userId) {
    const likedSet = getEventLikedBy(eventId);
    if (userId) {
      const override = getVoteOverride(eventId);
      const baseHas = likedSet.has(userId);
      const finalState = override === undefined ? baseHas : override;
      if (finalState) likedSet.add(userId);
      else likedSet.delete(userId);
    }
    return likedSet.size;
  }

  function notifyEventLikesChange(ownerId) {
    if (!ownerId) return;
    window.dispatchEvent(new CustomEvent("userEventLikesChange", { detail: { ownerId } }));
  }

  function toggleEventLike(eventId) {
    if (!isLoggedIn()) {
      alert("Please sign in to like events.");
      return;
    }
    const ownerId = getActiveOwnerId();
    if (!ownerId) return;
    const currentState = getEffectiveUserLike(eventId, ownerId);
    const newState = !currentState;
    voteState[eventId] = newState;

    // Persist the change using the centralized appData function
    if (window.appData && typeof window.appData.setUserEventLike === "function") {
      window.appData.setUserEventLike(ownerId, eventId, newState);
    }

    // Re-render the list and the modal if it's open
    renderEvents();
    if (eventDetailModal && eventDetailModal.style.display === "flex") {
      openEventDetail(eventId);
    }
    notifyEventLikesChange(ownerId);
  }

  // ---------- CALENDAR WIDGET ----------
  // Small self-contained calendar that highlights upcoming event days
  let calendarState = { year: null, month: null };
  let calendarGrid, calendarMonthTitle, calendarPrev, calendarNext, calendarAlertEl;

  function initCalendar() {
    const widget = document.getElementById('calendarWidget');
    if (!widget) return;
    calendarGrid = document.getElementById('calendarGrid');
    calendarMonthTitle = document.getElementById('calendarMonthTitle');
    calendarPrev = document.getElementById('calendarPrev');
    calendarNext = document.getElementById('calendarNext');
    calendarAlertEl = document.getElementById('calendarAlert');

    const today = new Date();
    calendarState.year = today.getFullYear();
    calendarState.month = today.getMonth();

    calendarPrev?.addEventListener('click', () => navigateMonth(-1));
    calendarNext?.addEventListener('click', () => navigateMonth(1));

    renderCalendar();
  }

  function formatMonthYear(y, m) {
    const d = new Date(y, m, 1);
    return d.toLocaleString(undefined, { month: 'long', year: 'numeric' });
  }

  function getEventsMapForMonth(year, month) {
    const data = loadData();
    const map = new Map();
    (data.events || []).forEach(ev => {
      const d = parseEventDate(ev.date);
      if (!d) return;
      if (d.getFullYear() === year && d.getMonth() === month) {
        const day = d.getDate();
        if (!map.has(day)) map.set(day, []);
        map.get(day).push(ev);
      }
    });
    return map;
  }

  function getEventsForDateObj(dateObj) {
    const data = loadData();
    return (data.events || []).filter(ev => {
      const d = parseEventDate(ev.date);
      if (!d) return false;
      return d.getFullYear() === dateObj.getFullYear() && d.getMonth() === dateObj.getMonth() && d.getDate() === dateObj.getDate();
    });
  }

  function renderCalendar() {
    if (!calendarGrid) return;
    calendarGrid.innerHTML = '';
    const year = calendarState.year;
    const month = calendarState.month;
    calendarMonthTitle.textContent = formatMonthYear(year, month);

    const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    weekdays.forEach(w => {
      const el = document.createElement('div');
      el.className = 'weekday';
      el.textContent = w;
      calendarGrid.appendChild(el);
    });

    const first = new Date(year, month, 1);
    const firstDay = first.getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    // leading empty cells
    for (let i = 0; i < firstDay; i++) {
      const empty = document.createElement('div');
      empty.className = 'cal-day empty';
      calendarGrid.appendChild(empty);
    }

    const eventsMap = getEventsMapForMonth(year, month);
    const today = new Date();

    for (let d = 1; d <= daysInMonth; d++) {
      const cell = document.createElement('div');
      cell.className = 'cal-day';
      const dateNum = document.createElement('div');
      dateNum.className = 'date-number';
      dateNum.textContent = d;
      cell.appendChild(dateNum);

      const dateObj = new Date(year, month, d);
      if (dateObj.getFullYear() === today.getFullYear() && dateObj.getMonth() === today.getMonth() && dateObj.getDate() === today.getDate()) {
        cell.classList.add('today');
      }

      if (eventsMap.has(d)) {
        const evs = eventsMap.get(d) || [];
        const hasUpcoming = evs.some(e => isUpcoming(e.date));
        const hasPast = evs.some(e => isPastEvent(e.date));

        if (hasUpcoming) cell.classList.add('has-event-upcoming');
        else if (hasPast) cell.classList.add('has-event-past');

        const dot = document.createElement('div');
        dot.className = 'event-dot';
        cell.appendChild(dot);

        // tooltip: list of event names
        const names = evs.map(e => e.name).filter(Boolean);
        if (names.length) cell.setAttribute('title', names.join('\n'));
        cell.style.cursor = 'pointer';
        cell.addEventListener('click', () => showEventDetails(dateObj));
      }

      calendarGrid.appendChild(cell);
    }

    checkUpcomingWeekEvents();
  }

  function checkUpcomingWeekEvents() {
    if (!calendarAlertEl) return;
    const data = loadData();
    const today = todayStart();
    const in7 = new Date(today.getTime() + 7 * 24 * 3600 * 1000);
    const upcoming = (data.events || []).filter(ev => {
      const d = parseEventDate(ev.date);
      if (!d) return false;
      return d >= today && d <= in7;
    }).sort((a, b) => (parseEventDate(a.date) || 0) - (parseEventDate(b.date) || 0));

    if (upcoming.length) {
      const ev = upcoming[0];
      const human = formatDateHuman(ev.date);
      calendarAlertEl.style.display = 'block';
      calendarAlertEl.textContent = `📅 Upcoming event: ${ev.name} on ${human}!`;
    } else {
      calendarAlertEl.style.display = 'none';
      calendarAlertEl.textContent = '';
    }
  }

  // Navigate months (direction: -1 previous, 1 next)
  function navigateMonth(direction) {
    if (typeof direction !== 'number') return;
    calendarState.month += direction;
    if (calendarState.month < 0) { calendarState.month = 11; calendarState.year -= 1; }
    if (calendarState.month > 11) { calendarState.month = 0; calendarState.year += 1; }
    renderCalendar();
  }

  // Show modal with events on the given date (dateObj is a Date)
  function showEventDetails(dateObj) {
    if (!calendarDayModal || !calendarDayList || !calendarDayTitle) return;
    const evs = getEventsForDateObj(dateObj).sort((a, b) => (parseEventDate(a.date) || 0) - (parseEventDate(b.date) || 0));
    calendarDayList.innerHTML = '';
    const humanTitle = dateObj.toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    calendarDayTitle.textContent = `Events on ${humanTitle}`;

    if (!evs.length) {
      calendarDayList.innerHTML = '<p class="muted">No events for this date.</p>';
    } else {
      evs.forEach(ev => {
        const div = document.createElement('div');
        div.className = 'calendar-day-event';
        const title = document.createElement('h4');
        title.innerHTML = escapeHtml(ev.name || 'Untitled Event');
        const meta = document.createElement('div');
        meta.className = 'meta';
        const dateSpan = document.createElement('span');
        dateSpan.innerHTML = `<i class="bi bi-calendar-event-fill"></i> ${escapeHtml(formatDateShort(ev.date))}`;
        const locSpan = document.createElement('span');
        locSpan.innerHTML = `<i class="bi bi-geo-alt-fill"></i> ${escapeHtml(ev.localization || 'TBA')}`;
        meta.appendChild(dateSpan);
        meta.appendChild(locSpan);

        const typeP = document.createElement('div');
        typeP.className = 'meta';
        typeP.innerHTML = `<small class="muted">Type: ${escapeHtml(ev.type || 'General')}</small>`;

        const desc = document.createElement('p');
        desc.textContent = ev.description || ev.summary || '';

        const actions = document.createElement('div');
        actions.style.marginTop = '8px';
        const viewBtn = document.createElement('button');
        viewBtn.className = 'explore-btn';
        viewBtn.textContent = 'View Details';
        viewBtn.addEventListener('click', () => {
          // close calendar modal and open event detail
          closeCalendarDayModalFn();
          openEventDetail(ev.id);
        });
        actions.appendChild(viewBtn);

        div.appendChild(title);
        div.appendChild(meta);
        div.appendChild(typeP);
        if (desc.textContent) div.appendChild(desc);
        div.appendChild(actions);

        calendarDayList.appendChild(div);
      });
    }

    // show modal
    calendarDayModal.style.display = 'flex';
  }

  function closeCalendarDayModalFn() {
    if (calendarDayModal) calendarDayModal.style.display = 'none';
  }

  // ---------- END CALENDAR WIDGET ----------

  // ---------- RENDERING ----------

  function renderEvents() {
    const data = loadData();
    buildLikesMaps(data);
    if (!eventsList) return;

    eventsList.innerHTML = "";

    const allEvents = (data.events || []).slice();
    populateLocationFilterOptions(allEvents);
    const currentUser = getCurrentUser();

    // Count upcoming/past for tabs
    let upcomingCount = 0;
    let pastCount = 0;
    allEvents.forEach(ev => {
      if (isUpcoming(ev.date)) upcomingCount++;
      else pastCount++;
    });
    if (upcomingCountEl) upcomingCountEl.textContent = `(${upcomingCount})`;
    if (pastCountEl) pastCountEl.textContent = `(${pastCount})`;

    // Apply filters
    const filter = filterSelect?.value || "all";
    const typeFilter = (typeSelect?.value || "all").toLowerCase();
    const sortMode = (sortSelect?.value || "asc").toLowerCase();
    const locationFilter = (locationSelect?.value || "all").toLowerCase();

    const filtered = allEvents
      .sort((a, b) => {
        const da = parseEventDate(a.date) || new Date(0);
        const db = parseEventDate(b.date) || new Date(0);
        if (sortMode === "desc") return db - da;
        if (sortMode === "alpha") {
          return (a.name || "").localeCompare(b.name || "", undefined, { sensitivity: "base" });
        }
        return da - db;
      })
      .filter(ev => {
        if (filter === "upcoming" && !isUpcoming(ev.date)) return false;
        if (filter === "past" && isUpcoming(ev.date)) return false;
        if (typeFilter !== "all" && (ev.type || "").toLowerCase() !== typeFilter) return false;
        if (locationFilter !== "all" && (ev.localization || "").toLowerCase() !== locationFilter) return false;
        return true;
      });

    const totalMatches = filtered.length;
    let eventsToRender = filtered;
    let startIndexForPage = 0;
    if (hasEventsPagination && eventsPaginationState && eventsPaginationState.pageSize > 0) {
      const effectiveSize = Math.max(eventsPaginationState.pageSize || defaultEventsPageSize || 1, 1);
      eventsPaginationState.pageSize = effectiveSize;
      const totalPages = effectiveSize > 0 ? Math.ceil((totalMatches || 0) / effectiveSize) : 0;
      if (totalPages === 0) {
        eventsPaginationState.pageIndex = 0;
      } else if (eventsPaginationState.pageIndex >= totalPages) {
        eventsPaginationState.pageIndex = totalPages - 1;
      } else if (eventsPaginationState.pageIndex < 0) {
        eventsPaginationState.pageIndex = 0;
      }
      startIndexForPage = eventsPaginationState.pageIndex * effectiveSize;
      const endIndex = startIndexForPage + effectiveSize;
      eventsToRender = filtered.slice(startIndexForPage, endIndex);
    }
    updateEventsPaginationUI(totalMatches, startIndexForPage, eventsToRender.length);

    if (eventsToRender.length === 0) {
      eventsList.innerHTML = '<p class="muted">No events found for this filter.</p>';
      return;
    }

    eventsToRender.forEach(ev => {
      // determine if event is happening within the next 7 days (inclusive)
      const today = todayStart();
      const in7 = new Date(today.getTime() + 7 * 24 * 3600 * 1000);
      const evDateObj = parseEventDate(ev.date);
      const isSoon = evDateObj && evDateObj >= today && evDateObj <= in7;
      const card = document.createElement("div");
      card.className = "event-card";
      const isPast = isPastEvent(ev.date);
      const eventLinks = getEventUserLinks(ev, data);
      const { count: ratingCount, average: ratingAvg } = getEventRatingStats(eventLinks);
      const canManage = canCurrentUserManageEvent(ev.id, data, currentUser);
      const userCanRate = Boolean(currentUser && currentUser.active);
      const sessionValue = userCanRate ? sessionRatings[ev.id] : undefined;
      const storedUserRating = currentUser ? getUserRatingFromEntries(eventLinks, currentUser) : null;
      const userRating = userCanRate
        ? (sessionValue !== undefined ? sessionValue : storedUserRating ?? null)
        : storedUserRating ?? null;
      const ownerProfiles = getEventOwnerProfiles(ev, data);
      const ownerDisplayText = ownerProfiles
        .map(profile => escapeHtml(profile.name))
        .join(", ") || "Community host";
      const ownerLinkId = ownerProfiles[0]?.id || ev.hostId || null;
      const ownerLinkHref = ownerLinkId
        ? `user_page.html?owner=${encodeURIComponent(ownerLinkId)}`
        : "user_page.html";
      const ownerIdForDisplay = getActiveOwnerId(currentUser);
      const isLiked = ownerIdForDisplay ? getEffectiveUserLike(ev.id, ownerIdForDisplay) : false;
      const displayLikes = getDisplayLikes(ev.id, ownerIdForDisplay);

      let ratingHtml = "";
      if (isPast) {
        const stars = [];
        for (let i = 1; i <= 5; i++) {
          let classes = "star";
          if (ratingAvg && i <= Math.round(ratingAvg)) classes += " filled";
          if (userRating && i <= userRating) classes += " user-rating";
          classes += " clickable";
          stars.push(`<span class="${classes}" data-value="${i}">★</span>`);
        }

        const summaryParts = [];
        const showDemoOnly = userCanRate && sessionValue !== undefined;
        if (!showDemoOnly) {
          if (ratingAvg) {
            summaryParts.push(`<span class="muted">★ ${ratingAvg.toFixed(1)}</span> <span>(${ratingCount})</span>`);
          } else {
            summaryParts.push(`<span class="muted">No ratings yet</span>`);
          }
        }

        if (showDemoOnly) {
          summaryParts.push(`<span class="demo-rating-note">Your demo rating: ${sessionValue}/5 (not saved)</span>`);
        } else if (userCanRate && userRating) {
          summaryParts.push(`<span class="demo-rating-note">You rated this ${userRating}/5</span>`);
        }

        const summary = summaryParts.join("");

        ratingHtml = `
            <div class="card-rating">
              <div class="rating-stars" data-event-id="${ev.id}">${stars.join("")}</div>
              <div class="rating-summary">${summary}</div>
            </div>
          `;
      }

      const alertBadgeHtml = isSoon && !isPast ? `<span class="event-alert-badge" aria-hidden="true">⚠️ Soon</span>` : "";
      const ownerHtml = `
          <div class="event-meta-row event-owner-row">
            <i class="bi bi-person-circle" aria-hidden="true"></i>
            <span class="event-owner-label">Owner:</span>
            <a class="event-owner-link" href="${ownerLinkHref}" data-event-id="${ev.id}">
              ${ownerDisplayText}
            </a>
          </div>

          <div class="event-meta-row">
            <button class="metric-btn vote-toggle ${isLiked ? "active" : ""}" data-event-id="${ev.id}">
              <i class="bi ${isLiked ? "bi-star-fill" : "bi-star"}"></i>
              <span class="vote-count">${displayLikes}</span>
            </button>
          </div>
        `;

      card.innerHTML = `
          <h3 class="card-title">${escapeHtml(ev.name)} ${alertBadgeHtml}</h3>
          <p class="card-summary">
            ${escapeHtml(ev.summary || ev.description || "")}
          </p>

          <div class="event-meta-row">
            <i class="bi bi-calendar-event-fill" aria-hidden="true"></i>
            <span>${formatDateShort(ev.date)}</span>
          </div>

          <div class="event-meta-row">
            <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
            <span>${escapeHtml(ev.localization || "To be announced")}</span>
          </div>

          ${ownerHtml}

          ${ratingHtml}

          <div class="card-actions">
            <button class="view-btn explore-btn ghost">
              <i class="bi bi-eye-fill" aria-hidden="true"></i> View
            </button>
            <button class="rsvp-btn explore-btn success" data-id="${ev.id}" data-requires-login>
              <i class="bi bi-calendar-check" aria-hidden="true"></i> RSVP
            </button>
            ${canManage ? `
              <button class="edit-btn explore-btn warning" data-id="${ev.id}" data-requires-login>
                <i class="bi bi-pencil-square" aria-hidden="true"></i> Edit
              </button>
            ` : ``}
            ${canManage ? `
              <button class="delete-btn explore-btn danger" data-id="${ev.id}" data-requires-login>
                <i class="bi bi-trash3" aria-hidden="true"></i> Delete
              </button>
            ` : ``}
          </div>
        `;

      card.querySelector(".explore-btn")
        .addEventListener("click", () => openEventDetail(ev.id));
      card.querySelector(".rsvp-btn")
        .addEventListener("click", e => { e.preventDefault(); rsvpEvent(ev.id); });
      const editBtn = card.querySelector(".edit-btn");
      if (editBtn) {
        editBtn.addEventListener("click", () => openEditModal(ev.id));
      }
      const deleteBtn = card.querySelector(".delete-btn");
      if (deleteBtn) {
        deleteBtn.addEventListener("click", () => deleteEventHandler(ev.id));
      }
      const likeBtn = card.querySelector(".vote-toggle");
      if (likeBtn) {
        likeBtn.addEventListener("click", () => toggleEventLike(ev.id));
      }


      // Add 'Add to My Calendar' button for upcoming events
      if (!isPast) {
        const actionsEl = card.querySelector('.card-actions');
        if (actionsEl) {
          const calBtn = document.createElement('button');
          calBtn.className = 'explore-btn calendar-btn';
          calBtn.type = 'button';
          calBtn.innerHTML = '<i class="bi bi-calendar-plus-fill me-1" aria-hidden="true"></i> Add to My Calendar';
          calBtn.addEventListener('click', (e) => {
            e.preventDefault();
            addEventToCalendar({
              name: ev.name,
              date: ev.date,
              endDate: ev.endDate || null,
              description: ev.description || ev.summary || '',
              location: ev.localization || ''
            });
          });
          actionsEl.appendChild(calBtn);
        }
      }

      const starsContainer = card.querySelector(`.rating-stars[data-event-id="${ev.id}"]`);
      if (starsContainer) {
        const stars = Array.from(starsContainer.querySelectorAll('.star'));

        function clearHover() {
          stars.forEach(s => s.classList.remove('hovered'));
        }

        function highlightTo(val) {
          stars.forEach(s => {
            const v = Number(s.dataset.value);
            if (v <= val) s.classList.add('hovered');
            else s.classList.remove('hovered');
          });
        }

        stars.forEach(s => {
          const val = Number(s.dataset.value);

          s.addEventListener('mouseenter', () => highlightTo(val));
          s.addEventListener('focus', () => highlightTo(val));
          s.addEventListener('mouseleave', () => clearHover());
          s.addEventListener('blur', () => clearHover());

          s.addEventListener('click', () => setRating(ev.id, val));
          s.addEventListener('keydown', (evKey) => {
            if (evKey.key === 'Enter' || evKey.key === ' ') {
              evKey.preventDefault();
              setRating(ev.id, val);
            }
          });
          s.setAttribute('tabindex', '0');
          s.setAttribute('role', 'button');
          s.setAttribute('aria-label', `Rate ${val} out of 5`);
        });

        starsContainer.addEventListener('mouseleave', clearHover);
      }

      eventsList.appendChild(card);
    });

    // Update calendar after rendering list so highlights & alert match data
    try { renderCalendar(); } catch (e) { /* ignore if calendar not present */ }

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
  }

  // ---------- DETAIL MODAL & RATING ----------

  function openEventDetail(id, options = {}) {
    const data = loadData();
    const ev = (data.events || []).find(x => x.id === id);
    if (!ev) return alert("Event not found.");

    if (!eventDetailModal) return;

    modalTitleEl.textContent = ev.name;
    modalMetaEl.textContent = `${formatDate(ev.date)} · ${ev.localization || "To be announced"}`;
    modalDescriptionEl.textContent = ev.description || ev.summary || "No description provided.";

    // Host
    const modalOwnerProfiles = getEventOwnerProfiles(ev, data);
    const modalOwnerLabel = modalOwnerProfiles
      .map(profile => profile.name)
      .filter(Boolean)
      .join(", ") || ev.host || "Community host";
    const modalOwnerId = modalOwnerProfiles[0]?.id || ev.hostId || null;
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
    if (modalAttendeesCountEl) {
      modalAttendeesCountEl.textContent = eventLinks.length;
    }

    // Rating (only for past events)
    const isPast = isPastEvent(ev.date);
    const currentUser = getCurrentUser();
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

        if (avg && i <= Math.round(avg)) {
          star.classList.add("filled");
        }
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
          `Demo rating selected: ${sessionValue}/5. (Not saved.) ` +
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

    const ownerIdForDisplay = getActiveOwnerId(currentUser);
    const isLiked = ownerIdForDisplay ? getEffectiveUserLike(ev.id, ownerIdForDisplay) : false;
    const likeBtn = document.getElementById("modal-likeBtn");
    if (likeBtn) {
      likeBtn.classList.toggle("active", isLiked);
      likeBtn.querySelector(".bi").className = `bi ${isLiked ? "bi-star-fill" : "bi-star"}`;
      likeBtn.onclick = () => toggleEventLike(id);
    }

    // (Optional) share button: simple alert for now
    if (modalShareBtn) {
      modalShareBtn.onclick = () => {
        alert("Sharing is simulated in this prototype.");
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
      alert("Please sign in to rate events.");
      return;
    }

    const data = loadData();
    const ev = (data.events || []).find(x => x.id === eventId);
    if (!ev) return;

    if (!isPastEvent(ev.date)) {
      alert("You can only rate past events.");
      return;
    }

    sessionRatings[eventId] = value;
    alert("Demo only: rating stored for this session.");
    const preserveReturnUrl = modalReturnUrl;
    renderEvents();
    if (eventDetailModal && eventDetailModal.style.display === "flex") {
      openEventDetail(eventId, { returnUrl: preserveReturnUrl });
    }
  }

  // ---------- EDIT / CREATE EVENT ----------

  function openEditModal(id) {
    const data = loadData();
    const currentUser = getCurrentUser();
    let selectionIds = [];

    if (id) {
      const ev = (data.events || []).find(x => x.id === id);
      if (!ev) return alert("Event not found.");
      if (!canCurrentUserManageEvent(ev.id, data, currentUser)) {
        alert("You can only edit events that belong to your collections.");
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

    populateCollectionSelector(selectionIds);
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

    const id = (fieldId.value || "").trim();
    const name = fieldName.value.trim();
    const dateVal = fieldDate.value;
    const location = fieldLocation.value.trim();
    const summary = fieldSummary.value.trim();
    const description = fieldDescription.value.trim();
    const type = fieldType.value.trim();
    const selectedCollections = fieldCollections
      ? Array.from(fieldCollections.selectedOptions)
          .map(option => option.value)
          .filter(Boolean)
      : [];

    if (!name || !dateVal) {
      alert("Please provide at least a name and date.");
      return;
    }

    let dateIso;
    try {
      dateIso = new Date(dateVal).toISOString();
    } catch {
      dateIso = dateVal;
    }

    const parsedDate = parseEventDate(dateVal);
    if (!parsedDate) {
      alert("Please provide a valid date.");
      return;
    }

    const now = new Date();
    const tenYearsLater = new Date(now);
    tenYearsLater.setFullYear(tenYearsLater.getFullYear() + 10);
    if (parsedDate < now) {
      alert("Please choose a date from today onwards.");
      return;
    }
    if (parsedDate > tenYearsLater) {
      alert("Please choose a date within the next 10 years.");
      return;
    }

    alert("This prototype only simulates event creation; no data is saved.");
    closeEditModal();
    renderEvents();
  }

  function deleteEventHandler(id) {
    const data = loadData();
    if (!canCurrentUserManageEvent(id, data)) {
      alert("You can only delete events that belong to your collections.");
      return;
    }

    if (!confirm("Delete this event? This action cannot be undone.")) return;
    deleteEntity(id);
    renderEvents();
  }

  // ---------- RSVP ----------

  function rsvpEvent(id) {
    const user = getCurrentUser();
    if (!user || !user.active) {
      alert("Please sign in to RSVP.");
      return;
    }

    const data = loadData();
    const ev = (data.events || []).find(x => x.id === id);
    if (!ev) return alert("Event not found.");

    alert("Demo only: RSVP is not saved.");
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
  initEventsPaginationControls();
  // Initialize calendar and render events (calendar will refresh from renderEvents)
  try { initCalendar(); } catch (e) { /* ignore if calendar not present */ }
  renderEvents();
  handleNewEventParam();
});

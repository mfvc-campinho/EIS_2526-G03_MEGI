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
  let deepLinkHandled = false;
  const sessionRatings = {};
  let modalReturnUrl = null;
  const eventsList = document.getElementById("eventsList");
  const newEventBtn = document.getElementById("newEventBtn");

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
  const cancelEditBtn = document.getElementById("cancel-event-edit");

  let locationFilterPopulated = false;

  // ---------- HELPERS ----------

  function loadData() {
    if (window.appData && typeof window.appData.loadData === "function") {
      return window.appData.loadData();
    }
    try {
      return JSON.parse(localStorage.getItem("collectionsData")) || { events: [] };
    } catch {
      return { events: [] };
    }
  }

  function clearDeepLinkParams() {
    const currentUrl = new URL(window.location.href);
    if (!currentUrl.searchParams.has("id")) return;
    currentUrl.searchParams.delete("id");
    history.replaceState(null, "", currentUrl.toString());
  }

  function saveEntityUpdate(id, patch) {
    if (window.appData && typeof window.appData.updateEntity === "function") {
      window.appData.updateEntity("events", id, patch);
    } else {
      // fallback: localStorage direct update (if needed)
      const data = loadData();
      const idx = (data.events || []).findIndex(e => e.id === id);
      if (idx !== -1) {
        data.events[idx] = { ...data.events[idx], ...patch };
        localStorage.setItem("collectionsData", JSON.stringify(data));
      }
    }
  }

  function addEntity(ev) {
    if (window.appData && typeof window.appData.addEntity === "function") {
      window.appData.addEntity("events", ev);
    } else {
      const data = loadData();
      data.events = data.events || [];
      data.events.push(ev);
      localStorage.setItem("collectionsData", JSON.stringify(data));
    }
  }

  function deleteEntity(id) {
    if (window.appData && typeof window.appData.deleteEntity === "function") {
      window.appData.deleteEntity("events", id);
    } else {
      const data = loadData();
      data.events = (data.events || []).filter(e => e.id !== id);
      localStorage.setItem("collectionsData", JSON.stringify(data));
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

  function canCurrentUserManageEvent(eventId, data, user = getCurrentUser()) {
    const ownerId = getActiveOwnerId(user);
    if (!ownerId) return false;
    const owners = getEventOwnerIds(eventId, data);
    if (!owners.length) return false;
    return owners.includes(ownerId);
  }

  // ---------- RENDERING ----------

  function renderEvents() {
    const data = loadData();
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

    if (filtered.length === 0) {
      eventsList.innerHTML = '<p class="muted">No events found for this filter.</p>';
      return;
    }

    filtered.forEach(ev => {
      const card = document.createElement("div");
      card.className = "event-card";
      const isPast = isPastEvent(ev.date);
      const baseRatings = ev.ratings || {};
      const ratingValues = Object.values(baseRatings);
      const ratingCount = ratingValues.length;
      const ratingAvg = ratingCount
        ? ratingValues.reduce((a, b) => a + b, 0) / ratingCount
        : null;
      const canManage = canCurrentUserManageEvent(ev.id, data, currentUser);
      const userCanRate = Boolean(currentUser && currentUser.active);
      const sessionValue = userCanRate ? sessionRatings[ev.id] : undefined;
      const storedUserRating = currentUser ? baseRatings[currentUser.id] : null;
      const userRating = userCanRate
        ? (sessionValue !== undefined ? sessionValue : storedUserRating || null)
        : null;

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

      card.innerHTML = `
          <h3 class="card-title">${escapeHtml(ev.name)}</h3>
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

          ${ratingHtml}

          <div class="card-actions">
            <button class="view-btn">
              <i class="bi bi-eye-fill" aria-hidden="true"></i> View
            </button>
            <button class="rsvp-btn" data-id="${ev.id}" data-requires-login>
              <i class="bi bi-calendar-check" aria-hidden="true"></i> RSVP
            </button>
            ${canManage ? `
              <button class="edit-btn" data-id="${ev.id}" data-requires-login>
                <i class="bi bi-pencil-square" aria-hidden="true"></i> Edit
              </button>
            ` : ``}
            ${canManage ? `
              <button class="delete-btn" data-id="${ev.id}" data-requires-login>
                <i class="bi bi-trash3" aria-hidden="true"></i> Delete
              </button>
            ` : ``}
          </div>
        `;

      card.querySelector(".view-btn")
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

    if (!deepLinkHandled && deepLinkEventId) {
      const exists = (data.events || []).some(ev => ev.id === deepLinkEventId);
      if (exists) {
        const ref = document.referrer && document.referrer.length ? document.referrer : null;
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
    if (ev.hostId && Array.isArray(data.users)) {
      const user = data.users.find(u => u.id === ev.hostId || u["owner-id"] === ev.hostId);
      modalHostEl.textContent = user ? (user["owner-name"] || ev.hostId) : ev.hostId;
    } else {
      modalHostEl.textContent = ev.host || "Community";
    }

    // Attendees
    const attendees = ev.attendees || [];
    modalAttendeesCountEl.textContent = attendees.length;

    // Rating (only for past events)
    const isPast = isPastEvent(ev.date);
    const currentUser = getCurrentUser();
    const baseRatings = ev.ratings || {};
    const values = Object.values(baseRatings);
    const count = values.length;
    const avg = count ? values.reduce((a, b) => a + b, 0) / count : null;
    const sessionValue = currentUser && currentUser.active ? sessionRatings[ev.id] : undefined;
    const storedUserRating = currentUser ? baseRatings[currentUser.id] : null;
    const userRating = currentUser && currentUser.active
      ? (sessionValue !== undefined ? sessionValue : storedUserRating || null)
      : storedUserRating || null;

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

    // (Optional) share button: simple alert for now
    if (modalShareBtn) {
      modalShareBtn.onclick = () => {
        alert("Sharing is simulated in this prototype.");
      };
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
    } else {
      form.reset();
      fieldId.value = "";
      document.getElementById("event-edit-title").textContent = "Create Event";
    }

    eventEditModal.style.display = "flex";
  }

  function closeEditModal() {
    if (eventEditModal) eventEditModal.style.display = "none";
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

    if (id) {
      saveEntityUpdate(id, {
        name,
        date: dateIso,
        localization: location,
        summary,
        description,
        type
      });
    } else {
      const newId = generateId(name);
      const currentUser = getCurrentUser() || {};
      const ev = {
        id: newId,
        name,
        date: dateIso,
        localization: location,
        summary,
        description,
        type,
        attendees: [],
        hostId: currentUser.id || null,
        ratings: {}
      };
      addEntity(ev);
    }

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

  // Close modals
  eventDetailClose?.addEventListener("click", closeEventDetail);
  modalCloseBtn?.addEventListener("click", closeEventDetail);
  eventEditClose?.addEventListener("click", closeEditModal);
  cancelEditBtn?.addEventListener("click", closeEditModal);

  // Close on backdrop click
  window.addEventListener("click", (ev) => {
    if (ev.target === eventDetailModal) closeEventDetail();
    if (ev.target === eventEditModal) closeEditModal();
  });

  // Re-render when user state changes so data-requires-login buttons reflect state
  window.addEventListener("userStateChange", renderEvents);

  // ---------- INITIAL RENDER ----------
  renderEvents();
});

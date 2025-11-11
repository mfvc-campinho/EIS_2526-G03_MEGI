// ===============================================
// app-events.js
// ===============================================
// Full client-side event manager for the Events page.
// - Renders events from appData
// - Filters upcoming/past/all
// - Create / Edit / Delete events (persisted via appData API)
// - Simple RSVP support (stores attendees array on event)
// ===============================================

document.addEventListener("DOMContentLoaded", () => {
  // Elements
  const eventsList = document.getElementById("eventsList");
  const newEventBtn = document.getElementById("newEventBtn");

  const eventDetailModal = document.getElementById("event-modal");
  const eventDetailClose = document.getElementById("close-event-modal");
  const eventDetailCloseBtn = document.getElementById("closeEventBtn");
  const eventTitleEl = document.getElementById("event-title");
  const eventMetaEl = document.getElementById("event-meta");
  const eventDescriptionEl = document.getElementById("event-description");
  const eventHostEl = document.getElementById("event-host");
  const eventAttendeesCountEl = document.getElementById("event-attendees-count");
  const rsvpBtn = document.getElementById("rsvpBtn");

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

  const filterSelect = document.getElementById("eventFilter");
  const typeSelect = document.getElementById("eventType");

  // Helpers
  function loadData() {
    return window.appData ? window.appData.loadData() : JSON.parse(localStorage.getItem("collectionsData"));
  }

  function todayStart() {
    const d = new Date();
    d.setHours(0, 0, 0, 0);
    return d;
  }

  function parseEventDate(dateStr) {
    if (!dateStr) return null;
    // Accepts YYYY-MM-DD or full ISO
    const d = new Date(dateStr);
    if (isNaN(d)) {
      // try with T00:00
      const tryD = new Date(dateStr + "T00:00:00");
      return isNaN(tryD) ? null : tryD;
    }
    return d;
  }

  function isUpcoming(dateStr) {
    const d = parseEventDate(dateStr);
    if (!d) return false;
    return d >= todayStart();
  }

  function formatDate(dateStr) {
    const d = parseEventDate(dateStr);
    if (!d) return "N/A";
    return d.toLocaleString();
  }

  // Rendering
  function renderEvents() {
    const data = loadData();
    if (!eventsList) return;
    eventsList.innerHTML = "";

    const filter = filterSelect?.value || "all";
    const typeFilter = typeSelect?.value || "all";

    // Clone and sort by date ascending
    const events = (data.events || []).slice().sort((a, b) => {
      const da = parseEventDate(a.date) || new Date(0);
      const db = parseEventDate(b.date) || new Date(0);
      return da - db;
    }).filter(ev => {
      if (filter === "upcoming" && !isUpcoming(ev.date)) return false;
      if (filter === "past" && isUpcoming(ev.date)) return false;
      if (typeFilter !== "all" && (ev.type || "").toLowerCase() !== typeFilter.toLowerCase()) return false;
      return true;
    });

    if (events.length === 0) {
      eventsList.innerHTML = "<p class=\"muted\">No events found.</p>";
      return;
    }

    events.forEach(ev => {
      const card = document.createElement("div");
      card.className = "collection-card";
      card.innerHTML = `
        <div class="card-body">
          <h3 class="card-title">${escapeHtml(ev.name)}</h3>
          <p class="muted">${escapeHtml(ev.localization || "")} · ${formatDate(ev.date)}</p>
          <p>${escapeHtml(ev.summary || "")}</p>
          <div class="card-actions">
            <button class="view-btn">View</button>
            <button class="rsvp-btn" data-id="${ev.id}" data-requires-login>RSVP</button>
            <button class="edit-btn" data-id="${ev.id}" data-requires-login>Edit</button>
            <button class="delete-btn" data-id="${ev.id}" data-requires-login>Delete</button>
          </div>
        </div>
      `;

      // Attach listeners
      card.querySelector(".view-btn").addEventListener("click", () => openEventDetail(ev.id));
      card.querySelector(".rsvp-btn").addEventListener("click", (e) => { e.preventDefault(); rsvpEvent(ev.id); });
      card.querySelector(".edit-btn").addEventListener("click", () => openEditModal(ev.id));
      card.querySelector(".delete-btn").addEventListener("click", () => deleteEvent(ev.id));

      eventsList.appendChild(card);
    });
  }

  // Safety: simple escape for text nodes
  function escapeHtml(str) {
    if (!str) return "";
    return String(str).replace(/[&<>\"']/g, s => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": "&#39;" }[s]));
  }

  // Detail modal
  function openEventDetail(id) {
    const data = loadData();
    const ev = (data.events || []).find(x => x.id === id);
    if (!ev) return alert("Event not found");

    eventTitleEl.textContent = ev.name;
    eventMetaEl.textContent = `${formatDate(ev.date)} · ${ev.localization || "TBD"}`;
    eventDescriptionEl.textContent = ev.description || ev.summary || "No description provided.";
    // Host resolution (if hostId exists)
    if (ev.hostId) {
      const user = (data.users || []).find(u => u["owner-id"] === ev.hostId || u.id === ev.hostId);
      eventHostEl.textContent = user ? (user["owner-name"] || user.name) : ev.hostId;
    } else {
      eventHostEl.textContent = ev.host || "Community";
    }

    eventAttendeesCountEl.textContent = (ev.attendees || []).length;

    // show modal
    if (eventDetailModal) eventDetailModal.style.display = "flex";

    // wire RSVP button (update listener)
    if (rsvpBtn) {
      rsvpBtn.onclick = () => rsvpEvent(id);
    }
  }

  function closeEventDetail() {
    if (eventDetailModal) eventDetailModal.style.display = "none";
  }

  // Edit / Create modal
  function openEditModal(id) {
    // If id provided, fill form
    const data = loadData();
    if (id) {
      const ev = (data.events || []).find(x => x.id === id);
      if (!ev) return alert("Event not found");
      fieldId.value = ev.id;
      fieldName.value = ev.name || "";
      // normalize for datetime-local if possible
      const d = parseEventDate(ev.date);
      if (d) {
        // yyyy-mm-ddThh:MM
        const iso = d.toISOString();
        fieldDate.value = iso.substring(0, 16);
      } else {
        fieldDate.value = "";
      }
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
    if (eventEditModal) eventEditModal.style.display = "flex";
  }

  function closeEditModal() {
    if (eventEditModal) eventEditModal.style.display = "none";
  }

  // CRUD actions
  function generateId(name) {
    const slug = (name || "event").toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/(^-|-$)/g, "");
    return `${slug}-${Date.now()}`;
  }

  function saveEventFromForm(e) {
    e.preventDefault();
    const id = fieldId.value && fieldId.value.trim();
    const name = fieldName.value.trim();
    const dateVal = fieldDate.value;
    const location = fieldLocation.value.trim();
    const summary = fieldSummary.value.trim();
    const description = fieldDescription.value.trim();
    const type = fieldType.value.trim();

    if (!name || !dateVal) return alert("Please provide at least a name and date.");

    const dateIso = (() => {
      // fieldDate is datetime-local; convert to ISO date string
      try { return new Date(dateVal).toISOString(); } catch (err) { return dateVal; }
    })();

    if (id) {
      // update
      window.appData.updateEntity("events", id, {
        name, date: dateIso, localization: location, summary, description, type
      });
    } else {
      const newId = generateId(name);
      const currentUser = JSON.parse(localStorage.getItem("currentUser")) || {};
      const ev = { id: newId, name, date: dateIso, localization: location, summary, description, type, attendees: [], hostId: currentUser.id || null };
      window.appData.addEntity("events", ev);
    }

    closeEditModal();
    renderEvents();
  }

  function deleteEvent(id) {
    if (!confirm("Delete this event? This action cannot be undone.")) return;
    window.appData.deleteEntity("events", id);
    renderEvents();
  }

  // RSVP
  function rsvpEvent(id) {
    const currentUser = JSON.parse(localStorage.getItem("currentUser")) || {};
    if (!currentUser || !currentUser.active) {
      return alert("Please sign in to RSVP.");
    }

    const data = loadData();
    const ev = (data.events || []).find(x => x.id === id);
    if (!ev) return alert("Event not found");

    if (!ev.attendees) ev.attendees = [];
    if (ev.attendees.includes(currentUser.id)) return alert("You have already RSVPed to this event.");

    ev.attendees.push(currentUser.id);
    window.appData.updateEntity("events", id, { attendees: ev.attendees });
    // update UI
    if (eventAttendeesCountEl && eventTitleEl.textContent === ev.name) {
      eventAttendeesCountEl.textContent = ev.attendees.length;
    }
    renderEvents();
    alert("✅ RSVP recorded (simulated persistence in localStorage).");
  }

  // Utilities: close on backdrop
  window.addEventListener("click", (ev) => {
    if (ev.target === eventDetailModal) closeEventDetail();
    if (ev.target === eventEditModal) closeEditModal();
  });

  // Wire modal close buttons
  eventDetailClose?.addEventListener("click", closeEventDetail);
  eventDetailCloseBtn?.addEventListener("click", closeEventDetail);
  eventEditClose?.addEventListener("click", closeEditModal);
  document.getElementById("cancel-event-edit")?.addEventListener("click", closeEditModal);

  // Wire top-level buttons
  newEventBtn?.addEventListener("click", () => openEditModal(null));

  // Form submit
  form?.addEventListener("submit", saveEventFromForm);

  // Filters
  filterSelect?.addEventListener("change", renderEvents);
  typeSelect?.addEventListener("change", renderEvents);

  // Re-render when user state changes so buttons with data-requires-login show/hide
  window.addEventListener("userStateChange", () => renderEvents());

  // Initial render
  renderEvents();
});

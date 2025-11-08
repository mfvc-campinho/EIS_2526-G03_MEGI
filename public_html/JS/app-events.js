// ===============================================
// app-events.js
// ===============================================
// Gere os eventos: criar, editar, apagar,
// e associar a coleções existentes.
// ===============================================

document.addEventListener("DOMContentLoaded", () => {
  const list = document.getElementById("events-list");
  const modal = document.getElementById("event-modal");
  const openBtn = document.getElementById("open-event-modal");
  const closeBtn = document.getElementById("close-event-modal");
  const form = document.getElementById("form-event");
  const idField = document.getElementById("event-id");
  const modalTitle = document.getElementById("event-modal-title");
  const checkboxContainer = document.getElementById("collections-checkboxes-event");

  const data = appData.loadData();
  data.collections.forEach(c => {
    const div = document.createElement("div");
    div.innerHTML = `<label><input type="checkbox" value="${c.id}"> ${c.name}</label>`;
    checkboxContainer.appendChild(div);
  });

  function openModal(edit = false) {
    modalTitle.textContent = edit ? "Editar Evento" : "Novo Evento";
    modal.style.display = "block";
  }

  function closeModal() {
    modal.style.display = "none";
    form.reset();
    idField.value = "";
  }

  function renderEvents() {
    const data = appData.loadData();
    list.innerHTML = "";
    data.events.forEach(ev => {
      const div = document.createElement("div");
      div.className = "event-card";
      div.innerHTML = `
        <p><strong>${ev.name}</strong> – ${ev.localization} (${ev.date})</p>
        <button onclick="editEvent('${ev.id}')">✏️</button>
        <button onclick="deleteEvent('${ev.id}')">🗑️</button>
      `;
      list.appendChild(div);
    });
  }

  openBtn.addEventListener("click", () => openModal(false));
  closeBtn.addEventListener("click", closeModal);
  window.addEventListener("click", (e) => { if (e.target === modal) closeModal(); });

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    const id = idField.value.trim();
    const newEvent = {
      id: id || "event-" + Date.now(),
      name: form["event-name"].value,
      localization: form["event-location"].value,
      date: form["event-date"].value
    };
    if (id) appData.updateEntity("events", id, newEvent);
    else {
      appData.addEntity("events", newEvent);
      const selected = [...form.querySelectorAll("input[type='checkbox']:checked")].map(cb => cb.value);
      selected.forEach(colId => appData.linkEventToCollection(newEvent.id, colId));
    }
    closeModal();
    renderEvents();
  });

  window.editEvent = (id) => {
    const data = appData.loadData();
    const ev = data.events.find(e => e.id === id);
    idField.value = ev.id;
    form["event-name"].value = ev.name;
    form["event-location"].value = ev.localization;
    form["event-date"].value = ev.date;
    openModal(true);
  };

  window.deleteEvent = (id) => {
    if (confirm("Remover este evento?")) {
      appData.deleteEntity("events", id);
      renderEvents();
    }
  };

  renderEvents();
});

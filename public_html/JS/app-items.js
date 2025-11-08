// ===============================================
// app-items.js
// ===============================================
// Gere os itens: criar, editar, apagar, e ligar
// a coleções via checkboxes no modal.
// ===============================================

document.addEventListener("DOMContentLoaded", () => {
  const list = document.getElementById("items-list");
  const modal = document.getElementById("item-modal");
  const openBtn = document.getElementById("open-item-modal");
  const closeBtn = document.getElementById("close-item-modal");
  const form = document.getElementById("form-item");
  const idField = document.getElementById("item-id");
  const modalTitle = document.getElementById("item-modal-title");
  const checkboxContainer = document.getElementById("collections-checkboxes");

  // preencher coleções
  const data = appData.loadData();
  data.collections.forEach(c => {
    const div = document.createElement("div");
    div.innerHTML = `<label><input type="checkbox" value="${c.id}"> ${c.name}</label>`;
    checkboxContainer.appendChild(div);
  });

  function openModal(edit = false) {
    modalTitle.textContent = edit ? "Editar Item" : "Novo Item";
    modal.style.display = "block";
  }

  function closeModal() {
    modal.style.display = "none";
    form.reset();
    idField.value = "";
  }

  function renderItems() {
    const data = appData.loadData();
    list.innerHTML = "";
    data.items.forEach(i => {
      const div = document.createElement("div");
      div.className = "item-card";
      div.innerHTML = `
        <p><strong>${i.name}</strong> – ${i.price}€</p>
        <button onclick="editItem('${i.id}')">✏️</button>
        <button onclick="deleteItem('${i.id}')">🗑️</button>
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
    const newItem = {
      id: id || "item-" + Date.now(),
      name: form["item-name"].value,
      price: parseFloat(form["item-price"].value) || 0,
      importance: form["item-importance"].value,
      image: form["item-image"].value || "../images/default.jpg"
    };
    if (id) appData.updateEntity("items", id, newItem);
    else {
      appData.addEntity("items", newItem);
      const selected = [...form.querySelectorAll("input[type='checkbox']:checked")].map(cb => cb.value);
      selected.forEach(colId => appData.linkItemToCollection(newItem.id, colId));
    }
    closeModal();
    renderItems();
  });

  window.editItem = (id) => {
    const data = appData.loadData();
    const item = data.items.find(i => i.id === id);
    idField.value = item.id;
    form["item-name"].value = item.name;
    form["item-price"].value = item.price;
    form["item-importance"].value = item.importance;
    form["item-image"].value = item.image;
    openModal(true);
  };

  window.deleteItem = (id) => {
    if (confirm("Remover este item?")) {
      appData.deleteEntity("items", id);
      renderItems();
    }
  };

  renderItems();
});

// ===============================================
// app-items.js — Manage items within a collection
// ===============================================

// 🔹 Ouvir o evento de login/logout e atualizar interface globalmente
window.addEventListener("userStateChange", (e) => {
  const user = e.detail;
  const isActiveUser = user && user.active;

  const addItemBtn = document.getElementById("add-item");
  if (addItemBtn) {
    addItemBtn.style.display = isActiveUser ? "inline-block" : "none";
  }

  // Atualiza os itens (mostra/esconde botões de edição)
  if (typeof renderItems === "function") renderItems();
});

document.addEventListener("DOMContentLoaded", () => {
  // 🔹 Ler utilizador atual (vindo do app-users.js)
  const userData = JSON.parse(localStorage.getItem("currentUser"));
  const currentUser = userData ? userData.name : "guest";
  const isActiveUser = userData && userData.active;

  // Seletores principais
  const itemsContainer = document.getElementById("collection-items");
  const modal = document.getElementById("item-modal");
  const form = document.getElementById("item-form");
  const closeBtn = document.getElementById("close-modal");
  const cancelBtn = document.getElementById("cancel-modal");
  const addItemBtn = document.getElementById("add-item");
  const title = document.getElementById("modal-title");
  const idField = document.getElementById("item-id");

  // 🔹 Esconde o botão Add Item se o perfil não estiver ativo
  if (!isActiveUser && addItemBtn) addItemBtn.style.display = "none";

  // Obtém o ID da coleção a partir da URL
  const params = new URLSearchParams(window.location.search);
  const collectionId = params.get("id");

  // ===============================================
  // 🔹 Renderizar itens da coleção atual (usando relação N:N)
  // ===============================================
  window.renderItems = function renderItems() {
    const items = appData.getItemsByCollection(collectionId);
    itemsContainer.innerHTML = "";

    if (!items || items.length === 0) {
      itemsContainer.innerHTML = `<p>No items yet.</p>`;
      return;
    }

    items.forEach(item => {
      const card = document.createElement("div");
      card.className = "item-card";

      // 🔹 Dono do item
      const isOwner = isActiveUser && (item.owner === "collector" || item.owner === currentUser);
      const ownerTag = `
        <p style="font-size:0.85rem;color:#555;margin-top:6px;">
          👤 <strong>${item.owner || "Unknown"}</strong>
        </p>
      `;

      // 🔹 Botões (edit/delete só aparecem se perfil ativo)
      const buttons = isOwner
        ? `
          <div class="item-buttons">
            <button class="explore-btn" onclick="editItem('${item.id}')">✏️ Edit</button>
            <button class="explore-btn danger" onclick="deleteItem('${item.id}')">🗑️ Delete</button>
          </div>`
        : "";

      // 🔹 Template
      card.innerHTML = `
        <div class="item-image-wrapper">
          <img src="${item.image}" alt="${item.name}" class="item-image">
        </div>
        <div class="item-info">
          <h3>${item.name}</h3>
          <ul class="item-details">
            <li><strong>Importance:</strong> ${item.importance}</li>
            <li><strong>Weight:</strong> ${item.weight || "N/A"} g</li>
            <li><strong>Price:</strong> €${item.price || "0.00"}</li>
            <li><strong>Date:</strong> ${item.date || "-"}</li>
          </ul>
          ${ownerTag}
          ${buttons}
        </div>`;
      itemsContainer.appendChild(card);
    });
  };

  // ===============================================
  // 🔹 Preencher lista de coleções do utilizador atual
  // ===============================================
  function populateCollectionsSelect() {
    const select = document.getElementById("item-collections");
    const data = appData.loadData();

    if (!data || !data.collections) return;

    select.innerHTML = "";
    const userCollections = data.collections.filter(c => c.owner === "collector" || c.owner === currentUser);

    userCollections.forEach(col => {
      const option = document.createElement("option");
      option.value = col.id;
      option.textContent = col.name;
      select.appendChild(option);
    });
  }

  // ===============================================
  // 🔹 Modal helpers
  // ===============================================
  function openModal(edit = false) {
    title.textContent = edit ? "Edit Item" : "Add Item";
    modal.style.display = "flex";
  }

  function closeModal() {
    modal.style.display = "none";
    form.reset();
    idField.value = "";
  }

  // ===============================================
  // 🔹 Criar / Editar / Apagar / Guardar
  // ===============================================
  window.editItem = (id) => {
    if (!isActiveUser) return alert("🚫 You must be logged in to edit items.");

    const data = appData.loadData();
    const item = data.items.find(i => i.id === id);
    if (!item) return alert("Item not found");
    if (item.owner !== currentUser && item.owner !== "collector")
      return alert("🚫 You cannot edit this item.");

    idField.value = item.id;
    form["item-name"].value = item.name;
    form["item-importance"].value = item.importance;
    form["item-weight"].value = item.weight || "";
    form["item-price"].value = item.price || "";
    form["item-date"].value = item.date || "";
    form["item-image"].value = item.image || "";
    openModal(true);
  };

  window.deleteItem = (id) => {
    if (!isActiveUser) return alert("🚫 You must be logged in to delete items.");

    const data = appData.loadData();
    const item = data.items.find(i => i.id === id);
    if (item.owner !== currentUser && item.owner !== "collector")
      return alert("🚫 You can only delete your own items.");

    if (!confirm("Delete this item?")) return;
    appData.deleteEntity("items", id);
    renderItems();
  };

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    if (!isActiveUser) return alert("🚫 You must be logged in to add items.");

    const id = idField.value.trim();
    const selectedCollections = Array.from(form["item-collections"].selectedOptions).map(opt => opt.value);

    const newItem = {
      id: id || "item-" + Date.now(),
      owner: "collector",
      name: form["item-name"].value,
      importance: form["item-importance"].value,
      weight: parseFloat(form["item-weight"].value) || null,
      price: parseFloat(form["item-price"].value) || 0,
      date: form["item-date"].value,
      image: form["item-image"].value || "../images/default.jpg"
    };

    if (id) appData.updateEntity("items", id, newItem);
    else {
      appData.addEntity("items", newItem);
      selectedCollections.forEach(cid => appData.linkItemToCollection(newItem.id, cid));
    }

    closeModal();
    renderItems();
  });

  if (addItemBtn) addItemBtn.addEventListener("click", () => openModal(false));
  if (closeBtn) closeBtn.addEventListener("click", closeModal);
  if (cancelBtn) cancelBtn.addEventListener("click", closeModal);
  window.addEventListener("click", (e) => { if (e.target === modal) closeModal(); });

  // Inicialização
  populateCollectionsSelect();
  renderItems();
});

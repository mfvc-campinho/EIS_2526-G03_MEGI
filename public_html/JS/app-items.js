// ===============================================
// app-items.js — Manage items within a collection
// ===============================================
document.addEventListener("DOMContentLoaded", () => {
  // ===============================================
  // 🔹 User State Management
  // ===============================================
  let currentUser;
  let isActiveUser;

  function updateUserState() {
    const userData = JSON.parse(localStorage.getItem("currentUser"));
    currentUser = userData ? userData.name : null;
    isActiveUser = userData && userData.active;

    // Esconde/mostra botões que requerem login
    document.querySelectorAll("[data-requires-login]").forEach(btn => {
      btn.style.display = isActiveUser ? "inline-block" : "none";
    });
  }

  updateUserState(); // Define o estado inicial

  // Seletores principais
  const itemsContainer = document.getElementById("collection-items");
  const modal = document.getElementById("item-modal");
  const form = document.getElementById("item-form");
  const closeBtn = document.getElementById("close-modal");
  const cancelBtn = document.getElementById("cancel-modal");
  const addItemBtn = document.getElementById("add-item");
  const title = document.getElementById("modal-title");
  const idField = document.getElementById("item-id");

  // Obtém o ID da coleção a partir da URL
  const params = new URLSearchParams(window.location.search);
  const collectionId = params.get("id");

  // ===============================================
  // 🔹 Destacar secção se for do utilizador
  // ===============================================
  function highlightOwnedSection() {
    const data = appData.loadData();
    const collection = data.collections.find(c => c.id === collectionId);

    if (isActiveUser && collection && collection.owner?.toLowerCase() === currentUser.toLowerCase()) {
      itemsContainer.classList.add("owned-section");
    } else {
      itemsContainer.classList.remove("owned-section");
    }
  }

  // ===============================================
  // 🔹 Renderizar itens da coleção atual (usando relação N:N)
  // ===============================================
  window.renderItems = function renderItems() {
    const data = appData.loadData();
    const collection = data.collections.find(c => c.id === collectionId);
    const items = appData.getItemsByCollection(collectionId);

    itemsContainer.innerHTML = "";

    if (!items || items.length === 0) {
      itemsContainer.innerHTML = `<p class="no-items-message">This collection has no items yet.</p>`;
      return;
    }

    const isCollectionOwner = isActiveUser && collection && (collection.owner?.toLowerCase() === currentUser?.toLowerCase());
    let cardsHTML = "";

    for (const item of items) {
      const card = document.createElement("div");
      card.className = "item-card";

      // 🔹 Botões (edit/delete só aparecem se perfil ativo)
      const buttons = isCollectionOwner
        ? `
          <div class="item-buttons">
            <button class="explore-btn" onclick="editItem('${item.id}')">✏️ Edit</button>
            <button class="explore-btn danger" onclick="deleteItem('${item.id}')">🗑️ Delete</button>
          </div>`
        : "";

      // 🔹 Template
      cardsHTML += `
        <div class="card item-card">
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
            ${buttons}
          </div>
        </div>
      `;
    }
    itemsContainer.innerHTML = cardsHTML;
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

  // Ouve o evento de login/logout e atualiza a página
  window.addEventListener("userStateChange", () => {
    updateUserState();
    renderItems();
  });

  // Inicialização
  populateCollectionsSelect();
  renderItems();
  highlightOwnedSection(); // Chama a nova função
});

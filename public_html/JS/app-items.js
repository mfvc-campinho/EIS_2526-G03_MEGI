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

  // Seletores para o modal da coleção
  const collectionModal = document.getElementById("collection-modal");
  const collectionForm = document.getElementById("form-collection");
  const editCollectionBtn = document.getElementById("edit-collection");
  const closeCollectionModalBtn = document.getElementById("close-collection-modal");
  const cancelCollectionModalBtn = document.getElementById("cancel-collection-modal");

  // Obtém o ID da coleção a partir da URL
  const params = new URLSearchParams(window.location.search);
  const collectionId = params.get("id");

  // ===============================================
  // 🔹 Renderizar detalhes da coleção (título, dono, etc.)
  // ===============================================
  function renderCollectionDetails() {
    const data = appData.loadData();
    const collection = data.collections.find(c => c.id === collectionId);

    if (collection) {
      document.getElementById("collection-title").textContent = collection.name;
      document.getElementById("owner-name").textContent = collection.owner;
      document.getElementById("creation-date").textContent = collection.createdAt;
      document.getElementById("type").textContent = collection.type || "N/A";
      document.getElementById("description").textContent = collection.description || "No description provided.";
    } else {
      // Se a coleção não for encontrada, mostra uma mensagem de erro
      document.getElementById("collection-title").textContent = "Collection Not Found";
      // Esconde os botões de ação se a coleção não existir
      if (addItemBtn) addItemBtn.style.display = 'none';
      if (editCollectionBtn) editCollectionBtn.style.display = 'none';
    }
  }

  // ===============================================
  // 🔹 Destacar secção se for do utilizador
  // ===============================================
  function highlightOwnedSection() {
    const data = appData.loadData();
    const collection = data.collections.find(c => c.id === collectionId);

    if (isActiveUser && collection && collection.owner?.toLowerCase() === currentUser.toLowerCase()) {
      itemsContainer.classList.add("owned-section");
      // Mostra o botão de editar coleção se for o dono
      if (editCollectionBtn) editCollectionBtn.style.display = "inline-block";
      if (addItemBtn) addItemBtn.style.display = "inline-block";
    } else {
      itemsContainer.classList.remove("owned-section");
      // Esconde o botão se não for o dono
      if (editCollectionBtn) editCollectionBtn.style.display = "none";
      if (addItemBtn) addItemBtn.style.display = "none";
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

    let cardsHTML = "";

    for (const item of items) {
      const isItemOwner = isActiveUser && item.owner && (item.owner.toLowerCase() === currentUser?.toLowerCase());

      const card = document.createElement("div");
      card.className = "item-card";

      // 🔹 Botões (edit/delete só aparecem se perfil ativo)
      const buttons = isItemOwner
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
            <img src="${item.image}" alt="${item.name}" class="item-image" loading="lazy">
          </div>
          <div class="item-info">
            <h3>${item.name}</h3>
            <ul class="item-details">
              <li><strong>Importance:</strong> ${item.importance}</li>
              <li><strong>Weight:</strong> ${item.weight || "N/A"} g</li>
              <li><strong>Price:</strong> €${item.price || "0.00"}</li>
              <li><strong>Date:</strong> ${item.acquisitionDate || "-"}</li>
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
    form["item-date"].value = item.acquisitionDate || "";
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

  // ===============================================
  // 🔹 Lógica para Editar a Coleção
  // ===============================================
  function openCollectionModal() {
    const data = appData.loadData();
    const collection = data.collections.find(c => c.id === collectionId);

    if (!collection) return alert("Collection not found!");
    if (!isActiveUser || collection.owner?.toLowerCase() !== currentUser.toLowerCase()) {
      return alert("🚫 You can only edit your own collections.");
    }

    // Preenche o formulário do modal da coleção
    collectionForm.querySelector("#collection-id").value = collection.id;
    collectionForm.querySelector("#col-name").value = collection.name;
    collectionForm.querySelector("#col-summary").value = collection.summary || "";
    collectionForm.querySelector("#col-description").value = collection.description || "";
    collectionForm.querySelector("#col-image").value = collection.coverImage || "";
    collectionForm.querySelector("#col-type").value = collection.type || "";

    collectionModal.style.display = "flex";
  }

  function closeCollectionModal() {
    if (collectionModal) collectionModal.style.display = "none";
  }

  if (editCollectionBtn) {
    editCollectionBtn.addEventListener("click", openCollectionModal);
  }
  if (closeCollectionModalBtn) {
    closeCollectionModalBtn.addEventListener("click", closeCollectionModal);
  }
  if (cancelCollectionModalBtn) {
    cancelCollectionModalBtn.addEventListener("click", closeCollectionModal);
  }
  if (collectionForm) {
    collectionForm.addEventListener("submit", (e) => {
      e.preventDefault();
      const id = collectionForm.querySelector("#collection-id").value;
      const updatedFields = {
        name: collectionForm.querySelector("#col-name").value,
        summary: collectionForm.querySelector("#col-summary").value,
        description: collectionForm.querySelector("#col-description").value,
        coverImage: collectionForm.querySelector("#col-image").value,
        type: collectionForm.querySelector("#col-type").value,
      };

      appData.updateEntity("collections", id, updatedFields);
      closeCollectionModal();
      renderCollectionDetails(); // Re-renderiza os detalhes para mostrar as alterações
      alert("✅ Collection updated successfully!");
    });
  }

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
      acquisitionDate: form["item-date"].value,
      image: form["item-image"].value || "../images/default.jpg"
    };

    if (id) appData.updateEntity("items", id, newItem);
    else { // Ao criar um novo item
      appData.addEntity("items", newItem);
      selectedCollections.forEach(cid => appData.linkItemToCollection(newItem.id, cid));
    }

    closeModal();
    renderItems();
  });

  if (addItemBtn) addItemBtn.addEventListener("click", () => openModal(false));
  if (closeBtn) closeBtn.addEventListener("click", closeModal);
  if (cancelBtn) cancelBtn.addEventListener("click", closeModal);
  window.addEventListener("click", (e) => {
    if (e.target === modal) closeModal();
    if (e.target === collectionModal) closeCollectionModal();
  });

  // Ouve o evento de login/logout e atualiza a página
  window.addEventListener("userStateChange", () => {
    updateUserState();
    highlightOwnedSection(); // Atualiza a visibilidade dos botões
    renderItems();
  });

  // Inicialização
  renderCollectionDetails(); // Preenche os detalhes da coleção
  populateCollectionsSelect();
  renderItems();
  highlightOwnedSection();
});

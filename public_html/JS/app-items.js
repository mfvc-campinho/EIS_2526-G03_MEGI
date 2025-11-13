// ===============================================
// app-items.js ├óÔé¼ÔÇØ Manage items within a collection
// ===============================================
document.addEventListener("DOMContentLoaded", () => {
  // ===============================================
  // User State Management
  // ===============================================
  const DEFAULT_OWNER_ID = "collector-main";
  let currentUserId;
  let currentUserName;
  let isActiveUser;

  function updateUserState() {
    const userData = JSON.parse(localStorage.getItem("currentUser"));
    currentUserId = userData ? userData.id : null;
    currentUserName = userData ? userData.name : null;
    isActiveUser = Boolean(userData && userData.active);

    // Esconde/mostra bot├â┬Áes que requerem login
    document.querySelectorAll("[data-requires-login]").forEach(btn => {
      btn.style.display = isActiveUser ? "inline-block" : "none";
    });
  }

  updateUserState(); // Define o estado inicial

  // Seletores principais
  const itemsContainer = document.getElementById("collection-items");
  const eventsContainer = document.getElementById("collection-events");
  const modal = document.getElementById("item-modal");
  const form = document.getElementById("item-form");
  const closeBtn = document.getElementById("close-modal");
  const cancelBtn = document.getElementById("cancel-modal");
  const addItemBtn = document.getElementById("add-item");
  const title = document.getElementById("modal-title");
  const idField = document.getElementById("item-id");

  // Seletores para o modal da cole��o
  const collectionModal = document.getElementById("collection-modal");
  const collectionForm = document.getElementById("form-collection");
  const editCollectionBtn = document.getElementById("edit-collection");
  const closeCollectionModalBtn = document.getElementById("close-collection-modal");
  const cancelCollectionModalBtn = document.getElementById("cancel-collection-modal");
  const hasCollectionPage = Boolean(itemsContainer);
  // Obt�m o ID da cole��o a partir da URL
  const params = new URLSearchParams(window.location.search);
  let collectionId = params.get("id");

  function getOwnerIdForCollection(target, data = appData.loadData()) {
    const id = typeof target === "string" ? target : target?.id;
    if (!id) return null;
    const linkOwner = appData.getCollectionOwnerId(id, data);
    if (linkOwner) return linkOwner;
    const direct = (data.collections || []).find(c => c.id === id);
    return direct?.ownerId || null;

  }

  function getOwnerProfileForCollection(target, data = appData.loadData()) {
    const id = typeof target === "string" ? target : target?.id;
    if (!id) return null;
    const profile = appData.getCollectionOwner(id, data);
    if (profile) return profile;
    const fallback = (data.collections || []).find(c => c.id === id);
    if (!fallback) return null;
    return {
      ["owner-name"]: fallback.ownerName || fallback["owner-name"],
      ["owner-photo"]: fallback.ownerPhoto || fallback["owner-photo"]
    };
  }

  function formatEventDate(dateStr) {
    if (!dateStr) return "Date TBA";
    const date = new Date(dateStr);
    if (Number.isNaN(date.getTime())) return dateStr;
    return date.toLocaleDateString(undefined, {
      year: "numeric",
      month: "short",
      day: "numeric"
    });
  }

  function formatEventDate(dateStr) {
    if (!dateStr) return "Date TBA";
    const date = new Date(dateStr);
    if (Number.isNaN(date.getTime())) return dateStr;
    return date.toLocaleDateString(undefined, {
      year: "numeric",
      month: "short",
      day: "numeric"
    });
  }

  function getCurrentCollection(data = appData.loadData()) {
    return data.collections.find(c => c.id === collectionId);
  }

  window.setCurrentCollectionId = (newId) => {
    collectionId = newId;
  };

  function getEffectiveOwnerId() {
    if (!isActiveUser) return null;
    return currentUserId || DEFAULT_OWNER_ID;
  }

  function isCollectionOwnedByCurrentUser(collection, data) {
    const ownerId = getEffectiveOwnerId();
    if (!ownerId || !collection) return false;
    const collectionOwnerId = getOwnerIdForCollection(collection, data);
    return Boolean(collectionOwnerId && collectionOwnerId === ownerId);
  }

  // ===============================================
  // Renderizar detalhes da cole├â┬º├â┬úo (t├â┬¡tulo, dono, etc.)
  // ===============================================
  function renderCollectionDetails() {
    if (!hasCollectionPage) return;
    const data = appData.loadData();
    const collection = getCurrentCollection(data);

    if (collection) {
      document.getElementById("collection-title").textContent = collection.name;
      const ownerProfile = getOwnerProfileForCollection(collection, data) || {};
      const collectionOwnerId = getOwnerIdForCollection(collection, data);
      const ownerDisplayName =
        ownerProfile["owner-name"] ||
        ownerProfile.name ||
        collectionOwnerId ||
        "Unknown Owner";

      const ownerNameEl = document.getElementById("owner-name");
      if (ownerNameEl) {
        ownerNameEl.textContent = ownerDisplayName;
        ownerNameEl.dataset.ownerId = collectionOwnerId || "";
        ownerNameEl.classList.toggle("owner-link", Boolean(collectionOwnerId));
        if (collectionOwnerId) {
          ownerNameEl.setAttribute("role", "link");
          ownerNameEl.tabIndex = 0;
          const goToProfile = () => {
            window.location.href = `user_page.html?owner=${encodeURIComponent(collectionOwnerId)}`;
          };
          ownerNameEl.onclick = goToProfile;
          ownerNameEl.onkeydown = (event) => {
            if (event.key === "Enter" || event.key === " ") {
              event.preventDefault();
              goToProfile();
            }
          };
        } else {
          ownerNameEl.removeAttribute("role");
          ownerNameEl.removeAttribute("tabindex");
          ownerNameEl.onclick = null;
          ownerNameEl.onkeydown = null;
        }
      }
      document.getElementById("creation-date").textContent = collection.createdAt;
      document.getElementById("type").textContent = collection.type || "N/A";
      document.getElementById("description").textContent =
        collection.description || "No description provided.";

      const ownerPhotoEl = document.getElementById("owner-photo");
      if (ownerPhotoEl) {
        const collectorDefault = "../images/rui.jpg";
        const guestDefault = "../images/user.jpg";
        const fallback =
          collectionOwnerId === DEFAULT_OWNER_ID ? collectorDefault : guestDefault;
        ownerPhotoEl.src = ownerProfile["owner-photo"] || ownerProfile.photo || fallback;
        ownerPhotoEl.alt = `${ownerDisplayName} owner`;
      }
    } else {
      // Se a cole├â┬º├â┬úo n├â┬úo for encontrada, mostra uma mensagem de erro
      document.getElementById("collection-title").textContent = "Collection Not Found";
      // Esconde os bot├â┬Áes de a├â┬º├â┬úo se a cole├â┬º├â┬úo n├â┬úo existir
      if (addItemBtn) addItemBtn.style.display = "none";
      if (editCollectionBtn) editCollectionBtn.style.display = "none";
    }
  }

  // ===============================================
  // Destacar sec├â┬º├â┬úo se for do utilizador
  // ===============================================
  function highlightOwnedSection() {
    if (!hasCollectionPage) return;
    const data = appData.loadData();
    const collection = getCurrentCollection(data);

    if (isCollectionOwnedByCurrentUser(collection, data)) {
      itemsContainer.classList.add("owned-section");
      // Mostra os bot├â┬Áes se for o dono
      if (editCollectionBtn) editCollectionBtn.style.display = "inline-block";
      if (addItemBtn) addItemBtn.style.display = "inline-block";
    } else {
      itemsContainer.classList.remove("owned-section");
      // Esconde os bot├â┬Áes se n├â┬úo for o dono
      if (editCollectionBtn) editCollectionBtn.style.display = "none";
      if (addItemBtn) addItemBtn.style.display = "none";
    }
  }

  // ===============================================
  // Renderizar itens da cole├â┬º├â┬úo atual (rela├â┬º├â┬úo N:N)
  // ===============================================
  // Tornada global para ser chamada por outros scripts
  window.renderItems = function renderItems() {
    if (!hasCollectionPage) return;
    const data = appData.loadData();
    const collection = getCurrentCollection(data);
    const ownsCollection = isCollectionOwnedByCurrentUser(collection, data);
    const items = appData.getItemsByCollection(collectionId, data);

    itemsContainer.innerHTML = "";

    if (!items || items.length === 0) {
      const emptyMessage = document.createElement("p");
      emptyMessage.className = "no-items-message";
      emptyMessage.textContent = "This collection has no items yet.";
      itemsContainer.appendChild(emptyMessage);
      return;
    }

    const loadingMessage = document.createElement("p");
    loadingMessage.className = "notice-message";
    loadingMessage.textContent = "Loading items...";
    itemsContainer.appendChild(loadingMessage);

    // Fun├â┬º├â┬úo para renderizar itens em lotes (chunks)
    function renderChunk(index = 0) {
      const chunkSize = 50; // Renderiza 50 itens de cada vez
      const fragment = document.createDocumentFragment();
      const chunk = items.slice(index, index + chunkSize);

      for (const item of chunk) {
        const isItemOwner = ownsCollection;
        const card = document.createElement("div");
        card.className = "card item-card";

        const ownerButtons = isItemOwner
          ? `
            <div class="item-buttons">
              <button class="explore-btn" onclick="editItem('${item.id}')"><i class="bi bi-pencil"></i> Edit</button>
              <button class="explore-btn danger" onclick="deleteItem('${item.id}')"><i class="bi bi-trash"></i> Delete</button>
            </div>`
          : "";

        card.innerHTML = `
      <div class="item-image-wrapper">
        <img src="${item.image}" alt="${item.name}" class="item-image" loading="lazy">
      </div>

      <div class="item-info simple-item">
        <h3>${item.name}</h3>

        <div class="item-actions">
          <a href="item_page.html?id=${item.id}" class="explore-btn view-btn">
            <i class="bi bi-eye"></i> View Item
          </a>
        </div>
        ${ownerButtons}
      </div>
    `;
        fragment.appendChild(card);
      }

      if (index === 0 && loadingMessage.isConnected) {
        loadingMessage.remove();
      }
      itemsContainer.appendChild(fragment);

      if (index + chunkSize < items.length) {
        // Agenda o pr├â┬│ximo lote sem bloquear o browser
        setTimeout(() => renderChunk(index + chunkSize), 0);
      }
    }

    // Inicia o processo de renderiza├â┬º├â┬úo
    renderChunk();
  };

  function renderCollectionEvents() {
    if (!eventsContainer) return;
    const data = appData.loadData();
    const collection = getCurrentCollection(data);
    const returnTarget = `${window.location.pathname}${window.location.search || ""}`;
    const encodedReturn = encodeURIComponent(returnTarget);
    const fromQuery = encodedReturn ? `&from=${encodedReturn}` : "";

    if (!collection) {
      eventsContainer.innerHTML = `<p class="notice-message">Collection not found.</p>`;
      return;
    }

    const events = appData.getEventsByCollection(collection.id, data) || [];
    if (!events.length) {
      eventsContainer.innerHTML = `<p class="notice-message">No events linked to this collection yet.</p>`;
      return;
    }

    eventsContainer.innerHTML = events.map(ev => `
      <article class="collection-event-card">
        <div>
          <h3>${ev.name}</h3>
          <p class="event-meta">${formatEventDate(ev.date)} ┬À ${ev.localization || "To be announced"}</p>
        </div>
        <a class="explore-btn ghost" href="event_page.html?id=${ev.id}${fromQuery}">
          <i class="bi bi-calendar-event"></i> View event
        </a>
      </article>
    `).join("");
  }

  // ===============================================
  // Preencher lista de cole├â┬º├â┬Áes do utilizador atual
  // ===============================================
  function populateCollectionsSelect() {
    const select = document.getElementById("item-collections");
    if (!select) return;
    const data = appData.loadData();

    if (!data || !data.collections) return;

    select.innerHTML = "";
    const ownerId = getEffectiveOwnerId();

    const userCollections = data.collections.filter(c => {
      const colOwnerId = getOwnerIdForCollection(c, data);
      return colOwnerId === DEFAULT_OWNER_ID || (ownerId && colOwnerId === ownerId);
    });

    userCollections.forEach(col => {
      const option = document.createElement("option");
      option.value = col.id;
      option.textContent = col.name;
      select.appendChild(option);
    });
  }

  // ===============================================
  // Modal helpers
  // ===============================================
  function openModal(edit = false) {
    if (!modal || !title) return;
    title.textContent = edit ? "Edit Item" : "Add Item";
    modal.style.display = "flex";
  }

  function closeModal() {
    if (!modal || !form || !idField) return;
    modal.style.display = "none";
    form.reset();
    idField.value = "";
  }

  window.openItemModal = (edit = false) => {
    openModal(edit);
  };

  // ===============================================
  // Criar / Editar / Apagar / Guardar
  // ===============================================
  window.editItem = (id) => {
    if (!isActiveUser) return alert("├░┼©┼í┬½ You must be logged in to edit items.");

    const data = appData.loadData();
    const item = data.items.find(i => i.id === id);
    if (!item) return alert("Item not found");
    const collection = getCurrentCollection(data);
    if (!isCollectionOwnedByCurrentUser(collection, data)) {
      return alert("├░┼©┼í┬½ You cannot edit this item.");
    }

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
    if (!isActiveUser) return alert("├░┼©┼í┬½ You must be logged in to delete items.");

    const data = appData.loadData();
    const item = data.items.find(i => i.id === id);
    const collection = getCurrentCollection(data);

    if (!isCollectionOwnedByCurrentUser(collection, data)) {
      return alert("├░┼©┼í┬½ You can only delete your own items.");
    }

    if (confirm("Delete this item?\n\n(This is a demonstration. No data will be changed.)")) {
      alert("├ó┼ôÔÇª Simulation successful. No data was deleted.");
    }
  };

  // ===============================================
  // L├â┬│gica para Editar a Cole├â┬º├â┬úo
  // ===============================================
  function openCollectionModal() {
    const data = appData.loadData();
    const collection = data.collections.find(c => c.id === collectionId);

    if (!collection) return alert("Collection not found!");
    if (!isCollectionOwnedByCurrentUser(collection, data)) {
      return alert("├░┼©┼í┬½ You can only edit your own collections.");
    }

    // Preenche o formul├â┬írio do modal da cole├â┬º├â┬úo
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

      alert(
        "├ó┼ôÔÇª Simulation successful. Collection would have been updated.\n\n(This is a demonstration. No data was saved.)"
      );

      closeCollectionModal();
      // N├â┬úo renderiza novamente para n├â┬úo dar a falsa impress├â┬úo de que os dados mudaram.
      // renderCollectionDetails();
    });
  }

  if (form) {
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      if (!isActiveUser) return alert("You must be logged in to add items.");

      const id = idField.value.trim();
      const selectedCollections = Array.from(
        form["item-collections"].selectedOptions
      ).map(opt => opt.value);

      const action = id ? "updated" : "created";

      alert(
        `Simulation successful. Item would have been ${action}.\n\n(This is a demonstration. No data was saved.)`
      );

      closeModal();
      // A renderização foi removida para não mostrar alterações que não aconteceram
      // renderItems();
    });
  }



  if (addItemBtn) addItemBtn.addEventListener("click", () => openModal(false));
  if (closeBtn) closeBtn.addEventListener("click", closeModal);
  if (cancelBtn) cancelBtn.addEventListener("click", closeModal);

  window.addEventListener("click", (e) => {
    if (e.target === modal) closeModal();
    if (e.target === collectionModal) closeCollectionModal();
  });

  function handleItemActionParam() {
    const actionParams = new URLSearchParams(window.location.search);
    const action = actionParams.get("itemAction");
    const targetItemId = actionParams.get("itemId");
    if (!action) return;

    if (action === "add") {
      openModal(false);
    } else if (action === "edit" && targetItemId) {
      window.editItem(targetItemId);
    } else if (action === "delete" && targetItemId) {
      window.deleteItem(targetItemId);
    }

    actionParams.delete("itemAction");
    actionParams.delete("itemId");
    const remaining = actionParams.toString();
    const nextUrl = remaining ? `${window.location.pathname}?${remaining}` : window.location.pathname;
    window.history.replaceState({}, "", nextUrl);
  }

  // Ouve o evento de login/logout e atualiza a p├â┬ígina
  window.addEventListener("userStateChange", (e) => {
    const newUserData = e.detail;
    const newIsActiveUser = newUserData && newUserData.active;

    // S├â┬│ renderiza de novo se o estado de login MUDOU
    if (newIsActiveUser === isActiveUser) return;

    updateUserState();
    highlightOwnedSection();
    renderCollectionEvents();
    renderItems();
  });

  // Inicializa├â┬º├â┬úo
  populateCollectionsSelect();  // Preenche select de colecoes (se existir)
  if (hasCollectionPage) {
    renderCollectionDetails();   // Preenche os detalhes da colecao
    renderItems();               // Renderiza itens da colecao
    highlightOwnedSection();     // Destaca se for dono
  }
  renderCollectionEvents();      // Lista eventos associados (se houver container)
  handleItemActionParam();      // Executa acoes vindas da item_page
});


window.viewItem = function viewItem(itemId) {
  // Save the current item ID to localStorage so the detail page can load it
  localStorage.setItem("currentItemId", itemId);

  // Redirect to the item page
  window.location.href = `item_page.html?id=${encodeURIComponent(itemId)}`;
};




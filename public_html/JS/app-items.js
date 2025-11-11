// ===============================================
// app-items.js â€” Manage items within a collection
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

    // Esconde/mostra botÃµes que requerem login
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

  // Seletores para o modal da coleÃ§Ã£o
  const collectionModal = document.getElementById("collection-modal");
  const collectionForm = document.getElementById("form-collection");
  const editCollectionBtn = document.getElementById("edit-collection");
  const closeCollectionModalBtn = document.getElementById("close-collection-modal");
  const cancelCollectionModalBtn = document.getElementById("cancel-collection-modal");

  // ObtÃ©m o ID da coleÃ§Ã£o a partir da URL
  const params = new URLSearchParams(window.location.search);
  const collectionId = params.get("id");

  function getOwnerIdForCollection(target, data = appData.loadData()) {
    const id = typeof target === "string" ? target : target?.id;
    if (!id) return null;
    return appData.getCollectionOwnerId(id, data);
  }

  function getOwnerProfileForCollection(target, data = appData.loadData()) {
    const id = typeof target === "string" ? target : target?.id;
    if (!id) return null;
    return appData.getCollectionOwner(id, data);
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
  // Renderizar detalhes da coleÃ§Ã£o (tÃ­tulo, dono, etc.)
  // ===============================================
  function renderCollectionDetails() {
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
      // Se a coleÃ§Ã£o nÃ£o for encontrada, mostra uma mensagem de erro
      document.getElementById("collection-title").textContent = "Collection Not Found";
      // Esconde os botÃµes de aÃ§Ã£o se a coleÃ§Ã£o nÃ£o existir
      if (addItemBtn) addItemBtn.style.display = "none";
      if (editCollectionBtn) editCollectionBtn.style.display = "none";
    }
  }

  // ===============================================
  // Destacar secÃ§Ã£o se for do utilizador
  // ===============================================
  function highlightOwnedSection() {
    const data = appData.loadData();
    const collection = getCurrentCollection(data);

    if (isCollectionOwnedByCurrentUser(collection, data)) {
      itemsContainer.classList.add("owned-section");
      // Mostra os botÃµes se for o dono
      if (editCollectionBtn) editCollectionBtn.style.display = "inline-block";
      if (addItemBtn) addItemBtn.style.display = "inline-block";
    } else {
      itemsContainer.classList.remove("owned-section");
      // Esconde os botÃµes se nÃ£o for o dono
      if (editCollectionBtn) editCollectionBtn.style.display = "none";
      if (addItemBtn) addItemBtn.style.display = "none";
    }
  }

  // ===============================================
  // Renderizar itens da coleÃ§Ã£o atual (relaÃ§Ã£o N:N)
  // ===============================================
  // Tornada global para ser chamada por outros scripts
  window.renderItems = function renderItems() {
    const data = appData.loadData();
    const collection = getCurrentCollection(data);
    const ownsCollection = isCollectionOwnedByCurrentUser(collection, data);
    const items = appData.getItemsByCollection(collectionId, data);

    itemsContainer.innerHTML = "";

    if (!items || items.length === 0) {
      itemsContainer.innerHTML =
        `<p class="no-items-message">This collection has no items yet.</p>`;
      return;
    }

    // Mensagem de carregamento inicial
    itemsContainer.innerHTML =
      `<p class="notice-message">Loading items...</p>`;

    // FunÃ§Ã£o para renderizar itens em lotes (chunks)
    function renderChunk(index = 0) {
      const chunkSize = 50; // Renderiza 50 itens de cada vez
      const fragment = document.createDocumentFragment();
      const chunk = items.slice(index, index + chunkSize);

      for (const item of chunk) {
        const isItemOwner = ownsCollection;
        const card = document.createElement("div");
        card.className = "card item-card";

        const buttons = isItemOwner
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

        <div class="vote-section">
          <button class="vote-btn upvote" data-id="${item.id}">
            <i class="bi bi-arrow-up-circle"></i>
          </button>
          <button class="vote-btn downvote" data-id="${item.id}">
            <i class="bi bi-arrow-down-circle"></i>
          </button>
        </div>

        <div class="item-actions">
          <a href="item_page.html?id=${item.id}" class="explore-btn view-btn">
            <i class="bi bi-eye"></i> View Item
          </a>
        </div>
      </div>
    `;
        fragment.appendChild(card);
      }

      if (index === 0) itemsContainer.innerHTML = ""; // Limpa "Loading..."
      itemsContainer.appendChild(fragment);

      if (index + chunkSize < items.length) {
        // Agenda o prÃ³ximo lote sem bloquear o browser
        setTimeout(() => renderChunk(index + chunkSize), 0);
      }
    }

    // Inicia o processo de renderizaÃ§Ã£o
    renderChunk();
  };

  function renderCollectionEvents() {
    if (!eventsContainer) return;
    const data = appData.loadData();
    const collection = getCurrentCollection(data);

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
          <p class="event-meta">${formatEventDate(ev.date)} · ${ev.localization || "To be announced"}</p>
        </div>
        <button class="explore-btn ghost" onclick="window.location.href='event_page.html#${ev.id}'">
          <i class="bi bi-calendar-event"></i> View event
        </button>
      </article>
    `).join("");
  }

  // ===============================================
  // Preencher lista de coleÃ§Ãµes do utilizador atual
  // ===============================================
  function populateCollectionsSelect() {
    const select = document.getElementById("item-collections");
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
    title.textContent = edit ? "Edit Item" : "Add Item";
    modal.style.display = "flex";
  }

  function closeModal() {
    modal.style.display = "none";
    form.reset();
    idField.value = "";
  }

  // ===============================================
  // Criar / Editar / Apagar / Guardar
  // ===============================================
  window.editItem = (id) => {
    if (!isActiveUser) return alert("ðŸš« You must be logged in to edit items.");

    const data = appData.loadData();
    const item = data.items.find(i => i.id === id);
    if (!item) return alert("Item not found");
    const collection = getCurrentCollection(data);
    if (!isCollectionOwnedByCurrentUser(collection, data)) {
      return alert("ðŸš« You cannot edit this item.");
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
    if (!isActiveUser) return alert("ðŸš« You must be logged in to delete items.");

    const data = appData.loadData();
    const item = data.items.find(i => i.id === id);
    const collection = getCurrentCollection(data);

    if (!isCollectionOwnedByCurrentUser(collection, data)) {
      return alert("ðŸš« You can only delete your own items.");
    }

    if (confirm("Delete this item?\n\n(This is a demonstration. No data will be changed.)")) {
      alert("âœ… Simulation successful. No data was deleted.");
    }
  };

  // ===============================================
  // LÃ³gica para Editar a ColeÃ§Ã£o
  // ===============================================
  function openCollectionModal() {
    const data = appData.loadData();
    const collection = data.collections.find(c => c.id === collectionId);

    if (!collection) return alert("Collection not found!");
    if (!isCollectionOwnedByCurrentUser(collection, data)) {
      return alert("ðŸš« You can only edit your own collections.");
    }

    // Preenche o formulÃ¡rio do modal da coleÃ§Ã£o
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
        "âœ… Simulation successful. Collection would have been updated.\n\n(This is a demonstration. No data was saved.)"
      );

      closeCollectionModal();
      // NÃ£o renderiza novamente para nÃ£o dar a falsa impressÃ£o de que os dados mudaram.
      // renderCollectionDetails();
    });
  }

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    if (!isActiveUser) return alert("ðŸš« You must be logged in to add items.");

    const id = idField.value.trim();
    const selectedCollections = Array.from(
      form["item-collections"].selectedOptions
    ).map(opt => opt.value);

    const action = id ? "updated" : "created";

    alert(
      `âœ… Simulation successful. Item would have been ${action}.\n\n(This is a demonstration. No data was saved.)`
    );

    closeModal();
    // A renderizaÃ§Ã£o Ã© removida para nÃ£o mostrar alteraÃ§Ãµes que nÃ£o aconteceram
    // renderItems();
  });
// ===============================================
// Voting Logic (demo only)
// ===============================================
function setupVotingListeners() {
  document.querySelectorAll(".vote-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      alert("Demo only: voting is not saved.");
    });
  });
}

window.addEventListener("load", () => {
  setTimeout(setupVotingListeners, 500);
});



  if (addItemBtn) addItemBtn.addEventListener("click", () => openModal(false));
  if (closeBtn) closeBtn.addEventListener("click", closeModal);
  if (cancelBtn) cancelBtn.addEventListener("click", closeModal);

  window.addEventListener("click", (e) => {
    if (e.target === modal) closeModal();
    if (e.target === collectionModal) closeCollectionModal();
  });

  // Ouve o evento de login/logout e atualiza a pÃ¡gina
  window.addEventListener("userStateChange", (e) => {
    const newUserData = e.detail;
    const newIsActiveUser = newUserData && newUserData.active;

    // SÃ³ renderiza de novo se o estado de login MUDOU
    if (newIsActiveUser === isActiveUser) return;

    updateUserState();
    highlightOwnedSection();
    renderCollectionEvents();
    renderItems();
  });

  // InicializaÃ§Ã£o
  renderCollectionDetails();    // Preenche os detalhes da coleÃ§Ã£o
  populateCollectionsSelect();  // Preenche select de coleÃ§Ãµes
  renderItems();                // Renderiza itens da coleÃ§Ã£o
  renderCollectionEvents();     // Lista eventos associados
  highlightOwnedSection();      // Destaca se for dono
});


window.viewItem = function viewItem(itemId) {
  // Save the current item ID to localStorage so the detail page can load it
  localStorage.setItem("currentItemId", itemId);

  // Redirect to the item page
  window.location.href = `item_page.html?id=${encodeURIComponent(itemId)}`;
};

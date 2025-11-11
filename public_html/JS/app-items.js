// ===============================================
// app-items.js — Manage items within a collection
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

  function getCurrentCollection(data = appData.loadData()) {
    return data.collections.find(c => c.id === collectionId);
  }

  function getEffectiveOwnerId() {
    if (!isActiveUser) return null;
    return currentUserId || DEFAULT_OWNER_ID;
  }

  function isCollectionOwnedByCurrentUser(collection) {
    const ownerId = getEffectiveOwnerId();
    return Boolean(ownerId && collection && collection.ownerId === ownerId);
  }

  // ===============================================
  // Renderizar detalhes da coleção (título, dono, etc.)
  // ===============================================
  function renderCollectionDetails() {
    const data = appData.loadData();
    const collection = getCurrentCollection(data);

    if (collection) {
      document.getElementById("collection-title").textContent = collection.name;
      document.getElementById("owner-name").textContent = collection.ownerName || collection.ownerId;
      document.getElementById("creation-date").textContent = collection.createdAt;
      document.getElementById("type").textContent = collection.type || "N/A";
      document.getElementById("description").textContent =
        collection.description || "No description provided.";

      const ownerPhotoEl = document.getElementById("owner-photo");
      if (ownerPhotoEl) {
        const collectorDefault = "../images/rui.jpg";
        const guestDefault = "../images/user.jpg";
        const fallback =
          collection.ownerId === DEFAULT_OWNER_ID ? collectorDefault : guestDefault;
        ownerPhotoEl.src = collection.ownerPhoto || fallback;
        ownerPhotoEl.alt = `${collection.ownerName || "Collection"} owner`;
      }
    } else {
      // Se a coleção não for encontrada, mostra uma mensagem de erro
      document.getElementById("collection-title").textContent = "Collection Not Found";
      // Esconde os botões de ação se a coleção não existir
      if (addItemBtn) addItemBtn.style.display = "none";
      if (editCollectionBtn) editCollectionBtn.style.display = "none";
    }
  }

  // ===============================================
  // Destacar secção se for do utilizador
  // ===============================================
  function highlightOwnedSection() {
    const data = appData.loadData();
    const collection = getCurrentCollection(data);

    if (isCollectionOwnedByCurrentUser(collection)) {
      itemsContainer.classList.add("owned-section");
      // Mostra os botões se for o dono
      if (editCollectionBtn) editCollectionBtn.style.display = "inline-block";
      if (addItemBtn) addItemBtn.style.display = "inline-block";
    } else {
      itemsContainer.classList.remove("owned-section");
      // Esconde os botões se não for o dono
      if (editCollectionBtn) editCollectionBtn.style.display = "none";
      if (addItemBtn) addItemBtn.style.display = "none";
    }
  }

  // ===============================================
  // Renderizar itens da coleção atual (relação N:N)
  // ===============================================
  // Tornada global para ser chamada por outros scripts
  window.renderItems = function renderItems() {
    const data = appData.loadData();
    const collection = getCurrentCollection(data);
    const ownsCollection = isCollectionOwnedByCurrentUser(collection);
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

    // Função para renderizar itens em lotes (chunks)
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
        // Agenda o próximo lote sem bloquear o browser
        setTimeout(() => renderChunk(index + chunkSize), 0);
      }
    }

    // Inicia o processo de renderização
    renderChunk();
  };

  // ===============================================
  // Preencher lista de coleções do utilizador atual
  // ===============================================
  function populateCollectionsSelect() {
    const select = document.getElementById("item-collections");
    const data = appData.loadData();

    if (!data || !data.collections) return;

    select.innerHTML = "";
    const ownerId = getEffectiveOwnerId();

    const userCollections = data.collections.filter(c =>
      c.ownerId === DEFAULT_OWNER_ID || (ownerId && c.ownerId === ownerId)
    );

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
    if (!isActiveUser) return alert("🚫 You must be logged in to edit items.");

    const data = appData.loadData();
    const item = data.items.find(i => i.id === id);
    if (!item) return alert("Item not found");
    const collection = getCurrentCollection(data);
    if (!isCollectionOwnedByCurrentUser(collection)) {
      return alert("🚫 You cannot edit this item.");
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
    if (!isActiveUser) return alert("🚫 You must be logged in to delete items.");

    const data = appData.loadData();
    const item = data.items.find(i => i.id === id);
    const collection = getCurrentCollection(data);

    if (!isCollectionOwnedByCurrentUser(collection)) {
      return alert("🚫 You can only delete your own items.");
    }

    if (confirm("Delete this item?\n\n(This is a demonstration. No data will be changed.)")) {
      alert("✅ Simulation successful. No data was deleted.");
    }
  };

  // ===============================================
  // Lógica para Editar a Coleção
  // ===============================================
  function openCollectionModal() {
    const data = appData.loadData();
    const collection = data.collections.find(c => c.id === collectionId);

    if (!collection) return alert("Collection not found!");
    if (!isCollectionOwnedByCurrentUser(collection)) {
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

      alert(
        "✅ Simulation successful. Collection would have been updated.\n\n(This is a demonstration. No data was saved.)"
      );

      closeCollectionModal();
      // Não renderiza novamente para não dar a falsa impressão de que os dados mudaram.
      // renderCollectionDetails();
    });
  }

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    if (!isActiveUser) return alert("🚫 You must be logged in to add items.");

    const id = idField.value.trim();
    const selectedCollections = Array.from(
      form["item-collections"].selectedOptions
    ).map(opt => opt.value);

    const action = id ? "updated" : "created";

    alert(
      `✅ Simulation successful. Item would have been ${action}.\n\n(This is a demonstration. No data was saved.)`
    );

    closeModal();
    // A renderização é removida para não mostrar alterações que não aconteceram
    // renderItems();
  });

// ===============================================
// 🔹 Voting Logic (Upvote / Downvote)
// ===============================================
function loadVotes() {
  return JSON.parse(localStorage.getItem("itemVotes")) || {};
}

function saveVotes(votes) {
  localStorage.setItem("itemVotes", JSON.stringify(votes));
}

function setupVotingListeners() {
  const votes = loadVotes();

  document.querySelectorAll(".vote-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      const id = btn.dataset.id;
      const isUpvote = btn.classList.contains("upvote");

      // Initialize if missing
      if (!votes[id]) votes[id] = { count: 0, userVote: null };

      const currentVote = votes[id].userVote;

      // Logic:
      //  - If same vote clicked again → remove it
      //  - Else → apply new vote (+1 or -1) and adjust count
      if (currentVote === (isUpvote ? "up" : "down")) {
        // Undo the vote
        votes[id].count += isUpvote ? -1 : 1;
        votes[id].userVote = null;
      } else {
        // Remove opposite vote if exists
        if (currentVote === "up") votes[id].count -= 1;
        if (currentVote === "down") votes[id].count += 1;

        // Apply new vote
        votes[id].count += isUpvote ? 1 : -1;
        votes[id].userVote = isUpvote ? "up" : "down";
      }

      // Update UI
      const countEl = document.getElementById(`vote-count-${id}`);
      countEl.textContent = votes[id].count;

      // Add feedback animation
      countEl.classList.add("updated");
      setTimeout(() => countEl.classList.remove("updated"), 300);

      // Update button highlight
      const card = btn.closest(".item-info");
      card.querySelectorAll(".vote-btn").forEach(b => b.classList.remove("active"));
      if (votes[id].userVote) {
        const selector = votes[id].userVote === "up" ? ".upvote" : ".downvote";
        card.querySelector(selector).classList.add("active");
      }

      saveVotes(votes);
    });
  });

  // Restore active states on load
  for (const [id, voteData] of Object.entries(votes)) {
    if (voteData.userVote) {
      const btn = document.querySelector(`.vote-btn.${voteData.userVote === "up" ? "upvote" : "downvote"}[data-id="${id}"]`);
      if (btn) btn.classList.add("active");
      const countEl = document.getElementById(`vote-count-${id}`);
      if (countEl) countEl.textContent = voteData.count;
    }
  }
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

  // Ouve o evento de login/logout e atualiza a página
  window.addEventListener("userStateChange", (e) => {
    const newUserData = e.detail;
    const newIsActiveUser = newUserData && newUserData.active;

    // Só renderiza de novo se o estado de login MUDOU
    if (newIsActiveUser === isActiveUser) return;

    updateUserState();
    highlightOwnedSection();
    renderItems();
  });

  // Inicialização
  renderCollectionDetails();    // Preenche os detalhes da coleção
  populateCollectionsSelect();  // Preenche select de coleções
  renderItems();                // Renderiza itens da coleção
  highlightOwnedSection();      // Destaca se for dono
});


window.viewItem = function viewItem(itemId) {
  // Save the current item ID to localStorage so the detail page can load it
  localStorage.setItem("currentItemId", itemId);

  // Redirect to the item page
  window.location.href = `item_page.html?id=${encodeURIComponent(itemId)}`;
};
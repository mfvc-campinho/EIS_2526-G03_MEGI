// ===============================================
// File: public_html/JS/app-items.js
// Purpose: Manage items inside a collection page — render items, open item modal (add/edit), and provide item CRUD simulation.
// Major blocks: user state management, selectors, render functions, modal helpers, create/edit/delete handlers.
// Notes: Exposes window.renderItems and window.viewItem for cross-page interactions.
// ===============================================
document.addEventListener("DOMContentLoaded", () => {
  // ===============================================
  // User State Management
  // ===============================================
  const DEFAULT_OWNER_ID = "collector-main";
  let currentUserId;
  let isActiveUser;

  function updateUserState() {
    const userData = JSON.parse(localStorage.getItem("currentUser"));
    currentUserId = userData ? userData.id : null;
    isActiveUser = Boolean(userData && userData.active);

    // Hide/show buttons that require login
    document.querySelectorAll("[data-requires-login]").forEach(btn => {
      btn.style.display = isActiveUser ? "inline-block" : "none";
    });
  }

  updateUserState(); // Initialize the user state

  // Main selectors
  const itemsContainer = document.getElementById("collection-items");
  const eventsContainer = document.getElementById("collection-events");
  const modal = document.getElementById("item-modal");
  const form = document.getElementById("item-form");
  const closeBtn = document.getElementById("close-modal");
  const cancelBtn = document.getElementById("cancel-modal");
  const addItemBtn = document.getElementById("add-item");
  const title = document.getElementById("modal-title");
  const idField = document.getElementById("item-id");

  // Selectors for the collection modal
  const collectionModal = document.getElementById("collection-modal");
  const collectionForm = document.getElementById("form-collection");
  const editCollectionBtn = document.getElementById("edit-collection");
  const closeCollectionModalBtn = document.getElementById("close-collection-modal");
  const cancelCollectionModalBtn = document.getElementById("cancel-collection-modal");
  const hasCollectionPage = Boolean(itemsContainer);
  // Get collection ID from URL
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
  // Render collection details (title, owner, metadata)
  // ===============================================
  function renderCollectionDetails() {
    if (!hasCollectionPage) return;
    const data = appData.loadData();
    const collection = getCurrentCollection(data);

    if (collection) {
      document.getElementById("collection-title").textContent = collection.name;
      // Also update the breadcrumb to correctly reference the collection
      const bc = document.getElementById("collection-breadcrumb-name");
      if (bc) bc.textContent = collection.name;
      const ownerProfile = getOwnerProfileForCollection(collection, data) || {};
      const collectionOwnerId = getOwnerIdForCollection(collection, data);
      const ownerDisplayName =
        ownerProfile["owner-name"] ||
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
      const itemsCountEl = document.getElementById("items-count");
      if (itemsCountEl) {
        const collectionItems = collection ? appData.getItemsByCollection(collection.id, data) : [];
        itemsCountEl.textContent = collectionItems.length;
      }

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
      // If the collection is not found, show an error state
      document.getElementById("collection-title").textContent = "Collection Not Found";
      // Update breadcrumb to reflect the error/state
      const bc = document.getElementById("collection-breadcrumb-name");
      if (bc) bc.textContent = "Collection Not Found";
      // Hide action buttons if the collection does not exist
      if (addItemBtn) addItemBtn.style.display = "none";
      if (editCollectionBtn) editCollectionBtn.style.display = "none";
    }
  }

  // ===============================================
  // Highlight section when owned by current user
  // ===============================================
  function highlightOwnedSection() {
    if (!hasCollectionPage) return;
    const data = appData.loadData();
    const collection = getCurrentCollection(data);

    if (isCollectionOwnedByCurrentUser(collection, data)) {
      itemsContainer.classList.add("owned-section");
      // Show action buttons when the current user is the owner
      if (editCollectionBtn) editCollectionBtn.style.display = "inline-block";
      if (addItemBtn) addItemBtn.style.display = "inline-block";
    } else {
      itemsContainer.classList.remove("owned-section");
      // Hide action buttons when not the owner
      if (editCollectionBtn) editCollectionBtn.style.display = "none";
      if (addItemBtn) addItemBtn.style.display = "none";
    }
  }

  // ===============================================
  // Render items for the current collection (many-to-many relation)
  // Exposed globally so other scripts can call it
  // ===============================================
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

    // Function to render items in chunks to avoid blocking the UI
    function renderChunk(index = 0) {
      const chunkSize = 50; // Renders 50 items at a time
      const fragment = document.createDocumentFragment();
      const chunk = items.slice(index, index + chunkSize);

      for (const item of chunk) {
        const isItemOwner = ownsCollection;
        const card = document.createElement("div");
        card.className = "card item-card";

        const ownerButtons = isItemOwner
          ? `
            <div class="item-buttons">
              <button class="explore-btn warning" onclick="editItem('${item.id}')"><i class="bi bi-pencil"></i> Edit</button>
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
        // Schedule next chunk without blocking the main thread
        setTimeout(() => renderChunk(index + chunkSize), 0);
      }
    }

    // Start the rendering process
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
        <a class="explore-btn ghost" href="event_page.html?id=${ev.id}">
          <i class="bi bi-calendar-event"></i> View event
        </a>
      </article>
    `).join("");
  }

  // ===============================================
  // Populate select with the current user's collections
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
  // Create / Edit / Delete / Save (simulated)
  // ===============================================
  window.editItem = (id) => {
    if (!isActiveUser) return alert("You must be logged in to edit items.");

    const data = appData.loadData();
    const item = data.items.find(i => i.id === id);
    if (!item) return alert("Item not found");
    const collection = getCurrentCollection(data);
    if (!isCollectionOwnedByCurrentUser(collection, data)) {
      return alert("You cannot edit this item.");
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
    if (!isActiveUser) return alert("You must be logged in to delete items.");

    const data = appData.loadData();
    const item = data.items.find(i => i.id === id);
    const collection = getCurrentCollection(data);

    if (!isCollectionOwnedByCurrentUser(collection, data)) {
      return alert("You can only delete your own items.");
    }

    if (confirm("Delete this item?\n\n(This is a demonstration. No data will be changed.)")) {
      alert("Simulation successful. No data was deleted.");
    }
  };

  // ===============================================
  // Logic for editing a collection (modal)
  // ===============================================
  function openCollectionModal() {
    const data = appData.loadData();
    const collection = data.collections.find(c => c.id === collectionId);

    if (!collection) return alert("Collection not found!");
    if (!isCollectionOwnedByCurrentUser(collection, data)) {
      return alert("You can only edit your own collections.");
    }

    // Fill the collection modal form with existing values
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
        "Simulation successful. Collection would have been updated.\n\n(This is a demonstration. No data was saved.)"
      );

      closeCollectionModal();
      // Not re-rendering to avoid giving the false impression that data changed.
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
      // Rendering is omitted to avoid showing changes that did not actually occur
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

  // Listen for login/logout events and update the page
  window.addEventListener("userStateChange", (e) => {
    const newUserData = e.detail;
    const newIsActiveUser = newUserData && newUserData.active;

    // Only re-render if the login state actually changed
    if (newIsActiveUser === isActiveUser) return;

    updateUserState();
    highlightOwnedSection();
    renderCollectionEvents();
    renderItems();
  });

  // Initialization
  populateCollectionsSelect();  // Populate collections select (if present)
  if (hasCollectionPage) {
    renderCollectionDetails();   // Fill the collection details
    renderItems();               // Render items for the collection
    highlightOwnedSection();     // Highlight if owned by current user
  }
  renderCollectionEvents();      // List associated events (if container exists)
  handleItemActionParam();      // Execute actions coming from item_page
});


window.viewItem = function viewItem(itemId) {
  // Save the current item ID to localStorage so the detail page can load it
  localStorage.setItem("currentItemId", itemId);

  // Redirect to the item page
  window.location.href = `item_page.html?id=${encodeURIComponent(itemId)}`;
};
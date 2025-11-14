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
  const itemsSessionState = window.demoItemsState || (window.demoItemsState = {});
  const itemVoteState = itemsSessionState.voteState || (itemsSessionState.voteState = {});
  let itemLikesById = {};
  let ownerItemLikesMap = {};

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
  const itemsContainer = document.getElementById("collection-items") || document.getElementById("user-liked-items");


  const eventsContainer = document.getElementById("collection-events");
  const modal = document.getElementById("item-modal");
  const form = document.getElementById("item-form");
  const closeBtn = document.getElementById("close-modal");
  const cancelBtn = document.getElementById("cancel-modal");
  const addItemBtn = document.getElementById("add-item");
  const title = document.getElementById("modal-title");
  const idField = document.getElementById("item-id");
  const itemsFilterSelect = document.getElementById("itemsFilter");
  const resetItemsFilterBtn = document.getElementById("resetItemsFilter");
  const itemsFilterNote = document.getElementById("items-filter-note");

  // Selectors for the collection modal
  const collectionModal = document.getElementById("collection-modal");
  const collectionForm = document.getElementById("form-collection");
  const editCollectionBtn = document.getElementById("edit-collection");
  const closeCollectionModalBtn = document.getElementById("close-collection-modal");
  const cancelCollectionModalBtn = document.getElementById("cancel-collection-modal");
  const hasCollectionPage = Boolean(itemsContainer);
  const itemsPaginationControls = Array.from(document.querySelectorAll('[data-pagination-for="collection-items"]'));
  const hasItemsPagination = itemsPaginationControls.length > 0;
  const defaultItemsPageSize = hasItemsPagination ? getInitialPageSizeFromControls(itemsPaginationControls) : null;
  const itemsPaginationState = hasItemsPagination
    ? { pageSize: defaultItemsPageSize, pageIndex: 0 }
    : null;
  // Get collection ID from URL
  const params = new URLSearchParams(window.location.search);
  let collectionId = hasCollectionPage ? params.get("id") : null;

  function getInitialPageSizeFromControls(controls) {
    for (const ctrl of controls) {
      const select = ctrl.querySelector("[data-page-size]");
      if (!select) continue;
      const parsed = parseInt(select.value, 10);
      if (!Number.isNaN(parsed) && parsed > 0) {
        return parsed;
      }
    }
    return 10;
  }

  function syncItemsPageSizeSelects(value) {
    if (!hasItemsPagination) return;
    itemsPaginationControls.forEach(ctrl => {
      const select = ctrl.querySelector("[data-page-size]");
      if (select) {
        select.value = String(value);
      }
    });
  }

  function updateItemsPaginationSummary(total, start = 0, shown = 0) {
    if (!hasItemsPagination) return;
    const totalSafe = Math.max(total || 0, 0);
    const shownSafe = Math.max(Math.min(shown || 0, totalSafe), 0);
    const startSafe = totalSafe === 0 ? 0 : Math.min(Math.max(start || 0, 0), Math.max(totalSafe - 1, 0));
    const rangeStart = totalSafe === 0 || shownSafe === 0 ? 0 : startSafe + 1;
    const rangeEnd = totalSafe === 0 || shownSafe === 0 ? 0 : startSafe + shownSafe;
    const effectiveSize = itemsPaginationState ? Math.max(itemsPaginationState.pageSize || defaultItemsPageSize || 1, 1) : 1;
    const totalPages = totalSafe === 0 ? 0 : Math.ceil(totalSafe / effectiveSize);
    const currentPage = itemsPaginationState ? itemsPaginationState.pageIndex : 0;
    const atStart = !totalSafe || currentPage <= 0;
    const atEnd = !totalSafe || currentPage >= Math.max(totalPages - 1, 0);
    itemsPaginationControls.forEach(ctrl => {
      const status = ctrl.querySelector("[data-pagination-status]");
      if (status) {
        status.textContent = `Mostrando ${rangeStart}-${rangeEnd} de ${totalSafe}`;
      }
      const prevBtn = ctrl.querySelector("[data-page-prev]");
      if (prevBtn) {
        prevBtn.disabled = atStart;
        prevBtn.setAttribute("aria-disabled", atStart ? "true" : "false");
        prevBtn.classList.toggle("disabled", atStart);
      }
      const nextBtn = ctrl.querySelector("[data-page-next]");
      if (nextBtn) {
        nextBtn.disabled = atEnd;
        nextBtn.setAttribute("aria-disabled", atEnd ? "true" : "false");
        nextBtn.classList.toggle("disabled", atEnd);
      }
    });
  }

  function initItemsPaginationControls() {
    if (!hasItemsPagination || !itemsPaginationState) return;
    syncItemsPageSizeSelects(itemsPaginationState.pageSize);
    itemsPaginationControls.forEach(ctrl => {
      const select = ctrl.querySelector("[data-page-size]");
      if (select) {
        select.addEventListener("change", event => {
          const next = parseInt(event.target.value, 10);
          if (Number.isNaN(next) || next <= 0) return;
          itemsPaginationState.pageSize = next;
          itemsPaginationState.pageIndex = 0;
          syncItemsPageSizeSelects(next);
          renderItems();
        });
      }
      const prevBtn = ctrl.querySelector("[data-page-prev]");
      if (prevBtn) {
        prevBtn.addEventListener("click", () => {
          if (itemsPaginationState.pageIndex > 0) {
            itemsPaginationState.pageIndex -= 1;
            renderItems();
          }
        });
      }
      const nextBtn = ctrl.querySelector("[data-page-next]");
      if (nextBtn) {
        nextBtn.addEventListener("click", () => {
          itemsPaginationState.pageIndex += 1;
          renderItems();
        });
      }
    });
  }

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
  // Item likes helpers
  // ===============================================
  function notifyItemLikesChange(ownerId) {
    if (!ownerId) return;
    window.dispatchEvent(new CustomEvent("userItemLikesChange", { detail: { ownerId } }));
  }

  function buildItemLikesMaps(data) {
    itemLikesById = {};
    ownerItemLikesMap = {};
    (data?.userShowcases || []).forEach(entry => {
      const owner = entry.ownerId;
      const likes = entry.likedItems || entry.itemLikes || [];
      if (!likes.length) return;
      ownerItemLikesMap[owner] = new Set(likes);
      likes.forEach(itemId => {
        if (!itemLikesById[itemId]) itemLikesById[itemId] = new Set();
        itemLikesById[itemId].add(owner);
      });
    });
  }

  function getItemLikedBy(itemId) {
    const set = itemLikesById[itemId];
    return set ? new Set(set) : new Set();
  }

  function getItemVoteOverride(itemId) {
    return Object.prototype.hasOwnProperty.call(itemVoteState, itemId)
      ? itemVoteState[itemId]
      : undefined;
  }

  function getUserBaseItemLike(itemId, ownerId) {
    if (!ownerId) return false;
    const likedSet = ownerItemLikesMap[ownerId];
    return likedSet ? likedSet.has(itemId) : false;
  }

  function getEffectiveItemLike(itemId, ownerId) {
    if (!ownerId) return false;
    const override = getItemVoteOverride(itemId);
    if (override === undefined) return getUserBaseItemLike(itemId, ownerId);
    return override;
  }

  function getItemDisplayLikes(itemId, ownerId) {
    const likedSet = getItemLikedBy(itemId);
    if (ownerId) {
      const override = getItemVoteOverride(itemId);
      if (override === true) {
        likedSet.add(ownerId);
      } else if (override === false) {
        likedSet.delete(ownerId);
      } else if (getUserBaseItemLike(itemId, ownerId)) {
        likedSet.add(ownerId);
      }
    }
    return likedSet.size;
  }

  function refreshItemLikesState() {
    const data = appData.loadData();
    buildItemLikesMaps(data);
    return data;
  }

  function getItemLikeSnapshot(itemId, options = {}) {
    const { reload = true } = options || {};
    if (reload) {
      refreshItemLikesState();
    }
    const ownerId = getEffectiveOwnerId();
    if (!itemId) {
      return {
        likeCount: 0,
        liked: false,
        ownerId,
        isActiveUser: Boolean(isActiveUser && ownerId)
      };
    }
    return {
      likeCount: getItemDisplayLikes(itemId, ownerId),
      liked: ownerId ? getEffectiveItemLike(itemId, ownerId) : false,
      ownerId,
      isActiveUser: Boolean(isActiveUser && ownerId)
    };
  }

  function toggleItemLike(itemId, options = {}) {
    const { skipRender = false, suppressAlert = false, onUpdate } = options || {};
    if (!isActiveUser) {
      alert("Sign in to like items.");
      return;
    }
    const ownerId = getEffectiveOwnerId();
    if (!ownerId) return;
    refreshItemLikesState();
    const currentState = getEffectiveItemLike(itemId, ownerId);
    itemVoteState[itemId] = !currentState;
    if (!skipRender) {
      renderItems();
    }
    notifyItemLikesChange(ownerId);
    if (typeof onUpdate === "function") {
      const snapshot = getItemLikeSnapshot(itemId, { reload: false });
      onUpdate(snapshot);
    }
    if (!suppressAlert) {
      alert("Simulation only: liking an item here is not saved to local storage.");
    }
  }

  function attachItemLikeHandlers() {
    if (!itemsContainer) return;
    const buttons = itemsContainer.querySelectorAll(".item-like-btn");
    buttons.forEach(btn => {
      const id = btn.dataset.itemId;
      if (!id) return;
      btn.addEventListener("click", () => toggleItemLike(id));
    });
  }

  function updateItemsFilterNote(message) {
    if (!itemsFilterNote) return;
    itemsFilterNote.textContent = message || "";
  }

  function resolveItemsFilterMode() {
    // If on user page, force "liked" mode. Otherwise, use the dropdown.
    if (itemsContainer?.id === "user-liked-items") {
      return "liked";
    }
    return itemsFilterSelect ? itemsFilterSelect.value : "all";
  }

  function parseItemTimestamp(item) {
    if (!item) return 0;
    const dateStr = item.acquisitionDate || item.updatedAt || item.createdAt;
    const ts = dateStr ? new Date(dateStr).getTime() : NaN;
    return Number.isNaN(ts) ? 0 : ts;
  }

  function applyItemsFilter(allItems) {
    const mode = resolveItemsFilterMode();
    const ownerId = getEffectiveOwnerId();
    const baseItems = Array.isArray(allItems) ? allItems.slice() : [];

    if (!mode || mode === "all") {
      return {
        items: baseItems,
        note: baseItems.length ? "Showing all items." : "",
        requiresLogin: false
      };
    }

    if (mode === "liked") {
      if (!isActiveUser || !ownerId) {
        return {
          items: [],
          note: "Sign in to view items you have liked.",
          requiresLogin: true
        };
      }
      const likedItems = baseItems.filter(item => getEffectiveItemLike(item.id, ownerId));
      const note = likedItems.length
        ? `Showing ${likedItems.length} liked item${likedItems.length === 1 ? "" : "s"}.`
        : "You haven't liked any items in this collection yet.";
      return { items: likedItems, note, requiresLogin: false };
    }

    if (mode === "mostLiked") {
      baseItems.sort((a, b) => getItemLikedBy(b.id).size - getItemLikedBy(a.id).size);
      return {
        items: baseItems,
        note: baseItems.length ? "Sorted by community likes." : "No items available to rank.",
        requiresLogin: false
      };
    }

    if (mode === "recent") {
      baseItems.sort((a, b) => parseItemTimestamp(b) - parseItemTimestamp(a));
      return {
        items: baseItems,
        note: baseItems.length ? "Newest acquisitions first." : "",
        requiresLogin: false
      };
    }

    return { items: baseItems, note: "", requiresLogin: false };
  }

  // ===============================================
  // Render items for the current collection (many-to-many relation)
  // Exposed globally so other scripts can call it
  // ===============================================
  window.renderItems = function renderItems() {
    if (!itemsContainer) return;
    const data = refreshItemLikesState();
    const collection = getCurrentCollection(data);

    itemsContainer.innerHTML = "";

    if (!collection) {
      const missingMessage = document.createElement("p");
      missingMessage.className = "notice-message";
      missingMessage.textContent = itemsContainer.id === "user-liked-items" ? "" : "Collection not found.";
      itemsContainer.appendChild(missingMessage);
      updateItemsPaginationSummary(0, 0, 0);
      return;
    }

    const ownsCollection = isCollectionOwnedByCurrentUser(collection, data);
    const allItems = collectionId
      ? (appData.getItemsByCollection(collectionId, data) || [])
      : (data.items || []);

    const filterResult = applyItemsFilter(allItems);
    updateItemsFilterNote(filterResult.note);

    if (filterResult.requiresLogin) {
      updateItemsPaginationSummary(0, 0, 0);
      const loginMessage = document.createElement("p");
      loginMessage.className = "notice-message";
      loginMessage.textContent = filterResult.note || "Sign in to use this filter.";
      itemsContainer.appendChild(loginMessage);
      return;
    }

    let items = filterResult.items || [];
    const totalItems = items.length;
    let startIndexForPage = 0;

    if (hasItemsPagination && itemsPaginationState && itemsPaginationState.pageSize > 0) {
      const effectiveSize = Math.max(itemsPaginationState.pageSize || defaultItemsPageSize || 1, 1);
      itemsPaginationState.pageSize = effectiveSize;
      const totalPages = effectiveSize > 0 ? Math.ceil((totalItems || 0) / effectiveSize) : 0;
      if (totalPages === 0) {
        itemsPaginationState.pageIndex = 0;
      } else if (itemsPaginationState.pageIndex >= totalPages) {
        itemsPaginationState.pageIndex = totalPages - 1;
      } else if (itemsPaginationState.pageIndex < 0) {
        itemsPaginationState.pageIndex = 0;
      }
      startIndexForPage = itemsPaginationState.pageIndex * effectiveSize;
      const endIndex = startIndexForPage + effectiveSize;
      items = items.slice(startIndexForPage, endIndex);
    }

    updateItemsPaginationSummary(totalItems, startIndexForPage, items.length);

    if (!items.length) {
      const emptyMessage = document.createElement("p");
      emptyMessage.className = "no-items-message";
      emptyMessage.textContent = filterResult.note || "This collection has no items yet.";
      itemsContainer.appendChild(emptyMessage);
      return;
    }

    const ownerIdForDisplay = getEffectiveOwnerId();
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
        const isLiked = ownerIdForDisplay ? getEffectiveItemLike(item.id, ownerIdForDisplay) : false;
        const likeCount = getItemDisplayLikes(item.id, ownerIdForDisplay);
        const likeButton = `
          <button class="item-like-btn ${isLiked ? "active" : ""}" data-item-id="${item.id}" aria-pressed="${isLiked}">
            <i class="bi ${isLiked ? "bi-star-fill" : "bi-star"}"></i>
            <span class="like-count">${likeCount}</span>
          </button>
        `;

        const ownerButtons = isItemOwner
          ? `
            <div class="item-buttons">
              <button class="explore-btn warning" onclick="editItem('${item.id}')"><i class="bi bi-pencil-square"></i> Edit</button>
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
          ${likeButton}
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
      } else {
        attachItemLikeHandlers();
      }
    }

    // Start the rendering process
    renderChunk();
  };

  window.itemLikeHelpers = {
    snapshot: (itemId, options) => getItemLikeSnapshot(itemId, options),
    toggle: (itemId, options) => toggleItemLike(itemId, options),
    refresh: () => refreshItemLikesState(),
    getOwnerId: () => getEffectiveOwnerId(),
    isUserActive: () => Boolean(isActiveUser)
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

    let encodedReturnUrl = "";
    try {
      const returnTarget = new URL(window.location.href);
      returnTarget.hash = "collection-events";
      encodedReturnUrl = encodeURIComponent(returnTarget.toString());
    } catch (err) {
      const fallback = `${window.location.pathname || ""}#collection-events`;
      encodedReturnUrl = encodeURIComponent(fallback);
    }

    eventsContainer.innerHTML = events.map(ev => `
      <article class="collection-event-card">
        <div>
          <h3>${ev.name}</h3>
          <p class="event-meta">${formatEventDate(ev.date)} · ${ev.localization || "To be announced"}</p>
        </div>
        <a class="explore-btn ghost" href="event_page.html?id=${ev.id}&returnUrl=${encodedReturnUrl}">
          <i class="bi bi-calendar-event"></i> View event
        </a>
      </article>
    `).join("");
  }

  // ===============================================
  // Collection statistics: compute & render
  // ===============================================
  function computeCollectionStatistics(targetCollectionId) {
    const cid = targetCollectionId || collectionId;
    const data = appData.loadData();
    if (!cid || !data) return {
      totalItems: 0,
      totalValue: 0,
      avgWeight: null,
      linkedEvents: 0,
      oldestItem: null,
      newestItem: null
    };

    const items = appData.getItemsByCollection(cid, data) || [];

    const totalItems = items.length;

    const totalValue = items.reduce((sum, it) => {
      const v = Number(it.price);
      return sum + (Number.isFinite(v) ? v : 0);
    }, 0);

    const weightValues = items
      .map(it => {
        const w = it && it.weight !== undefined && it.weight !== null ? Number(it.weight) : NaN;
        return Number.isFinite(w) ? w : NaN;
      })
      .filter(w => !Number.isNaN(w));

    const avgWeight = weightValues.length ? (weightValues.reduce((a, b) => a + b, 0) / weightValues.length) : null;

    const events = appData.getEventsByCollection(cid, data) || [];
    const linkedEvents = events.length;

    // Oldest / Newest by acquisitionDate
    const itemsWithDates = items
      .map(it => ({
        item: it,
        ts: it && it.acquisitionDate ? new Date(it.acquisitionDate).getTime() : NaN
      }))
      .filter(x => Number.isFinite(x.ts));

    let oldestItem = null;
    let newestItem = null;
    if (itemsWithDates.length) {
      itemsWithDates.sort((a, b) => a.ts - b.ts);
      const o = itemsWithDates[0].item;
      const n = itemsWithDates[itemsWithDates.length - 1].item;
      oldestItem = o ? { name: o.name || "—", date: o.acquisitionDate } : null;
      newestItem = n ? { name: n.name || "—", date: n.acquisitionDate } : null;
    }

    return {
      totalItems,
      totalValue,
      avgWeight,
      linkedEvents,
      oldestItem,
      newestItem
    };
  }

  function renderCollectionStats(statObj) {
    const panel = document.getElementById("collection-stats");
    if (!panel || !statObj) return;

    // Helper to toggle hidden when null/undefined
    function setCardValue(key, value, formatter) {
      const card = panel.querySelector(`.stat-card[data-key="${key}"]`);
      if (!card) return;
      const valueEl = card.querySelector('.stat-value');
      if (value === null || value === undefined || (typeof value === 'number' && Number.isNaN(value))) {
        card.classList.add('hidden');
        return;
      }
      card.classList.remove('hidden');
      valueEl.textContent = formatter ? formatter(value) : String(value);
    }

    // Total items — show zero when empty
    setCardValue('totalItems', statObj.totalItems, v => String(v));

    // Total value — format as EUR with two decimals
    setCardValue('totalValue', statObj.totalValue, v => {
      try {
        return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'EUR', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(v);
      } catch (e) {
        return `€ ${Number(v).toFixed(2)}`;
      }
    });

    // Average weight — hide if null, otherwise format to 2 decimals
    setCardValue('avgWeight', statObj.avgWeight, v => `${Number(v).toFixed(2)} g`);

    // Linked events — show zero when empty
    setCardValue('linkedEvents', statObj.linkedEvents, v => String(v));

    // Oldest / Newest
    setCardValue('oldestItem', statObj.oldestItem, v => {
      if (!v || !v.date) return '';
      const d = new Date(v.date);
      const dateStr = Number.isNaN(d.getTime()) ? v.date : d.toLocaleDateString();
      return `${v.name} · ${dateStr}`;
    });

    setCardValue('newestItem', statObj.newestItem, v => {
      if (!v || !v.date) return '';
      const d = new Date(v.date);
      const dateStr = Number.isNaN(d.getTime()) ? v.date : d.toLocaleDateString();
      return `${v.name} · ${dateStr}`;
    });
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



  if (itemsFilterSelect) {
    itemsFilterSelect.addEventListener("change", () => renderItems());
  }

  if (resetItemsFilterBtn) {
    resetItemsFilterBtn.addEventListener("click", () => {
      if (itemsFilterSelect) itemsFilterSelect.value = "all";
      renderItems();
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
    try {
      const stats = computeCollectionStatistics();
      renderCollectionStats(stats);
    } catch (err) {
      console.error('Error updating collection stats', err);
    }
  });

  // Initialization
  populateCollectionsSelect();  // Populate collections select (if present)
  if (hasCollectionPage) {
    if (collectionId) renderCollectionDetails();   // Fill the collection details
    // Compute and render collection statistics (keeps in sync with collection data)
    try {
      const stats = computeCollectionStatistics();
      renderCollectionStats(stats);
    } catch (e) {
      console.error('Error computing collection stats', e);
    }
    initItemsPaginationControls();
    renderItems();               // Render items for the collection
    highlightOwnedSection();     // Highlight if owned by current user
  } else if (itemsContainer) { renderItems(); }
  renderCollectionEvents();      // List associated events (if container exists)
  handleItemActionParam();      // Execute actions coming from item_page
  // Expose compute/render helpers for debugging and external triggers
  try {
    window.computeCollectionStatistics = computeCollectionStatistics;
    window.renderCollectionStats = renderCollectionStats;
  } catch (e) {
    // ignore in constrained environments
  }
});


window.viewItem = function viewItem(itemId) {
  // Save the current item ID to localStorage so the detail page can load it
  localStorage.setItem("currentItemId", itemId);

  // Redirect to the item page
  window.location.href = `item_page.html?id=${encodeURIComponent(itemId)}`;
};



window.viewItem = function viewItem(itemId) {
  // Save the current item ID to localStorage so the detail page can load it
  localStorage.setItem("currentItemId", itemId);

  // Redirect to the item page
  window.location.href = `item_page.html?id=${encodeURIComponent(itemId)}`;
};

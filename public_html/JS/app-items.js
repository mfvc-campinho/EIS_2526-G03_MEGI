// ===============================================
// File: public_html/JS/app-items.js (CLEAN VERSION)
// Purpose: Manage items inside a collection page — render items,
// handle likes, pagination, and modal interactions.
// ===============================================

document.addEventListener("DOMContentLoaded", () => {

  // ===============================================
  // USER STATE MANAGEMENT
  // ===============================================
  const DEFAULT_OWNER_ID = "collector-main";
  let currentUserId = null;
  let isActiveUser = false;

  const itemsSessionState = window.demoItemsState || (window.demoItemsState = {});
  const itemVoteState = itemsSessionState.voteState || (itemsSessionState.voteState = {});
  const itemRatingState = itemsSessionState.ratingState || (itemsSessionState.ratingState = {});
  const sessionItemRatings = itemRatingState.itemRatings || (itemRatingState.itemRatings = {});

  let itemLikesById = {};
  let ownerItemLikesMap = {};

  function updateUserState() {
    const userData = JSON.parse(localStorage.getItem("currentUser"));
    currentUserId = userData ? userData.id : null;
    isActiveUser = Boolean(userData && userData.active);

    // Show/hide elements requiring login
    document.querySelectorAll("[data-requires-login]").forEach(btn => {
      btn.style.display = isActiveUser ? "inline-block" : "none";
    });
  }

  updateUserState();


  // ===============================================
  // MAIN SELECTORS
  // ===============================================
  const itemsContainer =
    document.getElementById("collection-items") ||
    document.getElementById("user-liked-items");

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
  const importanceFilterSelect = document.getElementById("importanceFilter");
  const priceFilterSelect = document.getElementById("priceFilter");

  const collectionModal = document.getElementById("collection-modal");
  const collectionForm = document.getElementById("form-collection");
  const editCollectionBtn = document.getElementById("edit-collection");
  const closeCollectionModalBtn = document.getElementById("close-collection-modal");
  const cancelCollectionModalBtn = document.getElementById("cancel-collection-modal");


  // ===============================================
  // PAGE CONTEXT
  // ===============================================
  const isCollectionPage = Boolean(document.getElementById("collection-items"));

  const paginationControls = Array.from(
    document.querySelectorAll('[data-pagination-for="collection-items"]')
  );

  const hasPagination = paginationControls.length > 0;

  const defaultPageSize = hasPagination
    ? getInitialPageSizeFromControls(paginationControls)
    : null;

  const paginationState = hasPagination
    ? { pageSize: defaultPageSize, pageIndex: 0 }
    : null;

  // URL params
  const params = new URLSearchParams(window.location.search);
  let collectionId = isCollectionPage ? params.get("id") : null;


  // ===============================================
  // HELPERS — PAGE SIZE
  // ===============================================
  function getInitialPageSizeFromControls(ctrls) {
    for (const ctrl of ctrls) {
      const select = ctrl.querySelector("[data-page-size]");
      if (!select) continue;
      const parsed = parseInt(select.value, 10);
      if (!Number.isNaN(parsed) && parsed > 0) return parsed;
    }
    return 10;
  }

  function syncPageSizeSelects(value) {
    if (!hasPagination) return;
    paginationControls.forEach(ctrl => {
      const s = ctrl.querySelector("[data-page-size]");
      if (s) s.value = String(value);
    });
  }


  // ===============================================
  // PAGINATION STATUS
  // ===============================================
  function updatePaginationSummary(total, start = 0, shown = 0) {
    if (!hasPagination) return;

    const totalSafe = Math.max(total || 0, 0);
    const shownSafe = Math.max(Math.min(shown || 0, totalSafe), 0);
    const startSafe =
      totalSafe === 0 ? 0 : Math.min(Math.max(start || 0, 0), Math.max(totalSafe - 1, 0));
    const hasResults = totalSafe > 0;

    const rangeStart = totalSafe === 0 || shownSafe === 0 ? 0 : startSafe + 1;
    const rangeEnd = totalSafe === 0 || shownSafe === 0 ? 0 : startSafe + shownSafe;

    const size = paginationState
      ? Math.max(paginationState.pageSize || defaultPageSize || 1, 1)
      : 1;

    const totalPages = totalSafe === 0 ? 0 : Math.ceil(totalSafe / size);
    const currentPage = paginationState ? paginationState.pageIndex : 0;

    const atStart = !totalSafe || currentPage <= 0;
    const atEnd = !totalSafe || currentPage >= Math.max(totalPages - 1, 0);

    paginationControls.forEach(ctrl => {
      const status = ctrl.querySelector("[data-pagination-status]");
      if (status) {
        status.textContent = `Showing ${rangeStart}-${rangeEnd} of ${totalSafe}`;
      }
      const prev = ctrl.querySelector("[data-page-prev]");
      if (prev) {
        prev.disabled = atStart;
        prev.setAttribute("aria-disabled", atStart ? "true" : "false");
        prev.classList.toggle("disabled", atStart);
      }
      const next = ctrl.querySelector("[data-page-next]");
      if (next) {
        next.disabled = atEnd;
        next.setAttribute("aria-disabled", atEnd ? "true" : "false");
        next.classList.toggle("disabled", atEnd);
      }
      const actions = ctrl.querySelector(".pagination-actions");
      if (actions) {
        actions.hidden = !hasResults;
      }
    });
  }


  // ===============================================
  // OWNER HELPERS
  // ===============================================
  function getEffectiveOwnerId() {
    if (!isActiveUser) return null;
    return currentUserId || DEFAULT_OWNER_ID;
  }

  function getOwnerIdForCollection(target, data = appData.loadData()) {
    const id = typeof target === "string" ? target : target?.id;
    if (!id) return null;

    const linkOwner = appData.getCollectionOwnerId(id, data);
    if (linkOwner) return linkOwner;

    const direct = data.collections.find(c => c.id === id);
    return direct?.ownerId || null;
  }

  function getOwnerProfileForCollection(target, data = appData.loadData()) {
    const id = typeof target === "string" ? target : target?.id;
    if (!id) return null;

    const profile = appData.getCollectionOwner(id, data);
    if (profile) return profile;

    const fallback = data.collections.find(c => c.id === id);
    if (!fallback) return null;

    return {
      ["owner-name"]: fallback.ownerName || fallback["owner-name"],
      ["owner-photo"]: fallback.ownerPhoto || fallback["owner-photo"]
    };
  }


  // ===============================================
  // COLLECTION DETAILS RENDERING
  // ===============================================
  function getCurrentCollection(data = appData.loadData()) {
    return data.collections.find(c => c.id === collectionId);
  }

  function renderCollectionDetails() {
    if (!isCollectionPage) return;

    const data = appData.loadData();
    const collection = getCurrentCollection(data);

    if (!collection) {
      document.getElementById("collection-title").textContent = "Collection Not Found";
      const bc = document.getElementById("collection-breadcrumb-name");
      if (bc) bc.textContent = "Collection Not Found";
      if (addItemBtn) addItemBtn.style.display = "none";
      if (editCollectionBtn) editCollectionBtn.style.display = "none";
      return;
    }

    document.getElementById("collection-title").textContent = collection.name;

    const bc = document.getElementById("collection-breadcrumb-name");
    if (bc) bc.textContent = collection.name;

    const ownerProfile = getOwnerProfileForCollection(collection, data) || {};
    const ownerId = getOwnerIdForCollection(collection, data);
    const ownerDisplayName =
      ownerProfile["owner-name"] ||
      ownerId ||
      "Unknown Owner";

    const ownerNameEl = document.getElementById("owner-name");
    if (ownerNameEl) {
      ownerNameEl.textContent = ownerDisplayName;
      ownerNameEl.dataset.ownerId = ownerId || "";
      ownerNameEl.classList.add("owner-link");

      if (ownerId) {
        const goToProfile = () => {
          window.location.href = `user_page.html?owner=${encodeURIComponent(ownerId)}`;
        };
        ownerNameEl.onclick = goToProfile;
        ownerNameEl.onkeydown = (ev) => {
          if (ev.key === "Enter" || ev.key === " ") {
            ev.preventDefault();
            goToProfile();
          }
        };
      }
    }

    document.getElementById("creation-date").textContent = collection.createdAt;
    document.getElementById("type").textContent = collection.type || "N/A";
    document.getElementById("description").textContent =
      collection.description || "No description provided.";

    const itemsCountEl = document.getElementById("items-count");
    if (itemsCountEl) {
      const collectionItems = appData.getItemsByCollection(collection.id, data) || [];
      itemsCountEl.textContent = collectionItems.length;
    }

    const ownerPhotoEl = document.getElementById("owner-photo");
    if (ownerPhotoEl) {
      const collectorDefault = "../images/rui.jpg";
      const guestDefault = "../images/user.jpg";
      const fallback = ownerId === DEFAULT_OWNER_ID ? collectorDefault : guestDefault;
      ownerPhotoEl.src =
        ownerProfile["owner-photo"] || ownerProfile.photo || fallback;
      ownerPhotoEl.alt = `${ownerDisplayName} owner`;
    }
  }

  // ===============================================
  // HIGHLIGHT SECTION IF OWNED BY CURRENT USER
  // ===============================================
  function isCollectionOwnedByCurrentUser(collection, data) {
    const ownerId = getEffectiveOwnerId();
    if (!ownerId || !collection) return false;
    const cidOwner = getOwnerIdForCollection(collection, data);
    return Boolean(cidOwner && cidOwner === ownerId);
  }

  function highlightOwnedSection() {
    if (!isCollectionPage) return;

    const data = appData.loadData();
    const col = getCurrentCollection(data);

    if (isCollectionOwnedByCurrentUser(col, data)) {
      itemsContainer.classList.add("owned-section");
      if (editCollectionBtn) editCollectionBtn.style.display = "inline-block";
      if (addItemBtn) addItemBtn.style.display = "inline-block";
    } else {
      itemsContainer.classList.remove("owned-section");
      if (editCollectionBtn) editCollectionBtn.style.display = "none";
      if (addItemBtn) addItemBtn.style.display = "none";
    }
  }


  // ===============================================
  // LIKE SYSTEM (MAPS + HELPERS)
  // ===============================================
  function notifyItemLikesChange(ownerId) {
    if (!ownerId) return;
    window.dispatchEvent(
      new CustomEvent("userItemLikesChange", { detail: { ownerId } })
    );
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
    const set = getItemLikedBy(itemId);

    if (ownerId) {
      const override = getItemVoteOverride(itemId);
      if (override === true) set.add(ownerId);
      else if (override === false) set.delete(ownerId);
      else if (getUserBaseItemLike(itemId, ownerId)) set.add(ownerId);
    }

    return set.size;
  }

  function refreshItemLikesState() {
    const data = appData.loadData();
    buildItemLikesMaps(data);
    return data;
  }

  function getItemLikeSnapshot(itemId, options = {}) {
    const { reload = true } = options;
    if (reload) refreshItemLikesState();

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
    const { skipRender = false, onUpdate } = options;
    if (!isActiveUser) {
      alert("Sign in to like items.");
      return;
    }

    const ownerId = getEffectiveOwnerId();
    if (!ownerId) return;

    refreshItemLikesState();

    const currentState = getEffectiveItemLike(itemId, ownerId);
    const newState = !currentState;

    itemVoteState[itemId] = newState;

    if (window.appData?.setUserItemLike) {
      window.appData.setUserItemLike(ownerId, itemId, newState);
    }

    if (!skipRender) renderItems();
    notifyItemLikesChange(ownerId);

    if (typeof onUpdate === "function") {
      const snapshot = getItemLikeSnapshot(itemId, { reload: false });
      onUpdate(snapshot);
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

  function getItemRatingEntries(itemId, data) {
    if (!itemId) return [];
    return (data?.itemRatings || []).filter(entry => entry.itemId === itemId);
  }

  function getRatingStats(entries) {
    const rated = (entries || []).filter(entry => typeof entry.rating === "number");
    if (!rated.length) return { count: 0, average: null };
    const total = rated.reduce((sum, entry) => sum + entry.rating, 0);
    return { count: rated.length, average: total / rated.length };
  }

  function getUserRatingFromEntries(entries, userId) {
    if (!userId) return null;
    const entry = (entries || []).find(link => link.userId === userId);
    return entry && typeof entry.rating === "number" ? entry.rating : null;
  }

  function buildRatingStarsMarkup(itemId, average, userRating, allowRate) {
    const stars = [];
    for (let i = 1; i <= 5; i++) {
      let classes = "star";
      if (average && i <= Math.round(average)) classes += " filled";
      if (userRating && i <= userRating) classes += " user-rating";
      if (allowRate) classes += " clickable";
      stars.push(`<span class="${classes}" data-value="${i}">★</span>`);
    }
    return `<div class="rating-stars" data-item-id="${itemId}" data-rateable="${allowRate ? "true" : "false"}">${stars.join("")}</div>`;
  }

  function buildRatingSummary(average, count, userRating, sessionValue, allowRate) {
    const parts = [];
    if (average) {
      parts.push(`<span class="muted">★ ${average.toFixed(1)}</span> <span>(${count})</span>`);
    } else {
      parts.push(`<span class="muted">No ratings yet</span>`);
    }

    if (sessionValue !== undefined) {
      parts.push(`<span class="demo-rating-note">Your demo rating: ${sessionValue}/5 (not saved)</span>`);
    } else if (userRating) {
      parts.push(`<span class="demo-rating-note">You rated this ${userRating}/5</span>`);
    } else if (allowRate) {
      parts.push(`<span class="demo-rating-note">Click a star to rate this item.</span>`);
    }

    return parts.join(" ");
  }

  function setItemRating(itemId, value) {
    if (!isActiveUser) {
      alert("Sign in to rate items.");
      return;
    }
    if (!itemId) return;
    const ownerId = getEffectiveOwnerId();
    if (!ownerId) return;
    const numericValue = Number(value);
    if (!Number.isInteger(numericValue) || numericValue < 1 || numericValue > 5) {
      return;
    }
    sessionItemRatings[itemId] = numericValue;
    alert("Demo only: rating stored for this session.");
    window.renderItems();
  }

  function attachItemRatingHandlers() {
    if (!itemsContainer) return;
    const containers = itemsContainer.querySelectorAll('.rating-stars[data-item-id]');
    containers.forEach(container => {
      if (container.dataset.rateable !== "true") return;
      const id = container.dataset.itemId;
      if (!id) return;
      const stars = Array.from(container.querySelectorAll(".star"));

      function clearHover() {
        stars.forEach(star => star.classList.remove("hovered"));
      }

      function highlightTo(value) {
        stars.forEach(star => {
          const numeric = Number(star.dataset.value);
          if (numeric <= value) {
            star.classList.add("hovered");
          } else {
            star.classList.remove("hovered");
          }
        });
      }

      stars.forEach(star => {
        const val = Number(star.dataset.value);
        const rate = () => setItemRating(id, val);
        star.addEventListener("mouseenter", () => highlightTo(val));
        star.addEventListener("focus", () => highlightTo(val));
        star.addEventListener("mouseleave", clearHover);
        star.addEventListener("blur", clearHover);
        star.addEventListener("click", rate);
        star.addEventListener("keydown", ev => {
          if (ev.key === "Enter" || ev.key === " ") {
            ev.preventDefault();
            rate();
          }
        });
        star.setAttribute("tabindex", "0");
        star.setAttribute("role", "button");
        star.setAttribute("aria-label", `Rate ${val} out of 5`);
      });

      container.addEventListener("mouseleave", clearHover);
    });
  }


  // ===============================================
  // FILTERING
  // ===============================================
  function updateItemsFilterNote(message) {
    if (!itemsFilterNote) return;
    itemsFilterNote.textContent = message || "";
  }

  function resolveFilterMode() {
    // On user page, always show liked items
    if (itemsContainer?.id === "user-liked-items") return "liked";
    return itemsFilterSelect ? itemsFilterSelect.value : "all";
  }

  function parseItemTimestamp(item) {
    const dateStr =
      item.acquisitionDate || item.updatedAt || item.createdAt;
    const ts = dateStr ? new Date(dateStr).getTime() : NaN;
    return Number.isNaN(ts) ? 0 : ts;
  }

  const PRICE_FILTER_LABELS = {
    budget: "Budget (< EUR 100)",
    mid: "Mid (EUR 100-500)",
    premium: "Premium (> EUR 500)"
  };

  function matchesPriceRange(item, range) {
    const price = Number(item.price);
    if (!Number.isFinite(price)) return false;

    if (range === "budget") return price < 100;
    if (range === "mid") return price >= 100 && price <= 500;
    if (range === "premium") return price > 500;

    return true;
  }

  function applyItemsFilter(allItems) {
    const mode = resolveFilterMode();
    const importanceMode = importanceFilterSelect?.value || "all";
    const priceMode = priceFilterSelect?.value || "all";
    const ownerId = getEffectiveOwnerId();

    const base = Array.isArray(allItems) ? allItems.slice() : [];
    let filtered = base;
    let noteParts = [];
    let requiresLogin = false;

    if (!mode || mode === "all") {
      filtered = base;
      if (base.length) noteParts.push("Showing all items.");
    } else if (mode === "liked") {
      if (!isActiveUser || !ownerId) {
        return {
          items: [],
          note: "Sign in to view your liked items.",
          requiresLogin: true
        };
      }
      filtered = base.filter(it => getEffectiveItemLike(it.id, ownerId));
      const likedNote = filtered.length
        ? `Showing ${filtered.length} liked item${filtered.length === 1 ? "" : "s"}.`
        : "You haven't liked any items yet.";
      noteParts.push(likedNote);
    } else if (mode === "mostLiked") {
      filtered.sort((a, b) => getItemLikedBy(b.id).size - getItemLikedBy(a.id).size);
      const mostLikedNote = filtered.length
        ? "Sorted by community likes."
        : "No items available.";
      noteParts.push(mostLikedNote);
    } else if (mode === "recent") {
      filtered.sort((a, b) => parseItemTimestamp(b) - parseItemTimestamp(a));
      if (filtered.length) noteParts.push("Newest items first.");
    } else {
      filtered = base;
    }

    if (importanceMode !== "all") {
      filtered = filtered.filter(item => item.importance === importanceMode);
      noteParts.push(`Importance: ${importanceMode}`);
    }

    if (priceMode !== "all") {
      filtered = filtered.filter(item => matchesPriceRange(item, priceMode));
      const priceLabel = PRICE_FILTER_LABELS[priceMode] || priceMode;
      noteParts.push(`Price: ${priceLabel}`);
    }

    return {
      items: filtered,
      note: noteParts.filter(Boolean).join(" "),
      requiresLogin
    };
  }


  // ===============================================
  // RENDER ITEMS (WITH PAGINATION + CHUNK RENDERING)
  // ===============================================
  window.renderItems = function renderItems() {
    if (!itemsContainer) return;

    const data = refreshItemLikesState();
    const collection = getCurrentCollection(data);

    // If collection page but invalid ID
    if (isCollectionPage && !collection) {
      itemsContainer.innerHTML = `<p class="notice-message">Collection not found.</p>`;
      updatePaginationSummary(0, 0, 0);
      return;
    }

    itemsContainer.innerHTML = "";

    // Choose base items (collection or all items on user_page)
    const baseItems =
      isCollectionPage && collectionId
        ? appData.getItemsByCollection(collectionId, data) || []
        : data.items || [];

    const filter = applyItemsFilter(baseItems);
    updateItemsFilterNote(filter.note);

    let itemsToRender = filter.items;
    const total = itemsToRender.length;
    let startIndex = 0;

    // Apply pagination
    if (hasPagination && paginationState && paginationState.pageSize > 0) {
      const size = Math.max(
        paginationState.pageSize || defaultPageSize || 1,
        1
      );
      paginationState.pageSize = size;

      const totalPages = Math.ceil((total || 0) / size);
      if (totalPages === 0) paginationState.pageIndex = 0;
      else if (paginationState.pageIndex >= totalPages)
        paginationState.pageIndex = totalPages - 1;

      startIndex = paginationState.pageIndex * size;
      const endIndex = startIndex + size;
      itemsToRender = itemsToRender.slice(startIndex, endIndex);
    }

    updatePaginationSummary(total, startIndex, itemsToRender.length);

    if (!itemsToRender.length) {
      const emptyMsg = document.createElement("p");
      emptyMsg.className = "no-items-message";
      emptyMsg.textContent =
        filter.note || "This collection has no items yet.";
      itemsContainer.appendChild(emptyMsg);
      return;
    }

    const ownerId = getEffectiveOwnerId();

    const loading = document.createElement("p");
    loading.className = "notice-message";
    loading.textContent = "Loading items...";
    itemsContainer.appendChild(loading);

    // ----------
    // Chunked rendering
    // ----------
    function renderChunk(idx = 0) {
      const CHUNK = 40;
      const frag = document.createDocumentFragment();
      const slice = itemsToRender.slice(idx, idx + CHUNK);

      for (const item of slice) {
        const card = document.createElement("div");
        card.className = "card item-card";

        const isLiked = ownerId
          ? getEffectiveItemLike(item.id, ownerId)
          : false;

        const likeCount = getItemDisplayLikes(item.id, ownerId);

        const likeButton = `
          <button class="item-like-btn ${isLiked ? "active" : ""}"
                  data-item-id="${item.id}"
                  aria-pressed="${isLiked}">
            <i class="bi ${isLiked ? "bi-star-fill" : "bi-star"}"></i>
            <span class="like-count">${likeCount}</span>
          </button>
        `;

        const ratingEntries = getItemRatingEntries(item.id, data);
        const { count: ratingCount, average: ratingAvg } = getRatingStats(ratingEntries);
        const sessionValue = ownerId && isActiveUser ? sessionItemRatings[item.id] : undefined;
        const storedUserRating = ownerId ? getUserRatingFromEntries(ratingEntries, ownerId) : null;
        const userRating = ownerId
          ? (sessionValue !== undefined ? sessionValue : storedUserRating ?? null)
          : storedUserRating ?? null;
        const allowRating = Boolean(ownerId && isActiveUser);
        const ratingStars = buildRatingStarsMarkup(item.id, ratingAvg, userRating, allowRating);
        const ratingSummary = buildRatingSummary(ratingAvg, ratingCount, userRating, sessionValue, allowRating);
        const ratingBlock = `
          <div class="card-rating">
            ${ratingStars}
            <div class="rating-summary">${ratingSummary}</div>
          </div>
        `;

        const isItemOwner =
          isCollectionPage &&
          isCollectionOwnedByCurrentUser(collection, data);

        const ownerButtons = isItemOwner
          ? `
            <div class="item-buttons">
              <button class="explore-btn warning"
                      onclick="editItem('${item.id}')">
                <i class="bi bi-pencil-square"></i> Edit
              </button>
              <button class="explore-btn danger"
                      onclick="deleteItem('${item.id}')">
                <i class="bi bi-trash"></i> Delete
              </button>
            </div>
          `
          : "";

        card.innerHTML = `
          <div class="item-image-wrapper">
            <img src="${item.image}" alt="${item.name}"
                 class="item-image" loading="lazy">
          </div>

          <div class="item-info simple-item">
            <h3>${item.name}</h3>
            ${ratingBlock}

            <div class="item-actions">
              ${likeButton}
              <a href="item_page.html?id=${item.id}"
                 class="explore-btn view-btn">
                <i class="bi bi-eye"></i> View Item
              </a>
            </div>

            ${ownerButtons}
          </div>
        `;

        frag.appendChild(card);
      }

      if (idx === 0 && loading.isConnected) loading.remove();
      itemsContainer.appendChild(frag);

      if (idx + CHUNK < itemsToRender.length) {
        setTimeout(() => renderChunk(idx + CHUNK), 0);
      } else {
        attachItemLikeHandlers();
        attachItemRatingHandlers();
      }
    }

    renderChunk();
  };


  // ===============================================
  // LIKE HELPERS (GLOBAL)
  // ===============================================
  window.itemLikeHelpers = {
    snapshot: (id, options) => getItemLikeSnapshot(id, options),
    toggle: (id, options) => toggleItemLike(id, options),
    refresh: () => refreshItemLikesState(),
    getOwnerId: () => getEffectiveOwnerId(),
    isUserActive: () => Boolean(isActiveUser)
  };

  // ===============================================
  // COLLECTION EVENTS
  // ===============================================
  function formatEventDate(dateStr) {
    if (!dateStr) return "Date TBA";
    const d = new Date(dateStr);
    if (Number.isNaN(d.getTime())) return dateStr;
    return d.toLocaleDateString(undefined, {
      year: "numeric",
      month: "short",
      day: "numeric"
    });
  }

  function renderCollectionEvents() {
    if (!eventsContainer) return;

    const data = appData.loadData();
    const collection = getCurrentCollection(data);

    if (!collection) {
      eventsContainer.innerHTML =
        `<p class="notice-message">Collection not found.</p>`;
      return;
    }

    const events = appData.getEventsByCollection(collection.id, data) || [];

    if (!events.length) {
      eventsContainer.innerHTML =
        `<p class="notice-message">No events linked to this collection yet.</p>`;
      return;
    }

    let encodedReturnUrl = "";
    try {
      const ret = new URL(window.location.href);
      ret.hash = "collection-events";
      encodedReturnUrl = encodeURIComponent(ret.toString());
    } catch {
      const fallback = `${window.location.pathname || ""}#collection-events`;
      encodedReturnUrl = encodeURIComponent(fallback);
    }

    eventsContainer.innerHTML = events.map(ev => `
      <article class="collection-event-card">
        <div>
          <h3>${ev.name}</h3>
          <p class="event-meta">
            ${formatEventDate(ev.date)} · ${ev.localization || "To be announced"}
          </p>
        </div>
        <a class="explore-btn ghost"
           href="event_page.html?id=${ev.id}&returnUrl=${encodedReturnUrl}">
          <i class="bi bi-calendar-event"></i> View event
        </a>
      </article>
    `).join("");
  }


  // ===============================================
  // COLLECTION STATISTICS
  // ===============================================
  function computeCollectionStatistics(targetCollectionId) {
    const cid = targetCollectionId || collectionId;
    const data = appData.loadData();

    if (!cid || !data) {
      return {
        totalItems: 0,
        totalValue: 0,
        avgWeight: null,
        linkedEvents: 0,
        oldestItem: null,
        newestItem: null
      };
    }

    const items = appData.getItemsByCollection(cid, data) || [];
    const totalItems = items.length;

    const totalValue = items.reduce((sum, it) => {
      const v = Number(it.price);
      return sum + (Number.isFinite(v) ? v : 0);
    }, 0);

    const weightValues = items
      .map(it => Number(it.weight))
      .filter(w => Number.isFinite(w));

    const avgWeight = weightValues.length
      ? weightValues.reduce((a, b) => a + b, 0) / weightValues.length
      : null;

    const events = appData.getEventsByCollection(cid, data) || [];
    const linkedEvents = events.length;

    const itemsWithDates = items
      .map(it => ({
        item: it,
        ts: it.acquisitionDate ? new Date(it.acquisitionDate).getTime() : NaN
      }))
      .filter(x => Number.isFinite(x.ts));

    let oldestItem = null;
    let newestItem = null;

    if (itemsWithDates.length) {
      itemsWithDates.sort((a, b) => a.ts - b.ts);
      oldestItem = {
        name: itemsWithDates[0].item.name,
        date: itemsWithDates[0].item.acquisitionDate
      };
      newestItem = {
        name: itemsWithDates[itemsWithDates.length - 1].item.name,
        date: itemsWithDates[itemsWithDates.length - 1].item.acquisitionDate
      };
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

  function renderCollectionStats(stats) {
    const panel = document.getElementById("collection-stats");
    if (!panel || !stats) return;

    function setCardValue(key, value, formatter) {
      const card = panel.querySelector(`.stat-card[data-key="${key}"]`);
      if (!card) return;

      const valEl = card.querySelector(".stat-value");

      if (value === null || value === undefined) {
        card.classList.add("hidden");
        return;
      }

      card.classList.remove("hidden");
      valEl.textContent = formatter ? formatter(value) : String(value);
    }

    // total items
    setCardValue("totalItems", stats.totalItems);

    // total value (EUR)
    setCardValue("totalValue", stats.totalValue, (v) => {
      try {
        return new Intl.NumberFormat(undefined, {
          style: "currency",
          currency: "EUR",
          minimumFractionDigits: 2,
          maximumFractionDigits: 2
        }).format(v);
      } catch {
        return `€${Number(v).toFixed(2)}`;
      }
    });

    // avg weight
    setCardValue("avgWeight", stats.avgWeight, (v) => `${v.toFixed(2)} g`);

    // linked events
    setCardValue("linkedEvents", stats.linkedEvents);

    // oldest item
    setCardValue("oldestItem", stats.oldestItem, (v) => {
      const d = new Date(v.date);
      const dateStr = Number.isNaN(d.getTime()) ? v.date : d.toLocaleDateString();
      return `${v.name} · ${dateStr}`;
    });

    // newest item
    setCardValue("newestItem", stats.newestItem, (v) => {
      const d = new Date(v.date);
      const dateStr = Number.isNaN(d.getTime()) ? v.date : d.toLocaleDateString();
      return `${v.name} · ${dateStr}`;
    });
  }


  // ===============================================
  // POPULATE COLLECTION SELECT (ADD/EDIT ITEM)
  // ===============================================
  function populateCollectionsSelect() {
    const select = document.getElementById("item-collections");
    if (!select) return;

    const data = appData.loadData();
    if (!data || !data.collections) return;

    select.innerHTML = "";
    const ownerId = getEffectiveOwnerId();

    const userCollections = data.collections.filter(col => {
      const colOwnerId = getOwnerIdForCollection(col, data);
      return (
        colOwnerId === DEFAULT_OWNER_ID ||
        (ownerId && colOwnerId === ownerId)
      );
    });

    userCollections.forEach(col => {
      const opt = document.createElement("option");
      opt.value = col.id;
      opt.textContent = col.name;
      select.appendChild(opt);
    });
  }


  // ===============================================
  // ITEM MODAL HANDLING
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

  window.openItemModal = (edit = false) => openModal(edit);


  // ===============================================
  // EDIT & DELETE (SIMULATED)
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
    const collection = getCurrentCollection(data);

    if (!isCollectionOwnedByCurrentUser(collection, data)) {
      return alert("You can only delete your own items.");
    }

    if (confirm("Delete this item?\n\n(This is a demonstration.)")) {
      alert("Simulation: item deleted (not really).");
    }
  };


  // ===============================================
  // COLLECTION EDITING
  // ===============================================
  function openCollectionModal() {
    const data = appData.loadData();
    const collection = data.collections.find(c => c.id === collectionId);

    if (!collection) return alert("Collection not found!");
    if (!isCollectionOwnedByCurrentUser(collection, data)) {
      return alert("You can only edit your own collections.");
    }

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

  if (editCollectionBtn)
    editCollectionBtn.addEventListener("click", openCollectionModal);

  if (closeCollectionModalBtn)
    closeCollectionModalBtn.addEventListener("click", closeCollectionModal);

  if (cancelCollectionModalBtn)
    cancelCollectionModalBtn.addEventListener("click", closeCollectionModal);

  if (collectionForm) {
    collectionForm.addEventListener("submit", (e) => {
      e.preventDefault();
      alert("Simulation: collection updated (not really).");
      closeCollectionModal();
    });
  }


  // ===============================================
  // ITEM FORM SUBMISSION
  // ===============================================
  if (form) {
    form.addEventListener("submit", (e) => {
      e.preventDefault();

      if (!isActiveUser) return alert("You must be logged in to add items.");

      const id = idField.value.trim();
      const action = id ? "updated" : "created";

      alert(
        `Simulation: item would have been ${action} (not saved).`
      );

      closeModal();
    });
  }


  // ===============================================
  // FILTER LISTENERS
  // ===============================================
  if (itemsFilterSelect) {
    itemsFilterSelect.addEventListener("change", () => renderItems());
  }

  if (importanceFilterSelect) {
    importanceFilterSelect.addEventListener("change", () => renderItems());
  }

  if (priceFilterSelect) {
    priceFilterSelect.addEventListener("change", () => renderItems());
  }

  if (resetItemsFilterBtn) {
    resetItemsFilterBtn.addEventListener("click", () => {
      if (itemsFilterSelect) itemsFilterSelect.value = "all";
      if (importanceFilterSelect) importanceFilterSelect.value = "all";
      if (priceFilterSelect) priceFilterSelect.value = "all";
      renderItems();
    });
  }


  // ===============================================
  // MODAL CLICK-CLOSE
  // ===============================================
  window.addEventListener("click", (e) => {
    if (e.target === modal) closeModal();
    if (e.target === collectionModal) closeCollectionModal();
  });

  if (addItemBtn) {
    addItemBtn.addEventListener("click", (event) => {
      event.preventDefault();
      populateCollectionsSelect();
      openModal(false);
    });
  }


  // ===============================================
  // HANDLE URL ACTIONS (edit, delete, add)
  // ===============================================
  function handleItemActionParam() {
    const p = new URLSearchParams(window.location.search);

    const action = p.get("itemAction");
    const itemId = p.get("itemId");

    if (!action) return;

    if (action === "add") {
      openModal(false);
    } else if (action === "edit" && itemId) {
      window.editItem(itemId);
    } else if (action === "delete" && itemId) {
      window.deleteItem(itemId);
    }

    p.delete("itemAction");
    p.delete("itemId");

    const rest = p.toString();
    const nextUrl = rest
      ? `${window.location.pathname}?${rest}`
      : window.location.pathname;

    window.history.replaceState({}, "", nextUrl);
  }


  // ===============================================
  // LOGIN / LOGOUT DYNAMIC REFRESH
  // ===============================================
  window.addEventListener("userStateChange", (e) => {
    const newActive = e.detail?.active;

    if (newActive === isActiveUser) return;

    updateUserState();
    highlightOwnedSection();
    renderCollectionEvents();
    renderItems();

    try {
      const stats = computeCollectionStatistics();
      renderCollectionStats(stats);
    } catch (err) {
      console.error("Error updating stats:", err);
    }
  });


  // ===============================================
  // INITIALIZATION
  // ===============================================
  populateCollectionsSelect();

  if (isCollectionPage) {
    if (collectionId) renderCollectionDetails();

    try {
      const stats = computeCollectionStatistics();
      renderCollectionStats(stats);
    } catch (err) {
      console.error("Error computing stats:", err);
    }

    if (hasPagination) {
      syncPageSizeSelects(paginationState.pageSize);
      paginationControls.forEach(ctrl => {
        const s = ctrl.querySelector("[data-page-size]");
        if (s) {
          s.addEventListener("change", (ev) => {
            const next = parseInt(ev.target.value, 10);
            if (!Number.isNaN(next) && next > 0) {
              paginationState.pageSize = next;
              paginationState.pageIndex = 0;
              syncPageSizeSelects(next);
              renderItems();
            }
          });
        }

        const prev = ctrl.querySelector("[data-page-prev]");
        if (prev)
          prev.addEventListener("click", () => {
            if (paginationState.pageIndex > 0) {
              paginationState.pageIndex--;
              renderItems();
            }
          });

        const nextBtn = ctrl.querySelector("[data-page-next]");
        if (nextBtn)
          nextBtn.addEventListener("click", () => {
            paginationState.pageIndex++;
            renderItems();
          });
      });
    }

    renderItems();
    highlightOwnedSection();

  } else if (itemsContainer) {
    // user_page: only liked items
    renderItems();
  }

  renderCollectionEvents();
  handleItemActionParam();


  // Expose stats helpers
  window.computeCollectionStatistics = computeCollectionStatistics;
  window.renderCollectionStats = renderCollectionStats;

}); // END DOMContentLoaded


// ===============================================
// ITEM PAGE REDIRECT HELPER
// ===============================================
window.viewItem = function viewItem(itemId) {
  localStorage.setItem("currentItemId", itemId);
  window.location.href = `item_page.html?id=${encodeURIComponent(itemId)}`;
};

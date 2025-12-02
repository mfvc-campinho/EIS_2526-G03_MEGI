// ===============================================
// item_page.js — Display details of one item
// ===============================================
const DEFAULT_OWNER_ID = "collector-main";

document.addEventListener("DOMContentLoaded", () => {
  const params = new URLSearchParams(window.location.search);
  const itemId = params.get("id") || localStorage.getItem("currentItemId");

  const actionsContainer = document.querySelector(".item-action-buttons");
  const addBtn = document.getElementById("add-item-btn");
  const editBtn = document.getElementById("edit-item-btn");
  const deleteBtn = document.getElementById("delete-item-btn");
  const collectionsListEl = document.getElementById("item-collections-list");
  const ownerLinksEl = document.getElementById("item-owner-links");
  const itemLikeBtn = document.getElementById("item-like-btn");
  const itemLikeCount = document.getElementById("item-like-count");
  const itemLikeHelper = document.getElementById("item-like-helper");

  if (!itemId) {
    notify("No item selected.", "error");
    return;
  }

  const data = appData.loadData();
  const item = (data.items || []).find(i => i.id === itemId);

  if (!item) {
    notify("Item not found.", "error");
    return;
  }

  document.getElementById("item-name-display").textContent = item.name || "Unnamed Item";
  document.getElementById("item-importance-display").textContent = item.importance || "N/A";
  document.getElementById("item-weight-display").textContent = item.weight || "N/A";
  document.getElementById("item-price-display").textContent = item.price || "0.00";
  document.getElementById("item-date-display").textContent = item.acquisitionDate || "-";
  document.getElementById("item-image-display").src = item.image || "../images/default.jpg";

  const collectionLinks = (data.collectionItems || []).filter(link => link.itemId === itemId);
  const collectionIds = collectionLinks.map(link => link.collectionId);
  let primaryCollectionId = collectionIds[0] || null;

  const collections = (data.collections || []).filter(col => collectionIds.includes(col.id));
  if (!primaryCollectionId && item.collectionId) {
    primaryCollectionId = item.collectionId;
    const fallbackCollection = (data.collections || []).find(col => col.id === primaryCollectionId);
    if (fallbackCollection) collections.push(fallbackCollection);
  }

  /* Render accessible breadcrumb: Home > Collections > (Collection) > Item */
  function renderBreadcrumb() {
    const nav = document.getElementById('page-breadcrumb');
    if (!nav) return;
    const ol = nav.querySelector('.breadcrumb-list');
    if (!ol) return;
    // Clear existing items (keep Home and Collections if present)
    ol.innerHTML = '';

    const makeLi = (content, href, isCurrent) => {
      const li = document.createElement('li');
      li.className = 'breadcrumb-item';
      if (isCurrent) {
        li.setAttribute('aria-current', 'page');
        li.textContent = content;
      } else if (href) {
        const a = document.createElement('a');
        a.href = href;
        a.textContent = content;
        li.appendChild(a);
      } else {
        li.textContent = content;
      }
      return li;
    };

    // Home
    ol.appendChild(makeLi('Home', 'home_page.html', false));
    // Collections
    ol.appendChild(makeLi('Collections', 'all_collections.html', false));

    // Collection (if available)
    if (primaryCollectionId) {
      const col = (data.collections || []).find(c => c.id === primaryCollectionId) || collections[0];
      const colName = col?.name || primaryCollectionId;
      const colHref = `specific_collection.html?id=${encodeURIComponent(primaryCollectionId)}`;
      ol.appendChild(makeLi(colName, colHref, false));
    }

    // Current item
    const itemName = item?.name || 'Item';
    ol.appendChild(makeLi(itemName, null, true));
  }


  function resolveOwnerIdForCollection(collectionId) {
    if (!collectionId) return null;
    const link = (data.collectionsUsers || []).find(entry => entry.collectionId === collectionId);
    if (link?.ownerId) return link.ownerId;
    const collection = (data.collections || []).find(c => c.id === collectionId);
    return collection?.ownerId || null;
  }

  let collectionOwnerId = resolveOwnerIdForCollection(primaryCollectionId);
  let canManage = false;
  let manageEventsBound = false;

  function getStoredEffectiveOwnerId() {
    const storedUser = JSON.parse(localStorage.getItem("currentUser"));
    if (!storedUser || !storedUser.active) return null;
    return storedUser.id || DEFAULT_OWNER_ID;
  }

  function computeCanManage() {
    const effectiveOwnerId = getStoredEffectiveOwnerId();
    return Boolean(collectionOwnerId && effectiveOwnerId && collectionOwnerId === effectiveOwnerId);
  }

  function ensureCollectionLink() {
    if (primaryCollectionId) return true;
    notify("Unable to determine the collection for this item.", "error");
    return false;
  }

  if (primaryCollectionId && typeof window.setCurrentCollectionId === "function") {
    window.setCurrentCollectionId(primaryCollectionId);
  }

  function clearAndSetPlaceholder(container, message) {
    if (!container) return;
    container.innerHTML = "";
    const placeholder = document.createElement("p");
    placeholder.className = "pill-placeholder";
    placeholder.textContent = message;
    container.appendChild(placeholder);
  }

  function renderCollectionLinks() {
    if (!collectionsListEl) return;
    if (!collections.length) {
      clearAndSetPlaceholder(collectionsListEl, "This item is not linked to any collection yet.");
      return;
    }
    collectionsListEl.innerHTML = "";
    collections.forEach(col => {
      const link = document.createElement("a");
      link.className = "pill-link";
      link.href = `specific_collection.html?id=${encodeURIComponent(col.id)}`;
      link.innerHTML = `<i class="bi bi-box-seam" aria-hidden="true"></i><span>${col.name || col.id}</span>`;
      link.setAttribute("aria-label", `Open collection ${col.name || col.id}`);
      collectionsListEl.appendChild(link);
    });
  }

  function renderOwnerLinks() {
    if (!ownerLinksEl) return;
    const ownersMap = new Map();
    collections.forEach(col => {
      const ownerId = resolveOwnerIdForCollection(col.id);
      if (!ownerId || ownersMap.has(ownerId)) return;
      const profile = (data.users || []).find(u => {
        const uid = String(u?.id || u?.user_id || u?.['owner-id'] || '');
        const uname = String(u?.['owner-name'] || u?.user_name || u?.['user_name'] || '');
        return uid === ownerId || uname === ownerId;
      });
      ownersMap.set(ownerId, {
        id: ownerId,
        name: profile?.['owner-name'] || profile?.user_name || ownerId
      });
    });

    if (!ownersMap.size) {
      clearAndSetPlaceholder(ownerLinksEl, "Owner information not available.");
      return;
    }

    ownerLinksEl.innerHTML = "";
    ownersMap.forEach(owner => {
      const link = document.createElement("a");
      link.className = "pill-link";
      link.href = `user_page.html?owner=${encodeURIComponent(owner.id)}`;
      link.innerHTML = `<i class="bi bi-person" aria-hidden="true"></i><span>${owner.name}</span>`;
      link.setAttribute("aria-label", `View owner profile for ${owner.name}`);
      ownerLinksEl.appendChild(link);
    });
  }

  function getItemLikeHelpers() {
    return window.itemLikeHelpers || null;
  }

  function updateItemLikeDisplay(snapshot) {
    if (!itemLikeBtn || !itemLikeCount) return;
    if (!snapshot) {
      itemLikeBtn.disabled = true;
      itemLikeBtn.classList.remove("active");
      itemLikeBtn.setAttribute("aria-pressed", "false");
      itemLikeBtn.title = "Likes unavailable right now.";
      const icon = itemLikeBtn.querySelector("i");
      if (icon) icon.className = "bi bi-heart";
      itemLikeCount.textContent = "0";
      if (itemLikeHelper) itemLikeHelper.textContent = "Likes unavailable right now.";
      return;
    }
    const { likeCount = 0, liked = false, ownerId = null } = snapshot;
    itemLikeBtn.disabled = false;
    itemLikeBtn.classList.toggle("active", liked);
    itemLikeBtn.setAttribute("aria-pressed", liked ? "true" : "false");
    const icon = itemLikeBtn.querySelector("i");
    if (icon) icon.className = liked ? "bi bi-heart-fill" : "bi bi-heart";
    itemLikeCount.textContent = String(likeCount);
    if (ownerId) {
      itemLikeBtn.title = liked ? "Unlike this item" : "Like this item";
      if (itemLikeHelper) itemLikeHelper.textContent = "Likes are stored locally in this prototype and may not persist.";
    } else {
      itemLikeBtn.title = "Please sign in to like this item.";
      if (itemLikeHelper) itemLikeHelper.textContent = "Please sign in to like this item.";
    }
  }

  function refreshItemLikeState() {
    if (!itemLikeBtn) return;
    const helpers = getItemLikeHelpers();
    if (!helpers || typeof helpers.snapshot !== "function") {
      updateItemLikeDisplay(null);
      return;
    }
    const snapshot = helpers.snapshot(itemId);
    updateItemLikeDisplay(snapshot);
  }

  function initItemLikeButton() {
    if (!itemLikeBtn) return;
    itemLikeBtn.dataset.itemId = itemId;
    refreshItemLikeState();
    itemLikeBtn.addEventListener("click", () => {
      const helpers = getItemLikeHelpers();
      if (!helpers || typeof helpers.toggle !== "function") {
        notify("Likes unavailable right now.", "error");
        return;
      }
      helpers.toggle(itemId, {
        skipRender: true,
        onUpdate: updateItemLikeDisplay
      });
    });
  }

  // Render breadcrumb then collection/owner links
  renderBreadcrumb();
  renderCollectionLinks();
  renderOwnerLinks();
  initItemLikeButton();
  updateManageControls();

  function handleAddItemClick() {
    if (!canManage) return;
    if (!ensureCollectionLink()) return;
    if (typeof window.openItemModal !== "function") {
      notify("Item modal is not available.", "error");
      return;
    }
    window.openItemModal(false);
  }

  function handleEditItemClick() {
    if (!canManage) return;
    if (!ensureCollectionLink()) return;
    if (typeof window.editItem !== "function") {
      notify("Edit action is not available.", "error");
      return;
    }
    window.editItem(itemId);
  }

  function handleDeleteItemClick() {
    if (!canManage) return;
    if (!ensureCollectionLink()) return;
    if (typeof window.deleteItem !== "function") {
      notify("Delete action is not available.", "error");
      return;
    }
    window.deleteItem(itemId);
  }

  function bindManageEvents() {
    if (manageEventsBound) return;
    manageEventsBound = true;
    addBtn?.addEventListener("click", handleAddItemClick);
    editBtn?.addEventListener("click", handleEditItemClick);
    deleteBtn?.addEventListener("click", handleDeleteItemClick);
  }

  function updateManageControls() {
    const hasAccess = computeCanManage();
    canManage = hasAccess;
    if (actionsContainer) {
      actionsContainer.classList.toggle("hidden", !hasAccess);
    }
    if (hasAccess) {
      bindManageEvents();
    }
  }

  window.addEventListener("userStateChange", () => {
    refreshItemLikeState();
    updateManageControls();
  });
  window.addEventListener("userItemLikesChange", (event) => {
    const changedOwner = event.detail?.ownerId;
    const helpers = getItemLikeHelpers();
    const activeOwner = helpers?.getOwnerId ? helpers.getOwnerId() : null;
    if (!changedOwner || !activeOwner || changedOwner === activeOwner) {
      refreshItemLikeState();
    }
  });
});

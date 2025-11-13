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

  if (!itemId) {
    alert("No item selected.");
    return;
  }

  const data = appData.loadData();
  const item = (data.items || []).find(i => i.id === itemId);

  if (!item) {
    alert("Item not found.");
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

  function resolveOwnerIdForCollection(collectionId) {
    if (!collectionId) return null;
    const link = (data.collectionsUsers || []).find(entry => entry.collectionId === collectionId);
    if (link?.ownerId) return link.ownerId;
    const collection = (data.collections || []).find(c => c.id === collectionId);
    return collection?.ownerId || null;
  }

  let collectionOwnerId = resolveOwnerIdForCollection(primaryCollectionId);

  const currentUser = JSON.parse(localStorage.getItem("currentUser"));
  const effectiveOwnerId = currentUser && currentUser.active
    ? (currentUser.id || DEFAULT_OWNER_ID)
    : null;
  const canManage = Boolean(collectionOwnerId && effectiveOwnerId && collectionOwnerId === effectiveOwnerId);

  function ensureCollectionLink() {
    if (primaryCollectionId) return true;
    alert("Unable to determine the collection for this item.");
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
      const profile = (data.users || []).find(u => u["owner-id"] === ownerId);
      ownersMap.set(ownerId, {
        id: ownerId,
        name: profile?.["owner-name"] || ownerId
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

  renderCollectionLinks();
  renderOwnerLinks();

  if (canManage) {
    addBtn?.addEventListener("click", () => {
      if (!ensureCollectionLink()) return;
      if (typeof window.openItemModal !== "function") {
        alert("Item modal is not available.");
        return;
      }
      window.openItemModal(false);
    });

    editBtn?.addEventListener("click", () => {
      if (!ensureCollectionLink()) return;
      if (typeof window.editItem !== "function") {
        alert("Edit action is not available.");
        return;
      }
      window.editItem(itemId);
    });

    deleteBtn?.addEventListener("click", () => {
      if (!ensureCollectionLink()) return;
      if (typeof window.deleteItem !== "function") {
        alert("Delete action is not available.");
        return;
      }
      window.deleteItem(itemId);
    });
  } else {
    actionsContainer?.classList.add("hidden");
  }
});

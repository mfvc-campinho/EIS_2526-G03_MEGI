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

  const collectionLink = (data.collectionItems || []).find(link => link.itemId === itemId);
  const collectionId = collectionLink?.collectionId || null;

  let collectionOwnerId = null;
  if (collectionId) {
    const link = (data.collectionsUsers || []).find(entry => entry.collectionId === collectionId);
    if (link) {
      collectionOwnerId = link.ownerId;
    } else {
      const collection = (data.collections || []).find(c => c.id === collectionId);
      collectionOwnerId = collection?.ownerId || null;
    }
  }

  const currentUser = JSON.parse(localStorage.getItem("currentUser"));
  const effectiveOwnerId = currentUser && currentUser.active
    ? (currentUser.id || DEFAULT_OWNER_ID)
    : null;
  const canManage = Boolean(collectionOwnerId && effectiveOwnerId && collectionOwnerId === effectiveOwnerId);

  const friendlyName = item.name || "this item";

  function ensureCollectionLink() {
    if (collectionId) return true;
    alert("Unable to determine the collection for this item.");
    return false;
  }

  if (collectionId && typeof window.setCurrentCollectionId === "function") {
    window.setCurrentCollectionId(collectionId);
  }

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

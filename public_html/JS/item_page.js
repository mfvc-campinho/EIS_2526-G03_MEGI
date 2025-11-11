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

  document.getElementById("item-name").textContent = item.name || "Unnamed Item";
  document.getElementById("item-importance").textContent = item.importance || "N/A";
  document.getElementById("item-weight").textContent = item.weight || "N/A";
  document.getElementById("item-price").textContent = item.price || "0.00";
  document.getElementById("item-date").textContent = item.acquisitionDate || "-";
  document.getElementById("item-image").src = item.image || "../images/default.jpg";

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

  function redirectToCollection(action) {
    if (!ensureCollectionLink()) return;
    const search = new URLSearchParams({ id: collectionId, itemAction: action });
    if (action !== "add") {
      search.set("itemId", itemId);
    }
    window.location.href = `specific_collection.html?${search.toString()}`;
  }

  if (canManage) {
    addBtn?.addEventListener("click", () => redirectToCollection("add"));
    editBtn?.addEventListener("click", () => redirectToCollection("edit"));
    deleteBtn?.addEventListener("click", () => redirectToCollection("delete"));
  } else {
    actionsContainer?.classList.add("hidden");
  }
});

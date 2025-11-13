// ===============================================
// File: public_html/JS/app-data.js
// Purpose: Provide a lightweight data API for the demo app — load/save demo data to localStorage and helpers for relations (items/events/collections/users).
// Major blocks: initialization, utility functions (load/save), relation helpers, CRUD helpers, export (window.appData).
// Notes: appData is exported to window and used by page scripts (app-collections.js, app-events.js, app-items.js).
// ===============================================

document.addEventListener("DOMContentLoaded", () => {
  // ============================================================
  // 1. Initialization
  // - Import demo data from Data.js into localStorage if missing
  // ============================================================
  if (!localStorage.getItem("collectionsData")) {
    if (typeof collectionsData !== "undefined") {
      localStorage.setItem("collectionsData", JSON.stringify(collectionsData));
      console.log("✅ Initial data imported from Data.js");
    } else {
      console.error("❌ ERROR: Data.js was not loaded.");
    }
  } else {
    console.log("📦 Data loaded from localStorage.");
  }

  // ============================================================
  // 2. Utility functions
  // ============================================================
  function loadData() {
    return JSON.parse(localStorage.getItem("collectionsData"));
  }

  function saveData(data) {
    localStorage.setItem("collectionsData", JSON.stringify(data));
  }

  // ============================================================
  // 3. Many-to-many relations helpers
  // ============================================================

  // Items associated with a collection
  function getItemsByCollection(collectionId, data) {
    if (!data) data = loadData(); // Load data when argument is omitted
    if (!data || !data.collectionItems) return [];

    // Optimization: use a Set for O(1) lookups instead of Array.includes() which is O(n).
    const linkedItemIds = new Set(
      data.collectionItems
        .filter(link => link.collectionId === collectionId)
        .map(link => link.itemId)
    );

    return data.items.filter(item => linkedItemIds.has(item.id));
  }

  // Events associated with a collection
  function getEventsByCollection(collectionId) {
    const data = loadData();
    if (!data || !data.collectionEvents) return [];

    const linkedIds = data.collectionEvents
      .filter(link => link.collectionId === collectionId)
      .map(link => link.eventId);

    return data.events.filter(event => linkedIds.includes(event.id));
  }

  // Create a new item ↔ collection link
  function linkItemToCollection(itemId, collectionId) {
    const data = loadData();
    if (!data.collectionItems) data.collectionItems = [];

    const exists = data.collectionItems.some(
      l => l.itemId === itemId && l.collectionId === collectionId
    );
    if (!exists) {
      data.collectionItems.push({ itemId, collectionId });
      saveData(data);
      console.log(`Linked item ${itemId} to collection ${collectionId}`);
    }
  }

  // Create a new event ↔ collection link
  function linkEventToCollection(eventId, collectionId) {
    const data = loadData();
    if (!data.collectionEvents) data.collectionEvents = [];

    const exists = data.collectionEvents.some(
      l => l.eventId === eventId && l.collectionId === collectionId
    );
    if (!exists) {
      data.collectionEvents.push({ eventId, collectionId });
      saveData(data);
      console.log(`Linked event ${eventId} to collection ${collectionId}`);
    }
  }

  // Owner associated with a collection
  function getCollectionOwnerId(collectionId, data) {
    if (!collectionId) return null;
    if (!data) data = loadData();
    const link = (data.collectionsUsers || []).find(entry => entry.collectionId === collectionId);
    return link ? link.ownerId : null;
  }

  function getCollectionOwner(collectionId, data) {
    if (!collectionId) return null;
    if (!data) data = loadData();
    const ownerId = getCollectionOwnerId(collectionId, data);
    if (!ownerId) return null;
    const users = data.users || [];
    return users.find(user =>
      user["owner-id"] === ownerId ||
      user.id === ownerId
    ) || null;
  }

  // ============================================================
  // 4. Basic CRUD helpers
  // ============================================================
  function addEntity(type, entity) {
    const data = loadData();
    data[type].push(entity);
    saveData(data);
  }

  function updateEntity(type, id, newValues) {
    const data = loadData();
    const index = data[type].findIndex(e => e.id === id);
    if (index !== -1) {
      data[type][index] = { ...data[type][index], ...newValues };
      saveData(data);
    }
  }

  function deleteEntity(type, id) {
    const data = loadData();
    data[type] = data[type].filter(e => e.id !== id);

    // If deleting a collection, remove associated relations
    if (type === "collections") {
      data.collectionItems = data.collectionItems.filter(r => r.collectionId !== id);
      data.collectionEvents = data.collectionEvents.filter(r => r.collectionId !== id);
      data.collectionsUsers = data.collectionsUsers?.filter(r => r.collectionId !== id) || [];
    }

    // If deleting an item/event, remove associated links as well
    if (type === "items") {
      data.collectionItems = data.collectionItems.filter(r => r.itemId !== id);
    }
    if (type === "events") {
      data.collectionEvents = data.collectionEvents.filter(r => r.eventId !== id);
    }
    if (type === "users") {
      data.collectionsUsers = data.collectionsUsers?.filter(r => r.ownerId !== id) || [];
    }

    saveData(data);
  }

  // ============================================================
  // 5. Export global API (window.appData)
  // ============================================================
  window.appData = {
    loadData,
    saveData,
    getItemsByCollection,
    getEventsByCollection,
    getCollectionOwnerId,
    getCollectionOwner,
    linkItemToCollection,
    linkEventToCollection,
    addEntity,
    updateEntity,
    deleteEntity
  };
});

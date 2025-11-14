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

  // Events associated with users (attendance + ratings)
  function ensureEventsUsersArray(data) {
    if (!data.eventsUsers) data.eventsUsers = [];
    return data.eventsUsers;
  }

  function getEventUsers(eventId, data) {
    if (!eventId) return [];
    if (!data) data = loadData();
    const entries = ensureEventsUsersArray(data);
    return entries.filter(entry => entry.eventId === eventId);
  }

  function getEventUserEntry(eventId, userId, data) {
    if (!eventId || !userId) return null;
    if (!data) data = loadData();
    const entries = ensureEventsUsersArray(data);
    return entries.find(entry => entry.eventId === eventId && entry.userId === userId) || null;
  }

  function getEventsByUser(userId, data) {
    if (!userId) return [];
    if (!data) data = loadData();
    const entries = ensureEventsUsersArray(data);
    const eventIds = entries
      .filter(entry => entry.userId === userId)
      .map(entry => entry.eventId);
    const unique = Array.from(new Set(eventIds));
    return (data.events || []).filter(event => unique.includes(event.id));
  }

  function getEventRatingSummary(eventId, data) {
    const entries = getEventUsers(eventId, data);
    const ratingEntries = entries.filter(entry => typeof entry.rating === "number");
    if (!ratingEntries.length) {
      return { count: 0, average: null };
    }
    const total = ratingEntries.reduce((sum, entry) => sum + entry.rating, 0);
    return {
      count: ratingEntries.length,
      average: total / ratingEntries.length
    };
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
      data.eventsUsers = data.eventsUsers?.filter(r => r.eventId !== id) || [];
    }
    if (type === "users") {
      data.collectionsUsers = data.collectionsUsers?.filter(r => r.ownerId !== id) || [];
      data.eventsUsers = data.eventsUsers?.filter(r => r.userId !== id) || [];
    }

    saveData(data);
  }

  function migrateEventsUsersStructure() {
    const data = loadData();
    if (!data) return;
    if (!Array.isArray(data.eventsUsers)) {
      data.eventsUsers = [];
    }

    let mutated = false;
    const existingKey = new Set(
      data.eventsUsers.map(entry => `${entry.eventId}::${entry.userId}`)
    );

    (data.events || []).forEach(event => {
      if (Array.isArray(event.attendees) && event.attendees.length) {
        event.attendees.forEach(userId => {
          const key = `${event.id}::${userId}`;
          if (!existingKey.has(key)) {
            data.eventsUsers.push({
              eventId: event.id,
              userId,
              rating: typeof event.ratings?.[userId] === "number"
                ? event.ratings[userId]
                : null
            });
            existingKey.add(key);
            mutated = true;
          }
        });
      }

      if (event.ratings && typeof event.ratings === "object") {
        Object.entries(event.ratings).forEach(([userId, ratingValue]) => {
          const key = `${event.id}::${userId}`;
          if (!existingKey.has(key)) {
            data.eventsUsers.push({
              eventId: event.id,
              userId,
              rating: typeof ratingValue === "number" ? ratingValue : null
            });
            existingKey.add(key);
            mutated = true;
          } else {
            const entry = data.eventsUsers.find(
              link => link.eventId === event.id && link.userId === userId
            );
            if (entry && entry.rating !== ratingValue && typeof ratingValue === "number") {
              entry.rating = ratingValue;
              mutated = true;
            }
          }
        });
      }

      if ("attendees" in event) {
        delete event.attendees;
        mutated = true;
      }
      if ("ratings" in event) {
        delete event.ratings;
        mutated = true;
      }
    });

    if (mutated) {
      saveData(data);
    }
  }

  migrateEventsUsersStructure();

  // ============================================================
  // 5. Export global API (window.appData)
  // ============================================================
  window.appData = {
    loadData,
    saveData,
    getItemsByCollection,
    getEventsByCollection,
    getEventUsers,
    getEventUserEntry,
    getEventsByUser,
    getEventRatingSummary,
    getCollectionOwnerId,
    getCollectionOwner,
    linkItemToCollection,
    linkEventToCollection,
    addEntity,
    updateEntity,
    deleteEntity
  };
});

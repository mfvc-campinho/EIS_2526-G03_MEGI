// ===============================================
// File: public_html/JS/app-data.js
// Purpose: Provide a lightweight data API for the demo app — load/save demo data to localStorage and helpers for relations (items/events/collections/users).
// Major blocks: initialization, utility functions (load/save), relation helpers, CRUD helpers, export (window.appData).
// Notes: appData is exported to window and used by page scripts (app-collections.js, app-events.js, app-items.js).
// ===============================================

// Simple toast/notification helper available globally
(function () {
  if (window.notify) return;
  const CSS = `
  .app-toast-container{position:fixed;z-index:99999;right:16px;bottom:16px;display:flex;flex-direction:column;gap:8px;align-items:flex-end}
  .app-toast{background:#222;color:#fff;padding:10px 14px;border-radius:6px;box-shadow:0 6px 18px rgba(0,0,0,.2);opacity:0;transform:translateY(8px);transition:all .22s ease;max-width:320px}
  .app-toast.show{opacity:1;transform:translateY(0)}
  .app-toast.success{background:#198754}
  .app-toast.error{background:#dc3545}
  .app-toast.warning{background:#ffc107;color:#000}
  .app-toast.info{background:#0d6efd}
  `;
  try {
    const style = document.createElement('style'); style.textContent = CSS; document.head && document.head.appendChild(style);
  } catch (e) { }
  let container = null;
  function ensureContainer() {
    if (container) return container;
    container = document.querySelector('.app-toast-container');
    if (!container) {
      container = document.createElement('div');
      container.className = 'app-toast-container';
      document.body.appendChild(container);
    }
    return container;
  }
  window.notify = function (message, type = 'info', timeout = 4000) {
    try {
      const cont = ensureContainer();
      const toast = document.createElement('div');
      toast.className = 'app-toast ' + (type || 'info');
      toast.textContent = message;
      cont.appendChild(toast);
      // show
      requestAnimationFrame(() => { toast.classList.add('show'); });
      const remove = () => { try { toast.classList.remove('show'); setTimeout(() => toast.remove(), 220); } catch (e) { } };
      const t = setTimeout(remove, timeout || 4000);
      toast.addEventListener('click', () => { clearTimeout(t); remove(); });
      return { dismiss: remove };
    } catch (e) { console.error('notify() failed', e); }
  };
})();

document.addEventListener(DOMContentLoaded, () => {
  // ============================================================
  // 1. Initialization
  // - Import server data (pre-rendered by PHP) into localStorage if present
  // ============================================================
  try {
    const preloaded = window.SERVER_APP_DATA;
    if (preloaded && typeof preloaded === 'object') {
      localStorage.setItem('collectionsData', JSON.stringify(preloaded));
      try { window.dispatchEvent(new CustomEvent('appDataRefreshed')); } catch (e) { /* ignore */ }
    } else {
      console.warn('app-data: no SERVER_APP_DATA found; falling back to existing localStorage');
    }
  } catch (err) {
    console.error('app-data: unable to apply PHP bootstrap data', err);
  }
  // 2. Utility functions
  // ============================================================
  function loadData() {
    try {
      const raw = localStorage.getItem("collectionsData");
      const parsed = raw ? JSON.parse(raw) : null;
      return parsed && typeof parsed === "object" ? parsed : {};
    } catch (e) {
      console.warn("loadData: failed to parse collectionsData", e);
      return {};
    }
  }

  function saveData(data) {
    localStorage.setItem("collectionsData", JSON.stringify(data));
  }

  function ensureUserShowcasesArray(data) {
    if (!Array.isArray(data.userShowcases)) {
      data.userShowcases = [];
    }
    return data.userShowcases;
  }

  function findOrCreateUserShowcase(data, ownerId) {
    if (!ownerId) return null;
    const userShowcases = ensureUserShowcasesArray(data);
    let userShowcase = userShowcases.find(us => us.ownerId === ownerId);
    if (!userShowcase) {
      userShowcase = {
        ownerId,
        picks: [],
        likes: [],
        likedItems: [],
        likedEvents: []
      };
      userShowcases.push(userShowcase);
    }
    return userShowcase;
  }

  function ensureUserFollowsMap(data) {
    if (!data.userFollows || typeof data.userFollows !== "object" || Array.isArray(data.userFollows)) {
      data.userFollows = {};
    }
    return data.userFollows;
  }

  function dispatchFollowChangeEvent(detail) {
    if (typeof window?.CustomEvent !== "function") return;
    window.dispatchEvent(new CustomEvent("userFollowChange", { detail }));
  }

  function setUserItemLike(ownerId, itemId, isLiked) {
    if (!ownerId || !itemId) return;
    const data = loadData();
    const showcase = findOrCreateUserShowcase(data, ownerId);
    if (showcase) {
      const liked = new Set(Array.isArray(showcase.likedItems) ? showcase.likedItems : []);
      if (isLiked) liked.add(itemId);
      else liked.delete(itemId);
      showcase.likedItems = Array.from(liked);
      saveData(data);
      window.SERVER_APP_DATA = data;
    }
    // Persist directly to server (PHP) without re-fetching the dataset over JS
    try {
      fetch('../PHP/crud/ratings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        credentials: 'same-origin',
        body: new URLSearchParams({ action: isLiked ? 'likeItem' : 'unlikeItem', itemId })
      }).catch(e => console.warn('ratings: network error', e));
    } catch (e) {
      console.warn('ratings: unable to reach PHP endpoint', e);
    }
  }

  function setUserEventLike(ownerId, eventId, isLiked) {
    if (!ownerId || !eventId) return;
    const data = loadData();
    const showcase = findOrCreateUserShowcase(data, ownerId);
    if (showcase) {
      const liked = new Set(Array.isArray(showcase.likedEvents) ? showcase.likedEvents : []);
      if (isLiked) liked.add(eventId);
      else liked.delete(eventId);
      showcase.likedEvents = Array.from(liked);
      saveData(data);
      window.SERVER_APP_DATA = data;
    }
    try {
      fetch('../PHP/crud/ratings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        credentials: 'same-origin',
        body: new URLSearchParams({ action: isLiked ? 'likeEvent' : 'unlikeEvent', eventId })
      }).catch(e => console.warn('ratings: network error', e));
    } catch (e) {
      console.warn('ratings: unable to reach PHP endpoint', e);
    }
  }

  // Like/unlike a collection for a user (mirror of items/events)
  function setUserCollectionLike(ownerId, collectionId, isLiked) {
    if (!ownerId || !collectionId) return;
    const data = loadData();
    const showcase = findOrCreateUserShowcase(data, ownerId);
    if (showcase) {
      const liked = new Set(Array.isArray(showcase.likes) ? showcase.likes : []);
      if (isLiked) liked.add(collectionId);
      else liked.delete(collectionId);
      showcase.likes = Array.from(liked);
      saveData(data);
      window.SERVER_APP_DATA = data;
    }
    try {
      fetch('../PHP/crud/ratings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        credentials: 'same-origin',
        body: new URLSearchParams({ action: isLiked ? 'likeCollection' : 'unlikeCollection', collectionId })
      }).catch(e => console.warn('ratings: network error', e));
    } catch (e) {
      console.warn('ratings: unable to reach PHP endpoint', e);
    }
  }

  function getUserFollowing(followerId) {
    if (!followerId) return [];
    const data = loadData();
    if (!data) return [];
    const followsMap = ensureUserFollowsMap(data);
    const follows = followsMap[followerId];
    return Array.isArray(follows) ? [...follows] : [];
  }

  function isUserFollowing(followerId, targetOwnerId) {
    if (!followerId || !targetOwnerId) return false;
    return getUserFollowing(followerId).includes(targetOwnerId);
  }

  function toggleUserFollow(followerId, targetOwnerId) {
    // New behavior: perform optimistic local update and persist server-side.
    // Returns a Promise that resolves to `true` when following, `false` when unfollowed.
    return new Promise((resolve) => {
      if (!followerId || !targetOwnerId || followerId === targetOwnerId) return resolve(false);
      const data = loadData();
      if (!data) return resolve(false);
      const followsMap = ensureUserFollowsMap(data);
      const existingFollows = Array.isArray(followsMap[followerId]) ? [...followsMap[followerId]] : [];
      const followIndex = existingFollows.indexOf(targetOwnerId);
      const willFollow = followIndex === -1;

      // Apply optimistic change locally
      if (willFollow) {
        existingFollows.push(targetOwnerId);
      } else {
        existingFollows.splice(followIndex, 1);
      }
      followsMap[followerId] = existingFollows;
      saveData(data);
      dispatchFollowChangeEvent({ followerId, targetOwnerId, following: willFollow });

      // Persist to server
      try {
        fetch('../PHP/crud/follows.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ action: willFollow ? 'follow' : 'unfollow', targetId: targetOwnerId })
        })
          .then(resp => {
            if (resp.status === 401) {
              // revert optimistic change
              const d = loadData();
              const map = ensureUserFollowsMap(d);
              const cur = Array.isArray(map[followerId]) ? [...map[followerId]] : [];
              if (willFollow) {
                const idx = cur.indexOf(targetOwnerId);
                if (idx !== -1) cur.splice(idx, 1);
              } else {
                if (!cur.includes(targetOwnerId)) cur.push(targetOwnerId);
              }
              map[followerId] = cur;
              saveData(d);
              dispatchFollowChangeEvent({ followerId, targetOwnerId, following: !willFollow });
              notify('Please sign in to follow collectors.', 'warning');
              resolve(!willFollow);
              return null;
            }
            return resp.json().catch(() => null);
          })
          .then(json => {
            if (!json) return resolve(willFollow);
            if (json.success) {
              resolve(willFollow);
            } else {
              // revert optimistic change on server error
              const d = loadData();
              const map = ensureUserFollowsMap(d);
              const cur = Array.isArray(map[followerId]) ? [...map[followerId]] : [];
              if (willFollow) {
                const idx = cur.indexOf(targetOwnerId);
                if (idx !== -1) cur.splice(idx, 1);
              } else {
                if (!cur.includes(targetOwnerId)) cur.push(targetOwnerId);
              }
              map[followerId] = cur;
              saveData(d);
              dispatchFollowChangeEvent({ followerId, targetOwnerId, following: !willFollow });
              console.warn('follows: server error', json);
              resolve(!willFollow);
            }
          })
          .catch(err => {
            console.warn('follows: network error', err);
            // keep optimistic state (best-effort)
            resolve(willFollow);
          });
      } catch (e) {
        console.warn('follows: unable to POST', e);
        resolve(willFollow);
      }
    });
  }

  function getUserFollowers(ownerId) {
    if (!ownerId) return [];
    const data = loadData();
    if (!data) return [];
    const followsMap = ensureUserFollowsMap(data);
    return Object.entries(followsMap)
      .filter(([, followingList]) => Array.isArray(followingList) && followingList.includes(ownerId))
      .map(([followerId]) => followerId);
  }

  function getUserFollowerCount(ownerId) {
    return getUserFollowers(ownerId).length;
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

  function getEventCollectionIds(eventId, data) {
    if (!eventId) return [];
    const dataset = data || loadData();
    const links = dataset.collectionEvents || [];
    return Array.from(new Set(
      links
        .filter(link => link.eventId === eventId)
        .map(link => link.collectionId)
        .filter(Boolean)
    ));
  }

  function setEventCollections(eventId, collectionIds = []) {
    if (!eventId) return;
    const data = loadData();
    if (!data.collectionEvents) data.collectionEvents = [];

    const normalizedIds = Array.isArray(collectionIds)
      ? Array.from(new Set(collectionIds.filter(Boolean)))
      : [];

    data.collectionEvents = data.collectionEvents.filter(link => link.eventId !== eventId);
    normalizedIds.forEach(collectionId => {
      data.collectionEvents.push({ eventId, collectionId });
    });
    saveData(data);
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
    return users.find(user => {
      const uid = String(user?.id || user?.user_id || user?.['owner-id'] || '');
      const uname = String(user?.['owner-name'] || user?.user_name || user?.['user_name'] || '');
      return uid === ownerId || uname === ownerId;
    }) || null;
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

  // ============================================================
  // 4. Demo rating helpers (items & collections)
  // ============================================================
  const RATING_STORAGE_KEY = "gc-demo-product-ratings";
  const ratingSeedCache = {};
  let ratingStore = loadRatingStore();

  function loadRatingStore() {
    try {
      const raw = sessionStorage.getItem(RATING_STORAGE_KEY);
      return raw ? JSON.parse(raw) : {};
    } catch (err) {
      console.warn("Unable to load rating session store", err);
      return {};
    }
  }

  function persistRatingStore() {
    try {
      sessionStorage.setItem(RATING_STORAGE_KEY, JSON.stringify(ratingStore));
    } catch (err) {
      console.warn("Unable to persist rating session store", err);
    }
  }

  function normalizeProductType(type) {
    if (!type) return "item";
    const trimmed = String(type).toLowerCase();
    if (trimmed.startsWith("col")) return "collection";
    if (trimmed.startsWith("even")) return "event";
    return "item";
  }

  function getRatingBucket(ownerId, type) {
    if (!ownerId) return null;
    const normalizedType = normalizeProductType(type);
    if (!ratingStore[ownerId]) ratingStore[ownerId] = {};
    if (!ratingStore[ownerId][normalizedType]) {
      ratingStore[ownerId][normalizedType] = {};
    }
    return ratingStore[ownerId][normalizedType];
  }

  function computeSeededRating(type, id) {
    const normalizedType = normalizeProductType(type);
    const key = `${normalizedType}::${id || "unknown"}`;
    if (ratingSeedCache[key]) return ratingSeedCache[key];

    let hash = 0;
    for (let i = 0; i < key.length; i += 1) {
      hash = (hash * 31 + key.charCodeAt(i)) % 1000003;
    }
    const baseAverage = 3 + (hash % 20) / 10; // 3.0 - 4.9
    const count = 8 + (hash % 32); // 8 - 39 ratings
    const result = {
      average: Number(baseAverage.toFixed(1)),
      count
    };
    ratingSeedCache[key] = result;
    return result;
  }

  function clampRating(value) {
    if (typeof value !== "number" || Number.isNaN(value)) return null;
    return Math.min(5, Math.max(1, Math.round(value)));
  }

  function getStoredProductRating(type, id, options = {}) {
    const ownerId = options.ownerId || null;
    if (!ownerId) return null;
    const bucket = getRatingBucket(ownerId, type);
    if (!bucket) return null;
    const stored = bucket[id];
    return typeof stored === "number" ? clampRating(stored) : null;
  }

  function getProductRatingSnapshot(type, id, options = {}) {
    const ownerId = options.ownerId || null;
    const normalizedType = normalizeProductType(type);
    const baseline = computeSeededRating(normalizedType, id);
    const storedRating = getStoredProductRating(normalizedType, id, { ownerId });
    let count = baseline.count;
    let average = baseline.average;

    if (typeof storedRating === "number") {
      average = ((baseline.average * baseline.count) + storedRating) / (baseline.count + 1);
      count = baseline.count + 1;
    }

    return {
      type: normalizedType,
      id,
      average,
      count,
      baseAverage: baseline.average,
      baseCount: baseline.count,
      userRating: storedRating,
      ownerId,
      canRate: Boolean(ownerId)
    };
  }

  function setProductRating(type, id, value, options = {}) {
    const ownerId = options.ownerId || null;
    const normalizedType = normalizeProductType(type);
    const ratingValue = clampRating(value);
    if (!ownerId || ratingValue === null) return null;
    const bucket = getRatingBucket(ownerId, normalizedType);
    if (!bucket) return null;
    bucket[id] = ratingValue;
    persistRatingStore();
    const snapshot = getProductRatingSnapshot(normalizedType, id, { ownerId });
    try {
      window.dispatchEvent(new CustomEvent("productRatingChange", {
        detail: { type: normalizedType, id, ownerId, rating: ratingValue }
      }));
    } catch (err) {
      console.warn("Unable to dispatch rating change event", err);
    }
    return snapshot;
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
    getEventCollectionIds,
    setEventCollections,
    addEntity,
    updateEntity,
    deleteEntity,
    getProductRatingSnapshot,
    setProductRating,
    getStoredProductRating,
    setUserItemLike,
    setUserEventLike,
    setUserCollectionLike,
    getUserFollowing,
    getUserFollowers,
    getUserFollowerCount,
    isUserFollowing,
    toggleUserFollow
  };
});

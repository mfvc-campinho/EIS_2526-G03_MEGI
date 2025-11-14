// ===============================================
// File: public_html/JS/app-collections.js
// Purpose: Render collection cards across pages and provide interactions (preview, edit, delete, likes, top-pick flow).
// Major blocks: element selectors & page context, user state management, derived maps, rendering logic, global functions, event listeners and initialization.
// Notes: Exposes some globals used by HTML (togglePreview, editCollection, deleteCollection).
// ===============================================

document.addEventListener("DOMContentLoaded", () => {
    function attachCollectionInteractions(collections, data) {
        if (!list) return;
        const map = collections.reduce((acc, col) => {
            acc[col.id] = col;
            return acc;
        }, {});
        list.querySelectorAll(".vote-toggle").forEach(btn => {
            const id = btn.dataset.collectionId;
            btn.addEventListener("click", () => toggleVote(id));
        });
        list.querySelectorAll(".top-pick-btn").forEach(btn => {
            const id = btn.dataset.collectionId;
            btn.addEventListener("click", () => handleTopPickSelection(id, map[id], data));
        });
    }

    function toggleVote(collectionId) {
        if (!isActiveUser) {
            alert("To do that, login.");
            return;
        }
        const ownerId = getEffectiveOwnerId();
        if (!ownerId)
            return;
        const data = lastRenderData || appData.loadData();
        buildLikesMaps(data);
        const collection = data?.collections?.find(c => c.id === collectionId);
        if (!collection)
            return;
        alert("Simulation only: voting here would change the collection's total.");
        const currentState = getEffectiveUserLike(collection, ownerId);
        voteState[collectionId] = !currentState;
        renderCollections(lastRenderCriteria);
        notifyLikesChange(ownerId);
    }

    function startTopPickFlow(ownerId, collectionId, collectionName, dataParam) {
        if (!ownerId || !collectionId)
            return;
        const data = dataParam || appData.loadData();
        if (!data)
            return;
        const entries = getActiveShowcase(ownerId, data);
        const existing = entries.find(entry => entry.collectionId === collectionId);
        if (!existing && entries.length >= MAX_USER_CHOICES) {
            alert(`You already selected ${MAX_USER_CHOICES} collections. Remove one before adding another.`);
            return;
        }
        const defaultOrder = existing?.order || Math.min(MAX_USER_CHOICES, entries.length + 1);
        openTopPickModal({
            collectionName: collectionName || "this collection",
            helperText: existing
                ? `Update the position for "${collectionName || "this collection"}".`
                : `Add "${collectionName || "this collection"}" to your Top ${MAX_USER_CHOICES}.`,
            maxChoices: MAX_USER_CHOICES,
            defaultOrder,
            existingOrder: existing?.order || null,
            onSubmit: order => {
                if (!Number.isInteger(order) || order < 1 || order > MAX_USER_CHOICES) {
                    alert(`Please enter a number between 1 and ${MAX_USER_CHOICES}.`);
                    return;
                }
                const updated = entries.filter(entry => entry.collectionId !== collectionId);
                const conflictIndex = updated.findIndex(entry => entry.order === order);
                if (conflictIndex !== -1) {
                    updated.splice(conflictIndex, 1);
                }
                updated.push({ collectionId, order });
                updated.sort((a, b) => a.order - b.order);
                userChosenState[ownerId] = updated;
                closeTopPickModal();
                renderCollections(lastRenderCriteria);
                notifyShowcaseChange(ownerId);
            },
            onRemove: existing
                ? () => {
                    const filtered = entries.filter(entry => entry.collectionId !== collectionId);
                    userChosenState[ownerId] = filtered;
                    closeTopPickModal();
                    renderCollections(lastRenderCriteria);
                    notifyShowcaseChange(ownerId);
                }
                : null,
        });
    }

    window.demoTopPickFlow = function (options = {}) {
        const { ownerId, collectionId, collectionName } = options;
        const data = options.data || appData.loadData();
        startTopPickFlow(ownerId, collectionId, collectionName, data);
    };

    function handleTopPickSelection(collectionId, collection, data) {
        if (!isActiveUser) {
            return;
        }
        const ownerId = getEffectiveOwnerId();
        if (!ownerId)
            return;
        startTopPickFlow(ownerId, collectionId, collection?.name || "this collection", data);
    }
    // ==========================================================
    // 1. Element selectors and page context
    // ==========================================================
    const list = document.getElementById("collections-list") ||
        document.getElementById("homeCollections") ||
        document.getElementById("user-collections");
    // If there is no collections container on this page, stop early.
    if (!list)
        return;

    const isHomePage = list?.id === "homeCollections";
    const isUserPage = list?.id === "user-collections";
    const paginationControls = Array.from(document.querySelectorAll(`[data-pagination-for="${list.id}"]`));
    const hasPaginationControls = paginationControls.length > 0;
    const defaultPageSize = hasPaginationControls ? readInitialPageSize(paginationControls) : null;
    const paginationState = hasPaginationControls
        ? {
            pageSize: defaultPageSize,
            pageIndex: 0
        }
        : null;

    // Elements that may or may not exist depending on the page
    const filter = document.getElementById("rankingFilter");
    const modal = document.getElementById("collection-modal");
    const form = document.getElementById("form-collection");
    const openBtn = document.getElementById("open-collection-modal");
    const restoreBtn = document.getElementById("restoreDataBtn");

    // ==========================================================
    // 2. User state management
    // ==========================================================
    const DEFAULT_OWNER_ID = "collector-main";
    const MAX_USER_CHOICES = 5;
    let currentUserId;
    let isActiveUser;
    let collectionOwnerMap = {};
    const sessionState = window.demoCollectionsState || (window.demoCollectionsState = {});
    const voteState = sessionState.voteState || (sessionState.voteState = {});
    const userChosenState = sessionState.userChosenState || (sessionState.userChosenState = {});
    let defaultShowcaseMap = {};
    let showcaseInitialized = false;
    let lastRenderCriteria = "lastAdded";
    let lastRenderData = null;
    let topPickModalElements = null;
    let topPickModalHandlers = null;
    let likesByCollectionMap = {};
    let ownerLikesMap = {};

    function notifyShowcaseChange(ownerId) {
        if (!ownerId) return;
        window.dispatchEvent(new CustomEvent("userShowcaseChange", { detail: { ownerId } }));
    }

    function notifyLikesChange(ownerId) {
        if (!ownerId) return;
        window.dispatchEvent(new CustomEvent("userLikesChange", { detail: { ownerId } }));
    }

    function readInitialPageSize(controls) {
        for (const ctrl of controls) {
            const selector = ctrl.querySelector("[data-page-size]");
            if (!selector) continue;
            const parsed = parseInt(selector.value, 10);
            if (!Number.isNaN(parsed) && parsed > 0) {
                return parsed;
            }
        }
        return 10;
    }

    function syncPaginationSelects(value) {
        if (!hasPaginationControls) return;
        paginationControls.forEach(ctrl => {
            const selector = ctrl.querySelector("[data-page-size]");
            if (selector) {
                selector.value = String(value);
            }
        });
    }

    function updatePaginationSummary(total, startIndex = 0, shown = 0) {
        if (!hasPaginationControls) return;
        const cappedTotal = Math.max(total || 0, 0);
        const cappedShown = Math.max(Math.min(shown || 0, cappedTotal), 0);
        const cappedStart = cappedTotal === 0 ? 0 : Math.min(Math.max(startIndex || 0, 0), Math.max(cappedTotal - 1, 0));
        const rangeStart = cappedTotal === 0 || cappedShown === 0 ? 0 : cappedStart + 1;
        const rangeEnd = cappedTotal === 0 || cappedShown === 0 ? 0 : cappedStart + cappedShown;
        const effectivePageSize = paginationState ? Math.max(paginationState.pageSize || defaultPageSize || 1, 1) : 1;
        const totalPages = cappedTotal === 0 ? 0 : Math.ceil(cappedTotal / effectivePageSize);
        const currentPage = paginationState ? paginationState.pageIndex : 0;
        const atStart = !cappedTotal || currentPage <= 0;
        const atEnd = !cappedTotal || currentPage >= Math.max(totalPages - 1, 0);
        paginationControls.forEach(ctrl => {
            const status = ctrl.querySelector("[data-pagination-status]");
            if (status) {
                status.textContent = `Mostrando ${rangeStart}-${rangeEnd} de ${cappedTotal}`;
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

    function initializePaginationControls() {
        if (!hasPaginationControls || !paginationState) return;
        syncPaginationSelects(paginationState.pageSize);
        paginationControls.forEach(ctrl => {
            const selector = ctrl.querySelector("[data-page-size]");
            if (selector) {
                selector.addEventListener("change", event => {
                    const next = parseInt(event.target.value, 10);
                    if (Number.isNaN(next) || next <= 0) return;
                    paginationState.pageSize = next;
                    paginationState.pageIndex = 0;
                    syncPaginationSelects(next);
                    renderCollections(lastRenderCriteria);
                });
            }
            const prevBtn = ctrl.querySelector("[data-page-prev]");
            if (prevBtn) {
                prevBtn.addEventListener("click", () => {
                    if (paginationState.pageIndex > 0) {
                        paginationState.pageIndex -= 1;
                        renderCollections(lastRenderCriteria);
                    }
                });
            }
            const nextBtn = ctrl.querySelector("[data-page-next]");
            if (nextBtn) {
                nextBtn.addEventListener("click", () => {
                    paginationState.pageIndex += 1;
                    renderCollections(lastRenderCriteria);
                });
            }
        });
    }

    function buildLikesMaps(data) {
        likesByCollectionMap = {};
        ownerLikesMap = {};
        (data?.userShowcases || []).forEach(entry => {
            const owner = entry.ownerId;
            const likes = entry.likes || entry.likedCollections || [];
            ownerLikesMap[owner] = new Set(likes);
            likes.forEach(colId => {
                if (!likesByCollectionMap[colId]) likesByCollectionMap[colId] = new Set();
                likesByCollectionMap[colId].add(owner);
            });
        });
    }

    function getCollectionLikedBy(collection) {
        if (!collection) return new Set();
        const set = likesByCollectionMap[collection.id];
        return set ? new Set(set) : new Set();
    }

    function getVoteOverride(collectionId) {
        return Object.prototype.hasOwnProperty.call(voteState, collectionId)
            ? voteState[collectionId]
            : undefined;
    }

    function getUserBaseLike(collection, userId) {
        if (!userId || !collection) return false;
        const likedSet = ownerLikesMap[userId];
        return likedSet ? likedSet.has(collection.id) : false;
    }

    function getEffectiveUserLike(collection, userId) {
        if (!userId || !collection) return false;
        const override = getVoteOverride(collection.id);
        if (override === undefined) return getUserBaseLike(collection, userId);
        return override;
    }

    function getDisplayLikes(collection, userId) {
        const likedSet = getCollectionLikedBy(collection);
        if (userId && collection) {
            const override = getVoteOverride(collection.id);
            const baseHas = likedSet.has(userId);
            const finalState = override === undefined ? baseHas : override;
            if (finalState) likedSet.add(userId);
            else likedSet.delete(userId);
        }
        return likedSet.size;
    }

    function ensureTopPickModal() {
        if (topPickModalElements) return;
        const backdrop = document.createElement("div");
        backdrop.className = "top-pick-modal-backdrop";
        backdrop.innerHTML = `
      <div class="top-pick-modal" role="dialog" aria-modal="true">
        <form class="top-pick-form">
          <h3>Select a position</h3>
          <p class="top-pick-message">Choose where this collection should appear in your Top 5.</p>
          <label for="top-pick-select">Position</label>
          <select id="top-pick-select"></select>
          <div class="top-pick-actions">
            <button type="submit" class="primary">Save</button>
            <button type="button" class="remove-btn">Remove from Top 5</button>
            <button type="button" class="ghost cancel-btn">Cancel</button>
          </div>
        </form>
      </div>
    `;
        document.body.appendChild(backdrop);
        const modal = backdrop.querySelector(".top-pick-modal");
        const form = modal.querySelector(".top-pick-form");
        const message = modal.querySelector(".top-pick-message");
        const select = modal.querySelector("#top-pick-select");
        const removeBtn = modal.querySelector(".remove-btn");
        const cancelBtn = modal.querySelector(".cancel-btn");
        topPickModalElements = {
            backdrop,
            modal,
            form,
            message,
            select,
            removeBtn,
            cancelBtn,
        };
        form.addEventListener("submit", e => {
            e.preventDefault();
            if (!topPickModalHandlers?.onSubmit) return;
            const order = Number(select.value);
            topPickModalHandlers.onSubmit(order);
        });
        removeBtn.addEventListener("click", () => {
            if (topPickModalHandlers?.onRemove) {
                topPickModalHandlers.onRemove();
            }
        });
        cancelBtn.addEventListener("click", () => closeTopPickModal());
        backdrop.addEventListener("click", e => {
            if (e.target === backdrop) closeTopPickModal();
        });
        window.addEventListener("keydown", e => {
            if (e.key === "Escape" && backdrop.classList.contains("visible")) {
                closeTopPickModal();
            }
        });
    }

    function openTopPickModal(options) {
        ensureTopPickModal();
        const { backdrop, select, message, removeBtn } = topPickModalElements;
        const { collectionName, helperText, maxChoices, defaultOrder, existingOrder, onSubmit, onRemove } = options;
        message.textContent = helperText || `Choose where "${collectionName}" should appear (1 is the top).`;
        select.innerHTML = "";
        for (let i = 1; i <= maxChoices; i++) {
            const option = document.createElement("option");
            option.value = String(i);
            option.textContent = `#${i}`;
            select.appendChild(option);
        }
        select.value = String(defaultOrder);
        removeBtn.style.display = existingOrder ? "inline-flex" : "none";
        removeBtn.disabled = !onRemove;
        topPickModalHandlers = { onSubmit, onRemove };
        backdrop.classList.add("visible");
        select.focus();
    }

    function closeTopPickModal() {
        if (!topPickModalElements) return;
        topPickModalElements.backdrop.classList.remove("visible");
        topPickModalHandlers = null;
    }

    function hydrateCollectionOwnerMap(data) {
        collectionOwnerMap = (data?.collectionsUsers || []).reduce((acc, link) => {
            acc[link.collectionId] = link.ownerId;
            return acc;
        }, {});
    }

    function getCollectionOwnerIdCached(collectionId, data) {
        if (!collectionId)
            return null;
        if (collectionOwnerMap[collectionId])
            return collectionOwnerMap[collectionId];
        if (data?.collectionsUsers) {
            const link = data.collectionsUsers.find(entry => entry.collectionId === collectionId);
            if (link) {
                collectionOwnerMap[collectionId] = link.ownerId;
                return link.ownerId;
            }
        }
        const ownerId = appData.getCollectionOwnerId(collectionId, data);
        if (ownerId) {
            collectionOwnerMap[collectionId] = ownerId;
        }
        return ownerId;
    }

    function resolveUserPageOwnerId() {
        try {
            const params = new URLSearchParams(window.location.search);
            const ownerFromQuery = params.get("owner");
            if (ownerFromQuery)
                return ownerFromQuery;
        }
        catch (_a) {
            // Ignore malformed query strings
        }
        if (currentUserId)
            return currentUserId;
        return DEFAULT_OWNER_ID;
    }

    function updateUserState() {
        const userData = JSON.parse(localStorage.getItem("currentUser"));
        currentUserId = userData ? userData.id : null;
        isActiveUser = Boolean(userData && userData.active);
    }

    function getEffectiveOwnerId() {
        if (!isActiveUser)
            return null;
        return currentUserId || DEFAULT_OWNER_ID;
    }

    function isCollectionOwnedByCurrentUser(collection, data) {
        const ownerId = getEffectiveOwnerId();
        if (!ownerId || !collection)
            return false;
        const collectionOwnerId = getCollectionOwnerIdCached(collection.id, data);
        return Boolean(collectionOwnerId && collectionOwnerId === ownerId);
    }

    function ensureDefaultShowcases(data) {
        if (showcaseInitialized) return;
        (data?.userShowcases || []).forEach(entry => {
            defaultShowcaseMap[entry.ownerId] = (entry.picks || []).slice().sort((a, b) => a.order - b.order);
        });
        showcaseInitialized = true;
    }

    function getActiveShowcase(ownerId, data) {
        if (!ownerId) return [];
        if (userChosenState[ownerId] && userChosenState[ownerId].length) {
            return userChosenState[ownerId];
        }
        ensureDefaultShowcases(data);
        return (defaultShowcaseMap[ownerId] || []).slice();
    }

    function getUserChosenOrderForCollection(collectionId, ownerIdOverride, data) {
        const ownerId = ownerIdOverride || getEffectiveOwnerId();
        if (!ownerId)
            return null;
        const entries = getActiveShowcase(ownerId, data);
        const match = entries.find(entry => entry.collectionId === collectionId);
        return match ? match.order : null;
    }

    function getTimestamp(value) {
        if (!value)
            return null;
        const date = new Date(value);
        const time = date.getTime();
        return Number.isNaN(time) ? null : time;
    }

    function buildCollectionDerivedMaps(data) {
        const latestMap = {};
        const itemsByCollection = (data.collectionItems || []).reduce((acc, link) => {
            acc[link.collectionId] = acc[link.collectionId] || [];
            acc[link.collectionId].push(link.itemId);
            return acc;
        }, {});
        const eventsByCollection = (data.collectionEvents || []).reduce((acc, link) => {
            acc[link.collectionId] = acc[link.collectionId] || [];
            acc[link.collectionId].push(link.eventId);
            return acc;
        }, {});
        const itemsMap = (data.items || []).reduce((acc, item) => {
            acc[item.id] = item;
            return acc;
        }, {});
        const eventsMap = (data.events || []).reduce((acc, event) => {
            acc[event.id] = event;
            return acc;
        }, {});

        (data.collections || []).forEach(col => {
            let latest = getTimestamp(col.metrics?.lastUpdated) ?? getTimestamp(col.createdAt);
            (itemsByCollection[col.id] || []).forEach(itemId => {
                const item = itemsMap[itemId];
                if (!item)
                    return;
                const ts = getTimestamp(item.updatedAt) ?? getTimestamp(item.createdAt) ?? getTimestamp(item.acquisitionDate);
                if (ts && (!latest || ts > latest))
                    latest = ts;
            });
            (eventsByCollection[col.id] || []).forEach(eventId => {
                const ev = eventsMap[eventId];
                if (!ev)
                    return;
                const ts = getTimestamp(ev.updatedAt) ?? getTimestamp(ev.createdAt) ?? getTimestamp(ev.date);
                if (ts && (!latest || ts > latest))
                    latest = ts;
            });
            latestMap[col.id] = latest || getTimestamp(col.createdAt) || 0;
        });

        return { latestMap, itemsByCollection, itemsMap };
    }

    // ==========================================================
    // 3. Collections rendering
    // ==========================================================
    function renderCollections(criteria = "lastAdded", limitOverride) {
        const data = appData.loadData();
        lastRenderData = data;
        ensureDefaultShowcases(data);
        hydrateCollectionOwnerMap(data);
        buildLikesMaps(data);
        let collections = data.collections || [];
        lastRenderCriteria = criteria;
        if (isUserPage) {
            const ownerId = resolveUserPageOwnerId();
            if (!ownerId) {
                list.innerHTML = `<p class="notice-message">No collections found for this user.</p>`;
                return;
            }
            collections = collections.filter(c => getCollectionOwnerIdCached(c.id, data) === ownerId);
        }

        const { latestMap, itemsByCollection, itemsMap } = buildCollectionDerivedMaps(data);

        if (criteria === "lastAdded") {
            collections.sort((a, b) => (latestMap[b.id] || 0) - (latestMap[a.id] || 0));
        } else if (criteria === "userChosen") {
            if (!isActiveUser) {
                list.innerHTML = `<p class="notice-message">Log in to view your curated picks.</p>`;
                return;
            }
            const ownerId = getEffectiveOwnerId();
            const ownerEntries = ownerId ? getActiveShowcase(ownerId, data) : [];
            if (!ownerEntries.length) {
                list.innerHTML = `<p class="notice-message">You haven't selected any collections for your Top 5 yet.</p>`;
                return;
            }
            const orderMap = ownerEntries.reduce((acc, entry) => {
                acc[entry.collectionId] = entry.order;
                return acc;
            }, {});
            collections = collections.filter(c => orderMap[c.id]).sort((a, b) => orderMap[a.id] - orderMap[b.id]);
        } else if (criteria === "itemCount") {
            const itemCounts = data.collectionItems.reduce((acc, link) => {
                acc[link.collectionId] = (acc[link.collectionId] || 0) + 1;
                return acc;
            }, {});
            collections.sort((a, b) => (itemCounts[b.id] || 0) - (itemCounts[a.id] || 0));
        }

        const totalAvailable = collections.length;
        const limitOverridden = typeof limitOverride === "number" && limitOverride > 0;
        let startIndex = 0;
        let sliceSize = limitOverridden
            ? limitOverride
            : totalAvailable;
        const usingPagination = hasPaginationControls && paginationState && !limitOverridden;

        if (usingPagination) {
            const effectiveSize = Math.max(paginationState.pageSize || defaultPageSize || 1, 1);
            paginationState.pageSize = effectiveSize;
            const totalPages = effectiveSize > 0 ? Math.ceil((totalAvailable || 0) / effectiveSize) : 0;
            if (totalPages === 0) {
                paginationState.pageIndex = 0;
            } else if (paginationState.pageIndex >= totalPages) {
                paginationState.pageIndex = totalPages - 1;
            } else if (paginationState.pageIndex < 0) {
                paginationState.pageIndex = 0;
            }
            startIndex = paginationState.pageIndex * effectiveSize;
            sliceSize = effectiveSize;
        }

        const endIndex = sliceSize > 0 ? startIndex + sliceSize : startIndex;
        collections = collections.slice(startIndex, endIndex);
        updatePaginationSummary(totalAvailable, startIndex, collections.length);

        if (!collections.length) {
            list.innerHTML = `<p class="notice-message">No collections found.</p>`;
            return;
        }

        const ownerIdForDisplay = getEffectiveOwnerId();
        const cardsHTML = collections.map(col => {
            const itemIds = itemsByCollection[col.id] || [];
            const items = itemIds.slice(0, 2).map(id => itemsMap[id]).filter(Boolean);
            const itemsHTML = items.length
                ? `<ul class="mini-item-list">${items.map(it => `
                    <li>
                      <a class="mini-item-link" href="item_page.html?id=${encodeURIComponent(it.id || "")}">
                        <img src="${it.image}" alt="${it.name}" class="mini-item-img" loading="lazy">
                        <span>${it.name}</span>
                      </a>
                    </li>`).join("")}</ul>`
                : `<p class="no-items">No items yet.</p>`;

            const isOwnerLoggedIn = isCollectionOwnedByCurrentUser(col, data);
            const specialClass = isOwnerLoggedIn ? "collector-owned" : "";
            const canEdit = isOwnerLoggedIn;
            const displayVotes = getDisplayLikes(col, ownerIdForDisplay);
            const userPickOrder = getUserChosenOrderForCollection(col.id);
            const isStarred = ownerIdForDisplay ? getEffectiveUserLike(col, ownerIdForDisplay) : false;
            const pickLabel = userPickOrder ? `My pick #${userPickOrder}` : "Add to My Top 5";
            const topPickBtn = isActiveUser
                ? `<button class="metric-btn top-pick-btn ${userPickOrder ? "active" : ""}" data-collection-id="${col.id}">
                <i class="bi bi-award"></i>
                <span class="top-pick-label">${pickLabel}</span>
              </button>`
                : "";

            const buttons = `
                <button class="explore-btn" onclick="togglePreview('${col.id}', this)"><i class="bi bi-eye"></i> Show Preview</button>
                <button class="explore-btn" onclick="window.location.href='specific_collection.html?id=${col.id}'"><i class="bi bi-search"></i> Explore More</button>
                ${canEdit ? `<button class="explore-btn warning" onclick="editCollection('${col.id}')"><i class="bi bi-pencil-square"></i> Edit</button>` : ""}
                ${canEdit ? `<button class="explore-btn danger" onclick="deleteCollection('${col.id}')"><i class="bi bi-trash"></i> Delete</button>` : ""}
            `;

            return `
        <div class="card collection-card ${specialClass}">
          <div class="card-image" id="img-${col.id}">
            <img src="${col.coverImage || '../images/default.jpg'}" alt="${col.name}" loading="lazy">
          </div>
          <div class="card-info">
            <h3>${col.name}</h3>
            <p>${col.summary || ""}</p>
            <div class="items-preview" id="preview-${col.id}" style="display:none;">
              ${itemsHTML}
              <div class="collection-preview-meta muted" id="meta-${col.id}">
                <!-- This will be populated by togglePreview -->
              </div>
            </div>
            <div class="collection-metrics">
              <button class="metric-btn vote-toggle ${isStarred ? "active" : ""}" data-collection-id="${col.id}">
                <i class="bi ${isStarred ? "bi-star-fill" : "bi-star"}"></i>
                <span class="vote-count">${displayVotes}</span>
              </button>
              ${topPickBtn}
            </div>
            <div class="card-buttons">${buttons}</div>
          </div>
        </div>`;
        }).join("");

        list.innerHTML = cardsHTML;
        // Attach interaction handlers after DOM insertion
        attachCollectionInteractions(collections, data);
    }// ==========================================================
    // ==========================================================
    // 4. Global functions (accessible from HTML)
    // ==========================================================
    window.togglePreview = (id, btn) => {
        const img = document.getElementById(`img-${id}`);
        const prev = document.getElementById(`preview-${id}`);
        const meta = document.getElementById(`meta-${id}`);
        const isShowingPreview = prev.style.display === "block";

        if (isShowingPreview) {
            // Hide the preview
            prev.style.display = "none";
            img.style.display = "block";
            btn.innerHTML = '<i class="bi bi-eye"></i> Show Preview';
        } else {
            // Show the preview and populate meta-data
            const data = lastRenderData || appData.loadData();
            const col = data.collections.find(c => c.id === id);
            if (!col) return;

            const { latestMap, itemsByCollection } = buildCollectionDerivedMaps(data);
            const itemCount = (itemsByCollection[col.id] || []).length;
            const lastUpdatedDate = latestMap[id] ? new Date(latestMap[id]).toLocaleDateString() : "N/A";

            meta.innerHTML = `
                <span class="meta-item"><i class="bi bi-list-ol me-1"></i> ${itemCount} items</span>
                <span class="meta-item"><i class="bi bi-clock-history me-1"></i> Last updated: ${lastUpdatedDate}</span>`;

            prev.style.display = "block";
            img.style.display = "none";
            btn.innerHTML = '<i class="bi bi-eye-slash"></i> Hide Preview';
        }
    };

    window.editCollection = id => {
        const data = appData.loadData();
        lastRenderData = data;
        ensureDefaultShowcases(data);
        hydrateCollectionOwnerMap(data);
        const col = data.collections.find(c => c.id === id);
        if (!col || !isCollectionOwnedByCurrentUser(col, data))
            return alert("❌ You can only edit your own collections.");

        if (form) {
            form.querySelector("#collection-id").value = col.id;
            form["col-name"].value = col.name;
            form["col-summary"].value = col.summary;
            form["col-image"].value = col.coverImage;
            form["col-type"].value = col.type;
            form["col-description"].value = col.description || "";
            document.getElementById("collection-modal-title").textContent = "Edit Collection";
            modal.style.display = "flex";
        }
    };

    window.deleteCollection = id => {
        const data = appData.loadData();
        lastRenderData = data;
        ensureDefaultShowcases(data);
        hydrateCollectionOwnerMap(data);
        const col = data.collections.find(c => c.id === id);
        if (!col || !isCollectionOwnedByCurrentUser(col, data))
            return alert("❌ You can only delete your own collections.");

        if (confirm(`⚠️ Delete "${col.name}"?\n\n(This is a demonstration. No data will be changed.)`)) {
            alert("✅ Simulation successful. No data was deleted.");
            // appData.deleteEntity("collections", id);
            // alert(`🗑️ Collection "${col.name}" deleted.`);
            // renderCollections(filter ? filter.value : "lastAdded", isHomePage ? 5 : null);
        }
    };

    // ==========================================================
    // 5. Event listeners (existence-checked)
    // ==========================================================

    // Homepage filter
    if (filter) {
        filter.addEventListener("change", e =>
            renderCollections(e.target.value)
        );
    }
    // Collection modal
    if (modal && form) {
        const modalTitle = document.getElementById("collection-modal-title");
        const idField = document.getElementById("collection-id");

        const openModal = (edit = false) => {
            // Ensure the form is cleared before opening
            form.reset();
            idField.value = "";
            modalTitle.textContent = edit ? "Edit Collection" : "New Collection";
            modal.style.display = "flex";
        };
        const closeModal = () => {
            modal.style.display = "none";
            form.reset();
            idField.value = "";
        };

        if (openBtn) {
            openBtn.addEventListener("click", () => {
                if (!isActiveUser)
                    return alert("🚫 You must be logged in to add collections.");
                openModal(false);
            });
        }

        form.addEventListener("submit", e => {
            e.preventDefault();
            const id = idField.value.trim();
            const action = id ? "updated" : "created";

            alert(`✅ Simulation successful. Collection would have been ${action}.\n\n(This is a demonstration. No data was saved.)`);

            closeModal();
            // Rendering is omitted to avoid showing changes that did not actually occur
            // renderCollections(filter ? filter.value : "lastAdded", isHomePage ? 5 : null);
        });

        document.getElementById("close-collection-modal")?.addEventListener("click", closeModal);
        document.getElementById("cancel-collection-modal")?.addEventListener("click", closeModal);
        window.addEventListener("click", e => {
            if (e.target === modal)
                closeModal();
        });
    }

    // Restore Data button
    if (restoreBtn) {
        restoreBtn.addEventListener("click", () => {
            if (confirm("⚠️ Restore initial data? This will delete all current collections and log you out.")) {
                if (typeof collectionsData !== "undefined" && window.appData) {
                    localStorage.removeItem("collectionsData");
                    localStorage.removeItem("currentUser");
                    alert("✅ Data restored successfully! The page will now reload.");
                    location.reload();
                }
            }
        });
    }

    // React to global user state changes
    window.addEventListener("userStateChange", () => {
        updateUserState();
        renderCollections(filter ? filter.value : "lastAdded");
    });

    // ==========================================================
    // 6. Initialization
    // ==========================================================
    initializePaginationControls();
    updateUserState();
    renderCollections("lastAdded");
});

// ===============================================
// File: public_html/JS/app-collections.js
// Purpose: Render collection cards across pages and provide interactions (preview, edit, delete, likes).
// Major blocks: element selectors & page context, user state management, derived maps, rendering logic, global functions, event listeners and initialization.
// Notes: Exposes some globals used by HTML (togglePreview, editCollection, deleteCollection).
// ===============================================

document.addEventListener("DOMContentLoaded", () => {
    function attachCollectionInteractions() {
        if (!list) return;
        list.querySelectorAll(".vote-toggle").forEach(btn => {
            const id = btn.dataset.collectionId;
            btn.addEventListener("click", () => toggleVote(id));
        });
    }

    function toggleVote(collectionId) {
        if (!isActiveUser) {
            notify("Please sign in to like or vote.", "warning");
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
        // Informative notice about prototype behavior
        const currentState = getEffectiveUserLike(collection, ownerId);
        const newState = !currentState;
        voteState[collectionId] = newState;
        // Persist the like/unlike via appData if available
        if (window.appData && typeof window.appData.setUserCollectionLike === "function") {
            try {
                window.appData.setUserCollectionLike(ownerId, collectionId, newState);
            } catch (err) {
                console.warn('setUserCollectionLike failed', err);
            }
        }
        renderCollections(lastRenderCriteria);
        notifyLikesChange(ownerId);
        try {
            notify(newState ? 'Collection liked.' : 'Collection unliked.', newState ? 'success' : 'info');
        } catch (e) { }
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

    // Disable the restore demo-data button in Sprint 2 (server-backed)
    if (restoreBtn) {
        try { restoreBtn.style.display = 'none'; } catch (e) { /* ignore */ }
    }

    // ==========================================================
    // 2. User state management
    // ==========================================================
    const DEFAULT_OWNER_ID = "collector-main";
    let currentUserId;
    let isActiveUser;
    let collectionOwnerMap = {};
    const sessionState = window.demoCollectionsState || (window.demoCollectionsState = {});
    const voteState = sessionState.voteState || (sessionState.voteState = {});
    let lastRenderCriteria = "lastAdded";
    let lastRenderData = null;
    let likesByCollectionMap = {};
    let ownerLikesMap = {};

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
                status.textContent = `Showing ${rangeStart}-${rangeEnd} of ${cappedTotal}`;
            }
            const actions = ctrl.querySelector(".pagination-actions");
            if (actions) {
                actions.hidden = cappedTotal === 0;
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

    // Rating helpers removed

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
                const ts = getTimestamp(ev.updatedAt) ?? getTimestamp(ev.createdAt);
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
        } else if (criteria === "itemCount") {
            const itemCounts = (data.collectionItems || []).reduce((acc, link) => {
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
            const isStarred = ownerIdForDisplay ? getEffectiveUserLike(col, ownerIdForDisplay) : false;

            const buttons = `
                <button class="explore-btn" onclick="togglePreview('${col.id}', this)"><i class="bi bi-eye"></i> Show Preview</button>
                <button class="explore-btn" onclick="window.location.href='specific_collection.html?id=${col.id}'"><i class="bi bi-search"></i> Explore More</button>
                ${canEdit ? `<button class="explore-btn warning" onclick="editCollection('${col.id}')"><i class="bi bi-pencil-square"></i> Edit</button>` : ""}
                ${canEdit ? `<button class="explore-btn danger" onclick="deleteCollection('${col.id}')"><i class="bi bi-trash"></i> Delete</button>` : ""}
            `;

            return `
        <div class="card collection-card ${specialClass}">
                <div class="card-image" id="img-${col.id}">
                        <a href="specific_collection.html?id=${col.id}" aria-label="Open ${col.name}">
                            <img src="${col.coverImage || '../images/default.jpg'}" alt="${col.name}" loading="lazy">
                        </a>
                    </div>
                    <div class="card-info">
                        <h3><a href="specific_collection.html?id=${col.id}">${col.name}</a></h3>
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
            </div>
            <div class="card-buttons">${buttons}</div>
          </div>
        </div>`;
        }).join("");

        list.innerHTML = cardsHTML;
        // Attach interaction handlers after DOM insertion
        attachCollectionInteractions();
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
        hydrateCollectionOwnerMap(data);
        const col = data.collections.find(c => c.id === id);
        if (!col || !isCollectionOwnedByCurrentUser(col, data))
            return notify("You can only edit your own collections.", "error");

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
        hydrateCollectionOwnerMap(data);
        const col = data.collections.find(c => c.id === id);
        if (!col || !isCollectionOwnedByCurrentUser(col, data))
            return notify("You can only delete your own collections.", "error");

        if (confirm(`⚠️ Delete "${col.name}"?\n\n(This action will not change server data in this environment.)`)) {
            notify("No data was deleted.", "info");
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

    function resetFilterOnPageShow() {
        if (!isHomePage || !filter) return;
        window.addEventListener("pageshow", function () {
            setTimeout(function () {
                if (filter.value !== "lastAdded") {
                    filter.value = "lastAdded";
                    filter.dispatchEvent(new Event("change"));
                }
            }, 10);
        });
    }
    resetFilterOnPageShow();
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
                    return notify("Please sign in to add collections.", "warning");
                openModal(false);
            });
        }

        form.addEventListener("submit", async e => {
            e.preventDefault();
            const id = idField.value.trim();
            const formData = new FormData(form);
            const payload = new FormData();
            const isUpdate = Boolean(id);
            payload.append('action', isUpdate ? 'update' : 'create');
            if (isUpdate) payload.append('id', id);
            payload.append('name', formData.get('col-name') || '');
            payload.append('summary', formData.get('col-summary') || '');
            payload.append('description', formData.get('col-description') || '');
            payload.append('image', formData.get('col-image') || '');
            payload.append('type', formData.get('col-type') || '');

            try {
                const res = await fetch('../PHP/crud/collections.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: payload
                });
                const json = await res.json();
                if (json && json.success) {
                    // Refresh client cache by reloading server-side dataset
                    try {
                        const r2 = await fetch('../PHP/get_all.php');
                        if (r2.ok) {
                            const serverData = await r2.json();
                            localStorage.setItem('collectionsData', JSON.stringify(serverData));
                        }
                    } catch (err) {
                        console.warn('Unable to refresh local dataset', err);
                    }

                    closeModal();
                    renderCollections(filter ? filter.value : 'lastAdded', isHomePage ? 5 : null);
                    try { notify(isUpdate ? 'Collection updated successfully.' : 'Collection created successfully.', 'success'); } catch (e) { }
                } else {
                    notify('Error saving collection: ' + (json && json.error ? json.error : 'unknown'), 'error');
                }
            } catch (err) {
                console.error(err);
                notify('Network error while saving collection', 'error');
            }
        });

        document.getElementById("close-collection-modal")?.addEventListener("click", closeModal);
        document.getElementById("cancel-collection-modal")?.addEventListener("click", closeModal);
        window.addEventListener("click", e => {
            if (e.target === modal)
                closeModal();
        });
    }

    // Restore data removed in Sprint 2: no client-side demo seeding.

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

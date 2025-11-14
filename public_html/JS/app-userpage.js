// ===============================================
// app-userpage.js - Profile page logic
// ===============================================

document.addEventListener("DOMContentLoaded", () => {
  // Page elements
  const userNameEl = document.getElementById("user-name");
  const userAvatarEl = document.getElementById("user-avatar");
  const userEmailEl = document.getElementById("user-email");
  const userDobEl = document.getElementById("user-dob");
  const userMemberSinceEl = document.getElementById("user-member-since");
  const userFollowersEl = document.getElementById("user-followers");
  const usernameBannerEl = document.getElementById("username-banner");
  const userEventsContainer = document.getElementById("user-events");
  const userRsvpTitleEl = document.getElementById("user-rsvp-title");
  const userRsvpContainer = document.getElementById("user-rsvp-events");
  const topPicksNoteEl = document.getElementById("top-picks-note");
  const resetTopPicksBtn = document.getElementById("reset-top-picks-btn");
  const topPicksContainer = document.getElementById("user-top-picks");
  const likedCollectionsContainer = document.getElementById("user-liked-collections");
  const likedItemsContainer = document.getElementById("user-liked-items");
  const likedEventsContainer = document.getElementById("user-liked-events");
  const followUserBtn = document.getElementById("follow-user-btn");
  const userCollectionsTitleEl = document.getElementById("my-collections-title");
  const itemModal = document.getElementById("item-modal");
  const itemForm = document.getElementById("item-form");
  const closeItemModalBtn = document.getElementById("close-modal");
  const cancelItemModalBtn = document.getElementById("cancel-modal");
  const itemCollectionsSelect = document.getElementById("item-collections");
  const addItemProfileBtn = document.getElementById("profile-add-item-btn");
  const addEventProfileBtn = document.getElementById("profile-add-event-btn");

  // Modal elements
  const profileModal = document.getElementById("user-profile-modal");
  const editProfileBtn = document.getElementById("edit-profile-btn");
  const closeUserModalBtn = document.getElementById("close-user-modal");
  const cancelUserModalBtn = document.getElementById("cancel-user-modal");
  const profileForm = document.getElementById("form-user-profile");

  let currentUserData = null;
  let viewedOwnerId = "collector-main";
  let latestData = null;
  let activeUser = null;
  let isViewingOwnProfile = false;
  let ownerLikesLookup = {};

  function normalizeOwnerId(ownerId) {
    if (ownerId === undefined || ownerId === null) return "";
    return String(ownerId).trim().toLowerCase();
  }


  if (!window.demoCollectionsState) {
    window.demoCollectionsState = { voteState: {}, userChosenState: {} };
  } else {
    window.demoCollectionsState.voteState = window.demoCollectionsState.voteState || {};
    window.demoCollectionsState.userChosenState = window.demoCollectionsState.userChosenState || {};
  }
  const demoState = window.demoCollectionsState;
  const eventsDemoState = window.demoEventsState || (window.demoEventsState = { voteState: {} });

  const FOLLOW_SIMULATION_MESSAGE =
    "Attention: following collectors is just a backend simulation; there is no real persistence.";
  let followSimulationAlertShown = false;



  function formatEventDate(dateStr) {
    if (!dateStr) return "Date TBA";
    const date = new Date(dateStr);
    if (Number.isNaN(date.getTime())) return dateStr;
    return date.toLocaleDateString(undefined, {
      year: "numeric",
      month: "short",
      day: "numeric"
    });
  }

  function getEventTimestamp(event) {
    if (!event) return 0;
    const primary = event.date || event.updatedAt || event.createdAt;
    const time = primary ? new Date(primary).getTime() : NaN;
    return Number.isNaN(time) ? 0 : time;
  }

  function getPaginationControls(controlKey) {
    if (!controlKey) return [];
    return Array.from(document.querySelectorAll(`[data-pagination-for="${controlKey}"]`));
  }

  function updatePaginationStatus(controlKey, total, startIndex = 0, shownCount = total) {
    const controls = getPaginationControls(controlKey);
    if (!controls.length) return;

    const totalSafe = Math.max(total || 0, 0);
    const shownSafe = Math.max(Math.min(shownCount || 0, totalSafe), 0);
    const startSafe =
      totalSafe === 0
        ? 0
        : Math.min(Math.max(startIndex || 0, 0), Math.max(totalSafe - 1, 0));
    const rangeStart = totalSafe === 0 || shownSafe === 0 ? 0 : startSafe + 1;
    const rangeEnd = totalSafe === 0 || shownSafe === 0 ? 0 : startSafe + shownSafe;

    controls.forEach(ctrl => {
      const status = ctrl.querySelector("[data-pagination-status]");
      if (status) {
        status.textContent = `Showing ${rangeStart}-${rangeEnd} of ${totalSafe}`;
      }
      const actions = ctrl.querySelector(".pagination-actions");
      if (actions) {
        actions.hidden = totalSafe === 0;
      }
    });
  }

  function buildOwnerLikesLookup(data) {
    const map = {};
    (data?.userShowcases || []).forEach(entry => {
      const likes = entry.likes || entry.likedCollections || [];
      map[entry.ownerId] = new Set(likes);
    });
    return map;
  }

  function buildOwnerEventLikesLookup(data) {
    const map = {};
    (data?.userShowcases || []).forEach(entry => {
      const likes = entry.likedEvents || [];
      if (likes.length) {
        map[entry.ownerId] = new Set(likes);
      }
    });
    return map;
  }

  function getUserShowcaseEntry(data, ownerId) {
    if (!ownerId || !data?.userShowcases) return null;
    return data.userShowcases.find(entry => entry.ownerId === ownerId) || null;
  }

  function getOwnerLikedItems(data, ownerId) {
    const entry = getUserShowcaseEntry(data, ownerId);
    return entry?.likedItems || [];
  }

  function resolveFollowerCount(data, ownerId) {
    if (!ownerId || !data) return 0;
    if (typeof appData?.getUserFollowerCount === "function") {
      return appData.getUserFollowerCount(ownerId);
    }
    const followsMap = data.userFollows || {};
    return Object.values(followsMap).reduce((count, followingList) => {
      if (Array.isArray(followingList) && followingList.includes(ownerId)) {
        return count + 1;
      }
      return count;
    }, 0);
  }

  function doesUserLikeCollection(collection, ownerId) {
    if (!collection || !ownerId) return false;
    const likedSet = ownerLikesLookup[ownerId];
    let liked = likedSet ? likedSet.has(collection.id) : false;
    if (isViewingOwnProfile && activeUser?.id === ownerId && activeUser?.active) {
      const override = window.demoCollectionsState?.voteState?.[collection.id];
      if (override !== undefined) liked = override;
    }
    return liked;
  }

  function formatReadableDate(dateStr) {
    if (!dateStr) return null;
    const date = new Date(dateStr);
    if (Number.isNaN(date.getTime())) return null;
    return date.toLocaleDateString(undefined, {
      year: "numeric",
      month: "short",
      day: "numeric"
    });
  }

  function renderEventList(container, events, emptyMessage, paginationKey) {
    if (!container) return;
    const normalizedEvents = Array.isArray(events) ? events : [];
    if (!normalizedEvents.length) {
      container.innerHTML = `<p class="notice-message">${emptyMessage}</p>`;
      updatePaginationStatus(paginationKey, 0);
      return;
    }

    const sorted = normalizedEvents
      .slice()
      .sort((a, b) => getEventTimestamp(a) - getEventTimestamp(b));

    const encodedReturnUrl = encodeURIComponent(window.location.href);
    container.innerHTML = sorted
      .map(
        (ev) => `
      <article class="user-event-card">
        <div>
          <h3>${ev.name}</h3>
          <p class="event-meta">${formatEventDate(ev.date)} &middot; ${ev.localization || "To be announced"}</p>
        </div>
        <button class="explore-btn ghost" onclick="window.location.href='event_page.html?id=${ev.id}&returnUrl=${encodedReturnUrl}'">
          <i class="bi bi-calendar-event"></i> View event
        </button>
      </article>
    `
      )
      .join("");
    updatePaginationStatus(paginationKey, sorted.length, 0, sorted.length);
  }

  function toggleRsvpVisibility(showSection) {
    if (userRsvpTitleEl) {
      userRsvpTitleEl.hidden = !showSection;
    }
    if (userRsvpContainer) {
      userRsvpContainer.hidden = !showSection;
      if (!showSection) {
        userRsvpContainer.innerHTML = "";
      }
    }
  }

  function resolveShowcaseEntries(data, ownerId) {
    if (!ownerId) {
      return { entries: [], source: "none", fallback: null };
    }
    const sessionEntries = demoState.userChosenState?.[ownerId];
    if (sessionEntries?.length) {
      return {
        entries: sessionEntries.slice().sort((a, b) => a.order - b.order),
        source: "session",
        fallback: null
      };
    }
    const fallback = (data.userShowcases || []).find(
      (entry) => entry.ownerId === ownerId
    );
    return {
      entries: fallback?.picks
        ? fallback.picks.slice().sort((a, b) => a.order - b.order)
        : [],
      source: fallback ? "default" : "none",
      fallback
    };
  }

  function renderUserEvents(data, ownerId) {
    if (!userEventsContainer) return;

    let links = (data.collectionsUsers || []).filter(
      (link) => link.ownerId === ownerId
    );
    if (!links.length) {
      links = (data.collections || [])
        .filter((col) => col.ownerId === ownerId)
        .map((col) => ({ collectionId: col.id, ownerId }));
    }
    if (!links.length) {
      userEventsContainer.innerHTML = `<p class="notice-message">No collections linked to this user yet.</p>`;
      updatePaginationStatus("user-events", 0);
      return;
    }

    const collectionIds = new Set(links.map((link) => link.collectionId));
    const events = [];
    const seen = new Set();
    (data.collectionEvents || []).forEach((link) => {
      if (!collectionIds.has(link.collectionId)) return;
      const event = (data.events || []).find((ev) => ev.id === link.eventId);
      if (event && !seen.has(event.id)) {
        seen.add(event.id);
        events.push(event);
      }
    });

    renderEventList(
      userEventsContainer,
      events,
      "No events linked to this user yet.",
      "user-events"
    );
  }

  function renderUserRsvpEvents(data, ownerId) {
    const canShowRsvp = Boolean(isViewingOwnProfile);
    toggleRsvpVisibility(canShowRsvp);
    if (!canShowRsvp || !userRsvpContainer) return;
    let rsvpLinks = Array.isArray(data.eventsUsers) ? data.eventsUsers.slice() : [];
    if (!rsvpLinks.length) {
      rsvpLinks = (data.events || []).flatMap((event) => {
        if (!Array.isArray(event.attendees)) return [];
        return event.attendees.map((userId) => ({ eventId: event.id, userId }));
      });
    }
    const attendingIds = new Set(
      rsvpLinks
        .filter((link) => link.userId === ownerId)
        .map((link) => link.eventId)
    );
    const events = (data.events || []).filter((event) => attendingIds.has(event.id));
    renderEventList(
      userRsvpContainer,
      events,
      "No RSVP activity yet.",
      "user-rsvp-events"
    );
  }

  function renderUserChosenShowcase(data, ownerId) {
    if (!topPicksContainer) return;

    const { entries, source, fallback } = resolveShowcaseEntries(data, ownerId);

    if (topPicksNoteEl) {
      if (source === "session") {
        topPicksNoteEl.textContent =
          "Showing the order you picked for this session. Use “Show default order” to revert.";
      } else if (source === "default" && fallback?.lastUpdated) {
        const friendly = formatReadableDate(fallback.lastUpdated);
        topPicksNoteEl.textContent = friendly
          ? `Showing the default order last updated on ${friendly}.`
          : "Showing the default order defined for this user.";
      } else if (source === "default") {
        topPicksNoteEl.textContent = "Showing the default order defined for this user.";
      } else {
        topPicksNoteEl.textContent = "No curated order defined yet.";
      }
    }

    if (resetTopPicksBtn) {
      resetTopPicksBtn.disabled = source !== "session";
    }

    if (!entries.length) {
      topPicksContainer.innerHTML = `<p class="notice-message">No curated collections yet.</p>`;
      return;
    }

    const canEdit = isViewingOwnProfile;
    const cards = entries
      .map((entry) => {
        const col = (data.collections || []).find(
          (collection) => collection.id === entry.collectionId
        );
        if (!col) return "";
        const controls = canEdit
          ? `
          <div class="pick-controls">
            <button type="button" class="mini-btn" data-top-pick-action="reorder" data-collection-id="${col.id}">Put in first position</button>
          </div>`
          : "";
        return `
        <article class="top-pick-card">
          <a class="top-pick-link" href="specific_collection.html?id=${encodeURIComponent(col.id)}">
            <div class="rank-badge">#${entry.order}</div>
            <div>
              <h3>${col.name}</h3>
              <p>${col.summary || col.description || "No summary provided."}</p>
            </div>
          </a>
          ${controls}
        </article>
      `;
      })
      .filter(Boolean);

    topPicksContainer.innerHTML = cards.length
      ? cards.join("")
      : `<p class="notice-message">No curated collections yet.</p>`;
  }

  function handleResetTopPicks() {
    if (!viewedOwnerId) return;
    const sessionEntries = demoState.userChosenState?.[viewedOwnerId];
    if (!sessionEntries?.length) {
      alert("You're already seeing the default order.");
      return;
    }
    delete demoState.userChosenState[viewedOwnerId];
    window.dispatchEvent(
      new CustomEvent("userShowcaseChange", { detail: { ownerId: viewedOwnerId } })
    );
    renderUserChosenShowcase(latestData, viewedOwnerId);
  }

  function renderLikedCollections(dataArg) {
    if (!likedCollectionsContainer) return;
    const paginationKey = "user-liked-collections";
    const data = dataArg || latestData || appData.loadData();
    if (!data) {
      likedCollectionsContainer.innerHTML = `<p class="notice-message">Likes unavailable right now.</p>`;
      updatePaginationStatus(paginationKey, 0);
      return;
    }
    const targetOwner = viewedOwnerId;
    if (!targetOwner) {
      likedCollectionsContainer.innerHTML = `<p class="notice-message">No user selected.</p>`;
      updatePaginationStatus(paginationKey, 0);
      return;
    }
    const liked = (data.collections || []).filter(col => doesUserLikeCollection(col, targetOwner));
    if (!liked.length) {
      likedCollectionsContainer.innerHTML = `<p class="notice-message">${isViewingOwnProfile ? "You haven't starred any collections yet." : "This user hasn't starred any collections yet."}</p>`;
      updatePaginationStatus(paginationKey, 0);
      return;
    }
    const cards = liked.map(col => `
      <article class="liked-collection-card">
        <a href="specific_collection.html?id=${encodeURIComponent(col.id)}">
          <h3>${col.name}</h3>
          <p>${col.summary || col.description || "No summary provided."}</p>
        </a>
      </article>
    `);
    likedCollectionsContainer.innerHTML = cards.join("");
    updatePaginationStatus(paginationKey, cards.length, 0, cards.length);
  }

  function renderLikedItems(dataArg, ownerId) {
    if (!likedItemsContainer) return;
    const paginationKey = "user-liked-items";
    const data = dataArg || latestData || appData.loadData();
    if (!data) {
      likedItemsContainer.innerHTML = `<p class="notice-message">Likes unavailable right now.</p>`;
      updatePaginationStatus(paginationKey, 0);
      return;
    }
    const targetOwner = ownerId || viewedOwnerId;
    if (!targetOwner) {
      likedItemsContainer.innerHTML = `<p class="notice-message">No user selected.</p>`;
      updatePaginationStatus(paginationKey, 0);
      return;
    }
    const likedIds = getOwnerLikedItems(data, targetOwner);
    if (!likedIds.length) {
      likedItemsContainer.innerHTML = `<p class="notice-message">${isViewingOwnProfile ? "You haven't liked any items yet." : "This user hasn't liked any items yet."}</p>`;
      updatePaginationStatus(paginationKey, 0);
      return;
    }

    const itemsMap = (data.items || []).reduce((acc, item) => {
      acc[item.id] = item;
      return acc;
    }, {});

    const cards = likedIds
      .map(id => itemsMap[id])
      .filter(Boolean)
      .map(item => {
        const importanceText = item.importance ? `Importance: ${item.importance}` : "Importance not specified";
        const priceText = typeof item.price === "number" ? `Value: €${item.price}` : "Value unknown";
        const acquisitionLabel = formatReadableDate(item.acquisitionDate) || formatReadableDate(item.updatedAt) || "Date unknown";
        const imageSrc = item.image || "../images/default.jpg";
        const itemName = item.name || "Liked item";
        const safeName = itemName.replace(/"/g, "&quot;");
        return `
          <article class="liked-item-card">
            <a href="item_page.html?id=${encodeURIComponent(item.id)}">
              <div class="liked-item-inner">
                <img src="${imageSrc}" alt="${safeName}" loading="lazy">
                <div>
                  <h3>${itemName}</h3>
                  <p class="muted">${importanceText}</p>
                  <p class="muted">${priceText}</p>
                  <p class="muted">Acquired ${acquisitionLabel}</p>
                </div>
              </div>
            </a>
          </article>`;
      });

    if (!cards.length) {
      likedItemsContainer.innerHTML = `<p class="notice-message">${isViewingOwnProfile ? "You haven't liked any items yet." : "This user hasn't liked any items yet."}</p>`;
      updatePaginationStatus(paginationKey, 0);
      return;
    }

    likedItemsContainer.innerHTML = cards.join("");
    updatePaginationStatus(paginationKey, cards.length, 0, cards.length);
  }

  function renderLikedEvents(data, ownerId) {
    if (!likedEventsContainer) return;
    const paginationKey = "user-liked-events";
    const eventLikesMap = buildOwnerEventLikesLookup(data);
    const likedSet = eventLikesMap[ownerId] || new Set();
    const sessionOverrides = eventsDemoState.voteState || {};

    // Apply session changes
    Object.entries(sessionOverrides).forEach(([eventId, isLiked]) => {
      if (isLiked) likedSet.add(eventId);
      else likedSet.delete(eventId);
    });

    if (!likedSet.size) {
      likedEventsContainer.innerHTML = `<p class="notice-message">${isViewingOwnProfile ? "You haven't liked any events yet." : "This user hasn't liked any events yet."}</p>`;
      updatePaginationStatus(paginationKey, 0);
      return;
    }

    const likedEvents = (data.events || []).filter(ev => likedSet.has(ev.id));
    const cards = likedEvents.map(ev => `
      <article class="liked-collection-card">
        <a href="event_page.html?id=${encodeURIComponent(ev.id)}">
          <h3>${ev.name}</h3>
          <p>${ev.summary || ev.description || "No summary provided."}</p>
        </a>
      </article>`);
    likedEventsContainer.innerHTML = cards.join("");
    updatePaginationStatus(paginationKey, cards.length, 0, cards.length);
  }

  function showFollowSimulationMessage() {
    if (followSimulationAlertShown) return;
    alert(FOLLOW_SIMULATION_MESSAGE);
    followSimulationAlertShown = true;
  }

  function getFollowerList(followerId) {
    if (!followerId) return [];
    if (typeof appData?.getUserFollowing === "function") {
      return appData.getUserFollowing(followerId);
    }
    return [];
  }

  function isFollowingUser(targetOwnerId, followerId = activeUser?.id) {
    if (!targetOwnerId || !followerId) return false;
    if (typeof appData?.isUserFollowing === "function") {
      return appData.isUserFollowing(followerId, targetOwnerId);
    }
    return getFollowerList(followerId).includes(targetOwnerId);
  }

  function toggleFollowUser(targetOwnerId) {
    if (!targetOwnerId || !activeUser?.id || typeof appData?.toggleUserFollow !== "function") return false;
    const following = appData.toggleUserFollow(activeUser.id, targetOwnerId);
    showFollowSimulationMessage();
    return following;
  }

  function renderFollowButton(ownerName) {
    if (!followUserBtn) return;
    const followerId = normalizeOwnerId(activeUser?.id);
    const ownerId = normalizeOwnerId(viewedOwnerId);
    const isSelfProfile = Boolean(followerId && ownerId && followerId === ownerId);
    const shouldShow = Boolean(
      activeUser?.active &&
      followerId &&
      ownerId &&
      !isSelfProfile
    );
    followUserBtn.hidden = !shouldShow;
    followUserBtn.style.display = shouldShow ? "" : "none";
    if (!shouldShow) return;
    const following = isFollowingUser(viewedOwnerId, followerId);
    followUserBtn.classList.toggle("following", following);
    followUserBtn.classList.toggle("success", !following);
    followUserBtn.setAttribute("aria-pressed", String(following));
    const iconClass = following ? "bi-person-check-fill" : "bi-person-plus";
    const label = following ? "Following" : "Follow";
    followUserBtn.innerHTML = `
      <i class="bi ${iconClass} me-1" aria-hidden="true"></i>
      <span class="follow-label">${label}</span>
    `;
    followUserBtn.title = following
      ? `You are following ${ownerName || "this collector"}`
      : `Follow ${ownerName || "this collector"}`;
  }

  function populateItemCollectionsSelect() {
    if (!itemCollectionsSelect) return;
    const data = appData?.loadData ? appData.loadData() : null;
    const collections = Array.isArray(data?.collections) ? data.collections : [];
    itemCollectionsSelect.innerHTML = "";
    if (!collections.length) {
      const placeholder = document.createElement("option");
      placeholder.value = "";
      placeholder.disabled = true;
      placeholder.selected = true;
      placeholder.textContent = "No collections available";
      itemCollectionsSelect.appendChild(placeholder);
      return;
    }
    const placeholder = document.createElement("option");
    placeholder.value = "";
    placeholder.disabled = true;
    placeholder.selected = true;
    placeholder.textContent = "Select one or more collections";
    itemCollectionsSelect.appendChild(placeholder);
    collections.forEach(col => {
      const option = document.createElement("option");
      option.value = col.id || "";
      option.textContent = col.name || col["collection-name"] || "Untitled collection";
      itemCollectionsSelect.appendChild(option);
    });
  }

  function openItemModal() {
    if (!itemModal) return;
    populateItemCollectionsSelect();
    itemModal.style.display = "flex";
  }

  function closeItemModal() {
    if (!itemModal) return;
    itemForm?.reset();
    if (itemCollectionsSelect) {
      itemCollectionsSelect.selectedIndex = -1;
    }
    itemModal.style.display = "none";
  }

  function handleItemFormSubmit(event) {
    event.preventDefault();
    alert("This prototype only simulates adding items; no data is saved.");
    closeItemModal();
  }

  function handleTopPickAction(event) {
    const btn = event.target.closest("[data-top-pick-action]");
    if (!btn) return;
    event.preventDefault();
    if (!isViewingOwnProfile) {
      alert("Log in as the owner to change this order.");
      return;
    }
    const action = btn.dataset.topPickAction;
    const collectionId = btn.dataset.collectionId;
    if (!collectionId || action !== "reorder") return;
    if (!latestData) {
      alert("Data not ready. Please try again.");
      return;
    }
    if (typeof window.demoTopPickFlow !== "function") {
      alert("This action is unavailable right now.");
      return;
    }
    const collection = (latestData?.collections || []).find(c => c.id === collectionId);
    window.demoTopPickFlow({
      ownerId: viewedOwnerId,
      collectionId,
      collectionName: collection?.name,
      data: latestData
    });
  }

  function loadAndRenderUserData() {
    latestData = appData.loadData();
    const storedUser = JSON.parse(localStorage.getItem("currentUser"));
    activeUser = storedUser;
    ownerLikesLookup = buildOwnerLikesLookup(latestData);
    const params = new URLSearchParams(window.location.search);
    const ownerParam = (params.get("owner") || "").trim();
    const storedUserId = (storedUser?.id || "").trim();
    const resolvedOwnerId = ownerParam || storedUserId || "collector-main";
    viewedOwnerId = resolvedOwnerId;
    const activeOwnerId = normalizeOwnerId(activeUser?.id);
    const resolvedOwnerNormalized = normalizeOwnerId(resolvedOwnerId);
    isViewingOwnProfile =
      Boolean(activeUser?.active && activeOwnerId && resolvedOwnerNormalized && activeOwnerId === resolvedOwnerNormalized);
    const user = latestData.users.find((u) => u["owner-id"] === viewedOwnerId);

    if (!user) {
      document.querySelector("main").innerHTML = `
        <h1 class="page-title">User Not Found</h1>
        <p class="notice-message">No profile matched the id "${viewedOwnerId}".</p>`;
      return;
    }

    currentUserData = user;
    const ownerName = user["owner-name"] || viewedOwnerId;

    userNameEl.textContent = ownerName;
    if (userCollectionsTitleEl) {
      userCollectionsTitleEl.textContent = `${ownerName} Collections`;
    }
    // Only update the banner if it exists. We may use a static page title
    // (for example 'User Profile') in the markup so avoid throwing if
    // the element is missing.
    if (usernameBannerEl) usernameBannerEl.textContent = ownerName;
    userAvatarEl.src = user["owner-photo"];
    if (userEmailEl) {
      userEmailEl.textContent = isViewingOwnProfile ? user.email : "Private";
    }
    if (userDobEl) {
        userDobEl.textContent = isViewingOwnProfile ? user["date-of-birth"] : "Private";
    }
    if (userMemberSinceEl) {
      userMemberSinceEl.textContent = user["member-since"] || "N/A";
    }

    const collectionCount =
      (latestData.collectionsUsers || []).filter(
        (link) => link.ownerId === viewedOwnerId
      ).length ||
      (latestData.collections || []).filter(
        (col) => col.ownerId === viewedOwnerId
      ).length;
    const countEl = document.getElementById("user-collection-count");
    if (countEl) countEl.textContent = collectionCount;
    const followerCount = resolveFollowerCount(latestData, viewedOwnerId);
    if (userFollowersEl) userFollowersEl.textContent = followerCount;

    renderUserEvents(latestData, viewedOwnerId);
    renderUserRsvpEvents(latestData, viewedOwnerId);
    renderUserChosenShowcase(latestData, viewedOwnerId);
    renderLikedCollections(latestData);
    renderLikedItems(latestData, viewedOwnerId);
    renderLikedEvents(latestData, viewedOwnerId);
    renderFollowButton(ownerName);
  }

  function openProfileModal() {
    if (!currentUserData) return;
    profileForm.querySelector("#user-form-name").value =
      currentUserData["owner-name"] || "";
    profileForm.querySelector("#user-form-email").value =
      currentUserData.email;
    profileForm.querySelector("#user-form-dob").value =
      currentUserData["date-of-birth"];
    profileForm.querySelector("#user-form-photo").value =
      currentUserData["owner-photo"];
    profileModal.style.display = "flex";
  }

  function closeProfileModal() {
    profileModal.style.display = "none";
  }

  editProfileBtn.addEventListener("click", openProfileModal);
  closeUserModalBtn.addEventListener("click", closeProfileModal);
  cancelUserModalBtn.addEventListener("click", closeProfileModal);
  profileForm.addEventListener("submit", (e) => {
    e.preventDefault();
    alert("Demo only: profile changes are not saved.");
    closeProfileModal();
  });
  resetTopPicksBtn?.addEventListener("click", handleResetTopPicks);
  topPicksContainer?.addEventListener("click", handleTopPickAction);
  followUserBtn?.addEventListener("click", () => {
    if (!activeUser?.active || !activeUser?.id) {
      alert("Please log in to follow collectors.");
      return;
    }
    if (!viewedOwnerId || activeUser.id === viewedOwnerId) return;
    toggleFollowUser(viewedOwnerId);
    renderFollowButton(currentUserData?.["owner-name"] || viewedOwnerId);
  });

  addItemProfileBtn?.addEventListener("click", openItemModal);

  closeItemModalBtn?.addEventListener("click", closeItemModal);
  cancelItemModalBtn?.addEventListener("click", closeItemModal);
  itemForm?.addEventListener("submit", handleItemFormSubmit);
  window.addEventListener("click", (event) => {
    if (event.target === itemModal) {
      closeItemModal();
    }
  });

  addEventProfileBtn?.addEventListener("click", () => {
    const returnUrl = encodeURIComponent(window.location.href);
    window.location.href = `event_page.html?newEvent=true&returnUrl=${returnUrl}`;
  });

  window.addEventListener("userShowcaseChange", (event) => {
    const targetOwner = event?.detail?.ownerId;
    if (targetOwner && targetOwner !== viewedOwnerId) return;
    if (!latestData) latestData = appData.loadData();
    renderUserChosenShowcase(latestData, viewedOwnerId);
  });

  window.addEventListener("userLikesChange", (event) => {
    const targetOwner = event?.detail?.ownerId;
    if (targetOwner && targetOwner !== viewedOwnerId) return;
    ownerLikesLookup = buildOwnerLikesLookup(latestData);
    renderLikedCollections(latestData);
  });

  window.addEventListener("userEventLikesChange", (event) => {
    const targetOwner = event?.detail?.ownerId;
    if (targetOwner && targetOwner !== viewedOwnerId) return;
    if (!latestData) latestData = appData.loadData();
    renderLikedEvents(latestData, viewedOwnerId);
  });

  window.addEventListener("userItemLikesChange", (event) => {
    const targetOwner = event?.detail?.ownerId;
    if (targetOwner && targetOwner !== viewedOwnerId) return;
    latestData = appData.loadData();
    renderLikedItems(latestData, viewedOwnerId);
  });

  window.addEventListener("userFollowChange", (event) => {
    const followerId = event?.detail?.followerId;
    if (!followerId || followerId !== activeUser?.id) return;
    renderFollowButton(currentUserData?.["owner-name"] || viewedOwnerId);
  });

  window.addEventListener("userStateChange", () => {
    loadAndRenderUserData();
  });

  loadAndRenderUserData();
});

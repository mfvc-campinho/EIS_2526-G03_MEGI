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
  const usernameBannerEl = document.getElementById("username-banner");
  const userEventsContainer = document.getElementById("user-events");
  const userRsvpTitleEl = document.getElementById("user-rsvp-title");
  const userRsvpContainer = document.getElementById("user-rsvp-events");
  const topPicksNoteEl = document.getElementById("top-picks-note");
  const resetTopPicksBtn = document.getElementById("reset-top-picks-btn");
  const topPicksContainer = document.getElementById("user-top-picks");
  const likedCollectionsContainer = document.getElementById("user-liked-collections");

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

  if (!window.demoCollectionsState) {
    window.demoCollectionsState = { voteState: {}, userChosenState: {} };
  } else {
    window.demoCollectionsState.voteState = window.demoCollectionsState.voteState || {};
    window.demoCollectionsState.userChosenState = window.demoCollectionsState.userChosenState || {};
  }
  const demoState = window.demoCollectionsState;

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

  function buildOwnerLikesLookup(data) {
    const map = {};
    (data?.userShowcases || []).forEach(entry => {
      const likes = entry.likes || entry.likedCollections || [];
      map[entry.ownerId] = new Set(likes);
    });
    return map;
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

  function renderEventList(container, events, emptyMessage) {
    if (!container) return;
    if (!events.length) {
      container.innerHTML = `<p class="notice-message">${emptyMessage}</p>`;
      return;
    }

    const sorted = events
      .slice()
      .sort((a, b) => getEventTimestamp(a) - getEventTimestamp(b));

    container.innerHTML = sorted
      .map(
        (ev) => `
      <article class="user-event-card">
        <div>
          <h3>${ev.name}</h3>
          <p class="event-meta">${formatEventDate(ev.date)} &middot; ${ev.localization || "To be announced"}</p>
        </div>
        <button class="explore-btn ghost" onclick="window.location.href='event_page.html#${ev.id}'">
          <i class="bi bi-calendar-event"></i> View event
        </button>
      </article>
    `
      )
      .join("");
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

    renderEventList(userEventsContainer, events, "No events linked to this user yet.");
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
    renderEventList(userRsvpContainer, events, "No RSVP activity yet.");
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
            <button type="button" class="mini-btn" data-top-pick-action="reorder" data-collection-id="${col.id}">
              Change order
            </button>
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
    const data = dataArg || latestData || appData.loadData();
    if (!data) {
      likedCollectionsContainer.innerHTML = `<p class="notice-message">Likes unavailable right now.</p>`;
      return;
    }
    const targetOwner = viewedOwnerId;
    if (!targetOwner) {
      likedCollectionsContainer.innerHTML = `<p class="notice-message">No user selected.</p>`;
      return;
    }
    const liked = (data.collections || []).filter(col => doesUserLikeCollection(col, targetOwner));
    if (!liked.length) {
      likedCollectionsContainer.innerHTML = `<p class="notice-message">${isViewingOwnProfile ? "You haven't starred any collections yet." : "This user hasn't starred any collections yet."}</p>`;
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
    const ownerParam = params.get("owner");
    viewedOwnerId = ownerParam || storedUser?.id || "collector-main";
    isViewingOwnProfile = Boolean(storedUser && storedUser.id === viewedOwnerId && storedUser.active);
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
    // Only update the banner if it exists. We may use a static page title
    // (for example 'User Profile') in the markup so avoid throwing if
    // the element is missing.
    if (usernameBannerEl) usernameBannerEl.textContent = ownerName;
    userAvatarEl.src = user["owner-photo"];
    userEmailEl.textContent = user.email;
    userDobEl.textContent = user["date-of-birth"];
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

    renderUserEvents(latestData, viewedOwnerId);
    renderUserRsvpEvents(latestData, viewedOwnerId);
    renderUserChosenShowcase(latestData, viewedOwnerId);
    renderLikedCollections(latestData);
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

  loadAndRenderUserData();
});

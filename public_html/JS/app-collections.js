// ===============================================
// app-collections.js — unified final version
// ===============================================
document.addEventListener("DOMContentLoaded", () => {

  // 🔹 Simulated logged-in user
  const currentUser = { name: "collector" };

  // 🔹 Main selectors
  const list = document.getElementById("collections-list") || document.getElementById("homeCollections");
  const filter = document.getElementById("rankingFilter");
  const modal = document.getElementById("collection-modal");
  const openBtn = document.getElementById("open-collection-modal");
  const closeBtn = document.getElementById("close-collection-modal");
  const cancelBtn = document.getElementById("cancel-collection-modal");
  const form = document.getElementById("form-collection");
  const idField = document.getElementById("collection-id");
  const modalTitle = document.getElementById("collection-modal-title");
  const restoreBtn = document.getElementById("restoreDataBtn");
  const editBtn = document.getElementById("editCollectionBtn");
  const deleteBtn = document.getElementById("deleteCollectionBtn");

  const isHomePage = list?.id === "homeCollections";

  // ============================================================
  // 🔹 Render collections (Top 5 or all)
  // ============================================================
  function renderCollections(criteria = "lastAdded", limit = null) {
    const data = appData.loadData();
    let collections = data.collections || [];

    // 🔸 Filter & sort
    if (criteria === "lastAdded") {
      collections.sort((a, b) => new Date(b.metrics.addedAt) - new Date(a.metrics.addedAt));
    } else if (criteria === "userChosen") {
      collections = collections.filter(c => c.metrics.userChosen);
    } else if (criteria === "itemCount") {
      collections.sort((a, b) =>
        appData.getItemsByCollection(b.id).length - appData.getItemsByCollection(a.id).length
      );
    }

    if (limit) collections = collections.slice(0, limit);
    list.innerHTML = "";

    collections.forEach(col => {
      const items = (appData.getItemsByCollection(col.id) || []).slice(0, 2);
      const itemsHTML = items.length
        ? `<ul class="mini-item-list">${items.map(it =>
            `<li><img src="${it.image}" alt="${it.name}" class="mini-item-img"><span>${it.name}</span></li>`
          ).join("")}</ul>`
        : `<p class="no-items">No items yet.</p>`;

      const canEdit = col.owner?.toLowerCase() === currentUser.name.toLowerCase();

      const buttons = `
        <button class="explore-btn" onclick="togglePreview('${col.id}', this)">👁️ Show Preview</button>
        <button class="explore-btn" onclick="window.location.href='specific_collection.html?id=${col.id}'">🔍 Explore More</button>
        ${canEdit ? `
          <button class="explore-btn" onclick="editCollection('${col.id}')">✏️ Edit</button>
          <button class="explore-btn danger" onclick="deleteCollection('${col.id}')">🗑️ Delete</button>
        ` : ""}
      `;

      const card = `
        <div class="collection-card">
          <div class="card-image" id="img-${col.id}">
            ${col.coverImage ? `<img src="${col.coverImage}" alt="${col.name}">` : ""}
          </div>
          <div class="card-info">
            <h3>${col.name}</h3>
            <div class="items-preview" id="preview-${col.id}" style="display:none;">
              <p>${col.summary || ""}</p>
              ${itemsHTML}
            </div>
            <div class="card-buttons">${buttons}</div>
          </div>
        </div>
      `;
      list.insertAdjacentHTML("beforeend", card);
    });
  }

  // ============================================================
  // 🔹 Toggle Preview
  // ============================================================
  window.togglePreview = (id, btn) => {
    const img = document.getElementById(`img-${id}`);
    const prev = document.getElementById(`preview-${id}`);
    const show = prev.style.display === "block";
    prev.style.display = show ? "none" : "block";
    img.style.display = show ? "block" : "none";
    btn.textContent = show ? "👁️ Show Preview" : "🙈 Hide Preview";
  };

  // ============================================================
  // 🔹 Modal control
  // ============================================================
  function openModal(edit = false) {
    modalTitle.textContent = edit ? "Edit Collection" : "New Collection";
    modal.style.display = "flex";
  }
  function closeModal() {
    modal.style.display = "none";
    form.reset();
    idField.value = "";
  }

  openBtn?.addEventListener("click", () => openModal(false));
  closeBtn?.addEventListener("click", closeModal);
  cancelBtn?.addEventListener("click", closeModal);
  window.addEventListener("click", e => { if (e.target === modal) closeModal(); });

  // ============================================================
  // 🔹 Form submit (create or edit)
  // ============================================================
  form?.addEventListener("submit", e => {
    e.preventDefault();
    const timestamp = Date.now();
    const id = idField.value.trim();

    const newCol = {
      id: id || "col-" + timestamp,
      name: form["col-name"].value,
      owner: currentUser.name, // ✅ Always "collector"
      summary: form["col-summary"].value,
      coverImage: form["col-image"].value || "../images/default.jpg",
      type: form["col-type"].value,
      createdAt: new Date().toISOString().split("T")[0],
      metrics: { votes: 0, userChosen: false, addedAt: new Date().toISOString() }
    };

    if (id) appData.updateEntity("collections", id, newCol);
    else appData.addEntity("collections", newCol);

    closeModal();
    renderCollections(filter?.value || "lastAdded", isHomePage ? 5 : null);
  });

  // ============================================================
  // 🔹 Edit / Delete — per card
  // ============================================================
  window.editCollection = id => {
    const data = appData.loadData();
    const col = data.collections.find(c => c.id === id);
    if (!col || col.owner.toLowerCase() !== currentUser.name.toLowerCase()) {
      return alert("❌ You can only edit your own collections.");
    }

    form["col-name"].value = col.name;
    form["col-summary"].value = col.summary;
    form["col-image"].value = col.coverImage;
    form["col-type"].value = col.type;
    idField.value = col.id;
    openModal(true);
  };

  window.deleteCollection = id => {
    const data = appData.loadData();
    const col = data.collections.find(c => c.id === id);
    if (!col || col.owner.toLowerCase() !== currentUser.name.toLowerCase()) {
      return alert("❌ You can only delete your own collections.");
    }
    if (confirm(`⚠️ Delete "${col.name}"?`)) {
      appData.deleteEntity("collections", id);
      alert(`🗑️ Collection "${col.name}" deleted.`);
      renderCollections(filter?.value || "lastAdded", isHomePage ? 5 : null);
    }
  };

  // ============================================================
  // 🔹 Global Edit / Delete buttons
  // ============================================================
  editBtn?.addEventListener("click", () => {
    const data = appData.loadData();
    const myCollections = data.collections.filter(c =>
      c.owner?.toLowerCase() === currentUser.name.toLowerCase()
    );

    if (myCollections.length === 0)
      return alert("⚠️ You don't own any collections to edit.");

    const names = myCollections.map(c => `• ${c.name}`).join("\n");
    const name = prompt(`Which collection do you want to edit?\n\n${names}`);
    if (!name) return;

    const col = myCollections.find(c => c.name.toLowerCase() === name.toLowerCase());
    if (!col) return alert("❌ Collection not found.");

    form["col-name"].value = col.name;
    form["col-summary"].value = col.summary;
    form["col-image"].value = col.coverImage;
    form["col-type"].value = col.type;
    idField.value = col.id;
    modalTitle.textContent = "Edit Collection";
    modal.style.display = "flex";
  });

  deleteBtn?.addEventListener("click", () => {
    const data = appData.loadData();
    const myCollections = data.collections.filter(c =>
      c.owner?.toLowerCase() === currentUser.name.toLowerCase()
    );

    if (myCollections.length === 0)
      return alert("⚠️ You don't own any collections to delete.");

    const names = myCollections.map(c => `• ${c.name}`).join("\n");
    const name = prompt(`Which collection do you want to delete?\n\n${names}`);
    if (!name) return;

    const col = myCollections.find(c => c.name.toLowerCase() === name.toLowerCase());
    if (!col) return alert("❌ Collection not found.");

    if (confirm(`⚠️ Delete "${col.name}"? This cannot be undone.`)) {
      appData.deleteEntity("collections", col.id);
      alert(`🗑️ "${col.name}" deleted successfully.`);
      renderCollections(filter?.value || "lastAdded", isHomePage ? 5 : null);
    }
  });

  // ============================================================
  // 🔹 Restore initial data
  // ============================================================
  restoreBtn?.addEventListener("click", () => {
    if (confirm("⚠️ Restore initial data? This will delete all current collections.")) {
      try {
        if (typeof collectionsData !== "undefined") {
          localStorage.clear();
          localStorage.setItem("appData", JSON.stringify(collectionsData));
          alert("✅ Data restored successfully!");
          location.reload();
        } else {
          alert("❌ Data.js not loaded or collectionsData missing.");
        }
      } catch (err) {
        console.error(err);
        alert("❌ Error restoring data.");
      }
    }
  });

  // ============================================================
  // 🔹 Initialize
  // ============================================================
  renderCollections("lastAdded", isHomePage ? 5 : null);
  filter?.addEventListener("change", e => renderCollections(e.target.value, isHomePage ? 5 : null));
});

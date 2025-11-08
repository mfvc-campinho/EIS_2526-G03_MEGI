// ===============================================
// app-collections.js
// ===============================================
// Gere a interface de coleções (criar, editar, apagar)
// e também renderiza a listagem (Top 5 ou completa)
// ===============================================

document.addEventListener("DOMContentLoaded", () => {

    // 🔹 Seletores principais
    const list = document.getElementById("collections-list") || document.getElementById("homeCollections");
    const filter = document.getElementById("rankingFilter");
    const isHomePage = list?.id === "homeCollections"; // ✅ deteta se é homepage

    // 🔹 Modais (só existem na página de coleções completa)
    const modal = document.getElementById("collection-modal");
    const openBtn = document.getElementById("open-collection-modal");
    const closeBtn = document.getElementById("close-collection-modal");
    const form = document.getElementById("form-collection");
    const idField = document.getElementById("collection-id");
    const modalTitle = document.getElementById("collection-modal-title");

    // ============================================================
    // 🔹 Renderização de coleções (Top 5 ou todas)
    // ============================================================
    function renderCollections(limit = null) {
        const data = appData.loadData();
        let collections = data.collections;

        // Filtro da homepage (Top 5)
        if (limit) {
            // Ordena por data de adição (podes mudar para outro critério)
            collections = [...collections].sort(
                (a, b) => new Date(b.metrics.addedAt) - new Date(a.metrics.addedAt)
            ).slice(0, limit);
        }

        list.className = "collections-row";
        list.innerHTML = "";

        collections.forEach(col => {
            const card = document.createElement("div");
            card.className = "collection-card";


            // 🔸 Preview de até 2 itens
            const items = (appData.getItemsByCollection(col.id) || []).slice(0, 2);
            let itemsHTML = "";
            if (items.length > 0) {
                itemsHTML = `
      <ul class="mini-item-list">
        ${items.map(it => `
          <li>
            <img src="${it.image}" alt="${it.name}" class="mini-item-img">
            <span>${it.name} – ${it.importance}</span>
          </li>
        `).join("")}
      </ul>
    `;
            } else {
                itemsHTML = `<p class="no-items">No items yet.</p>`;
            }

            // 🔹 HTML principal do card
            card.innerHTML = `
    <div class="card-image" id="img-${col.id}">
      ${col.coverImage ? `<img src="${col.coverImage}" alt="${col.name}">` : ""}
    </div>
    <div class="card-info">
      <h3>${col.name}</h3>
      

      <!-- Container dos itens, inicialmente escondido -->
      <div class="items-preview" id="preview-${col.id}" style="display: none;">
      <p>${col.summary || ""}</p>  
      ${itemsHTML}
      </div>

      <div class="card-buttons">
        <button class="explore-btn" onclick="togglePreview('${col.id}', this)">👁️ Show Preview</button>
        <button class="explore-btn" onclick="window.location.href='specific_collection.html?id=${col.id}'">
          🔍 Explore More
        </button>
      </div>
    </div>
  `;

            list.appendChild(card);
        });

    }
    // ===============================================
    // 🔹 Função para alternar entre capa e preview
    // ===============================================
    window.togglePreview = (collectionId, button) => {
        const imageDiv = document.getElementById(`img-${collectionId}`);
        const previewDiv = document.getElementById(`preview-${collectionId}`);

        const isShowingPreview = previewDiv.style.display === "block";

        if (isShowingPreview) {
            previewDiv.style.display = "none";
            imageDiv.style.display = "block";
            button.textContent = "👁️ Show Preview";
        } else {
            previewDiv.style.display = "block";
            imageDiv.style.display = "none";
            button.textContent = "🙈 Hide Preview";
        }
    };


    // ============================================================
    // 🔹 CRUD — só na página de coleções completas
    // ============================================================
    function openModal(edit = false) {
        modalTitle.textContent = edit ? "Editar Coleção" : "Nova Coleção";
        modal.style.display = "block";
    }

    function closeModal() {
        modal.style.display = "none";
        form.reset();
        idField.value = "";
    }

    if (!isHomePage) {
        openBtn?.addEventListener("click", () => openModal(false));
        closeBtn?.addEventListener("click", closeModal);
        window.addEventListener("click", (e) => { if (e.target === modal) closeModal(); });

        form?.addEventListener("submit", (e) => {
            e.preventDefault();
            const id = idField.value.trim();
            const newCol = {
                id: id || "col-" + Date.now(),
                name: form["col-name"].value,
                owner: form["col-owner"].value,
                summary: form["col-summary"].value,
                coverImage: form["col-image"].value || "../images/default.jpg",
                type: form["col-type"].value,
                createdAt: new Date().toISOString().split("T")[0],
                metrics: { votes: 0, userChosen: false, addedAt: new Date().toISOString().split("T")[0] }
            };
            if (id) appData.updateEntity("collections", id, newCol);
            else appData.addEntity("collections", newCol);
            closeModal();
            renderCollections();
        });

        window.editCollection = (id) => {
            const data = appData.loadData();
            const col = data.collections.find(c => c.id === id);
            idField.value = col.id;
            form["col-name"].value = col.name;
            form["col-owner"].value = col.owner;
            form["col-summary"].value = col.summary;
            form["col-image"].value = col.coverImage;
            form["col-type"].value = col.type;
            openModal(true);
        };

        window.deleteCollection = (id) => {
            if (confirm("Remover esta coleção?")) {
                appData.deleteEntity("collections", id);
                renderCollections();
            }
        };
    }

    // ============================================================
    // 🔹 Inicialização
    // ============================================================
    if (isHomePage) {
        renderCollections(5); // 👈 Top 5
    } else {
        renderCollections();  // 👈 Todas
    }

    filter?.addEventListener("change", (e) => {
        if (isHomePage) renderCollections(5);
    });
});

// ===============================================
// app-collections.js — Versão reescrita e robusta
// Funciona em todas as páginas (home, all, user) sem quebrar noutras.
// ===============================================

document.addEventListener("DOMContentLoaded", () => {
    // ==========================================================
    // 1. Seletores de Elementos e Contexto da Página
    // ==========================================================
    const list = document.getElementById("collections-list") ||
        document.getElementById("homeCollections") ||
        document.getElementById("user-collections");
    // Se não houver um contentor de coleções nesta página, o script não faz mais nada.
    if (!list)
        return;

    const isHomePage = list?.id === "homeCollections";
    const isUserPage = list?.id === "user-collections";

    // Elementos que podem ou não existir dependendo da página
    const filter = document.getElementById("rankingFilter");
    const modal = document.getElementById("collection-modal");
    const form = document.getElementById("form-collection");
    const openBtn = document.getElementById("open-collection-modal");
    const restoreBtn = document.getElementById("restoreDataBtn");
    const editBtn = document.getElementById("editCollectionBtn");
    const deleteBtn = document.getElementById("deleteCollectionBtn");

    // ==========================================================
    // 2. Gestão do Estado do Utilizador
    // ==========================================================
    let currentUser;
    let isActiveUser;

    function updateUserState() {
        const userData = JSON.parse(localStorage.getItem("currentUser"));
        currentUser = userData ? userData.name : null;
        isActiveUser = userData && userData.active;
    }

    // ==========================================================
    // 3. Renderização das Coleções
    // ==========================================================
    function renderCollections(criteria = "lastAdded", limit = null) {
        const data = appData.loadData();
        let collections = data.collections || [];

        // Filtra para a página de utilizador
        if (isUserPage) {
            if (!isActiveUser) {
                list.innerHTML = `
          <div class="notice-message">
            <p>Please sign in to view your collections.</p>
          </div>`;
                return;
            }
            collections = collections.filter(c => c.owner?.toLowerCase() === currentUser.toLowerCase());
        }

        // Ordena conforme o critério
        if (criteria === "lastAdded") {
            collections.sort((a, b) => new Date(b.metrics.addedAt) - new Date(a.metrics.addedAt));
        } else if (criteria === "userChosen") {
            collections = collections.filter(c => c.metrics.userChosen);
        } else if (criteria === "itemCount") {
            // Otimização de Performance: Pré-calcular a contagem de itens
            // Em vez de recalcular em cada comparação do sort, calculamos uma vez para cada coleção.
            const itemCounts = data.collectionItems.reduce((acc, link) => {
                acc[link.collectionId] = (acc[link.collectionId] || 0) + 1;
                return acc;
            }, {});

            // Ordena usando a contagem pré-calculada.
            collections.sort((a, b) => (itemCounts[b.id] || 0) - (itemCounts[a.id] || 0));
        }

        // Aplica o limite (para a homepage)
        if (limit)
            collections = collections.slice(0, limit);

        list.innerHTML = "";
        if (collections.length === 0) {
            list.innerHTML = `<p class="notice-message">No collections found.</p>`;
            return;
        }

        // Gera o HTML dos cartões
        let allCardsHTML = ""; // 1. Acumulador de HTML

        for (const col of collections) {
            const items = (appData.getItemsByCollection(col.id, data) || []).slice(0, 2);
            const itemsHTML = items.length
                ? `<ul class="mini-item-list">${items.map(it =>
                    `<li><img src="${it.image}" alt="${it.name}" class="mini-item-img" loading="lazy"><span>${it.name}</span></li>`
                ).join("")}</ul>`
                : `<p class="no-items">No items yet.</p>`;

            const isOwnerLoggedIn = isActiveUser && col.owner?.toLowerCase() === currentUser.toLowerCase();
            const specialClass = isOwnerLoggedIn ? 'collector-owned' : '';
            const canEdit = isActiveUser && (col.owner?.toLowerCase() === currentUser.toLowerCase());

            const buttons = `
        <button class="explore-btn" onclick="togglePreview('${col.id}', this)">👁️ Show Preview</button>
        <button class="explore-btn" onclick="window.location.href='specific_collection.html?id=${col.id}'">🔍 Explore More</button>
        ${canEdit ? `<button class="explore-btn" onclick="editCollection('${col.id}')">✏️ Edit</button>` : ""}
        ${canEdit ? `<button class="explore-btn danger" onclick="deleteCollection('${col.id}')">🗑️ Delete</button>` : ""}
      `;

            // 2. Adiciona o HTML do cartão ao acumulador em vez de ao DOM
            allCardsHTML += `
        <div class="card collection-card ${specialClass}">
          <div class="card-image" id="img-${col.id}"><img src="${col.coverImage || '../images/default.jpg'}" alt="${col.name}" loading="lazy"></div>
          <div class="card-info">
            <h3>${col.name}</h3>
            <p>${col.summary || ""}</p>
            <div class="items-preview" id="preview-${col.id}" style="display:none;">${itemsHTML}</div>
            <div class="card-buttons">${buttons}</div>
          </div>
        </div>
      `;
        }

        // 3. Insere todo o HTML no DOM de uma só vez, após o loop
        list.innerHTML = allCardsHTML;
    }

    // ==========================================================
    // 4. Funções Globais (acessíveis pelo HTML)
    // ==========================================================
    window.togglePreview = (id, btn) => {
        const img = document.getElementById(`img-${id}`);
        const prev = document.getElementById(`preview-${id}`);
        const isShowingPreview = prev.style.display === "block";
        prev.style.display = isShowingPreview ? "none" : "block";
        img.style.display = isShowingPreview ? "block" : "none";
        btn.textContent = isShowingPreview ? "👁️ Show Preview" : "🙈 Hide Preview";
    };

    window.editCollection = id => {
        const data = appData.loadData();
        const col = data.collections.find(c => c.id === id);
        if (!col || !isActiveUser || col.owner.toLowerCase() !== currentUser.toLowerCase())
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
        const col = data.collections.find(c => c.id === id);
        if (!col || !isActiveUser || col.owner.toLowerCase() !== currentUser.toLowerCase())
            return alert("❌ You can only delete your own collections.");

        if (confirm(`⚠️ Delete "${col.name}"?`)) {
            appData.deleteEntity("collections", id);
            alert(`🗑️ Collection "${col.name}" deleted.`);
            renderCollections(filter ? filter.value : "lastAdded", isHomePage ? 5 : null);
        }
    };

    // ==========================================================
    // 5. Event Listeners (com verificações de existência)
    // ==========================================================

    // Filtro da Homepage
    if (filter) {
        filter.addEventListener("change", e =>
            renderCollections(e.target.value, isHomePage ? 5 : null)
        );
    }
    // Modal de Coleção
    if (modal && form) {
        const modalTitle = document.getElementById("collection-modal-title");
        const idField = document.getElementById("collection-id");

        const openModal = (edit = false) => {
            // Garante que o formulário é limpo antes de abrir
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

            const updatedFields = {
                name: form["col-name"].value,
                summary: form["col-summary"].value,
                coverImage: form["col-image"].value || "../images/default.jpg",
                type: form["col-type"].value,
                description: form["col-description"].value,
            };

            if (id) { // Estamos a editar uma coleção existente
                appData.updateEntity("collections", id, updatedFields);
            } else { // Estamos a criar uma nova coleção
                const newCol = {
                    id: "col-" + Date.now(),
                    owner: currentUser,
                    createdAt: new Date().toISOString().split("T")[0],
                    metrics: { votes: 0, userChosen: false, addedAt: new Date().toISOString() }, ...updatedFields // Adiciona os campos do formulário
                };
                appData.addEntity("collections", newCol);
            }

            closeModal();
            renderCollections(filter ? filter.value : "lastAdded", isHomePage ? 5 : null);
        });

        document.getElementById("close-collection-modal")?.addEventListener("click", closeModal);
        document.getElementById("cancel-collection-modal")?.addEventListener("click", closeModal);
        window.addEventListener("click", e => {
            if (e.target === modal)
                closeModal();
        });
    }

    // Botões de Ação Globais
    const setupGlobalActions = (btn, action) => {
        if (!btn)
            return;
        btn.addEventListener("click", () => {
            if (!isActiveUser)
                return alert(`🚫 You must be logged in to ${action} collections.`);
            const data = appData.loadData();
            const myCollections = data.collections.filter(c => c.owner?.toLowerCase() === currentUser.toLowerCase());
            if (myCollections.length === 0)
                return alert(`⚠️ You don't own any collections to ${action}.`);

            const names = myCollections.map(c => `• ${c.name}`).join("\n");
            const name = prompt(`Which collection do you want to ${action}?\n\n${names}`);
            if (!name)
                return;

            const col = myCollections.find(c => c.name.toLowerCase() === name.toLowerCase());
            if (!col)
                return alert("❌ Collection not found.");

            if (action === 'edit') {
                editCollection(col.id);
            } else if (action === 'delete') {
                deleteCollection(col.id);
            }
        });
    };

    setupGlobalActions(editBtn, 'edit');
    setupGlobalActions(deleteBtn, 'delete');

    // Botão de Restaurar Dados
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

    // Reatividade ao estado do utilizador
    window.addEventListener("userStateChange", () => {
        updateUserState();
        renderCollections(filter ? filter.value : "lastAdded", isHomePage ? 5 : null);
    });

    // ==========================================================
    // 6. Inicialização
    // ==========================================================
    updateUserState();
    renderCollections("lastAdded", isHomePage ? 5 : null);
});

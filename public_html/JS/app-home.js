// ===============================================
// app-home.js
// ===============================================
// Mostra o Top 5 coleções na homepage,
// incluindo preview dos itens e dropdown dinâmico
// ===============================================

document.addEventListener("DOMContentLoaded", () => {

  const container = document.getElementById("homeCollections");
  const filter = document.getElementById("rankingFilter");
  const dropdown = document.getElementById("collectionsDropdown");

  // ============================================================
  // 🔹 Preenche o menu "Collections" com as coleções existentes
  // ============================================================
  function populateDropdown() {
    const data = appData.loadData();
    dropdown.innerHTML = "";
    data.collections.forEach(col => {
      const a = document.createElement("a");
      a.href = `collection_page.html?id=${col.id}`;
      a.textContent = col.name;
      dropdown.appendChild(a);
    });
  }

  // ============================================================
  // 🔹 Renderiza o Top 5 de coleções (com preview dos itens)
  // ============================================================
  function renderTopCollections(criteria = "lastAdded") {
    const data = appData.loadData();
    let collections = data.collections;

    // Ordenação conforme filtro
    if (criteria === "lastAdded") {
      collections.sort((a, b) => new Date(b.metrics.addedAt) - new Date(a.metrics.addedAt));
    } else if (criteria === "userChosen") {
      collections = collections.filter(c => c.metrics.userChosen);
    } else if (criteria === "itemCount") {
      collections.sort((a, b) => b.items.length - a.items.length);
    }

    // Top 5
    const top5 = collections.slice(0, 5);
    container.innerHTML = "";

    top5.forEach(col => {
      const card = document.createElement("div");
      card.className = "collection-card";

      // 🔸 Preview até 2 itens
      const items = col.items ? col.items.slice(0, 2) : [];
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
        <div class="card-image">
          ${col.coverImage ? `<img src="${col.coverImage}" alt="${col.name}">` : ""}
        </div>
        <div class="card-info">
          <h3>${col.name}</h3>
          <p>${col.summary || ""}</p>

          ${itemsHTML}

          <div class="card-buttons">
            <button class="explore-btn"
              onclick="window.location.href='collection_page.html?id=${col.id}'">
              🔍 Explore More
            </button>
          </div>
        </div>
      `;

      container.appendChild(card);
    });
  }

  // ============================================================
  // 🔹 Inicialização
  // ============================================================
  populateDropdown();
  renderTopCollections();
  filter.addEventListener("change", e => renderTopCollections(e.target.value));
});

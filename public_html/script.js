document.addEventListener("DOMContentLoaded", function () {
  // ===============================
  // 🔹 1) Página principal (collection_page.html)
  // ===============================
  const exploreButtons = document.querySelectorAll(".explore-btn");

  if (exploreButtons.length > 0) {
    exploreButtons.forEach((button) => {
      button.addEventListener("click", () => {
        const id = button.getAttribute("data-id");
        if (!id) {
          alert("Collection ID missing.");
          return;
        }
        window.location.href = `specific_collection.html?id=${id}`;
      });
    });
    return; // Interrompe aqui — o resto do código é só para a página específica
  }

  // ===============================
  // 🔹 2) Página específica (specific_collection.html)
  // ===============================
  const collections = {
  escudos: {
    name: "Escudos Portugueses",
    owner: "Valentim Moureiro",
    ownerPhoto: "images/valentim.jpg",
    created: "2018-04-10",
    items: [
      { title: "Escudo de 1950", description: "Moeda histórica de prata.", value: 120, dateAdded: "2020-03-15", rarity: "Raro", condition: "Muito Bom", image: "images/escudo1950.jpg" },
      { title: "Escudo de 1960", description: "Moeda comemorativa rara.", value: 250, dateAdded: "2021-07-10", rarity: "Muito Raro", condition: "Excelente", image: "images/escudo1960.jpg" }
    ]
  },
  playboys: {
    name: "Playboys Portuguesas",
    owner: "Rui Frio",
    ownerPhoto: "../images/rui.jpg",
    created: "2019-01-05",
    items: [
      { title: "Edição 2011", description: "Capa Rita Pereira", value: 5, dateAdded: "2019-05-22", rarity: "Raro", condition: "Bom", image: "../images/playboy.jpg" },
      { title: "Edição 1995", description: "Capa Lenka da Silva", value: 12, dateAdded: "2020-11-05", rarity: "Muito Raro", condition: "Excelente", image: "../images/lenka.jpg" },
      { title: "Edição 2016", description: "Capa Fabiana Brito", value: 12, dateAdded: "2016-08-05", rarity: "Comum", condition: "Mau", image: "../images/fabiana.jpg" }
    ]
  },
  retratos: {
    name: "Retratos de Líderes Fascistas",
    owner: "André Fartura",
    ownerPhoto: "images/coins.png",
    created: "2017-02-02",
    items: [
      { title: "Retrato de Salazar", description: "Pintura a óleo datada de 1940.", value: 300, dateAdded: "2018-09-10", rarity: "Raro", condition: "Bom", image: "../images/salazar.jpg" }
    ]
  },
  pokemon: {
    name: "Cartas de Pokémon",
    owner: "Cristina Sem Feira",
    ownerPhoto: "../images/cristina.jpg",
    created: "2021-04-20",
    items: [
      { title: "Pikachu Base Set", description: "Carta clássica de 1999.", value: 150, dateAdded: "2021-06-18", rarity: "Comum", condition: "Excelente", image: "images/pikachu.jpg" },
      { title: "Charizard Holo", description: "Edição rara de 1ª geração.", value: 2000, dateAdded: "2022-04-22", rarity: "Muito Raro", condition: "Excelente", image: "images/charizard.jpg" }
    ]
  },
  camisolas: {
    name: "Camisolas de Futebol Autografadas",
    owner: "André Vilas Todas Boas",
    ownerPhoto: "images/rui_tosta.jpg",
    created: "2019-06-25",
    items: [
      { title: "Camisola FC Porto 2004", description: "Autografada por Deco e Ricardo Carvalho.", value: 450, dateAdded: "2020-03-12", rarity: "Raro", condition: "Excelente", image: "images/porto.jpg" },
      { title: "Camisola Benfica 2010", description: "Autografada por Aimar e Cardozo.", value: 400, dateAdded: "2021-09-03", rarity: "Raro", condition: "Muito Bom", image: "images/benfica.jpg" }
    ]
  }
};

  // ========== Obter ID da coleção ==========
  const params = new URLSearchParams(window.location.search);
  const id = params.get("id");
  const data = collections[id];

  const titleEl = document.getElementById("collection-title");
  const itemsContainer = document.getElementById("collection-items");
  const ownerName = document.getElementById("owner-name");
  const ownerPhoto = document.getElementById("owner-photo");
  const creationDate = document.getElementById("creation-date");

  if (!data) {
    if (titleEl) titleEl.textContent = "Coleção não encontrada.";
    return;
  }

  // ========== Preencher informações da coleção ==========
  titleEl.textContent = data.name;
  ownerName.textContent = data.owner;
  creationDate.textContent = data.created;
  ownerPhoto.src = data.ownerPhoto;

  // ===============================
  // 🔹 Funções auxiliares
  // ===============================
  function saveToStorage() {
    localStorage.setItem(`collection_${id}`, JSON.stringify(data.items));
  }

  function loadFromStorage() {
    const saved = localStorage.getItem(`collection_${id}`);
    if (saved) {
      try {
        data.items = JSON.parse(saved);
      } catch (e) {
        console.error("Erro ao carregar localStorage:", e);
      }
    }
  }

  // ===============================
  // 🔹 Renderização dos itens
  // ===============================
  function renderCollection() {
    if (!itemsContainer) return;

    loadFromStorage(); // Carrega do localStorage primeiro

    itemsContainer.innerHTML = "";

    // Ordena por valor decrescente
    data.items.sort((a, b) => b.value - a.value);
    const topValue = data.items[0]?.value ?? 0;

data.items.forEach((item) => {
  const card = document.createElement("div");
  card.classList.add("item-card");
  if (item.value === topValue) card.classList.add("premium-item");

  card.innerHTML = `
    <div class="item-image-wrapper">
      <img src="${item.image || 'images/default.jpg'}" alt="${item.title}" class="item-image"/>
    </div>
    <div class="item-info">
      <h4>${item.title}</h4>
      <p>${item.description}</p>
      <ul>
        <li><strong>Valor:</strong> €${item.value}</li>
        <li><strong>Data adicionada:</strong> ${item.dateAdded}</li>
        <li><strong>Raridade:</strong> ${item.rarity}</li>
        <li><strong>Condição:</strong> ${item.condition}</li>
      </ul>
      <div class="item-buttons">
        <button class="edit-btn">Editar</button>
        <button class="remove-btn">Remover</button>
      </div>
    </div>
  `;
  itemsContainer.appendChild(card);
});

  }

  renderCollection();

  // ===============================
  // 🔹 Modal
  // ===============================
  const modal = document.getElementById("item-modal");
  const openBtn = document.getElementById("add-item");
  const closeBtn = document.getElementById("close-modal");
  const cancelBtn = document.getElementById("cancel-modal");
  const form = document.getElementById("item-form");
  let editIndex = null;

  function openModal(index = null) {
    modal.style.display = "flex";
    editIndex = index;
    form.reset();

    if (index !== null) {
      const item = data.items[index];
      form.querySelector("#item-title").value = item.title;
      form.querySelector("#item-description").value = item.description;
      form.querySelector("#item-image").value = item.image;
      form.querySelector("#item-value").value = item.value;
      form.querySelector("#item-date").value = item.dateAdded;
      form.querySelector("#item-rarity").value = item.rarity;
      form.querySelector("#item-condition").value = item.condition;
      document.getElementById("modal-title").textContent = "Editar Item";
    } else {
      document.getElementById("modal-title").textContent = "Adicionar Item";
    }
  }

  function closeModal() {
    modal.style.display = "none";
  }

  openBtn.addEventListener("click", () => openModal());
  closeBtn.addEventListener("click", closeModal);
  cancelBtn.addEventListener("click", closeModal);
  window.addEventListener("click", (e) => {
    if (e.target === modal) closeModal();
  });

  // ===============================
  // 🔹 Submissão
  // ===============================
  form.addEventListener("submit", (e) => {
    e.preventDefault();
    const newItem = {
      title: form.querySelector("#item-title").value,
      description: form.querySelector("#item-description").value,
      image: form.querySelector("#item-image").value,
      value: Number(form.querySelector("#item-value").value),
      dateAdded: form.querySelector("#item-date").value,
      rarity: form.querySelector("#item-rarity").value,
      condition: form.querySelector("#item-condition").value
    };

    if (editIndex !== null) {
      data.items[editIndex] = newItem;
    } else {
      data.items.push(newItem);
    }

    saveToStorage();
    renderCollection();
    closeModal();
  });

  // ===============================
  // 🔹 Editar / Remover
  // ===============================
  itemsContainer.addEventListener("click", (e) => {
    if (e.target.classList.contains("edit-btn")) {
      const index = [...itemsContainer.children].indexOf(e.target.closest(".item-card"));
      openModal(index);
    }
    if (e.target.classList.contains("remove-btn")) {
      const index = [...itemsContainer.children].indexOf(e.target.closest(".item-card"));
      if (confirm("Remover este item?")) {
        data.items.splice(index, 1);
        saveToStorage();
        renderCollection();
      }
    }
  });
});







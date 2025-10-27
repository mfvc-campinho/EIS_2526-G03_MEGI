document.addEventListener("DOMContentLoaded", function () {
  const collections = {
    escudos: {
      name: "Escudos Portugueses",
      items: [
        { title: "Escudo de 1950", description: "Moeda histórica de prata.", value: 120, dateAdded: "2020-03-15", rarity: "Raro", condition: "Muito Bom", image: "images/escudo1950.jpg" },
        { title: "Escudo de 1960", description: "Moeda comemorativa rara.", value: 250, dateAdded: "2021-07-10", rarity: "Muito Raro", condition: "Excelente", image: "images/escudo1960.jpg" }
      ]
    },

    playboys: {
      name: "Playboys Portuguesas",
      items: [
        { title: "Edição 1989", description: "Primeira edição publicada em Portugal.", value: 50, dateAdded: "2019-05-22", rarity: "Raro", condition: "Bom", image: "images/playboy1989.jpg" },
        { title: "Edição 1995", description: "Edição de colecionador.", value: 70, dateAdded: "2020-11-05", rarity: "Muito Raro", condition: "Excelente", image: "images/playboy1995.jpg" }
      ]
    },

    retratos: {
      name: "Retratos de Líderes Fascistas",
      items: [
        { title: "Retrato de Salazar", description: "Pintura a óleo datada de 1940.", value: 300, dateAdded: "2018-09-10", rarity: "Raro", condition: "Bom", image: "images/salazar.jpg" },
        { title: "Retrato de Mussolini", description: "Reprodução italiana dos anos 30.", value: 500, dateAdded: "2020-12-01", rarity: "Muito Raro", condition: "Excelente", image: "images/mussolini.jpg" }
      ]
    },

    pokemon: {
      name: "Cartas de Pokémon",
      items: [
        { title: "Pikachu Base Set", description: "Carta clássica de 1999.", value: 150, dateAdded: "2021-06-18", rarity: "Comum", condition: "Excelente", image: "images/pikachu.jpg" },
        { title: "Charizard Holo", description: "Edição rara de 1ª geração.", value: 2000, dateAdded: "2022-04-22", rarity: "Muito Raro", condition: "Excelente", image: "images/charizard.jpg" }
      ]
    },

    camisolas: {
      name: "Camisolas de Futebol Autografadas",
      items: [
        { title: "Camisola FC Porto 2004", description: "Autografada por Deco e Ricardo Carvalho.", value: 450, dateAdded: "2020-03-12", rarity: "Raro", condition: "Excelente", image: "images/porto.jpg" },
        { title: "Camisola Benfica 2010", description: "Autografada por Aimar e Cardozo.", value: 400, dateAdded: "2021-09-03", rarity: "Raro", condition: "Muito Bom", image: "images/benfica.jpg" }
      ]
    }
  };


  const params = new URLSearchParams(window.location.search);
  const id = params.get("id");
  const data = collections[id];

  const titleElement = document.getElementById("collection-title");
  const container = document.getElementById("collection-items");

  if (!data) {
    titleElement.textContent = "Collection not found.";
    return;
  }

  titleElement.textContent = data.name;
  const storageKey = `collection_${data.name.replace(/\s+/g, "_").toLowerCase()}`;

  // ===============================
  // 🔹 Funções de persistência
  // ===============================
  function saveCollection() {
    const items = [];
    container.querySelectorAll(".collection-item").forEach(card => {
      const obj = {
        title: card.querySelector("h4")?.textContent || "",
        subtitle: card.querySelectorAll("p")[0]?.textContent || "",
        description: card.querySelector(".item-desc")?.textContent || "",
        value: card.querySelector("ul li:nth-child(1)")?.textContent.replace("Valor:", "").trim(),
        date: card.querySelector("ul li:nth-child(2)")?.textContent.replace("Data adicionada:", "").trim(),
        rarity: card.querySelector("ul li:nth-child(3)")?.textContent.replace("Raridade:", "").trim(),
        condition: card.querySelector("ul li:nth-child(4)")?.textContent.replace("Condição:", "").trim(),
      };
      items.push(obj);
    });
    localStorage.setItem(storageKey, JSON.stringify(items));
  }

  function loadCollection() {
    const saved = localStorage.getItem(storageKey);
    const items = saved ? JSON.parse(saved) : data.items;

    container.innerHTML = "";
    items.forEach((item, index) => {
      const div = document.createElement("div");
      div.classList.add("collection-item");
      if (index === 0)
        div.classList.add("highlight");

      div.innerHTML = `
        <h4>${item.title}</h4>
        <p>${item.title}</p>
        <p class="item-desc">${item.description}</p>
        <ul>
          <li><strong>Valor:</strong> €${item.value}</li>
          <li><strong>Data adicionada:</strong> ${item.dateAdded || item.date}</li>
          <li><strong>Raridade:</strong> ${item.rarity}</li>
          <li><strong>Condição:</strong> ${item.condition}</li>
        </ul>
        <div class="item-buttons">
          <button class="edit-btn">Editar</button>
          <button class="remove-btn">Remover</button>
        </div>
      `;
      container.appendChild(div);
    });
  }

  // ===============================
  // 🔹 Criação de botões principais
  // ===============================
  const buttonContainer = document.createElement("div");
  buttonContainer.classList.add("top-buttons");
  buttonContainer.innerHTML = `
    <button id="add-item">Adicionar</button>

  `;
  container.parentElement.insertBefore(buttonContainer, container);

  // ===============================
  // 🔹 Modal de formulário
  // ===============================
  const modal = document.createElement("div");
  modal.classList.add("modal");
  modal.innerHTML = `
    <div class="modal-content">
      <h2 id="modal-title">Adicionar Item</h2>
      <form id="item-form">
        <label>Título:</label><input type="text" id="item-title" required>
        <label>Descrição:</label><input type="text" id="item-description" required>
        <label>Valor (€):</label><input type="number" id="item-value" required>
        <label>Data adicionada:</label><input type="date" id="item-date" value="${new Date().toISOString().split("T")[0]}">
        <label>Raridade:</label><input type="text" id="item-rarity" required>
        <label>Condição:</label><input type="text" id="item-condition" required>
        <div class="modal-actions">
          <button type="submit" class="save-btn">Guardar</button>
          <button type="button" id="cancel">Cancelar</button>
        </div>
      </form>
    </div>
  `;
  document.body.appendChild(modal);

  const form = modal.querySelector("form");
  let currentAction = null;
  let currentCard = null;

  function openModal(action, card = null) {
    modal.style.display = "flex";
    currentAction = action;
    currentCard = card;
    form.reset();
    document.getElementById("modal-title").textContent =
      action === "add" ? "Adicionar Item" : "Editar Item";

    if (action === "edit" && card) {
      form.querySelector("#item-title").value = card.querySelector("h4").textContent;
      form.querySelector("#item-description").value = card.querySelector(".item-desc").textContent;
      form.querySelector("#item-value").value = card.querySelector("ul li:nth-child(1)").textContent.replace("Valor:", "").replace("€", "").trim();
      form.querySelector("#item-date").value = card.querySelector("ul li:nth-child(2)").textContent.replace("Data adicionada:", "").trim();
      form.querySelector("#item-rarity").value = card.querySelector("ul li:nth-child(3)").textContent.replace("Raridade:", "").trim();
      form.querySelector("#item-condition").value = card.querySelector("ul li:nth-child(4)").textContent.replace("Condição:", "").trim();
    }
  }

  function closeModal() {
    modal.style.display = "none";
    form.reset();
  }

  // ===============================
  // 🔹 Eventos dos botões
  // ===============================
  document.getElementById("add-item").onclick = () => openModal("add");

  container.addEventListener("click", e => {
    if (e.target.classList.contains("edit-btn")) {
      openModal("edit", e.target.closest(".collection-item"));
    }
    if (e.target.classList.contains("remove-btn")) {
      const card = e.target.closest(".collection-item");
      if (confirm("Remover este item?")) {
        card.remove();
        saveCollection();
      }
    }
  });

  document.getElementById("cancel").onclick = closeModal;

  // ===============================
  // 🔹 Submissão do formulário
  // ===============================
  form.onsubmit = e => {
    e.preventDefault();
    const title = form.querySelector("#item-title").value;
    const description = form.querySelector("#item-description").value;
    const value = form.querySelector("#item-value").value;
    const date = form.querySelector("#item-date").value;
    const rarity = form.querySelector("#item-rarity").value;
    const condition = form.querySelector("#item-condition").value;

    if (currentAction === "add") {
      const newCard = document.createElement("div");
      newCard.classList.add("collection-item");
      newCard.innerHTML = `
        <h4>${title}</h4>
        <p>${title}</p>
        <p class="item-desc">${description}</p>
        <ul>
          <li><strong>Valor:</strong> €${value}</li>
          <li><strong>Data adicionada:</strong> ${date}</li>
          <li><strong>Raridade:</strong> ${rarity}</li>
          <li><strong>Condição:</strong> ${condition}</li>
        </ul>
        <div class="item-buttons">
          <button class="edit-btn">Editar</button>
          <button class="remove-btn">Remover</button>
        </div>
      `;
      container.appendChild(newCard);
    }

    if (currentAction === "edit" && currentCard) {
      currentCard.querySelector("h4").textContent = title;
      currentCard.querySelectorAll("p")[0].textContent = title;
      currentCard.querySelector(".item-desc").textContent = description;
      const lis = currentCard.querySelectorAll("ul li");
      lis[0].innerHTML = `<strong>Valor:</strong> €${value}`;
      lis[1].innerHTML = `<strong>Data adicionada:</strong> ${date}`;
      lis[2].innerHTML = `<strong>Raridade:</strong> ${rarity}`;
      lis[3].innerHTML = `<strong>Condição:</strong> ${condition}`;
    }

    saveCollection();
    closeModal();
  };

  // ===============================
  // 🔹 Inicialização
  // ===============================
  loadCollection();
});

// ===============================================
// app-data.js — Gestão de dados para GoodCollections
// ===============================================
// Lê e grava os dados das coleções, itens e eventos,
// seguindo exatamente a estrutura do ficheiro Data.js.
// ===============================================

document.addEventListener("DOMContentLoaded", () => {
  // ============================================================
  // 1️⃣ Inicialização
  // ============================================================
  if (!localStorage.getItem("collectionsData")) {
    if (typeof collectionsData !== "undefined") {
      localStorage.setItem("collectionsData", JSON.stringify(collectionsData));
      console.log("✅ Dados iniciais importados do Data.js");
    } else {
      console.error("❌ ERRO: O ficheiro Data.js não foi carregado.");
    }
  } else {
    console.log("📦 Dados carregados do localStorage.");
  }

  // ============================================================
  // 2. Funções utilitárias
  // ============================================================
  function loadData() {
    return JSON.parse(localStorage.getItem("collectionsData"));
  }

  function saveData(data) {
    localStorage.setItem("collectionsData", JSON.stringify(data));
  }

  // ============================================================
  // 3. Ligações N:N
  // ============================================================

  // Itens associados a uma coleção
  function getItemsByCollection(collectionId, data) {
    if (!data) data = loadData(); // Carrega os dados se não forem passados como argumento
    if (!data || !data.collectionItems) return [];

    // Otimização: Usar um Set para pesquisa O(1) em vez de Array.includes() que é O(n).
    const linkedItemIds = new Set(
      data.collectionItems
        .filter(link => link.collectionId === collectionId)
        .map(link => link.itemId)
    );

    return data.items.filter(item => linkedItemIds.has(item.id));
  }

  // Eventos associados a uma coleção
  function getEventsByCollection(collectionId) {
    const data = loadData();
    if (!data || !data.collectionEvents) return [];

    const linkedIds = data.collectionEvents
      .filter(link => link.collectionId === collectionId)
      .map(link => link.eventId);

    return data.events.filter(event => linkedIds.includes(event.id));
  }

  // Criar uma nova ligação item ↔ coleção
  function linkItemToCollection(itemId, collectionId) {
    const data = loadData();
    if (!data.collectionItems) data.collectionItems = [];

    const exists = data.collectionItems.some(
      l => l.itemId === itemId && l.collectionId === collectionId
    );
    if (!exists) {
      data.collectionItems.push({ itemId, collectionId });
      saveData(data);
      console.log(`Linked item ${itemId} to collection ${collectionId}`);
    }
  }

  // Criar uma nova ligação evento ↔ coleção
  function linkEventToCollection(eventId, collectionId) {
    const data = loadData();
    if (!data.collectionEvents) data.collectionEvents = [];

    const exists = data.collectionEvents.some(
      l => l.eventId === eventId && l.collectionId === collectionId
    );
    if (!exists) {
      data.collectionEvents.push({ eventId, collectionId });
      saveData(data);
      console.log(`Linked event ${eventId} to collection ${collectionId}`);
    }
  }

  // Dono associado a uma coleção
  function getCollectionOwnerId(collectionId, data) {
    if (!collectionId) return null;
    if (!data) data = loadData();
    const link = (data.collectionsUsers || []).find(entry => entry.collectionId === collectionId);
    return link ? link.ownerId : null;
  }

  function getCollectionOwner(collectionId, data) {
    if (!collectionId) return null;
    if (!data) data = loadData();
    const ownerId = getCollectionOwnerId(collectionId, data);
    if (!ownerId) return null;
    const users = data.users || [];
    return users.find(user =>
      user["owner-id"] === ownerId ||
      user.id === ownerId
    ) || null;
  }

  // ============================================================
  // 4. CRUD básico
  // ============================================================
  function addEntity(type, entity) {
    const data = loadData();
    data[type].push(entity);
    saveData(data);
  }

  function updateEntity(type, id, newValues) {
    const data = loadData();
    const index = data[type].findIndex(e => e.id === id);
    if (index !== -1) {
      data[type][index] = { ...data[type][index], ...newValues };
      saveData(data);
    }
  }

  function deleteEntity(type, id) {
    const data = loadData();
    data[type] = data[type].filter(e => e.id !== id);

    // Se apagar coleção, remove as relações associadas
    if (type === "collections") {
      data.collectionItems = data.collectionItems.filter(r => r.collectionId !== id);
      data.collectionEvents = data.collectionEvents.filter(r => r.collectionId !== id);
      data.collectionsUsers = data.collectionsUsers?.filter(r => r.collectionId !== id) || [];
    }

    // Se apagar item/evento, remove as ligações também
    if (type === "items") {
      data.collectionItems = data.collectionItems.filter(r => r.itemId !== id);
    }
    if (type === "events") {
      data.collectionEvents = data.collectionEvents.filter(r => r.eventId !== id);
    }
    if (type === "users") {
      data.collectionsUsers = data.collectionsUsers?.filter(r => r.ownerId !== id) || [];
    }

    saveData(data);
  }

  // ============================================================
  // 5. Exportar API global
  // ============================================================
  window.appData = {
    loadData,
    saveData,
    getItemsByCollection,
    getEventsByCollection,
    getCollectionOwnerId,
    getCollectionOwner,
    linkItemToCollection,
    linkEventToCollection,
    addEntity,
    updateEntity,
    deleteEntity
  };
});

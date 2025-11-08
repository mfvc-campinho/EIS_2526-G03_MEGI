// ===============================================
// app-data.js — núcleo da aplicação
// ===============================================
// Gere todos os dados do localStorage (coleções, itens, eventos)
// e as relações muitos-para-muitos.
// ===============================================

document.addEventListener("DOMContentLoaded", () => {

  // 1️⃣ Inicializar dados (caso não existam)
  if (!localStorage.getItem("collectionsData")) {
    localStorage.setItem("collectionsData", JSON.stringify(collectionsData));
    console.log("✅ Dados iniciais guardados no localStorage.");
  } else {
    console.log("📦 Dados carregados do localStorage.");
  }

  // 2️⃣ Funções base
  function loadData() {
    return JSON.parse(localStorage.getItem("collectionsData"));
  }

  function saveData(data) {
    localStorage.setItem("collectionsData", JSON.stringify(data));
  }

  // 3️⃣ Ligações (coleção ↔ itens / eventos)
  function getItemsByCollection(collectionId) {
    const data = loadData();
    const rel = data.collectionItems.filter(r => r.collectionId === collectionId);
    return data.items.filter(i => rel.some(r => r.itemId === i.id));
  }

  function getEventsByCollection(collectionId) {
    const data = loadData();
    const rel = data.collectionEvents.filter(r => r.collectionId === collectionId);
    return data.events.filter(e => rel.some(r => r.eventId === e.id));
  }

  function linkItemToCollection(itemId, collectionId) {
    const data = loadData();
    const exists = data.collectionItems.some(r => r.itemId === itemId && r.collectionId === collectionId);
    if (!exists) {
      data.collectionItems.push({ itemId, collectionId });
      saveData(data);
    }
  }

  function linkEventToCollection(eventId, collectionId) {
    const data = loadData();
    const exists = data.collectionEvents.some(r => r.eventId === eventId && r.collectionId === collectionId);
    if (!exists) {
      data.collectionEvents.push({ eventId, collectionId });
      saveData(data);
    }
  }

  // 4️⃣ CRUD genérico
  function addEntity(type, entity) {
    const data = loadData();
    data[type].push(entity);
    saveData(data);
  }

  function updateEntity(type, id, newValues) {
    const data = loadData();
    const i = data[type].findIndex(e => e.id === id);
    if (i !== -1) {
      data[type][i] = { ...data[type][i], ...newValues };
      saveData(data);
    }
  }

  function deleteEntity(type, id) {
    const data = loadData();
    data[type] = data[type].filter(e => e.id !== id);
    if (type === "items")
      data.collectionItems = data.collectionItems.filter(r => r.itemId !== id);
    if (type === "events")
      data.collectionEvents = data.collectionEvents.filter(r => r.eventId !== id);
    saveData(data);
  }

  // 5️⃣ Tornar global
  window.appData = {
    loadData, saveData,
    getItemsByCollection, getEventsByCollection,
    linkItemToCollection, linkEventToCollection,
    addEntity, updateEntity, deleteEntity
  };
});

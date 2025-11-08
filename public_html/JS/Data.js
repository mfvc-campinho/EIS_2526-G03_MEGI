// ======================================================
// NEW collectionsData (Relational N ↔ N version)
// ======================================================
// Agora temos:
//
// collections[]       → só metadados
// items[]             → todos os itens soltos
// events[]            → todos os eventos soltos
// collectionItems[]   → relações N:N coleção ↔ item
// collectionEvents[]  → relações N:N coleção ↔ evento
//
// Baseado totalmente nos teus dados originais.
// ======================================================

const collectionsData = {
  collections: [
    {
      id: "escudos",
      name: "Portuguese Escudos",
      type: "Coins",
      owner: "Valentim Moureiro",
      coverImage: "../images/coins.png",
      summary: "A journey through Portugal’s historical coins.",
      createdAt: "2018-04-10",
      metrics: { votes: 120, userChosen: true, addedAt: "2025-10-15" }
    },
    {
      id: "playboys",
      name: "Portuguese Playboy Editions",
      type: "Coins",
      owner: "Rui Frio",
      coverImage: "../images/playboy.jpg",
      summary: "A collection of iconic Portuguese magazine editions.",
      createdAt: "2019-01-05",
      metrics: { votes: 95, userChosen: true, addedAt: "2025-10-22" }
    },
    {
      id: "pokemon",
      name: "Pokémon Trading Cards",
      type: "Coins",
      owner: "Cristina Sem Feira",
      coverImage: "../images/pikachu.jpg",
      summary: "Rare and classic cards from the Pokémon universe.",
      createdAt: "2021-04-20",
      metrics: { votes: 88, userChosen: false, addedAt: "2025-10-10" }
    },
    {
      id: "portraits",
      name: "Portraits of Historical Leaders",
      type: "Coins",
      owner: "Andre Fartura",
      coverImage: "../images/salazar.jpg",
      summary: "Historical register of controversial figures.",
      createdAt: "2017-02-02",
      metrics: { votes: 67, userChosen: false, addedAt: "2025-10-25" }
    },
    {
      id: "jerseys",
      name: "Autographed Football Jerseys",
      type: "Coins",
      owner: "Rui Tosta",
      coverImage: "../images/benfica.jpg",
      summary: "Signed memorabilia from legendary players.",
      createdAt: "2019-06-25",
      metrics: { votes: 105, userChosen: true, addedAt: "2025-10-05" }
    }
  ],

  // ======================================================
  // 🔶 ITENS (cada um agora tem ID único)
  // ======================================================
  items: [
    // escudos
    {
      id: "escudos-item-1",
      name: "1950 Escudo",
      importance: "High",
      weight: 4.5,
      price: 120,
      acquisitionDate: "2020-03-15",
      image: "../images/escudo1950.jpg"
    },
    {
      id: "escudos-item-2",
      name: "1960 Escudo",
      importance: "Very High",
      weight: 5.1,
      price: 250,
      acquisitionDate: "2021-07-10",
      image: "../images/escudo1960.jpg"
    },

    // playboys
    {
      id: "playboys-item-1",
      name: "Edition 2011",
      importance: "Medium",
      weight: 0.3,
      price: 5,
      acquisitionDate: "2019-05-22",
      image: "../images/playboy.jpg"
    },
    {
      id: "playboys-item-2",
      name: "Edition 1995",
      importance: "High",
      weight: 0.3,
      price: 12,
      acquisitionDate: "2020-11-05",
      image: "../images/lenka.jpg"
    },
    {
      id: "playboys-item-3",
      name: "Edition 2016",
      importance: "Low",
      weight: 0.3,
      price: 12,
      acquisitionDate: "2016-08-05",
      image: "../images/fabiana.jpg"
    },

    // pokemon
    {
      id: "pokemon-item-1",
      name: "Pikachu Base Set",
      importance: "Low",
      weight: 0.005,
      price: 150,
      acquisitionDate: "2021-06-18",
      image: "../images/pikachu.jpg"
    },
    {
      id: "pokemon-item-2",
      name: "Charizard Holo",
      importance: "Very High",
      weight: 0.005,
      price: 2000,
      acquisitionDate: "2022-04-22",
      image: "../images/charizard.jpg"
    },

    // portraits
    {
      id: "portraits-item-1",
      name: "Portrait of Salazar",
      importance: "Medium",
      weight: 2.4,
      price: 300,
      acquisitionDate: "2018-09-10",
      image: "../images/salazar.jpg"
    },

    // jerseys
    {
      id: "jerseys-item-1",
      name: "FC Porto 2004 Jersey",
      importance: "High",
      weight: 0.4,
      price: 450,
      acquisitionDate: "2020-03-12",
      image: "../images/porto.jpg"
    },
    {
      id: "jerseys-item-2",
      name: "Benfica 2010 Jersey",
      importance: "High",
      weight: 0.4,
      price: 400,
      acquisitionDate: "2021-09-03",
      image: "../images/benfica.jpg"
    }
  ],

  // ======================================================
  // 🔴 EVENTOS (cada um com ID único)
  // ======================================================
  events: [
    { id: "escudos-event-1", name: "Lisbon Numismatic Fair", localization: "Lisbon", date: "2025-03-22" },
    { id: "escudos-event-2", name: "Coin Acquisition Meetup", localization: "Porto", date: "2025-05-10" },

    { id: "playboys-event-1", name: "Vintage Magazine Exhibition", localization: "Lisbon", date: "2025-02-10" },

    { id: "pokemon-event-1", name: "Pokémon Expo 2025", localization: "Tokyo", date: "2025-03-10" },
    { id: "pokemon-event-2", name: "Trading Card Convention", localization: "London", date: "2025-05-01" },

    { id: "portraits-event-1", name: "Historical Exhibit Lisbon", localization: "Lisbon", date: "2025-01-15" },

    { id: "jerseys-event-1", name: "Autograph Session 2025", localization: "Porto Stadium", date: "2025-06-01" },
    { id: "jerseys-event-2", name: "Collectors’ Expo 2025", localization: "Lisbon", date: "2025-09-12" }
  ],

  // ======================================================
  // 🔷 RELAÇÃO N:N → coleção ↔ item
  // ======================================================
  collectionItems: [
    { collectionId: "escudos", itemId: "escudos-item-1" },
    { collectionId: "escudos", itemId: "escudos-item-2" },

    { collectionId: "playboys", itemId: "playboys-item-1" },
    { collectionId: "playboys", itemId: "playboys-item-2" },
    { collectionId: "playboys", itemId: "playboys-item-3" },

    { collectionId: "pokemon", itemId: "pokemon-item-1" },
    { collectionId: "pokemon", itemId: "pokemon-item-2" },

    { collectionId: "portraits", itemId: "portraits-item-1" },

    { collectionId: "jerseys", itemId: "jerseys-item-1" },
    { collectionId: "jerseys", itemId: "jerseys-item-2" }
  ],

  // ======================================================
  // 🔶 RELAÇÃO N:N → coleção ↔ evento
  // ======================================================
  collectionEvents: [
    { collectionId: "escudos", eventId: "escudos-event-1" },
    { collectionId: "escudos", eventId: "escudos-event-2" },

    { collectionId: "playboys", eventId: "playboys-event-1" },

    { collectionId: "pokemon", eventId: "pokemon-event-1" },
    { collectionId: "pokemon", eventId: "pokemon-event-2" },

    { collectionId: "portraits", eventId: "portraits-event-1" },

    { collectionId: "jerseys", eventId: "jerseys-event-1" },
    { collectionId: "jerseys", eventId: "jerseys-event-2" }
  ]
};

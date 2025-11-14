// ======================================================
// Data.js (with Relational)

// ======================================================


const collectionsData = {
  // ======================================================
  // COLLECTIONS
  // ======================================================
  collections: [
    {
      id: "escudos",
      name: "Portuguese Escudos",
      type: "Coins",
      coverImage: "../images/coins.png",
      summary: "A journey through Portugal’s historical coins.",
      description: "This collection showcases Portugal’s numismatic legacy, featuring original Escudo coins minted before the euro era. It highlights their unique designs, materials, and historical significance in the country’s economy.",
      createdAt: "2018-04-10"
    },
    {
      id: "playboys",
      name: "Portuguese Playboy Editions",
      type: "Magazines",
      coverImage: "../images/playboy.jpg",
      summary: "A collection of iconic Portuguese magazine editions.",
      description: "A curated archive of Portuguese Playboy issues spanning several decades, capturing evolving trends in media, design, and culture. Each edition represents a snapshot of its era’s aesthetics and editorial vision.",
      createdAt: "2019-01-05",
    },
    {
      id: "pokemon",
      name: "Pokémon Trading Cards",
      type: "Collectible Cards",
      coverImage: "../images/pikachu.jpg",
      summary: "Rare and classic cards from the Pokémon universe.",
      description: "A comprehensive Pokémon TCG collection featuring rare holographic cards, first editions, and limited releases from various generations. It celebrates both the nostalgic and competitive sides of Pokémon collecting.",
      createdAt: "2021-04-20",
    },
    {
      id: "portraits",
      name: "Portraits of Historical Leaders",
      type: "Art & History",
      coverImage: "../images/salazar.jpg",
      summary: "Historical register of controversial figures.",
      description: "An artistic and historical exploration through portraits of world leaders who shaped the 20th century. The collection invites reflection on power, influence, and the legacy of leadership in visual art.",
      createdAt: "2017-02-02",
    },
    {
      id: "jerseys",
      name: "Autographed Football Jerseys",
      type: "Sports Memorabilia",
      coverImage: "../images/benfica.jpg",
      summary: "Signed memorabilia from legendary players.",
      description: "An exclusive selection of football jerseys autographed by renowned athletes from Portuguese and international teams. Each item tells a story of sporting triumph, teamwork, and fan devotion.",
      createdAt: "2019-06-25",
    }
    ,
    {
      id: "escudos-gold",
      name: "Golden Escudos Vault",
      type: "Coins",
      coverImage: "../images/gold_coins.jpg",
      summary: "Handpicked gold Escudos from the monarchy to mid-century republic.",
      description: "Focuses on premium gold-minted Escudo coins, documenting mint marks, alloys, and historical context tied to Portugal's treasury reforms.",
      createdAt: "2020-11-18",
    }
  ],


  // ======================================================
  // USERS
  // ======================================================
  users: [
    {
      "owner-id": "collector-main",
      "owner-name": "Cristina Sem Feira",
      "owner-photo": "../images/cristina.jpg",
      "date-of-birth": "1985-05-20",
      "email": "collector.main@email.com",
      "password": "password123", // argument not used
      "member-since": "2015"
    },
    {
      "owner-id": "rui-frio",
      "owner-name": "Rui Frio",
      "owner-photo": "../images/cristina.jpg",
      "date-of-birth": "1982-07-14",
      "email": "rui.frio@email.com",
      "password": "password123", // argument not used
      "member-since": "2018"
    }
  ],


  // ======================================================
  // ITENS
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
      createdAt: "2020-03-01T00:00:00Z",
      updatedAt: "2020-03-15T00:00:00Z",
      image: "../images/escudo1950.jpg"
    },
    {
      id: "escudos-item-2",
      name: "1960 Escudo",
      importance: "Very High",
      weight: 5.1,
      price: 250,
      acquisitionDate: "2021-07-10",
      createdAt: "2021-06-20T00:00:00Z",
      updatedAt: "2021-07-10T00:00:00Z",
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
      createdAt: "2019-05-01T00:00:00Z",
      updatedAt: "2019-05-22T00:00:00Z",
      image: "../images/playboy.jpg"
    },
    {
      id: "playboys-item-2",
      name: "Edition 1995",
      importance: "High",
      weight: 0.3,
      price: 12,
      acquisitionDate: "2020-11-05",
      createdAt: "2020-10-15T00:00:00Z",
      updatedAt: "2020-11-05T00:00:00Z",
      image: "../images/lenka.jpg"
    },
    {
      id: "playboys-item-3",
      name: "Edition 2016",
      importance: "Low",
      weight: 0.3,
      price: 12,
      acquisitionDate: "2016-08-05",
      createdAt: "2016-07-20T00:00:00Z",
      updatedAt: "2016-08-05T00:00:00Z",
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
      createdAt: "2021-06-01T00:00:00Z",
      updatedAt: "2021-06-18T00:00:00Z",
      image: "../images/pikachuset.JPG"
    },
    {
      id: "pokemon-item-2",
      name: "Charizard Holo",
      importance: "Very High",
      weight: 0.005,
      price: 2000,
      acquisitionDate: "2022-04-22",
      createdAt: "2022-03-30T00:00:00Z",
      updatedAt: "2022-04-22T00:00:00Z",
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
      createdAt: "2018-08-25T00:00:00Z",
      updatedAt: "2018-09-10T00:00:00Z",
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
      createdAt: "2020-02-20T00:00:00Z",
      updatedAt: "2020-03-12T00:00:00Z",
      image: "../images/porto.jpg"
    },
    {
      id: "jerseys-item-2",
      name: "Benfica 2010 Jersey",
      importance: "High",
      weight: 0.4,
      price: 400,
      acquisitionDate: "2021-09-03",
      createdAt: "2021-08-10T00:00:00Z",
      updatedAt: "2021-09-03T00:00:00Z",
      image: "../images/benfica.jpg"
    }
  ],

  // ======================================================
  // EVENTS
  // ======================================================
  events: [
    {
      id: "escudos-event-1",
      name: "Lisbon Numismatic Fair",
      localization: "Lisbon",
      date: "2025-12-12",
      type: "fair",
      summary: "Annual showcase for Iberian coins, rare notes, and appraisal sessions.",
      description: "Dealers and historians gather to trade Escudo-era coins, host restoration demos, and discuss preservation techniques for metallic currencies.",
      createdAt: "2025-09-01T00:00:00Z",
      updatedAt: "2025-11-20T00:00:00Z",
      hostId: "collector-main",
    },
    {
      id: "escudos-event-2",
      name: "Coin Acquisition Meetup",
      localization: "Porto",
      date: "2025-05-10",
      type: "meetup",
      summary: "Small-group meetup focused on sourcing missing Escudo variants.",
      description: "Collectors swap duplicates, share leads for reputable sellers, and review authentication tips for mid-century Portuguese currency.",
      createdAt: "2025-02-15T00:00:00Z",
      updatedAt: "2025-04-15T00:00:00Z",
      hostId: "collector-main",
    },

    {
      id: "playboys-event-1",
      name: "Vintage Magazine Exhibition",
      localization: "Lisbon",
      date: "2025-02-10",
      type: "exhibition",
      summary: "Curated wall display covering the evolution of Portuguese Playboy layouts.",
      description: "Graphic designers and cultural historians walk through iconic spreads, cover redesigns, and interviews with former editorial staff.",
      createdAt: "2024-11-05T00:00:00Z",
      updatedAt: "2025-01-05T00:00:00Z",
      hostId: "rui-frio",
    },

    {
      id: "pokemon-event-1",
      name: "Pokémon Expo 2025",
      localization: "Tokyo",
      date: "2025-03-10",
      type: "expo",
      summary: "Global expo highlighting competitive decks and newly graded grails.",
      description: "Includes PSA grading booths, artist signings, and a showcase of legendary cards from the Kanto through Paldea releases.",
      createdAt: "2024-12-01T00:00:00Z",
      updatedAt: "2025-02-01T00:00:00Z",
      hostId: "rui-frio",
    },
    {
      id: "pokemon-event-2",
      name: "Trading Card Convention",
      localization: "London",
      date: "2025-05-01",
      type: "convention",
      summary: "European convention dedicated to rare pulls, auctions, and live trades.",
      description: "Vendors curate showcase cases for first editions, while panels cover long-term storage, pricing data, and authenticity checks.",
      createdAt: "2025-01-20T00:00:00Z",
      updatedAt: "2025-03-15T00:00:00Z",
      hostId: "rui-frio",
    },

    {
      id: "portraits-event-1",
      name: "Historical Exhibit Lisbon",
      localization: "Lisbon",
      date: "2025-01-15",
      type: "gallery",
      summary: "Gallery event reflecting on political portraiture and its narratives.",
      description: "Art critics discuss brush techniques, symbolism, and the tension between propaganda and documentation in 20th-century leadership portraits.",
      createdAt: "2024-10-10T00:00:00Z",
      updatedAt: "2024-12-12T00:00:00Z",
      hostId: "rui-frio",
    },

    {
      id: "jerseys-event-1",
      name: "Autograph Session 2025",
      localization: "Porto Stadium",
      date: "2025-06-01",
      type: "signing",
      summary: "Pitch-side autograph session with national league legends.",
      description: "Participants bring authenticated jerseys for signatures, while equipment managers talk about fabric care for long-term display.",
      createdAt: "2025-02-28T00:00:00Z",
      updatedAt: "2025-04-30T00:00:00Z",
      hostId: "rui-frio",
    },
    {
      id: "jerseys-event-2",
      name: "Collectors’ Expo 2025",
      localization: "Lisbon",
      date: "2025-09-12",
      type: "expo",
      summary: "Large expo covering game-worn memorabilia and restoration services.",
      description: "Workshops detail how to certify match-used kits, remove stains without damaging signatures, and insure valuable memorabilia.",
      createdAt: "2025-05-05T00:00:00Z",
      updatedAt: "2025-07-18T00:00:00Z",
      host: "Liga Memorabilia",
    }
  ],

  // ======================================================
  // RELATIONSHIP N:N → COLLECTIONS ↔ ITEMS
  // ======================================================
  collectionItems: [
    { collectionId: "escudos", itemId: "escudos-item-1" },
    { collectionId: "escudos", itemId: "escudos-item-2" },
    { collectionId: "escudos-gold", itemId: "escudos-item-1" },

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
  // RELATIONSHIP N:N → COLLLECTIONS ↔ EVENTS
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
  ],

  // ======================================================
  // RELATIONSHIP N:N – EVENTS ↔ USERS
  // ======================================================
  eventsUsers: [
    { eventId: "escudos-event-1", userId: "collector-main", rating: 5 },
    { eventId: "escudos-event-1", userId: "rui-frio", rating: null },

    { eventId: "escudos-event-2", userId: "collector-main", rating: null },

    { eventId: "playboys-event-1", userId: "rui-frio", rating: 4 },

    { eventId: "pokemon-event-1", userId: "collector-main", rating: 5 },
    { eventId: "pokemon-event-1", userId: "rui-frio", rating: 5 },

    { eventId: "pokemon-event-2", userId: "rui-frio", rating: null },

    { eventId: "portraits-event-1", userId: "rui-frio", rating: 3 },

    { eventId: "jerseys-event-1", userId: "rui-frio", rating: null }
  ],
  // ======================================================
  // RELATIONSHIP N:1 COLLECTIONS ↔ USERS
  // ======================================================
  collectionsUsers: [
    { collectionId: "escudos", ownerId: "collector-main" },
    { collectionId: "escudos-gold", ownerId: "collector-main" },
    { collectionId: "playboys", ownerId: "rui-frio" },
    { collectionId: "pokemon", ownerId: "rui-frio" },
    { collectionId: "portraits", ownerId: "rui-frio" },
    { collectionId: "jerseys", ownerId: "rui-frio" }
  ],

  // ======================================================
  // SHOWCASES
  // ======================================================
  userShowcases: [
      {
        ownerId: "collector-main",
        lastUpdated: "2025-09-30T00:00:00Z", // not used yet, future ready
        picks: [
          { collectionId: "escudos", order: 1 },
        { collectionId: "escudos-gold", order: 2 },
        { collectionId: "playboys", order: 3 },
        { collectionId: "pokemon", order: 4 },
        { collectionId: "jerseys", order: 5 }
        ],
        likes: ["escudos", "escudos-gold", "playboys", "jerseys"],
        likedItems: [
          "escudos-item-1",
          "escudos-item-2",
          "playboys-item-2",
          "pokemon-item-1",
          "jerseys-item-1"
        ]
      },
      {
        ownerId: "rui-frio",
        lastUpdated: "2025-08-12T00:00:00Z",
        picks: [
        { collectionId: "pokemon", order: 1 },
        { collectionId: "portraits", order: 2 },
          { collectionId: "jerseys", order: 3 }
        ],
        likes: ["pokemon", "jerseys", "escudos"],
        likedItems: [
          "pokemon-item-1",
          "pokemon-item-2",
          "portraits-item-1",
          "jerseys-item-2"
        ]
      }
    ]
  };

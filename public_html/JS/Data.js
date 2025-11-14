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
      id: "escudosgold",
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
      "owner-id": "collectormain",
      "owner-name": "Cristina Sem Feira",
      "owner-photo": "../images/cristina.jpg",
      "date-of-birth": "1985-05-20",
      "email": "collector.main@email.com",
      "password": "password123", // argument not used
      "member-since": "2015-01-01"
    },
    {
      "owner-id": "ruifrio",
      "owner-name": "Rui Frio",
      "owner-photo": "../images/cristina.jpg",
      "date-of-birth": "1982-07-14",
      "email": "rui.frio@email.com",
      "password": "password123", // argument not used
      "member-since": "2018-01-01"
    }
  ],


  // ======================================================
  // ITENS
  // ======================================================
  items: [
    // escudos
    {
      id: "escudositem1",
      name: "1950 Escudo",
      importance: "High",
      weight: 4.5,
      price: 120,
      acquisitionDate: "2020-03-15",
      createdAt: "2020-03-01",
      updatedAt: "2020-03-15",
      image: "../images/escudo1950.jpg"
    },
    {
      id: "escudositem2",
      name: "1960 Escudo",
      importance: "Very High",
      weight: 5.1,
      price: 250,
      acquisitionDate: "2021-07-10",
      createdAt: "2021-06-20",
      updatedAt: "2021-07-10",
      image: "../images/escudo1960.jpg"
    },

    // playboys
    {
      id: "playboysitem1",
      name: "Edition 2011",
      importance: "Medium",
      weight: 0.3,
      price: 5,
      acquisitionDate: "2019-05-22",
      createdAt: "2019-05-01",
      updatedAt: "2019-05-22",
      image: "../images/playboy.jpg"
    },
    {
      id: "playboysitem2",
      name: "Edition 1995",
      importance: "High",
      weight: 0.3,
      price: 12,
      acquisitionDate: "2020-11-05",
      createdAt: "2020-10-15",
      updatedAt: "2020-11-05",
      image: "../images/lenka.jpg"
    },
    {
      id: "playboysitem3",
      name: "Edition 2016",
      importance: "Low",
      weight: 0.3,
      price: 12,
      acquisitionDate: "2016-08-05",
      createdAt: "2016-07-20",
      updatedAt: "2016-08-05",
      image: "../images/fabiana.jpg"
    },

    // pokemon
    {
      id: "pokemonitem1",
      name: "Pikachu Base Set",
      importance: "Low",
      weight: 0.005,
      price: 150,
      acquisitionDate: "2021-06-18",
      createdAt: "2021-06-01",
      updatedAt: "2021-06-18",
      image: "../images/pikachuset.JPG"
    },
    {
      id: "pokemonitem2",
      name: "Charizard Holo",
      importance: "Very High",
      weight: 0.005,
      price: 2000,
      acquisitionDate: "2022-04-22",
      createdAt: "2022-03-30",
      updatedAt: "2022-04-22",
      image: "../images/charizard.jpg"
    },

    // portraits
    {
      id: "portraitsitem1",
      name: "Portrait of Salazar",
      importance: "Medium",
      weight: 2.4,
      price: 300,
      acquisitionDate: "2018-09-10",
      createdAt: "2018-08-25",
      updatedAt: "2018-09-10",
      image: "../images/salazar.jpg"
    },

    // jerseys
    {
      id: "jerseysitem1",
      name: "FC Porto 2004 Jersey",
      importance: "High",
      weight: 0.4,
      price: 450,
      acquisitionDate: "2020-03-12",
      createdAt: "2020-02-20",
      updatedAt: "2020-03-12",
      image: "../images/porto.jpg"
    },
    {
      id: "jerseysitem2",
      name: "Benfica 2010 Jersey",
      importance: "High",
      weight: 0.4,
      price: 400,
      acquisitionDate: "2021-09-03",
      createdAt: "2021-08-10",
      updatedAt: "2021-09-03",
      image: "../images/benfica.jpg"
    }
  ],

  // ======================================================
  // EVENTS
  // ======================================================
  events: [
    {
      id: "escudosevent1",
      name: "Lisbon Numismatic Fair",
      localization: "Lisbon",
      date: "2025-12-12",
      type: "fair",
      summary: "Annual showcase for Iberian coins, rare notes, and appraisal sessions.",
      description: "Dealers and historians gather to trade Escudo-era coins, host restoration demos, and discuss preservation techniques for metallic currencies.",
      createdAt: "2025-09-01",
      updatedAt: "2025-11-20",
      hostId: "collectormain",
    },
    {
      id: "escudosevent2",
      name: "Coin Acquisition Meetup",
      localization: "Porto",
      date: "2025-05-10",
      type: "meetup",
      summary: "Small-group meetup focused on sourcing missing Escudo variants.",
      description: "Collectors swap duplicates, share leads for reputable sellers, and review authentication tips for mid-century Portuguese currency.",
      createdAt: "2025-02-15",
      updatedAt: "2025-04-15",
      hostId: "collectormain",
    },

    {
      id: "playboysevent1",
      name: "Vintage Magazine Exhibition",
      localization: "Lisbon",
      date: "2025-02-10",
      type: "exhibition",
      summary: "Curated wall display covering the evolution of Portuguese Playboy layouts.",
      description: "Graphic designers and cultural historians walk through iconic spreads, cover redesigns, and interviews with former editorial staff.",
      createdAt: "2024-11-05",
      updatedAt: "2025-01-05",
      hostId: "ruifrio",
    },

    {
      id: "pokemonevent1",
      name: "Pokémon Expo 2025",
      localization: "Tokyo",
      date: "2025-03-10",
      type: "expo",
      summary: "Global expo highlighting competitive decks and newly graded grails.",
      description: "Includes PSA grading booths, artist signings, and a showcase of legendary cards from the Kanto through Paldea releases.",
      createdAt: "2024-12-01",
      updatedAt: "2025-02-01",
      hostId: "ruifrio",
    },
    {
      id: "pokemonevent2",
      name: "Trading Card Convention",
      localization: "London",
      date: "2025-05-01",
      type: "convention",
      summary: "European convention dedicated to rare pulls, auctions, and live trades.",
      description: "Vendors curate showcase cases for first editions, while panels cover long-term storage, pricing data, and authenticity checks.",
      createdAt: "2025-01-20",
      updatedAt: "2025-03-15",
      hostId: "ruifrio",
    },

    {
      id: "portraitsevent1",
      name: "Historical Exhibit Lisbon",
      localization: "Lisbon",
      date: "2025-01-15",
      type: "gallery",
      summary: "Gallery event reflecting on political portraiture and its narratives.",
      description: "Art critics discuss brush techniques, symbolism, and the tension between propaganda and documentation in 20th-century leadership portraits.",
      createdAt: "2024-10-10",
      updatedAt: "2024-12-12",
      hostId: "ruifrio",
    },

    {
      id: "jerseysevent1",
      name: "Autograph Session 2025",
      localization: "Porto Stadium",
      date: "2025-06-01",
      type: "signing",
      summary: "Pitch-side autograph session with national league legends.",
      description: "Participants bring authenticated jerseys for signatures, while equipment managers talk about fabric care for long-term display.",
      createdAt: "2025-02-28",
      updatedAt: "2025-04-30",
      hostId: "ruifrio",
    },
    {
      id: "jerseysevent2",
      name: "Collectors’ Expo 2025",
      localization: "Lisbon",
      date: "2025-09-12",
      type: "expo",
      summary: "Large expo covering game-worn memorabilia and restoration services.",
      description: "Workshops detail how to certify match-used kits, remove stains without damaging signatures, and insure valuable memorabilia.",
      createdAt: "2025-05-05",
      updatedAt: "2025-07-18",
      host: "Liga Memorabilia",
    }
  ],

  // ======================================================
  // RELATIONSHIP N:N → COLLECTIONS ↔ ITEMS
  // ======================================================
  collectionItems: [
    { collectionId: "escudos", itemId: "escudositem1" },
    { collectionId: "escudos", itemId: "escudositem2" },
    { collectionId: "escudosgold", itemId: "escudositem1" },

    { collectionId: "playboys", itemId: "playboysitem1" },
    { collectionId: "playboys", itemId: "playboysitem2" },
    { collectionId: "playboys", itemId: "playboysitem3" },

    { collectionId: "pokemon", itemId: "pokemonitem1" },
    { collectionId: "pokemon", itemId: "pokemonitem2" },

    { collectionId: "portraits", itemId: "portraitsitem1" },

    { collectionId: "jerseys", itemId: "jerseysitem1" },
    { collectionId: "jerseys", itemId: "jerseysitem2" }
  ],

  // ======================================================
  // RELATIONSHIP N:N → COLLLECTIONS ↔ EVENTS
  // ======================================================
  collectionEvents: [
    { collectionId: "escudos", eventId: "escudosevent1" },
    { collectionId: "escudos", eventId: "escudosevent2" },

    { collectionId: "playboys", eventId: "playboysevent1" },

    { collectionId: "pokemon", eventId: "pokemonevent1" },
    { collectionId: "pokemon", eventId: "pokemonevent2" },

    { collectionId: "portraits", eventId: "portraitsevent1" },

    { collectionId: "jerseys", eventId: "jerseysevent1" },
    { collectionId: "jerseys", eventId: "jerseysevent2" }
  ],

  // ======================================================
  // RELATIONSHIP N:N – EVENTS ↔ USERS
  // ======================================================
  eventsUsers: [
    { eventId: "escudosevent1", userId: "collectormain", rating: 5 },
    { eventId: "escudosevent1", userId: "ruifrio", rating: null },

    { eventId: "escudosevent2", userId: "collectormain", rating: null },

    { eventId: "playboysevent1", userId: "ruifrio", rating: 4 },

    { eventId: "pokemonevent1", userId: "collectormain", rating: 5 },
    { eventId: "pokemonevent1", userId: "ruifrio", rating: 5 },

    { eventId: "pokemonevent2", userId: "ruifrio", rating: null },

    { eventId: "portraitsevent1", userId: "ruifrio", rating: 3 },

    { eventId: "jerseysevent1", userId: "ruifrio", rating: null }
  ],
  // ======================================================
  // RELATIONSHIP N:1 COLLECTIONS ↔ USERS
  // ======================================================
  collectionsUsers: [
    { collectionId: "escudos", ownerId: "collectormain" },
    { collectionId: "escudosgold", ownerId: "collectormain" },
    { collectionId: "playboys", ownerId: "ruifrio" },
    { collectionId: "pokemon", ownerId: "ruifrio" },
    { collectionId: "portraits", ownerId: "ruifrio" },
    { collectionId: "jerseys", ownerId: "ruifrio" }
  ],

  // ======================================================
  // SHOWCASES
  // ======================================================
  userShowcases: [
      {
        ownerId: "collectormain",
        picks: [
          { collectionId: "escudos", order: 1 },
        { collectionId: "escudosgold", order: 2 },
        { collectionId: "playboys", order: 3 },
        { collectionId: "pokemon", order: 4 },
        { collectionId: "jerseys", order: 5 }
        ],
        likes: ["escudos", "escudosgold", "playboys", "jerseys"],
        likedItems: [
          "escudositem1",
          "escudositem2",
          "playboysitem2",
          "pokemonitem1",
          "jerseysitem1"
        ]
      },
      {
        ownerId: "ruifrio",
        picks: [
        { collectionId: "pokemon", order: 1 },
        { collectionId: "portraits", order: 2 },
          { collectionId: "jerseys", order: 3 }
        ],
        likes: ["pokemon", "jerseys", "escudos"],
        likedItems: [
          "pokemonitem1",
          "pokemonitem2",
          "portraitsitem1",
          "jerseysitem2"
        ]
      }
    ]
  };

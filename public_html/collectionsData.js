(() => {
  const STORAGE_KEY = 'collectionsStore.v1';

  const defaultCollections = {
    escudos: {
      id: 'escudos',
      name: 'Escudos Portugueses',
      owner: 'Valentim Moureiro',
      coverImage: '../images/coins.png',
      summary: 'Uma viagem as moedas historicas nacionais.',
      createdAt: '2018-04-10',
      metrics: {
        votes: 120,
        userChosen: true,
        added: '2025-10-15'
      },
      items: [
        {
          title: 'Escudo de 1950',
          description: 'Moeda historica de prata.',
          value: 120,
          dateAdded: '2020-03-15',
          rarity: 'Raro',
          condition: 'Muito Bom',
          image: '../images/escudo1950.jpg'
        },
        {
          title: 'Escudo de 1960',
          description: 'Moeda comemorativa rara.',
          value: 250,
          dateAdded: '2021-07-10',
          rarity: 'Muito Raro',
          condition: 'Excelente',
          image: '../images/escudo1960.jpg'
        }
      ]
    },
    playboys: {
      id: 'playboys',
      name: 'Playboys Portuguesas',
      owner: 'Rui Frio',
      coverImage: '../images/playboy.jpg',
      summary: 'Colecao de edicoes iconicas portuguesas.',
      createdAt: '2019-01-05',
      metrics: {
        votes: 95,
        userChosen: true,
        added: '2025-10-22'
      },
      items: [
        {
          title: 'Edicao 2011',
          description: 'Capa Rita Pereira',
          value: 5,
          dateAdded: '2019-05-22',
          rarity: 'Raro',
          condition: 'Bom',
          image: '../images/playboy.jpg'
        },
        {
          title: 'Edicao 1995',
          description: 'Capa Lenka da Silva',
          value: 12,
          dateAdded: '2020-11-05',
          rarity: 'Muito Raro',
          condition: 'Excelente',
          image: '../images/lenka.jpg'
        },
        {
          title: 'Edicao 2016',
          description: 'Capa Fabiana Brito',
          value: 12,
          dateAdded: '2016-08-05',
          rarity: 'Comum',
          condition: 'Mau',
          image: '../images/fabiana.jpg'
        }
      ]
    },
    pokemon: {
      id: 'pokemon',
      name: 'Cartas de Pokemon',
      owner: 'Cristina Sem Feira',
      coverImage: '../images/pikachu.jpg',
      summary: 'Cartas raras e classicas da franquia.',
      createdAt: '2021-04-20',
      metrics: {
        votes: 88,
        userChosen: false,
        added: '2025-10-10'
      },
      items: [
        {
          title: 'Pikachu Base Set',
          description: 'Carta classica de 1999.',
          value: 150,
          dateAdded: '2021-06-18',
          rarity: 'Comum',
          condition: 'Excelente',
          image: '../images/pikachu.jpg'
        },
        {
          title: 'Charizard Holo',
          description: 'Edicao rara de primeira geracao.',
          value: 2000,
          dateAdded: '2022-04-22',
          rarity: 'Muito Raro',
          condition: 'Excelente',
          image: '../images/charizard.jpg'
        }
      ]
    },
    retratos: {
      id: 'retratos',
      name: 'Retratos de Lideres Fascistas',
      owner: 'Andre Fartura',
      coverImage: '../images/salazar.jpg',
      summary: 'Registo historico de figuras controversas.',
      createdAt: '2017-02-02',
      metrics: {
        votes: 67,
        userChosen: false,
        added: '2025-10-25'
      },
      items: [
        {
          title: 'Retrato de Salazar',
          description: 'Pintura a oleo datada de 1940.',
          value: 300,
          dateAdded: '2018-09-10',
          rarity: 'Raro',
          condition: 'Bom',
          image: '../images/salazar.jpg'
        }
      ]
    },
    camisolas: {
      id: 'camisolas',
      name: 'Camisolas Autografadas',
      owner: 'Rui Tosta',
      coverImage: '../images/porto.jpg',
      summary: 'Reliquias assinadas por jogadores lendarios.',
      createdAt: '2019-06-25',
      metrics: {
        votes: 105,
        userChosen: true,
        added: '2025-10-05'
      },
      items: [
        {
          title: 'Camisola FC Porto 2004',
          description: 'Autografada por Deco e Ricardo Carvalho.',
          value: 450,
          dateAdded: '2020-03-12',
          rarity: 'Raro',
          condition: 'Excelente',
          image: '../images/porto.jpg'
        },
        {
          title: 'Camisola Benfica 2010',
          description: 'Autografada por Aimar e Cardozo.',
          value: 400,
          dateAdded: '2021-09-03',
          rarity: 'Raro',
          condition: 'Muito Bom',
          image: '../images/benfica.jpg'
        }
      ]
    }
  };

  const clone = (value) => JSON.parse(JSON.stringify(value));

  function loadFromStorage() {
    try {
      const data = localStorage.getItem(STORAGE_KEY);
      if (!data) {
        return clone(defaultCollections);
      }
      const parsed = JSON.parse(data);
      return parsed ?? clone(defaultCollections);
    } catch (error) {
      console.warn('Falha ao carregar colecoes, a repor valores base.', error);
      return clone(defaultCollections);
    }
  }

  function saveToStorage(state) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    } catch (error) {
      console.error('Falha ao guardar colecoes.', error);
    }
  }

  function generateIdFromName(name, existingIds) {
    const base =
      name
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '') || 'colecao';

    let candidate = base;
    let counter = 1;
    while (existingIds.has(candidate)) {
      candidate = `${base}-${counter++}`;
    }
    return candidate;
  }

  let collections = loadFromStorage();

  const store = {
    getAll() {
      return Object.values(collections);
    },
    getMap() {
      return collections;
    },
    getById(id) {
      return collections[id] ?? null;
    },
    addCollection({ name, owner = 'Colecionador desconhecido', coverImage = '../images/coins.png' }) {
      const trimmedName = name?.trim();
      if (!trimmedName) {
        throw new Error('O nome da colecao e obrigatorio.');
      }
      const ids = new Set(Object.keys(collections));
      const id = generateIdFromName(trimmedName, ids);
      const now = new Date();
      const today = now.toISOString().split('T')[0];
      collections[id] = {
        id,
        name: trimmedName,
        owner: owner?.trim() || 'Colecionador desconhecido',
        coverImage,
        summary: 'Colecao ainda sem descricao.',
        createdAt: today,
        metrics: {
          votes: 0,
          userChosen: false,
          added: today
        },
        items: []
      };
      saveToStorage(collections);
      return collections[id];
    },
    updateCollection(id, partial) {
      if (!collections[id]) return null;
      collections[id] = {
        ...collections[id],
        ...partial,
        metrics: {
          ...collections[id].metrics,
          ...(partial?.metrics ?? {})
        }
      };
      saveToStorage(collections);
      return collections[id];
    },
    updateCollectionItems(id, items = []) {
      if (!collections[id]) return null;
      collections[id].items = Array.isArray(items) ? [...items] : [];
      saveToStorage(collections);
      return collections[id];
    },
    resetToDefault() {
      collections = clone(defaultCollections);
      saveToStorage(collections);
      return store.getAll();
    }
  };

  window.collectionsStore = store;
})();

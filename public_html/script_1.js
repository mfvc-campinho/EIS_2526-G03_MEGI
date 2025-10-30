document.addEventListener('DOMContentLoaded', () => {
  const store = window.collectionsStore;
  const collectionsRow = document.getElementById('collectionsRow');
  const collectionsDropdown = document.getElementById('collectionsDropdown');

  if (collectionsRow && collectionsDropdown) {
    if (!store) {
      console.error('Colections store indisponivel.');
      return;
    }

    const fallbackImage = '../images/coins.png';

    const renderCollections = () => {
      const collections = store.getAll();
      collectionsRow.innerHTML = '';
      collectionsDropdown.innerHTML = '';

      if (!collections.length) {
        const empty = document.createElement('p');
        empty.className = 'empty-state';
        empty.textContent = 'Ainda nao existem colecoes.';
        collectionsRow.appendChild(empty);
        return;
      }

      collections.forEach((collection) => {
        const imageSrc = collection.coverImage || fallbackImage;
        const card = document.createElement('article');
        card.className = 'collection-card';
        card.id = `collection-${collection.id}`;
        const itemCount = Array.isArray(collection.items) ? collection.items.length : 0;

        card.innerHTML = `
          <div class="card-image">
            <img src="${imageSrc}" alt="${collection.name}" />
          </div>
          <div class="card-info">
            <h3>${collection.name}</h3>
            <p>${collection.owner}</p>
            <p class="collection-summary">${collection.summary || ''}</p>
            <p class="collection-meta">${itemCount} itens</p>
            <button class="explore-btn" data-id="${collection.id}">Explore More</button>
          </div>
        `;

        const button = card.querySelector('.explore-btn');
        button.addEventListener('click', () => {
          window.location.href = `specific_collection.html?id=${collection.id}`;
        });

        collectionsRow.appendChild(card);

        const link = document.createElement('a');
        link.href = `#collection-${collection.id}`;
        link.dataset.id = collection.id;
        link.className = 'jump-to';
        link.textContent = collection.name;
        link.addEventListener('click', (event) => {
          event.preventDefault();
          card.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
        collectionsDropdown.appendChild(link);
      });
    };

    renderCollections();

    window.addEventListener('storage', (event) => {
      if (event.key === 'collectionsStore.v1') {
        renderCollections();
      }
    });

    return;
  }

  const titleEl = document.getElementById('collection-title');
  if (!titleEl || !store) {
    return;
  }

  const params = new URLSearchParams(window.location.search);
  const id = params.get('id');
  const collection = id ? store.getById(id) : null;

  const itemsContainer = document.getElementById('collection-items');
  const ownerName = document.getElementById('owner-name');
  const ownerPhoto = document.getElementById('owner-photo');
  const creationDate = document.getElementById('creation-date');
  const modal = document.getElementById('item-modal');
  const openBtn = document.getElementById('add-item');
  const closeBtn = document.getElementById('close-modal');
  const cancelBtn = document.getElementById('cancel-modal');
  const form = document.getElementById('item-form');

  if (!collection || !itemsContainer || !ownerName || !ownerPhoto || !creationDate || !modal || !form) {
    titleEl.textContent = 'Colecao nao encontrada.';
    if (openBtn) openBtn.disabled = true;
    return;
  }

  titleEl.textContent = collection.name;
  ownerName.textContent = collection.owner;
  creationDate.textContent = collection.createdAt || 'Data desconhecida';

  const fallbackOwnerPhoto = collection.coverImage || '../images/coins.png';
  ownerPhoto.src = collection.ownerPhoto || fallbackOwnerPhoto;

  let items = Array.isArray(collection.items) ? [...collection.items] : [];

  const persistItems = () => {
    store.updateCollectionItems(collection.id, items);
    items = [...(store.getById(collection.id)?.items ?? [])];
  };

  const migrateLegacyItems = () => {
    try {
      const legacyKey = `collection_${collection.id}`;
      const legacy = localStorage.getItem(legacyKey);
      if (!legacy) return;
      const parsed = JSON.parse(legacy);
      if (!Array.isArray(parsed) || !parsed.length) return;
      items = parsed;
      persistItems();
    } catch (error) {
      console.warn('Falha ao migrar itens antigos.', error);
    }
  };

  migrateLegacyItems();

  const renderCollection = () => {
    items.sort((a, b) => (Number(b.value) || 0) - (Number(a.value) || 0));
    itemsContainer.innerHTML = '';

    if (!items.length) {
      const empty = document.createElement('p');
      empty.className = 'empty-state';
      empty.textContent = 'Ainda nao existem itens nesta colecao.';
      itemsContainer.appendChild(empty);
      return;
    }

    const topValue = Number(items[0]?.value) || 0;

    items.forEach((item, index) => {
      const card = document.createElement('div');
      card.className = 'item-card';
      if (Number(item.value) === topValue && index === 0) {
        card.classList.add('premium-item');
      }

      const formattedValue = Number(item.value) || 0;
      const formattedDate = item.dateAdded || 'N/A';
      const formattedRarity = item.rarity || 'N/A';
      const formattedCondition = item.condition || 'N/A';

      card.innerHTML = `
        <div class="item-image-wrapper">
          <img src="${item.image || 'images/default.jpg'}" alt="${item.title}" class="item-image"/>
        </div>
        <div class="item-info">
          <h4>${item.title}</h4>
          <p>${item.description}</p>
          <ul>
            <li><strong>Valor:</strong> &euro;${formattedValue}</li>
            <li><strong>Data adicionada:</strong> ${formattedDate}</li>
            <li><strong>Raridade:</strong> ${formattedRarity}</li>
            <li><strong>Condicao:</strong> ${formattedCondition}</li>
          </ul>
          <div class="item-buttons">
            <button class="edit-btn">Editar</button>
            <button class="remove-btn">Remover</button>
          </div>
        </div>
      `;

      const removeBtn = card.querySelector('.remove-btn');
      const editBtn = card.querySelector('.edit-btn');

      removeBtn.addEventListener('click', () => {
        if (confirm('Remover este item?')) {
          items.splice(index, 1);
          persistItems();
          renderCollection();
        }
      });

      editBtn.addEventListener('click', () => openModal(index));

      itemsContainer.appendChild(card);
    });
  };

  let editIndex = null;

  const openModal = (index = null) => {
    modal.style.display = 'flex';
    editIndex = index;
    form.reset();

    const isEditing = index !== null;
    document.getElementById('modal-title').textContent = isEditing ? 'Editar Item' : 'Adicionar Item';

    if (isEditing) {
      const item = items[index];
      form.querySelector('#item-title').value = item.title || '';
      form.querySelector('#item-description').value = item.description || '';
      form.querySelector('#item-image').value = item.image || '';
      form.querySelector('#item-value').value = item.value ?? '';
      form.querySelector('#item-date').value = item.dateAdded || '';
      form.querySelector('#item-rarity').value = item.rarity || '';
      form.querySelector('#item-condition').value = item.condition || '';
    }
  };

  const closeModal = () => {
    modal.style.display = 'none';
    editIndex = null;
  };

  openBtn?.addEventListener('click', () => openModal());
  closeBtn?.addEventListener('click', closeModal);
  cancelBtn?.addEventListener('click', closeModal);

  window.addEventListener('click', (event) => {
    if (event.target === modal) {
      closeModal();
    }
  });

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    const newItem = {
      title: form.querySelector('#item-title').value,
      description: form.querySelector('#item-description').value,
      image: form.querySelector('#item-image').value,
      value: Number(form.querySelector('#item-value').value) || 0,
      dateAdded: form.querySelector('#item-date').value,
      rarity: form.querySelector('#item-rarity').value,
      condition: form.querySelector('#item-condition').value
    };

    if (editIndex !== null) {
      items[editIndex] = newItem;
    } else {
      items.push(newItem);
    }

    persistItems();
    renderCollection();
    closeModal();
  });

  renderCollection();
});


document.addEventListener('DOMContentLoaded', () => {
  const gotoButton = document.getElementById('goto-change-state-button');
  if (gotoButton) {
    gotoButton.addEventListener('click', () => {
      window.location.replace('index.html');
    });
  }

  const userIndicator = document.getElementById('user-indicator');
  const userRole = sessionStorage.getItem('userRole');
  if (userIndicator) {
    userIndicator.textContent = userRole === 'C' ? 'Collector' : 'Guest';
  }

  const addBtn = document.getElementById('colectionAdd');
  const editBtn = document.getElementById('colectionEdit');
  const deleteBtn = document.getElementById('colectionDelete');
  if (addBtn && editBtn && deleteBtn) {
    if (userRole === 'C') {
      addBtn.style.display = 'inline-block';
      editBtn.style.display = 'inline-block';
      deleteBtn.style.display = 'inline-block';
    } else {
      addBtn.style.display = 'none';
      editBtn.style.display = 'none';
      deleteBtn.style.display = 'none';
    }
  }

  const store = window.collectionsStore;
  const filter = document.getElementById('rankingFilter');
  const container = document.getElementById('homeCollections');

  if (!store || !filter || !container) {
    console.warn('Colections store indisponivel na homepage.');
    return;
  }

  function createCard(collection, position) {
    const article = document.createElement('article');
    article.className = 'collection-card';
    article.dataset.id = collection.id;
    article.dataset.name = collection.name;
    article.dataset.added = collection.metrics?.added || collection.createdAt || '';
    article.dataset.userChosen = collection.metrics?.userChosen ? 'true' : 'false';
    article.dataset.votes = collection.metrics?.votes ?? 0;
    article.dataset.items = collection.items?.length ?? 0;

    article.innerHTML = `
      <img src="${collection.coverImage}" alt="${collection.name}" class="collection-image">
      <div class="collection-content">
        <h3 class="collection-title">#${position} ${collection.name}</h3>
        <p class="collection-author">${collection.owner}</p>
        <button type="button" class="explore-btn">Explore More</button>
      </div>
    `;

    article.querySelector('.explore-btn').addEventListener('click', () => {
      window.location.href = `specific_collection.html?id=${collection.id}`;
    });

    return article;
  }

  function sortCollections(collections) {
    const sorted = [...collections];
    switch (filter.value) {
      case 'userChosen':
        return sorted
          .filter((collection) => collection.metrics?.userChosen)
          .sort(
            (a, b) =>
              Number(b.metrics?.votes ?? 0) - Number(a.metrics?.votes ?? 0)
          );
      case 'itemCount':
        return sorted.sort(
          (a, b) =>
            Number(b.items?.length ?? 0) - Number(a.items?.length ?? 0)
        );
      case 'lastAdded':
      default:
        return sorted.sort((a, b) => {
          const dateA = new Date(a.metrics?.added || a.createdAt || 0).getTime();
          const dateB = new Date(b.metrics?.added || b.createdAt || 0).getTime();
          return dateB - dateA;
        });
    }
  }

  function renderRanking() {
    const collections = sortCollections(store.getAll()).slice(0, 5);
    container.innerHTML = '';

    if (!collections.length) {
      const emptyState = document.createElement('p');
      emptyState.className = 'empty-state';
      emptyState.textContent = 'No collections match this filter.';
      container.appendChild(emptyState);
      return;
    }

    collections.forEach((collection, index) => {
      const card = createCard(collection, index + 1);
      container.appendChild(card);
    });
  }

  filter.addEventListener('change', renderRanking);

  addBtn?.addEventListener('click', () => {
    const name = prompt('Nome da nova colecao:');
    if (!name) {
      return;
    }

    try {
      store.addCollection({ name });
      renderRanking();
    } catch (error) {
      console.error('Falha ao adicionar colecao.', error);
      alert('Nao foi possivel adicionar a colecao. Tente novamente.');
    }
  });

  renderRanking();
});

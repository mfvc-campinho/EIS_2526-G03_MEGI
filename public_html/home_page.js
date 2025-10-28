document.addEventListener('DOMContentLoaded', () => {

  // 2. LÓGICA DO CLIQUE DO BOTÃO
  // O botão é procurado (getElementById) DEPOIS de o DOM estar pronto.
  const gotoButton = document.getElementById('goto-change-state-button');

  if (gotoButton) {
    gotoButton.addEventListener('click', () => {
      // A AÇÃO DE REDIRECIONAMENTO (só ocorre após o clique)
      window.location.replace('index.html');
    });
  }

  const userIndicator = document.getElementById('user-indicator');
  const userRole = sessionStorage.getItem('userRole');

  if (userIndicator) {
    userIndicator.textContent = (userRole === 'C') ? 'Collector' : 'Guest';
  }

  // 2. Controlar botões conforme o tipo de utilizador
  const addBtn = document.getElementById('colectionAdd');
  const editBtn = document.getElementById('colectionEdit');
  const deleteBtn = document.getElementById('colectionDelete');

  if (userRole === 'C') {
    // Collector → tem acesso a todos
    addBtn.style.display = 'inline-block';
    editBtn.style.display = 'inline-block';
    deleteBtn.style.display = 'inline-block';
  } else {
    // Guest → só pode explorar
    addBtn.style.display = 'none';
    editBtn.style.display = 'none';
    deleteBtn.style.display = 'none';
  }


});

document.addEventListener('DOMContentLoaded', () => {
  const filter = document.getElementById('rankingFilter');
  const container = document.querySelector('.collection-container');
  const allCards = Array.from(container.querySelectorAll('.collection-card'));

  function atualizarRanking() {
    let filtered = [...allCards];

    switch (filter.value) {
      case 'userChosen':
        filtered = filtered.filter(card => card.dataset.userChosen === 'true');
        filtered.sort((a, b) => Number(b.dataset.votes) - Number(a.dataset.votes));
        break;
      case 'itemCount':
        filtered.sort((a, b) => Number(b.dataset.items) - Number(a.dataset.items));
        break;
      case 'lastAdded':
      default:
        filtered.sort((a, b) => new Date(b.dataset.added) - new Date(a.dataset.added));
        break;
    }

    const top5 = filtered.slice(0, 5);
    container.innerHTML = '';

    if (top5.length === 0) {
      const emptyState = document.createElement('p');
      emptyState.className = 'empty-state';
      emptyState.textContent = 'No collections match this filter.';
      container.appendChild(emptyState);
      return;
    }

    top5.forEach((card, index) => {
      const title = card.querySelector('.collection-title');
      title.textContent = `#${index + 1} ${card.dataset.name}`;
      container.appendChild(card);
    });
  }

  filter.addEventListener('change', atualizarRanking);
  atualizarRanking();
});



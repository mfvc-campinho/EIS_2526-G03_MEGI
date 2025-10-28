document.addEventListener('DOMContentLoaded', () => {
  // 1. LÓGICA DO INDICADOR DE UTILIZADOR
  // O elemento é procurado (getElementById) DEPOIS de o DOM estar pronto.
  const userIndicator = document.getElementById('user-indicator');
  const userRole = sessionStorage.getItem('userRole');

  if (userIndicator) {
    if (userRole === 'C') {
      userIndicator.textContent = 'Collector';
    } else {
      userIndicator.textContent = 'Guest';
    }
  }

  // 2. LÓGICA DO CLIQUE DO BOTÃO
  // O botão é procurado (getElementById) DEPOIS de o DOM estar pronto.
  const gotoButton = document.getElementById('goto-change-state-button');

  if (gotoButton) {
    gotoButton.addEventListener('click', () => {
      // A AÇÃO DE REDIRECIONAMENTO (só ocorre após o clique)
      window.location.replace('index.html');
    });
  } else {
    console.error('Botão de redirecionamento (ID: goto-change-state-button) não encontrado.');
  }
});
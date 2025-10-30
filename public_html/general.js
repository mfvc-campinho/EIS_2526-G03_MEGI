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

});



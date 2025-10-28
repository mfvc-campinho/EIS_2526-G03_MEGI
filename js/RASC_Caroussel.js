// JavaScript para o menu responsivo slide-down

document.addEventListener('DOMContentLoaded', () => {
  // Seleciona o botão hamburger e o contentor dos links
  const hamburger = document.querySelector('.hamburger');
  const navLinks = document.querySelector('.nav-links');

  // Verifica se os elementos existem antes de adicionar o listener
  if (hamburger && navLinks) {
    hamburger.addEventListener('click', () => {
      // Adiciona ou remove a classe 'active'
      // O CSS usa esta classe para expandir o menu (max-height)
      navLinks.classList.toggle('active');
    });
  } else {
    console.error("Erro no JavaScript: Elementos 'hamburger' ou 'nav-links' não foram encontrados no DOM.");
  }
});


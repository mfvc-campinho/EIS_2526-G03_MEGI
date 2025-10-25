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

// JavaScript para o CARROSSEL
document.addEventListener('DOMContentLoaded', () => {
  const track = document.querySelector('.carousel-track');
  const slides = Array.from(document.querySelectorAll('.carousel-slide'));
  const prevButton = document.querySelector('.prev-button');
  const nextButton = document.querySelector('.next-button');
  let currentSlideIndex = 0;

  // Variável para armazenar a largura do slide
  let slideWidth = 0;

  // Função que CALIBRA A LARGURA e move o carrossel
  const updateSlidePosition = () => {
    // CORREÇÃO AQUI: Recalcula a largura em cada movimento (mais seguro)
    // Usa o offsetWidth que é menos propenso a bugs de arredondamento do getBoundingClientRect().width
    slideWidth = slides[0].offsetWidth;

    // Aplica a transformação baseada na nova largura
    track.style.transform = 'translateX(' + (-slideWidth * currentSlideIndex) + 'px)';
  };

  // ----------------------------------------------------
  // Lógica para o botão PRÓXIMO
  // ----------------------------------------------------
  nextButton.addEventListener('click', () => {
    currentSlideIndex = (currentSlideIndex + 1) % slides.length;
    updateSlidePosition();
  });

  // ----------------------------------------------------
  // Lógica para o botão ANTERIOR
  // ----------------------------------------------------
  prevButton.addEventListener('click', () => {
    currentSlideIndex = (currentSlideIndex - 1 + slides.length) % slides.length;
    updateSlidePosition();
  });

  // ----------------------------------------------------
  // Lógica de Inicialização e Responsividade
  // ----------------------------------------------------

  // 1. Calibra o carrossel quando a página carrega pela primeira vez
  updateSlidePosition();

  // 2. Recalibra o carrossel se a janela mudar de tamanho
  window.addEventListener('resize', updateSlidePosition);
});

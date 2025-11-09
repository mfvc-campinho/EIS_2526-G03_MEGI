// ===============================================
// app-loader.js — Gestão do ecrã de carregamento
// ===============================================

/**
 * Este script espera que toda a página, incluindo imagens e outros recursos,
 * esteja completamente carregada. Quando isso acontece, ele adiciona uma classe
 * para esconder o loader com uma animação de fade-out e, simultaneamente,
 * torna o conteúdo principal da página visível com um fade-in.
 */
window.addEventListener("load", () => {
  const loader = document.getElementById("loader");
  const content = document.getElementById("content");

  // Um pequeno atraso para garantir que a transição seja suave
  setTimeout(() => {
    loader.classList.add("hidden");
    content.style.opacity = "1";
  }, 400);
});
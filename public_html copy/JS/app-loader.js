// ===============================================
// app-loader.js — Gestão do ecrã de carregamento
// ===============================================
//
// O loader foi removido para melhorar a velocidade de carregamento.
// Este script agora apenas garante que o conteúdo principal da página seja visível.
//
document.addEventListener("DOMContentLoaded", () => {
  const loader = document.getElementById("loader");
  const content = document.getElementById("content");
  if (loader) loader.style.display = "none";
  if (content) content.style.opacity = "1";
});
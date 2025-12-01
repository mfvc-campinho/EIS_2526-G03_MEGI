// ===============================================
// app-loader.js — Page loader helper
// - Hides the loader and reveals the main content on DOM ready
// - For performance, the visual loader was removed; this script ensures
//   content visibility remains correct across pages.
// ===============================================
document.addEventListener("DOMContentLoaded", () => {
  const loader = document.getElementById("loader");
  const content = document.getElementById("content");
  if (loader) loader.style.display = "none";
  if (content) content.style.opacity = "1";
});
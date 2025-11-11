/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/ClientSide/javascript.js to edit this template
 */
// ===============================================
// item_page.js — Display details of one item
// ===============================================
document.addEventListener("DOMContentLoaded", () => {
  // Get item ID from query string or localStorage
  const params = new URLSearchParams(window.location.search);
  const itemId = params.get("id") || localStorage.getItem("currentItemId");

  if (!itemId) {
    alert("No item selected.");
    return;
  }

  // Load data from app-data.js
  const data = appData.loadData();
  const item = data.items.find(i => i.id === itemId);

  if (!item) {
    alert("Item not found.");
    return;
  }

  // Fill page elements
  document.getElementById("item-name").textContent = item.name || "Unnamed Item";
  document.getElementById("item-importance").textContent = item.importance || "N/A";
  document.getElementById("item-weight").textContent = item.weight || "N/A";
  document.getElementById("item-price").textContent = item.price || "0.00";
  document.getElementById("item-date").textContent = item.acquisitionDate || "-";
  document.getElementById("item-image").src = item.image || "../images/default.jpg";
});



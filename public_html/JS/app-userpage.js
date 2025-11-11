// ===============================================
// app-userpage.js — Lógica para a página de perfil
// ===============================================

document.addEventListener("DOMContentLoaded", () => {
  // Elementos da página
  const userNameEl = document.getElementById("user-name");
  const userAvatarEl = document.getElementById("user-avatar");
  const userEmailEl = document.getElementById("user-email");
  const userDobEl = document.getElementById("user-dob");
  const userMemberSinceEl = document.getElementById("user-member-since");
  const usernameBannerEl = document.getElementById("username-banner");

  // Elementos do Modal
  const profileModal = document.getElementById("user-profile-modal");
  const editProfileBtn = document.getElementById("edit-profile-btn");
  const closeUserModalBtn = document.getElementById("close-user-modal");
  const cancelUserModalBtn = document.getElementById("cancel-user-modal");
  const profileForm = document.getElementById("form-user-profile");

  let currentUserData = null;
  let viewedOwnerId = "collector-main";

  // 1. Carregar e renderizar os dados do utilizador
  function loadAndRenderUserData() {
    const data = appData.loadData();
    const storedUser = JSON.parse(localStorage.getItem("currentUser"));
    const params = new URLSearchParams(window.location.search);
    const ownerParam = params.get("owner");
    viewedOwnerId = ownerParam || storedUser?.id || "collector-main";
    const user = data.users.find(u => u["owner-id"] === viewedOwnerId);

    if (!user) {
      document.querySelector("main").innerHTML = `<h1 class="page-title">User Not Found</h1><p class="notice-message">No profile matched the id "${viewedOwnerId}".</p>`;
      return;
    }

    currentUserData = user;

    const ownerName = user["owner-name"] || user.name || viewedOwnerId;

    // Preenche a página com os dados
    userNameEl.textContent = ownerName;
    usernameBannerEl.textContent = ownerName;
    userAvatarEl.src = user["owner-photo"];
    userEmailEl.textContent = user.email;
    userDobEl.textContent = user["date-of-birth"];
    if (userMemberSinceEl)
      userMemberSinceEl.textContent = user["member-since"] || "N/A";

    const collectionCount = (data.collectionsUsers || []).filter(link => link.ownerId === viewedOwnerId).length;
    const countEl = document.getElementById("user-collection-count");
    if (countEl)
      countEl.textContent = collectionCount;
  }

  // 2. Controlar o modal de edição
  function openProfileModal() {
    if (!currentUserData) return;

    // Preenche o formulário com os dados atuais
    profileForm.querySelector("#user-form-name").value = currentUserData["owner-name"] || currentUserData.name || "";
    profileForm.querySelector("#user-form-email").value = currentUserData.email;
    profileForm.querySelector("#user-form-dob").value = currentUserData['date-of-birth'];
    profileForm.querySelector("#user-form-photo").value = currentUserData['owner-photo'];

    profileModal.style.display = "flex";
  }

  function closeProfileModal() {
    profileModal.style.display = "none";
  }

  // 3. Event Listeners
  editProfileBtn.addEventListener("click", openProfileModal);
  closeUserModalBtn.addEventListener("click", closeProfileModal);
  cancelUserModalBtn.addEventListener("click", closeProfileModal);
  profileForm.addEventListener("submit", (e) => {
    e.preventDefault();
    alert("✅ Simulation successful. Profile would have been updated.\n\n(This is a demonstration. No data was saved.)");
    closeProfileModal();
  });

  // Inicialização
  loadAndRenderUserData();
});

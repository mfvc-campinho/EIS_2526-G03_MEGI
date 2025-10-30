// ----------------------------------------------------
//  Login + Role Management using localStorage usersStore
// ----------------------------------------------------

document.addEventListener('DOMContentLoaded', () => {
  const usersStore = window.usersStore;
  if (!usersStore) {
    console.error('usersStore indisponivel. Carrega usersData.js antes de index.js.');
  }

  const loginForm = document.getElementById('loginForm');
  const emailInput = document.getElementById('email');
  const passwordInput = document.getElementById('password');

  const userIndicator = document.getElementById('user-indicator');
  const guestSection = document.getElementById('guestSection');
  const collectorButton = document.getElementById('enter-collector-button');
  const logoutCollectorButton = document.getElementById('logout-collector-button');
  const createAccountBtn = document.querySelector('.new-account');
  const forgetBtn = document.getElementById('forgetBtn');
  const guestBtn = document.getElementById('guestBtn');
  const guestLink = document.getElementById('guestLink');

  // Ensure role exists in session storage
  if (!sessionStorage.getItem('userRole')) {
    sessionStorage.setItem('userRole', 'none');
  }

  function getStoredUser() {
    const storedId = sessionStorage.getItem('userId');
    if (!storedId || !usersStore) {
      return null;
    }
    const user = usersStore.getAll().find((entry) => entry.id === storedId) ?? null;
    if (!user) {
      sessionStorage.removeItem('userId');
    }
    return user;
  }

  let currentUser = getStoredUser();

  function setSessionForUser(user) {
    if (user) {
      sessionStorage.setItem('userId', user.id);
      sessionStorage.setItem('userRole', user.role);
      currentUser = user;
    } else {
      sessionStorage.removeItem('userId');
      sessionStorage.setItem('userRole', 'none');
      currentUser = null;
    }
    updateUi();
  }

  function updateUi() {
    const role = sessionStorage.getItem('userRole') ?? 'none';
    const roleLabel = role === 'C' ? 'Collector' : 'Guest';
    const isLogged = Boolean(currentUser);

    if (userIndicator) {
      if (isLogged) {
        userIndicator.textContent = `${currentUser.name} (${roleLabel})`;
      } else if (role === 'none') {
        userIndicator.textContent = 'Guest';
      } else {
        userIndicator.textContent = roleLabel;
      }
    }

    if (loginForm) {
      loginForm.style.display = isLogged ? 'none' : 'block';
    }
    if (collectorButton) {
      collectorButton.style.display = isLogged ? 'inline-block' : 'none';
      collectorButton.textContent = role === 'C' ? 'Enter as Collector' : 'Enter as Guest';
    }
    if (logoutCollectorButton) {
      logoutCollectorButton.style.display = isLogged ? 'inline-block' : 'none';
    }
    if (createAccountBtn) {
      createAccountBtn.style.display = isLogged ? 'none' : 'inline-block';
    }
    if (forgetBtn) {
      forgetBtn.style.display = isLogged ? 'none' : 'inline-block';
    }
    if (guestSection) {
      guestSection.classList.toggle('logged-in', isLogged);
    }
    if (guestLink) {
      guestLink.style.display = isLogged ? 'none' : 'inline-block';
    }
  }

  if (loginForm) {
    loginForm.addEventListener('submit', (event) => {
      event.preventDefault();
      if (!usersStore) {
        alert('Nao foi possivel validar o login neste momento.');
        return;
      }

      const email = emailInput?.value.trim().toLowerCase() ?? '';
      const password = passwordInput?.value.trim() ?? '';

      if (!email || !password) {
        alert('Preencha email e password.');
        return;
      }

      const account = usersStore.getByEmail(email);
      if (!account || account.password !== password) {
        alert('Credenciais incorretas. Tente novamente.');
        return;
      }

      setSessionForUser(account);
      alert(`Bem-vindo, ${account.name}!`);

      if (emailInput) emailInput.value = '';
      if (passwordInput) passwordInput.value = '';
    });
  }

  if (collectorButton) {
    collectorButton.addEventListener('click', (event) => {
      event.preventDefault();
      const role = sessionStorage.getItem('userRole');
      const label = role === 'C' ? 'Collector' : 'Guest';
      alert(`Entering as ${label}.`);
      window.location.href = 'home_page.html';
    });
  }

  if (logoutCollectorButton) {
    logoutCollectorButton.addEventListener('click', (event) => {
      event.preventDefault();
      setSessionForUser(null);
      alert('Sessao terminada.');
      if (emailInput) emailInput.focus();
    });
  }

  if (createAccountBtn) {
    createAccountBtn.addEventListener('click', () => {
      if (!usersStore) {
        alert('Nao foi possivel criar contas neste momento.');
        return;
      }

      const name = prompt('Nome do utilizador:');
      if (name === null) return;

      const email = prompt('Email do utilizador:');
      if (email === null) return;

      const password = prompt('Password (minimo 4 caracteres):');
      if (password === null) return;

      if (password.trim().length < 4) {
        alert('A password deve ter pelo menos 4 caracteres.');
        return;
      }

      const isCollector = confirm('Este utilizador deve ter perfil de Collector? (OK para Collector, Cancel para Guest)');
      const role = isCollector ? 'C' : 'G';

      try {
        const newUser = usersStore.addUser({
          name,
          email,
          password,
          role
        });
        alert(`Conta criada para ${newUser.name}. Pode iniciar sessao com o email ${newUser.email}.`);
      } catch (error) {
        alert(error.message || 'Nao foi possivel criar a conta.');
      }
    });
  }

  if (guestBtn) {
    guestBtn.addEventListener('click', (event) => {
      event.preventDefault();
      sessionStorage.removeItem('userId');
      sessionStorage.setItem('userRole', 'G');
      alert("You're browsing as a guest!");
      window.location.href = 'home_page.html';
    });
  }

  updateUi();
});

// ----------------------------------------------------
//  Login + Role Management using localStorage usersStore
// ----------------------------------------------------

document.addEventListener('DOMContentLoaded', () => {
  const usersStore = window.usersStore;
  const userStateUi = window.userStateUi;
  if (!usersStore) {
    console.error('usersStore unavailable. Load usersData.js before index.js.');
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
    if (!storedId || !usersStore?.getById) {
      return null;
    }

    const user = usersStore.getById(storedId);
    if (!user) {
      sessionStorage.removeItem('userId');
    }
    return user ?? null;
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

  function updateIndicatorFallback() {
    if (!userIndicator) {
      return;
    }

    const role = sessionStorage.getItem('userRole') ?? 'none';
    if (currentUser?.name) {
      userIndicator.textContent = currentUser.name;
    } else if (role === 'C') {
      userIndicator.textContent = 'Collector';
    } else {
      userIndicator.textContent = 'Guest';
    }
  }

  function updateUi() {
    const role = sessionStorage.getItem('userRole') ?? 'none';
    const isLogged = Boolean(currentUser);

    if (userStateUi?.refresh) {
      userStateUi.refresh();
    } else {
      updateIndicatorFallback();
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
        alert('Unable to validate the login right now.');
        return;
      }

      const email = emailInput?.value.trim().toLowerCase() ?? '';
      const password = passwordInput?.value.trim() ?? '';

      if (!email || !password) {
        alert('Please enter your email and password.');
        return;
      }

      const account = usersStore.getByEmail(email);
      if (!account || account.password !== password) {
        alert('Incorrect credentials. Please try again.');
        return;
      }

      setSessionForUser(account);
      alert(`Welcome, ${account.name}!`);

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
      alert('Session ended.');
      if (emailInput) emailInput.focus();
    });
  }

  if (createAccountBtn) {
    createAccountBtn.addEventListener('click', () => {
      if (!usersStore) {
        alert('Unable to create accounts right now.');
        return;
      }

      const name = prompt('User name:');
      if (name === null) return;

      const email = prompt('User email:');
      if (email === null) return;

      const password = prompt('Password (minimum 4 characters):');
      if (password === null) return;

      if (password.trim().length < 4) {
        alert('Password must be at least 4 characters long.');
        return;
      }

      const isCollector = confirm('Should this user have Collector access? (OK for Collector, Cancel for Guest)');
      const role = isCollector ? 'C' : 'G';

      try {
        const newUser = usersStore.addUser({
          name,
          email,
          password,
          role
        });
        alert(`Account created for ${newUser.name}. They can sign in with ${newUser.email}.`);
      } catch (error) {
        alert(error.message || 'Could not create the account.');
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

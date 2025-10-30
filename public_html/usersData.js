(() => {
  const STORAGE_KEY = 'usersStore.v1';

  const defaultUsers = [
    {
      id: 'collector-demo',
      name: 'Collector Demo',
      email: 'collector@goodcollections.test',
      password: 'collector123',
      role: 'C'
    },
    {
      id: 'guest-demo',
      name: 'Guest Demo',
      email: 'guest@goodcollections.test',
      password: 'guest1234',
      role: 'G'
    }
  ];

  const clone = (value) => JSON.parse(JSON.stringify(value));

  function loadFromStorage() {
    try {
      const data = localStorage.getItem(STORAGE_KEY);
      if (!data) {
        return clone(defaultUsers);
      }
      const parsed = JSON.parse(data);
      if (!Array.isArray(parsed)) {
        return clone(defaultUsers);
      }
      return parsed;
    } catch (error) {
      console.warn('Falha ao carregar utilizadores, a repor valores base.', error);
      return clone(defaultUsers);
    }
  }

  function saveToStorage(users) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(users));
    } catch (error) {
      console.error('Falha ao guardar utilizadores.', error);
    }
  }

  function generateIdFromEmail(email, existingIds) {
    const [namePart = 'user'] = email.split('@');
    let base = namePart
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/(^-|-$)/g, '');
    if (!base) {
      base = `user-${Date.now()}`;
    }

    let candidate = base;
    let counter = 1;
    while (existingIds.has(candidate)) {
      candidate = `${base}-${counter++}`;
    }
    return candidate;
  }

  let users = loadFromStorage();

  const store = {
    getAll() {
      return [...users];
    },
    getByEmail(email) {
      return users.find((user) => user.email.toLowerCase() === email.toLowerCase()) ?? null;
    },
    addUser({ name, email, password, role = 'C' }) {
      const trimmedEmail = email?.trim().toLowerCase();
      const trimmedName = name?.trim();
      const trimmedPassword = password?.trim();

      if (!trimmedEmail || !trimmedPassword) {
        throw new Error('Email e password sao obrigatorios.');
      }
      if (this.getByEmail(trimmedEmail)) {
        throw new Error('Ja existe uma conta com esse email.');
      }
      if (!['C', 'G'].includes(role)) {
        role = 'C';
      }

      const ids = new Set(users.map((user) => user.id));
      const id = generateIdFromEmail(trimmedEmail, ids);

      const newUser = {
        id,
        name: trimmedName || 'Utilizador sem nome',
        email: trimmedEmail,
        password: trimmedPassword,
        role
      };

      users.push(newUser);
      saveToStorage(users);
      return newUser;
    },
    reset() {
      users = clone(defaultUsers);
      saveToStorage(users);
      return this.getAll();
    }
  };

  window.usersStore = store;
})();

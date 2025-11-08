(() => {
  // Simple in-browser user store backed by localStorage.
  const STORAGE_KEY = 'usersStore.v1';

  // Default collector accounts available on a fresh installation.
  const defaultUsers = [
    {
      id: 'collector1',
      name: 'Ana',
      email: 'collector@goodcollections.test',
      password: 'collector123',
      role: 'C'
    },
    {
      id: 'collector2',
      name: 'Filipa',
      email: 'collector1@goodcollections.test',
      password: 'collector123',
      role: 'C'
    }
  ];

  const clone = (value) => JSON.parse(JSON.stringify(value));

  function readUsers() {
    try {
      const data = localStorage.getItem(STORAGE_KEY);
      if (!data) {
        return clone(defaultUsers);
      }

      const parsed = JSON.parse(data);
      if (!Array.isArray(parsed)) {
        console.warn('usersStore: Stored users are invalid. Restoring defaults.');
        return clone(defaultUsers);
      }
      return parsed;
    } catch (error) {
      console.warn('usersStore: Failed to load saved users. Restoring defaults.', error);
      return clone(defaultUsers);
    }
  }

  function persistUsers(users) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(users));
    } catch (error) {
      console.error('usersStore: Failed to persist users.', error);
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

  let users = readUsers();

  const store = {
    getAll() {
      // Return a new array so callers do not mutate the internal state.
      return [...users];
    },
    getByEmail(email) {
      if (!email) {
        return null;
      }
      return users.find((user) => user.email.toLowerCase() === email.toLowerCase()) ?? null;
    },
    getById(id) {
      if (!id) {
        return null;
      }
      return users.find((user) => user.id === id) ?? null;
    },
    addUser({ name, email, password, role = 'C' }) {
      const trimmedEmail = email?.trim().toLowerCase();
      const trimmedName = name?.trim();
      const trimmedPassword = password?.trim();

      if (!trimmedEmail || !trimmedPassword) {
        throw new Error('Email and password are required.');
      }
      if (this.getByEmail(trimmedEmail)) {
        throw new Error('An account with this email already exists.');
      }
      if (!['C', 'G'].includes(role)) {
        role = 'C';
      }

      const ids = new Set(users.map((user) => user.id));
      const id = generateIdFromEmail(trimmedEmail, ids);

      const newUser = {
        id,
        name: trimmedName || 'Unnamed collector',
        email: trimmedEmail,
        password: trimmedPassword,
        role
      };

      users.push(newUser);
      persistUsers(users);
      return newUser;
    },
    reset() {
      users = clone(defaultUsers);
      persistUsers(users);
      return this.getAll();
    }
  };

  window.usersStore = store;
})();

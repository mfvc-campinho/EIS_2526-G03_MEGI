// back-link.js — make "Back to ..." links show the previous page when possible
(function () {
  function isSameOrigin(url) {
    try {
      const u = new URL(url, location.href);
      return u.origin === location.origin;
    } catch (e) {
      return false;
    }
  }

  function titleFromPathname(pathname) {
    if (!pathname) return null;
    const name = pathname.split('/').pop().replace('.html', '').replace(/_/g, ' ');
    // pretty names
    if (/all collections/i.test(name) || name === 'all') return 'All Collections';
    if (/specific collection/i.test(name) || name === 'specific collection' || name === 'specificcollection') return 'Collection';
    if (/item page/i.test(name) || name === 'item' || name === 'item page') return 'Item';
    if (/user page/i.test(name) || name === 'user page' || name === 'user_page') return 'User Profile';
    // fallback: capitalize
    return name.replace(/(^|\s)\S/g, s => s.toUpperCase());
  }

  function parseReferrer() {
    const r = document.referrer || sessionStorage.getItem('lastPageRef') || '';
    if (!r) return null;
    try {
      const u = new URL(r, location.href);
      return u;
    } catch (e) {
      return null;
    }
  }

  function tryResolveCollectionNameFromId(id) {
    if (!id) return null;
    try {
      const raw = window.appData && typeof window.appData.loadData === 'function'
        ? window.appData.loadData()
        : JSON.parse(localStorage.getItem('collectionsData') || 'null');
      if (!raw) return null;
      const col = (raw.collections || []).find(c => String(c.id) === String(id));
      return col ? (col.name || col.title || col.id) : null;
    } catch (e) {
      return null;
    }
  }

  function setBackLink(anchor) {
    if (!anchor) return;
    const ref = parseReferrer();
    let title = null;
    let href = null;

    if (ref) {
      href = ref.origin === location.origin ? (ref.pathname + ref.search) : ref.href;
      const path = ref.pathname || '';
      if (path.endsWith('specific_collection.html')) {
        // try to extract id
        const id = ref.searchParams.get('id');
        const colName = tryResolveCollectionNameFromId(id);
        title = colName || 'Collection';
      } else if (path.endsWith('item_page.html')) {
        // Try to resolve item name from query id when coming from an item page
        const itemId = ref.searchParams.get('id');
        let itemName = null;
        if (itemId) {
          try {
            const raw = window.appData && typeof window.appData.loadData === 'function'
              ? window.appData.loadData()
              : JSON.parse(localStorage.getItem('collectionsData') || 'null');
            if (raw && raw.items) {
              const it = (raw.items || []).find(i => String(i.id || i.item_id) === String(itemId));
              if (it) itemName = it.name || it.title || it['item-name'] || null;
            }
          } catch (e) { /* ignore */ }
        }
        title = itemName || 'Item';
      } else if (path.endsWith('all_collections.html')) {
        title = 'Collections';
      } else if (path.endsWith('user_page.html')) {
        // attempt to read owner param
        const owner = ref.searchParams.get('owner');
        title = owner ? `User Profile` : 'User Profile';
      } else {
        title = titleFromPathname(path) || null;
      }
    }

    // Special case: if we arrived here from an item page but the user clicked
    // a collection link on the item page (i.e. lastPage.href points to this
    // specific_collection), prefer sending them back to All Collections.
    try {
      const lastRaw = sessionStorage.getItem('lastPage');
      const lastRefRaw = sessionStorage.getItem('lastPageRef');
      if (lastRaw && lastRefRaw) {
        const last = JSON.parse(lastRaw);
        const lastRefUrl = new URL(lastRefRaw, location.href);
        // Only apply when the lastRef was an item page
        if (lastRefUrl.pathname && lastRefUrl.pathname.endsWith('item_page.html')) {
          // parse last.href to see if it pointed to this collection
          try {
            const lastHrefUrl = new URL(last.href, location.href);
            const currSearch = new URL(location.href).searchParams;
            const currId = currSearch.get('id');
            if (lastHrefUrl.pathname.endsWith('specific_collection.html')) {
              const lastId = lastHrefUrl.searchParams.get('id');
              if (lastId && currId && String(lastId) === String(currId)) {
                // user clicked the collection link from an item page — go back to All Collections
                title = 'All Collections';
                href = 'all_collections.html';
              }
            }
          } catch (e) {
            // ignore malformed last.href
          }
        }
      }
    } catch (e) {
      // ignore session parse errors
    }

    // fallback to any stored lastPage object
    if (!title) {
      const last = sessionStorage.getItem('lastPage');
      if (last) {
        try {
          const obj = JSON.parse(last);
          title = obj.title || titleFromPathname(obj.href || '') || null;
          href = href || obj.href || href;
        } catch (e) { }
      }
    }

    // Build link text and href
    try {
      anchor.innerHTML = '';
      const icon = document.createElement('i');
      icon.className = 'bi bi-arrow-left';
      anchor.appendChild(icon);
      if (title) {
        anchor.appendChild(document.createTextNode(' Back to ' + title));
        if (href && isSameOrigin(href)) {
          anchor.href = href;
        } else if (href && !isSameOrigin(href)) {
          // external referrer — prefer history back
          anchor.href = 'javascript:history.back()';
        } else {
          anchor.href = 'javascript:history.back()';
        }
        anchor.setAttribute('aria-label', 'Back to ' + title);
      } else {
        anchor.appendChild(document.createTextNode(' Back to previous page'));
        anchor.href = 'javascript:history.back()';
        anchor.setAttribute('aria-label', 'Back to previous page');
      }
    } catch (e) {
      console.warn('back-link: failed to set', e);
    }
  }

  function recordClicksForLastPage() {
    // Record clicks on internal nav and breadcrumb links so we can reconstruct previous page when referrer is empty
    const selectors = ['nav a.nav-link', '.nav-left a.logo', '.breadcrumb-list a', '.pill-link', '.collection-list a'];
    const els = document.querySelectorAll(selectors.join(','));
    els.forEach(a => {
      a.addEventListener('click', (ev) => {
        try {
          const href = a.getAttribute('href') || '';
          const title = (a.textContent || a.getAttribute('aria-label') || '').trim();
          sessionStorage.setItem('lastPage', JSON.stringify({ href, title }));
          sessionStorage.setItem('lastPageRef', location.href);
        } catch (e) { }
      });
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    try {
      recordClicksForLastPage();
      // find all back anchors inside .back-section
      document.querySelectorAll('.back-section a.explore-btn, .center-block a.explore-btn').forEach(a => setBackLink(a));
    } catch (e) {
      console.warn('back-link init error', e);
    }
  });
})();

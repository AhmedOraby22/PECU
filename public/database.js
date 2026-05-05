// PECU - Production Engineering Unit
// MySQL-backed API client (sync to avoid changing page logic)

const DB = {
  apiBase: '/api.php',
  lastError: '',

  _normalizeProduct(p) {
    if (!p || !p.id) return p;
    return {
      ...p,
      nameEn: p.nameEn ?? p.name_en ?? '',
      createdAt: p.createdAt ?? p.created_at ?? null,
      featured: !!(p.featured === true || p.featured === 1 || p.featured === '1'),
      price: Number(p.price || 0),
      stock: parseInt(p.stock || 0, 10),
    };
  },

  _normalizeQuote(q) {
    if (!q || !q.id) return q;
    return {
      ...q,
      org: q.org ?? q.organization ?? '',
      qty: q.qty ?? q.quantity ?? 0,
      createdAt: q.createdAt ?? q.created_at ?? null,
    };
  },

  _normalizeUser(u) {
    if (!u || !u.id) return u;
    return {
      ...u,
      createdAt: u.createdAt ?? u.created_at ?? null,
    };
  },

  _normalizeSitePage(p) {
    if (!p || !p.slug) return p;
    return {
      ...p,
      navLabel: p.navLabel ?? p.nav_label ?? '',
      sortOrder: p.sortOrder ?? p.sort_order ?? 0,
      active: !!(p.active === true || p.active === 1 || p.active === '1'),
      links: Array.isArray(p.links) ? p.links : [],
      createdAt: p.createdAt ?? p.created_at ?? null,
      updatedAt: p.updatedAt ?? p.updated_at ?? null,
    };
  },

  _normalizeCatalogItem(item) {
    if (!item || !item.id) return item;
    return {
      ...item,
      sortOrder: item.sortOrder ?? item.sort_order ?? 0,
      active: !!(item.active === true || item.active === 1 || item.active === '1'),
      createdAt: item.createdAt ?? item.created_at ?? null,
      updatedAt: item.updatedAt ?? item.updated_at ?? null,
    };
  },

  _request(method, action, payload = null) {
    this.lastError = '';
    try {
      const xhr = new XMLHttpRequest();
      let url = `${this.apiBase}?action=${encodeURIComponent(action)}`;
      if (method === 'GET' && payload) {
        Object.keys(payload).forEach((k) => {
          url += `&${encodeURIComponent(k)}=${encodeURIComponent(payload[k])}`;
        });
      }

      xhr.open(method, url, false);
      xhr.setRequestHeader('Content-Type', 'application/json;charset=UTF-8');
      const postBody = method === 'POST' ? JSON.stringify({ action, ...payload }) : null;
      xhr.send(postBody);

      if (xhr.status >= 200 && xhr.status < 300) {
        const data = xhr.responseText ? JSON.parse(xhr.responseText) : null;
        if (data && data.error) {
          this.lastError = data.details ? `${data.error}: ${data.details}` : data.error;
          return null;
        }
        return data;
      }

      // Fallback: some hosts/proxies break JSON POST bodies for php endpoints.
      // Retry as GET with query string for write actions.
      if (method === 'POST' && payload && typeof payload === 'object') {
        const retry = new XMLHttpRequest();
        let retryUrl = `${this.apiBase}?action=${encodeURIComponent(action)}`;
        Object.keys(payload).forEach((k) => {
          retryUrl += `&${encodeURIComponent(k)}=${encodeURIComponent(payload[k])}`;
        });
        retry.open('GET', retryUrl, false);
        retry.setRequestHeader('Content-Type', 'application/json;charset=UTF-8');
        retry.send(null);
        if (retry.status >= 200 && retry.status < 300) {
          const data = retry.responseText ? JSON.parse(retry.responseText) : null;
          if (data && data.error) {
            this.lastError = data.details ? `${data.error}: ${data.details}` : data.error;
            return null;
          }
          return data;
        }
      }

      try {
        const err = xhr.responseText ? JSON.parse(xhr.responseText) : null;
        if (err && err.error) {
          this.lastError = err.details ? `${err.error}: ${err.details}` : err.error;
        } else {
          this.lastError = xhr.responseText ? xhr.responseText : `HTTP ${xhr.status}`;
        }
      } catch (_e) {
        this.lastError = xhr.responseText ? xhr.responseText : `HTTP ${xhr.status}`;
      }
      console.error('API error:', xhr.status, xhr.responseText);
      return null;
    } catch (err) {
      this.lastError = 'تعذر الاتصال بـ API';
      console.error('API connection failed:', err);
      return null;
    }
  },

  init() {
    this._request('GET', 'init');
    this.syncSitePageNav();
  },

  // Products
  getProducts() {
    const rows = this._request('GET', 'getProducts') || [];
    return rows.map((p) => this._normalizeProduct(p));
  },
  getProduct(id) {
    const row = this._request('GET', 'getProduct', { id: parseInt(id, 10) });
    return row && row.id ? this._normalizeProduct(row) : undefined;
  },
  addProduct(data) {
    return this._request('POST', 'addProduct', data) || null;
  },
  uploadProductImage(file) {
    this.lastError = '';
    try {
      const xhr = new XMLHttpRequest();
      xhr.open('POST', `${this.apiBase}?action=uploadProductImage`, false);
      const form = new FormData();
      form.append('image', file);
      xhr.send(form);

      if (xhr.status >= 200 && xhr.status < 300) {
        const data = xhr.responseText ? JSON.parse(xhr.responseText) : null;
        if (data && data.error) {
          this.lastError = data.error;
          return null;
        }
        return data;
      }

      try {
        const err = xhr.responseText ? JSON.parse(xhr.responseText) : null;
        this.lastError = err && err.error ? err.error : (xhr.responseText || `HTTP ${xhr.status}`);
      } catch (_e) {
        this.lastError = xhr.responseText || `HTTP ${xhr.status}`;
      }
      return null;
    } catch (err) {
      this.lastError = 'تعذر رفع صورة المنتج';
      console.error('Product image upload failed:', err);
      return null;
    }
  },
  updateProduct(id, data) {
    this._request('POST', 'updateProduct', { id: parseInt(id, 10), ...data });
  },
  deleteProduct(id) {
    this._request('POST', 'deleteProduct', { id: parseInt(id, 10) });
  },

  // Hero slides
  getHeroSlides() {
    const rows = this._request('GET', 'getHeroSlides') || [];
    return rows.map((s) => ({
      ...s,
      sortOrder: s.sortOrder ?? s.sort_order ?? 0,
      active: !!(s.active === true || s.active === 1 || s.active === '1'),
      createdAt: s.createdAt ?? s.created_at ?? null,
      updatedAt: s.updatedAt ?? s.updated_at ?? null,
    }));
  },
  addHeroSlide(data) {
    return this._request('POST', 'addHeroSlide', data) || null;
  },
  deleteHeroSlide(id) {
    this._request('POST', 'deleteHeroSlide', { id: parseInt(id, 10) });
  },
  uploadHeroSlideImage(file) {
    return this._uploadImage('uploadHeroSlideImage', file, 'تعذر رفع صورة السلايدر');
  },

  // Catalog
  getCatalogItems() {
    const rows = this._request('GET', 'getCatalogItems') || [];
    return rows.map((item) => this._normalizeCatalogItem(item));
  },
  addCatalogItem(data) {
    return this._request('POST', 'addCatalogItem', data) || null;
  },
  deleteCatalogItem(id) {
    this._request('POST', 'deleteCatalogItem', { id: parseInt(id, 10) });
  },
  uploadCatalogImage(file) {
    return this._uploadImage('uploadCatalogImage', file, 'تعذر رفع صورة الكتالوج');
  },

  // Quotes
  getQuotes() {
    const rows = this._request('GET', 'getQuotes') || [];
    return rows.map((q) => this._normalizeQuote(q));
  },
  addQuote(data) {
    return this._request('POST', 'addQuote', data) || null;
  },
  updateQuoteStatus(id, status) {
    this._request('POST', 'updateQuoteStatus', { id: parseInt(id, 10), status });
  },
  deleteQuote(id) {
    this._request('POST', 'deleteQuote', { id: parseInt(id, 10) });
  },

  // Site pages
  getSitePages() {
    const rows = this._request('GET', 'getSitePages') || [];
    return rows.map((p) => this._normalizeSitePage(p));
  },
  getSitePage(slug) {
    const row = this._request('GET', 'getSitePage', { slug });
    return row && row.slug ? this._normalizeSitePage(row) : null;
  },
  updateSitePage(slug, data) {
    return this._request('POST', 'updateSitePage', { slug, ...data }) || null;
  },
  uploadSitePageImage(file) {
    return this._uploadImage('uploadSitePageImage', file, 'تعذر رفع صورة الصفحة');
  },
  _uploadImage(action, file, fallbackError) {
    this.lastError = '';
    try {
      const xhr = new XMLHttpRequest();
      xhr.open('POST', `${this.apiBase}?action=${encodeURIComponent(action)}`, false);
      const form = new FormData();
      form.append('image', file);
      xhr.send(form);

      if (xhr.status >= 200 && xhr.status < 300) {
        const data = xhr.responseText ? JSON.parse(xhr.responseText) : null;
        if (data && data.error) {
          this.lastError = data.error;
          return null;
        }
        return data;
      }

      try {
        const err = xhr.responseText ? JSON.parse(xhr.responseText) : null;
        this.lastError = err && err.error ? err.error : (xhr.responseText || `HTTP ${xhr.status}`);
      } catch (_e) {
        this.lastError = xhr.responseText || `HTTP ${xhr.status}`;
      }
      return null;
    } catch (err) {
      this.lastError = fallbackError;
      console.error(`${action} failed:`, err);
      return null;
    }
  },
  syncSitePageNav() {
    const links = Array.from(document.querySelectorAll('[data-page-slug]'));
    if (links.length === 0) return;

    const pages = this.getSitePages();
    const currentSlug = new URLSearchParams(location.search).get('slug');
    links.forEach((link) => {
      const page = pages.find((p) => p.slug === link.dataset.pageSlug);
      const item = link.closest('li') || link;
      if (!page || !page.active) {
        item.style.display = 'none';
        return;
      }

      item.style.display = '';
      let navLabel = page.navLabel;
      if (page.slug === 'news' && (!navLabel || navLabel === 'أخبارنا')) {
        navLabel = 'الكتالوج';
      } else if (page.slug === 'design-your-furniture' && navLabel === 'صمم أثاثك') {
        navLabel = 'صمم أثاثك الأن';
      } else if (page.slug === 'student-training' && navLabel === 'تدريب الطلاب') {
        navLabel = 'تدريبات الطلاب';
      }
      link.textContent = navLabel || link.textContent;
      link.href = `content.html?slug=${encodeURIComponent(page.slug)}`;
      link.classList.toggle('active', currentSlug === page.slug);
    });
  },

  // Users
  getUsers() {
    const rows = this._request('GET', 'getUsers') || [];
    return rows.map((u) => this._normalizeUser(u));
  },
  getUser(id) {
    const row = this._request('GET', 'getUser', { id: parseInt(id, 10) });
    return row && row.id ? this._normalizeUser(row) : undefined;
  },
  addUser(data) {
    return this._request('POST', 'addUser', data) || null;
  },
  updateUser(id, data) {
    this._request('POST', 'updateUser', { id: parseInt(id, 10), ...data });
  },
  deleteUser(id) {
    this._request('POST', 'deleteUser', { id: parseInt(id, 10) });
  },

  // Auth
  checkAdmin(username, password) {
    const result = this._request('POST', 'checkAdmin', { username, password });
    return !!(result && result.ok);
  },
  checkWebsiteUser(username, password) {
    const result = this._request('POST', 'checkWebsiteUser', { username, password });
    if (result && result.ok && result.user) {
      return result.user;
    }
    return null;
  },
  isLoggedIn() {
    return sessionStorage.getItem('pecu_logged') === 'true';
  },
  login() {
    sessionStorage.setItem('pecu_logged', 'true');
  },
  logout() {
    sessionStorage.removeItem('pecu_logged');
  },
  websiteLogin(user) {
    sessionStorage.setItem('pecu_site_user', JSON.stringify(user || {}));
  },
  websiteLogout() {
    sessionStorage.removeItem('pecu_site_user');
  },
  getWebsiteUser() {
    try {
      const raw = sessionStorage.getItem('pecu_site_user');
      return raw ? JSON.parse(raw) : null;
    } catch (_e) {
      return null;
    }
  }
};

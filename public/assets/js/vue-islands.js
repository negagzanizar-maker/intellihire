(function () {
  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  function safeNumber(value, fallback = 0) {
    const number = Number(value);
    return Number.isFinite(number) ? number : fallback;
  }

  ready(() => {
    if (!window.Vue || !window.Vue.createApp) return;

    const config = window.IntelliHireVue || {};

    mountNotifications(config.notifications);
    mountCandidatures(config.candidatures);
  });

  function mountNotifications(config) {
    const el = document.getElementById('notification-vue-app');
    if (!el || !config) return;

    window.Vue.createApp({
      data() {
        return {
          baseUrl: config.baseUrl || '',
          open: false,
          loading: false,
          unread: safeNumber(config.unread),
          items: Array.isArray(config.items) ? config.items : [],
          csrfToken: config.csrfToken || '',
        };
      },
      mounted() {
        document.addEventListener('click', this.close);
      },
      beforeUnmount() {
        document.removeEventListener('click', this.close);
      },
      methods: {
        close() {
          this.open = false;
        },
        toggle() {
          this.open = !this.open;
          if (this.open) this.load();
        },
        notificationHref(item) {
          return item && item.lien ? `${this.baseUrl}/index.php?url=${item.lien}` : null;
        },
        formatNotifTime(value) {
          if (!value) return '';
          const date = new Date(String(value).replace(' ', 'T'));
          if (Number.isNaN(date.getTime())) return String(value);
          return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' })
            + ' '
            + date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
        },
        load() {
          if (this.loading) return;
          this.loading = true;

          fetch(`${this.baseUrl}/index.php?url=ajax/notifications`, {
            headers: { Accept: 'application/json' },
          })
            .then(response => response.json())
            .then(response => {
              if (!response.success) return;
              this.items = Array.isArray(response.data) ? response.data : [];
              this.unread = safeNumber(response.unread);
            })
            .catch(() => {})
            .finally(() => {
              this.loading = false;
            });
        },
        markRead() {
          fetch(`${this.baseUrl}/index.php?url=ajax/mark_read`, {
            method: 'POST',
            headers: {
              Accept: 'application/json',
              'X-CSRF-Token': this.csrfToken,
            },
          })
            .then(response => response.json())
            .then(response => {
              if (!response.success) throw new Error(response.message || 'Lecture impossible.');
              this.unread = 0;
              this.items = this.items.map(item => ({ ...item, lu: 1 }));
            })
            .catch(() => {});
        },
      },
    }).mount(el);
  }

  function mountCandidatures(config) {
    const el = document.getElementById('candidature-vue-app');
    if (!el || !config) return;

    window.Vue.createApp({
      data() {
        return {
          baseUrl: config.baseUrl || '',
          rows: Array.isArray(config.initialRows) ? config.initialRows : [],
          total: safeNumber(config.total),
          pages: Math.max(1, safeNumber(config.pages, 1)),
          page: Math.max(1, safeNumber(config.page, 1)),
          filters: {
            search: config.filters?.search || '',
            statut: config.filters?.statut || '',
            id_offre: config.filters?.id_offre || '',
          },
          offres: Array.isArray(config.offres) ? config.offres : [],
          statuses: [
            { value: 'Recue', label: 'Recue' },
            { value: 'En_cours', label: 'En cours' },
            { value: 'Entretien', label: 'Entretien' },
            { value: 'Acceptee', label: 'Acceptée' },
            { value: 'Refusee', label: 'Refusée' },
          ],
          loading: false,
          error: '',
          debounceTimer: null,
        };
      },
      computed: {
        resultLabel() {
          const plural = this.total > 1 ? 's' : '';
          return `${this.total} candidature${plural} affichée${plural}`;
        },
        paginationItems() {
          const items = [];
          if (this.pages <= 7) {
            for (let page = 1; page <= this.pages; page++) {
              items.push({ type: 'page', value: page, label: String(page), key: `p-${page}` });
            }
            return items;
          }

          const keep = new Set([1, this.pages, this.page, this.page - 1, this.page + 1, this.page - 2, this.page + 2]);
          let lastPage = 0;

          for (let page = 1; page <= this.pages; page++) {
            if (!keep.has(page)) continue;
            if (page - lastPage > 1) {
              items.push({ type: 'ellipsis', label: '…', key: `e-${page}` });
            }
            items.push({ type: 'page', value: page, label: String(page), key: `p-${page}` });
            lastPage = page;
          }

          return items;
        },
      },
      methods: {
        debouncedFetch() {
          clearTimeout(this.debounceTimer);
          this.debounceTimer = setTimeout(() => this.fetchRows(1), 350);
        },
        fetchRows(page = 1) {
          const nextPage = Math.max(1, Math.min(page, this.pages || 1));
          this.loading = true;
          this.error = '';

          const params = new URLSearchParams();
          params.set('search', this.filters.search || '');
          params.set('statut', this.filters.statut || '');
          params.set('id_offre', this.filters.id_offre || '');
          params.set('page', nextPage);

          fetch(`${this.baseUrl}/index.php?url=ajax/candidatures&${params.toString()}`, {
            headers: { Accept: 'application/json' },
          })
            .then(response => response.json())
            .then(response => {
              if (!response.success) throw new Error(response.message || 'Chargement impossible.');
              this.rows = Array.isArray(response.data) ? response.data : [];
              this.total = safeNumber(response.total);
              this.pages = Math.max(1, safeNumber(response.pages, 1));
              this.page = Math.max(1, safeNumber(response.page, nextPage));
            })
            .catch(error => {
              this.error = error.message || 'Chargement impossible.';
            })
            .finally(() => {
              this.loading = false;
            });
        },
        resetFilters() {
          this.filters.search = '';
          this.filters.statut = '';
          this.filters.id_offre = '';
          this.fetchRows(1);
        },
        detailUrl(row) {
          return `${this.baseUrl}/index.php?url=candidatures/detail/${row.id_candidature}`;
        },
        goTo(row, event) {
          if (event?.target?.closest?.('a, button, input, select, textarea, label')) return;
          const url = this.detailUrl(row);
          if (event?.ctrlKey || event?.metaKey || event?.button === 1) {
            window.open(url, '_blank');
          } else {
            window.location.href = url;
          }
        },
        candidateName(row) {
          return `${row.prenom || ''} ${row.nom || ''}`.trim();
        },
        score(row) {
          return Math.max(0, Math.min(100, Math.round(safeNumber(row.score_matching))));
        },
        badgeClass(status) {
          return `badge-${String(status || '').toLowerCase()}`;
        },
        formatStatus(status) {
          return String(status || '').replace('_', ' ');
        },
        formatDate(value) {
          if (!value) return '';
          const date = new Date(String(value).replace(' ', 'T'));
          if (Number.isNaN(date.getTime())) return String(value);
          return date.toLocaleDateString('fr-FR');
        },
      },
    }).mount(el);
  }
})();


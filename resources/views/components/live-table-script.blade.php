<!-- Reusable Alpine.js Live Table Explorer Helper -->
<script>
    function liveTableExplorer(config = {}) {
        return {
            containerId: config.containerId || 'live-table-container',
            filterTabsId: config.filterTabsId || null,
            isLoading: false,
            searchQuery: config.initialSearch || '',

            init() {
                window.addEventListener('popstate', () => {
                    this.loadUrl(window.location.href, false);
                });
                this.bindPaginationLinks();
            },

            bindPaginationLinks() {
                this.$nextTick(() => {
                    const container = document.getElementById(this.containerId);
                    if (!container) return;
                    const links = container.querySelectorAll('nav[role="navigation"] a');
                    links.forEach(link => {
                        if (link.dataset.liveBound) return;
                        link.dataset.liveBound = 'true';
                        link.addEventListener('click', (e) => {
                            e.preventDefault();
                            this.loadUrl(link.href);
                        });
                    });
                });
            },

            buildUrl(extraParams = {}) {
                const form = this.$refs.filterForm;
                const action = form ? form.action.split('?')[0] : window.location.pathname;
                const currentUrl = new URL(window.location.href);
                const params = new URLSearchParams(currentUrl.search);

                if (form) {
                    const formData = new FormData(form);
                    for (const [key, value] of formData.entries()) {
                        if (value !== '' && value !== null) {
                            params.set(key, value);
                        } else {
                            params.delete(key);
                        }
                    }
                }

                for (const [key, value] of Object.entries(extraParams)) {
                    if (value !== '' && value !== null) {
                        params.set(key, value);
                    } else {
                        params.delete(key);
                    }
                }

                const qs = params.toString();
                return qs ? `${action}?${qs}` : action;
            },

            setParam(key, val) {
                const extra = {};
                extra[key] = val;
                if (key !== 'page') {
                    extra['page'] = ''; // reset to page 1 on filter/search change
                }
                const url = this.buildUrl(extra);
                this.loadUrl(url);
            },

            async loadUrl(url, updateHistory = true) {
                if (this.isLoading) return;
                this.isLoading = true;

                try {
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) throw new Error(`HTTP ${response.status}`);

                    const html = await response.text();
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    const newContainer = doc.getElementById(this.containerId);
                    const currentContainer = document.getElementById(this.containerId);

                    if (newContainer && currentContainer) {
                        currentContainer.innerHTML = newContainer.innerHTML;
                    }

                    if (this.filterTabsId) {
                        const newTabs = doc.getElementById(this.filterTabsId);
                        const currentTabs = document.getElementById(this.filterTabsId);
                        if (newTabs && currentTabs) {
                            currentTabs.innerHTML = newTabs.innerHTML;
                        }
                    }

                    if (updateHistory) {
                        window.history.pushState({}, '', url);
                    }

                    this.bindPaginationLinks();

                    if (typeof window.reinitLocks === 'function') {
                        window.reinitLocks();
                    }
                } catch (err) {
                    console.error('Live table update failed:', err);
                    window.showToast?.('Failed to load updated results. Please refresh.', 'error');
                } finally {
                    this.isLoading = false;
                }
            }
        };
    }
</script>

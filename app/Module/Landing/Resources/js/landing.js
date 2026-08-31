(() => {
    const root = document.documentElement;
    const themeToggle = document.getElementById('theme-toggle');

    function currentTheme() {
        return root.getAttribute('data-theme') === 'day' ? 'day' : 'night';
    }

    function applyTheme(theme) {
        root.setAttribute('data-theme', theme);
        try {
            localStorage.setItem('vatrapi-theme', theme);
        } catch (error) {
            // Ignore private-mode storage failures.
        }
        if (themeToggle) {
            const isDay = theme === 'day';
            themeToggle.setAttribute('aria-pressed', isDay ? 'true' : 'false');
            themeToggle.setAttribute('aria-label', isDay ? 'Switch to night theme' : 'Switch to day theme');
        }
    }

    applyTheme(currentTheme());

    themeToggle?.addEventListener('click', () => {
        applyTheme(currentTheme() === 'night' ? 'day' : 'night');
    });

    const dataNode = document.getElementById('landing-data');
    if (!dataNode) {
        return;
    }

    const data = JSON.parse(dataNode.textContent);
    const endpoints = data.endpoints;
    const byId = Object.fromEntries(endpoints.map((endpoint) => [endpoint.id, endpoint]));
    const natalDefault = byId['natal-chart'] ?? endpoints[0];

    const bodyEl = document.getElementById('play-body');
    const outEl = document.getElementById('play-out');
    const statusEl = document.getElementById('play-status');
    const summaryEl = document.getElementById('play-summary');
    const stageEl = document.getElementById('play-stage');
    const tabs = [...document.querySelectorAll('.play-tab')];

    let activeId = natalDefault.id;

    function pretty(value) {
        return JSON.stringify(value, null, 2);
    }

    function parseBody() {
        return JSON.parse(bodyEl.value);
    }

    function selectEndpoint(id, { scroll } = { scroll: false }) {
        const endpoint = byId[id] ?? natalDefault;
        activeId = endpoint.id;

        tabs.forEach((tab) => {
            const on = tab.dataset.endpoint === endpoint.id;
            tab.classList.toggle('is-active', on);
            tab.setAttribute('aria-selected', on ? 'true' : 'false');
        });

        if (stageEl) {
            stageEl.setAttribute('aria-labelledby', `tab-${endpoint.id}`);
        }
        if (summaryEl) {
            summaryEl.textContent = endpoint.summary;
        }
        bodyEl.value = pretty(endpoint.sampleBody);
        outEl.textContent = pretty({ ok: true, data: {} });
        outEl.classList.remove('is-ok', 'is-error');
        statusEl.textContent = 'idle';

        if (scroll) {
            document.getElementById('playground')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    function curlFor(endpoint, body) {
        return [
            `curl -sS -X POST '${data.apiBase}${endpoint.path}'`,
            `  -H 'Content-Type: application/json'`,
            `  -H 'Accept: application/json'`,
            `  -d '${JSON.stringify(body)}'`,
        ].join(' \\\n');
    }

    async function copyCurl() {
        const endpoint = byId[activeId];
        let body;
        try {
            body = parseBody();
        } catch (error) {
            statusEl.textContent = 'invalid JSON';
            return;
        }

        const command = curlFor(endpoint, body);
        try {
            await navigator.clipboard.writeText(command);
            statusEl.textContent = 'curl copied';
        } catch (error) {
            window.prompt('Copy curl', command);
        }
    }

    async function runRequest() {
        const endpoint = byId[activeId];
        let body;
        try {
            body = parseBody();
        } catch (error) {
            outEl.textContent = pretty({ ok: false, error: 'Request body is not valid JSON.' });
            outEl.classList.add('is-error');
            outEl.classList.remove('is-ok');
            statusEl.textContent = 'invalid JSON';
            return;
        }

        statusEl.textContent = 'running…';
        const started = performance.now();

        try {
            const response = await fetch(`${data.apiBase}${endpoint.path}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                body: JSON.stringify(body),
            });
            const payload = await response.json();
            const ms = Math.round(performance.now() - started);
            outEl.textContent = pretty(payload);
            outEl.classList.toggle('is-ok', payload.ok === true);
            outEl.classList.toggle('is-error', payload.ok !== true);
            statusEl.textContent = `${response.status} · ${ms}ms`;
        } catch (error) {
            outEl.textContent = pretty({ ok: false, error: 'Network error talking to the API.' });
            outEl.classList.add('is-error');
            outEl.classList.remove('is-ok');
            statusEl.textContent = 'network error';
        }
    }

    function bindSelects(selector) {
        document.querySelectorAll(selector).forEach((node) => {
            node.addEventListener('click', (event) => {
                const id = node.getAttribute('data-select');
                if (!id) {
                    return;
                }
                event.preventDefault();
                selectEndpoint(id, { scroll: true });
            });
        });
    }

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => selectEndpoint(tab.dataset.endpoint));
    });

    document.getElementById('play-run')?.addEventListener('click', () => {
        void runRequest();
    });
    document.getElementById('copy-curl')?.addEventListener('click', () => {
        void copyCurl();
    });

    bodyEl?.addEventListener('keydown', (event) => {
        if ((event.metaKey || event.ctrlKey) && event.key === 'Enter') {
            event.preventDefault();
            void runRequest();
        }
    });

    bindSelects('[data-select]');
    selectEndpoint(natalDefault.id);
})();

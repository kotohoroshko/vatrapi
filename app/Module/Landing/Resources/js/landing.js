(() => {
    const root = document.documentElement;
    const themeToggle = document.getElementById('theme-toggle');

    function currentTheme() {
        return root.getAttribute('data-theme') === 'day' ? 'day' : 'night';
    }

    function applyTheme(theme, { persist } = { persist: false }) {
        root.setAttribute('data-theme', theme);
        if (persist) {
            try {
                localStorage.setItem('vatrapi-theme', theme);
            } catch (error) {
                // Ignore private-mode storage failures.
            }
        }
        if (themeToggle) {
            const isDay = theme === 'day';
            themeToggle.setAttribute('aria-pressed', isDay ? 'true' : 'false');
            themeToggle.setAttribute('aria-label', isDay ? 'Switch to dark theme' : 'Switch to light theme');
        }
    }

    applyTheme(currentTheme());

    themeToggle?.addEventListener('click', () => {
        applyTheme(currentTheme() === 'night' ? 'day' : 'night', { persist: true });
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
    const pathEl = document.getElementById('play-path');
    const stageEl = document.getElementById('play-stage');
    const copyEl = document.getElementById('copy-curl');
    const tabs = [...document.querySelectorAll('.play-tab')];

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    const EMPTY_OUTPUT = 'Run the request to see the response.';

    let activeId = natalDefault.id;
    let copyTimer = null;

    function pretty(value) {
        return JSON.stringify(value, null, 2);
    }

    function parseBody() {
        return JSON.parse(bodyEl.value);
    }

    function escapeHtml(text) {
        return text.replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
        })[char]);
    }

    const TOKEN = /("(?:\\.|[^"\\])*")(\s*:)?|(-?\d+(?:\.\d+)?(?:[eE][+-]?\d+)?)|\b(true|false|null)\b|([{}[\],])/g;

    function highlight(json) {
        let html = '';
        let last = 0;
        json.replace(TOKEN, (match, str, colon, num, lit, punct, offset) => {
            html += escapeHtml(json.slice(last, offset));
            if (str !== undefined) {
                html += colon
                    ? `<span class="tok-key">${escapeHtml(str)}</span><span class="tok-punct">${escapeHtml(colon)}</span>`
                    : `<span class="tok-str">${escapeHtml(str)}</span>`;
            } else if (num !== undefined) {
                html += `<span class="tok-num">${num}</span>`;
            } else if (lit !== undefined) {
                html += `<span class="tok-lit">${lit}</span>`;
            } else {
                html += `<span class="tok-punct">${escapeHtml(punct)}</span>`;
            }
            last = offset + match.length;
            return match;
        });
        return html + escapeHtml(json.slice(last));
    }

    function setStatus(text, state = null) {
        statusEl.textContent = text;
        statusEl.classList.remove('is-ok', 'is-error', 'is-busy');
        if (state) {
            statusEl.classList.add(`is-${state}`);
        }
    }

    function showOutput(value) {
        outEl.classList.remove('is-empty');
        outEl.innerHTML = highlight(pretty(value));
    }

    function resetOutput() {
        outEl.classList.add('is-empty');
        outEl.textContent = EMPTY_OUTPUT;
        setStatus('idle');
    }

    function selectEndpoint(id, { scroll } = { scroll: false }) {
        const endpoint = byId[id] ?? natalDefault;
        activeId = endpoint.id;

        tabs.forEach((tab) => {
            const on = tab.dataset.endpoint === endpoint.id;
            tab.classList.toggle('is-active', on);
            tab.setAttribute('aria-selected', on ? 'true' : 'false');
            tab.tabIndex = on ? 0 : -1;
        });

        stageEl?.setAttribute('aria-labelledby', `tab-${endpoint.id}`);
        if (summaryEl) {
            summaryEl.textContent = endpoint.summary;
        }
        if (pathEl) {
            pathEl.textContent = endpoint.publicPath;
        }
        bodyEl.value = pretty(endpoint.sampleBody);
        resetOutput();

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

    function flashCopy(label) {
        if (!copyEl) {
            return;
        }
        copyEl.textContent = label;
        clearTimeout(copyTimer);
        copyTimer = setTimeout(() => {
            copyEl.textContent = 'Copy curl';
        }, 1600);
    }

    async function copyCurl() {
        const endpoint = byId[activeId];
        let body;
        try {
            body = parseBody();
        } catch (error) {
            setStatus('invalid JSON', 'error');
            return;
        }

        const command = curlFor(endpoint, body);
        try {
            await navigator.clipboard.writeText(command);
            flashCopy('Copied');
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
            showOutput({ ok: false, error: 'Request body is not valid JSON.' });
            setStatus('invalid JSON', 'error');
            return;
        }

        setStatus('running…', 'busy');
        const started = performance.now();

        try {
            // Runs through the landing proxy, which adds the playground's service key server-side.
            const response = await fetch(`${data.playgroundBase}/${endpoint.id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(body),
            });
            const payload = await response.json();
            const ms = Math.round(performance.now() - started);
            showOutput(payload);
            setStatus(`${response.status} · ${ms} ms`, payload.ok === true ? 'ok' : 'error');
        } catch (error) {
            showOutput({ ok: false, error: 'Network error talking to the API.' });
            setStatus('network error', 'error');
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

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => selectEndpoint(tab.dataset.endpoint));
        tab.addEventListener('keydown', (event) => {
            const step = { ArrowDown: 1, ArrowRight: 1, ArrowUp: -1, ArrowLeft: -1 }[event.key];
            if (!step) {
                return;
            }
            event.preventDefault();
            const next = tabs[(index + step + tabs.length) % tabs.length];
            selectEndpoint(next.dataset.endpoint);
            next.focus();
        });
    });

    document.getElementById('play-run')?.addEventListener('click', () => {
        void runRequest();
    });
    copyEl?.addEventListener('click', () => {
        void copyCurl();
    });

    bodyEl?.addEventListener('keydown', (event) => {
        if ((event.metaKey || event.ctrlKey) && event.key === 'Enter') {
            event.preventDefault();
            void runRequest();
        }
    });

    const runHint = document.getElementById('run-hint');
    if (runHint && /Mac|iPhone|iPad/.test(navigator.platform || navigator.userAgent)) {
        runHint.textContent = '⌘↵';
    }

    bindSelects('[data-select]');
    selectEndpoint(natalDefault.id);
})();

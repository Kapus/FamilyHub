// dashboard.js
// Hanterar modulnavigering och AJAX-inladdning av innehåll
(function () {
    const container = document.getElementById('module-container');
    const links = document.querySelectorAll('[data-module]');
    const availableModules = new Set(Array.from(links).map(link => link.dataset.module));
    let activeModuleName = null;

    function teardownCurrentModule() {
        if (!activeModuleName || !window.FamilyHubModules) {
            return;
        }
        const mod = window.FamilyHubModules[activeModuleName];
        if (mod && typeof mod.destroy === 'function') {
            mod.destroy(container);
        }
        activeModuleName = null;
    }

    function setActive(moduleName) {
        links.forEach(link => {
            link.classList.toggle('active', link.dataset.module === moduleName);
        });
    }

    function normalizeModule(moduleName) {
        if (moduleName && availableModules.has(moduleName)) {
            return moduleName;
        }
        if (availableModules.has('tasks')) {
            return 'tasks';
        }
        return links.length > 0 ? links[0].dataset.module : 'tasks';
    }

    function buildPageUrl(moduleName) {
        const url = new URL(window.location.href);
        url.searchParams.set('module', moduleName);
        return url;
    }

    async function loadModule(moduleName, options = {}) {
        const { updateHistory = true, pushState = false } = options;
        const resolvedModule = normalizeModule(moduleName);

        try {
            setActive(resolvedModule);
            teardownCurrentModule();
            container.innerHTML = '<div class="text-center py-5"><div class="spinner-border" role="status"></div></div>';

            let pageUrl = buildPageUrl(resolvedModule);
            if (updateHistory) {
                const method = pushState ? 'pushState' : 'replaceState';
                window.history[method](null, '', pageUrl.toString());
            }

            const response = await fetch(`ajax/load_module.php?${pageUrl.searchParams.toString()}`);
            if (!response.ok) {
                throw new Error('Kunde inte ladda modulen.');
            }
            const html = await response.text();
            container.innerHTML = html;

            if (window.FamilyHubModules) {
                const mod = window.FamilyHubModules[resolvedModule];
                if (mod && typeof mod.init === 'function') {
                    mod.init(container);
                    activeModuleName = resolvedModule;
                }
            }
        } catch (error) {
            container.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        }
    }

    links.forEach(link => {
        link.addEventListener('click', event => {
            event.preventDefault();
            const moduleName = link.dataset.module;
            loadModule(moduleName, { pushState: true });
        });
    });

    window.addEventListener('popstate', () => {
        const params = new URLSearchParams(window.location.search);
        const moduleName = params.get('module');
        loadModule(moduleName, { updateHistory: false });
    });

    window.FamilyHubDashboard = { loadModule, normalizeModule };
})();

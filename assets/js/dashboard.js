// dashboard.js
// Hanterar modulnavigering och AJAX-inladdning av innehåll
(function () {
    const container = document.getElementById('module-container');
    const links = document.querySelectorAll('[data-module]');
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

    async function loadModule(moduleName) {
        try {
            setActive(moduleName);
            teardownCurrentModule();
            container.innerHTML = '<div class="text-center py-5"><div class="spinner-border" role="status"></div></div>';
            const response = await fetch(`ajax/load_module.php?module=${encodeURIComponent(moduleName)}`);
            if (!response.ok) {
                throw new Error('Kunde inte ladda modulen.');
            }
            const html = await response.text();
            container.innerHTML = html;

            if (window.FamilyHubModules) {
                const mod = window.FamilyHubModules[moduleName];
                if (mod && typeof mod.init === 'function') {
                    mod.init(container);
                    activeModuleName = moduleName;
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
            loadModule(moduleName);
        });
    });

    window.FamilyHubDashboard = { loadModule };
})();

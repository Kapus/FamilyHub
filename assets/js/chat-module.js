// chat-module.js
// Kopplar formulärlogik för familjechatten
(function () {
    if (!window.FamilyHubModules) {
        window.FamilyHubModules = {};
    }

    const handlers = new WeakMap();

    function setFeedback(feedbackEl, message, state) {
        if (!feedbackEl) {
            return;
        }
        const baseClass = feedbackEl.dataset.baseClass || feedbackEl.className;
        if (!feedbackEl.dataset.baseClass) {
            feedbackEl.dataset.baseClass = baseClass;
        }
        feedbackEl.className = baseClass;
        feedbackEl.textContent = message || '';
        if (!state) {
            return;
        }
        if (state === 'success') {
            feedbackEl.classList.add('text-success');
        }
        if (state === 'error') {
            feedbackEl.classList.add('text-danger');
        }
    }

    function attachHandler(form, feedbackEl) {
        const submitHandler = async (event) => {
            event.preventDefault();
            setFeedback(feedbackEl, 'Skickar...', null);

            const formData = new FormData(form);

            try {
                const response = await fetch('ajax/send_message.php', {
                    method: 'POST',
                    body: formData,
                });

                let result = {};
                try {
                    result = await response.json();
                } catch (jsonError) {
                    throw new Error('Servern gav ett oväntat svar.');
                }

                if (!response.ok || !result.success) {
                    throw new Error(result.error || 'Kunde inte skicka meddelandet.');
                }

                setFeedback(feedbackEl, 'Meddelandet skickades!', 'success');
                form.reset();

                if (window.FamilyHubDashboard && typeof window.FamilyHubDashboard.loadModule === 'function') {
                    setTimeout(() => window.FamilyHubDashboard.loadModule('chat'), 300);
                }
            } catch (error) {
                setFeedback(feedbackEl, error.message, 'error');
            }
        };

        form.addEventListener('submit', submitHandler);
        handlers.set(form, submitHandler);
    }

    function detachHandler(form) {
        const handler = handlers.get(form);
        if (!handler) {
            return;
        }
        form.removeEventListener('submit', handler);
        handlers.delete(form);
    }

    window.FamilyHubModules.chat = {
        init(container) {
            const form = container.querySelector('#chat-form');
            const feedback = container.querySelector('#chat-feedback');

            if (!form) {
                return;
            }

            detachHandler(form);
            attachHandler(form, feedback);
        },
        destroy(container) {
            const form = container.querySelector('#chat-form');
            if (form) {
                detachHandler(form);
            }
        },
    };
})();

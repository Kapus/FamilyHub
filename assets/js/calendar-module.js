// calendar-module.js
// Initierar FullCalendar för Familyhub
(function () {
    if (!window.FamilyHubModules) {
        window.FamilyHubModules = {};
    }

    let calendarRef = null;

    function destroyCalendar() {
        if (calendarRef) {
            calendarRef.destroy();
            calendarRef = null;
        }
    }

    window.FamilyHubModules.calendar = {
        init(container) {
            destroyCalendar();

            const calendarEl = container.querySelector('#familyhub-calendar');
            if (!calendarEl) {
                return;
            }

            const controls = {
                today: container.querySelector('[data-calendar-action="today"]'),
                prev: container.querySelector('[data-calendar-action="prev"]'),
                next: container.querySelector('[data-calendar-action="next"]'),
                viewMonth: container.querySelector('[data-calendar-view="dayGridMonth"]'),
                viewWeek: container.querySelector('[data-calendar-view="dayGridWeek"]'),
                viewDay: container.querySelector('[data-calendar-view="dayGridDay"]'),
            };

            const formWrapper = container.querySelector('[data-calendar-form]');
            const toggleButton = container.querySelector('[data-calendar-toggle]');

            const viewButtons = [];

            calendarRef = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                height: 'auto',
                locale: 'sv',
                firstDay: 1,
                headerToolbar: false,
                buttonText: {
                    today: 'Idag',
                    month: 'Månad',
                    week: 'Vecka',
                    day: 'Dag',
                },
                events: async (info, success, failure) => {
                    try {
                        const params = new URLSearchParams({
                            start: info.startStr,
                            end: info.endStr,
                        });
                        const response = await fetch(`ajax/events.php?${params.toString()}`);
                        if (!response.ok) {
                            throw new Error('Kunde inte hämta kalenderdata.');
                        }
                        const data = await response.json();
                        success(data);
                    } catch (error) {
                        failure(error);
                    }
                },
                eventDidMount(info) {
                    if (info.backgroundColor) {
                        info.el.style.borderColor = info.backgroundColor;
                    }
                },
                datesSet() {
                    setActiveView(calendarRef.view.type);
                },
            });

            calendarRef.render();

            function setActiveView(viewType) {
                viewButtons.forEach(btn => btn.classList.remove('active'));
                if (viewType === 'dayGridMonth' && controls.viewMonth) {
                    controls.viewMonth.classList.add('active');
                } else if (viewType === 'dayGridWeek' && controls.viewWeek) {
                    controls.viewWeek.classList.add('active');
                } else if (viewType === 'dayGridDay' && controls.viewDay) {
                    controls.viewDay.classList.add('active');
                }
            }

            if (controls.today) {
                controls.today.addEventListener('click', () => calendarRef.today());
            }
            if (controls.prev) {
                controls.prev.addEventListener('click', () => calendarRef.prev());
            }
            if (controls.next) {
                controls.next.addEventListener('click', () => calendarRef.next());
            }
            if (controls.viewMonth) {
                viewButtons.push(controls.viewMonth);
                controls.viewMonth.addEventListener('click', () => calendarRef.changeView('dayGridMonth'));
            }
            if (controls.viewWeek) {
                viewButtons.push(controls.viewWeek);
                controls.viewWeek.addEventListener('click', () => calendarRef.changeView('dayGridWeek'));
            }
            if (controls.viewDay) {
                viewButtons.push(controls.viewDay);
                controls.viewDay.addEventListener('click', () => calendarRef.changeView('dayGridDay'));
            }

            const titleEl = container.querySelector('[data-calendar-title]');
            if (titleEl) {
                const updateTitle = () => {
                    titleEl.textContent = calendarRef.view.title;
                };
                calendarRef.on('datesSet', updateTitle);
                updateTitle();
            }

            setActiveView(calendarRef.view.type);

            const form = container.querySelector('#calendar-event-form');
            const feedback = container.querySelector('#calendar-feedback');

            const setFormVisible = (visible) => {
                if (!formWrapper || !toggleButton) {
                    return;
                }
                formWrapper.hidden = !visible;
                formWrapper.classList.toggle('d-none', !visible);
                toggleButton.textContent = visible ? 'Avbryt' : 'Lägg till';
                toggleButton.setAttribute('aria-expanded', visible ? 'true' : 'false');
                if (!visible && form) {
                    form.reset();
                    const colorField = form.querySelector('#event-color');
                    if (colorField) {
                        colorField.value = '#0d6efd';
                    }
                }
                if (!visible && feedback) {
                    feedback.textContent = '';
                    feedback.className = 'small';
                }
            };

            if (toggleButton && formWrapper) {
                setFormVisible(false);
                toggleButton.addEventListener('click', () => {
                    const willShow = formWrapper.hidden;
                    setFormVisible(willShow);
                    if (willShow) {
                        const focusTarget = form ? form.querySelector('input, select, textarea') : null;
                        if (focusTarget) {
                            focusTarget.focus();
                        }
                    }
                });
            }

            if (form) {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    if (feedback) {
                        feedback.textContent = '';
                        feedback.className = 'small';
                    }

                    const formData = new FormData(form);
                    const start = formData.get('startdatum');
                    let end = formData.get('slutdatum');

                    if (!start) {
                        if (feedback) {
                            feedback.textContent = 'Startdatum måste anges.';
                            feedback.classList.add('text-danger');
                        }
                        return;
                    }

                    if (end && end < start) {
                        if (feedback) {
                            feedback.textContent = 'Slutdatum kan inte vara före startdatum.';
                            feedback.classList.add('text-danger');
                        }
                        return;
                    }

                    if (!end) {
                        formData.set('slutdatum', start);
                        end = start;
                    }

                    try {
                        const response = await fetch('ajax/add_event.php', {
                            method: 'POST',
                            body: formData,
                        });
                        const result = await response.json();
                        if (!response.ok || !result.success) {
                            throw new Error(result.error || 'Kunde inte spara händelsen.');
                        }

                        if (feedback) {
                            feedback.textContent = 'Händelsen sparades.';
                            feedback.classList.add('text-success');
                        }
                        if (form) {
                            form.reset();
                            const colorField = form.querySelector('#event-color');
                            if (colorField) {
                                colorField.value = '#0d6efd';
                            }
                        }
                        calendarRef.refetchEvents();
                    } catch (error) {
                        if (feedback) {
                            feedback.textContent = error.message;
                            feedback.classList.add('text-danger');
                        }
                    }
                });
            }
        },
        destroy: destroyCalendar,
    };
})();

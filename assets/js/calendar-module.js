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

            const createButton = container.querySelector('[data-calendar-create]');
            const filterSelect = container.querySelector('[data-calendar-filter]');
            const modalEl = container.querySelector('#calendar-event-modal');
            const modalForm = modalEl ? modalEl.querySelector('#calendar-modal-form') : null;
            const modalTitle = modalEl ? modalEl.querySelector('[data-calendar-modal-title]') : null;
            const modalFeedback = modalEl ? modalEl.querySelector('#calendar-modal-feedback') : null;
            const saveButton = modalEl ? modalEl.querySelector('[data-calendar-save]') : null;
            const deleteButton = modalEl ? modalEl.querySelector('[data-calendar-delete]') : null;

            const applyFeedback = (element, message, status) => {
                if (!element) {
                    return;
                }
                const baseClass = element.dataset.baseClass ?? element.className;
                if (!element.dataset.baseClass) {
                    element.dataset.baseClass = baseClass;
                }
                element.className = baseClass || '';
                element.textContent = message || '';
                if (!message || !status) {
                    return;
                }
                if (status === 'success') {
                    element.classList.add('text-success');
                } else if (status === 'danger') {
                    element.classList.add('text-danger');
                }
            };

            let modalInstance = null;
            let modalMode = 'create';
            let currentEventId = null;

            const getActiveFilter = () => {
                if (!filterSelect) {
                    return 'all';
                }
                const value = filterSelect.value || 'all';
                return value;
            };

            const getModalInstance = () => {
                if (!modalEl) {
                    return null;
                }
                const modalFactory = typeof bootstrap !== 'undefined' && bootstrap.Modal
                    ? bootstrap.Modal
                    : null;
                if (!modalFactory) {
                    console.warn('Bootstrap Modal saknas, kan inte visa dialogen.');
                    return null;
                }
                modalInstance = modalFactory.getOrCreateInstance(modalEl);
                return modalInstance;
            };

            const resetModalForm = () => {
                if (!modalForm) {
                    return;
                }
                modalForm.reset();
                const colorField = modalForm.querySelector('#modal-event-color');
                if (colorField) {
                    colorField.value = '#0d6efd';
                }
                const idField = modalForm.querySelector('#modal-event-id');
                if (idField) {
                    idField.value = '';
                }
                applyFeedback(modalFeedback, '', null);
            };

            const setModalMode = (mode) => {
                modalMode = mode;
                if (!modalForm) {
                    return;
                }
                const titleLabel = modalTitle
                    ? (mode === 'edit'
                        ? (modalTitle.dataset.editLabel || 'Redigera händelse')
                        : (modalTitle.dataset.createLabel || 'Ny händelse'))
                    : null;
                if (modalTitle && titleLabel) {
                    modalTitle.textContent = titleLabel;
                }
                if (saveButton) {
                    const editText = saveButton.dataset.editText || saveButton.textContent || 'Spara ändringar';
                    const createText = saveButton.dataset.createText || 'Skapa händelse';
                    saveButton.textContent = mode === 'edit' ? editText : createText;
                }
                if (deleteButton) {
                    if (mode === 'edit') {
                        deleteButton.classList.remove('d-none');
                        deleteButton.disabled = false;
                    } else {
                        deleteButton.classList.add('d-none');
                        deleteButton.disabled = false;
                        deleteButton.dataset.eventId = '';
                    }
                }
            };

            const focusFirstField = () => {
                if (!modalForm) {
                    return;
                }
                const focusTarget = modalForm.querySelector('input, select, textarea');
                if (focusTarget) {
                    try {
                        focusTarget.focus({ preventScroll: true });
                    } catch (error) {
                        focusTarget.focus();
                    }
                }
            };

            const openCreateModal = () => {
                if (!modalForm) {
                    return;
                }
                currentEventId = null;
                resetModalForm();
                setModalMode('create');

                const userField = modalForm.querySelector('#modal-event-user');
                if (userField) {
                    const filterValue = getActiveFilter();
                    if (filterValue === 'family' || filterValue === 'all') {
                        userField.value = '';
                    } else {
                        userField.value = filterValue;
                    }
                }

                const instance = getModalInstance();
                if (instance) {
                    instance.show();
                }
            };

            const openEditModal = (calendarEvent) => {
                if (!modalForm || !calendarEvent) {
                    return;
                }
                resetModalForm();
                setModalMode('edit');

                const startDate = calendarEvent.startStr ?? '';
                const inclusiveEnd = calendarEvent.extendedProps && calendarEvent.extendedProps.inclusiveEnd
                    ? calendarEvent.extendedProps.inclusiveEnd
                    : startDate;
                const userValue = calendarEvent.extendedProps && typeof calendarEvent.extendedProps.anvandarId !== 'undefined'
                    ? calendarEvent.extendedProps.anvandarId
                    : '';
                const colorValue = calendarEvent.backgroundColor
                    || (calendarEvent.extendedProps && calendarEvent.extendedProps.color)
                    || '#0d6efd';

                const idField = modalForm.querySelector('#modal-event-id');
                const titleField = modalForm.querySelector('#modal-event-title');
                const startField = modalForm.querySelector('#modal-event-start');
                const endField = modalForm.querySelector('#modal-event-end');
                const userField = modalForm.querySelector('#modal-event-user');
                const colorField = modalForm.querySelector('#modal-event-color');

                if (idField) {
                    idField.value = calendarEvent.id;
                }
                currentEventId = calendarEvent.id;
                if (titleField) {
                    titleField.value = calendarEvent.title || '';
                }
                if (startField) {
                    startField.value = startDate;
                }
                if (endField) {
                    endField.value = inclusiveEnd || '';
                }
                if (userField) {
                    userField.value = userValue === null || userValue === ''
                        ? ''
                        : String(userValue);
                }
                if (colorField) {
                    colorField.value = colorValue;
                }

                if (deleteButton) {
                    deleteButton.dataset.eventId = calendarEvent.id;
                    deleteButton.disabled = false;
                }

                applyFeedback(modalFeedback, '', null);

                const instance = getModalInstance();
                if (instance) {
                    instance.show();
                }
            };

            resetModalForm();
            setModalMode('create');

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
                            _: Date.now().toString(),
                        });
                        const filterValue = getActiveFilter();
                        if (filterValue && filterValue !== 'all') {
                            params.set('userId', filterValue);
                        }
                        const response = await fetch(`ajax/events.php?${params.toString()}`);
                        if (!response.ok) {
                            throw new Error('Kunde inte hämta kalenderdata.');
                        }
                        const data = await response.json();
                        success(data);
                    } catch (error) {
                        console.error('FullCalendar events fetch failed:', error);
                        failure(error);
                    }
                },
                eventDidMount(info) {
                    if (info.backgroundColor) {
                        info.el.style.borderColor = info.backgroundColor;
                    }
                },
                eventClick(info) {
                    info.jsEvent.preventDefault();
                    openEditModal(info.event);
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

            if (createButton) {
                createButton.addEventListener('click', () => openCreateModal());
            }

            if (filterSelect) {
                filterSelect.addEventListener('change', () => {
                    calendarRef.refetchEvents();
                });
            }

            if (modalEl) {
                modalEl.addEventListener('shown.bs.modal', () => {
                    focusFirstField();
                });
                modalEl.addEventListener('hidden.bs.modal', () => {
                    currentEventId = null;
                    resetModalForm();
                    setModalMode('create');
                });
            }

            if (modalForm) {
                modalForm.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    applyFeedback(modalFeedback, '', null);

                    const formData = new FormData(modalForm);
                    const start = formData.get('startdatum');
                    let end = formData.get('slutdatum');

                    if (!start) {
                        applyFeedback(modalFeedback, 'Startdatum måste anges.', 'danger');
                        return;
                    }

                    if (end && end < start) {
                        applyFeedback(modalFeedback, 'Slutdatum kan inte vara före startdatum.', 'danger');
                        return;
                    }

                    if (!end) {
                        formData.set('slutdatum', start);
                        end = start;
                    }

                    let endpoint = 'ajax/add_event.php';
                    let successMessage = 'Händelsen skapades.';

                    if (modalMode === 'edit') {
                        if (!currentEventId) {
                            applyFeedback(modalFeedback, 'Händelsen saknar identitet.', 'danger');
                            return;
                        }
                        formData.set('event_id', currentEventId);
                        endpoint = 'ajax/update_event.php';
                        successMessage = 'Händelsen uppdaterades.';
                    } else {
                        formData.delete('event_id');
                    }

                    if (saveButton) {
                        saveButton.disabled = true;
                    }
                    if (deleteButton && modalMode === 'edit') {
                        deleteButton.disabled = true;
                    }

                    try {
                        const response = await fetch(endpoint, {
                            method: 'POST',
                            body: formData,
                        });
                        const result = await response.json();
                        if (!response.ok || !result.success) {
                            throw new Error(result.error || 'Kunde inte spara händelsen.');
                        }

                        applyFeedback(modalFeedback, successMessage, 'success');
                        calendarRef.refetchEvents();
                        const instance = getModalInstance();
                        if (instance) {
                            instance.hide();
                        }
                    } catch (error) {
                        applyFeedback(modalFeedback, error.message, 'danger');
                    } finally {
                        if (saveButton) {
                            saveButton.disabled = false;
                        }
                        if (deleteButton && modalMode === 'edit') {
                            deleteButton.disabled = false;
                        }
                    }
                });
            }

            if (deleteButton) {
                deleteButton.addEventListener('click', async () => {
                    if (modalMode !== 'edit' || !currentEventId) {
                        return;
                    }
                    if (!window.confirm('Vill du ta bort den här händelsen?')) {
                        return;
                    }

                    applyFeedback(modalFeedback, '', null);
                    deleteButton.disabled = true;
                    const payload = new FormData();
                    payload.append('event_id', currentEventId);

                    try {
                        const response = await fetch('ajax/delete_event.php', {
                            method: 'POST',
                            body: payload,
                        });
                        const result = await response.json();
                        if (!response.ok || !result.success) {
                            throw new Error(result.error || 'Kunde inte ta bort händelsen.');
                        }

                        applyFeedback(modalFeedback, 'Händelsen togs bort.', 'success');
                        calendarRef.refetchEvents();
                        const instance = getModalInstance();
                        if (instance) {
                            instance.hide();
                        }
                    } catch (error) {
                        applyFeedback(modalFeedback, error.message, 'danger');
                    } finally {
                        deleteButton.disabled = false;
                    }
                });
            }
        },
        destroy: destroyCalendar,
    };
})();

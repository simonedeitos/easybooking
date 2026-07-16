/* ── EasyBooking Calendar (FullCalendar v6) ─────────────────── */

let calendarInstance = null;
let calendarToolbarBound = false;

const CalendarColors = {
    byStatus: {
        'Programmata':   '#7c6af7',
        'Svolta':        '#a6e3a1',
        'Assente':       '#f38ba8',
        'Rimandata':     '#f9e2af',
        'Riprogrammata': '#89dceb',
    },
    byTeacher: [
        '#7c6af7','#89dceb','#a6e3a1','#f9e2af','#f38ba8',
        '#cba6f7','#fab387','#94e2d5','#eba0ac','#b4befe',
    ],
    teacherMap: {}
};

function getTeacherColor(teacherId) {
    if (!CalendarColors.teacherMap[teacherId]) {
        const numericId = Math.abs(parseInt(teacherId, 10) || 0);
        const idx = numericId % CalendarColors.byTeacher.length;
        CalendarColors.teacherMap[teacherId] = CalendarColors.byTeacher[idx];
    }
    return CalendarColors.teacherMap[teacherId];
}

// ── Initialise FullCalendar ───────────────────────────────────
function initCalendar(options = {}) {
    const calEl = document.getElementById('calendar');
    if (!calEl) return;

    const colorMode = options.colorMode || 'status'; // 'status' | 'teacher'
    // Teacher filter value is passed explicitly by renderCalendar() at construction
    // time. Defaults to empty string (= show all teachers) if not provided.
    const teacherFilterValue = typeof options.teacherFilterValue === 'string'
        ? options.teacherFilterValue
        : '';

    console.debug('[EasyBooking] initCalendar → insegnante_id=' + (teacherFilterValue || 'tutti') + ' view=' + (options.initialView || 'timeGridWeek'));

    try {
        calendarInstance = new FullCalendar.Calendar(calEl, {
            initialView: options.initialView || 'timeGridWeek',
            locale: 'it',
            headerToolbar: false, // custom toolbar
            height: 'auto',
            allDaySlot: false,
            slotMinTime: options.slotMin || '08:00:00',
            slotMaxTime: options.slotMax || '21:00:00',
            slotDuration: '00:30:00',
            slotLabelInterval: '01:00:00',
            nowIndicator: true,
            editable: true,
            selectable: true,
            selectMirror: true,
            businessHours: options.businessHours || true,
            scrollTime: '09:00:00',

            // Teacher filter value is captured at construction time (passed explicitly
            // via options.teacherFilterValue after a destroy+recreate cycle) and baked
            // into extraParams. This eliminates any stale-closure or stale-cache issue
            // that could affect a refetchEvents()-only approach.
            eventSources: [{
                url: 'api/get-eventi-calendario.php',
                extraParams() {
                    const params = { cacheBust: Date.now() };
                    if (teacherFilterValue) params.insegnante_id = teacherFilterValue;
                    return params;
                },
                failure(err) {
                    console.error('Calendar events error:', err);
                    showToast('Impossibile caricare il calendario. Riprova.', 'danger');
                }
            }],

            // Color events
            eventDidMount(info) {
                const ev = info.event;
                const extProps = ev.extendedProps;
                let color = ev.backgroundColor || '#7c6af7';
                if (colorMode === 'teacher') {
                    color = getTeacherColor(extProps.insegnante_id);
                } else {
                    color = CalendarColors.byStatus[extProps.stato] || color;
                }
                const textColor = isDark(color) ? '#fff' : '#000';
                info.el.style.backgroundColor    = color;
                info.el.style.borderColor        = color;
                info.el.style.color              = textColor;
                // Apply colour to all child text elements so theme CSS cannot override inheritance.
                // The querySelectorAll is already scoped to info.el (.fc-event), so 'a' matches
                // only anchor elements that are descendants of this specific event element.
                info.el.querySelectorAll('.fc-event-main, .fc-event-main-frame, .fc-event-title-container, .fc-event-title, .fc-event-time, a').forEach(function(child) {
                    child.style.color = textColor;
                });
                info.el.style.borderRadius       = '5px';
                info.el.style.fontSize           = '0.78rem';
                info.el.setAttribute('data-bs-toggle', 'tooltip');
                info.el.setAttribute('title', buildTooltip(extProps));
                new bootstrap.Tooltip(info.el, { trigger: 'hover', html: true });
            },

            // Click existing event → open edit modal
            eventClick(info) {
                openEventModal(info.event);
            },

            // Click empty slot → open create modal
            select(info) {
                openNewEventModal(info.startStr, info.endStr);
                calendarInstance.unselect();
            },

            // Drag to move
            eventDrop(info) {
                saveEventMove(info.event, info.revert);
            },

            // Resize
            eventResize(info) {
                saveEventResize(info.event, info.revert);
            }
        });

        calendarInstance.render();
        calendarInstance.on('datesSet', () => {
            const titleEl = document.getElementById('cal-title');
            if (titleEl) {
                titleEl.textContent = calendarInstance.view.title;
            }
        });
        bindCalendarToolbar();
        updateViewButtons(options.initialView || 'timeGridWeek');
    } catch (error) {
        console.error('Calendar initialization failed:', error);
        calEl.innerHTML = '<div class="alert alert-danger mb-0">Errore inizializzazione calendario. Controlla la console.</div>';
    }
}

// ── Toolbar bindings ──────────────────────────────────────────
function bindCalendarToolbar() {
    if (calendarToolbarBound) {
        return;
    }
    calendarToolbarBound = true;
    document.getElementById('cal-prev')?.addEventListener('click', () => calendarInstance?.prev());
    document.getElementById('cal-next')?.addEventListener('click', () => calendarInstance?.next());
    document.getElementById('cal-today')?.addEventListener('click', () => calendarInstance?.today());
    document.getElementById('cal-view-week')?.addEventListener('click', () => {
        calendarInstance?.changeView('timeGridWeek');
        updateViewButtons('timeGridWeek');
    });
    document.getElementById('cal-view-month')?.addEventListener('click', () => {
        calendarInstance?.changeView('dayGridMonth');
        updateViewButtons('dayGridMonth');
    });
    document.getElementById('cal-view-day')?.addEventListener('click', () => {
        calendarInstance?.changeView('timeGridDay');
        updateViewButtons('timeGridDay');
    });
}

// View-type to toolbar button ID mapping (used by updateViewButtons)
const VIEW_BUTTON_MAP = {
    'timeGridWeek': 'cal-view-week',
    'dayGridMonth': 'cal-view-month',
    'timeGridDay':  'cal-view-day',
};

// ── Update view button active state ───────────────────────────
function updateViewButtons(viewType) {
    const activeId = VIEW_BUTTON_MAP[viewType] || 'cal-view-week';
    document.querySelectorAll('.cal-view-btn').forEach(function(btn) {
        const isActive = btn.id === activeId;
        btn.classList.toggle('btn-primary', isActive);
        btn.classList.toggle('btn-outline-light', !isActive);
        btn.classList.toggle('active', isActive);
    });
}

// ── Build tooltip HTML ────────────────────────────────────────
function buildTooltip(props) {
    const cliente = props.cliente || 'N/D';
    const insegnante = props.insegnante || 'N/D';
    return `<b>${escapeHtml(cliente)}</b><br>
            ${escapeHtml(insegnante)}${props.strumento ? ' – ' + escapeHtml(props.strumento) : ''}<br>
            Stato: ${escapeHtml(props.stato || '')}`;
}

// ── Open event edit modal ─────────────────────────────────────
function openEventModal(event) {
    const modal = document.getElementById('eventModal');
    if (!modal) return;

    const props = event.extendedProps;
    const m = new bootstrap.Modal(modal);

    modal.querySelector('#event-id').value          = event.id;
    modal.querySelector('#event-data').value        = event.startStr.slice(0, 10);
    modal.querySelector('#event-ora-inizio').value  = event.startStr.slice(11, 16);
    modal.querySelector('#event-ora-fine').value    = event.endStr?.slice(11, 16) || '';
    modal.querySelector('#event-stato').value       = props.stato || 'Programmata';
    modal.querySelector('#event-note').value        = props.note || '';

    const titleEl = modal.querySelector('.modal-title');
    if (titleEl) titleEl.textContent = `Lezione: ${props.cliente || ''} - ${props.insegnante || ''}`;

    m.show();
}

// ── Open new event modal ──────────────────────────────────────
function openNewEventModal(startStr, endStr) {
    const modal = document.getElementById('newEventModal');
    if (!modal) return;

    const m = new bootstrap.Modal(modal);
    if (startStr) {
        const dateInput = modal.querySelector('#new-event-data');
        const startInput = modal.querySelector('#new-event-ora-inizio');
        const endInput   = modal.querySelector('#new-event-ora-fine');
        if (dateInput) dateInput.value = startStr.slice(0, 10);
        if (startInput && startStr.length > 10) startInput.value = startStr.slice(11, 16);
        if (endInput   && endStr?.length > 10)  endInput.value   = endStr.slice(11, 16);
    }
    m.show();
}

// ── Save drag-move ────────────────────────────────────────────
function askMoveConfirmation(event) {
    const fallbackStatus = Object.hasOwn(CalendarColors.byStatus, 'Riprogrammata')
        ? 'Riprogrammata'
        : (Object.keys(CalendarColors.byStatus)[0] || 'Programmata');

    if (!window.bootstrap?.Modal) {
        const startDate = event.startStr.slice(0, 10);
        const startTime = event.startStr.slice(11, 16);
        const endTime = event.endStr?.slice(11, 16) || '';
        const safeStartDate = startDate.replace(/[^0-9-]/g, '');
        const safeStartTime = startTime.replace(/[^0-9:]/g, '');
        const safeEndTime = endTime.replace(/[^0-9:]/g, '');
        const confirmed = confirm(`Confermi lo spostamento al ${safeStartDate} dalle ${safeStartTime} alle ${safeEndTime}?`);
        if (!confirmed) return Promise.resolve(null);
        const selectedStatus = prompt('Stato appuntamento:', fallbackStatus);
        if (selectedStatus === null) return Promise.resolve(null);
        return Promise.resolve({ stato: selectedStatus.trim() || fallbackStatus });
    }

    return new Promise((resolve) => {
        const modalWrapper = document.createElement('div');
        const statusOptions = Object.keys(CalendarColors.byStatus)
            .map((status) => `<option value="${escapeHtml(status)}">${escapeHtml(status)}</option>`)
            .join('');
        const dateLabel = event.start
            ? event.start.toLocaleDateString('it-IT', { weekday: 'long', day: '2-digit', month: '2-digit', year: 'numeric' })
            : event.startStr.slice(0, 10);
        const startTime = event.startStr.slice(11, 16);
        const endTime = event.endStr?.slice(11, 16) || '';
        modalWrapper.innerHTML = `
            <div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="move-modal-title" aria-describedby="move-modal-description">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="move-modal-title">Conferma spostamento</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                        </div>
                        <div class="modal-body" id="move-modal-description">
                            <p class="mb-2">Nuova data e ora:</p>
                            <p class="fw-semibold mb-3">${escapeHtml(dateLabel)} · ${escapeHtml(startTime)} - ${escapeHtml(endTime)}</p>
                            <label for="move-event-status" class="form-label">Stato appuntamento</label>
                            <select id="move-event-status" class="form-select">${statusOptions}</select>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-light" data-action="cancel">Annulla</button>
                            <button type="button" class="btn btn-primary" data-action="confirm">Salva</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        const modalEl = modalWrapper.firstElementChild;
        document.body.appendChild(modalEl);
        const modal = new bootstrap.Modal(modalEl, { backdrop: 'static' });
        const statusSelect = modalEl.querySelector('#move-event-status');
        statusSelect.value = fallbackStatus;

        let modalResolved = false;
        const finish = (result) => {
            if (modalResolved) return;
            modalResolved = true;
            resolve(result);
            modal.hide();
        };

        modalEl.querySelector('[data-action="cancel"]')?.addEventListener('click', () => finish(null));
        modalEl.querySelector('[data-action="confirm"]')?.addEventListener('click', () => {
            finish({ stato: statusSelect?.value || fallbackStatus });
        });
        modalEl.addEventListener('hidden.bs.modal', () => {
            if (!modalResolved) {
                modalResolved = true;
                resolve(null);
            }
            modal.dispose();
            modalEl.remove();
        });

        modal.show();
    });
}

async function saveEventMove(event, revertFn) {
    const data = {
        id: event.id,
        data: event.startStr.slice(0, 10),
        ora_inizio: event.startStr.slice(11, 16),
        ora_fine: event.endStr?.slice(11, 16) || '',
        action: 'move_event',
        csrf_token: getCsrfToken()
    };
    const ok = await checkConflict(event.id, data.data, data.ora_inizio, data.ora_fine, event.extendedProps.insegnante_id);
    if (!ok) {
        showToast('Conflitto: l\'insegnante ha già una lezione in questo orario', 'warning');
        revertFn();
        return;
    }
    const moveConfirmation = await askMoveConfirmation(event);
    if (!moveConfirmation) {
        revertFn();
        return;
    }
    data.stato = moveConfirmation.stato;
    try {
        const resp = await fetch('calendario.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(data).toString()
        });
        const result = await resp.json();
        if (!result.success) { showToast(result.message || 'Errore salvataggio', 'danger'); revertFn(); }
        else showToast('Lezione spostata', 'success');
    } catch (e) {
        showToast('Errore di rete', 'danger'); revertFn();
    }
}

// ── Save resize ────────────────────────────────────────────────
async function saveEventResize(event, revertFn) {
    const data = {
        id: event.id,
        ora_fine: event.endStr?.slice(11, 16) || '',
        action: 'resize_event',
        csrf_token: getCsrfToken()
    };
    try {
        const resp = await fetch('calendario.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(data).toString()
        });
        const result = await resp.json();
        if (!result.success) { showToast(result.message || 'Errore ridimensionamento', 'danger'); revertFn(); }
        else showToast('Orario fine aggiornato', 'success');
    } catch (e) {
        showToast('Errore di rete', 'danger'); revertFn();
    }
}

// ── Conflict detection ────────────────────────────────────────
async function checkConflict(excludeId, data, oraInizio, oraFine, insegnanteId) {
    try {
        const params = new URLSearchParams({
            action: 'check_conflict',
            exclude_id: excludeId,
            data, ora_inizio: oraInizio, ora_fine: oraFine,
            insegnante_id: insegnanteId,
            csrf_token: getCsrfToken()
        });
        const resp   = await fetch('calendario.php?' + params.toString());
        const result = await resp.json();
        return !result.conflict;
    } catch (e) {
        return true; // allow on error
    }
}

// ── Utility: is dark color ────────────────────────────────────
function isDark(hex) {
    hex = hex.replace('#', '');
    const r = parseInt(hex.slice(0,2),16),
          g = parseInt(hex.slice(2,4),16),
          b = parseInt(hex.slice(4,6),16);
    const luminance = (0.299*r + 0.587*g + 0.114*b) / 255;
    return luminance < 0.5;
}

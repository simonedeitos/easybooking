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
        const numericId = Math.abs(Number(teacherId) || 0);
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
    const teacherFilter = document.getElementById('calendarTeacherFilter');

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

            // Load events from server
            events(info, successCallback, failureCallback) {
                const params = new URLSearchParams({
                    start: info.startStr,
                    end: info.endStr
                });
                const teacherId = teacherFilter?.value || '';
                if (teacherId !== '') {
                    params.append('insegnante_id', teacherId);
                }

                fetch('api/get-eventi-calendario.php?' + params.toString())
                    .then((response) => {
                        if (!response.ok) {
                            throw new Error('HTTP ' + response.status);
                        }
                        return response.json();
                    })
                    .then((data) => {
                        if (!Array.isArray(data)) {
                            const message = data?.error || 'Risposta calendario non valida.';
                            throw new Error(message);
                        }
                        successCallback(data);
                    })
                    .catch((error) => {
                        console.error('Calendar events error:', error);
                        showToast('Impossibile caricare il calendario. Riprova.', 'danger');
                        failureCallback(error);
                    });
            },

            // Color events
            eventDidMount(info) {
                const ev = info.event;
                const extProps = ev.extendedProps || {};
                let color = ev.backgroundColor || '#7c6af7';
                if (colorMode === 'teacher') {
                    color = getTeacherColor(extProps.insegnante_id);
                } else {
                    color = CalendarColors.byStatus[extProps.stato] || color;
                }
                info.el.style.backgroundColor    = color;
                info.el.style.borderColor        = color;
                info.el.style.color              = isDark(color) ? '#fff' : '#1e1e2e';
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
        bindCalendarToolbar();
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
        document.querySelectorAll('.cal-view-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('cal-view-week')?.classList.add('active');
    });
    document.getElementById('cal-view-month')?.addEventListener('click', () => {
        calendarInstance?.changeView('dayGridMonth');
        document.querySelectorAll('.cal-view-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('cal-view-month')?.classList.add('active');
    });
    document.getElementById('cal-view-day')?.addEventListener('click', () => {
        calendarInstance?.changeView('timeGridDay');
        document.querySelectorAll('.cal-view-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('cal-view-day')?.classList.add('active');
    });

    // Update title on navigation
    calendarInstance?.on('datesSet', () => {
        const titleEl = document.getElementById('cal-title');
        if (titleEl) titleEl.textContent = calendarInstance.view.title;
    });
}

// ── Build tooltip HTML ────────────────────────────────────────
function buildTooltip(props) {
    const cliente = props.cliente || [props.cliente_nome, props.cliente_cognome].filter(Boolean).join(' ');
    const insegnante = props.insegnante || [props.insegnante_nome, props.insegnante_cognome].filter(Boolean).join(' ');
    return `<b>${escapeHtml(cliente || '')}</b><br>
            ${escapeHtml(insegnante || 'N/D')}${props.strumento ? ' – ' + escapeHtml(props.strumento) : ''}<br>
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

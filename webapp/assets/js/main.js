/* ── Main JavaScript for EasyBooking ───────────────────────── */

// ── Theme Management ─────────────────────────────────────────
const ThemeManager = (() => {
    const DARK  = 'dark';
    const LIGHT = 'light';
    const KEY   = 'eb_theme';

    function apply(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        // swap stylesheet
        const darkLink  = document.getElementById('theme-dark-css');
        const lightLink = document.getElementById('theme-light-css');
        if (darkLink)  darkLink.disabled  = (theme !== DARK);
        if (lightLink) lightLink.disabled = (theme !== LIGHT);
        // update toggle icon
        const icon = document.getElementById('theme-toggle-icon');
        if (icon) {
            icon.className = theme === DARK ? 'fas fa-sun' : 'fas fa-moon';
        }
        localStorage.setItem(KEY, theme);
    }

    function toggle() {
        const current = localStorage.getItem(KEY) || DARK;
        const next = current === DARK ? LIGHT : DARK;
        apply(next);
        // Persist to server
        fetch('impostazioni.php?action=set_theme', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': getCsrfToken()
            },
            body: 'theme=' + encodeURIComponent(next) + '&csrf_token=' + encodeURIComponent(getCsrfToken())
        }).catch(() => {/* non-critical */});
    }

    function init() {
        // The server sets the authoritative theme on the <html> element via
        // the data-theme attribute.  Use that as the source of truth and
        // sync localStorage so the two stay in agreement.  This prevents
        // a stale localStorage value from overriding the user's saved
        // database preference on every page load.
        const serverTheme = document.documentElement.dataset.theme;
        const resolved = serverTheme || localStorage.getItem(KEY) || DARK;
        // Keep localStorage in sync with the server value.
        localStorage.setItem(KEY, resolved);
        apply(resolved);
    }

    return { init, toggle, apply };
})();

// ── CSRF Helpers ─────────────────────────────────────────────
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

// Auto-attach CSRF and AJAX marker to all non-GET fetch requests
const _origFetch = window.fetch;
window.fetch = function(url, opts = {}) {
    if (opts.method && opts.method.toUpperCase() !== 'GET') {
        if (!opts.headers) opts.headers = {};
        opts.headers['X-CSRF-Token'] = getCsrfToken();
        // Mark as XMLHttpRequest so the server can detect AJAX calls
        // and return JSON error responses instead of HTML redirects.
        opts.headers['X-Requested-With'] = 'XMLHttpRequest';
    }
    return _origFetch(url, opts);
};

// ── Sidebar Toggle ────────────────────────────────────────────
function initSidebar() {
    const sidebar  = document.getElementById('sidebar');
    const overlay  = document.getElementById('sidebar-overlay');
    const hamburger = document.getElementById('hamburger-btn');

    function openSidebar() {
        sidebar?.classList.add('open');
        overlay?.classList.add('open');
    }
    function closeSidebar() {
        sidebar?.classList.remove('open');
        overlay?.classList.remove('open');
    }

    hamburger?.addEventListener('click', () => {
        sidebar?.classList.contains('open') ? closeSidebar() : openSidebar();
    });
    overlay?.addEventListener('click', closeSidebar);
}

// ── Toast Notifications ───────────────────────────────────────
function showToast(message, type = 'info', duration = 4000) {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(container);
    }
    const icons = { success: 'fa-check-circle', danger: 'fa-times-circle', warning: 'fa-exclamation-triangle', info: 'fa-info-circle' };
    const icon  = icons[type] || icons.info;
    const id    = 'toast-' + Date.now();
    const html  = `
    <div id="${id}" class="toast align-items-center" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <i class="fas ${icon} me-2 text-${type}"></i>
            <strong class="me-auto">EasyBooking</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">${escapeHtml(message)}</div>
    </div>`;
    container.insertAdjacentHTML('beforeend', html);
    const toastEl = document.getElementById(id);
    const toast = new bootstrap.Toast(toastEl, { delay: duration });
    toast.show();
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

// ── Confirm Dialogs ───────────────────────────────────────────
function confirmDelete(form, name = 'questo elemento') {
    if (confirm('Sei sicuro di voler eliminare ' + name + '?\nQuesta azione non può essere annullata.')) {
        form.submit();
    }
    return false;
}

function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// ── DataTables Default Init ───────────────────────────────────
function initDataTables() {
    if (typeof $ === 'undefined' || typeof $.fn.DataTable === 'undefined') return;

    $('.datatable').each(function() {
        if (!$.fn.DataTable.isDataTable(this)) {
            $(this).DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/it-IT.json'
                },
                pageLength: 25,
                responsive: true,
                order: [],
                dom: '<"row align-items-center mb-3"<"col-sm-6"l><"col-sm-6 text-end"f>>rt<"row mt-3"<"col-sm-6"i><"col-sm-6"p>>',
                columnDefs: [
                    { orderable: false, targets: [-1] }
                ]
            });
        }
    });
}

// ── AJAX Form Submit Helper ───────────────────────────────────
function ajaxForm(formEl, onSuccess, onError) {
    formEl.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = formEl.querySelector('[type="submit"]');
        const original = btn?.innerHTML;
        if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Salvataggio...'; }

        try {
            const fd = new FormData(formEl);
            const resp = await fetch(formEl.action || window.location.href, {
                method: 'POST',
                headers: { 'X-CSRF-Token': getCsrfToken() },
                body: fd
            });
            const data = await resp.json();
            if (data.success) {
                onSuccess(data);
            } else {
                onError(data.message || 'Errore sconosciuto');
            }
        } catch (err) {
            onError('Errore di rete: ' + err.message);
        } finally {
            if (btn) { btn.disabled = false; btn.innerHTML = original; }
        }
    });
}

// ── Inline Status Update ──────────────────────────────────────
function updateStatus(url, id, newStatus, csrfToken) {
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': csrfToken
        },
        body: `id=${encodeURIComponent(id)}&stato=${encodeURIComponent(newStatus)}&action=update_status&csrf_token=${encodeURIComponent(csrfToken)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Stato aggiornato', 'success');
        } else {
            showToast(data.message || 'Errore aggiornamento', 'danger');
        }
    })
    .catch(() => showToast('Errore di rete', 'danger'));
}

// ── Modal Reset ───────────────────────────────────────────────
function resetModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    modal.querySelectorAll('input:not([type=hidden]), textarea, select').forEach(el => {
        if (el.type === 'checkbox' || el.type === 'radio') {
            el.checked = el.defaultChecked;
        } else {
            el.value = el.defaultValue || '';
        }
    });
    modal.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    modal.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
}

// ── Number Formatting ─────────────────────────────────────────
function formatCurrency(num) {
    return '€\u00a0' + parseFloat(num).toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// ── Bootstrap tooltips and popovers init ─────────────────────
function initTooltips() {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el, { trigger: 'hover' });
    });
}

// ── DOMContentLoaded ─────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    ThemeManager.init();
    initSidebar();
    initDataTables();
    initTooltips();

    // Theme toggle button
    document.getElementById('theme-toggle')?.addEventListener('click', () => ThemeManager.toggle());

    // Dismiss alerts on click
    document.querySelectorAll('.alert-dismissible .btn-close').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.alert')?.remove();
        });
    });

    // Auto-focus first input in modals
    document.querySelectorAll('.modal').forEach(modalEl => {
        modalEl.addEventListener('shown.bs.modal', () => {
            const first = modalEl.querySelector('input:not([type=hidden]):not([readonly]), textarea, select');
            first?.focus();
        });
    });
});

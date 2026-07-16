/**
 * cloud.js – Frontend logic for Cloud Storage management page
 * Dependencies: Bootstrap 5, Font Awesome 6
 */

(function () {
    'use strict';

    // ── Utilities ─────────────────────────────────────────────────────────

    function getCsrf() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        if (!container) return;
        const id   = 'toast-' + Date.now();
        const icon = type === 'success' ? 'fa-check-circle' : type === 'danger' ? 'fa-times-circle' : 'fa-info-circle';
        const html = `<div id="${id}" class="toast align-items-center text-bg-${type} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body"><i class="fas ${icon} me-2"></i>${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>`;
        container.insertAdjacentHTML('beforeend', html);
        const el    = document.getElementById(id);
        const toast = new bootstrap.Toast(el, { delay: 4000 });
        toast.show();
        el.addEventListener('hidden.bs.toast', () => el.remove());
    }

    function htmlEsc(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // ── Cloud Stats (compact badge) ───────────────────────────────────────

    function refreshStats() {
        fetch('api/cloud-api.php?action=get_stats')
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                const s   = data.stats;
                const pct = Math.min(100, s.percentuale);

                const barEl  = document.getElementById('cloud-stats-bar');
                const textEl = document.getElementById('cloud-stats-text');
                if (barEl) {
                    barEl.style.width = pct + '%';
                    barEl.setAttribute('aria-valuenow', pct);
                    barEl.className = 'progress-bar ' +
                        (pct >= 90 ? 'bg-danger' : pct >= 70 ? 'bg-warning' : 'bg-success');
                }
                if (textEl) {
                    textEl.textContent = s.spazio_human + ' / ' + s.spazio_max_human + ' (' + pct + '%)';
                }

                // Update badge icon color
                const badge = document.getElementById('cloud-quota-badge');
                if (badge) {
                    const icon = badge.querySelector('.fa-hdd');
                    if (icon) {
                        icon.className = 'fas fa-hdd ' + (pct >= 90 ? 'text-danger' : pct >= 70 ? 'text-warning' : 'text-success');
                    }
                }
            })
            .catch(() => {});
    }

    // ── Client selection ──────────────────────────────────────────────────

    let currentClienteId   = null;
    let currentClienteHash = null;

    function selectClient(id, nome, hash) {
        currentClienteId   = id;
        currentClienteHash = hash;

        // Highlight active item
        document.querySelectorAll('.cloud-client-item').forEach(item => {
            const btn = item.querySelector('.cloud-client-btn');
            item.classList.toggle('active', btn?.dataset.clientId === String(id));
        });
        // Fallback for plain buttons (no wrapper)
        document.querySelectorAll('.cloud-client-btn').forEach(btn => {
            if (!btn.closest('.cloud-client-item')) {
                btn.classList.toggle('active', btn.dataset.clientId === String(id));
            }
        });

        // Show detail panel
        const emptyState  = document.getElementById('cloud-empty-state');
        const detailPanel = document.getElementById('cloud-client-detail');
        if (emptyState)  emptyState.classList.add('d-none');
        if (detailPanel) detailPanel.classList.remove('d-none');

        // Update toolbar
        const nameEl = document.getElementById('cloud-toolbar-name');
        if (nameEl) nameEl.textContent = nome;

        loadFiles(id);
    }

    function bindClientButtons() {
        document.querySelectorAll('.cloud-client-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                selectClient(
                    btn.dataset.clientId,
                    btn.dataset.clientNome || 'Cliente',
                    btn.dataset.clientHash || ''
                );
            });
        });

        // Sidebar copy-link buttons
        document.querySelectorAll('.cloud-sidebar-copy-link').forEach(btn => {
            btn.addEventListener('click', e => {
                e.stopPropagation();
                const hash = btn.dataset.hash;
                if (!hash) return;
                const url = buildShareUrl(hash);
                cloudCopyLink(url);
            });
        });
    }

    // ── File list ─────────────────────────────────────────────────────────

    function loadFiles(clienteId) {
        const container = document.getElementById('cloud-files-list');
        if (!container) return;
        container.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm"></div> Caricamento…</div>';

        fetch('api/cloud-api.php?action=list_files&cliente_id=' + encodeURIComponent(clienteId))
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    container.innerHTML = '<div class="p-3 text-danger">Errore nel caricamento dei file.</div>';
                    return;
                }
                renderFileList(container, data.files);
                updateToolbarStats(data.files);
            })
            .catch(() => {
                container.innerHTML = '<div class="p-3 text-danger">Errore di rete.</div>';
            });
    }

    function updateToolbarStats(files) {
        const filesEl = document.getElementById('cloud-toolbar-files');
        const spaceEl = document.getElementById('cloud-toolbar-space');
        if (filesEl) filesEl.textContent = files.length;
        if (spaceEl) {
            const total = files.reduce((sum, f) => sum + (parseInt(f.dimensione_bytes, 10) || 0), 0);
            spaceEl.textContent = formatBytes(total);
        }
    }

    function formatBytes(bytes) {
        if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' GB';
        if (bytes >= 1048576)    return (bytes / 1048576).toFixed(2) + ' MB';
        if (bytes >= 1024)       return (bytes / 1024).toFixed(2) + ' KB';
        return bytes + ' B';
    }

    /**
     * Builds the public share URL for a client cloud hash.
     * Mirrors the PHP cloudShareUrl() fallback: strips the current filename
     * from window.location.pathname so the URL is correct even when the app
     * is installed in a subdirectory (e.g. /easybooking/).
     */
    function buildShareUrl(hash) {
        const basePath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
        return window.location.origin + basePath + '/share/' + encodeURIComponent(hash);
    }

    function renderFileList(container, files) {
        if (files.length === 0) {
            container.innerHTML = '<div class="text-muted text-center py-5"><i class="fas fa-cloud-upload-alt fa-2x mb-2 d-block"></i>Nessun file. Trascina i file nella zona qui sopra.</div>';
            return;
        }

        let html = '<div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0"><thead><tr>'
            + '<th>Nome</th><th>Dimensione</th><th>Nota</th><th>Data</th><th class="text-end">Azioni</th>'
            + '</tr></thead><tbody>';

        files.forEach(f => {
            const icon  = f.icon || 'fa-file';
            const date  = f.created_at ? f.created_at.substring(0, 10) : '';
            const nota  = f.nota ? htmlEsc(f.nota) : '';

            const audioBtn = f.is_audio
                ? `<button class="btn btn-sm btn-outline-info me-1" onclick="cloudPlayAudio(${f.id},'${htmlEsc(f.nome_originale)}')" title="Ascolta"><i class="fas fa-play"></i></button>`
                : '';

            html += `<tr data-file-id="${f.id}">
                <td><i class="fas ${icon} me-2 text-muted"></i>${htmlEsc(f.nome_originale)}</td>
                <td class="text-nowrap">${f.dimensione_human}</td>
                <td class="cloud-note-cell" data-file-id="${f.id}" style="max-width:200px;">
                    <span class="cloud-note-display">${nota ? htmlEsc(nota) : '<span class="text-muted">—</span>'}</span>
                    <div class="cloud-note-editor d-none">
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control cloud-note-input" value="${nota}" placeholder="Aggiungi nota…">
                            <button class="btn btn-outline-success cloud-note-save" type="button" title="Salva"><i class="fas fa-check"></i></button>
                            <button class="btn btn-outline-secondary cloud-note-cancel" type="button" title="Annulla"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                </td>
                <td class="text-nowrap">${date}</td>
                <td class="text-end text-nowrap">
                    ${audioBtn}
                    <a href="api/cloud-api.php?action=download_file&file_id=${f.id}" class="btn btn-sm btn-outline-secondary me-1" title="Download"><i class="fas fa-download"></i></a>
                    <button class="btn btn-sm btn-outline-primary me-1 cloud-edit-note-btn" data-file-id="${f.id}" title="Modifica nota"><i class="fas fa-pencil-alt"></i></button>
                    <button class="btn btn-sm btn-outline-danger cloud-delete-btn" data-file-id="${f.id}" title="Elimina"><i class="fas fa-trash"></i></button>
                </td>
            </tr>`;
        });

        html += '</tbody></table></div>';
        container.innerHTML = html;

        // Bind inline note editing
        bindInlineNoteEditing(container);
        // Bind delete buttons
        container.querySelectorAll('.cloud-delete-btn').forEach(btn => {
            btn.addEventListener('click', () => cloudDeleteFile(btn.dataset.fileId));
        });
    }

    // ── Inline note editing ───────────────────────────────────────────────

    function bindInlineNoteEditing(container) {
        container.querySelectorAll('.cloud-edit-note-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const fileId = btn.dataset.fileId;
                const cell   = container.querySelector(`.cloud-note-cell[data-file-id="${fileId}"]`);
                if (!cell) return;
                cell.querySelector('.cloud-note-display').classList.add('d-none');
                cell.querySelector('.cloud-note-editor').classList.remove('d-none');
                cell.querySelector('.cloud-note-input').focus();
            });
        });

        container.querySelectorAll('.cloud-note-cancel').forEach(btn => {
            btn.addEventListener('click', () => {
                const cell = btn.closest('.cloud-note-cell');
                if (!cell) return;
                cell.querySelector('.cloud-note-display').classList.remove('d-none');
                cell.querySelector('.cloud-note-editor').classList.add('d-none');
            });
        });

        container.querySelectorAll('.cloud-note-save').forEach(btn => {
            btn.addEventListener('click', () => {
                const cell   = btn.closest('.cloud-note-cell');
                const fileId = cell.dataset.fileId;
                const nota   = cell.querySelector('.cloud-note-input').value.trim();
                saveNote(fileId, nota, cell, btn);
            });
        });

        // Also save on Enter key in input
        container.querySelectorAll('.cloud-note-input').forEach(input => {
            input.addEventListener('keydown', e => {
                if (e.key === 'Enter') {
                    const cell   = input.closest('.cloud-note-cell');
                    const fileId = cell.dataset.fileId;
                    const nota   = input.value.trim();
                    const saveBtn = cell.querySelector('.cloud-note-save');
                    saveNote(fileId, nota, cell, saveBtn);
                }
                if (e.key === 'Escape') {
                    const cell = input.closest('.cloud-note-cell');
                    cell.querySelector('.cloud-note-display').classList.remove('d-none');
                    cell.querySelector('.cloud-note-editor').classList.add('d-none');
                }
            });
        });
    }

    function saveNote(fileId, nota, cell, saveBtn) {
        if (saveBtn) saveBtn.disabled = true;
        const fd = new FormData();
        fd.append('action', 'update_file');
        fd.append('file_id', fileId);
        fd.append('nota', nota);
        fd.append('csrf_token', getCsrf());

        fetch('api/cloud-api.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (saveBtn) saveBtn.disabled = false;
                if (data.success) {
                    showToast('Nota aggiornata.', 'success');
                    // Update display
                    const display = cell.querySelector('.cloud-note-display');
                    display.innerHTML = nota ? htmlEsc(nota) : '<span class="text-muted">—</span>';
                    cell.querySelector('.cloud-note-display').classList.remove('d-none');
                    cell.querySelector('.cloud-note-editor').classList.add('d-none');
                } else {
                    showToast(data.message || 'Errore.', 'danger');
                }
            })
            .catch(() => {
                if (saveBtn) saveBtn.disabled = false;
                showToast('Errore di rete.', 'danger');
            });
    }

    // ── Delete file ───────────────────────────────────────────────────────

    window.cloudDeleteFile = function (fileId) {
        if (!confirm('Eliminare questo file definitivamente?')) return;
        const fd = new FormData();
        fd.append('action', 'delete_file');
        fd.append('file_id', fileId);
        fd.append('csrf_token', getCsrf());

        fetch('api/cloud-api.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('File eliminato.', 'success');
                    loadFiles(currentClienteId);
                    refreshStats();
                } else {
                    showToast(data.message || 'Errore.', 'danger');
                }
            })
            .catch(() => showToast('Errore di rete.', 'danger'));
    };

    // ── Audio player modal ────────────────────────────────────────────────

    window.cloudPlayAudio = function (fileId, fileName) {
        const modalEl = document.getElementById('cloudAudioModal');
        if (!modalEl) return;
        document.getElementById('cloud-audio-title').textContent = fileName;
        const player = document.getElementById('cloud-audio-player');
        player.src = 'api/cloud-api.php?action=get_file&file_id=' + fileId;
        player.load();
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    };

    function bindAudioModalClose() {
        const modalEl = document.getElementById('cloudAudioModal');
        if (!modalEl) return;
        modalEl.addEventListener('hide.bs.modal', () => {
            const player = document.getElementById('cloud-audio-player');
            if (player) { player.pause(); player.src = ''; }
        });
    }

    // ── Copy link ─────────────────────────────────────────────────────────

    window.cloudCopyLink = function (url) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url)
                .then(() => showToast('Link copiato negli appunti!', 'success'))
                .catch(() => fallbackCopy(url));
        } else {
            fallbackCopy(url);
        }
    };

    function fallbackCopy(url) {
        try {
            const el = document.createElement('textarea');
            el.value = url;
            el.style.position = 'fixed';
            el.style.opacity  = '0';
            document.body.appendChild(el);
            el.focus();
            el.select();
            const ok = document.execCommand('copy');
            document.body.removeChild(el);
            showToast(ok ? 'Link copiato!' : 'Copia non supportata. URL: ' + url, ok ? 'success' : 'warning');
        } catch (e) {
            showToast('Copia non supportata. URL: ' + url, 'warning');
        }
    }

    function bindCopyLinkBtn() {
        const btn = document.getElementById('cloud-copy-link-btn');
        if (!btn) return;
        btn.addEventListener('click', () => {
            if (!currentClienteHash) {
                showToast('Nessun link disponibile per questo cliente.', 'warning');
                return;
            }
            const url = buildShareUrl(currentClienteHash);
            cloudCopyLink(url);
        });
    }

    // ── Settings (disable cloud) ──────────────────────────────────────────

    function bindSettingsBtn() {
        const btn = document.getElementById('cloud-settings-btn');
        if (!btn) return;
        btn.addEventListener('click', () => {
            if (!currentClienteId) return;
            const modalEl = document.getElementById('deleteCloudModal');
            if (!modalEl) return;
            const input      = document.getElementById('delete-cloud-confirm-text');
            const confirmBtn = document.getElementById('delete-cloud-confirm-btn');
            if (input)      input.value = '';
            if (confirmBtn) confirmBtn.disabled = true;
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });
    }

    function bindDeleteCloudModal() {
        const input      = document.getElementById('delete-cloud-confirm-text');
        const confirmBtn = document.getElementById('delete-cloud-confirm-btn');
        if (!input || !confirmBtn) return;

        input.addEventListener('input', () => {
            confirmBtn.disabled = input.value !== 'CANCELLA';
        });

        confirmBtn.addEventListener('click', () => {
            if (input.value !== 'CANCELLA') return;
            const modalEl = document.getElementById('deleteCloudModal');
            if (modalEl) bootstrap.Modal.getInstance(modalEl).hide();
            cloudDisable(currentClienteId);
        });
    }

    function cloudDisable(clienteId) {
        const fd = new FormData();
        fd.append('action', 'disable_cloud');
        fd.append('cliente_id', clienteId);
        fd.append('csrf_token', getCsrf());

        fetch('api/cloud-api.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast(data.message || 'Errore.', 'danger');
                }
            })
            .catch(() => showToast('Errore di rete.', 'danger'));
    }

    // ── Create Cloud Modal ────────────────────────────────────────────────

    function bindCreateCloudModal() {
        const modalEl = document.getElementById('createCloudModal');

        // Reset search, selection and confirm button every time the modal opens,
        // regardless of how it is triggered (button click, Bootstrap API, etc.)
        if (modalEl) {
            modalEl.addEventListener('show.bs.modal', () => {
                const search     = document.getElementById('create-cloud-search');
                const hidden     = document.getElementById('create-cloud-selected');
                const confirmBtn = document.getElementById('create-cloud-confirm-btn');
                if (search)     { search.value = ''; }
                if (hidden)     { hidden.value = ''; }
                filterCreateCloudList('');
                document.querySelectorAll('.create-cloud-list-item').forEach(i => i.classList.remove('active'));
                if (confirmBtn) { confirmBtn.disabled = true; }
            });
        }

        const openBtn = document.getElementById('create-cloud-btn');
        if (openBtn) {
            openBtn.addEventListener('click', () => {
                if (!modalEl) return;
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            });
        }

        // Live search filter
        const searchInput = document.getElementById('create-cloud-search');
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                filterCreateCloudList(searchInput.value.trim().toLowerCase());
            });
        }

        // Item selection
        document.querySelectorAll('.create-cloud-list-item').forEach(item => {
            item.addEventListener('click', () => {
                document.querySelectorAll('.create-cloud-list-item').forEach(i => i.classList.remove('active'));
                item.classList.add('active');
                const hidden = document.getElementById('create-cloud-selected');
                if (hidden) hidden.value = item.dataset.clientId;
                const confirmBtn = document.getElementById('create-cloud-confirm-btn');
                if (confirmBtn) confirmBtn.disabled = false;
            });
        });

        const confirmBtn = document.getElementById('create-cloud-confirm-btn');
        if (!confirmBtn) return;
        confirmBtn.addEventListener('click', () => {
            const hidden    = document.getElementById('create-cloud-selected');
            const clienteId = hidden ? hidden.value : '';
            if (!clienteId) {
                showToast('Seleziona un cliente.', 'warning');
                return;
            }

            confirmBtn.disabled = true;
            const fd = new FormData();
            fd.append('action', 'enable_cloud');
            fd.append('cliente_id', clienteId);
            fd.append('csrf_token', getCsrf());

            fetch('api/cloud-api.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    confirmBtn.disabled = false;
                    if (data.success) {
                        showToast(data.message || 'Cloud abilitato.', 'success');
                        const modalEl = document.getElementById('createCloudModal');
                        if (modalEl) bootstrap.Modal.getInstance(modalEl).hide();
                        setTimeout(() => location.reload(), 800);
                    } else {
                        showToast(data.message || 'Errore.', 'danger');
                    }
                })
                .catch(() => {
                    confirmBtn.disabled = false;
                    showToast('Errore di rete.', 'danger');
                });
        });
    }

    function filterCreateCloudList(query) {
        const items     = document.querySelectorAll('.create-cloud-list-item');
        const noResults = document.getElementById('create-cloud-no-results');
        let   visible   = 0;

        items.forEach(item => {
            const name = item.dataset.clientName || '';
            const show = !query || name.includes(query);
            // Use setProperty with 'important' priority so the inline style
            // overrides Bootstrap's d-flex { display: flex !important } rule.
            if (show) {
                item.style.removeProperty('display');
            } else {
                item.style.setProperty('display', 'none', 'important');
            }
            if (show) visible++;
        });

        if (noResults) noResults.classList.toggle('d-none', visible > 0);
    }

    // ── Drag & Drop Upload ────────────────────────────────────────────────

    function initDropZone() {
        const zone      = document.getElementById('cloud-drop-zone');
        const input     = document.getElementById('cloud-file-input');
        const btnUpload = document.getElementById('cloud-upload-btn');
        if (!zone || !input) return;

        ['dragenter', 'dragover'].forEach(ev => {
            zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.add('drag-over'); });
        });
        ['dragleave', 'drop'].forEach(ev => {
            zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.remove('drag-over'); });
        });

        zone.addEventListener('drop', e => {
            const files = e.dataTransfer.files;
            if (files.length) uploadFiles(files);
        });

        zone.addEventListener('click', () => input.click());

        input.addEventListener('change', () => {
            if (input.files.length) uploadFiles(input.files);
        });

        if (btnUpload) {
            btnUpload.addEventListener('click', () => input.click());
        }
    }

    function uploadFiles(fileList) {
        if (!currentClienteId) return;

        const progressWrapper = document.getElementById('cloud-upload-progress');
        const progressBar     = document.getElementById('cloud-upload-bar');
        const progressText    = document.getElementById('cloud-upload-text');

        if (progressWrapper) progressWrapper.classList.remove('d-none');

        const fd = new FormData();
        fd.append('action', 'upload_file');
        fd.append('cliente_id', currentClienteId);
        fd.append('csrf_token', getCsrf());

        Array.from(fileList).forEach(f => fd.append('files[]', f));

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'api/cloud-api.php');

        xhr.upload.addEventListener('progress', e => {
            if (e.lengthComputable && progressBar && progressText) {
                const pct = Math.round(e.loaded / e.total * 100);
                progressBar.style.width = pct + '%';
                progressBar.setAttribute('aria-valuenow', pct);
                progressText.textContent = pct + '%';
            }
        });

        xhr.addEventListener('load', () => {
            if (progressWrapper) progressWrapper.classList.add('d-none');
            if (progressBar) { progressBar.style.width = '0%'; }

            try {
                const data = JSON.parse(xhr.responseText);
                if (data.errors && data.errors.length) {
                    data.errors.forEach(err => showToast(err, 'warning'));
                }
                if (data.uploaded && data.uploaded.length) {
                    showToast(data.uploaded.length + ' file caricati con successo.', 'success');
                    loadFiles(currentClienteId);
                    refreshStats();
                } else if (!data.errors || !data.errors.length) {
                    showToast(data.message || 'Nessun file caricato.', 'warning');
                }
            } catch (e) {
                showToast('Risposta non valida dal server.', 'danger');
            }
        });

        xhr.addEventListener('error', () => {
            if (progressWrapper) progressWrapper.classList.add('d-none');
            showToast('Errore di rete durante l\'upload.', 'danger');
        });

        xhr.send(fd);
    }

    // ── Init ──────────────────────────────────────────────────────────────

    document.addEventListener('DOMContentLoaded', () => {
        refreshStats();
        bindClientButtons();
        initDropZone();
        bindAudioModalClose();
        bindCopyLinkBtn();
        bindSettingsBtn();
        bindDeleteCloudModal();
        bindCreateCloudModal();
    });

})();

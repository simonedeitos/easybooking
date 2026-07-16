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
        const el   = document.getElementById(id);
        const toast = new bootstrap.Toast(el, { delay: 4000 });
        toast.show();
        el.addEventListener('hidden.bs.toast', () => el.remove());
    }

    function formatBytes(bytes) {
        if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' GB';
        if (bytes >= 1048576)    return (bytes / 1048576).toFixed(2) + ' MB';
        if (bytes >= 1024)       return (bytes / 1024).toFixed(2) + ' KB';
        return bytes + ' B';
    }

    // ── Cloud Stats ───────────────────────────────────────────────────────

    function refreshStats() {
        fetch('api/cloud-api.php?action=get_stats')
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                const s = data.stats;
                const pct = Math.min(100, s.percentuale);

                const barEl  = document.getElementById('cloud-stats-bar');
                const textEl = document.getElementById('cloud-stats-text');
                if (barEl) {
                    barEl.style.width = pct + '%';
                    barEl.setAttribute('aria-valuenow', pct);
                    barEl.className = 'progress-bar' +
                        (pct >= 90 ? ' bg-danger' : pct >= 70 ? ' bg-warning' : ' bg-primary');
                }
                if (textEl) {
                    textEl.textContent = s.spazio_human + ' / ' + s.spazio_max_human + ' (' + pct + '%)';
                }

                const alertEl = document.getElementById('cloud-quota-alert');
                if (alertEl) {
                    if (pct >= 90) {
                        alertEl.textContent = '⚠️ Attenzione: spazio quasi esaurito (' + pct + '%).';
                        alertEl.className = 'alert alert-danger py-1 mt-2';
                    } else if (pct >= 70) {
                        alertEl.textContent = '⚠️ Lo spazio cloud è al ' + pct + '%. Considera di liberare spazio.';
                        alertEl.className = 'alert alert-warning py-1 mt-2';
                    } else {
                        alertEl.textContent = '';
                        alertEl.className = 'd-none';
                    }
                }
            })
            .catch(() => {});
    }

    // ── Enable / Disable Cloud ────────────────────────────────────────────

    function bindCloudToggle() {
        document.querySelectorAll('[data-cloud-enable]').forEach(btn => {
            btn.addEventListener('click', () => {
                const clienteId = btn.dataset.cloudEnable;
                if (!confirm('Abilitare il cloud per questo cliente?')) return;
                cloudToggle('enable_cloud', clienteId, btn);
            });
        });

        document.querySelectorAll('[data-cloud-disable]').forEach(btn => {
            btn.addEventListener('click', () => {
                const clienteId = btn.dataset.cloudDisable;
                if (!confirm('Disabilitare il cloud? Tutti i file verranno eliminati definitivamente.')) return;
                cloudToggle('disable_cloud', clienteId, btn);
            });
        });
    }

    function cloudToggle(action, clienteId, btn) {
        btn.disabled = true;
        const fd = new FormData();
        fd.append('action', action);
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
                    btn.disabled = false;
                }
            })
            .catch(() => {
                showToast('Errore di rete.', 'danger');
                btn.disabled = false;
            });
    }

    // ── File list ─────────────────────────────────────────────────────────

    let currentClienteId = null;

    function openClientCloud(clienteId, clienteNome) {
        currentClienteId = clienteId;

        const titleEl = document.getElementById('cloud-modal-title');
        if (titleEl) titleEl.textContent = '☁️ Cloud – ' + clienteNome;

        loadFiles(clienteId);

        const modalEl = document.getElementById('cloudFilesModal');
        if (modalEl) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    }

    function loadFiles(clienteId) {
        const container = document.getElementById('cloud-files-list');
        if (!container) return;
        container.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm"></div> Caricamento…</div>';

        fetch('api/cloud-api.php?action=list_files&cliente_id=' + encodeURIComponent(clienteId))
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    container.innerHTML = '<div class="text-danger">Errore nel caricamento dei file.</div>';
                    return;
                }
                renderFileList(container, data.files, clienteId);
            })
            .catch(() => {
                container.innerHTML = '<div class="text-danger">Errore di rete.</div>';
            });
    }

    function renderFileList(container, files, clienteId) {
        if (files.length === 0) {
            container.innerHTML = '<div class="text-muted text-center py-4"><i class="fas fa-cloud-upload-alt fa-2x mb-2 d-block"></i>Nessun file. Trascina i file qui sopra o usa il pulsante.</div>';
            return;
        }

        let html = '<div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr>'
            + '<th>Nome</th><th>Dimensione</th><th>Nota</th><th>Data</th><th class="text-end">Azioni</th>'
            + '</tr></thead><tbody>';

        files.forEach(f => {
            const icon    = f.icon || 'fa-file';
            const note    = f.nota ? htmlEsc(f.nota) : '<span class="text-muted">—</span>';
            const date    = f.created_at ? f.created_at.substring(0, 10) : '';
            const audioBtn = f.is_audio
                ? `<button class="btn btn-sm btn-outline-info me-1" onclick="cloudPlayAudio(${f.id},'${htmlEsc(f.nome_originale)}')" title="Ascolta"><i class="fas fa-play"></i></button>`
                : '';

            html += `<tr>
                <td><i class="fas ${icon} me-2 text-muted"></i>${htmlEsc(f.nome_originale)}</td>
                <td class="text-nowrap">${f.dimensione_human}</td>
                <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${note}</td>
                <td class="text-nowrap">${date}</td>
                <td class="text-end text-nowrap">
                    ${audioBtn}
                    <a href="api/cloud-api.php?action=download_file&file_id=${f.id}" class="btn btn-sm btn-outline-secondary me-1" title="Download"><i class="fas fa-download"></i></a>
                    <button class="btn btn-sm btn-outline-primary me-1" onclick="cloudEditNote(${f.id},'${htmlEsc(f.nota || '')}')" title="Modifica nota"><i class="fas fa-pencil-alt"></i></button>
                    <button class="btn btn-sm btn-outline-danger" onclick="cloudDeleteFile(${f.id})" title="Elimina"><i class="fas fa-trash"></i></button>
                </td>
            </tr>`;
        });

        html += '</tbody></table></div>';
        container.innerHTML = html;
    }

    function htmlEsc(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
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

    // ── Edit note modal ───────────────────────────────────────────────────

    window.cloudEditNote = function (fileId, currentNota) {
        const modalEl = document.getElementById('cloudNoteModal');
        if (!modalEl) return;
        document.getElementById('cloud-note-file-id').value = fileId;
        document.getElementById('cloud-note-text').value    = currentNota;
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    };

    function bindNoteSave() {
        const btn = document.getElementById('cloud-note-save-btn');
        if (!btn) return;
        btn.addEventListener('click', () => {
            const fileId = document.getElementById('cloud-note-file-id').value;
            const nota   = document.getElementById('cloud-note-text').value;
            const fd = new FormData();
            fd.append('action', 'update_file');
            fd.append('file_id', fileId);
            fd.append('nota', nota);
            fd.append('csrf_token', getCsrf());

            btn.disabled = true;
            fetch('api/cloud-api.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    if (data.success) {
                        showToast('Nota aggiornata.', 'success');
                        bootstrap.Modal.getInstance(document.getElementById('cloudNoteModal')).hide();
                        loadFiles(currentClienteId);
                    } else {
                        showToast(data.message || 'Errore.', 'danger');
                    }
                })
                .catch(() => { btn.disabled = false; showToast('Errore di rete.', 'danger'); });
        });
    }

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
                .catch(() => showToast('Impossibile copiare automaticamente. Copia manualmente: ' + url, 'warning'));
        } else {
            // Legacy fallback – execCommand is deprecated but still works in many browsers
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
    };

    // ── Open cloud modal from table row ──────────────────────────────────

    window.openClientCloud = openClientCloud;

    function bindOpenButtons() {
        document.querySelectorAll('[data-open-cloud]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id   = btn.dataset.openCloud;
                const nome = btn.dataset.clienteNome || 'Cliente';
                openClientCloud(id, nome);
            });
        });
    }

    // ── Drag & Drop Upload ────────────────────────────────────────────────

    function initDropZone() {
        const zone    = document.getElementById('cloud-drop-zone');
        const input   = document.getElementById('cloud-file-input');
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
        bindCloudToggle();
        bindOpenButtons();
        initDropZone();
        bindNoteSave();
        bindAudioModalClose();
    });

})();

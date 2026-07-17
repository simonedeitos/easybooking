<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($page_title) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --accent: #5e72e4;
            --accent-dark: #3a4fc4;
            --bg: #f0f2f7;
            --card-bg: #fff;
            --text: #2d3748;
            --muted: #718096;
        }
        * { box-sizing: border-box; }
        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
        }
        /* ── Header ────────────────────────────────────────── */
        .cloud-header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 60%, #0f3460 100%);
            padding: 2.5rem 0 3rem;
            position: relative;
            overflow: hidden;
        }
        .cloud-header::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0; right: 0;
            height: 40px;
            background: var(--bg);
            clip-path: ellipse(55% 100% at 50% 100%);
        }
        .cloud-header .app-brand {
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.55);
        }
        .cloud-header .page-label {
            font-size: 1rem;
            color: rgba(255,255,255,0.75);
            margin-top: 0.15rem;
        }
        .cloud-header .client-name {
            font-size: 2.1rem;
            font-weight: 800;
            color: #fff;
            margin-top: 0.6rem;
            letter-spacing: -0.02em;
            line-height: 1.15;
        }
        /* ── Section cards ─────────────────────────────────── */
        .section-card {
            background: var(--card-bg);
            border-radius: 1rem;
            box-shadow: 0 2px 16px rgba(0,0,0,0.07);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        .section-title {
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--muted);
            padding: 1rem 1.25rem 0.75rem;
            border-bottom: 1px solid #edf2f7;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        /* ── Lessons ────────────────────────────────────────── */
        .lesson-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 0.85rem 1.25rem;
            border-bottom: 1px solid #edf2f7;
        }
        .lesson-item:last-child { border-bottom: none; }
        .lesson-date-badge {
            background: var(--accent);
            color: #fff;
            border-radius: 0.6rem;
            text-align: center;
            padding: 0.35rem 0.65rem;
            min-width: 3.2rem;
            flex-shrink: 0;
        }
        .lesson-date-badge .day { font-size: 1.4rem; font-weight: 800; line-height: 1; display: block; }
        .lesson-date-badge .month { font-size: 0.65rem; font-weight: 600; letter-spacing: 0.06em; text-transform: uppercase; }
        .lesson-info { flex: 1; min-width: 0; }
        .lesson-time { font-weight: 700; font-size: 0.95rem; color: var(--text); }
        .lesson-meta { font-size: 0.8rem; color: var(--muted); margin-top: 0.15rem; }
        .badge-scadenza {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffc10733;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.2rem 0.6rem;
        }
        .badge-acquisto {
            background: #e9ecef;
            color: #495057;
            border: 1px solid #d7dbe0;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.2rem 0.6rem;
        }
        .badge-da-saldare {
            background: #fde2e1;
            color: #b42318;
            border: 1px solid #f8c2bf;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.2rem 0.6rem;
        }
        .section-badges {
            margin-left: auto;
            display: flex;
            gap: 0.4rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        /* ── Files ──────────────────────────────────────────── */
        .file-item {
            display: flex;
            align-items: flex-start;
            gap: 0.9rem;
            padding: 0.85rem 1.25rem;
            border-bottom: 1px solid #edf2f7;
            transition: background 0.15s;
        }
        .file-item:last-child { border-bottom: none; }
        .file-item:hover { background: #f7f9fc; }
        .file-icon-box {
            width: 2.6rem;
            height: 2.6rem;
            border-radius: 0.5rem;
            background: #eef0fb;
            border: 1px solid #dbe3f4;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
            color: var(--accent);
        }
        .file-icon-box i { display: block; font-size: 1.18rem; line-height: 1; }
        .file-icon-box.audio { background: #e7f5ee; color: #198754; }
        .file-icon-box.video { background: #fceaea; color: #dc3545; }
        .file-icon-box.image { background: #fff0e6; color: #fd7e14; }
        .file-icon-box.pdf   { background: #fceaea; color: #dc3545; }
        .file-icon-box.word  { background: #e7eeff; color: #0d6efd; }
        .file-icon-box.excel { background: #e7f5ee; color: #198754; }
        .file-icon-box.pptx  { background: #fff0e6; color: #fd7e14; }
        .file-icon-box.zip   { background: #f8f0fe; color: #6f42c1; }
        .file-body { flex: 1; min-width: 0; }
        .file-name { font-weight: 600; font-size: 0.92rem; word-break: break-word; }
        .file-meta { font-size: 0.78rem; color: var(--muted); margin-top: 0.15rem; }
        .file-nota {
            margin-top: 0.4rem;
            padding: 0.35rem 0.6rem;
            background: #f0f4ff;
            border-left: 3px solid var(--accent);
            border-radius: 0 0.4rem 0.4rem 0;
            font-size: 0.8rem;
            color: #4a5568;
        }
        .file-actions { display: flex; gap: 0.4rem; flex-shrink: 0; flex-wrap: wrap; align-self: center; }
        /* ── Audio player modal ─────────────────────────────── */
        .player-waveform-shell {
            position: relative;
            min-height: 94px;
            background: #f7f9fc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 0.85rem 1rem;
        }
        #waveform {
            position: absolute;
            inset: 0;
            opacity: 0;
            pointer-events: none;
        }
        .dom-waveform {
            position: relative;
            height: 64px;
            cursor: pointer;
            user-select: none;
            touch-action: none;
        }
        .dom-waveform-track,
        .dom-waveform-progress {
            position: absolute;
            inset: 0;
            overflow: hidden;
        }
        .dom-waveform-progress { width: 0%; }
        .dom-waveform-bars {
            display: flex;
            align-items: center;
            gap: 3px;
            height: 100%;
        }
        .dom-waveform-track .dom-waveform-bars .dom-waveform-bar { background: rgba(94,114,228,0.22); }
        .dom-waveform-progress .dom-waveform-bars .dom-waveform-bar { background: #5e72e4; }
        .dom-waveform-bar {
            flex: 1 1 auto;
            min-height: 8%;
            border-radius: 999px;
            transition: height 0.18s ease;
        }
        .dom-waveform-scrubber {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0%;
            width: 2px;
            background: #5e72e4;
            border-radius: 999px;
            pointer-events: none;
        }
        .dom-waveform-scrubber::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 10px;
            height: 10px;
            border-radius: 999px;
            transform: translate(-50%, -50%);
            background: #5e72e4;
            box-shadow: 0 0 0 3px #ffffff;
        }
        .player-controls {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-top: 0.75rem;
            flex-wrap: wrap;
        }
        .time-display { font-size: 0.85rem; color: var(--muted); font-variant-numeric: tabular-nums; }
        /* ── States ─────────────────────────────────────────── */
        .empty-state { text-align: center; padding: 3rem 1rem; color: var(--muted); }
        .empty-state i { font-size: 2.5rem; opacity: 0.35; display: block; margin-bottom: 0.75rem; }
        .error-state { text-align: center; padding: 3rem 1rem; }
        /* ── Footer ─────────────────────────────────────────── */
        .cloud-footer {
            text-align: center;
            color: var(--muted);
            font-size: 0.78rem;
            padding: 2rem 0 1.5rem;
        }
        @media (max-width: 576px) {
            .cloud-header .client-name { font-size: 1.6rem; }
            .file-actions { flex-direction: column; }
            .file-actions .btn { width: 100%; }
        }
    </style>
</head>
<body>

<!-- ── Header ─────────────────────────────────────────────── -->
<header class="cloud-header mb-4">
    <div class="container" style="max-width:800px; position:relative; z-index:1;">
        <div class="app-brand"><i class="fas fa-cloud me-1"></i><?= h($app_name) ?></div>
        <div class="page-label">Cloud Page</div>
        <?php if ($cliente_nome !== ''): ?>
            <div class="client-name"><?= h($cliente_nome) ?></div>
        <?php endif; ?>
    </div>
</header>

<main class="container pb-4" style="max-width: 800px;">
<?php if ($error_message !== ''): ?>
    <div class="section-card">
        <div class="error-state">
            <i class="fas fa-circle-exclamation fa-2x text-danger mb-3 d-block"></i>
            <h5 class="mb-2"><?= h($error_title !== '' ? $error_title : 'Errore') ?></h5>
            <p class="text-muted mb-0"><?= h($error_message) ?></p>
            <?php if (!empty($debug_details)): ?>
            <details class="mt-3 text-start">
                <summary class="small text-muted">Dettagli tecnici (debug)</summary>
                <pre class="mt-2 p-2 bg-light border rounded small mb-0"><?= h($debug_details) ?></pre>
            </details>
            <?php endif; ?>
            <p class="small text-muted mt-3">
                <i class="fas fa-info-circle me-1"></i>
                Riferimento tecnico: consultare il file <code>cloud-debug.log</code> nella cartella <code>public_html</code>.
            </p>
        </div>
    </div>
<?php else: ?>

    <!-- ── Prossime lezioni ──────────────────────────────── -->
    <div class="section-card">
        <div class="section-title">
            <i class="fas fa-calendar-alt"></i> Prossime lezioni
            <?php if ($scadenza_pacchetto !== null || $data_acquisto_pacchetto !== null || $pacchetto_da_saldare === true): ?>
                <span class="section-badges">
                    <?php if ($scadenza_pacchetto !== null): ?>
                        <span class="badge-scadenza">
                            <i class="fas fa-clock me-1"></i>Scadenza pacchetto: <?= h($scadenza_pacchetto) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($data_acquisto_pacchetto !== null): ?>
                        <span class="badge-acquisto">
                            <i class="fas fa-receipt me-1"></i>Pacchetto acquistato il: <?= h($data_acquisto_pacchetto) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($pacchetto_da_saldare === true): ?>
                        <span class="badge-da-saldare">Pacchetto ancora da saldare</span>
                    <?php endif; ?>
                </span>
            <?php endif; ?>
        </div>
        <?php if (empty($lezioni_future)): ?>
            <div class="empty-state py-4">
                <i class="fas fa-calendar-check"></i>
                <p class="mb-0">Nessuna lezione pianificata.</p>
            </div>
        <?php else: ?>
            <?php foreach ($lezioni_future as $l): ?>
            <div class="lesson-item">
                <div class="lesson-date-badge">
                    <span class="day"><?= h($l['giorno']) ?></span>
                    <span class="month"><?= h($l['mese']) ?></span>
                </div>
                <div class="lesson-info">
                    <div class="lesson-time">
                        <?= h($l['ora_inizio']) ?> – <?= h($l['ora_fine']) ?>
                    </div>
                    <div class="lesson-meta">
                        <?php if ($l['pacchetto'] !== ''): ?>
                            <i class="fas fa-box me-1"></i><?= h($l['pacchetto']) ?>
                        <?php endif; ?>
                        <?php if ($l['strumento'] !== ''): ?>
                            <?= $l['pacchetto'] !== '' ? ' &middot; ' : '' ?><i class="fas fa-music me-1"></i><?= h($l['strumento']) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ── File ─────────────────────────────────────────── -->
    <div class="section-card">
        <div class="section-title">
            <i class="fas fa-folder-open"></i> File condivisi
        </div>
        <?php if (empty($files)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p class="mb-0">Nessun file disponibile. I materiali compariranno qui appena saranno caricati.</p>
            </div>
        <?php else: ?>
            <?php foreach ($files as $file):
                $mime     = $file['mime_type'] ?? '';
                $icon     = $file['icon'] ?? 'fa-file';
                $isAudio  = !empty($file['is_audio']);
                $iconClass = 'file-icon-box';
                if ($isAudio) $iconClass .= ' audio';
                elseif (str_starts_with($mime, 'video/')) $iconClass .= ' video';
                elseif (str_starts_with($mime, 'image/')) $iconClass .= ' image';
                elseif ($mime === 'application/pdf') $iconClass .= ' pdf';
                elseif (str_contains($icon, 'word'))       $iconClass .= ' word';
                elseif (str_contains($icon, 'excel'))      $iconClass .= ' excel';
                elseif (str_contains($icon, 'powerpoint')) $iconClass .= ' pptx';
                elseif (str_contains($icon, 'archive'))    $iconClass .= ' zip';
            ?>
            <div class="file-item">
                <div class="<?= $iconClass ?>">
                    <i class="fas <?= h($icon) ?>"></i>
                </div>
                <div class="file-body">
                    <div class="file-name"><?= h($file['nome_originale'] ?? '') ?></div>
                    <div class="file-meta">
                        <i class="fas fa-weight-hanging me-1"></i><?= h($file['dimensione_human'] ?? '0 B') ?>
                        <?php if (!empty($file['created_at_human'])): ?>
                         &middot; <i class="fas fa-calendar me-1"></i><?= h($file['created_at_human']) ?>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($file['nota'])): ?>
                    <div class="file-nota">
                        <i class="fas fa-sticky-note me-1"></i><?= h($file['nota']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="file-actions">
                    <?php if ($isAudio): ?>
                    <button class="btn btn-sm btn-outline-success play-audio"
                            type="button"
                            data-file-id="<?= (int)$file['id'] ?>"
                            data-file-name="<?= h($file['nome_originale'] ?? '') ?>">
                        <i class="fas fa-waveform-lines me-1"></i>Ascolta
                    </button>
                    <?php endif; ?>
                    <a href="?hash=<?= h(rawurlencode($hash)) ?>&amp;action=download&amp;file_id=<?= (int)$file['id'] ?>"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-download me-1"></i>Scarica
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

<?php endif; ?>
</main>

<!-- ── Audio player modal ───────────────────────────────────── -->
<div class="modal fade" id="audioModal" tabindex="-1" aria-labelledby="ws-title" aria-hidden="true"
     data-share-hash="<?= h($hash) ?>">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h6 class="modal-title mb-0"><i class="fas fa-music me-2 text-success"></i>Player Audio</h6>
                    <p id="ws-title" class="fw-bold mb-0 mt-1 fs-6 text-truncate" style="max-width:380px;" aria-live="polite"></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>
            <div class="modal-body pt-2">
                <div class="player-waveform-shell mb-2">
                    <div id="waveform"></div>
                    <div id="ws-dom-wave" class="dom-waveform" role="slider" aria-label="Forma d'onda audio" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                        <div class="dom-waveform-track">
                            <div id="ws-wave-back" class="dom-waveform-bars"></div>
                        </div>
                        <div id="ws-wave-front" class="dom-waveform-progress">
                            <div id="ws-wave-progress-bars" class="dom-waveform-bars"></div>
                        </div>
                        <div id="ws-scrubber" class="dom-waveform-scrubber"></div>
                    </div>
                </div>
                <audio id="ws-fallback" controls class="w-100 mt-2" style="display:none; border-radius:6px;">
                    Il tuo browser non supporta l'elemento audio.
                </audio>
                <div class="player-controls">
                    <button id="ws-play" class="btn btn-sm btn-primary" disabled>
                        <i class="fas fa-play"></i>
                    </button>
                    <span class="time-display">
                        <span id="ws-current">0:00</span> / <span id="ws-duration">0:00</span>
                    </span>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Chiudi</button>
            </div>
        </div>
    </div>
</div>

<footer class="cloud-footer">
    <i class="fas fa-lock me-1"></i>Accesso protetto e privato
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/wavesurfer.js@7/dist/wavesurfer.min.js"></script>
<script>
(function () {
    'use strict';

    const audioModalEl = document.getElementById('audioModal');
    const playButtons  = document.querySelectorAll('.play-audio');
    if (!audioModalEl) { return; }

    // Safe: index_cloud.php validates $hash as a 32-char hex token before rendering.
    const shareHash = audioModalEl.dataset.shareHash || '';
    const buildUrl  = (action, fileId) =>
        '?hash=' + encodeURIComponent(shareHash) +
        '&action=' + encodeURIComponent(action) +
        '&file_id=' + encodeURIComponent(fileId);

    const waveEl     = document.getElementById('waveform');
    const domWaveEl  = document.getElementById('ws-dom-wave');
    const waveBackEl = document.getElementById('ws-wave-back');
    const waveProgEl = document.getElementById('ws-wave-progress-bars');
    const progressEl = document.getElementById('ws-wave-front');
    const scrubberEl = document.getElementById('ws-scrubber');
    const fallbackEl = document.getElementById('ws-fallback');
    const playBtn    = document.getElementById('ws-play');
    const curEl      = document.getElementById('ws-current');
    const durEl      = document.getElementById('ws-duration');
    const titleEl    = document.getElementById('ws-title');

    let ws = null;
    let audioModal = null;
    let isDragging = false;

    function fmt(s) {
        const m = Math.floor(s / 60);
        const sec = Math.floor(s % 60);
        return m + ':' + (sec < 10 ? '0' : '') + sec;
    }

    function destroyWs() {
        if (ws) { try { ws.destroy(); } catch(e) {} ws = null; }
        fallbackEl.pause();
        fallbackEl.src = '';
        fallbackEl.style.display = 'none';
        domWaveEl.style.display  = 'block';
        waveEl.innerHTML         = '';
        waveBackEl.innerHTML     = '';
        waveProgEl.innerHTML     = '';
        progressEl.style.width   = '0%';
        scrubberEl.style.left    = '0%';
        domWaveEl.setAttribute('aria-valuenow', '0');
        playBtn.disabled         = true;
        playBtn.style.display    = '';
        playBtn.innerHTML        = '<i class="fas fa-play"></i>';
        curEl.textContent        = '0:00';
        durEl.textContent        = '0:00';
    }

    function setWaveProgress(ratio) {
        const safeRatio = Math.max(0, Math.min(1, ratio || 0));
        const pct = (safeRatio * 100).toFixed(2) + '%';
        progressEl.style.width = pct;
        scrubberEl.style.left = pct;
        domWaveEl.setAttribute('aria-valuenow', String(Math.round(safeRatio * 100)));
    }

    function makeWaveBars(samples) {
        waveBackEl.innerHTML = '';
        waveProgEl.innerHTML = '';

        const source = samples.length ? samples : new Array(120).fill(0.1);
        let min = source[0];
        let max = source[0];
        source.forEach((v) => {
            const value = Math.abs(Number(v) || 0);
            if (value < min) min = value;
            if (value > max) max = value;
        });
        const range = Math.max(max - min, 0.000001);

        source.forEach((value) => {
            const amp = Math.abs(Number(value) || 0);
            const normalized = ((amp - min) / range);
            const height = 8 + (normalized * 86);
            const backBar = document.createElement('span');
            backBar.className = 'dom-waveform-bar';
            backBar.style.height = height.toFixed(2) + '%';
            const frontBar = backBar.cloneNode(true);
            waveBackEl.appendChild(backBar);
            waveProgEl.appendChild(frontBar);
        });
    }

    async function getPCMFromWaveSurfer() {
        if (!ws) return [];

        try {
            if (typeof ws.exportPCM === 'function') {
                const pcm = await ws.exportPCM(140, 10000, true);
                if (Array.isArray(pcm) && pcm.length) {
                    return pcm.map((v) => Math.abs(Number(v) || 0));
                }
            }
        } catch (e) {}

        try {
            if (typeof ws.exportPeaks === 'function') {
                const peaks = ws.exportPeaks({ channels: 1, maxLength: 140, precision: 1000 });
                const values = Array.isArray(peaks) && Array.isArray(peaks[0]) ? peaks[0] : peaks;
                if (Array.isArray(values) && values.length) {
                    return values.map((v) => Math.abs(Number(v) || 0));
                }
            }
        } catch (e) {}

        try {
            if (typeof ws.getDecodedData === 'function') {
                const decoded = ws.getDecodedData();
                if (decoded && typeof decoded.getChannelData === 'function') {
                    const channel = decoded.getChannelData(0);
                    const chunk = Math.max(1, Math.floor(channel.length / 140));
                    const peaks = [];
                    for (let i = 0; i < channel.length; i += chunk) {
                        let peak = 0;
                        for (let j = 0; j < chunk && (i + j) < channel.length; j++) {
                            const sample = Math.abs(channel[i + j] || 0);
                            if (sample > peak) peak = sample;
                        }
                        peaks.push(peak);
                    }
                    return peaks;
                }
            }
        } catch (e) {}

        return [];
    }

    function seekByClientX(clientX) {
        const rect = domWaveEl.getBoundingClientRect();
        if (!rect.width) return;
        const ratio = (clientX - rect.left) / rect.width;
        const safeRatio = Math.max(0, Math.min(1, ratio));
        setWaveProgress(safeRatio);
        if (ws) {
            ws.seekTo(safeRatio);
        }
    }

    function showFallback(url) {
        domWaveEl.style.display    = 'none';
        fallbackEl.style.display  = 'block';
        fallbackEl.src            = url;
        fallbackEl.load();
        playBtn.style.display     = 'none';
    }

    function openPlayer(fileId, fileName) {
        titleEl.textContent = fileName;
        destroyWs();

        const streamUrl = buildUrl('get_file', fileId);

        try {
            if (typeof WaveSurfer === 'undefined') { throw new Error('WaveSurfer not loaded'); }
            ws = WaveSurfer.create({
                container: waveEl,
                waveColor: 'rgba(94,114,228,0.22)',
                progressColor: '#5e72e4',
                cursorColor: '#5e72e4',
                height: 64,
                barWidth: 3,
                barGap: 2,
                barRadius: 8,
                url: streamUrl,
            });

            ws.on('ready', async () => {
                durEl.textContent = fmt(ws.getDuration());
                playBtn.disabled  = false;
                makeWaveBars(await getPCMFromWaveSurfer());
                setWaveProgress(0);
                ws.play();
            });
            ws.on('timeupdate', (t) => {
                curEl.textContent = fmt(t);
                const d = ws ? ws.getDuration() : 0;
                setWaveProgress(d > 0 ? t / d : 0);
            });
            ws.on('play',  () => { playBtn.innerHTML = '<i class="fas fa-pause"></i>'; });
            ws.on('pause', () => { playBtn.innerHTML = '<i class="fas fa-play"></i>'; });
            ws.on('finish',() => {
                playBtn.innerHTML = '<i class="fas fa-play"></i>';
                curEl.textContent = fmt(ws.getDuration());
                setWaveProgress(1);
            });
            ws.on('error', () => { showFallback(streamUrl); });
        } catch (e) {
            showFallback(streamUrl);
        }

        if (!audioModal) { audioModal = new bootstrap.Modal(audioModalEl); }
        audioModal.show();
    }

    playButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            openPlayer(btn.dataset.fileId || '', btn.dataset.fileName || 'Riproduzione audio');
        });
    });

    playBtn.addEventListener('click', () => { if (ws) ws.playPause(); });
    domWaveEl.addEventListener('pointerdown', (event) => {
        if (!ws) return;
        isDragging = true;
        seekByClientX(event.clientX);
    });
    domWaveEl.addEventListener('pointermove', (event) => {
        if (!isDragging || !ws) return;
        seekByClientX(event.clientX);
    });
    ['pointerup', 'pointercancel', 'pointerleave'].forEach((evtName) => {
        domWaveEl.addEventListener(evtName, () => { isDragging = false; });
    });

    audioModalEl.addEventListener('hide.bs.modal', () => {
        destroyWs();
    });
})();
</script>
</body>
</html>

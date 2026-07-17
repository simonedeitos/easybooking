<?php
// cloud_pubblica.php – Public cloud access page for deployments where webapp/
// itself is the document root. For shared-hosting setups with a separate
// public_html/ document root, use public_html/index_cloud.php instead.
// Access via: /share/[HASH] (rewritten by .htaccess) or directly with ?hash=[HASH]

declare(strict_types=1);

ob_start();
ini_set('log_errors', '1');
ini_set('display_errors', '0');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/cloud-functions.php';

function h(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Compute base URL path for share links (works for any install subdirectory).
// e.g. if the app is at /easybooking/, SCRIPT_NAME is /easybooking/cloud_pubblica.php
// so dirname gives /easybooking and share links become /easybooking/share/[hash]/...
$_scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
// Sanitize to guard against unusual proxy/server configurations.
if (!preg_match('#^(/[a-zA-Z0-9_/.-]*)?$#', $_scriptDir)) {
    $_scriptDir = '';
}
// Validate hash from GET parameter
$hash = trim(get('hash'));

// Basic anti-brute-force: reject obviously invalid hashes early
if (!preg_match('/^[a-f0-9]{32}$/', $hash)) {
    http_response_code(404);
    $errMsg   = 'Link non valido o scaduto.';
    $cliente  = null;
    $files    = [];
    $appName  = 'EasyBooking';
    $lezioniData = ['lezioni' => [], 'scadenza_pacchetto' => null, 'pacchetto_nome' => ''];
} else {
    $pdo = Database::getInstance();

    $appName = cloudAppName($pdo);

    // Look up client by hash
    $stmt = $pdo->prepare(
        'SELECT id, nome, cognome, cloud_cartella, cloud_enabled
         FROM clienti WHERE cloud_hash = ? AND cloud_enabled = 1 LIMIT 1'
    );
    $stmt->execute([$hash]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        http_response_code(404);
        $errMsg  = 'Link non valido o scaduto.';
        $files   = [];
        $lezioniData = ['lezioni' => [], 'scadenza_pacchetto' => null, 'pacchetto_nome' => ''];
    } else {
        $errMsg = null;

        // Fetch future lessons and active package info
        $lezioniData = cloudLezioniFuture($pdo, (int)$cliente['id']);

        // List files for this client
        $stmt = $pdo->prepare(
            'SELECT id, nome_originale, dimensione_bytes, mime_type, nota, created_at
             FROM cloud_files WHERE cliente_id = ? ORDER BY created_at DESC'
        );
        $stmt->execute([$cliente['id']]);
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($files as &$f) {
            $f['dimensione_human']  = cloudFormatSize((int)$f['dimensione_bytes']);
            $f['icon']              = cloudFileIcon($f['mime_type']);
            $f['is_audio']          = in_array($f['mime_type'], CLOUD_AUDIO_MIMES, true);
            $f['created_at_human']  = !empty($f['created_at'])
                ? date('d/m/Y', strtotime((string)$f['created_at']))
                : '';
        }
        unset($f);

        // Handle file download request
        $action = get('action');
        if ($action === 'download') {
            $fileId = sanitizeInt(get('file_id'));
            foreach ($files as $f) {
                if ((int)$f['id'] === $fileId) {
                    $sf = $pdo->prepare('SELECT nome_file, mime_type, nome_originale FROM cloud_files WHERE id = ? AND cliente_id = ? LIMIT 1');
                    $sf->execute([$fileId, $cliente['id']]);
                    $fileRow = $sf->fetch(PDO::FETCH_ASSOC);
                    if ($fileRow) {
                        $filePath = cloudFilePath($cliente['cloud_cartella'], $fileRow['nome_file']);
                        if (is_file($filePath)) {
                            ob_end_clean();
                            $safeName = preg_replace('/[\r\n"\\\\]/', '_', $fileRow['nome_originale']);
                            header('Content-Type: ' . ($fileRow['mime_type'] ?? 'application/octet-stream'));
                            header('Content-Disposition: attachment; filename="' . $safeName . '"');
                            header('Content-Length: ' . filesize($filePath));
                            header('X-Content-Type-Options: nosniff');
                            readfile($filePath);
                            exit;
                        }
                    }
                    break;
                }
            }
            http_response_code(404);
        }

        // Handle audio stream request
        if ($action === 'stream') {
            $fileId = sanitizeInt(get('file_id'));
            $sf = $pdo->prepare('SELECT nome_file, mime_type FROM cloud_files WHERE id = ? AND cliente_id = ? LIMIT 1');
            $sf->execute([$fileId, $cliente['id']]);
            $fileRow = $sf->fetch(PDO::FETCH_ASSOC);
            if ($fileRow) {
                $filePath = cloudFilePath($cliente['cloud_cartella'], $fileRow['nome_file']);
                if (is_file($filePath)) {
                    $size = filesize($filePath);
                    $mime = $fileRow['mime_type'] ?? 'application/octet-stream';
                    ob_end_clean();
                    header('Accept-Ranges: bytes');
                    header('Content-Type: ' . $mime);
                    header('X-Content-Type-Options: nosniff');
                    if (isset($_SERVER['HTTP_RANGE'])) {
                        [$unit, $range] = explode('=', $_SERVER['HTTP_RANGE'], 2);
                        if ($unit === 'bytes') {
                            [$start, $end] = explode('-', $range, 2);
                            $start = (int)$start;
                            $end   = $end !== '' ? (int)$end : $size - 1;
                            $end   = min($end, $size - 1);
                            http_response_code(206);
                            header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
                            header('Content-Length: ' . ($end - $start + 1));
                            $fp = fopen($filePath, 'rb');
                            fseek($fp, $start);
                            echo fread($fp, $end - $start + 1);
                            fclose($fp);
                            exit;
                        }
                    }
                    header('Content-Length: ' . $size);
                    readfile($filePath);
                    exit;
                }
            }
            http_response_code(404);
            exit;
        }
    }
}

ob_end_clean();

$_clienteNome  = $cliente ? h($cliente['cognome'] . ' ' . $cliente['nome']) : '';
$_pageTitle    = $cliente ? $cliente['cognome'] . ' ' . $cliente['nome'] . ' – Cloud Page' : 'Pagina non trovata';
$_streamBase   = h($_scriptDir . '/share/' . urlencode($hash) . '/stream/');
$_downloadBase = h($_scriptDir . '/share/' . urlencode($hash) . '/download/');
?><!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($_pageTitle) ?></title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
            color: var(--accent);
        }
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
        #waveform { min-height: 80px; background: #f7f9fc; border-radius: 0.5rem; overflow: hidden; }
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
    <div class="container" style="position: relative; z-index: 1;">
        <div class="app-brand"><i class="fas fa-cloud me-1"></i><?= h($appName) ?></div>
        <div class="page-label">Cloud Page</div>
        <?php if ($cliente): ?>
            <div class="client-name"><?= $_clienteNome ?></div>
        <?php endif; ?>
    </div>
</header>

<main class="container pb-4" style="max-width: 800px;">
<?php if ($errMsg): ?>
    <div class="section-card p-4">
        <div class="empty-state">
            <i class="fas fa-exclamation-circle text-danger"></i>
            <h5 class="mb-1"><?= h($errMsg) ?></h5>
            <p class="small mb-0">Contatta la scuola se hai bisogno di un nuovo accesso.</p>
        </div>
    </div>
<?php else: ?>

    <!-- ── Prossime lezioni ──────────────────────────────── -->
    <div class="section-card">
        <div class="section-title">
            <i class="fas fa-calendar-alt"></i> Prossime lezioni
            <?php if ($lezioniData['scadenza_pacchetto']): ?>
                <span class="badge-scadenza ms-auto">
                    <i class="fas fa-clock me-1"></i>Scadenza pacchetto: <?= h($lezioniData['scadenza_pacchetto']) ?>
                </span>
            <?php endif; ?>
        </div>
        <?php if (empty($lezioniData['lezioni'])): ?>
            <div class="empty-state py-4">
                <i class="fas fa-calendar-check"></i>
                <p class="mb-0">Nessuna lezione pianificata.</p>
            </div>
        <?php else: ?>
            <?php foreach ($lezioniData['lezioni'] as $l): ?>
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
                        <?php if ($l['pacchetto']): ?>
                            <i class="fas fa-box me-1"></i><?= h($l['pacchetto']) ?>
                        <?php endif; ?>
                        <?php if ($l['strumento']): ?>
                            <?= $l['pacchetto'] ? ' &middot; ' : '' ?><i class="fas fa-music me-1"></i><?= h($l['strumento']) ?>
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
                <p class="mb-0">Nessun file disponibile.</p>
            </div>
        <?php else: ?>
            <?php foreach ($files as $f):
                // Determine icon colour class
                $iconClass = 'file-icon-box';
                if ($f['is_audio']) $iconClass .= ' audio';
                elseif (str_starts_with($f['mime_type'] ?? '', 'video/')) $iconClass .= ' video';
                elseif (str_starts_with($f['mime_type'] ?? '', 'image/')) $iconClass .= ' image';
                elseif (($f['mime_type'] ?? '') === 'application/pdf') $iconClass .= ' pdf';
                elseif (str_contains($f['icon'], 'word'))  $iconClass .= ' word';
                elseif (str_contains($f['icon'], 'excel')) $iconClass .= ' excel';
                elseif (str_contains($f['icon'], 'powerpoint')) $iconClass .= ' pptx';
                elseif (str_contains($f['icon'], 'archive')) $iconClass .= ' zip';
            ?>
            <div class="file-item">
                <div class="<?= $iconClass ?>">
                    <i class="fas <?= h($f['icon']) ?>"></i>
                </div>
                <div class="file-body">
                    <div class="file-name"><?= h($f['nome_originale']) ?></div>
                    <div class="file-meta">
                        <i class="fas fa-weight-hanging me-1"></i><?= h($f['dimensione_human']) ?>
                        <?php if ($f['created_at_human']): ?>
                         &middot; <i class="fas fa-calendar me-1"></i><?= h($f['created_at_human']) ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($f['nota']): ?>
                        <div class="file-nota"><i class="fas fa-sticky-note me-1"></i><?= h($f['nota']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="file-actions">
                    <?php if ($f['is_audio']): ?>
                        <button class="btn btn-sm btn-outline-success play-audio"
                                type="button"
                                data-file-id="<?= (int)$f['id'] ?>"
                                data-file-name="<?= h($f['nome_originale']) ?>">
                            <i class="fas fa-waveform-lines me-1"></i>Ascolta
                        </button>
                    <?php endif; ?>
                    <a href="<?= $_downloadBase . (int)$f['id'] ?>"
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
<div class="modal fade" id="audioModal" tabindex="-1" aria-labelledby="ws-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h6 class="modal-title mb-0"><i class="fas fa-music me-2 text-success"></i>Player Audio</h6>
                    <p id="ws-title" class="fw-bold mb-0 mt-1 fs-6 text-truncate" style="max-width:380px;"></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <!-- WaveSurfer container -->
                <div id="waveform" class="mb-2"></div>
                <!-- Fallback native player (hidden by default) -->
                <audio id="ws-fallback" controls class="w-100 mt-2" style="display:none; border-radius:6px;">
                    Il tuo browser non supporta l'audio HTML5.
                </audio>
                <!-- Controls row -->
                <div class="player-controls">
                    <button id="ws-play" class="btn btn-sm btn-primary" disabled>
                        <i class="fas fa-play"></i>
                    </button>
                    <span class="time-display">
                        <span id="ws-current">0:00</span> / <span id="ws-duration">0:00</span>
                    </span>
                    <select id="ws-speed" class="form-select form-select-sm ms-auto" style="width:auto;">
                        <option value="0.5">0.5×</option>
                        <option value="0.75">0.75×</option>
                        <option value="1" selected>1×</option>
                        <option value="1.25">1.25×</option>
                        <option value="1.5">1.5×</option>
                        <option value="2">2×</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Chiudi</button>
            </div>
        </div>
    </div>
</div>

<footer class="cloud-footer">
    <i class="fas fa-lock me-1"></i>Accesso protetto &mdash; sola lettura
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/wavesurfer.js@7/dist/wavesurfer.min.js"></script>
<script>
(function () {
    'use strict';

    const STREAM_BASE  = <?= json_encode($_streamBase) ?>;
    const playButtons  = document.querySelectorAll('.play-audio');
    const modalEl      = document.getElementById('audioModal');
    if (!modalEl) { return; }

    let ws = null;
    let modalInstance = null;

    const waveEl     = document.getElementById('waveform');
    const fallbackEl = document.getElementById('ws-fallback');
    const playBtn    = document.getElementById('ws-play');
    const curEl      = document.getElementById('ws-current');
    const durEl      = document.getElementById('ws-duration');
    const speedEl    = document.getElementById('ws-speed');
    const titleEl    = document.getElementById('ws-title');

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
        waveEl.style.display     = 'block';
        waveEl.innerHTML         = '';
        playBtn.disabled         = true;
        playBtn.style.display    = '';
        playBtn.innerHTML        = '<i class="fas fa-play"></i>';
        curEl.textContent        = '0:00';
        durEl.textContent        = '0:00';
    }

    function showFallback(url) {
        waveEl.style.display      = 'none';
        fallbackEl.style.display  = 'block';
        fallbackEl.src            = url;
        fallbackEl.load();
        playBtn.style.display     = 'none';
    }

    function openPlayer(fileId, fileName) {
        titleEl.textContent = fileName;
        destroyWs();

        const url = STREAM_BASE + fileId;

        try {
            if (typeof WaveSurfer === 'undefined') { throw new Error('WaveSurfer not loaded'); }
            ws = WaveSurfer.create({
                container: waveEl,
                waveColor: 'rgba(94,114,228,0.35)',
                progressColor: '#5e72e4',
                cursorColor: '#5e72e4',
                height: 72,
                barWidth: 2,
                barRadius: 2,
                url: url,
            });

            ws.on('ready', () => {
                durEl.textContent = fmt(ws.getDuration());
                playBtn.disabled  = false;
                ws.play();
            });
            ws.on('timeupdate', (t) => { curEl.textContent = fmt(t); });
            ws.on('play',  () => { playBtn.innerHTML = '<i class="fas fa-pause"></i>'; });
            ws.on('pause', () => { playBtn.innerHTML = '<i class="fas fa-play"></i>'; });
            ws.on('finish',() => {
                playBtn.innerHTML = '<i class="fas fa-play"></i>';
                curEl.textContent = fmt(ws.getDuration());
            });
            ws.on('error', () => { showFallback(url); });
        } catch (e) {
            showFallback(url);
        }

        if (!modalInstance) {
            modalInstance = new bootstrap.Modal(modalEl);
        }
        modalInstance.show();
    }

    playButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            openPlayer(btn.dataset.fileId || '', btn.dataset.fileName || '');
        });
    });

    playBtn.addEventListener('click', () => { if (ws) ws.playPause(); });

    speedEl.addEventListener('change', () => {
        const rate = parseFloat(speedEl.value);
        if (ws) ws.setPlaybackRate(rate);
        if (fallbackEl.src) fallbackEl.playbackRate = rate;
    });

    modalEl.addEventListener('hide.bs.modal', () => {
        destroyWs();
        speedEl.value = '1';
    });
})();
</script>
</body>
</html>


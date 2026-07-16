<?php
// cloud_pubblica.php – Public cloud access page (no login required)
// Access via: /share/[HASH] (rewritten by .htaccess) or directly with ?hash=[HASH]

declare(strict_types=1);

ob_start();
ini_set('log_errors', '1');
ini_set('display_errors', '0');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/cloud-functions.php';

function h(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Validate hash from GET parameter
$hash = trim(get('hash'));

// Basic anti-brute-force: reject obviously invalid hashes early
if (!preg_match('/^[a-f0-9]{32}$/', $hash)) {
    http_response_code(404);
    $errMsg = 'Link non valido o scaduto.';
    $cliente = null;
    $files   = [];
} else {
    $pdo = Database::getInstance();

    // Look up client by hash
    $stmt = $pdo->prepare(
        'SELECT id, nome, cognome, cloud_cartella, cloud_enabled
         FROM clienti WHERE cloud_hash = ? AND cloud_enabled = 1 LIMIT 1'
    );
    $stmt->execute([$hash]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        http_response_code(404);
        $errMsg = 'Link non valido o scaduto.';
        $files   = [];
    } else {
        $errMsg = null;
        // List files for this client
        $stmt = $pdo->prepare(
            'SELECT id, nome_originale, dimensione_bytes, mime_type, nota, created_at
             FROM cloud_files WHERE cliente_id = ? ORDER BY created_at DESC'
        );
        $stmt->execute([$cliente['id']]);
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($files as &$f) {
            $f['dimensione_human'] = cloudFormatSize((int)$f['dimensione_bytes']);
            $f['icon']             = cloudFileIcon($f['mime_type']);
            $f['is_audio']         = in_array($f['mime_type'], CLOUD_AUDIO_MIMES, true);
        }
        unset($f);

        // Handle file download request
        $action = get('action');
        if ($action === 'download') {
            $fileId = sanitizeInt(get('file_id'));
            foreach ($files as $f) {
                if ((int)$f['id'] === $fileId) {
                    $path = cloudFilePath($cliente['cloud_cartella'], '');
                    // Re-fetch nome_file for this specific file
                    $sf = $pdo->prepare('SELECT nome_file, mime_type, nome_originale FROM cloud_files WHERE id = ? AND cliente_id = ? LIMIT 1');
                    $sf->execute([$fileId, $cliente['id']]);
                    $fileRow = $sf->fetch(PDO::FETCH_ASSOC);
                    if ($fileRow) {
                        $filePath = cloudFilePath($cliente['cloud_cartella'], $fileRow['nome_file']);
                        if (is_file($filePath)) {
                            ob_end_clean();
                            header('Content-Type: ' . ($fileRow['mime_type'] ?? 'application/octet-stream'));
                            header('Content-Disposition: attachment; filename="' . addslashes($fileRow['nome_originale']) . '"');
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
?><!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $cliente ? h($cliente['cognome'] . ' ' . $cliente['nome']) . ' – File Condivisi' : 'Pagina non trovata' ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background: #f5f7fa; }
        .cloud-header { background: #1a1a2e; color: #fff; padding: 1.5rem 0; }
        .cloud-header .brand { font-size: 1.3rem; font-weight: 700; }
        .cloud-header .brand i { color: #6c8ebf; }
        .file-card { border: 1px solid #dee2e6; border-radius: 0.5rem; background: #fff; transition: box-shadow 0.15s; }
        .file-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.1); }
        .file-icon { font-size: 2rem; color: #6c757d; }
        .file-icon.audio { color: #0d6efd; }
        .footer-pub { background: #1a1a2e; color: #aaa; font-size: 0.8rem; padding: 1rem 0; margin-top: 3rem; }
    </style>
</head>
<body>
<header class="cloud-header mb-4">
    <div class="container">
        <div class="brand"><i class="fas fa-cloud me-2"></i>File Condivisi</div>
        <?php if ($cliente): ?>
            <div class="mt-1 opacity-75"><?= h($cliente['cognome'] . ' ' . $cliente['nome']) ?></div>
        <?php endif; ?>
    </div>
</header>

<main class="container py-2">
<?php if ($errMsg): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle me-2"></i><?= h($errMsg) ?>
    </div>
<?php else: ?>

    <?php if (empty($files)): ?>
        <div class="text-center text-muted py-5">
            <i class="fas fa-folder-open fa-3x mb-3 d-block opacity-50"></i>
            <p>Nessun file disponibile.</p>
        </div>
    <?php else: ?>
        <p class="text-muted mb-4"><i class="fas fa-file me-1"></i><?= count($files) ?> file disponibili</p>
        <div class="row g-3">
        <?php foreach ($files as $f): ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="file-card p-3">
                    <div class="d-flex align-items-start gap-3">
                        <div class="file-icon <?= $f['is_audio'] ? 'audio' : '' ?>">
                            <i class="fas <?= h($f['icon']) ?>"></i>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-bold text-break"><?= h($f['nome_originale']) ?></div>
                            <div class="text-muted small"><?= h($f['dimensione_human']) ?></div>
                            <?php if ($f['nota']): ?>
                                <div class="text-secondary small mt-1 fst-italic"><?= h($f['nota']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="mt-3 d-flex gap-2 flex-wrap">
                        <a href="?hash=<?= urlencode($hash) ?>&action=download&file_id=<?= (int)$f['id'] ?>"
                           class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-download me-1"></i>Download
                        </a>
                        <?php if ($f['is_audio']): ?>
                            <button class="btn btn-sm btn-outline-primary"
                                    onclick="playAudio(<?= (int)$f['id'] ?>, '<?= h($f['nome_originale']) ?>')">
                                <i class="fas fa-play me-1"></i>Ascolta
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php endif; ?>
</main>

<!-- Audio player modal -->
<div class="modal fade" id="audioModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-music me-2"></i>Player Audio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p id="audio-modal-title" class="fw-bold mb-3"></p>
                <audio id="audio-player" controls class="w-100">
                    Il tuo browser non supporta l'audio HTML5.
                </audio>
            </div>
        </div>
    </div>
</div>

<footer class="footer-pub text-center">
    <div class="container">Powered by EasyBooking &mdash; Accesso a sola lettura</div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function playAudio(fileId, fileName) {
    document.getElementById('audio-modal-title').textContent = fileName;
    const player = document.getElementById('audio-player');
    player.src = '<?= h(rtrim(dirname($_SERVER['PHP_SELF']), '/')) ?>/cloud_pubblica.php?hash=<?= urlencode($hash) ?>&action=stream&file_id=' + fileId;
    player.load();
    new bootstrap.Modal(document.getElementById('audioModal')).show();
}
document.getElementById('audioModal').addEventListener('hide.bs.modal', function () {
    const p = document.getElementById('audio-player');
    p.pause();
    p.src = '';
});
</script>
</body>
</html>

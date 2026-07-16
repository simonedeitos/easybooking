<?php
/**
 * Public Cloud Storage Access Point
 * URL: vocefutura.it/share/[HASH]
 * No login required - hash-based access only
 */

ob_start();
ini_set('log_errors', '1');
ini_set('display_errors', '0');

// ════════════════════════════════════════════════════════════
// FIND AND INCLUDE CONFIG - Percorsi dinamici
// ════════════════════════════════════════════════════════════

$config_path = null;
$cloud_functions_path = null;

function readEnvConfigValue(string $key, array $envFiles): ?string
{
    foreach ($envFiles as $envFile) {
        if (!$envFile || !is_file($envFile)) {
            continue;
        }
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                continue;
            }
            [$lineKey, $lineValue] = array_map('trim', explode('=', $line, 2));
            if ($lineKey !== $key) {
                continue;
            }
            return trim($lineValue, " \t\n\r\0\x0B\"'");
        }
    }

    return null;
}

// Determina il percorso base
$current_dir = __DIR__;
$parent_dir = dirname($current_dir);
$grandparent_dir = dirname($parent_dir);
$great_grandparent_dir = dirname($grandparent_dir);

$possible_env_files = array_values(array_unique([
    // Hostinger / public_html document root + EasyBooking in /public_html/easybooking/webapp
    $current_dir . '/../public_html/easybooking/webapp/.env',
    $current_dir . '/easybooking/webapp/.env',
    $parent_dir . '/.env',
    $parent_dir . '/easybooking/webapp/.env',
    $grandparent_dir . '/webapp/.env',
    $great_grandparent_dir . '/webapp/.env',
]));

$explicit_config_path = getenv('EASYBOOKING_CONFIG_PATH')
    ?: ($_SERVER['EASYBOOKING_CONFIG_PATH'] ?? '')
    ?: ($_ENV['EASYBOOKING_CONFIG_PATH'] ?? '')
    ?: readEnvConfigValue('EASYBOOKING_CONFIG_PATH', $possible_env_files);

// Prova prima un percorso esplicito da ambiente/.env, poi i layout più comuni.
$possible_configs = array_values(array_filter(array_unique([
    $explicit_config_path,
    $current_dir . '/../public_html/easybooking/webapp/config/database.php',
    $current_dir . '/easybooking/webapp/config/database.php',
    $current_dir . '/../../config/database.php',           // Se in public_html/
    $parent_dir . '/config/database.php',                  // Se in webapp/public_html/
    $grandparent_dir . '/webapp/config/database.php',      // Se in webapp/
    dirname(dirname(dirname(__FILE__))) . '/webapp/config/database.php',
    $great_grandparent_dir . '/webapp/config/database.php',
])));

foreach ($possible_configs as $path) {
    if (file_exists($path)) {
        $config_path = $path;
        break;
    }
}

if (!$config_path) {
    http_response_code(500);
    error_log('Cloud config not found. Paths tried: ' . implode(', ', $possible_configs));
    die('Server configuration error. Contact administrator.');
}

// Includi database config
require_once $config_path;

// Trova cloud-functions.php nello stesso percorso di database.php
$cloud_functions_path = dirname($config_path) . '/cloud-functions.php';
if (!file_exists($cloud_functions_path)) {
    http_response_code(500);
    die('Cloud functions not found.');
}
require_once $cloud_functions_path;

// ════════════════════════════════════════════════════════════
// INIT
// ════════════════════════════════════════════════════════════

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = Database::getInstance();

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// ════════════════════════════════════════════════════════════
// LEGGI PARAMETRI
// ════════════════════════════════════════════════════════════

$hash = isset($_GET['hash']) ? trim($_GET['hash']) : '';
$action = isset($_GET['action']) ? trim($_GET['action']) : '';
$file_id = isset($_GET['file_id']) ? (int)$_GET['file_id'] : 0;

// Valida hash format (32 hex chars da bin2hex(random_bytes(16)))
if (empty($hash) || !preg_match('/^[a-f0-9]{32}$/', $hash)) {
    http_response_code(404);
    die('Invalid or missing access hash.');
}

try {
    // ════════════════════ TROVA CLIENTE ════════════════════
    $stmt = $pdo->prepare(
        'SELECT c.id, c.nome, c.cognome, c.cloud_enabled, c.cloud_cartella
         FROM clienti c
         WHERE c.cloud_hash = ? AND c.cloud_enabled = 1 LIMIT 1'
    );
    $stmt->execute([$hash]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cliente) {
        http_response_code(403);
        die('Access denied or client not found.');
    }
    
    $cliente_id = $cliente['id'];
    $cliente_nome = h($cliente['cognome'] . ' ' . $cliente['nome']);
    $cartella = $cliente['cloud_cartella'];
    
    // ════════════════════ ACTION: DOWNLOAD ════════════════════
    if ($action === 'download' && $file_id > 0) {
        $stmt = $pdo->prepare(
            'SELECT cf.id, cf.nome_file, cf.nome_originale, cf.dimensione_bytes
             FROM cloud_files cf
             WHERE cf.id = ? AND cf.cliente_id = ? LIMIT 1'
        );
        $stmt->execute([$file_id, $cliente_id]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$file) {
            http_response_code(404);
            die('File not found.');
        }
        
        $file_path = cloudFilePath($cartella, $file['nome_file']);
        
        if (!file_exists($file_path) || !is_file($file_path)) {
            http_response_code(404);
            error_log('Cloud file not found: ' . $file_path);
            die('File not accessible.');
        }
        
        ob_end_clean();
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file['nome_originale'] . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        
        readfile($file_path);
        exit;
    }
    
    // ════════════════════ ACTION: GET_FILE (streaming audio) ════════════════════
    if ($action === 'get_file' && $file_id > 0) {
        $stmt = $pdo->prepare(
            'SELECT cf.nome_file, cf.mime_type
             FROM cloud_files cf
             WHERE cf.id = ? AND cf.cliente_id = ? LIMIT 1'
        );
        $stmt->execute([$file_id, $cliente_id]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$file) {
            http_response_code(404);
            die('File not found.');
        }
        
        $file_path = cloudFilePath($cartella, $file['nome_file']);
        
        if (!file_exists($file_path) || !is_file($file_path)) {
            http_response_code(404);
            die('File not accessible.');
        }
        
        $size = filesize($file_path);
        $mime = $file['mime_type'] ?? 'application/octet-stream';
        
        ob_end_clean();
        header('Accept-Ranges: bytes');
        header('Content-Type: ' . $mime);
        header('X-Content-Type-Options: nosniff');
        
        // Handle range requests for audio streaming
        if (isset($_SERVER['HTTP_RANGE'])) {
            $range = $_SERVER['HTTP_RANGE'];
            if (preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
                $start = (int)$matches[1];
                $end = $matches[2] !== '' ? (int)$matches[2] : $size - 1;
                $end = min($end, $size - 1);
                
                http_response_code(206);
                header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
                header('Content-Length: ' . ($end - $start + 1));
                
                $fp = fopen($file_path, 'rb');
                fseek($fp, $start);
                echo fread($fp, $end - $start + 1);
                fclose($fp);
                exit;
            }
        }
        
        header('Content-Length: ' . $size);
        readfile($file_path);
        exit;
    }
    
    // ════════════════════ LISTA FILE (pagina principale) ════════════════════
    $stmt = $pdo->prepare(
        'SELECT id, nome_originale, dimensione_bytes, nota, mime_type, created_at
         FROM cloud_files
         WHERE cliente_id = ?
         ORDER BY created_at DESC'
    );
    $stmt->execute([$cliente_id]);
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_size = 0;
    foreach ($files as $f) {
        $total_size += (int)$f['dimensione_bytes'];
    }
    
} catch (Exception $e) {
    http_response_code(500);
    error_log('Cloud public error: ' . $e->getMessage());
    die('Database error.');
}

// ════════════════════════════════════════════════════════════
// HELPER FUNCTIONS
// ════════════════════════════════════════════════════════════

function getFileIcon($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $icons = [
        'pdf' => ['icon' => 'fa-file-pdf', 'color' => 'text-danger'],
        'doc' => ['icon' => 'fa-file-word', 'color' => 'text-primary'],
        'docx' => ['icon' => 'fa-file-word', 'color' => 'text-primary'],
        'xls' => ['icon' => 'fa-file-excel', 'color' => 'text-success'],
        'xlsx' => ['icon' => 'fa-file-excel', 'color' => 'text-success'],
        'zip' => ['icon' => 'fa-file-zipper', 'color' => 'text-warning'],
        'rar' => ['icon' => 'fa-file-zipper', 'color' => 'text-warning'],
        'mp3' => ['icon' => 'fa-file-audio', 'color' => 'text-info'],
        'wav' => ['icon' => 'fa-file-audio', 'color' => 'text-info'],
        'm4a' => ['icon' => 'fa-file-audio', 'color' => 'text-info'],
        'aac' => ['icon' => 'fa-file-audio', 'color' => 'text-info'],
        'flac' => ['icon' => 'fa-file-audio', 'color' => 'text-info'],
        'ogg' => ['icon' => 'fa-file-audio', 'color' => 'text-info'],
        'mp4' => ['icon' => 'fa-file-video', 'color' => 'text-purple'],
        'webm' => ['icon' => 'fa-file-video', 'color' => 'text-purple'],
        'jpg' => ['icon' => 'fa-file-image', 'color' => 'text-secondary'],
        'jpeg' => ['icon' => 'fa-file-image', 'color' => 'text-secondary'],
        'png' => ['icon' => 'fa-file-image', 'color' => 'text-secondary'],
        'gif' => ['icon' => 'fa-file-image', 'color' => 'text-secondary'],
        'webp' => ['icon' => 'fa-file-image', 'color' => 'text-secondary'],
    ];
    return $icons[$ext] ?? ['icon' => 'fa-file', 'color' => 'text-muted'];
}

function isAudioFile($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ['mp3', 'wav', 'm4a', 'aac', 'flac', 'ogg', 'webm']);
}

function formatFileSize($bytes) {
    $bytes = (int)$bytes;
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cloud Storage - <?= $cliente_nome ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container-custom {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .header-cloud {
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }
        
        .header-cloud i {
            font-size: 3.5rem;
            margin-bottom: 15px;
            display: block;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .header-cloud h1 {
            font-size: 2.2rem;
            font-weight: 700;
            margin: 0 0 10px 0;
        }
        
        .header-cloud p {
            font-size: 1.1rem;
            opacity: 0.95;
            margin: 0;
        }
        
        .card {
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            border: none;
            border-radius: 15px;
            overflow: hidden;
        }
        
        .stats-box {
            background: rgba(255,255,255,0.15);
            color: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            backdrop-filter: blur(10px);
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            display: block;
        }
        
        .stat-label {
            font-size: 0.95rem;
            opacity: 0.9;
            margin-top: 5px;
        }
        
        .file-item {
            border-left: 5px solid #667eea;
            transition: all 0.3s ease;
        }
        
        .file-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .file-icon {
            font-size: 2.5rem;
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: #f8f9fa;
            flex-shrink: 0;
        }
        
        .file-info {
            flex: 1;
            min-width: 0;
        }
        
        .file-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
            word-break: break-word;
        }
        
        .file-meta {
            font-size: 0.9rem;
            color: #666;
        }
        
        .file-nota {
            margin-top: 8px;
            padding: 8px;
            background: #f0f0f0;
            border-radius: 6px;
            font-size: 0.85rem;
            color: #555;
            border-left: 3px solid #667eea;
        }
        
        .btn-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .btn-actions button,
        .btn-actions a {
            padding: 6px 12px;
            font-size: 0.9rem;
            border-radius: 6px;
            transition: all 0.2s;
            white-space: nowrap;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.4;
        }
        
        .empty-state h5 {
            color: #666;
            margin-bottom: 10px;
        }
        
        footer {
            text-align: center;
            color: white;
            margin-top: 50px;
            font-size: 0.9rem;
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .header-cloud h1 {
                font-size: 1.8rem;
            }
            
            .file-icon {
                width: 50px;
                height: 50px;
                font-size: 1.8rem;
            }
            
            .btn-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .btn-actions button,
            .btn-actions a {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container-custom">
        <!-- Header -->
        <div class="header-cloud">
            <i class="fas fa-cloud"></i>
            <h1>Cloud Storage</h1>
            <p><?= $cliente_nome ?></p>
        </div>

        <!-- Stats -->
        <div class="stats-box">
            <div class="stat-item">
                <span class="stat-number"><?= count($files) ?></span>
                <span class="stat-label">File</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?= formatFileSize($total_size) ?></span>
                <span class="stat-label">Totale</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">✓</span>
                <span class="stat-label">Disponibile</span>
            </div>
        </div>

        <!-- Files Container -->
        <div class="card">
            <div class="card-body p-4">
                <h5 class="mb-4">
                    <i class="fas fa-folder-open me-2"></i>I tuoi materiali
                </h5>

                <?php if (empty($files)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h5>Nessun file disponibile</h5>
                    <p class="text-muted">I materiali compariranno qui</p>
                </div>
                <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($files as $file): 
                        $icon_data = getFileIcon($file['nome_originale']);
                        $is_audio = isAudioFile($file['nome_originale']);
                    ?>
                    <div class="col-12">
                        <div class="card file-item mb-0">
                            <div class="card-body p-3">
                                <div class="d-flex gap-3 align-items-start flex-wrap">
                                    <div class="file-icon <?= $icon_data['color'] ?>">
                                        <i class="fas <?= $icon_data['icon'] ?>"></i>
                                    </div>
                                    
                                    <div class="file-info">
                                        <div class="file-name"><?= h($file['nome_originale']) ?></div>
                                        <div class="file-meta">
                                            <i class="fas fa-database me-1"></i><?= formatFileSize($file['dimensione_bytes']) ?> • 
                                            <i class="fas fa-calendar me-1"></i><?= date('d/m/Y H:i', strtotime($file['created_at'])) ?>
                                        </div>
                                        
                                        <?php if ($file['nota']): ?>
                                        <div class="file-nota">
                                            <i class="fas fa-sticky-note me-2"></i><?= h($file['nota']) ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="btn-actions ms-auto">
                                        <?php if ($is_audio): ?>
                                        <button class="btn btn-sm btn-outline-success play-audio" 
                                                data-file-id="<?= $file['id'] ?>" 
                                                title="Riproduci">
                                            <i class="fas fa-play me-1"></i>Riproduci
                                        </button>
                                        <?php endif; ?>
                                        
                                        <a href="?hash=<?= h($hash) ?>&action=download&file_id=<?= $file['id'] ?>" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="Scarica">
                                            <i class="fas fa-download me-1"></i>Scarica
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer -->
        <footer>
            <p><i class="fas fa-lock me-2"></i>Accesso protetto e privato</p>
        </footer>
    </div>

    <!-- Audio Player Modal -->
    <div class="modal fade" id="audioModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-music me-2"></i>Riproduci Audio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <audio controls class="w-100" id="audioPlayer" style="border-radius: 8px;">
                        <source src="" type="audio/mpeg">
                        Il tuo browser non supporta l'elemento audio.
                    </audio>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Chiudi</button>
                    <a id="downloadBtn" href="#" class="btn btn-primary">
                        <i class="fas fa-download me-2"></i>Scarica
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.play-audio').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const fileId = btn.dataset.fileId;
                const hash = new URLSearchParams(window.location.search).get('hash');
                
                const audioPlayer = document.getElementById('audioPlayer');
                audioPlayer.src = `?hash=${hash}&action=get_file&file_id=${fileId}`;
                audioPlayer.load();
                
                const downloadBtn = document.getElementById('downloadBtn');
                downloadBtn.href = `?hash=${hash}&action=download&file_id=${fileId}`;
                
                const modal = new bootstrap.Modal(document.getElementById('audioModal'));
                modal.show();
            });
        });
    </script>
</body>
</html>

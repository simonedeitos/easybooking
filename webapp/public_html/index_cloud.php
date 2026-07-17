<?php
/**
 * index_cloud.php – Public cloud access controller for deployments where
 * public_html/ is the document root and the EasyBooking webapp/ directory
 * lives elsewhere on disk (shared hosting / Hostinger-style setups).
 *
 * To avoid fragile path guessing, configure EASYBOOKING_WEBAPP_PATH with the
 * absolute path to the webapp/ directory. The recommended place is a local
 * public_html/.env file containing for example:
 * EASYBOOKING_WEBAPP_PATH=/home/username/easybooking/webapp
 */

declare(strict_types=1);

ob_start();
ini_set('log_errors', '1');
ini_set('display_errors', '0');

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function loadPublicCloudLocalConfig(string $envFile): void
{
    $configuredPath = getenv('EASYBOOKING_WEBAPP_PATH');
    $hasConfiguredPath = $configuredPath !== false && trim((string) $configuredPath) !== '';
    if (!is_file($envFile) || $hasConfiguredPath) {
        return;
    }

    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if ($key !== 'EASYBOOKING_WEBAPP_PATH' || $value === '') {
            continue;
        }

        $firstChar = $value[0];
        $lastChar = $value[strlen($value) - 1];
        if (($firstChar === '"' || $firstChar === "'") && $firstChar === $lastChar) {
            $value = substr($value, 1, -1);
        }

        putenv('EASYBOOKING_WEBAPP_PATH=' . $value);
        $_ENV['EASYBOOKING_WEBAPP_PATH'] = $value;
        $_SERVER['EASYBOOKING_WEBAPP_PATH'] = $value;
        break;
    }
}

function normalizeDirectoryPath(string $path): ?string
{
    $path = trim($path);
    if ($path === '') {
        return null;
    }

    $realPath = realpath($path);
    if ($realPath === false || !is_dir($realPath)) {
        return null;
    }

    return rtrim($realPath, DIRECTORY_SEPARATOR);
}

function renderCloudPage(array $state = []): void
{
    $state = array_merge([
        'page_title' => 'Cloud Storage',
        'cliente_nome' => '',
        'files' => [],
        'file_count' => 0,
        'total_size_human' => '0 B',
        'hash' => '',
        'error_title' => '',
        'error_message' => '',
    ], $state);

    $page_title = $state['page_title'];
    $cliente_nome = $state['cliente_nome'];
    $files = $state['files'];
    $file_count = $state['file_count'];
    $total_size_human = $state['total_size_human'];
    $hash = $state['hash'];
    $error_title = $state['error_title'];
    $error_message = $state['error_message'];

    require __DIR__ . '/cloud-cliente-template.php';
    exit;
}

function resolveWebappBootstrap(): array
{
    loadPublicCloudLocalConfig(__DIR__ . '/.env');

    $explicitPath = '';
    if (defined('EASYBOOKING_WEBAPP_PATH') && is_string(EASYBOOKING_WEBAPP_PATH)) {
        $explicitPath = trim(EASYBOOKING_WEBAPP_PATH);
    }
    if ($explicitPath === '') {
        $explicitPath = trim((string) (getenv('EASYBOOKING_WEBAPP_PATH') ?: ''));
    }

    $candidates = [];
    if ($explicitPath !== '') {
        $candidates[] = ['path' => $explicitPath, 'source' => 'EASYBOOKING_WEBAPP_PATH'];
    }

    foreach ([
        dirname(__DIR__),
        dirname(__DIR__) . '/webapp',
        dirname(dirname(__DIR__)) . '/webapp',
        dirname(__DIR__) . '/easybooking/webapp',
    ] as $fallbackPath) {
        $candidates[] = ['path' => $fallbackPath, 'source' => 'fallback'];
    }

    $pathsTried = [];
    foreach ($candidates as $candidate) {
        $normalizedPath = normalizeDirectoryPath($candidate['path']);
        $pathsTried[] = $candidate['path'];

        if ($normalizedPath === null) {
            continue;
        }

        $configPath = $normalizedPath . '/config/database.php';
        $cloudFunctionsPath = $normalizedPath . '/config/cloud-functions.php';
        if (!is_file($configPath) || !is_file($cloudFunctionsPath)) {
            continue;
        }

        error_log(sprintf(
            'Public cloud bootstrap using webapp path "%s" (source: %s)',
            $normalizedPath,
            $candidate['source']
        ));

        return [
            'webapp_path' => $normalizedPath,
            'config_path' => $configPath,
            'cloud_functions_path' => $cloudFunctionsPath,
        ];
    }

    throw new RuntimeException(
        'Unable to resolve the EasyBooking webapp path. Set EASYBOOKING_WEBAPP_PATH in public_html/.env. Paths tried: ' .
        implode(', ', array_unique($pathsTried))
    );
}

function sendCloudFile(string $filePath, string $mimeType, string $downloadName = ''): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($filePath));
    header('X-Content-Type-Options: nosniff');

    if ($downloadName !== '') {
        // Keep the fallback ASCII name conservative for legacy user agents while
        // sending the full UTF-8 filename via filename*= for modern browsers.
        $fallbackName = preg_replace('/[^A-Za-z0-9._-]/u', '_', $downloadName) ?: 'download';
        header(
            "Content-Disposition: attachment; filename=\"{$fallbackName}\"; filename*=UTF-8''" .
            rawurlencode($downloadName)
        );
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
    } else {
        header('Accept-Ranges: bytes');

        if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d+)-(\d*)/', (string) $_SERVER['HTTP_RANGE'], $matches)) {
            $size = filesize($filePath);
            $start = (int) $matches[1];
            $end = $matches[2] !== '' ? (int) $matches[2] : $size - 1;
            $end = min($end, $size - 1);

            if ($start <= $end) {
                http_response_code(206);
                header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
                header('Content-Length: ' . ($end - $start + 1));

                $fp = fopen($filePath, 'rb');
                if ($fp === false) {
                    error_log('Public cloud stream open failed: ' . $filePath);
                    http_response_code(500);
                    exit;
                }

                fseek($fp, $start);
                echo fread($fp, $end - $start + 1);
                fclose($fp);
                exit;
            }
        }
    }

    readfile($filePath);
    exit;
}

$hash = trim((string) ($_GET['hash'] ?? ''));
$action = trim((string) ($_GET['action'] ?? ''));
$fileId = isset($_GET['file_id']) ? (int) $_GET['file_id'] : 0;

if (!preg_match('/^[a-f0-9]{32}$/', $hash)) {
    http_response_code(404);
    renderCloudPage([
        'page_title' => 'Link non valido',
        'error_title' => 'Link non valido o scaduto',
        'error_message' => 'Il link richiesto non è disponibile. Contatta la scuola se hai bisogno di un nuovo accesso.',
    ]);
}

try {
    $bootstrap = resolveWebappBootstrap();

    require_once $bootstrap['config_path'];
    require_once $bootstrap['cloud_functions_path'];

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $pdo = Database::getInstance();

    $stmt = $pdo->prepare(
        'SELECT id, nome, cognome, cloud_enabled, cloud_cartella
         FROM clienti
         WHERE cloud_hash = ? AND cloud_enabled = 1
         LIMIT 1'
    );
    $stmt->execute([$hash]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        http_response_code(404);
        renderCloudPage([
            'page_title' => 'Link non disponibile',
            'error_title' => 'Contenuto non disponibile',
            'error_message' => 'Questo spazio cloud non è disponibile oppure il link non è più valido.',
        ]);
    }

    $clienteNome = trim(($cliente['cognome'] ?? '') . ' ' . ($cliente['nome'] ?? ''));
    $cartella = (string) ($cliente['cloud_cartella'] ?? '');
    $clienteId = (int) $cliente['id'];

    if ($action === 'download' && $fileId > 0) {
        $stmt = $pdo->prepare(
            'SELECT nome_file, nome_originale, mime_type
             FROM cloud_files
             WHERE id = ? AND cliente_id = ?
             LIMIT 1'
        );
        $stmt->execute([$fileId, $clienteId]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$file) {
            http_response_code(404);
            renderCloudPage([
                'page_title' => 'File non trovato',
                'error_title' => 'File non disponibile',
                'error_message' => 'Il file richiesto non è disponibile in questo momento.',
            ]);
        }

        $filePath = cloudFilePath($cartella, (string) $file['nome_file']);
        if (!is_file($filePath)) {
            error_log('Public cloud download file not found: ' . $filePath);
            http_response_code(404);
            renderCloudPage([
                'page_title' => 'File non trovato',
                'error_title' => 'File non disponibile',
                'error_message' => 'Il file richiesto non è disponibile in questo momento.',
            ]);
        }

        sendCloudFile(
            $filePath,
            (string) ($file['mime_type'] ?: 'application/octet-stream'),
            (string) $file['nome_originale']
        );
    }

    if ($action === 'get_file' && $fileId > 0) {
        $stmt = $pdo->prepare(
            'SELECT nome_file, mime_type
             FROM cloud_files
             WHERE id = ? AND cliente_id = ?
             LIMIT 1'
        );
        $stmt->execute([$fileId, $clienteId]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$file) {
            http_response_code(404);
            renderCloudPage([
                'page_title' => 'Audio non disponibile',
                'error_title' => 'Audio non disponibile',
                'error_message' => 'Il file audio richiesto non è disponibile in questo momento.',
            ]);
        }

        $filePath = cloudFilePath($cartella, (string) $file['nome_file']);
        if (!is_file($filePath)) {
            error_log('Public cloud stream file not found: ' . $filePath);
            http_response_code(404);
            renderCloudPage([
                'page_title' => 'Audio non disponibile',
                'error_title' => 'Audio non disponibile',
                'error_message' => 'Il file audio richiesto non è disponibile in questo momento.',
            ]);
        }

        sendCloudFile($filePath, (string) ($file['mime_type'] ?: 'application/octet-stream'));
    }

    $stmt = $pdo->prepare(
        'SELECT id, nome_originale, dimensione_bytes, nota, mime_type, created_at
         FROM cloud_files
         WHERE cliente_id = ?
         ORDER BY created_at DESC'
    );
    $stmt->execute([$clienteId]);
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalSize = 0;
    foreach ($files as &$file) {
        $dimensioneBytes = (int) ($file['dimensione_bytes'] ?? 0);
        $totalSize += $dimensioneBytes;
        $file['dimensione_human'] = cloudFormatSize($dimensioneBytes);
        $file['icon'] = cloudFileIcon($file['mime_type'] ?? null);
        $file['is_audio'] = in_array($file['mime_type'] ?? '', CLOUD_AUDIO_MIMES, true);
        $file['created_at_human'] = !empty($file['created_at'])
            ? date('d/m/Y H:i', strtotime((string) $file['created_at']))
            : '';
    }
    unset($file);

    renderCloudPage([
        'page_title' => 'Cloud Storage - ' . $clienteNome,
        'cliente_nome' => $clienteNome,
        'files' => $files,
        'file_count' => count($files),
        'total_size_human' => cloudFormatSize($totalSize),
        'hash' => $hash,
    ]);
} catch (Throwable $e) {
    error_log('Public cloud controller error: ' . $e->getMessage());
    http_response_code(500);
    renderCloudPage([
        'page_title' => 'Servizio temporaneamente non disponibile',
        'error_title' => 'Servizio temporaneamente non disponibile',
        'error_message' => 'Non è stato possibile aprire lo spazio cloud in questo momento. Riprova più tardi o contatta la scuola.',
    ]);
}

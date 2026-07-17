<?php
/**
 * index_cloud.php – Public cloud access controller for deployments where
 * public_html/ is the document root and the EasyBooking webapp/ directory
 * lives elsewhere on disk (shared hosting / Hostinger-style setups), including
 * scenarios where main domain and app subdomain use fully separate document
 * roots (and possibly separate hosting accounts).
 *
 * In these setups configure EASYBOOKING_WEBAPP_PATH with the absolute path to
 * the webapp/ directory. You can set it in a local public_html/.env file:
 * EASYBOOKING_WEBAPP_PATH=/home/username/easybooking/webapp
 *
 * Alternatively set it at server level (for example Apache/vhost):
 * SetEnv EASYBOOKING_WEBAPP_PATH "/home/username/easybooking/webapp"
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
    if (!is_file($envFile)) {
        return;
    }

    $supportedKeys = ['EASYBOOKING_WEBAPP_PATH', 'EASYBOOKING_DEBUG_TOKEN'];
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if (!in_array($key, $supportedKeys, true) || $value === '') {
            continue;
        }

        $alreadyConfigured = getenv($key);
        if ($alreadyConfigured !== false && trim((string) $alreadyConfigured) !== '') {
            continue;
        }

        $firstChar = $value[0];
        $lastChar = $value[strlen($value) - 1];
        if (($firstChar === '"' || $firstChar === "'") && $firstChar === $lastChar) {
            $value = substr($value, 1, -1);
        }

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
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

class PublicCloudBootstrapException extends RuntimeException
{
    /** @var string[] */
    private array $pathsTried;

    /** @var array<int, array{source: string, value: string}> */
    private array $configuredPathSources;

    /**
     * @param string[] $pathsTried
     * @param array<int, array{source: string, value: string}> $configuredPathSources
     */
    public function __construct(
        string $message,
        array $pathsTried = [],
        array $configuredPathSources = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->pathsTried = $pathsTried;
        $this->configuredPathSources = $configuredPathSources;
    }

    /** @return string[] */
    public function getPathsTried(): array
    {
        return $this->pathsTried;
    }

    /** @return array<int, array{source: string, value: string}> */
    public function getConfiguredPathSources(): array
    {
        return $this->configuredPathSources;
    }
}

class PublicCloudDatabaseException extends RuntimeException
{
}

function configuredDebugToken(): string
{
    $candidate = trim((string) (getenv('EASYBOOKING_DEBUG_TOKEN') ?: ''));
    if ($candidate === '') {
        $candidate = trim((string) ($_SERVER['EASYBOOKING_DEBUG_TOKEN'] ?? ''));
    }
    return $candidate;
}

function requestDebugToken(): string
{
    foreach ([
        (string) ($_GET['debug_token'] ?? ''),
        (string) ($_SERVER['HTTP_X_EASYBOOKING_DEBUG_TOKEN'] ?? ''),
    ] as $candidate) {
        $candidate = trim($candidate);
        if ($candidate !== '') {
            return $candidate;
        }
    }

    return '';
}

function canShowDebugDetails(): bool
{
    $configured = configuredDebugToken();
    $provided = requestDebugToken();
    if ($configured === '' || $provided === '') {
        return false;
    }

    return hash_equals($configured, $provided);
}

function currentConfiguredWebappPathValue(): string
{
    foreach ([
        defined('EASYBOOKING_WEBAPP_PATH') ? (string) EASYBOOKING_WEBAPP_PATH : '',
        (string) (getenv('EASYBOOKING_WEBAPP_PATH') ?: ''),
        (string) ($_SERVER['EASYBOOKING_WEBAPP_PATH'] ?? ''),
    ] as $candidate) {
        $candidate = trim($candidate);
        if ($candidate !== '') {
            return $candidate;
        }
    }

    return '';
}

function formatDebugDetails(Throwable $e): string
{
    return sprintf(
        "%s\n%s:%d",
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    );
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
        'debug_details' => '',
    ], $state);

    $page_title = $state['page_title'];
    $cliente_nome = $state['cliente_nome'];
    $files = $state['files'];
    $file_count = $state['file_count'];
    $total_size_human = $state['total_size_human'];
    $hash = $state['hash'];
    $error_title = $state['error_title'];
    $error_message = $state['error_message'];
    $debug_details = $state['debug_details'];

    require __DIR__ . '/cloud-cliente-template.php';
    exit;
}

function resolveWebappBootstrap(): array
{
    loadPublicCloudLocalConfig(__DIR__ . '/.env');

    $configuredPathSources = [];
    foreach ([
        ['value' => defined('EASYBOOKING_WEBAPP_PATH') && is_string(EASYBOOKING_WEBAPP_PATH) ? EASYBOOKING_WEBAPP_PATH : '', 'source' => 'EASYBOOKING_WEBAPP_PATH constant'],
        ['value' => (string) (getenv('EASYBOOKING_WEBAPP_PATH') ?: ''), 'source' => 'getenv(EASYBOOKING_WEBAPP_PATH)'],
        ['value' => (string) ($_SERVER['EASYBOOKING_WEBAPP_PATH'] ?? ''), 'source' => '$_SERVER[EASYBOOKING_WEBAPP_PATH]'],
    ] as $configuredSource) {
        $value = trim((string) $configuredSource['value']);
        if ($value !== '') {
            $configuredPathSources[] = ['source' => $configuredSource['source'], 'value' => $value];
        }
    }

    $candidates = [];
    foreach ($configuredPathSources as $configuredPathSource) {
        $candidates[] = [
            'path' => $configuredPathSource['value'],
            'source' => $configuredPathSource['source'],
        ];
    }

    foreach ([
        ['path' => dirname(__DIR__), 'source' => 'webapp/public_html sibling'],
        ['path' => dirname(__DIR__) . '/webapp', 'source' => 'parent/webapp fallback'],
        ['path' => dirname(dirname(__DIR__)) . '/webapp', 'source' => 'grandparent/webapp fallback'],
        ['path' => dirname(__DIR__) . '/easybooking/webapp', 'source' => 'hostinger easybooking/webapp fallback'],
    ] as $fallbackCandidate) {
        $candidates[] = $fallbackCandidate;
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

        if ($candidate['source'] !== 'webapp/public_html sibling') {
            error_log(sprintf(
                'Public cloud bootstrap using webapp path "%s" (source: %s)',
                $normalizedPath,
                $candidate['source']
            ));
        }

        return [
            'webapp_path' => $normalizedPath,
            'config_path' => $configPath,
            'cloud_functions_path' => $cloudFunctionsPath,
        ];
    }

    throw new PublicCloudBootstrapException(
        'Unable to resolve the EasyBooking webapp path. Set EASYBOOKING_WEBAPP_PATH in public_html/.env. Paths tried: ' .
        implode(', ', array_unique($pathsTried)),
        array_values(array_unique($pathsTried)),
        $configuredPathSources
    );
}

function sendCloudFile(string $filePath, string $mimeType, string $downloadName = ''): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: ' . $mimeType);
    header('X-Content-Type-Options: nosniff');

    if ($downloadName !== '') {
        // Keep the fallback ASCII name conservative for legacy user agents while
        // sending the full UTF-8 filename via filename*= for modern browsers.
        $fallbackName = preg_replace('/[^A-Za-z0-9._-]/u', '_', $downloadName) ?: 'download';
        header('Content-Length: ' . filesize($filePath));
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

    header('Content-Length: ' . filesize($filePath));
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

    try {
        $pdo = Database::getInstance();
    } catch (Throwable $dbError) {
        throw new PublicCloudDatabaseException(
            'Database connection failed after bootstrap resolution.',
            0,
            $dbError
        );
    }

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
} catch (PublicCloudBootstrapException $e) {
    $configuredPath = currentConfiguredWebappPathValue();
    $pathsTried = $e->getPathsTried();
    $sources = $e->getConfiguredPathSources();
    $sourceParts = [];
    foreach ($sources as $source) {
        $sourceParts[] = $source['source'] . '=' . $source['value'];
    }

    error_log(
        'Public cloud configuration error (bootstrap path not resolved). ' .
        'This is a missing/incorrect deployment configuration, not a transient service outage. ' .
        'Current EASYBOOKING_WEBAPP_PATH=' . ($configuredPath !== '' ? $configuredPath : '[not set]') . '. ' .
        'Configured sources: ' . (!empty($sourceParts) ? implode('; ', $sourceParts) : '[none]') . '. ' .
        'Paths tried: ' . (!empty($pathsTried) ? implode(', ', $pathsTried) : '[none]') . '. ' .
        'Fix: set EASYBOOKING_WEBAPP_PATH to the absolute webapp/ path in public_html/.env ' .
        'or via Apache/vhost SetEnv EASYBOOKING_WEBAPP_PATH "/absolute/path/to/webapp".'
    );
    http_response_code(500);
    renderCloudPage([
        'page_title' => 'Servizio temporaneamente non disponibile',
        'error_title' => 'Servizio temporaneamente non disponibile',
        'error_message' => 'Non è stato possibile aprire lo spazio cloud in questo momento. Riprova più tardi o contatta la scuola.',
        'debug_details' => canShowDebugDetails() ? formatDebugDetails($e) : '',
    ]);
} catch (PublicCloudDatabaseException $e) {
    $technicalError = $e->getPrevious() instanceof Throwable ? $e->getPrevious()->getMessage() : $e->getMessage();
    error_log(
        'Public cloud database connection error. Bootstrap resolved but database connection failed. ' .
        'This is a database connectivity/credentials issue. Details: ' . $technicalError
    );
    http_response_code(500);
    renderCloudPage([
        'page_title' => 'Servizio temporaneamente non disponibile',
        'error_title' => 'Servizio temporaneamente non disponibile',
        'error_message' => 'Non è stato possibile aprire lo spazio cloud in questo momento. Riprova più tardi o contatta la scuola.',
        'debug_details' => canShowDebugDetails() ? formatDebugDetails($e->getPrevious() instanceof Throwable ? $e->getPrevious() : $e) : '',
    ]);
} catch (Throwable $e) {
    error_log('Public cloud controller unexpected error: ' . $e->getMessage());
    http_response_code(500);
    renderCloudPage([
        'page_title' => 'Servizio temporaneamente non disponibile',
        'error_title' => 'Servizio temporaneamente non disponibile',
        'error_message' => 'Non è stato possibile aprire lo spazio cloud in questo momento. Riprova più tardi o contatta la scuola.',
        'debug_details' => canShowDebugDetails() ? formatDebugDetails($e) : '',
    ]);
}

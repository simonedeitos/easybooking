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

const PUBLIC_CLOUD_MIN_DEBUG_TOKEN_LENGTH = 32;
const PUBLIC_CLOUD_DEBUG_LOG_MAX_BYTES = 1048576; // 1 MB size cap for cloud-debug.log

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function loadPublicCloudLocalConfig(string $envFile): void
{
    if (!is_file($envFile)) {
        return;
    }

    $supportedKeys = ['EASYBOOKING_WEBAPP_PATH', 'EASYBOOKING_DEBUG_TOKEN', 'EASYBOOKING_SUBDOMAIN_FOLDER_NAME'];
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
    static $weakTokenWarningLogged = false;
    $configured = configuredDebugToken();
    $tokenLength = function_exists('mb_strlen') ? mb_strlen($configured, '8bit') : strlen($configured);
    if ($configured !== '' && $tokenLength < PUBLIC_CLOUD_MIN_DEBUG_TOKEN_LENGTH) {
        if (!$weakTokenWarningLogged) {
            error_log(
                'Public cloud debug token is configured but too short (<'
                . PUBLIC_CLOUD_MIN_DEBUG_TOKEN_LENGTH
                . ' chars). ' .
                'Debug details are disabled until EASYBOOKING_DEBUG_TOKEN is updated.'
            );
            $weakTokenWarningLogged = true;
        }
        return false;
    }

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

/**
 * Write a structured entry to cloud-debug.log in public_html/.
 *
 * The file is created/appended automatically; no configuration needed.
 * It is protected from direct HTTP access via the .htaccess rule that
 * returns 403 for *.log requests.  Read it via File Manager or FTP.
 *
 * IMPORTANT: this function must never write passwords or other secrets.
 * Only DB_HOST, DB_NAME (not DB_PASS), EASYBOOKING_WEBAPP_PATH, and
 * path-resolution details are included.
 *
 * Note: currentConfiguredWebappPathValue() is called below; it is defined
 * above in this same file.
 */
function writeCloudDebugLog(string $errorType, Throwable $exception, array $pathsTried = [], array $extraContext = []): void
{
    $logFile = __DIR__ . '/cloud-debug.log';

    // Rotate the log if it exceeds the 1 MB cap: keep the second half of the
    // current content so recent entries are always preserved.
    if (@is_file($logFile) && @filesize($logFile) > PUBLIC_CLOUD_DEBUG_LOG_MAX_BYTES) {
        $content = @file_get_contents($logFile);
        if ($content !== false && strlen($content) > PUBLIC_CLOUD_DEBUG_LOG_MAX_BYTES) {
            $trimmed = substr($content, (int) (PUBLIC_CLOUD_DEBUG_LOG_MAX_BYTES / 2));
            // Align to the next line boundary so we don't cut mid-line.
            $nl = strpos($trimmed, "\n");
            if ($nl !== false) {
                $trimmed = substr($trimmed, $nl + 1);
            }
            @file_put_contents($logFile, "=== log rotated at " . date('Y-m-d H:i:s') . " ===\n" . $trimmed, LOCK_EX);
        }
    }

    $entry  = "\n" . str_repeat('=', 60) . "\n";
    $entry .= '[' . date('Y-m-d H:i:s') . '] ERROR TYPE: ' . $errorType . "\n";
    $entry .= 'EXCEPTION CLASS: ' . get_class($exception) . "\n";
    $entry .= 'MESSAGE: ' . $exception->getMessage() . "\n";
    $entry .= 'FILE: ' . $exception->getFile() . ':' . $exception->getLine() . "\n";

    $prev = $exception->getPrevious();
    if ($prev !== null) {
        $entry .= 'CAUSED BY: ' . get_class($prev) . ': ' . $prev->getMessage() . "\n";
    }

    // Paths that were attempted for resolving webapp/
    if (!empty($pathsTried)) {
        $entry .= "PATHS TRIED:\n";
        foreach (array_values($pathsTried) as $i => $path) {
            $status = @is_dir($path) ? '[dir exists - config missing?]' : '[not found]';
            $entry .= '  ' . ($i + 1) . '. ' . $path . ' ' . $status . "\n";
        }
    }

    // EASYBOOKING_WEBAPP_PATH (configured value, even if it didn't resolve)
    $cfgPath = currentConfiguredWebappPathValue();
    $entry .= 'EASYBOOKING_WEBAPP_PATH (configured): ' . ($cfgPath !== '' ? $cfgPath : '[not set]') . "\n";

    // DB connection details - host and name only, NEVER the password
    $dbHost = (string) (getenv('DB_HOST') ?: '');
    $dbName = (string) (getenv('DB_NAME') ?: '');
    if ($dbHost !== '' || $dbName !== '') {
        $entry .= 'DB_HOST: ' . ($dbHost !== '' ? $dbHost : '[not set]') . "\n";
        $entry .= 'DB_NAME: ' . ($dbName !== '' ? $dbName : '[not set]') . "\n";
        // Explicitly note that DB_PASS is intentionally omitted
        $entry .= "DB_PASS: [intentionally omitted from log]\n";
    }

    // Any extra diagnostic context provided by the caller
    if (!empty($extraContext)) {
        $entry .= "ADDITIONAL CONTEXT:\n";
        foreach ($extraContext as $key => $value) {
            $entry .= '  ' . $key . ': ' . $value . "\n";
        }
    }

    $entry .= "HOW TO FIX: open this file (cloud-debug.log) via File Manager or FTP\n";
    $entry .= "  in the public_html/ folder of your hosting account to read this log.\n";
    $entry .= "  If EASYBOOKING_WEBAPP_PATH is [not set], create public_html/.env and add:\n";
    $entry .= "  EASYBOOKING_WEBAPP_PATH=/absolute/path/to/your/webapp\n";

    @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
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
        'page_title'          => 'Cloud Page',
        'app_name'            => 'EasyBooking',
        'cliente_nome'        => '',
        'files'               => [],
        'file_count'          => 0,
        'total_size_human'    => '0 B',
        'hash'                => '',
        'lezioni_future'      => [],
        'scadenza_pacchetto'  => null,
        'pacchetto_nome_attivo' => '',
        'data_acquisto_pacchetto' => null,
        'pacchetto_da_saldare' => false,
        'error_title'         => '',
        'error_message'       => '',
        'debug_details'       => '',
    ], $state);

    $page_title          = $state['page_title'];
    $app_name            = $state['app_name'];
    $cliente_nome        = $state['cliente_nome'];
    $files               = $state['files'];
    $file_count          = $state['file_count'];
    $total_size_human    = $state['total_size_human'];
    $hash                = $state['hash'];
    $lezioni_future      = $state['lezioni_future'];
    $scadenza_pacchetto  = $state['scadenza_pacchetto'];
    $pacchetto_nome_attivo = $state['pacchetto_nome_attivo'];
    $data_acquisto_pacchetto = $state['data_acquisto_pacchetto'];
    $pacchetto_da_saldare = (bool)$state['pacchetto_da_saldare'];
    $error_title         = $state['error_title'];
    $error_message       = $state['error_message'];
    $debug_details       = $state['debug_details'];

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

    $builtInFallbacks = [
        ['path' => dirname(__DIR__), 'source' => 'webapp/public_html sibling'],
        ['path' => dirname(__DIR__) . '/webapp', 'source' => 'parent/webapp fallback'],
        ['path' => dirname(dirname(__DIR__)) . '/webapp', 'source' => 'grandparent/webapp fallback'],
        ['path' => dirname(__DIR__) . '/easybooking/webapp', 'source' => 'hostinger easybooking/webapp fallback'],
    ];

    // Hostinger / shared-hosting: if EASYBOOKING_SUBDOMAIN_FOLDER_NAME is set
    // (e.g. "gest.vocefutura.it"), try the sibling-directory pattern:
    //   /home/user/domains/gest.vocefutura.it/webapp
    // which is typical when main domain and app subdomain share the same hosting
    // account but have separate document-root folders placed side by side.
    $subdomainFolderName = trim((string) (getenv('EASYBOOKING_SUBDOMAIN_FOLDER_NAME') ?: ''));
    // Validate: allow only alphanumeric characters, dots, hyphens, and underscores
    // to prevent any path traversal attempt.
    if ($subdomainFolderName !== '' && preg_match('/^[A-Za-z0-9._-]+$/', $subdomainFolderName)) {
        $builtInFallbacks[] = [
            'path' => dirname(dirname(__DIR__)) . '/' . $subdomainFolderName . '/webapp',
            'source' => 'EASYBOOKING_SUBDOMAIN_FOLDER_NAME sibling fallback',
        ];
        $builtInFallbacks[] = [
            'path' => dirname(dirname(__DIR__)) . '/' . $subdomainFolderName,
            'source' => 'EASYBOOKING_SUBDOMAIN_FOLDER_NAME sibling (no /webapp) fallback',
        ];
    }

    foreach ($builtInFallbacks as $fallbackCandidate) {
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

    $appName    = cloudAppName($pdo);
    $lezioniData = cloudLezioniFuture($pdo, $clienteId);

    renderCloudPage([
        'page_title'           => $appName . ' – ' . $clienteNome,
        'app_name'             => $appName,
        'cliente_nome'         => $clienteNome,
        'files'                => $files,
        'file_count'           => count($files),
        'total_size_human'     => cloudFormatSize($totalSize),
        'hash'                 => $hash,
        'lezioni_future'       => $lezioniData['lezioni'],
        'scadenza_pacchetto'   => $lezioniData['scadenza_pacchetto'],
        'pacchetto_nome_attivo' => $lezioniData['pacchetto_nome'],
        'data_acquisto_pacchetto' => $lezioniData['data_acquisto_pacchetto'],
        'pacchetto_da_saldare' => $lezioniData['pacchetto_da_saldare'],
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

    // Always write to cloud-debug.log — readable via File Manager/FTP without any token config
    writeCloudDebugLog(
        'BOOTSTRAP_PATH_NOT_RESOLVED',
        $e,
        $pathsTried,
        [
            'EASYBOOKING_WEBAPP_PATH (configured)' => $configuredPath !== '' ? $configuredPath : '[not set]',
            'Configured sources' => !empty($sourceParts) ? implode('; ', $sourceParts) : '[none]',
            'Action needed' => 'Set EASYBOOKING_WEBAPP_PATH in public_html/.env',
        ]
    );

    http_response_code(500);
    renderCloudPage([
        'page_title' => 'Servizio temporaneamente non disponibile',
        'error_title' => 'Servizio temporaneamente non disponibile',
        'error_message' => 'Non è stato possibile aprire lo spazio cloud in questo momento. Riprova più tardi o contatta la scuola.',
        'debug_details' => canShowDebugDetails() ? formatDebugDetails($e) : '',
    ]);
} catch (PublicCloudDatabaseException $e) {
    $previous = $e->getPrevious();
    $errorType = $previous instanceof Throwable ? get_class($previous) : get_class($e);
    $errorCode = $previous instanceof Throwable ? (string) $previous->getCode() : (string) $e->getCode();
    $debugException = $previous instanceof Throwable ? $previous : $e;
    error_log(
        'Public cloud database connection error. Bootstrap resolved but database connection failed. ' .
        'This is a database connectivity/credentials issue. Error type: ' . $errorType . ', code: ' . $errorCode . '.'
    );

    // Always write to cloud-debug.log — readable via File Manager/FTP without any token config
    writeCloudDebugLog(
        'DATABASE_CONNECTION_FAILED',
        $e,
        [],
        [
            'Error type' => $errorType,
            'Error code' => $errorCode,
            'Hint' => 'Check DB_HOST and DB_NAME in webapp/.env (DB_PASS intentionally not logged)',
        ]
    );

    http_response_code(500);
    renderCloudPage([
        'page_title' => 'Servizio temporaneamente non disponibile',
        'error_title' => 'Servizio temporaneamente non disponibile',
        'error_message' => 'Non è stato possibile aprire lo spazio cloud in questo momento. Riprova più tardi o contatta la scuola.',
        'debug_details' => canShowDebugDetails() ? formatDebugDetails($debugException) : '',
    ]);
} catch (Throwable $e) {
    error_log('Public cloud controller unexpected error: ' . $e->getMessage());

    // Always write to cloud-debug.log — readable via File Manager/FTP without any token config
    writeCloudDebugLog('UNEXPECTED_ERROR', $e);

    http_response_code(500);
    renderCloudPage([
        'page_title' => 'Servizio temporaneamente non disponibile',
        'error_title' => 'Servizio temporaneamente non disponibile',
        'error_message' => 'Non è stato possibile aprire lo spazio cloud in questo momento. Riprova più tardi o contatta la scuola.',
        'debug_details' => canShowDebugDetails() ? formatDebugDetails($e) : '',
    ]);
}

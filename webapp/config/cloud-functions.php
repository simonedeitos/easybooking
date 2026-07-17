<?php
// config/cloud-functions.php – utility functions for cloud storage management

// ── Cloud Storage Path ────────────────────────────────────────────────────
// The cloud storage folder lives OUTSIDE the public web root.
// Override via CLOUD_STORAGE_PATH constant or environment variable.
if (!defined('CLOUD_STORAGE_PATH')) {
    $envPath = getenv('CLOUD_STORAGE_PATH');
    define('CLOUD_STORAGE_PATH', $envPath !== false ? rtrim($envPath, '/') : dirname(__DIR__, 3) . '/cloud_storage');
}

// ── Public base URL ───────────────────────────────────────────────────────
// Optionally override the auto-detected base URL used in cloudShareUrl().
// Set CLOUD_PUBLIC_BASE_URL in .env when behind a reverse proxy or when the
// auto-detection produces wrong results (e.g. https://yourdomain.com or
// https://yourdomain.com/easybooking).
if (!defined('CLOUD_PUBLIC_BASE_URL')) {
    $envUrl = getenv('CLOUD_PUBLIC_BASE_URL');
    if (($envUrl === false || $envUrl === '') && isset($_SERVER['CLOUD_PUBLIC_BASE_URL'])) {
        $envUrl = (string) $_SERVER['CLOUD_PUBLIC_BASE_URL'];
    }
    if ($envUrl !== false && $envUrl !== '') {
        define('CLOUD_PUBLIC_BASE_URL', rtrim($envUrl, '/'));
    }
    unset($envUrl);
}

// Max global storage: 150 GB in bytes
define('CLOUD_MAX_BYTES', 150 * 1024 * 1024 * 1024);
// Max single-file size: 500 MB in bytes
define('CLOUD_MAX_FILE_BYTES', 500 * 1024 * 1024);

// Allowed MIME types
define('CLOUD_ALLOWED_MIMES', [
    // Audio
    'audio/mpeg', 'audio/wav', 'audio/x-wav', 'audio/mp4', 'audio/aac',
    'audio/flac', 'audio/x-flac', 'audio/ogg', 'audio/webm',
    // Documents
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'text/plain', 'text/csv',
    // Images
    'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
    // Archives
    'application/zip', 'application/x-zip-compressed',
    'application/x-rar-compressed', 'application/x-7z-compressed',
    // Video
    'video/mp4', 'video/webm', 'video/ogg', 'video/quicktime',
]);

// Audio MIME types that support in-browser streaming
define('CLOUD_AUDIO_MIMES', [
    'audio/mpeg', 'audio/wav', 'audio/x-wav', 'audio/mp4', 'audio/aac',
    'audio/flac', 'audio/x-flac', 'audio/ogg', 'audio/webm',
]);

// Italian month abbreviations (1-indexed; index 0 is unused)
define('CLOUD_MESI_IT', ['', 'gen', 'feb', 'mar', 'apr', 'mag', 'giu',
                          'lug', 'ago', 'set', 'ott', 'nov', 'dic']);

// ── Path helpers ──────────────────────────────────────────────────────────

/**
 * Returns the absolute path to a client's cloud folder.
 */
function cloudClientDir(string $cartella): string
{
    return CLOUD_STORAGE_PATH . '/' . $cartella;
}

/**
 * Returns the absolute path to a specific file inside a client's folder.
 * The $nomeFile must NOT contain directory separators.
 */
function cloudFilePath(string $cartella, string $nomeFile): string
{
    return cloudClientDir($cartella) . '/' . basename($nomeFile);
}

// ── Enable / Disable ──────────────────────────────────────────────────────

/**
 * Enables cloud storage for a client:
 *  - Generates a random public hash
 *  - Creates the local storage folder
 *  - Updates the database record
 * Returns ['success' => bool, 'message' => string, 'hash' => string|null].
 */
function cloudEnableForClient(PDO $pdo, int $clienteId): array
{
    // Generate unique hash
    do {
        $hash = bin2hex(random_bytes(16));
        $stmt = $pdo->prepare('SELECT id FROM clienti WHERE cloud_hash = ? LIMIT 1');
        $stmt->execute([$hash]);
    } while ($stmt->fetchColumn() !== false);

    // Folder name = client ID + hash prefix for uniqueness
    $cartella = 'cliente_' . $clienteId . '_' . substr($hash, 0, 8);
    $dir = cloudClientDir($cartella);

    if (!is_dir($dir) && !mkdir($dir, 0700, true)) {
        return ['success' => false, 'message' => 'Impossibile creare la cartella cloud.', 'hash' => null];
    }

    $stmt = $pdo->prepare(
        'UPDATE clienti SET cloud_enabled = 1, cloud_hash = ?, cloud_cartella = ?, updated_at = NOW()
         WHERE id = ?'
    );
    $stmt->execute([$hash, $cartella, $clienteId]);

    cloudUpdateStats($pdo);

    return ['success' => true, 'message' => 'Cloud abilitato.', 'hash' => $hash];
}

/**
 * Disables cloud storage for a client:
 *  - Deletes all local files and the folder
 *  - Removes file records from DB
 *  - Resets cloud columns
 */
function cloudDisableForClient(PDO $pdo, int $clienteId): array
{
    $stmt = $pdo->prepare('SELECT cloud_cartella FROM clienti WHERE id = ? LIMIT 1');
    $stmt->execute([$clienteId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && !empty($row['cloud_cartella'])) {
        $dir = cloudClientDir($row['cloud_cartella']);
        cloudDeleteDir($dir);
    }

    $pdo->prepare('DELETE FROM cloud_files WHERE cliente_id = ?')->execute([$clienteId]);
    $pdo->prepare(
        'UPDATE clienti SET cloud_enabled = 0, cloud_hash = NULL, cloud_cartella = NULL, updated_at = NOW()
         WHERE id = ?'
    )->execute([$clienteId]);

    cloudUpdateStats($pdo);

    return ['success' => true, 'message' => 'Cloud disabilitato e file eliminati.'];
}

// ── File Validation ───────────────────────────────────────────────────────

/**
 * Validates an uploaded file array (from $_FILES).
 * Returns null on success or an error string.
 */
function cloudValidateUpload(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return 'Errore durante il caricamento del file (codice ' . ($file['error'] ?? '?') . ').';
    }
    if (($file['size'] ?? 0) > CLOUD_MAX_FILE_BYTES) {
        return 'Il file supera la dimensione massima consentita (500 MB).';
    }
    $mime = cloudDetectMime($file['tmp_name'] ?? '');
    if ($mime === null || !in_array($mime, CLOUD_ALLOWED_MIMES, true)) {
        return 'Tipo di file non consentito.';
    }
    return null;
}

/**
 * Detects the real MIME type of a file using finfo.
 */
function cloudDetectMime(string $path): ?string
{
    if (!is_file($path)) {
        return null;
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($path);
    return $mime !== false ? $mime : null;
}

/**
 * Generates a safe, unique filename for storage (no path traversal).
 */
function cloudSafeFilename(string $originalName): string
{
    $ext  = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $uuid = bin2hex(random_bytes(8));
    // Allow only alphanumeric extensions
    $ext  = preg_replace('/[^a-z0-9]/', '', $ext);
    return $uuid . ($ext !== '' ? '.' . $ext : '');
}

// ── Space Calculation ─────────────────────────────────────────────────────

/**
 * Returns total bytes used across all client cloud folders.
 */
function cloudCalcolaSpazioTotale(PDO $pdo): int
{
    $stmt = $pdo->query('SELECT COALESCE(SUM(dimensione_bytes), 0) FROM cloud_files');
    return (int)$stmt->fetchColumn();
}

/**
 * Returns bytes used by a single client.
 */
function cloudSpazioCliente(PDO $pdo, int $clienteId): int
{
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(dimensione_bytes), 0) FROM cloud_files WHERE cliente_id = ?');
    $stmt->execute([$clienteId]);
    return (int)$stmt->fetchColumn();
}

/**
 * Refreshes the cloud_stats table with current totals.
 */
function cloudUpdateStats(PDO $pdo): void
{
    $pdo->exec(
        'INSERT INTO cloud_stats (id, spazio_totale_bytes, numero_file, numero_clienti, aggiornato_at)
         SELECT 1,
                COALESCE((SELECT SUM(dimensione_bytes) FROM cloud_files), 0),
                COALESCE((SELECT COUNT(*) FROM cloud_files), 0),
                COALESCE((SELECT COUNT(*) FROM clienti WHERE cloud_enabled = 1), 0),
                NOW()
         ON DUPLICATE KEY UPDATE
                spazio_totale_bytes = VALUES(spazio_totale_bytes),
                numero_file         = VALUES(numero_file),
                numero_clienti      = VALUES(numero_clienti),
                aggiornato_at       = NOW()'
    );
}

// ── Format helpers ────────────────────────────────────────────────────────

/**
 * Human-readable file size (B, KB, MB, GB).
 */
function cloudFormatSize(int $bytes): string
{
    if ($bytes >= 1024 ** 3) {
        return number_format($bytes / 1024 ** 3, 2) . ' GB';
    }
    if ($bytes >= 1024 ** 2) {
        return number_format($bytes / 1024 ** 2, 2) . ' MB';
    }
    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' B';
}

/**
 * Returns a FontAwesome icon class appropriate for the given MIME type.
 */
function cloudFileIcon(?string $mime): string
{
    if ($mime === null) {
        return 'fa-file-lines';
    }
    if (str_starts_with($mime, 'audio/')) {
        return 'fa-file-audio';
    }
    if (str_starts_with($mime, 'video/')) {
        return 'fa-file-video';
    }
    if (str_starts_with($mime, 'image/')) {
        return 'fa-file-image';
    }
    return match (true) {
        $mime === 'application/pdf'                                                           => 'fa-file-pdf',
        in_array($mime, ['application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'], true) => 'fa-file-word',
        in_array($mime, ['application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'], true)       => 'fa-file-excel',
        in_array($mime, ['application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation'], true) => 'fa-file-powerpoint',
        in_array($mime, ['application/zip','application/x-zip-compressed',
            'application/x-rar-compressed','application/x-7z-compressed'], true)             => 'fa-file-zipper',
        $mime === 'text/csv'                                                                  => 'fa-file-csv',
        $mime === 'text/plain'                                                                => 'fa-file-lines',
        default                                                                                => 'fa-file-lines',
    };
}

// ── App name helper ───────────────────────────────────────────────────────

/**
 * Returns the application name from system_config, falling back to 'EasyBooking'.
 * Usable on public pages that only load cloud-functions.php (not functions.php).
 */
function cloudAppName(PDO $pdo): string
{
    try {
        $stmt = $pdo->prepare("SELECT `value` FROM system_config WHERE `key` = 'app_name' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($row && $row['value'] !== '') ? (string)$row['value'] : 'EasyBooking';
    } catch (Throwable) {
        return 'EasyBooking';
    }
}

// ── Future lessons helper ─────────────────────────────────────────────────

/**
 * Returns future scheduled lessons for a client together with the active
 * package expiry date (= date of the last scheduled lesson linked to the
 * most recent active purchase).
 *
 * @return array{lezioni: list<array<string,string>>, scadenza_pacchetto: string|null, pacchetto_nome: string, data_acquisto_pacchetto: string|null, pacchetto_da_saldare: bool}
 */
function cloudLezioniFuture(PDO $pdo, int $clienteId): array
{
    // Future lessons with stato = 'Programmata'
    $stmt = $pdo->prepare(
        'SELECT data, ora_inizio, ora_fine, pacchetto_nome, strumento
         FROM prenotazioni
         WHERE cliente_id = ? AND data >= CURDATE() AND stato = ?
         ORDER BY data ASC, ora_inizio ASC'
    );
    $stmt->execute([$clienteId, 'Programmata']);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $lezioni = [];
    foreach ($rows as $r) {
        $ts = strtotime((string)$r['data']);
        $lezioni[] = [
            'data'         => (string)$r['data'],
            'data_human'   => date('d', $ts) . ' ' . CLOUD_MESI_IT[(int)date('n', $ts)] . ' ' . date('Y', $ts),
            'giorno'       => date('d', $ts),
            'mese'         => strtoupper(CLOUD_MESI_IT[(int)date('n', $ts)]),
            'ora_inizio'   => substr((string)$r['ora_inizio'], 0, 5),
            'ora_fine'     => substr((string)$r['ora_fine'], 0, 5),
            'pacchetto'    => (string)($r['pacchetto_nome'] ?? ''),
            'strumento'    => (string)($r['strumento'] ?? ''),
        ];
    }

    // Find most recent active purchase (numero_lezioni > 0)
    $stmt = $pdo->prepare(
        'SELECT a.id, a.data_acquisto, a.stato_pagamento, p.nome AS pacchetto_nome
         FROM acquisti a
         LEFT JOIN pacchetti p ON p.id = a.pacchetto_id
         WHERE a.cliente_id = ? AND a.numero_lezioni > 0
         ORDER BY a.data_acquisto DESC
         LIMIT 1'
    );
    $stmt->execute([$clienteId]);
    $acquisto = $stmt->fetch(PDO::FETCH_ASSOC);

    $scadenzaPacchetto = null;
    $pacchettoNome     = '';
    $dataAcquistoPacchetto = null;
    $pacchettoDaSaldare = false;

    if ($acquisto) {
        $pacchettoNome = (string)($acquisto['pacchetto_nome'] ?? '');
        $statoPagamento = trim((string)($acquisto['stato_pagamento'] ?? ''));
        $pacchettoDaSaldare = in_array($statoPagamento, ['Non Pagato', 'Parziale'], true);
        if (!empty($acquisto['data_acquisto'])) {
            $ts = strtotime((string)$acquisto['data_acquisto']);
            if ($ts !== false) {
                $dataAcquistoPacchetto = date('d', $ts) . ' ' . CLOUD_MESI_IT[(int)date('n', $ts)] . ' ' . date('Y', $ts);
            }
        }
        // Scadenza = MAX(data) of Programmata lessons linked to this purchase
        $stmt = $pdo->prepare(
            'SELECT MAX(data) FROM prenotazioni WHERE acquisto_id = ? AND stato = ?'
        );
        $stmt->execute([(int)$acquisto['id'], 'Programmata']);
        $maxData = $stmt->fetchColumn();
        if ($maxData) {
            $ts = strtotime((string)$maxData);
            $scadenzaPacchetto = date('d', $ts) . ' ' . CLOUD_MESI_IT[(int)date('n', $ts)] . ' ' . date('Y', $ts);
        }
    }

    return [
        'lezioni'            => $lezioni,
        'scadenza_pacchetto' => $scadenzaPacchetto,
        'pacchetto_nome'     => $pacchettoNome,
        'data_acquisto_pacchetto' => $dataAcquistoPacchetto,
        'pacchetto_da_saldare' => $pacchettoDaSaldare,
    ];
}

// ── Public share URL ──────────────────────────────────────────────────────

/**
 * Builds the public base URL used by share links.
 */
function cloudPublicBaseUrl(): string
{
    if (defined('CLOUD_PUBLIC_BASE_URL')) {
        return rtrim(CLOUD_PUBLIC_BASE_URL, '/');
    }

    static $fallbackWarningLogged = false;
    if (!$fallbackWarningLogged) {
        error_log(
            'CLOUD_PUBLIC_BASE_URL is not configured explicitly. Falling back to current HTTP host auto-detection. ' .
            'In split deployments (admin app on subdomain and public /share links on main domain), configure CLOUD_PUBLIC_BASE_URL in webapp/.env.'
        );
        $fallbackWarningLogged = true;
    }

    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = 'localhost';
    foreach ([$_SERVER['SERVER_NAME'] ?? '', $_SERVER['HTTP_HOST'] ?? ''] as $candidateHost) {
        $candidateHost = trim((string)$candidateHost);
        if ($candidateHost !== '' && preg_match('/^[a-z0-9.-]+(?::\d+)?$/i', $candidateHost)) {
            $host = $candidateHost;
            break;
        }
    }
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
    if (!preg_match('#^(/[a-zA-Z0-9_/.-]*)?$#', $base)) {
        $base = '';
    }
    return $proto . '://' . $host . $base;
}

function cloudHasExplicitPublicBaseUrlConfig(): bool
{
    return defined('CLOUD_PUBLIC_BASE_URL') && trim((string) CLOUD_PUBLIC_BASE_URL) !== '';
}

/**
 * Builds the public share URL for a client cloud hash.
 * Uses the CLOUD_PUBLIC_BASE_URL constant if defined, otherwise falls back
 * to the current host.
 */
function cloudShareUrl(string $hash): string
{
    return cloudPublicBaseUrl() . '/share/' . urlencode($hash);
}

// ── Filesystem helpers ────────────────────────────────────────────────────

/**
 * Recursively deletes a directory and all its contents.
 */
function cloudDeleteDir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $items = array_diff(scandir($dir) ?: [], ['.', '..']);
    foreach ($items as $item) {
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            cloudDeleteDir($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}

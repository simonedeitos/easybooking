<?php
// api/cloud-api.php – RESTful API for cloud storage operations

declare(strict_types=1);

ob_start();
ini_set('log_errors', '1');
ini_set('display_errors', '0');
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/encryption.php';
require_once __DIR__ . '/../config/cloud-functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAuth();

$pdo    = Database::getInstance();
$user   = currentUser();
$action = post('action') !== '' ? post('action') : get('action');

/**
 * Sends a JSON response and terminates.
 * @param array<string,mixed> $data
 */
function apiResponse(array $data, int $status = 200): never
{
    ob_end_clean();
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Aborts with an error JSON response.
 */
function apiError(string $message, int $status = 400): never
{
    apiResponse(['success' => false, 'message' => $message], $status);
}

/**
 * Retrieves a client row by ID, ensuring cloud_enabled = 1.
 * @return array<string,mixed>
 */
function getCloudClient(PDO $pdo, int $clienteId, bool $requireEnabled = true): array
{
    $stmt = $pdo->prepare('SELECT * FROM clienti WHERE id = ? LIMIT 1');
    $stmt->execute([$clienteId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        apiError('Cliente non trovato.', 404);
    }
    if ($requireEnabled && !$row['cloud_enabled']) {
        apiError('Cloud non abilitato per questo cliente.', 403);
    }
    return $row;
}

// ── Route dispatcher ──────────────────────────────────────────────────────
switch ($action) {

    // ── Enable cloud for a client ─────────────────────────────────────
    case 'enable_cloud':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            apiError('Metodo non consentito.', 405);
        }
        verifyCsrf();
        $clienteId = sanitizeInt(post('cliente_id'));
        if ($clienteId <= 0) {
            apiError('ID cliente non valido.');
        }
        // Verify client exists
        $stmt = $pdo->prepare('SELECT id, cloud_enabled FROM clienti WHERE id = ? LIMIT 1');
        $stmt->execute([$clienteId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            apiError('Cliente non trovato.', 404);
        }
        if ($row['cloud_enabled']) {
            apiError('Cloud già abilitato per questo cliente.');
        }
        $result = cloudEnableForClient($pdo, $clienteId);
        if (!$result['success']) {
            apiError($result['message'], 500);
        }
        apiResponse(['success' => true, 'message' => $result['message'], 'hash' => $result['hash']]);

    // ── Disable cloud for a client ────────────────────────────────────
    case 'disable_cloud':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            apiError('Metodo non consentito.', 405);
        }
        verifyCsrf();
        $clienteId = sanitizeInt(post('cliente_id'));
        if ($clienteId <= 0) {
            apiError('ID cliente non valido.');
        }
        $result = cloudDisableForClient($pdo, $clienteId);
        apiResponse(['success' => $result['success'], 'message' => $result['message']]);

    // ── Upload one or more files ──────────────────────────────────────
    case 'upload_file':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            apiError('Metodo non consentito.', 405);
        }
        verifyCsrf();
        $clienteId = sanitizeInt(post('cliente_id'));
        if ($clienteId <= 0) {
            apiError('ID cliente non valido.');
        }
        $cliente = getCloudClient($pdo, $clienteId);

        // Check global quota before upload
        $stmt  = $pdo->query('SELECT spazio_totale_bytes FROM cloud_stats WHERE id = 1 LIMIT 1');
        $usato = (int)($stmt->fetchColumn() ?: 0);
        if ($usato >= CLOUD_MAX_BYTES) {
            apiError('Spazio cloud esaurito. Limite globale di 150 GB raggiunto.', 507);
        }

        if (empty($_FILES['files'])) {
            apiError('Nessun file ricevuto.');
        }

        // Normalise single-file uploads to the multi-file structure
        $files = $_FILES['files'];
        if (!is_array($files['name'])) {
            $files = [
                'name'     => [$files['name']],
                'type'     => [$files['type']],
                'tmp_name' => [$files['tmp_name']],
                'error'    => [$files['error']],
                'size'     => [$files['size']],
            ];
        }

        $cartella = $cliente['cloud_cartella_locale'];
        $dir      = cloudClientDir($cartella);
        $uploaded = [];
        $errors   = [];

        foreach ($files['name'] as $i => $originalName) {
            $file = [
                'name'     => $originalName,
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];

            $validationError = cloudValidateUpload($file);
            if ($validationError !== null) {
                $errors[] = htmlspecialchars($originalName) . ': ' . $validationError;
                continue;
            }

            $mime     = cloudDetectMime($file['tmp_name']);
            $safeName = cloudSafeFilename($originalName);
            $dest     = $dir . '/' . $safeName;

            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                $errors[] = htmlspecialchars($originalName) . ': errore durante il salvataggio.';
                continue;
            }

            $size = (int)filesize($dest);
            $stmt = $pdo->prepare(
                'INSERT INTO cloud_file (cliente_id, nome_originale, nome_file, dimensione_bytes, mime_type, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, NOW(), NOW())'
            );
            $stmt->execute([$clienteId, basename($originalName), $safeName, $size, $mime]);
            $uploaded[] = ['id' => (int)$pdo->lastInsertId(), 'nome' => $originalName, 'size' => $size];
        }

        cloudUpdateStats($pdo);

        apiResponse([
            'success'  => count($uploaded) > 0,
            'uploaded' => $uploaded,
            'errors'   => $errors,
            'message'  => count($uploaded) . ' file caricati' . (count($errors) > 0 ? ', ' . count($errors) . ' errori.' : '.'),
        ]);

    // ── List files of a client ────────────────────────────────────────
    case 'list_files':
        $clienteId = sanitizeInt(get('cliente_id') ?: post('cliente_id'));
        if ($clienteId <= 0) {
            apiError('ID cliente non valido.');
        }
        $cliente = getCloudClient($pdo, $clienteId);

        $stmt = $pdo->prepare(
            'SELECT id, nome_originale, nome_file, dimensione_bytes, mime_type, nota, created_at
             FROM cloud_file WHERE cliente_id = ? ORDER BY created_at DESC'
        );
        $stmt->execute([$clienteId]);
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($files as &$f) {
            $f['dimensione_human'] = cloudFormatSize((int)$f['dimensione_bytes']);
            $f['icon']             = cloudFileIcon($f['mime_type']);
            $f['is_audio']         = in_array($f['mime_type'], CLOUD_AUDIO_MIMES, true);
        }
        unset($f);

        apiResponse(['success' => true, 'files' => $files]);

    // ── Download a file ───────────────────────────────────────────────
    case 'download_file':
        $fileId = sanitizeInt(get('file_id') ?: post('file_id'));
        if ($fileId <= 0) {
            apiError('ID file non valido.');
        }
        $stmt = $pdo->prepare(
            'SELECT cf.*, c.cloud_cartella_locale, c.cloud_enabled
             FROM cloud_file cf
             JOIN clienti c ON c.id = cf.cliente_id
             WHERE cf.id = ? LIMIT 1'
        );
        $stmt->execute([$fileId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || !$row['cloud_enabled']) {
            apiError('File non trovato.', 404);
        }

        $path = cloudFilePath($row['cloud_cartella_locale'], $row['nome_file']);
        if (!is_file($path)) {
            apiError('File non presente sul disco.', 404);
        }

        ob_end_clean();
        $safeName = preg_replace('/[\r\n"\\\\]/', '_', $row['nome_originale']);
        header('Content-Type: ' . ($row['mime_type'] ?? 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . $safeName . '"');
        header('Content-Length: ' . filesize($path));
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;

    // ── Stream file (for audio player) ───────────────────────────────
    case 'get_file':
        $fileId = sanitizeInt(get('file_id') ?: post('file_id'));
        if ($fileId <= 0) {
            apiError('ID file non valido.');
        }
        $stmt = $pdo->prepare(
            'SELECT cf.*, c.cloud_cartella_locale, c.cloud_enabled
             FROM cloud_file cf
             JOIN clienti c ON c.id = cf.cliente_id
             WHERE cf.id = ? LIMIT 1'
        );
        $stmt->execute([$fileId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || !$row['cloud_enabled']) {
            apiError('File non trovato.', 404);
        }

        $path = cloudFilePath($row['cloud_cartella_locale'], $row['nome_file']);
        if (!is_file($path)) {
            apiError('File non presente sul disco.', 404);
        }

        $size = filesize($path);
        $mime = $row['mime_type'] ?? 'application/octet-stream';

        ob_end_clean();
        header('Accept-Ranges: bytes');
        header('Content-Type: ' . $mime);
        header('X-Content-Type-Options: nosniff');

        // Handle range requests for audio streaming
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
                $fp = fopen($path, 'rb');
                fseek($fp, $start);
                echo fread($fp, $end - $start + 1);
                fclose($fp);
                exit;
            }
        }

        header('Content-Length: ' . $size);
        readfile($path);
        exit;

    // ── Delete a file ─────────────────────────────────────────────────
    case 'delete_file':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            apiError('Metodo non consentito.', 405);
        }
        verifyCsrf();
        $fileId = sanitizeInt(post('file_id'));
        if ($fileId <= 0) {
            apiError('ID file non valido.');
        }
        $stmt = $pdo->prepare(
            'SELECT cf.*, c.cloud_cartella_locale, c.cloud_enabled
             FROM cloud_file cf
             JOIN clienti c ON c.id = cf.cliente_id
             WHERE cf.id = ? LIMIT 1'
        );
        $stmt->execute([$fileId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            apiError('File non trovato.', 404);
        }

        $path = cloudFilePath($row['cloud_cartella_locale'], $row['nome_file']);
        if (is_file($path)) {
            @unlink($path);
        }

        $pdo->prepare('DELETE FROM cloud_file WHERE id = ?')->execute([$fileId]);
        cloudUpdateStats($pdo);

        apiResponse(['success' => true, 'message' => 'File eliminato.']);

    // ── Update file note ──────────────────────────────────────────────
    case 'update_file':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            apiError('Metodo non consentito.', 405);
        }
        verifyCsrf();
        $fileId = sanitizeInt(post('file_id'));
        $nota   = trim(post('nota'));
        if ($fileId <= 0) {
            apiError('ID file non valido.');
        }
        $stmt = $pdo->prepare('SELECT id FROM cloud_file WHERE id = ? LIMIT 1');
        $stmt->execute([$fileId]);
        if (!$stmt->fetchColumn()) {
            apiError('File non trovato.', 404);
        }
        $pdo->prepare('UPDATE cloud_file SET nota = ?, updated_at = NOW() WHERE id = ?')
            ->execute([$nota !== '' ? $nota : null, $fileId]);

        apiResponse(['success' => true, 'message' => 'Nota aggiornata.']);

    // ── Get cloud stats ───────────────────────────────────────────────
    case 'get_stats':
        cloudUpdateStats($pdo);
        $stmt = $pdo->query('SELECT * FROM cloud_stats WHERE id = 1 LIMIT 1');
        $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['spazio_totale_bytes' => 0, 'numero_file' => 0, 'numero_clienti' => 0];
        $stats['spazio_human']    = cloudFormatSize((int)$stats['spazio_totale_bytes']);
        $stats['spazio_max']      = CLOUD_MAX_BYTES;
        $stats['spazio_max_human'] = cloudFormatSize(CLOUD_MAX_BYTES);
        $stats['percentuale']     = CLOUD_MAX_BYTES > 0
            ? round((int)$stats['spazio_totale_bytes'] / CLOUD_MAX_BYTES * 100, 1)
            : 0;
        apiResponse(['success' => true, 'stats' => $stats]);

    default:
        apiError('Azione non riconosciuta.', 400);
}

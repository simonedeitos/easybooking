<?php
// ─── CSRF ──────────────────────────────────────────────────────────────────
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        jsonResponse(['success' => false, 'message' => 'Token CSRF non valido.'], 403);
    }
}

function verifyCsrfOrRedirect(string $redirectUrl): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        setFlash('danger', 'Token di sicurezza non valido. Riprova.');
        redirect($redirectUrl);
    }
}

// ─── FLASH MESSAGES ────────────────────────────────────────────────────────
function setFlash(string $type, string $message): void {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function getFlashMessages(): array {
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function renderFlashMessages(): string {
    $html = '';
    foreach (getFlashMessages() as $flash) {
        $type = htmlspecialchars($flash['type']);
        $msg  = htmlspecialchars($flash['message']);
        $icon = match($flash['type']) {
            'success' => 'fa-check-circle',
            'danger'  => 'fa-times-circle',
            'warning' => 'fa-exclamation-triangle',
            default   => 'fa-info-circle',
        };
        $html .= <<<HTML
        <div class="alert alert-{$type} alert-dismissible fade show" role="alert">
            <i class="fas {$icon} me-2"></i>{$msg}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        HTML;
    }
    return $html;
}

// ─── INPUT SANITIZATION ────────────────────────────────────────────────────
function sanitize(mixed $value): string {
    return htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
}

function sanitizeInt(mixed $value): int {
    return (int)filter_var($value, FILTER_SANITIZE_NUMBER_INT);
}

function sanitizeFloat(mixed $value): float {
    return (float)filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
}

function sanitizeEmail(mixed $value): string {
    return filter_var(trim((string)$value), FILTER_SANITIZE_EMAIL);
}

function post(string $key, mixed $default = ''): string {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

function get(string $key, mixed $default = ''): string {
    return isset($_GET[$key]) ? trim($_GET[$key]) : $default;
}

// ─── PAGINATION ────────────────────────────────────────────────────────────
function paginate(int $total, int $perPage, int $currentPage): array {
    $totalPages = max(1, (int)ceil($total / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    return [
        'total'        => $total,
        'per_page'     => $perPage,
        'current_page' => $currentPage,
        'total_pages'  => $totalPages,
        'offset'       => $offset,
        'has_prev'     => $currentPage > 1,
        'has_next'     => $currentPage < $totalPages,
    ];
}

function renderPagination(array $pagination, string $baseUrl = ''): string {
    if ($pagination['total_pages'] <= 1) return '';
    $cp = $pagination['current_page'];
    $tp = $pagination['total_pages'];
    $sep = str_contains($baseUrl, '?') ? '&' : '?';
    $html = '<nav><ul class="pagination justify-content-center flex-wrap">';
    $html .= '<li class="page-item' . ($cp <= 1 ? ' disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . $baseUrl . $sep . 'page=' . max(1, $cp - 1) . '">‹</a></li>';
    $start = max(1, $cp - 2);
    $end   = min($tp, $cp + 2);
    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . $sep . 'page=1">1</a></li>';
        if ($start > 2) $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
    }
    for ($i = $start; $i <= $end; $i++) {
        $html .= '<li class="page-item' . ($i === $cp ? ' active' : '') . '">';
        $html .= '<a class="page-link" href="' . $baseUrl . $sep . 'page=' . $i . '">' . $i . '</a></li>';
    }
    if ($end < $tp) {
        if ($end < $tp - 1) $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . $sep . 'page=' . $tp . '">' . $tp . '</a></li>';
    }
    $html .= '<li class="page-item' . ($cp >= $tp ? ' disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . $baseUrl . $sep . 'page=' . min($tp, $cp + 1) . '">›</a></li>';
    $html .= '</ul></nav>';
    return $html;
}

// ─── DATE HELPERS ──────────────────────────────────────────────────────────
function formatDate(string $date, string $format = 'd/m/Y'): string {
    if (empty($date) || $date === '0000-00-00') return '—';
    try {
        return (new DateTime($date))->format($format);
    } catch (Exception) {
        return $date;
    }
}

function formatDateTime(string $dt, string $format = 'd/m/Y H:i'): string {
    return formatDate($dt, $format);
}

function italianDate(string $date): string {
    if (empty($date)) return '—';
    try {
        $d = new DateTime($date);
        $days   = ['Domenica','Lunedì','Martedì','Mercoledì','Giovedì','Venerdì','Sabato'];
        $months = ['','Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
        return $days[(int)$d->format('w')] . ' ' . $d->format('j') . ' ' . $months[(int)$d->format('n')] . ' ' . $d->format('Y');
    } catch (Exception) {
        return $date;
    }
}

function italianDayName(string $dayKey): string {
    return match($dayKey) {
        'lun' => 'Lunedì', 'mar' => 'Martedì', 'mer' => 'Mercoledì',
        'gio' => 'Giovedì', 'ven' => 'Venerdì', 'sab' => 'Sabato',
        'dom' => 'Domenica', default => $dayKey,
    };
}

function getSmtpConfig(?PDO $pdo = null): array
{
    $defaults = [
        'enabled' => false,
        'host' => '',
        'port' => 587,
        'username' => '',
        'password' => '',
        'encryption' => 'tls',
        'sender_email' => '',
        'sender_name' => 'EasyBooking',
    ];

    try {
        $pdo = $pdo instanceof PDO ? $pdo : Database::getInstance();
        $keys = [
            'smtp_enabled',
            'smtp_host',
            'smtp_port',
            'smtp_username',
            'smtp_password',
            'smtp_encryption',
            'smtp_sender_email',
            'smtp_sender_name',
        ];
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $stmt = $pdo->prepare("SELECT `key`, `value` FROM system_config WHERE `key` IN ($placeholders)");
        $stmt->execute($keys);
        foreach ($stmt->fetchAll() as $row) {
            $key = (string)($row['key'] ?? '');
            $value = trim((string)($row['value'] ?? ''));
            switch ($key) {
                case 'smtp_enabled':
                    $defaults['enabled'] = $value === '1';
                    break;
                case 'smtp_host':
                    $defaults['host'] = $value;
                    break;
                case 'smtp_port':
                    $port = (int)$value;
                    if ($port > 0 && $port <= 65535) {
                        $defaults['port'] = $port;
                    }
                    break;
                case 'smtp_username':
                    $defaults['username'] = $value;
                    break;
                case 'smtp_password':
                    $defaults['password'] = decodeSmtpSecret($value);
                    break;
                case 'smtp_encryption':
                    $defaults['encryption'] = in_array($value, ['', 'tls', 'ssl'], true) ? $value : 'tls';
                    break;
                case 'smtp_sender_email':
                    $defaults['sender_email'] = $value;
                    break;
                case 'smtp_sender_name':
                    $defaults['sender_name'] = $value !== '' ? $value : 'EasyBooking';
                    break;
            }
        }
    } catch (Throwable) {
        return $defaults;
    }

    return $defaults;
}

function encodeSmtpSecret(string $plain): string
{
    if ($plain === '') {
        return '';
    }
    if (!function_exists('encryptField')) {
        error_log('encodeSmtpSecret warning: encryptField non disponibile, password SMTP salvata in chiaro.');
        return $plain;
    }
    $encrypted = encryptField($plain);
    if ($encrypted === '') {
        error_log('encodeSmtpSecret warning: cifratura SMTP non riuscita, password salvata in chiaro.');
        return $plain;
    }
    return 'enc:' . $encrypted;
}

function decodeSmtpSecret(string $stored): string
{
    if ($stored === '') {
        return '';
    }
    if (!str_starts_with($stored, 'enc:')) {
        return $stored;
    }
    $payload = substr($stored, 4);
    if ($payload === '') {
        return '';
    }
    if (!function_exists('decryptField')) {
        return $payload;
    }
    return decryptField($payload);
}

function getStoredSmtpPasswordRaw(?PDO $pdo = null): string
{
    try {
        $pdo = $pdo instanceof PDO ? $pdo : Database::getInstance();
        $stmt = $pdo->prepare("SELECT `value` FROM system_config WHERE `key` = 'smtp_password' LIMIT 1");
        $stmt->execute();
        $value = $stmt->fetchColumn();
        return is_string($value) ? $value : '';
    } catch (Throwable) {
        return '';
    }
}

function ensureNotificationLogsTable(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS `notification_logs` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `notification_type` VARCHAR(50) NOT NULL,
            `recipient_email` VARCHAR(255) NOT NULL,
            `recipient_name` VARCHAR(255) DEFAULT NULL,
            `subject` VARCHAR(255) DEFAULT NULL,
            `sent_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `status` ENUM('success','failed','pending') NOT NULL DEFAULT 'pending',
            `error_message` TEXT DEFAULT NULL,
            `mail_server_used` VARCHAR(100) DEFAULT NULL,
            `retry_count` INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            INDEX `idx_notification_logs_sent_at` (`sent_at`),
            INDEX `idx_notification_logs_status` (`status`),
            INDEX `idx_notification_logs_type` (`notification_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function logNotification(array $data): bool
{
    try {
        $pdo = Database::getInstance();
        ensureNotificationLogsTable($pdo);
        $stmt = $pdo->prepare(
            'INSERT INTO notification_logs
                (notification_type, recipient_email, recipient_name, subject, sent_at, status, error_message, mail_server_used, retry_count)
             VALUES
                (:notification_type, :recipient_email, :recipient_name, :subject, :sent_at, :status, :error_message, :mail_server_used, :retry_count)'
        );
        $stmt->execute([
            ':notification_type' => (string)($data['notification_type'] ?? 'unknown'),
            ':recipient_email' => (string)($data['recipient_email'] ?? ''),
            ':recipient_name' => ($data['recipient_name'] ?? null) !== null ? (string)$data['recipient_name'] : null,
            ':subject' => ($data['subject'] ?? null) !== null ? (string)$data['subject'] : null,
            ':sent_at' => (string)($data['sent_at'] ?? date('Y-m-d H:i:s')),
            ':status' => in_array(($data['status'] ?? ''), ['success', 'failed', 'pending'], true) ? (string)$data['status'] : 'pending',
            ':error_message' => ($data['error_message'] ?? null) !== null ? (string)$data['error_message'] : null,
            ':mail_server_used' => ($data['mail_server_used'] ?? null) !== null ? (string)$data['mail_server_used'] : null,
            ':retry_count' => max(0, (int)($data['retry_count'] ?? 0)),
        ]);
        return true;
    } catch (Throwable $e) {
        error_log('logNotification error: ' . $e->getMessage());
        return false;
    }
}

function getNotificationLogs(int $limit = 50, int $offset = 0, array $filters = []): array
{
    $limit = max(1, min(200, $limit));
    $offset = max(0, $offset);

    try {
        $pdo = Database::getInstance();
        ensureNotificationLogsTable($pdo);

        $where = [];
        $params = [];

        if (!empty($filters['status']) && in_array($filters['status'], ['success', 'failed', 'pending'], true)) {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['notification_type'])) {
            $where[] = 'notification_type = ?';
            $params[] = (string)$filters['notification_type'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(sent_at) >= ?';
            $params[] = (string)$filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(sent_at) <= ?';
            $params[] = (string)$filters['date_to'];
        }

        $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM notification_logs' . $whereSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $rowsStmt = $pdo->prepare(
            'SELECT id, notification_type, recipient_email, recipient_name, subject, sent_at, status, error_message, mail_server_used, retry_count
             FROM notification_logs' . $whereSql . '
             ORDER BY sent_at DESC, id DESC
             LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset
        );
        $rowsStmt->execute($params);

        return [
            'total' => $total,
            'rows' => $rowsStmt->fetchAll(),
        ];
    } catch (Throwable $e) {
        error_log('getNotificationLogs error: ' . $e->getMessage());
        return [
            'total' => 0,
            'rows' => [],
        ];
    }
}

function testSmtpConnection(?array $smtpConfig = null): array
{
    $smtp = $smtpConfig ?? getSmtpConfig();
    if (empty($smtp['enabled'])) {
        return ['success' => true, 'message' => 'SMTP disabilitato: verrà usata la configurazione PHP mail predefinita.'];
    }

    $host = trim((string)($smtp['host'] ?? ''));
    $port = (int)($smtp['port'] ?? 0);
    $encryption = (string)($smtp['encryption'] ?? '');

    if ($host === '' || $port <= 0 || $port > 65535) {
        return ['success' => false, 'message' => 'Host/porta SMTP non validi.'];
    }

    $target = $encryption === 'ssl' ? ('ssl://' . $host) : $host;
    $errno = 0;
    $errstr = '';
    $socket = fsockopen($target, $port, $errno, $errstr, 5);
    if (!is_resource($socket)) {
        $detail = 'errore #' . $errno;
        if ($errstr !== '') {
            $detail .= ' - ' . $errstr;
        }
        return ['success' => false, 'message' => 'Connessione SMTP fallita: ' . $detail];
    }
    stream_set_timeout($socket, 5);
    $banner = fgets($socket);
    @fclose($socket);
    if ($banner === false || preg_match('/^220\s/', ltrim($banner)) !== 1) {
        $bannerText = $banner !== false ? trim($banner) : 'banner non disponibile';
        return ['success' => false, 'message' => 'Connessione aperta ma banner SMTP non valido: ' . $bannerText];
    }

    return ['success' => true, 'message' => 'Connessione SMTP riuscita verso ' . $host . ':' . $port . '.'];
}

function getSystemConfigValue(string $key, string $default = ''): string
{
    try {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT `value` FROM system_config WHERE `key` = ? LIMIT 1');
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return is_string($value) ? $value : $default;
    } catch (Throwable) {
        return $default;
    }
}

function buildHtmlEmail(string $templateName, array $templateData = []): array
{
    require_once dirname(__DIR__) . '/includes/email-builder.php';
    return emailTemplateBuilder($templateName, $templateData);
}

// ─── EMAIL HELPER ──────────────────────────────────────────────────────────
function sendEmail(string $to, string $subject, mixed $body, string $from = '', ?string &$errorMessage = null): bool {
    $to = trim((string)(preg_replace('/[\r\n\x00]+/', '', $to) ?? ''));
    $subject = trim((string)(preg_replace('/[\r\n\x00]+/', '', $subject) ?? ''));
    $from = trim((string)(preg_replace('/[\r\n\x00]+/', '', $from) ?? ''));
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Destinatario email non valido.';
        return false;
    }
    if ($subject === '') {
        $errorMessage = 'Oggetto email mancante.';
        return false;
    }

    $smtp = getSmtpConfig();
    if (!empty($smtp['enabled']) && !empty($smtp['host']) && !empty($smtp['port'])) {
        @ini_set('SMTP', (string)$smtp['host']);
        @ini_set('smtp_port', (string)$smtp['port']);
    }

    $senderName = trim((string)($smtp['sender_name'] ?? 'EasyBooking'));
    $smtpSenderEmail = trim((string)($smtp['sender_email'] ?? ''));

    if ($from === '') {
        if ($smtpSenderEmail !== '' && filter_var($smtpSenderEmail, FILTER_VALIDATE_EMAIL)) {
            $from = $smtpSenderEmail;
        } else {
            try {
                $pdo  = Database::getInstance();
                $stmt = $pdo->prepare("SELECT `value` FROM system_config WHERE `key` = 'app_email' LIMIT 1");
                $stmt->execute();
                $row = $stmt->fetch();
                $from = $row ? $row['value'] : 'noreply@easybooking.local';
            } catch (Exception) {
                $from = 'noreply@easybooking.local';
            }
        }
    }

    if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
        $from = 'noreply@easybooking.local';
    }

    @ini_set('sendmail_from', $from);

    $safeSenderName = $senderName !== '' ? $senderName : 'EasyBooking';
    $safeSenderName = preg_replace('/[\r\n\x00]+/', '', $safeSenderName) ?? 'EasyBooking';
    $htmlBody = is_array($body) ? trim((string)($body['html'] ?? '')) : trim((string)$body);
    $textBody = is_array($body) ? trim((string)($body['text'] ?? '')) : '';
    if ($textBody === '') {
        $textBody = html_entity_decode(strip_tags($htmlBody), ENT_QUOTES, 'UTF-8');
    }
    if ($htmlBody === '' && $textBody === '') {
        $errorMessage = 'Corpo email vuoto.';
        return false;
    }

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "From: {$safeSenderName} <{$from}>\r\n";
    $headers .= "Reply-To: {$from}\r\n";
    $message = '';
    if ($htmlBody !== '' && $textBody !== '') {
        $boundary = 'easybooking-' . bin2hex(random_bytes(12));
        $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        $message = "--{$boundary}\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: quoted-printable\r\n\r\n"
            . quoted_printable_encode($textBody) . "\r\n"
            . "--{$boundary}\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: quoted-printable\r\n\r\n"
            . quoted_printable_encode($htmlBody) . "\r\n"
            . "--{$boundary}--";
    } elseif ($htmlBody !== '') {
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: quoted-printable\r\n";
        $message = quoted_printable_encode($htmlBody);
    } else {
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: quoted-printable\r\n";
        $message = quoted_printable_encode($textBody);
    }

    $encodedSubject = function_exists('mb_encode_mimeheader')
        ? mb_encode_mimeheader($subject, 'UTF-8')
        : $subject;

    $lastError = '';
    set_error_handler(static function (int $severity, string $message) use (&$lastError): bool {
        $lastError = $message;
        return false;
    });
    try {
        $result = mail($to, $encodedSubject, $message, $headers);
    } finally {
        restore_error_handler();
    }
    if (!$result) {
        $errorMessage = $lastError !== '' ? $lastError : 'mail() ha restituito false';
    }
    return $result;
}

// ─── MISC ──────────────────────────────────────────────────────────────────
function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

function isAjax(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function wantsJsonResponse(): bool
{
    $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
    return isAjax() || str_contains($accept, 'application/json');
}

function jsonResponse(array $data, int $status = 200): never {
    // Discard any buffered output (PHP warnings, notices, HTML fragments)
    // so only clean JSON is sent to the client.
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function respondOperationResult(
    bool $success,
    string $message,
    string $redirectUrl,
    int $status = 200,
    array $payload = []
): never {
    if (wantsJsonResponse()) {
        jsonResponse(array_merge(['success' => $success, 'message' => $message], $payload), $status);
    }

    setFlash($success ? 'success' : ($status >= 500 ? 'danger' : 'warning'), $message);
    redirect($redirectUrl);
}

function appName(): string {
    try {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("SELECT `value` FROM system_config WHERE `key` = 'app_name' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ? $row['value'] : 'EasyBooking';
    } catch (Exception) {
        return 'EasyBooking';
    }
}

function activePage(string $page): string {
    $current = basename($_SERVER['PHP_SELF'], '.php');
    return $current === $page ? 'active' : '';
}

function statusBadge(string $stato): string {
    $map = [
        'Programmata'   => 'primary',
        'Svolta'        => 'success',
        'Assente'       => 'danger',
        'Rimandata'     => 'warning',
        'Riprogrammata' => 'info',
    ];
    $color = $map[$stato] ?? 'secondary';
    return '<span class="badge bg-' . $color . '">' . htmlspecialchars($stato) . '</span>';
}

function paymentBadge(string $stato): string {
    $map = [
        'Pagato'         => 'success',
        'Non Pagato'     => 'danger',
        'Parziale'       => 'warning',
        'In Attesa'      => 'secondary',
    ];
    $color = $map[$stato] ?? 'secondary';
    return '<span class="badge bg-' . $color . '">' . htmlspecialchars($stato) . '</span>';
}

function decryptFullName(?string $nome, ?string $cognome, string $fallback = ''): string
{
    $parts = [];
    if ($nome !== null && $nome !== '') {
        $parts[] = decryptField($nome);
    }
    if ($cognome !== null && $cognome !== '') {
        $parts[] = decryptField($cognome);
    }

    $fullName = trim(implode(' ', array_filter($parts, static fn(string $part): bool => $part !== '')));
    return $fullName !== '' ? $fullName : $fallback;
}

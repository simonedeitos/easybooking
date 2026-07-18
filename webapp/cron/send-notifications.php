<?php
/**
 * cron/send-notifications.php
 *
 * Invia le notifiche email configurate da ogni utente in Impostazioni → Notifiche.
 */

declare(strict_types=1);

if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Europe/Rome');
}

$__isCli = (PHP_SAPI === 'cli');
$__logFile = __DIR__ . '/send-notifications.log';
$__testMode = isset($_GET['test_mode']) && $_GET['test_mode'] === '1';

if (!$__isCli) {
    ob_start();
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/php-error.log');
    error_reporting(E_ALL);
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/encryption.php';
require_once __DIR__ . '/../includes/email-builder.php';

function cronAuthorizeHttp(): void
{
    $secret = trim((string)(getenv('CRON_SECRET') ?: ''));
    if ($secret === '') {
        http_response_code(403);
        echo "Accesso negato: CRON_SECRET non configurato nel file .env.\n";
        echo "Aggiungere la riga: CRON_SECRET=una_stringa_segreta_lunga\n";
        exit(1);
    }

    $token = trim((string)($_GET['cron_token'] ?? ''));
    if ($token === '' || !hash_equals($secret, $token)) {
        http_response_code(403);
        echo "Accesso negato: token non valido.\n";
        echo "Usare: ?cron_token=<valore di CRON_SECRET nel .env>\n";
        exit(1);
    }
}

function validateCronEnvironment(bool $isHttp): void
{
    $envFile = dirname(__DIR__) . '/.env';
    if (!is_file($envFile)) {
        $msg = "CONFIGURAZIONE MANCANTE: il file webapp/.env non esiste.\n"
             . "Copiare webapp/.env.example in webapp/.env e compilare i valori.\n";
        if ($isHttp) {
            http_response_code(500);
            echo $msg;
            exit(1);
        }
        fwrite(STDERR, $msg);
        exit(1);
    }

    if (defined('DB_PASS') && DB_PASS === '') {
        $msg = "AVVISO: DB_PASS non impostato nel file .env. La connessione al database potrebbe fallire.\n";
        if ($isHttp) {
            error_log('[send-notifications] ' . trim($msg));
            return;
        }
        fwrite(STDERR, $msg);
    }
}

function cronLog(string $message, ?string $logFile = null): void
{
    $timestamp = '[' . date('Y-m-d H:i:s') . ']';
    $output = $timestamp . ' ' . $message . PHP_EOL;
    echo $output;

    if ($logFile && is_writable(dirname($logFile))) {
        @file_put_contents($logFile, $output, FILE_APPEND | LOCK_EX);
    }
}

function ensureNotificheLogTable(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS `notifiche_log` (
            `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
            `user_id`       INT UNSIGNED    NOT NULL,
            `tipo`          VARCHAR(50)     NOT NULL,
            `riferimento`   VARCHAR(50)     NOT NULL,
            `sent_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_user_tipo_riferimento` (`user_id`, `tipo`, `riferimento`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    try {
        $pdo->exec("ALTER TABLE `notifiche_log` MODIFY `riferimento` VARCHAR(50) NOT NULL");
    } catch (Throwable $e) {
        error_log('[send-notifications] Impossibile aggiornare notifiche_log.riferimento a VARCHAR(50): ' . $e->getMessage());
    }
}

function markNotificationSent(PDO $pdo, int $userId, string $tipo, string $riferimento): bool
{
    try {
        $stmt = $pdo->prepare('INSERT INTO notifiche_log (user_id, tipo, riferimento) VALUES (?, ?, ?)');
        $stmt->execute([$userId, $tipo, $riferimento]);
        return true;
    } catch (PDOException) {
        return false;
    }
}

function unmarkNotificationSent(PDO $pdo, int $userId, string $tipo, string $riferimento): void
{
    $stmt = $pdo->prepare('DELETE FROM notifiche_log WHERE user_id = ? AND tipo = ? AND riferimento = ?');
    $stmt->execute([$userId, $tipo, $riferimento]);
}

function cronResolveTimezone(PDO $pdo): string
{
    $candidate = trim(getSystemConfigValue('app_timezone', getenv('APP_TIMEZONE') ?: date_default_timezone_get()));
    if ($candidate === '') {
        $candidate = 'Europe/Rome';
    }

    try {
        new DateTimeZone($candidate);
        date_default_timezone_set($candidate);
        return $candidate;
    } catch (Throwable) {
        date_default_timezone_set('Europe/Rome');
        return 'Europe/Rome';
    }
}

function italianDayKeyForDate(DateTimeImmutable $date): string
{
    $map = ['Mon' => 'lun', 'Tue' => 'mar', 'Wed' => 'mer', 'Thu' => 'gio', 'Fri' => 'ven', 'Sat' => 'sab', 'Sun' => 'dom'];
    return $map[$date->format('D')] ?? 'lun';
}

function cronConfiguredTime(DateTimeImmutable $now, string $configuredTime): ?DateTimeImmutable
{
    if (preg_match('/^\d{2}:\d{2}(?::\d{2})?$/', $configuredTime) !== 1) {
        return null;
    }

    $configuredTime = strlen($configuredTime) === 5 ? ($configuredTime . ':00') : $configuredTime;
    try {
        return new DateTimeImmutable($now->format('Y-m-d') . ' ' . $configuredTime);
    } catch (Throwable) {
        return null;
    }
}

function cronTimeMatches(DateTimeImmutable $now, string $configuredTime, int $windowSeconds = 60): bool
{
    $scheduledAt = cronConfiguredTime($now, $configuredTime);
    if (!$scheduledAt instanceof DateTimeImmutable) {
        return false;
    }

    return abs($now->getTimestamp() - $scheduledAt->getTimestamp()) <= max(0, $windowSeconds);
}

function cronMonthlyDayMatches(DateTimeImmutable $now, int $configuredDay): bool
{
    $configuredDay = max(1, min(31, $configuredDay));
    $lastDayOfMonth = (int)$now->format('t');
    return (int)$now->format('j') === min($configuredDay, $lastDayOfMonth);
}

function cronPreviewDirectory(): string
{
    $dir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'easybooking-email-previews';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir;
}

function saveTestModePreview(string $notificationType, string $recipientEmail, string $subject, array $body, string $timezone): string
{
    $baseName = date('Ymd-His') . '-' . preg_replace('/[^a-z0-9_-]+/i', '-', $notificationType) . '.html';
    $path = cronPreviewDirectory() . DIRECTORY_SEPARATOR . $baseName;
    $html = "<!-- recipient: {$recipientEmail} | subject: {$subject} | timezone: {$timezone} -->\n" . (string)($body['html'] ?? '');
    file_put_contents($path, $html);
    return $path;
}

function sendLoggedNotification(
    array $smtpConfig,
    array $smtpConnectionTest,
    string $notificationType,
    string $recipientEmail,
    string $recipientName,
    string $subject,
    array $body,
    string $logFile,
    bool $testMode = false,
    string $timezone = 'Europe/Rome',
    int $additionalRetries = 2
): bool {
    $retryCount = 0;
    $smtpPort = (int)($smtpConfig['port'] ?? 0);
    $mailServer = 'php-mail';
    if (!empty($smtpConfig['enabled']) && !empty($smtpConfig['host'])) {
        $mailServer = (string)$smtpConfig['host'];
        if ($smtpPort > 0 && $smtpPort <= 65535) {
            $mailServer .= ':' . $smtpPort;
        }
    }

    if ($testMode) {
        $previewPath = saveTestModePreview($notificationType, $recipientEmail, $subject, $body, $timezone);
        cronLog("    [TEST MODE] Anteprima salvata in: {$previewPath}", $logFile);
        logNotification([
            'notification_type' => $notificationType,
            'recipient_email' => $recipientEmail,
            'recipient_name' => $recipientName,
            'subject' => $subject,
            'status' => 'pending',
            'error_message' => 'Test mode attivo. Anteprima: ' . $previewPath,
            'mail_server_used' => 'test-mode',
            'retry_count' => 0,
        ]);
        return true;
    }

    if (!empty($smtpConfig['enabled']) && empty($smtpConnectionTest['success'])) {
        $lastError = (string)($smtpConnectionTest['message'] ?? 'Connessione SMTP fallita.');
        cronLog('    ✗ SMTP non disponibile: ' . $lastError, $logFile);
        logNotification([
            'notification_type' => $notificationType,
            'recipient_email' => $recipientEmail,
            'recipient_name' => $recipientName,
            'subject' => $subject,
            'status' => 'failed',
            'error_message' => $lastError,
            'mail_server_used' => $mailServer,
            'retry_count' => 0,
        ]);
        return false;
    }

    $lastError = '';
    $maxAttempts = max(1, $additionalRetries + 1);
    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        cronLog("    Tentativo {$attempt}/{$maxAttempts} invio {$notificationType}", $logFile);
        $errorMessage = '';
        $sent = sendEmail($recipientEmail, $subject, $body, '', $errorMessage);
        if ($sent) {
            $retryCount = max(0, $attempt - 1);
            logNotification([
                'notification_type' => $notificationType,
                'recipient_email' => $recipientEmail,
                'recipient_name' => $recipientName,
                'subject' => $subject,
                'status' => 'success',
                'error_message' => null,
                'mail_server_used' => $mailServer,
                'retry_count' => $retryCount,
            ]);
            return true;
        }

        $retryCount = max(0, $attempt - 1);
        $lastError = $errorMessage !== '' ? $errorMessage : 'Invio non riuscito.';
        cronLog("    ✗ Tentativo {$attempt} fallito: {$lastError}", $logFile);

        if ($attempt < $maxAttempts) {
            $delaySeconds = min(8, 2 ** $retryCount);
            cronLog("    ⟳ Nuovo tentativo tra {$delaySeconds} secondi", $logFile);
            sleep($delaySeconds);
        }
    }

    logNotification([
        'notification_type' => $notificationType,
        'recipient_email' => $recipientEmail,
        'recipient_name' => $recipientName,
        'subject' => $subject,
        'status' => 'failed',
        'error_message' => $lastError,
        'mail_server_used' => $mailServer,
        'retry_count' => $retryCount,
    ]);

    return false;
}

function notificationEnabled(array $config, string $enabledKey): bool
{
    return !empty($config[$enabledKey]) && !empty($config['abilita_email']) && !empty($config['email_notifiche']);
}

function processReminderLezioni(
    PDO $pdo,
    array $user,
    array $config,
    array $smtpConfig,
    array $smtpConnectionTest,
    DateTimeImmutable $now,
    string $logFile,
    bool $testMode,
    string $timezone
): void {
    cronLog('  [reminder_lezioni] Inizio', $logFile);
    if (!notificationEnabled($config, 'reminder_lezioni_enabled')) {
        cronLog('    ✗ Notifica disabilitata o email mancante', $logFile);
        return;
    }

    $currentDayKey = italianDayKeyForDate($now);
    $configuredDay = (string)($config['reminder_lezioni_giorno_settimana'] ?? 'lun');
    $configuredTime = (string)($config['reminder_lezioni_ora'] ?? '09:00:00');
    cronLog("    Giorno corrente/configurato: {$currentDayKey}/{$configuredDay}", $logFile);
    cronLog('    Ora corrente/configurata: ' . $now->format('H:i:s') . '/' . $configuredTime, $logFile);

    if ($currentDayKey !== $configuredDay || !cronTimeMatches($now, $configuredTime)) {
        cronLog('    ✗ Finestra oraria non corrisponde', $logFile);
        return;
    }

    $payload = buildNotificationEmailPreview($pdo, $user, $config, 'reminder_lezioni', $now);
    cronLog('    Dati generati: ' . $payload['summary'], $logFile);
    if (empty($payload['should_send'])) {
        cronLog('    ✗ Nessuna lezione da inviare', $logFile);
        return;
    }

    $reference = $now->format('Y-m-d');
    if (!markNotificationSent($pdo, (int)$user['id'], 'reminder_lezioni', $reference)) {
        cronLog('    ✗ Già inviato per oggi', $logFile);
        return;
    }

    $sent = sendLoggedNotification(
        $smtpConfig,
        $smtpConnectionTest,
        'reminder_lezioni',
        (string)$config['email_notifiche'],
        'Utente #' . (int)$user['id'],
        (string)$payload['subject'],
        ['html' => (string)$payload['html'], 'text' => (string)$payload['text']],
        $logFile,
        $testMode,
        $timezone
    );

    if ($sent) {
        cronLog('    ✓ Promemoria gestito correttamente', $logFile);
        return;
    }

    unmarkNotificationSent($pdo, (int)$user['id'], 'reminder_lezioni', $reference);
    cronLog('    ✗ Invio promemoria fallito', $logFile);
}

function processReportSettimanale(
    PDO $pdo,
    array $user,
    array $config,
    array $smtpConfig,
    array $smtpConnectionTest,
    DateTimeImmutable $now,
    string $logFile,
    bool $testMode,
    string $timezone
): void {
    cronLog('  [report_settimanale] Inizio', $logFile);
    if (!notificationEnabled($config, 'report_settimanale_enabled')) {
        cronLog('    ✗ Notifica disabilitata o email mancante', $logFile);
        return;
    }

    $currentDayKey = italianDayKeyForDate($now);
    $configuredDay = (string)($config['report_settimanale_giorno'] ?? 'lun');
    $configuredTime = (string)($config['report_settimanale_ora'] ?? '18:00:00');
    cronLog("    Giorno corrente/configurato: {$currentDayKey}/{$configuredDay}", $logFile);
    cronLog('    Ora corrente/configurata: ' . $now->format('H:i:s') . '/' . $configuredTime, $logFile);

    if ($currentDayKey !== $configuredDay || !cronTimeMatches($now, $configuredTime)) {
        cronLog('    ✗ Finestra oraria non corrisponde', $logFile);
        return;
    }

    $payload = buildNotificationEmailPreview($pdo, $user, $config, 'report_settimanale', $now);
    cronLog('    Report generato: ' . $payload['summary'], $logFile);
    if (empty($payload['should_send'])) {
        cronLog('    ✗ Nessun dato disponibile per il report', $logFile);
        return;
    }

    $reference = $now->format('o-\WW');
    if (!markNotificationSent($pdo, (int)$user['id'], 'report_settimanale', $reference)) {
        cronLog('    ✗ Report già inviato questa settimana', $logFile);
        return;
    }

    $sent = sendLoggedNotification(
        $smtpConfig,
        $smtpConnectionTest,
        'report_settimanale',
        (string)$config['email_notifiche'],
        'Utente #' . (int)$user['id'],
        (string)$payload['subject'],
        ['html' => (string)$payload['html'], 'text' => (string)$payload['text']],
        $logFile,
        $testMode,
        $timezone
    );

    if ($sent) {
        cronLog('    ✓ Report settimanale gestito correttamente', $logFile);
        return;
    }

    unmarkNotificationSent($pdo, (int)$user['id'], 'report_settimanale', $reference);
    cronLog('    ✗ Invio report settimanale fallito', $logFile);
}

function processReportMensile(
    PDO $pdo,
    array $user,
    array $config,
    array $smtpConfig,
    array $smtpConnectionTest,
    DateTimeImmutable $now,
    string $logFile,
    bool $testMode,
    string $timezone
): void {
    cronLog('  [report_mensile] Inizio', $logFile);
    if (!notificationEnabled($config, 'report_mensile_enabled')) {
        cronLog('    ✗ Notifica disabilitata o email mancante', $logFile);
        return;
    }

    $configuredDay = (int)($config['report_mensile_giorno_mese'] ?? 1);
    $configuredTime = (string)($config['report_mensile_ora'] ?? '18:00:00');
    cronLog('    Giorno mese corrente/configurato: ' . $now->format('j') . '/' . $configuredDay, $logFile);
    cronLog('    Ora corrente/configurata: ' . $now->format('H:i:s') . '/' . $configuredTime, $logFile);

    if (!cronMonthlyDayMatches($now, $configuredDay) || !cronTimeMatches($now, $configuredTime)) {
        cronLog('    ✗ Finestra mensile non corrisponde', $logFile);
        return;
    }

    $payload = buildNotificationEmailPreview($pdo, $user, $config, 'report_mensile', $now);
    cronLog('    Report generato: ' . $payload['summary'], $logFile);
    if (empty($payload['should_send'])) {
        cronLog('    ✗ Nessun dato disponibile per il report', $logFile);
        return;
    }

    $reference = $now->format('Y-m');
    if (!markNotificationSent($pdo, (int)$user['id'], 'report_mensile', $reference)) {
        cronLog('    ✗ Report già inviato questo mese', $logFile);
        return;
    }

    $sent = sendLoggedNotification(
        $smtpConfig,
        $smtpConnectionTest,
        'report_mensile',
        (string)$config['email_notifiche'],
        'Utente #' . (int)$user['id'],
        (string)$payload['subject'],
        ['html' => (string)$payload['html'], 'text' => (string)$payload['text']],
        $logFile,
        $testMode,
        $timezone
    );

    if ($sent) {
        cronLog('    ✓ Report mensile gestito correttamente', $logFile);
        return;
    }

    unmarkNotificationSent($pdo, (int)$user['id'], 'report_mensile', $reference);
    cronLog('    ✗ Invio report mensile fallito', $logFile);
}

function processAvvisoScadenza(
    PDO $pdo,
    array $user,
    array $config,
    array $smtpConfig,
    array $smtpConnectionTest,
    DateTimeImmutable $now,
    string $logFile,
    bool $testMode,
    string $timezone
): void {
    cronLog('  [avviso_scadenza] Inizio', $logFile);
    if (!notificationEnabled($config, 'avviso_scadenza_enabled')) {
        cronLog('    ✗ Notifica disabilitata o email mancante', $logFile);
        return;
    }

    $payload = buildNotificationEmailPreview($pdo, $user, $config, 'avviso_scadenza', $now);
    cronLog('    Dati generati: ' . $payload['summary'], $logFile);
    if (empty($payload['should_send'])) {
        cronLog('    ✗ Nessun pacchetto in esaurimento', $logFile);
        return;
    }

    $reference = $now->format('Y-m-d');
    if (!markNotificationSent($pdo, (int)$user['id'], 'avviso_scadenza', $reference)) {
        cronLog('    ✗ Avviso già inviato oggi', $logFile);
        return;
    }

    $sent = sendLoggedNotification(
        $smtpConfig,
        $smtpConnectionTest,
        'avviso_scadenza',
        (string)$config['email_notifiche'],
        'Utente #' . (int)$user['id'],
        (string)$payload['subject'],
        ['html' => (string)$payload['html'], 'text' => (string)$payload['text']],
        $logFile,
        $testMode,
        $timezone
    );

    if ($sent) {
        cronLog('    ✓ Avviso scadenza gestito correttamente', $logFile);
        return;
    }

    unmarkNotificationSent($pdo, (int)$user['id'], 'avviso_scadenza', $reference);
    cronLog('    ✗ Invio avviso scadenza fallito', $logFile);
}

function processAvvisoNonConfermata(
    PDO $pdo,
    array $user,
    array $config,
    array $smtpConfig,
    array $smtpConnectionTest,
    DateTimeImmutable $now,
    string $logFile,
    bool $testMode,
    string $timezone
): void {
    cronLog('  [avviso_non_confermata] Inizio', $logFile);
    if (!notificationEnabled($config, 'avviso_non_confermata_enabled')) {
        cronLog('    ✗ Notifica disabilitata o email mancante', $logFile);
        return;
    }

    $payload = buildNotificationEmailPreview($pdo, $user, $config, 'avviso_non_confermata', $now);
    cronLog('    Dati generati: ' . $payload['summary'], $logFile);
    if (empty($payload['should_send'])) {
        cronLog('    ✗ Nessuna lezione da verificare', $logFile);
        return;
    }

    $reference = $now->format('Y-m-d');
    if (!markNotificationSent($pdo, (int)$user['id'], 'avviso_non_confermata', $reference)) {
        cronLog('    ✗ Avviso già inviato oggi', $logFile);
        return;
    }

    $sent = sendLoggedNotification(
        $smtpConfig,
        $smtpConnectionTest,
        'avviso_non_confermata',
        (string)$config['email_notifiche'],
        'Utente #' . (int)$user['id'],
        (string)$payload['subject'],
        ['html' => (string)$payload['html'], 'text' => (string)$payload['text']],
        $logFile,
        $testMode,
        $timezone
    );

    if ($sent) {
        cronLog('    ✓ Avviso non confermata gestito correttamente', $logFile);
        return;
    }

    unmarkNotificationSent($pdo, (int)$user['id'], 'avviso_non_confermata', $reference);
    cronLog('    ✗ Invio avviso non confermata fallito', $logFile);
}

if (!$__isCli) {
    cronAuthorizeHttp();
    header('Content-Type: text/plain; charset=utf-8');
}

validateCronEnvironment(!$__isCli);
cronLog('======================================================', $__logFile);
cronLog('Avvio send-notifications.php', $__logFile);
cronLog('Modalità test: ' . ($__testMode ? 'ATTIVA' : 'OFF'), $__logFile);
cronLog('Timezone bootstrap: ' . date_default_timezone_get(), $__logFile);
cronLog('======================================================', $__logFile);

try {
    $pdo = Database::getInstance();
    cronLog('✓ Connessione DB OK', $__logFile);

    ensureNotificheLogTable($pdo);
    ensureNotificationLogsTable($pdo);
    cronLog('✓ Tabelle verificate', $__logFile);

    $resolvedTimezone = cronResolveTimezone($pdo);
    cronLog('✓ Timezone attiva: ' . $resolvedTimezone, $__logFile);

    $smtpConfig = getSmtpConfig($pdo);
    cronLog('SMTP Config: enabled=' . (!empty($smtpConfig['enabled']) ? 'YES' : 'NO') . ', host=' . ($smtpConfig['host'] ?? 'N/A'), $__logFile);

    $smtpConnectionTest = testSmtpConnection($smtpConfig);
    cronLog('SMTP Test: ' . ($smtpConnectionTest['success'] ? 'SUCCESS' : 'FAILED') . ' - ' . ($smtpConnectionTest['message'] ?? ''), $__logFile);

    $now = new DateTimeImmutable('now');
    cronLog('Ora di sistema: ' . $now->format('Y-m-d H:i:s (l)'), $__logFile);

    $stmt = $pdo->query(
        "SELECT nc.*, u.email AS user_email
         FROM notifiche_config nc
         INNER JOIN users u ON u.id = nc.user_id"
    );
    $configs = $stmt->fetchAll();
    cronLog('Configurazioni trovate: ' . count($configs), $__logFile);

    if ($configs === []) {
        cronLog('✗ Nessuna configurazione notifiche trovata.', $__logFile);
        cronLog('Fine send-notifications.php', $__logFile);
        exit(0);
    }

    foreach ($configs as $config) {
        $user = ['id' => (int)$config['user_id'], 'email' => (string)$config['user_email']];
        cronLog('', $__logFile);
        cronLog('Processing user #' . $user['id'] . ' (' . $user['email'] . ')', $__logFile);

        try {
            processReminderLezioni($pdo, $user, $config, $smtpConfig, $smtpConnectionTest, $now, $__logFile, $__testMode, $resolvedTimezone);
            processReportSettimanale($pdo, $user, $config, $smtpConfig, $smtpConnectionTest, $now, $__logFile, $__testMode, $resolvedTimezone);
            processReportMensile($pdo, $user, $config, $smtpConfig, $smtpConnectionTest, $now, $__logFile, $__testMode, $resolvedTimezone);
            processAvvisoScadenza($pdo, $user, $config, $smtpConfig, $smtpConnectionTest, $now, $__logFile, $__testMode, $resolvedTimezone);
            processAvvisoNonConfermata($pdo, $user, $config, $smtpConfig, $smtpConnectionTest, $now, $__logFile, $__testMode, $resolvedTimezone);
        } catch (Throwable $e) {
            cronLog('✗ ERRORE durante l\'elaborazione: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(), $__logFile);
        }
    }

    cronLog('', $__logFile);
    cronLog('✓ Completato invio notifiche per ' . count($configs) . ' utente/i.', $__logFile);
} catch (Throwable $e) {
    $errMsg = $e->getMessage();
    cronLog('✗ ERRORE FATALE: ' . $errMsg . ' in ' . $e->getFile() . ':' . $e->getLine(), $__logFile);
    if (!$__isCli) {
        error_log('[send-notifications] ERRORE FATALE: ' . $errMsg);
        http_response_code(500);
    }
    exit(1);
}

cronLog('Fine send-notifications.php', $__logFile);
cronLog('======================================================', $__logFile);
exit(0);

<?php
/**
 * cron/send-notifications.php
 *
 * Invia le notifiche email configurate da ogni utente in Impostazioni → Notifiche.
 */

// ⚠️ CRITICO: Impostare il fuso orario SUBITO, prima di qualsiasi altro codice
// date_default_timezone_set('Europe/Rome');

declare(strict_types=1);

$__isCli = (PHP_SAPI === 'cli');
$__logFile = __DIR__ . '/send-notifications.log';

// ── Configurazione errori per modalità HTTP ──────────────────────────────────
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

function cronAuthorizeHttp(): void
{
    $secret = trim((string) (getenv('CRON_SECRET') ?: ''));
    if ($secret === '') {
        http_response_code(403);
        echo "Accesso negato: CRON_SECRET non configurato nel file .env.\n";
        echo "Aggiungere la riga: CRON_SECRET=una_stringa_segreta_lunga\n";
        exit(1);
    }

    $token = trim((string) ($_GET['cron_token'] ?? ''));
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
        } else {
            fwrite(STDERR, $msg);
        }
    }
}

function cronLog(string $message, ?string $logFile = null): void
{
    $timestamp = '[' . date('Y-m-d H:i:s') . ']';
    $output = $timestamp . ' ' . $message . PHP_EOL;
    
    echo $output;
    
    // Log su file se specificato
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
            `riferimento`   VARCHAR(20)     NOT NULL,
            `sent_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_user_tipo_riferimento` (`user_id`, `tipo`, `riferimento`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function markNotificationSent(PDO $pdo, int $userId, string $tipo, string $riferimento): bool
{
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO notifiche_log (user_id, tipo, riferimento) VALUES (?, ?, ?)'
        );
        $stmt->execute([$userId, $tipo, $riferimento]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function unmarkNotificationSent(PDO $pdo, int $userId, string $tipo, string $riferimento): void
{
    $stmt = $pdo->prepare('DELETE FROM notifiche_log WHERE user_id = ? AND tipo = ? AND riferimento = ?');
    $stmt->execute([$userId, $tipo, $riferimento]);
}

function sendLoggedNotification(
    array $smtpConfig,
    array $smtpConnectionTest,
    string $notificationType,
    string $recipientEmail,
    string $recipientName,
    string $subject,
    string $body,
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
    $lastError = '';

    if (!empty($smtpConfig['enabled']) && empty($smtpConnectionTest['success'])) {
        $lastError = (string)($smtpConnectionTest['message'] ?? 'Connessione SMTP fallita.');
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

    $maxAttempts = max(1, $additionalRetries + 1);
    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        $retryUsed = max(0, $attempt - 1);
        $errorMessage = '';
        $sent = sendEmail($recipientEmail, $subject, $body, '', $errorMessage);
        if ($sent) {
            $retryCount = $retryUsed;
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

        $lastError = $errorMessage !== '' ? $errorMessage : 'Invio non riuscito.';
        $retryCount = $retryUsed;
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

function italianDayKeyForDate(DateTimeImmutable $date): string
{
    $map = ['Mon' => 'lun', 'Tue' => 'mar', 'Wed' => 'mer', 'Thu' => 'gio', 'Fri' => 'ven', 'Sat' => 'sab', 'Sun' => 'dom'];
    return $map[$date->format('D')] ?? 'lun';
}

function italianDateLabel(DateTimeImmutable $date): string
{
    $days   = ['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'];
    $months = ['', 'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'];
    return $days[(int)$date->format('w')] . ' ' . $date->format('j') . ' ' . $months[(int)$date->format('n')] . ' ' . $date->format('Y');
}

function h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function processReminderLezioni(PDO $pdo, array $user, array $config, array $smtpConfig, array $smtpConnectionTest, DateTimeImmutable $now, string $logFile): void
{
    cronLog("  [processReminderLezioni] Inizio per user_id=" . $user['id'], $logFile);
    
    if (empty($config['reminder_lezioni_enabled'])) {
        cronLog("    ✗ reminder_lezioni_enabled è disabilitato", $logFile);
        return;
    }
    if (empty($config['abilita_email'])) {
        cronLog("    ✗ abilita_email è disabilitato", $logFile);
        return;
    }
    if (empty($config['email_notifiche'])) {
        cronLog("    ✗ email_notifiche è vuoto", $logFile);
        return;
    }

    $currentDayKey = italianDayKeyForDate($now);
    $configuredDay = (string)($config['reminder_lezioni_giorno_settimana'] ?? 'lun');
    $configuredHour = substr((string)($config['reminder_lezioni_ora'] ?? '09:00:00'), 0, 5);
    $currentHour = $now->format('H:i');

    cronLog("    Giorno corrente: $currentDayKey, Giorno configurato: $configuredDay", $logFile);
    cronLog("    Ora corrente: $currentHour, Ora configurata: $configuredHour", $logFile);

    if ($currentDayKey !== $configuredDay || $currentHour !== $configuredHour) {
        cronLog("    ✗ Giorno/ora non corrisponde", $logFile);
        return;
    }

    cronLog("    ✓ Giorno/ora corrisponde - Procedendo...", $logFile);

    $riferimento = $now->format('Y-m-d');
    if (!markNotificationSent($pdo, (int)$user['id'], 'reminder_lezioni', $riferimento)) {
        cronLog("    ✗ Già inviato in questo periodo (o errore al salvataggio)", $logFile);
        return;
    }

    cronLog("    ✓ Marcato come inviato", $logFile);

    $giorniFuturi = max(1, (int)($config['reminder_lezioni_giorni_futuri'] ?? 7));
    $dataLimite = $now->modify("+{$giorniFuturi} days")->format('Y-m-d');
    $oggi = $now->format('Y-m-d');

    cronLog("    Cercando lezioni da $oggi a $dataLimite", $logFile);

    $stmt = $pdo->prepare(
        "SELECT p.data, p.ora_inizio, p.ora_fine, p.strumento, c.nome AS cliente_nome, c.cognome AS cliente_cognome
         FROM prenotazioni p
         INNER JOIN clienti c ON c.id = p.cliente_id
         WHERE p.data BETWEEN ? AND ?
           AND p.stato = 'Programmata'
         ORDER BY p.data ASC, p.ora_inizio ASC"
    );
    $stmt->execute([$oggi, $dataLimite]);
    $lezioni = $stmt->fetchAll();

    cronLog("    Trovate " . count($lezioni) . " lezioni", $logFile);

    if (empty($lezioni)) {
        cronLog("    ✗ Nessuna lezione trovata", $logFile);
        return;
    }

    $rows = '';
    foreach ($lezioni as $lezione) {
        $clienteNome = trim(decryptField((string)($lezione['cliente_nome'] ?? '')) . ' ' . decryptField((string)($lezione['cliente_cognome'] ?? '')));
        $dataFmt = italianDateLabel(new DateTimeImmutable((string)$lezione['data']));
        $rows .= '<li>' . h($dataFmt) . ' – ore ' . h(substr((string)$lezione['ora_inizio'], 0, 5))
            . '–' . h(substr((string)$lezione['ora_fine'], 0, 5))
            . ' – ' . h($clienteNome !== '' ? $clienteNome : 'Cliente')
            . (!empty($lezione['strumento']) ? ' (' . h($lezione['strumento']) . ')' : '')
            . '</li>';
    }

    $body = '<p>Ciao,</p>'
        . '<p>Ecco il promemoria delle lezioni programmate nei prossimi ' . (int)$giorniFuturi . ' giorni:</p>'
        . '<ul>' . $rows . '</ul>'
        . '<p>Questo è un messaggio automatico generato da EasyBooking.</p>';

    $subject = 'Promemoria lezioni – EasyBooking';
    $recipientEmail = (string)$config['email_notifiche'];
    
    cronLog("    Invio a: $recipientEmail", $logFile);
    
    $sent = sendLoggedNotification(
        $smtpConfig,
        $smtpConnectionTest,
        'reminder_lezioni',
        $recipientEmail,
        'Utente #' . (int)$user['id'],
        $subject,
        $body
    );
    
    if ($sent) {
        cronLog("    ✓ Promemoria lezioni inviato (" . count($lezioni) . ' lezioni)', $logFile);
    } else {
        unmarkNotificationSent($pdo, (int)$user['id'], 'reminder_lezioni', $riferimento);
        cronLog("    ✗ Invio promemoria lezioni fallito", $logFile);
    }
}

function processReportSettimanale(PDO $pdo, array $user, array $config, array $smtpConfig, array $smtpConnectionTest, DateTimeImmutable $now, string $logFile): void
{
    cronLog("  [processReportSettimanale] Inizio per user_id=" . $user['id'], $logFile);
    
    if (empty($config['report_settimanale_enabled'])) {
        cronLog("    ✗ report_settimanale_enabled è disabilitato", $logFile);
        return;
    }
    if (empty($config['abilita_email'])) {
        cronLog("    ✗ abilita_email è disabilitato", $logFile);
        return;
    }
    if (empty($config['email_notifiche'])) {
        cronLog("    ✗ email_notifiche è vuoto", $logFile);
        return;
    }

    $currentDayKey = italianDayKeyForDate($now);
    $configuredDay = (string)($config['report_settimanale_giorno'] ?? 'lun');
    $configuredHour = substr((string)($config['report_settimanale_ora'] ?? '18:00:00'), 0, 5);
    $currentHour = $now->format('H:i');

    cronLog("    Giorno corrente: $currentDayKey, Giorno configurato: $configuredDay", $logFile);
    cronLog("    Ora corrente: $currentHour, Ora configurata: $configuredHour", $logFile);

    if ($currentDayKey !== $configuredDay || $currentHour !== $configuredHour) {
        cronLog("    ✗ Giorno/ora non corrisponde", $logFile);
        return;
    }

    cronLog("    ✓ Giorno/ora corrisponde - Procedendo...", $logFile);

    $riferimento = $now->format('o-\WW');
    if (!markNotificationSent($pdo, (int)$user['id'], 'report_settimanale', $riferimento)) {
        cronLog("    ✗ Già inviato in questo periodo", $logFile);
        return;
    }

    $dataFine = $now->format('Y-m-d');
    $dataInizio = $now->modify('-6 days')->format('Y-m-d');
    $tipo = (string)($config['report_settimanale_tipo'] ?? 'lezioni');

    cronLog("    Report da $dataInizio a $dataFine, tipo: $tipo", $logFile);

    $body = '<p>Ciao,</p><p>Ecco il report settimanale (' . h($dataInizio) . ' – ' . h($dataFine) . '):</p>'
        . '<p>Report di test EasyBooking.</p>'
        . '<p>Questo è un messaggio automatico generato da EasyBooking.</p>';

    $subject = 'Report settimanale – EasyBooking';
    $recipientEmail = (string)$config['email_notifiche'];
    
    cronLog("    Invio a: $recipientEmail", $logFile);
    
    $sent = sendLoggedNotification(
        $smtpConfig,
        $smtpConnectionTest,
        'report_settimanale',
        $recipientEmail,
        'Utente #' . (int)$user['id'],
        $subject,
        $body
    );
    
    if ($sent) {
        cronLog("    ✓ Report settimanale inviato", $logFile);
    } else {
        unmarkNotificationSent($pdo, (int)$user['id'], 'report_settimanale', $riferimento);
        cronLog("    ✗ Invio report settimanale fallito", $logFile);
    }
}

if (!$__isCli) {
    cronAuthorizeHttp();
    header('Content-Type: text/plain; charset=utf-8');
}

validateCronEnvironment(!$__isCli);
cronLog('======================================================', $__logFile);
cronLog('Avvio send-notifications.php', $__logFile);
cronLog('Timezone: ' . date_default_timezone_get(), $__logFile);
cronLog('Data/Ora: ' . date('Y-m-d H:i:s'), $__logFile);
cronLog('======================================================', $__logFile);

try {
    $pdo = Database::getInstance();
    cronLog('✓ Connessione DB OK', $__logFile);
    
    ensureNotificheLogTable($pdo);
    ensureNotificationLogsTable($pdo);
    cronLog('✓ Tabelle verificate', $__logFile);
    
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

    if (empty($configs)) {
        cronLog('✗ Nessuna configurazione notifiche trovata.', $__logFile);
        cronLog('Fine send-notifications.php', $__logFile);
        exit(0);
    }

    foreach ($configs as $config) {
        $user = ['id' => (int)$config['user_id'], 'email' => (string)$config['user_email']];

        cronLog("", $__logFile);
        cronLog("Processing user #" . $user['id'] . " (" . $user['email'] . ")", $__logFile);

        try {
            processReminderLezioni($pdo, $user, $config, $smtpConfig, $smtpConnectionTest, $now, $__logFile);
            processReportSettimanale($pdo, $user, $config, $smtpConfig, $smtpConnectionTest, $now, $__logFile);
        } catch (Throwable $e) {
            cronLog("✗ ERRORE durante l'elaborazione: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine(), $__logFile);
        }
    }

    cronLog("", $__logFile);
    cronLog("✓ Completato invio notifiche per " . count($configs) . " utente/i.", $__logFile);
} catch (Throwable $e) {
    $errMsg = $e->getMessage();
    cronLog("✗ ERRORE FATALE: " . $errMsg . " in " . $e->getFile() . ":" . $e->getLine(), $__logFile);
    if (!$__isCli) {
        error_log('[send-notifications] ERRORE FATALE: ' . $errMsg);
        http_response_code(500);
    }
    exit(1);
}

cronLog("Fine send-notifications.php", $__logFile);
cronLog("======================================================", $__logFile);
exit(0);

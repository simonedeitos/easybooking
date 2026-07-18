<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/encryption.php';
require_once __DIR__ . '/includes/email-builder.php';
require_once __DIR__ . '/includes/auth.php';
requireAuth();
$pdo = Database::getInstance();
$user = currentUser();
$isAdmin = ($user['role'] ?? 'user') === 'admin';
$embedded = get('embedded') === '1';

function h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function notificationDayMap(): array
{
    return [
        'lun' => 'Lunedì',
        'mar' => 'Martedì',
        'mer' => 'Mercoledì',
        'gio' => 'Giovedì',
        'ven' => 'Venerdì',
        'sab' => 'Sabato',
        'dom' => 'Domenica',
    ];
}

function notificationReportTypes(): array
{
    return [
        'lezioni' => 'Riepilogo lezioni',
        'clienti' => 'Riepilogo clienti',
        'incassi' => 'Riepilogo incassi',
    ];
}

function notificationFlag(string $key): int
{
    return isset($_POST[$key]) ? 1 : 0;
}

function notificationTime(string $value, string $fallback): string
{
    $value = trim($value);
    if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $value) === 1) {
        return strlen($value) === 5 ? $value . ':00' : $value;
    }
    return $fallback;
}

function notificationRedirectTarget(bool $embedded): string
{
    return 'notifiche.php' . ($embedded ? '?embedded=1' : '');
}

function notificationValidEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function notificationInRange(int $value, int $min, int $max): bool
{
    return $value >= $min && $value <= $max;
}

function notificationCleanText(mixed $value, int $maxLength = 255): string
{
    $value = trim((string)$value);
    if ($maxLength > 0) {
        $value = mb_substr($value, 0, $maxLength);
    }
    return $value;
}

$requestAction = $_SERVER['REQUEST_METHOD'] === 'POST' ? post('action') : get('action');
$previewConfigOverride = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($requestAction === 'preview') {
        verifyCsrf();
        $previewConfigOverride = [
            'email_notifiche' => trim(post('email_notifiche')),
            'abilita_email' => notificationFlag('abilita_email'),
            'reminder_lezioni_enabled' => notificationFlag('reminder_lezioni_enabled'),
            'reminder_lezioni_giorni_prima' => max(0, sanitizeInt(post('reminder_lezioni_giorni_prima'))),
            'reminder_lezioni_giorno_settimana' => post('reminder_lezioni_giorno_settimana') ?: 'lun',
            'reminder_lezioni_ora' => notificationTime(post('reminder_lezioni_ora'), '09:00:00'),
            'reminder_lezioni_giorni_futuri' => max(1, sanitizeInt(post('reminder_lezioni_giorni_futuri'))),
            'report_settimanale_enabled' => notificationFlag('report_settimanale_enabled'),
            'report_settimanale_giorno' => post('report_settimanale_giorno') ?: 'lun',
            'report_settimanale_ora' => notificationTime(post('report_settimanale_ora'), '18:00:00'),
            'report_settimanale_tipo' => post('report_settimanale_tipo') ?: 'lezioni',
            'report_mensile_enabled' => notificationFlag('report_mensile_enabled'),
            'report_mensile_giorno_mese' => max(1, min(31, sanitizeInt(post('report_mensile_giorno_mese')))),
            'report_mensile_ora' => notificationTime(post('report_mensile_ora'), '18:00:00'),
            'report_mensile_tipo' => post('report_mensile_tipo') ?: 'lezioni',
            'avviso_scadenza_enabled' => notificationFlag('avviso_scadenza_enabled'),
            'avviso_scadenza_giorni' => max(0, sanitizeInt(post('avviso_scadenza_giorni'))),
            'avviso_non_confermata_enabled' => notificationFlag('avviso_non_confermata_enabled'),
            'avviso_non_confermata_giorni' => max(0, sanitizeInt(post('avviso_non_confermata_giorni'))),
        ];
    }

    if ($requestAction === 'save') {
        try {
            verifyCsrf();

            $days = notificationDayMap();
            $reportTypes = notificationReportTypes();
            $email = trim(post('email_notifiche'));
            $abilitaEmail = notificationFlag('abilita_email');
            $reminderDay = post('reminder_lezioni_giorno_settimana');
            $reportSettimanaleGiorno = post('report_settimanale_giorno');
            $reportSettimanaleTipo = post('report_settimanale_tipo');
            $reportMensileTipo = post('report_mensile_tipo');

            if ($abilitaEmail === 1) {
                if ($email === '') {
                    setFlash('danger', 'Inserisci l\'email da usare per le notifiche.');
                    redirect(notificationRedirectTarget($embedded));
                }
                if (!notificationValidEmail($email)) {
                    setFlash('danger', 'Inserisci un indirizzo email valido per le notifiche.');
                    redirect(notificationRedirectTarget($embedded));
                }
            } elseif ($email !== '' && !notificationValidEmail($email)) {
                setFlash('danger', 'L\'email indicata non è valida.');
                redirect(notificationRedirectTarget($embedded));
            }

            if (!array_key_exists($reminderDay, $days)) {
                $reminderDay = 'lun';
            }
            if (!array_key_exists($reportSettimanaleGiorno, $days)) {
                $reportSettimanaleGiorno = 'lun';
            }
            if (!array_key_exists($reportSettimanaleTipo, $reportTypes)) {
                $reportSettimanaleTipo = 'lezioni';
            }
            if (!array_key_exists($reportMensileTipo, $reportTypes)) {
                $reportMensileTipo = 'lezioni';
            }

            $reminderFutureDays = sanitizeInt(post('reminder_lezioni_giorni_futuri'));
            if ($reminderFutureDays < 1) {
                setFlash('danger', 'I giorni futuri da controllare per i promemoria devono essere almeno 1.');
                redirect(notificationRedirectTarget($embedded));
            }

            $reportMensileGiornoMese = sanitizeInt(post('report_mensile_giorno_mese'));
            if (!notificationInRange($reportMensileGiornoMese, 1, 31)) {
                setFlash('danger', 'Il giorno del report mensile deve essere compreso tra 1 e 31.');
                redirect(notificationRedirectTarget($embedded));
            }

            $userId = isset($user['id']) && is_numeric($user['id']) ? (int)$user['id'] : 0;
            if ($userId <= 0) {
                throw new RuntimeException('User ID non valido.');
            }

            error_log('[notifiche.php] Preparazione statement INSERT/UPDATE per user_id=' . $userId);

            $stmt = $pdo->prepare(
                'INSERT INTO notifiche_config (
                    user_id, email_notifiche, abilita_email,
                    reminder_lezioni_enabled, reminder_lezioni_giorni_prima, reminder_lezioni_giorno_settimana, reminder_lezioni_ora, reminder_lezioni_giorni_futuri,
                    report_settimanale_enabled, report_settimanale_giorno, report_settimanale_ora, report_settimanale_tipo,
                    report_mensile_enabled, report_mensile_giorno_mese, report_mensile_ora, report_mensile_tipo,
                    avviso_scadenza_enabled, avviso_scadenza_giorni,
                    avviso_non_confermata_enabled, avviso_non_confermata_giorni
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    email_notifiche = VALUES(email_notifiche),
                    abilita_email = VALUES(abilita_email),
                    reminder_lezioni_enabled = VALUES(reminder_lezioni_enabled),
                    reminder_lezioni_giorni_prima = VALUES(reminder_lezioni_giorni_prima),
                    reminder_lezioni_giorno_settimana = VALUES(reminder_lezioni_giorno_settimana),
                    reminder_lezioni_ora = VALUES(reminder_lezioni_ora),
                    reminder_lezioni_giorni_futuri = VALUES(reminder_lezioni_giorni_futuri),
                    report_settimanale_enabled = VALUES(report_settimanale_enabled),
                    report_settimanale_giorno = VALUES(report_settimanale_giorno),
                    report_settimanale_ora = VALUES(report_settimanale_ora),
                    report_settimanale_tipo = VALUES(report_settimanale_tipo),
                    report_mensile_enabled = VALUES(report_mensile_enabled),
                    report_mensile_giorno_mese = VALUES(report_mensile_giorno_mese),
                    report_mensile_ora = VALUES(report_mensile_ora),
                    report_mensile_tipo = VALUES(report_mensile_tipo),
                    avviso_scadenza_enabled = VALUES(avviso_scadenza_enabled),
                    avviso_scadenza_giorni = VALUES(avviso_scadenza_giorni),
                    avviso_non_confermata_enabled = VALUES(avviso_non_confermata_enabled),
                    avviso_non_confermata_giorni = VALUES(avviso_non_confermata_giorni)'
            );

            error_log('[notifiche.php] Statement preparato correttamente');

            $bindParams = [
                $userId,
                $email !== '' ? $email : null,
                $abilitaEmail,
                notificationFlag('reminder_lezioni_enabled'),
                max(0, sanitizeInt(post('reminder_lezioni_giorni_prima'))),
                $reminderDay,
                notificationTime(post('reminder_lezioni_ora'), '09:00:00'),
                $reminderFutureDays,
                notificationFlag('report_settimanale_enabled'),
                $reportSettimanaleGiorno,
                notificationTime(post('report_settimanale_ora'), '18:00:00'),
                $reportSettimanaleTipo,
                notificationFlag('report_mensile_enabled'),
                $reportMensileGiornoMese,
                notificationTime(post('report_mensile_ora'), '18:00:00'),
                $reportMensileTipo,
                notificationFlag('avviso_scadenza_enabled'),
                max(0, sanitizeInt(post('avviso_scadenza_giorni'))),
                notificationFlag('avviso_non_confermata_enabled'),
                max(0, sanitizeInt(post('avviso_non_confermata_giorni'))),
            ];

            error_log('[notifiche.php] Parametri bind: ' . json_encode($bindParams));

            $stmt->execute($bindParams);

            error_log('[notifiche.php] Query eseguita con successo');

            setFlash('success', 'Preferenze notifiche salvate con successo.');
            redirect(notificationRedirectTarget($embedded));
        } catch (PDOException $e) {
            error_log('[notifiche.php] PDOException [' . $e->getCode() . ']: ' . $e->getMessage());
            error_log('[notifiche.php] SQLSTATE: ' . $e->errorInfo[0] ?? 'N/A');
            error_log('[notifiche.php] Driver Code: ' . $e->errorInfo[1] ?? 'N/A');
            error_log('[notifiche.php] Driver Message: ' . $e->errorInfo[2] ?? 'N/A');
            error_log('[notifiche.php] Query: ' . ($stmt->queryString ?? 'N/A'));
            
            $errorMsg = 'Errore durante il salvataggio delle notifiche. ';
            if (!empty($e->errorInfo[2])) {
                $errorMsg .= 'Dettagli: ' . $e->errorInfo[2];
            }
            setFlash('danger', $errorMsg);
            redirect(notificationRedirectTarget($embedded));
        } catch (Throwable $e) {
            error_log('[notifiche.php] Throwable [' . get_class($e) . '] [' . $e->getCode() . ']: ' . $e->getMessage());
            error_log('[notifiche.php] Stack trace: ' . $e->getTraceAsString());
            
            setFlash('danger', 'Errore durante il salvataggio delle notifiche. Contatta il supporto se il problema persiste.');
            redirect(notificationRedirectTarget($embedded));
        }
    }

    if ($requestAction === 'save_smtp') {
        verifyCsrf();
        if (!$isAdmin) {
            setFlash('danger', 'Solo un amministratore può salvare la configurazione SMTP.');
            redirect(notificationRedirectTarget($embedded));
        }

        try {
            $smtpEnabled = notificationFlag('smtp_enabled');
            $smtpHost = notificationCleanText(post('smtp_host'));
            $smtpPort = sanitizeInt(post('smtp_port'));
            $smtpUsername = notificationCleanText(post('smtp_username'));
            $smtpPassword = (string)post('smtp_password');
            $smtpEncryption = post('smtp_encryption');
            $smtpSenderEmail = notificationCleanText(post('smtp_sender_email'));
            $smtpSenderName = notificationCleanText(post('smtp_sender_name'));

            if ($smtpEnabled === 1 && $smtpHost === '') {
                setFlash('danger', 'Se SMTP è abilitato devi specificare l\'host.');
                redirect(notificationRedirectTarget($embedded));
            }
            if ($smtpEnabled === 1 && !notificationInRange($smtpPort, 1, 65535)) {
                setFlash('danger', 'La porta SMTP deve essere compresa tra 1 e 65535.');
                redirect(notificationRedirectTarget($embedded));
            }
            if (!in_array($smtpEncryption, ['', 'tls', 'ssl'], true)) {
                setFlash('danger', 'Tipo di cifratura SMTP non valido.');
                redirect(notificationRedirectTarget($embedded));
            }
            if ($smtpSenderEmail !== '' && !notificationValidEmail($smtpSenderEmail)) {
                setFlash('danger', 'Email mittente SMTP non valida.');
                redirect(notificationRedirectTarget($embedded));
            }

            if ($smtpEnabled !== 1) {
                $passwordToStore = '';
            } elseif ($smtpPassword !== '') {
                $passwordToStore = encodeSmtpSecret($smtpPassword);
            } else {
                $passwordToStore = getStoredSmtpPasswordRaw($pdo);
            }

            error_log('[notifiche.php SMTP] Preparazione salvataggio: enabled=' . $smtpEnabled . ', host=' . $smtpHost . ', port=' . $smtpPort);

            $stmt = $pdo->prepare(
                'INSERT INTO system_config (`key`, `value`) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), updated_at = CURRENT_TIMESTAMP'
            );

            $pairs = [
                'smtp_enabled' => (string)$smtpEnabled,
                'smtp_host' => $smtpHost,
                'smtp_port' => (string)($smtpPort > 0 ? $smtpPort : 587),
                'smtp_username' => $smtpUsername,
                'smtp_password' => $passwordToStore,
                'smtp_encryption' => $smtpEncryption,
                'smtp_sender_email' => $smtpSenderEmail,
                'smtp_sender_name' => $smtpSenderName !== '' ? $smtpSenderName : 'EasyBooking',
            ];
            $pdo->beginTransaction();
            try {
                foreach ($pairs as $key => $value) {
                    error_log('[notifiche.php SMTP] Salvataggio key=' . $key);
                    $stmt->execute([$key, $value]);
                }
                $pdo->commit();
                error_log('[notifiche.php SMTP] Transazione completata con successo');
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('[notifiche.php SMTP] Errore durante transazione: ' . $e->getMessage());
                throw $e;
            }

            setFlash('success', 'Configurazione SMTP salvata.');
            redirect(notificationRedirectTarget($embedded));
        } catch (Throwable $e) {
            error_log('[notifiche.php SMTP] Throwable [' . get_class($e) . ']: ' . $e->getMessage());
            setFlash('danger', 'Errore durante il salvataggio SMTP. Contatta il supporto se il problema persiste.');
            redirect(notificationRedirectTarget($embedded));
        }
    }

    if ($requestAction === 'test_smtp') {
        verifyCsrf();
        if (!$isAdmin) {
            setFlash('danger', 'Solo un amministratore può testare la connessione SMTP.');
            redirect(notificationRedirectTarget($embedded));
        }
        $testResult = testSmtpConnection(getSmtpConfig($pdo));
        setFlash(!empty($testResult['success']) ? 'success' : 'danger', (string)($testResult['message'] ?? 'Test SMTP completato.'));
        redirect(notificationRedirectTarget($embedded));
    }
}

$config = [
    'email_notifiche' => (string)($user['email'] ?? ''),
    'abilita_email' => 1,
    'reminder_lezioni_enabled' => 1,
    'reminder_lezioni_giorni_prima' => 1,
    'reminder_lezioni_giorno_settimana' => 'lun',
    'reminder_lezioni_ora' => '09:00:00',
    'reminder_lezioni_giorni_futuri' => 7,
    'report_settimanale_enabled' => 0,
    'report_settimanale_giorno' => 'lun',
    'report_settimanale_ora' => '18:00:00',
    'report_settimanale_tipo' => 'lezioni',
    'report_mensile_enabled' => 0,
    'report_mensile_giorno_mese' => 1,
    'report_mensile_ora' => '18:00:00',
    'report_mensile_tipo' => 'lezioni',
    'avviso_scadenza_enabled' => 1,
    'avviso_scadenza_giorni' => 7,
    'avviso_non_confermata_enabled' => 1,
    'avviso_non_confermata_giorni' => 2,
];
$pageError = '';

try {
    error_log('[notifiche.php] Tentativo di caricamento notifiche_config per user_id=' . (int)$user['id']);
    
    $stmt = $pdo->prepare('SELECT * FROM notifiche_config WHERE user_id = ? LIMIT 1');
    $stmt->execute([(int)$user['id']]);
    $row = $stmt->fetch();
    
    if ($row) {
        error_log('[notifiche.php] Configurazione trovata per user_id=' . (int)$user['id']);
        $config = array_merge($config, $row);
        if (empty($config['email_notifiche'])) {
            $config['email_notifiche'] = (string)($user['email'] ?? '');
        }
    } else {
        error_log('[notifiche.php] Nessuna configurazione trovata per user_id=' . (int)$user['id'] . ', usando defaults');
    }
} catch (PDOException $e) {
    error_log('[notifiche.php] PDOException nel caricamento config: ' . $e->getMessage());
    error_log('[notifiche.php] SQLSTATE: ' . $e->errorInfo[0] ?? 'N/A');
    $pageError = 'Impossibile caricare la configurazione notifiche. Dettagli: ' . ($e->errorInfo[2] ?? $e->getMessage());
}

if (is_array($previewConfigOverride)) {
    $config = array_merge($config, $previewConfigOverride);
}

$dayOptions = notificationDayMap();
$reportTypeOptions = notificationReportTypes();
$formAction = notificationRedirectTarget($embedded);
$smtpConfig = getSmtpConfig($pdo);
$emailPreviewTypes = notificationPreviewTypes();
$emailPreviews = [];
foreach ($emailPreviewTypes as $previewType => $previewLabel) {
    try {
        $emailPreviews[$previewType] = buildNotificationEmailPreview($pdo, $user, $config, $previewType, new DateTimeImmutable('now'));
    } catch (Throwable $e) {
        $emailPreviews[$previewType] = [
            'subject' => $previewLabel . ' – errore',
            'summary' => 'Impossibile generare l’anteprima: ' . $e->getMessage(),
            'html' => '<div style="font-family:Arial,sans-serif;padding:24px;"><strong>Errore anteprima:</strong> '
                . h($e->getMessage()) . '</div>',
            'text' => '',
        ];
    }
}

$logStatus = get('log_status', '');
$logType = get('log_type', '');
$logDateFrom = get('log_date_from', '');
$logDateTo = get('log_date_to', '');
$logPage = max(1, sanitizeInt(get('log_page', '1')));
$logPerPage = 20;
$logFilters = [];
if (in_array($logStatus, ['success', 'failed', 'pending'], true)) {
    $logFilters['status'] = $logStatus;
}
if ($logType !== '') {
    $logFilters['notification_type'] = notificationCleanText($logType, 50);
}
if ($logDateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $logDateFrom) === 1) {
    $logFilters['date_from'] = $logDateFrom;
}
if ($logDateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $logDateTo) === 1) {
    $logFilters['date_to'] = $logDateTo;
}
$logResult = getNotificationLogs($logPerPage, ($logPage - 1) * $logPerPage, $logFilters);
$logPagination = paginate((int)$logResult['total'], $logPerPage, $logPage);
$logRows = $logResult['rows'];

$logTypeOptions = [];
try {
    $distinctTypeStmt = $pdo->prepare('SELECT DISTINCT notification_type FROM notification_logs ORDER BY notification_type ASC');
    $distinctTypeStmt->execute();
    $logTypeOptions = $distinctTypeStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable) {
    $logTypeOptions = [];
}

$logBaseQuery = [];
if ($logStatus !== '') { $logBaseQuery['log_status'] = $logStatus; }
if ($logType !== '') { $logBaseQuery['log_type'] = $logType; }
if ($logDateFrom !== '') { $logBaseQuery['log_date_from'] = $logDateFrom; }
if ($logDateTo !== '') { $logBaseQuery['log_date_to'] = $logDateTo; }
$logBaseUrl = 'notifiche.php' . ($embedded ? '?embedded=1' : '');
if ($logBaseQuery) {
    $logBaseUrl .= ($embedded ? '&' : '?') . http_build_query($logBaseQuery);
}

require_once __DIR__ . '/includes/header.php';
?>
<?php if ($embedded): ?>
<style>
.sidebar,
.top-navbar { display: none !important; }
.app-wrapper { display: block; }
.main-content {
    margin: 0 !important;
    min-height: auto;
    padding: 1rem;
}
</style>
<?php endif; ?>
<?php if (!$embedded): ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1">Notifiche</h2>
        <p class="text-secondary mb-0">Configura email, promemoria, report periodici e avvisi critici.</p>
    </div>
</div>
<?php endif; ?>

<?php if ($pageError !== ''): ?>
<div class="alert alert-warning<?= $embedded ? ' m-3 mb-0' : '' ?>">
    <i class="fas fa-exclamation-triangle me-2"></i><?= h($pageError) ?>
</div>
<?php endif; ?>

<div class="row g-4<?= $embedded ? ' p-3' : '' ?>">
    <div class="col-lg-8">
        <form method="post" action="<?= h($formAction) ?>" class="d-grid gap-4">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save">

            <div class="card">
                <div class="card-header"><i class="fas fa-envelope me-2"></i>Email generale</div>
                <div class="card-body row g-3">
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="abilita_email" name="abilita_email" value="1" <?= !empty($config['abilita_email']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="abilita_email">Abilita email notifiche</label>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <label for="email_notifiche" class="form-label">Email per notifiche</label>
                        <input type="email" class="form-control" id="email_notifiche" name="email_notifiche" value="<?= h((string)$config['email_notifiche']) ?>" placeholder="nome@dominio.it">
                        <div class="form-text">Se attivi l'invio email, questo indirizzo diventa obbligatorio.</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><i class="fas fa-calendar-day me-2"></i>Promemoria lezioni</div>
                <div class="card-body row g-3">
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="reminder_lezioni_enabled" name="reminder_lezioni_enabled" value="1" <?= !empty($config['reminder_lezioni_enabled']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="reminder_lezioni_enabled">Attiva promemoria lezioni</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="reminder_lezioni_giorni_prima" class="form-label">Giorni in anticipo</label>
                        <input type="number" class="form-control" id="reminder_lezioni_giorni_prima" name="reminder_lezioni_giorni_prima" min="0" value="<?= h((string)$config['reminder_lezioni_giorni_prima']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="reminder_lezioni_giorno_settimana" class="form-label">Giorno invio</label>
                        <select class="form-select" id="reminder_lezioni_giorno_settimana" name="reminder_lezioni_giorno_settimana">
                            <?php foreach ($dayOptions as $dayKey => $dayLabel): ?>
                            <option value="<?= h($dayKey) ?>" <?= (string)$config['reminder_lezioni_giorno_settimana'] === $dayKey ? 'selected' : '' ?>><?= h($dayLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="reminder_lezioni_ora" class="form-label">Ora invio</label>
                        <input type="time" class="form-control" id="reminder_lezioni_ora" name="reminder_lezioni_ora" value="<?= h(substr((string)$config['reminder_lezioni_ora'], 0, 5)) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="reminder_lezioni_giorni_futuri" class="form-label">Giorni futuri da controllare</label>
                        <input type="number" class="form-control" id="reminder_lezioni_giorni_futuri" name="reminder_lezioni_giorni_futuri" min="1" value="<?= h((string)$config['reminder_lezioni_giorni_futuri']) ?>">
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><i class="fas fa-chart-line me-2"></i>Report periodici</div>
                <div class="card-body row g-4">
                    <div class="col-12">
                        <div class="border rounded p-3">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" id="report_settimanale_enabled" name="report_settimanale_enabled" value="1" <?= !empty($config['report_settimanale_enabled']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="report_settimanale_enabled">Attiva report settimanale</label>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="report_settimanale_giorno" class="form-label">Giorno</label>
                                    <select class="form-select" id="report_settimanale_giorno" name="report_settimanale_giorno">
                                        <?php foreach ($dayOptions as $dayKey => $dayLabel): ?>
                                        <option value="<?= h($dayKey) ?>" <?= (string)$config['report_settimanale_giorno'] === $dayKey ? 'selected' : '' ?>><?= h($dayLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="report_settimanale_ora" class="form-label">Ora invio</label>
                                    <input type="time" class="form-control" id="report_settimanale_ora" name="report_settimanale_ora" value="<?= h(substr((string)$config['report_settimanale_ora'], 0, 5)) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="report_settimanale_tipo" class="form-label">Tipo report</label>
                                    <select class="form-select" id="report_settimanale_tipo" name="report_settimanale_tipo">
                                        <?php foreach ($reportTypeOptions as $typeKey => $typeLabel): ?>
                                        <option value="<?= h($typeKey) ?>" <?= (string)$config['report_settimanale_tipo'] === $typeKey ? 'selected' : '' ?>><?= h($typeLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="border rounded p-3">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" id="report_mensile_enabled" name="report_mensile_enabled" value="1" <?= !empty($config['report_mensile_enabled']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="report_mensile_enabled">Attiva report mensile</label>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="report_mensile_giorno_mese" class="form-label">Giorno del mese</label>
                                    <input type="number" class="form-control" id="report_mensile_giorno_mese" name="report_mensile_giorno_mese" min="1" max="31" value="<?= h((string)$config['report_mensile_giorno_mese']) ?>">
                                    <div class="form-text">Inserisci un valore tra 1 e 31.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="report_mensile_ora" class="form-label">Ora invio</label>
                                    <input type="time" class="form-control" id="report_mensile_ora" name="report_mensile_ora" value="<?= h(substr((string)$config['report_mensile_ora'], 0, 5)) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="report_mensile_tipo" class="form-label">Tipo report</label>
                                    <select class="form-select" id="report_mensile_tipo" name="report_mensile_tipo">
                                        <?php foreach ($reportTypeOptions as $typeKey => $typeLabel): ?>
                                        <option value="<?= h($typeKey) ?>" <?= (string)$config['report_mensile_tipo'] === $typeKey ? 'selected' : '' ?>><?= h($typeLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><i class="fas fa-triangle-exclamation me-2"></i>Avvisi critici</div>
                <div class="card-body row g-4">
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" id="avviso_scadenza_enabled" name="avviso_scadenza_enabled" value="1" <?= !empty($config['avviso_scadenza_enabled']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="avviso_scadenza_enabled">Avviso scadenza pacchetti</label>
                            </div>
                            <label for="avviso_scadenza_giorni" class="form-label">Giorni prima della scadenza</label>
                            <input type="number" class="form-control" id="avviso_scadenza_giorni" name="avviso_scadenza_giorni" min="0" value="<?= h((string)$config['avviso_scadenza_giorni']) ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" id="avviso_non_confermata_enabled" name="avviso_non_confermata_enabled" value="1" <?= !empty($config['avviso_non_confermata_enabled']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="avviso_non_confermata_enabled">Avviso lezione non confermata</label>
                            </div>
                            <label for="avviso_non_confermata_giorni" class="form-label">Giorni prima della lezione</label>
                            <input type="number" class="form-control" id="avviso_non_confermata_giorni" name="avviso_non_confermata_giorni" min="0" value="<?= h((string)$config['avviso_non_confermata_giorni']) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-outline-secondary" name="action" value="preview"><i class="fas fa-eye me-2"></i>Aggiorna anteprima</button>
                <button type="submit" class="btn btn-primary" name="action" value="save"><i class="fas fa-save me-2"></i>Salva notifiche</button>
            </div>
        </form>
    </div>
    <div class="col-lg-4">
        <div class="card border-info">
            <div class="card-header bg-info-subtle"><i class="fas fa-info-circle me-2"></i>Come funzionano</div>
            <div class="card-body">
                <ul class="mb-0 ps-3 d-grid gap-2">
                    <li><strong>Email generale</strong>: abilita o sospende l'invio email per il tuo utente.</li>
                    <li><strong>Promemoria lezioni</strong>: scegli quando inviare il reminder e quanti giorni futuri includere.</li>
                    <li><strong>Report periodici</strong>: attiva settimanale e mensile separatamente, con tipo riepilogo dedicato.</li>
                    <li><strong>Avvisi critici</strong>: gestisci scadenze pacchetti e lezioni non confermate in modo indipendente.</li>
                </ul>
            </div>
        </div>
        <?php if ($isAdmin): ?>
        <div class="card mt-4">
            <div class="card-header"><i class="fas fa-server me-2"></i>Configurazione SMTP</div>
            <form method="post" action="<?= h($formAction) ?>">
                <div class="card-body row g-3">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="save_smtp">
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="smtp_enabled" name="smtp_enabled" value="1" <?= !empty($smtpConfig['enabled']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="smtp_enabled">Abilita SMTP personalizzato</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label for="smtp_host" class="form-label">Host SMTP</label>
                        <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?= h((string)$smtpConfig['host']) ?>" placeholder="smtp.provider.com">
                    </div>
                    <div class="col-6">
                        <label for="smtp_port" class="form-label">Porta</label>
                        <input type="number" class="form-control" id="smtp_port" name="smtp_port" min="1" max="65535" value="<?= h((string)$smtpConfig['port']) ?>">
                    </div>
                    <div class="col-6">
                        <label for="smtp_encryption" class="form-label">Cifratura</label>
                        <select class="form-select" id="smtp_encryption" name="smtp_encryption">
                            <option value="" <?= (string)$smtpConfig['encryption'] === '' ? 'selected' : '' ?>>Nessuna</option>
                            <option value="tls" <?= (string)$smtpConfig['encryption'] === 'tls' ? 'selected' : '' ?>>TLS</option>
                            <option value="ssl" <?= (string)$smtpConfig['encryption'] === 'ssl' ? 'selected' : '' ?>>SSL</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="smtp_username" class="form-label">Username SMTP</label>
                        <input type="text" class="form-control" id="smtp_username" name="smtp_username" value="<?= h((string)$smtpConfig['username']) ?>">
                    </div>
                    <div class="col-12">
                        <label for="smtp_password" class="form-label">Password SMTP</label>
                        <input type="password" class="form-control" id="smtp_password" name="smtp_password" autocomplete="new-password">
                        <div class="form-text">Lascia vuoto per mantenere la password già salvata.</div>
                    </div>
                    <div class="col-12">
                        <label for="smtp_sender_email" class="form-label">Email mittente</label>
                        <input type="email" class="form-control" id="smtp_sender_email" name="smtp_sender_email" value="<?= h((string)$smtpConfig['sender_email']) ?>">
                    </div>
                    <div class="col-12">
                        <label for="smtp_sender_name" class="form-label">Nome mittente</label>
                        <input type="text" class="form-control" id="smtp_sender_name" name="smtp_sender_name" value="<?= h((string)$smtpConfig['sender_name']) ?>">
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Salva SMTP</button>
                </div>
            </form>
        </div>
        <form method="post" action="<?= h($formAction) ?>" class="mt-2">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="test_smtp">
            <button type="submit" class="btn btn-outline-secondary"><i class="fas fa-plug me-2"></i>Test connessione SMTP</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="card mt-4<?= $embedded ? ' mx-3 mb-3' : '' ?>">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="fas fa-envelope-open-text me-2"></i>Anteprima email</span>
        <span class="small text-secondary">Basata sui valori correnti del form e sui dati reali disponibili adesso.</span>
    </div>
    <div class="card-body">
        <ul class="nav nav-tabs" id="notificationPreviewTabs" role="tablist">
            <?php $previewIndex = 0; ?>
            <?php foreach ($emailPreviewTypes as $previewType => $previewLabel): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link<?= $previewIndex === 0 ? ' active' : '' ?>" id="tab-<?= h($previewType) ?>" data-bs-toggle="tab" data-bs-target="#pane-<?= h($previewType) ?>" type="button" role="tab" aria-controls="pane-<?= h($previewType) ?>" aria-selected="<?= $previewIndex === 0 ? 'true' : 'false' ?>">
                    <?= h($previewLabel) ?>
                </button>
            </li>
            <?php $previewIndex++; ?>
            <?php endforeach; ?>
        </ul>
        <div class="tab-content border border-top-0 rounded-bottom p-3 bg-body-tertiary">
            <?php $previewIndex = 0; ?>
            <?php foreach ($emailPreviewTypes as $previewType => $previewLabel): ?>
            <?php $preview = $emailPreviews[$previewType] ?? null; ?>
            <div class="tab-pane fade<?= $previewIndex === 0 ? ' show active' : '' ?>" id="pane-<?= h($previewType) ?>" role="tabpanel" aria-labelledby="tab-<?= h($previewType) ?>">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <div class="fw-semibold"><?= h((string)($preview['subject'] ?? $previewLabel)) ?></div>
                        <div class="small text-secondary"><?= h((string)($preview['summary'] ?? '')) ?></div>
                    </div>
                </div>
                <iframe
                    title="Anteprima <?= h($previewLabel) ?>"
                    class="w-100 border rounded bg-white"
                    style="min-height: 560px;"
                    sandbox=""
                    srcdoc="<?= h((string)($preview['html'] ?? '')) ?>"></iframe>
            </div>
            <?php $previewIndex++; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card mt-4<?= $embedded ? ' mx-3 mb-3' : '' ?>">
    <div class="card-header"><i class="fas fa-list me-2"></i>Log notifiche</div>
    <div class="card-body">
        <form method="get" class="row g-3 mb-3">
            <?php if ($embedded): ?>
            <input type="hidden" name="embedded" value="1">
            <?php endif; ?>
            <div class="col-md-3">
                <label for="log_status" class="form-label">Stato</label>
                <select class="form-select" id="log_status" name="log_status">
                    <option value="">Tutti</option>
                    <option value="success" <?= $logStatus === 'success' ? 'selected' : '' ?>>Successo</option>
                    <option value="failed" <?= $logStatus === 'failed' ? 'selected' : '' ?>>Fallito</option>
                    <option value="pending" <?= $logStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="log_type" class="form-label">Tipo</label>
                <select class="form-select" id="log_type" name="log_type">
                    <option value="">Tutti</option>
                    <?php foreach ($logTypeOptions as $type): ?>
                    <option value="<?= h((string)$type) ?>" <?= $logType === (string)$type ? 'selected' : '' ?>><?= h((string)$type) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="log_date_from" class="form-label">Da</label>
                <input type="date" class="form-control" id="log_date_from" name="log_date_from" value="<?= h($logDateFrom) ?>">
            </div>
            <div class="col-md-2">
                <label for="log_date_to" class="form-label">A</label>
                <input type="date" class="form-control" id="log_date_to" name="log_date_to" value="<?= h($logDateTo) ?>">
            </div>
            <div class="col-md-2 d-grid align-content-end">
                <button type="submit" class="btn btn-outline-primary mt-2">Filtra</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Destinatario</th>
                        <th>Oggetto</th>
                        <th>Server</th>
                        <th>Retry</th>
                        <th>Stato</th>
                        <th>Errore</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logRows)): ?>
                    <tr><td colspan="8" class="text-center text-secondary py-4">Nessun log disponibile.</td></tr>
                    <?php else: ?>
                    <?php foreach ($logRows as $logRow): ?>
                    <tr>
                        <td><?= h(formatDateTime((string)$logRow['sent_at'])) ?></td>
                        <td><?= h((string)$logRow['notification_type']) ?></td>
                        <td><?= h((string)$logRow['recipient_email']) ?></td>
                        <td><?= h((string)$logRow['subject']) ?></td>
                        <td><?= h((string)($logRow['mail_server_used'] ?? '')) ?></td>
                        <td><?= h((string)$logRow['retry_count']) ?></td>
                        <td>
                            <?php $status = (string)$logRow['status']; ?>
                            <span class="badge bg-<?= $status === 'success' ? 'success' : ($status === 'failed' ? 'danger' : 'warning') ?>"><?= h($status) ?></span>
                        </td>
                        <td class="small text-danger"><?= h((string)($logRow['error_message'] ?? '')) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?= renderPagination($logPagination, $logBaseUrl) ?>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php';

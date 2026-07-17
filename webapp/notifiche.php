<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/encryption.php';
require_once __DIR__ . '/includes/auth.php';
requireAuth();
$pdo = Database::getInstance();
$user = currentUser();
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

$requestAction = $_SERVER['REQUEST_METHOD'] === 'POST' ? post('action') : get('action');

if ($requestAction === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
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

        $userId = (int)$user['id'];
        if ($userId <= 0) {
            throw new \RuntimeException('User ID non valido.');
        }

        $params = [
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

        $updateStmt = $pdo->prepare(
            'UPDATE notifiche_config SET
                email_notifiche = ?,
                abilita_email = ?,
                reminder_lezioni_enabled = ?,
                reminder_lezioni_giorni_prima = ?,
                reminder_lezioni_giorno_settimana = ?,
                reminder_lezioni_ora = ?,
                reminder_lezioni_giorni_futuri = ?,
                report_settimanale_enabled = ?,
                report_settimanale_giorno = ?,
                report_settimanale_ora = ?,
                report_settimanale_tipo = ?,
                report_mensile_enabled = ?,
                report_mensile_giorno_mese = ?,
                report_mensile_ora = ?,
                report_mensile_tipo = ?,
                avviso_scadenza_enabled = ?,
                avviso_scadenza_giorni = ?,
                avviso_non_confermata_enabled = ?,
                avviso_non_confermata_giorni = ?
            WHERE user_id = ?'
        );

        $checkStmt = $pdo->prepare('SELECT id FROM notifiche_config WHERE user_id = ? LIMIT 1');
        $checkStmt->execute([$userId]);
        $existingRow = $checkStmt->fetch();

        if ($existingRow) {
            $updateStmt->execute(array_merge($params, [$userId]));
        } else {
            try {
                $insertStmt = $pdo->prepare(
                    'INSERT INTO notifiche_config (
                        user_id, email_notifiche, abilita_email,
                        reminder_lezioni_enabled, reminder_lezioni_giorni_prima, reminder_lezioni_giorno_settimana, reminder_lezioni_ora, reminder_lezioni_giorni_futuri,
                        report_settimanale_enabled, report_settimanale_giorno, report_settimanale_ora, report_settimanale_tipo,
                        report_mensile_enabled, report_mensile_giorno_mese, report_mensile_ora, report_mensile_tipo,
                        avviso_scadenza_enabled, avviso_scadenza_giorni,
                        avviso_non_confermata_enabled, avviso_non_confermata_giorni
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $insertStmt->execute(array_merge([$userId], $params));
            } catch (PDOException $insertEx) {
                // Race condition: another request inserted first; fall back to UPDATE
                if ((string)$insertEx->getCode() === '23000') {
                    $updateStmt->execute(array_merge($params, [$userId]));
                } else {
                    throw $insertEx;
                }
            }
        }

        setFlash('success', 'Preferenze notifiche salvate con successo.');
        redirect(notificationRedirectTarget($embedded));
    } catch (\Throwable $e) {
        error_log('notifiche.php save error [' . $e->getCode() . ']: ' . $e->getMessage());
        setFlash('danger', 'Errore durante il salvataggio delle notifiche. Contatta il supporto se il problema persiste.');
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
    $stmt = $pdo->prepare('SELECT * FROM notifiche_config WHERE user_id = ? LIMIT 1');
    $stmt->execute([(int)$user['id']]);
    $row = $stmt->fetch();
    if ($row) {
        $config = array_merge($config, $row);
        if (empty($config['email_notifiche'])) {
            $config['email_notifiche'] = (string)($user['email'] ?? '');
        }
    }
} catch (PDOException $e) {
    $pageError = 'Impossibile caricare la configurazione notifiche.';
}

$dayOptions = notificationDayMap();
$reportTypeOptions = notificationReportTypes();
$formAction = notificationRedirectTarget($embedded);

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

            <div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Salva notifiche</button>
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
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php';

#!/usr/bin/env php
<?php
/**
 * cron/send-notifications.php
 *
 * Invia le notifiche email configurate da ogni utente in Impostazioni → Notifiche.
 * Pensato per essere eseguito ogni ora, al minuto 0.
 *
 *   0 * * * *   php /home/utente/public_html/webapp/cron/send-notifications.php >> /home/utente/public_html/webapp/cron/send-notifications.log 2>&1
 *
 * Tipi di notifica gestiti (tabella notifiche_config):
 *  - Promemoria lezioni  (reminder_lezioni_*): inviato nel giorno/ora configurati,
 *    con l'elenco delle lezioni programmate nei prossimi N giorni per il proprietario.
 *  - Report settimanale  (report_settimanale_*): inviato nel giorno/ora configurati.
 *  - Report mensile      (report_mensile_*): inviato nel giorno del mese/ora configurati.
 *  - Avviso scadenza pacchetti (avviso_scadenza_*): verificato ogni ora.
 *  - Avviso lezioni non confermate (avviso_non_confermata_*): verificato ogni ora.
 *
 * Ogni invio viene tracciato in una tabella di log (notifiche_log) per evitare
 * invii duplicati nella stessa ora/giorno.
 */

declare(strict_types=1);

$__isCli = (PHP_SAPI === 'cli');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/encryption.php';

function cronAuthorizeHttp(): void
{
    $secret = trim((string) (getenv('CRON_SECRET') ?: ''));
    if ($secret === '') {
        http_response_code(403);
        echo "Accesso negato: CRON_SECRET non configurato.\n";
        exit(1);
    }

    $token = trim((string) ($_GET['cron_token'] ?? ''));
    if ($token === '' || !hash_equals($secret, $token)) {
        http_response_code(403);
        echo "Accesso negato: token non valido.\n";
        exit(1);
    }
}

function cronLog(string $message): void
{
    echo '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
}

/**
 * Assicura che esista la tabella di log usata per evitare invii duplicati
 * nella stessa finestra oraria/giornaliera.
 */
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

/**
 * Registra l'invio per il periodo corrente. Restituisce false se era già stato
 * inviato (grazie al vincolo UNIQUE), evitando così invii duplicati.
 */
function markNotificationSent(PDO $pdo, int $userId, string $tipo, string $riferimento): bool
{
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO notifiche_log (user_id, tipo, riferimento) VALUES (?, ?, ?)'
        );
        $stmt->execute([$userId, $tipo, $riferimento]);
        return true;
    } catch (PDOException $e) {
        // Violazione vincolo UNIQUE => già inviata in questo periodo
        return false;
    }
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

/**
 * Invia il promemoria lezioni per l'utente, se configurato per l'ora/giorno correnti.
 */
function processReminderLezioni(PDO $pdo, array $user, array $config, DateTimeImmutable $now): void
{
    if (empty($config['reminder_lezioni_enabled']) || empty($config['abilita_email']) || empty($config['email_notifiche'])) {
        return;
    }

    $currentDayKey = italianDayKeyForDate($now);
    $configuredDay = (string)($config['reminder_lezioni_giorno_settimana'] ?? 'lun');
    $configuredHour = substr((string)($config['reminder_lezioni_ora'] ?? '09:00:00'), 0, 5);
    $currentHour = $now->format('H:i');

    if ($currentDayKey !== $configuredDay || $currentHour !== $configuredHour) {
        return;
    }

    $riferimento = $now->format('Y-m-d');
    if (!markNotificationSent($pdo, (int)$user['id'], 'reminder_lezioni', $riferimento)) {
        return;
    }

    $giorniFuturi = max(1, (int)($config['reminder_lezioni_giorni_futuri'] ?? 7));
    $dataLimite = $now->modify("+{$giorniFuturi} days")->format('Y-m-d');
    $oggi = $now->format('Y-m-d');

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

    if (empty($lezioni)) {
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

    sendEmail((string)$config['email_notifiche'], 'Promemoria lezioni – EasyBooking', $body);
    cronLog("Promemoria lezioni inviato a utente #{$user['id']} (" . count($lezioni) . ' lezioni)');
}

/**
 * Genera e invia un riepilogo (lezioni/clienti/incassi) per il periodo indicato.
 */
function buildReportBody(PDO $pdo, string $tipo, string $dataInizio, string $dataFine): string
{
    switch ($tipo) {
        case 'clienti':
            $stmt = $pdo->prepare(
                "SELECT COUNT(DISTINCT c.id) AS totale_clienti, COUNT(p.id) AS totale_lezioni
                 FROM prenotazioni p
                 INNER JOIN clienti c ON c.id = p.cliente_id
                 WHERE p.data BETWEEN ? AND ?"
            );
            $stmt->execute([$dataInizio, $dataFine]);
            $row = $stmt->fetch() ?: ['totale_clienti' => 0, 'totale_lezioni' => 0];
            return '<p>Clienti coinvolti: <strong>' . (int)$row['totale_clienti'] . '</strong></p>'
                 . '<p>Lezioni totali: <strong>' . (int)$row['totale_lezioni'] . '</strong></p>';

        case 'incassi':
            $stmt = $pdo->prepare(
                "SELECT COALESCE(SUM(importo_pagato), 0) AS totale
                 FROM acquisti
                 WHERE data_acquisto BETWEEN ? AND ?"
            );
            $stmt->execute([$dataInizio, $dataFine]);
            $totale = (float)($stmt->fetchColumn() ?: 0);
            return '<p>Incassi nel periodo: <strong>€ ' . number_format($totale, 2, ',', '.') . '</strong></p>';

        case 'lezioni':
        default:
            $stmt = $pdo->prepare(
                "SELECT stato, COUNT(*) AS totale
                 FROM prenotazioni
                 WHERE data BETWEEN ? AND ?
                 GROUP BY stato"
            );
            $stmt->execute([$dataInizio, $dataFine]);
            $rows = $stmt->fetchAll();
            if (empty($rows)) {
                return '<p>Nessuna lezione nel periodo selezionato.</p>';
            }
            $html = '<ul>';
            foreach ($rows as $r) {
                $html .= '<li>' . h((string)$r['stato']) . ': ' . (int)$r['totale'] . '</li>';
            }
            $html .= '</ul>';
            return $html;
    }
}

function processReportSettimanale(PDO $pdo, array $user, array $config, DateTimeImmutable $now): void
{
    if (empty($config['report_settimanale_enabled']) || empty($config['abilita_email']) || empty($config['email_notifiche'])) {
        return;
    }

    $currentDayKey = italianDayKeyForDate($now);
    $configuredDay = (string)($config['report_settimanale_giorno'] ?? 'lun');
    $configuredHour = substr((string)($config['report_settimanale_ora'] ?? '18:00:00'), 0, 5);
    $currentHour = $now->format('H:i');

    if ($currentDayKey !== $configuredDay || $currentHour !== $configuredHour) {
        return;
    }

    $riferimento = $now->format('o-\WW'); // anno-settimana ISO
    if (!markNotificationSent($pdo, (int)$user['id'], 'report_settimanale', $riferimento)) {
        return;
    }

    $dataFine = $now->format('Y-m-d');
    $dataInizio = $now->modify('-6 days')->format('Y-m-d');
    $tipo = (string)($config['report_settimanale_tipo'] ?? 'lezioni');

    $body = '<p>Ciao,</p><p>Ecco il report settimanale (' . h($dataInizio) . ' – ' . h($dataFine) . '):</p>'
        . buildReportBody($pdo, $tipo, $dataInizio, $dataFine)
        . '<p>Questo è un messaggio automatico generato da EasyBooking.</p>';

    sendEmail((string)$config['email_notifiche'], 'Report settimanale – EasyBooking', $body);
    cronLog("Report settimanale ({$tipo}) inviato a utente #{$user['id']}");
}

function processReportMensile(PDO $pdo, array $user, array $config, DateTimeImmutable $now): void
{
    if (empty($config['report_mensile_enabled']) || empty($config['abilita_email']) || empty($config['email_notifiche'])) {
        return;
    }

    $configuredDay = (int)($config['report_mensile_giorno_mese'] ?? 1);
    $configuredHour = substr((string)($config['report_mensile_ora'] ?? '18:00:00'), 0, 5);
    $currentHour = $now->format('H:i');
    $currentDay = (int)$now->format('j');
    $lastDayOfMonth = (int)$now->format('t');

    // Se il giorno configurato supera i giorni del mese corrente (es. 31 in Febbraio),
    // si invia l'ultimo giorno del mese.
    $effectiveDay = min($configuredDay, $lastDayOfMonth);

    if ($currentDay !== $effectiveDay || $currentHour !== $configuredHour) {
        return;
    }

    $riferimento = $now->format('Y-m');
    if (!markNotificationSent($pdo, (int)$user['id'], 'report_mensile', $riferimento)) {
        return;
    }

    $dataInizio = $now->format('Y-m-01');
    $dataFine = $now->format('Y-m-t');
    $tipo = (string)($config['report_mensile_tipo'] ?? 'lezioni');

    $body = '<p>Ciao,</p><p>Ecco il report mensile (' . h($dataInizio) . ' – ' . h($dataFine) . '):</p>'
        . buildReportBody($pdo, $tipo, $dataInizio, $dataFine)
        . '<p>Questo è un messaggio automatico generato da EasyBooking.</p>';

    sendEmail((string)$config['email_notifiche'], 'Report mensile – EasyBooking', $body);
    cronLog("Report mensile ({$tipo}) inviato a utente #{$user['id']}");
}

function processAvvisoScadenzaPacchetti(PDO $pdo, array $user, array $config, DateTimeImmutable $now): void
{
    if (empty($config['avviso_scadenza_enabled']) || empty($config['abilita_email']) || empty($config['email_notifiche'])) {
        return;
    }

    $giorniPrima = max(0, (int)($config['avviso_scadenza_giorni'] ?? 7));
    $dataLimite = $now->modify("+{$giorniPrima} days")->format('Y-m-d');
    $oggi = $now->format('Y-m-d');

    // Pacchetti la cui ultima lezione programmata scade entro i giorni configurati.
    $stmt = $pdo->prepare(
        "SELECT a.id AS acquisto_id, c.nome AS cliente_nome, c.cognome AS cliente_cognome, MAX(p.data) AS scadenza
         FROM acquisti a
         INNER JOIN clienti c ON c.id = a.cliente_id
         INNER JOIN prenotazioni p ON p.acquisto_id = a.id
         WHERE p.stato = 'Programmata'
         GROUP BY a.id, c.nome, c.cognome
         HAVING scadenza BETWEEN ? AND ?"
    );
    $stmt->execute([$oggi, $dataLimite]);
    $pacchetti = $stmt->fetchAll();

    if (empty($pacchetti)) {
        return;
    }

    $riferimento = $now->format('Y-m-d-H');
    if (!markNotificationSent($pdo, (int)$user['id'], 'avviso_scadenza', $riferimento)) {
        return;
    }

    $rows = '';
    foreach ($pacchetti as $pk) {
        $clienteNome = trim(decryptField((string)($pk['cliente_nome'] ?? '')) . ' ' . decryptField((string)($pk['cliente_cognome'] ?? '')));
        $scadenzaFmt = italianDateLabel(new DateTimeImmutable((string)$pk['scadenza']));
        $rows .= '<li>' . h($clienteNome !== '' ? $clienteNome : 'Cliente') . ' – scadenza: ' . h($scadenzaFmt) . '</li>';
    }

    $body = '<p>Ciao,</p><p>I seguenti pacchetti stanno per esaurirsi (entro ' . (int)$giorniPrima . ' giorni):</p>'
        . '<ul>' . $rows . '</ul>'
        . '<p>Questo è un messaggio automatico generato da EasyBooking.</p>';

    sendEmail((string)$config['email_notifiche'], 'Avviso scadenza pacchetti – EasyBooking', $body);
    cronLog("Avviso scadenza pacchetti inviato a utente #{$user['id']} (" . count($pacchetti) . ' pacchetti)');
}

function processAvvisoLezioniNonConfermate(PDO $pdo, array $user, array $config, DateTimeImmutable $now): void
{
    if (empty($config['avviso_non_confermata_enabled']) || empty($config['abilita_email']) || empty($config['email_notifiche'])) {
        return;
    }

    $giorniPrima = max(0, (int)($config['avviso_non_confermata_giorni'] ?? 2));
    $dataLimite = $now->modify("+{$giorniPrima} days")->format('Y-m-d');
    $oggi = $now->format('Y-m-d');

    // "Non confermata" = lezione ancora in stato "Programmata" che si avvicina
    // senza essere stata gestita (nessun campo di conferma esplicito nello schema
    // attuale: qui si considerano tutte le lezioni Programmata in scadenza).
    $stmt = $pdo->prepare(
        "SELECT p.data, p.ora_inizio, c.nome AS cliente_nome, c.cognome AS cliente_cognome
         FROM prenotazioni p
         INNER JOIN clienti c ON c.id = p.cliente_id
         WHERE p.stato = 'Programmata'
           AND p.data BETWEEN ? AND ?
         ORDER BY p.data ASC, p.ora_inizio ASC"
    );
    $stmt->execute([$oggi, $dataLimite]);
    $lezioni = $stmt->fetchAll();

    if (empty($lezioni)) {
        return;
    }

    $riferimento = $now->format('Y-m-d-H');
    if (!markNotificationSent($pdo, (int)$user['id'], 'avviso_non_confermata', $riferimento)) {
        return;
    }

    $rows = '';
    foreach ($lezioni as $lezione) {
        $clienteNome = trim(decryptField((string)($lezione['cliente_nome'] ?? '')) . ' ' . decryptField((string)($lezione['cliente_cognome'] ?? '')));
        $dataFmt = italianDateLabel(new DateTimeImmutable((string)$lezione['data']));
        $rows .= '<li>' . h($dataFmt) . ' – ore ' . h(substr((string)$lezione['ora_inizio'], 0, 5))
            . ' – ' . h($clienteNome !== '' ? $clienteNome : 'Cliente') . '</li>';
    }

    $body = '<p>Ciao,</p><p>Le seguenti lezioni si avvicinano (entro ' . (int)$giorniPrima . ' giorni) e potrebbero necessitare conferma:</p>'
        . '<ul>' . $rows . '</ul>'
        . '<p>Questo è un messaggio automatico generato da EasyBooking.</p>';

    sendEmail((string)$config['email_notifiche'], 'Avviso lezioni da confermare – EasyBooking', $body);
    cronLog("Avviso lezioni non confermate inviato a utente #{$user['id']} (" . count($lezioni) . ' lezioni)');
}

if (!$__isCli) {
    cronAuthorizeHttp();
    header('Content-Type: text/plain; charset=utf-8');
}

cronLog('Avvio send-notifications.php');

try {
    $pdo = Database::getInstance();
    ensureNotificheLogTable($pdo);

    $now = new DateTimeImmutable('now');

    $stmt = $pdo->query(
        "SELECT nc.*, u.email AS user_email
         FROM notifiche_config nc
         INNER JOIN users u ON u.id = nc.user_id"
    );
    $configs = $stmt->fetchAll();

    if (empty($configs)) {
        cronLog('Nessuna configurazione notifiche trovata. Fine.');
        exit(0);
    }

    foreach ($configs as $config) {
        $user = ['id' => (int)$config['user_id'], 'email' => (string)$config['user_email']];

        try {
            processReminderLezioni($pdo, $user, $config, $now);
            processReportSettimanale($pdo, $user, $config, $now);
            processReportMensile($pdo, $user, $config, $now);
            processAvvisoScadenzaPacchetti($pdo, $user, $config, $now);
            processAvvisoLezioniNonConfermate($pdo, $user, $config, $now);
        } catch (Throwable $e) {
            cronLog("ERRORE durante l'elaborazione per utente #{$user['id']}: " . $e->getMessage());
        }
    }

    cronLog('Completato invio notifiche per ' . count($configs) . ' utente/i.');
} catch (Throwable $e) {
    cronLog('ERRORE FATALE: ' . $e->getMessage());
    if (!$__isCli) {
        http_response_code(500);
    }
    exit(1);
}

cronLog('Fine send-notifications.php');
exit(0);

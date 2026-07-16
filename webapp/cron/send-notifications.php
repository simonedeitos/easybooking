<?php
/**
 * cron/send-notifications.php
 *
 * Cron job: processes all active notification preferences and sends emails.
 *
 * Handles:
 *  1. Promemoria Lezioni (lesson reminders)
 *  2. Report Settimanale (weekly report)
 *  3. Report Mensile    (monthly report)
 *  4. Avvisi Critici   (package expiry + unconfirmed lessons)
 *
 * Recommended Hostinger cron schedules (run all 4 checks in one command):
 *   0 * * * *   php /path/to/webapp/cron/send-notifications.php >> /path/to/webapp/cron/send-notifications.log 2>&1
 * (runs every hour – the script self-checks whether the right time/day has come)
 *
 * Requires PHP mail() configured, or adjust sendMail() to use SMTP.
 */

declare(strict_types=1);

// ── Guard ──────────────────────────────────────────────────────────────────
if (PHP_SAPI !== 'cli') {
    $token    = $_GET['cron_token'] ?? '';
    $expected = defined('CRON_SECRET') ? CRON_SECRET : (getenv('CRON_SECRET') ?: '');
    if ($expected === '' || !hash_equals($expected, $token)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

// ── Bootstrap ─────────────────────────────────────────────────────────────
$webappDir = dirname(__DIR__);
require_once $webappDir . '/config/database.php';
require_once $webappDir . '/config/functions.php';

// ── Helpers ────────────────────────────────────────────────────────────────

function cronLog(string $msg): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    echo $line;
    error_log($line);
}

/**
 * Sends an email via PHP mail().
 * Replace with PHPMailer/SMTP if needed.
 */
function sendMail(string $to, string $subject, string $body, string $fromName = 'EasyBooking'): bool
{
    $from    = 'noreply@easybooking.local';
    $headers = "From: {$fromName} <{$from}>\r\n"
        . "Reply-To: {$from}\r\n"
        . "MIME-Version: 1.0\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n"
        . "X-Mailer: EasyBooking-Cron\r\n";
    return mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
}

/**
 * Maps Italian 3-letter day abbreviation to PHP date('D') or numeric format.
 * Returns the ISO day of week (1=Mon…7=Sun) for 'N' format.
 */
function dayAbbrToIso(string $abbr): int
{
    return match($abbr) {
        'lun' => 1, 'mar' => 2, 'mer' => 3,
        'gio' => 4, 'ven' => 5, 'sab' => 6, 'dom' => 7,
        default => 1,
    };
}

/**
 * Returns true if the current time's ISO weekday matches $abbr
 * AND the current hour:minute is within 30 minutes of $timeStr.
 */
function isRightDayAndTime(string $abbr, string $timeStr): bool
{
    $now = new DateTime();
    if ((int)$now->format('N') !== dayAbbrToIso($abbr)) {
        return false;
    }
    [$h, $m] = explode(':', substr($timeStr, 0, 5));
    $target  = (int)$h * 60 + (int)$m;
    $current = (int)$now->format('G') * 60 + (int)$now->format('i');
    // Allow a 59-minute window (cron runs hourly)
    return abs($current - $target) <= 59;
}

/**
 * Returns true if today's day-of-month matches $dayOfMonth
 * AND the current hour:minute is within 59 minutes of $timeStr.
 */
function isRightDayOfMonthAndTime(int $dayOfMonth, string $timeStr): bool
{
    $now = new DateTime();
    if ((int)$now->format('j') !== $dayOfMonth) {
        return false;
    }
    [$h, $m] = explode(':', substr($timeStr, 0, 5));
    $target  = (int)$h * 60 + (int)$m;
    $current = (int)$now->format('G') * 60 + (int)$now->format('i');
    return abs($current - $target) <= 59;
}

function formatDateIt(string $date): string
{
    try {
        return (new DateTime($date))->format('d/m/Y');
    } catch (Exception) {
        return $date;
    }
}

// ── Main ───────────────────────────────────────────────────────────────────

try {
    $pdo = Database::getInstance();
} catch (Throwable $e) {
    cronLog('ERRORE connessione DB: ' . $e->getMessage());
    exit(1);
}

// Fetch all notification configs for users who have email enabled
$stmt = $pdo->query(
    'SELECT nc.*, u.email AS user_email
     FROM notifiche_config nc
     JOIN users u ON u.id = nc.user_id
     WHERE nc.abilita_email = 1'
);
$configs = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($configs as $cfg) {
    $email = $cfg['email_notifiche'] ?: $cfg['user_email'];
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        cronLog('Salto user_id=' . $cfg['user_id'] . ': email non valida.');
        continue;
    }

    // ── 1. Promemoria Lezioni ─────────────────────────────────────────────
    if ($cfg['reminder_lezioni_enabled']) {
        if (isRightDayAndTime($cfg['reminder_lezioni_giorno_settimana'], $cfg['reminder_lezioni_ora'])) {
            $giorniFuturi  = max(1, (int)$cfg['reminder_lezioni_giorni_futuri']);
            $dataInizio    = date('Y-m-d');
            $dataFine      = date('Y-m-d', strtotime("+{$giorniFuturi} days"));

            $s = $pdo->prepare(
                "SELECT p.data, p.ora_inizio, p.ora_fine, p.strumento, p.stato,
                        CONCAT(c.nome,' ',c.cognome) AS cliente,
                        CONCAT(i.nome,' ',i.cognome) AS insegnante
                 FROM prenotazioni p
                 JOIN clienti c ON c.id = p.cliente_id
                 JOIN insegnanti i ON i.id = p.insegnante_id
                 WHERE p.data BETWEEN ? AND ?
                   AND p.stato IN ('Programmata','Riprogrammata','Rimandata')
                 ORDER BY p.data, p.ora_inizio"
            );
            $s->execute([$dataInizio, $dataFine]);
            $lezioni = $s->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($lezioni)) {
                $body  = "Promemoria lezioni – prossimi {$giorniFuturi} giorni\n\n";
                foreach ($lezioni as $l) {
                    $body .= sprintf(
                        "• %s | %s - %s | %s | %s | (%s)\n",
                        formatDateIt($l['data']),
                        substr($l['ora_inizio'], 0, 5),
                        substr($l['ora_fine'], 0, 5),
                        $l['cliente'],
                        $l['strumento'] ?? '—',
                        $l['stato']
                    );
                }
                if (sendMail($email, 'EasyBooking – Promemoria lezioni', $body)) {
                    cronLog("Promemoria lezioni inviato a {$email} (" . count($lezioni) . ' lezioni).');
                } else {
                    cronLog("Errore invio promemoria lezioni a {$email}.");
                }
            } else {
                cronLog("Promemoria lezioni: nessuna lezione nei prossimi {$giorniFuturi} giorni.");
            }
        }
    }

    // ── 2. Report Settimanale ─────────────────────────────────────────────
    if ($cfg['report_settimanale_enabled']) {
        if (isRightDayAndTime($cfg['report_settimanale_giorno'], $cfg['report_settimanale_ora'])) {
            $tipo      = $cfg['report_settimanale_tipo'];
            // Report covers the previous complete week (Mon–Sun)
            $lunedi    = date('Y-m-d', strtotime('monday last week'));
            $domenica  = date('Y-m-d', strtotime('sunday last week'));

            $body  = "Report settimanale – " . formatDateIt($lunedi) . " / " . formatDateIt($domenica) . "\n\n";

            switch ($tipo) {
                case 'lezioni':
                    $s = $pdo->prepare(
                        "SELECT COUNT(*) AS totale,
                                SUM(CASE WHEN stato='Svolta' THEN 1 ELSE 0 END) AS svolte,
                                SUM(CASE WHEN stato='Assente' THEN 1 ELSE 0 END) AS assenti,
                                SUM(CASE WHEN stato='Rimandata' THEN 1 ELSE 0 END) AS rimandate
                         FROM prenotazioni WHERE data BETWEEN ? AND ?"
                    );
                    $s->execute([$lunedi, $domenica]);
                    $r = $s->fetch(PDO::FETCH_ASSOC);
                    $body .= "Lezioni totali:  {$r['totale']}\n";
                    $body .= "  Svolte:        {$r['svolte']}\n";
                    $body .= "  Assenti:       {$r['assenti']}\n";
                    $body .= "  Rimandate:     {$r['rimandate']}\n";
                    break;
                case 'clienti':
                    $s    = $pdo->prepare("SELECT COUNT(*) FROM clienti WHERE DATE(created_at) BETWEEN ? AND ?");
                    $s->execute([$lunedi, $domenica]);
                    $nuovi = (int)$s->fetchColumn();
                    $tot   = (int)$pdo->query("SELECT COUNT(*) FROM clienti")->fetchColumn();
                    $body .= "Nuovi clienti questa settimana: {$nuovi}\n";
                    $body .= "Totale clienti:                 {$tot}\n";
                    break;
                case 'incassi':
                    $s = $pdo->prepare(
                        "SELECT COALESCE(SUM(importo_pagato),0) AS totale
                         FROM acquisti WHERE data_acquisto BETWEEN ? AND ? AND stato_pagamento = 'Pagato'"
                    );
                    $s->execute([$lunedi, $domenica]);
                    $incasso = number_format((float)$s->fetchColumn(), 2, ',', '.');
                    $body .= "Incassi settimana: € {$incasso}\n";
                    break;
            }

            if (sendMail($email, 'EasyBooking – Report settimanale', $body)) {
                cronLog("Report settimanale ({$tipo}) inviato a {$email}.");
            } else {
                cronLog("Errore invio report settimanale a {$email}.");
            }
        }
    }

    // ── 3. Report Mensile ─────────────────────────────────────────────────
    if ($cfg['report_mensile_enabled']) {
        if (isRightDayOfMonthAndTime((int)$cfg['report_mensile_giorno_mese'], $cfg['report_mensile_ora'])) {
            $tipo        = $cfg['report_mensile_tipo'];
            // Report covers the previous complete month
            $primaMese   = date('Y-m-01', strtotime('first day of last month'));
            $ultimoMese  = date('Y-m-t', strtotime('last day of last month'));

            $body  = "Report mensile precedente – " . formatDateIt($primaMese) . " / " . formatDateIt($ultimoMese) . "\n\n";

            switch ($tipo) {
                case 'lezioni':
                    $s = $pdo->prepare(
                        "SELECT COUNT(*) AS totale,
                                SUM(CASE WHEN stato='Svolta' THEN 1 ELSE 0 END) AS svolte,
                                SUM(CASE WHEN stato='Assente' THEN 1 ELSE 0 END) AS assenti
                         FROM prenotazioni WHERE data BETWEEN ? AND ?"
                    );
                    $s->execute([$primaMese, $ultimoMese]);
                    $r = $s->fetch(PDO::FETCH_ASSOC);
                    $body .= "Lezioni totali:  {$r['totale']}\n";
                    $body .= "  Svolte:        {$r['svolte']}\n";
                    $body .= "  Assenti:       {$r['assenti']}\n";
                    break;
                case 'clienti':
                    $s = $pdo->prepare("SELECT COUNT(*) FROM clienti WHERE DATE(created_at) BETWEEN ? AND ?");
                    $s->execute([$primaMese, $ultimoMese]);
                    $nuovi = (int)$s->fetchColumn();
                    $tot   = (int)$pdo->query("SELECT COUNT(*) FROM clienti")->fetchColumn();
                    $body .= "Nuovi clienti questo mese: {$nuovi}\n";
                    $body .= "Totale clienti:            {$tot}\n";
                    break;
                case 'incassi':
                    $s = $pdo->prepare(
                        "SELECT COALESCE(SUM(importo_pagato),0) AS totale
                         FROM acquisti WHERE data_acquisto BETWEEN ? AND ? AND stato_pagamento = 'Pagato'"
                    );
                    $s->execute([$primaMese, $ultimoMese]);
                    $incasso = number_format((float)$s->fetchColumn(), 2, ',', '.');
                    $body .= "Incassi mese: € {$incasso}\n";
                    break;
            }

            if (sendMail($email, 'EasyBooking – Report mensile', $body)) {
                cronLog("Report mensile ({$tipo}) inviato a {$email}.");
            } else {
                cronLog("Errore invio report mensile a {$email}.");
            }
        }
    }

    // ── 4. Avvisi Critici ─────────────────────────────────────────────────

    // 4a. Scadenza pacchetti (acquisti)
    if ($cfg['avviso_scadenza_enabled']) {
        $giorniAvviso = max(0, (int)$cfg['avviso_scadenza_giorni']);
        $dataLimite   = date('Y-m-d', strtotime("+{$giorniAvviso} days"));

        // Find clients whose last lesson of a purchased package is within $giorniAvviso days
        $s = $pdo->prepare(
            "SELECT c.id AS cliente_id,
                    CONCAT(c.nome,' ',c.cognome) AS cliente,
                    a.id AS acquisto_id,
                    a.data_acquisto,
                    p.nome AS pacchetto,
                    a.numero_lezioni,
                    (SELECT COUNT(*) FROM prenotazioni pr WHERE pr.acquisto_id = a.id AND pr.stato='Svolta') AS lezioni_svolte,
                    (a.numero_lezioni - (SELECT COUNT(*) FROM prenotazioni pr WHERE pr.acquisto_id = a.id AND pr.stato='Svolta')) AS lezioni_rimanenti
             FROM acquisti a
             JOIN clienti c ON c.id = a.cliente_id
             LEFT JOIN pacchetti p ON p.id = a.pacchetto_id
             WHERE a.stato_pagamento = 'Pagato'
               AND (a.numero_lezioni - (SELECT COUNT(*) FROM prenotazioni pr2 WHERE pr2.acquisto_id = a.id AND pr2.stato='Svolta'))
                   BETWEEN 0 AND 2
             ORDER BY c.cognome, c.nome"
        );
        $s->execute();
        $scadenze = $s->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($scadenze)) {
            $body = "⚠️ Avviso scadenza pacchetti – lezioni in esaurimento\n\n";
            foreach ($scadenze as $sc) {
                $body .= sprintf(
                    "• %s | Pacchetto: %s | Lezioni rimanenti: %d\n",
                    $sc['cliente'],
                    $sc['pacchetto'] ?? '—',
                    max(0, (int)$sc['lezioni_rimanenti'])
                );
            }
            if (sendMail($email, 'EasyBooking – Avviso scadenza pacchetti', $body)) {
                cronLog("Avviso scadenza pacchetti inviato a {$email} (" . count($scadenze) . ' clienti).');
            } else {
                cronLog("Errore invio avviso scadenza a {$email}.");
            }
        }
    }

    // 4b. Lezioni non confermate
    if ($cfg['avviso_non_confermata_enabled']) {
        $giorniAvviso = max(0, (int)$cfg['avviso_non_confermata_giorni']);
        $dataLimite   = date('Y-m-d', strtotime("+{$giorniAvviso} days"));

        $s = $pdo->prepare(
            "SELECT p.id, p.data, p.ora_inizio, p.strumento,
                    CONCAT(c.nome,' ',c.cognome) AS cliente
             FROM prenotazioni p
             JOIN clienti c ON c.id = p.cliente_id
             WHERE p.data BETWEEN CURDATE() AND ?
               AND p.stato = 'Programmata'
             ORDER BY p.data, p.ora_inizio"
        );
        $s->execute([$dataLimite]);
        $nonConfirmate = $s->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($nonConfirmate)) {
            $body = "⚠️ Lezioni non ancora confermate nei prossimi {$giorniAvviso} giorni\n\n";
            foreach ($nonConfirmate as $l) {
                $body .= sprintf(
                    "• %s | %s | %s | %s\n",
                    formatDateIt($l['data']),
                    substr($l['ora_inizio'], 0, 5),
                    $l['cliente'],
                    $l['strumento'] ?? '—'
                );
            }
            if (sendMail($email, 'EasyBooking – Lezioni non confermate', $body)) {
                cronLog("Avviso non confermate inviato a {$email} (" . count($nonConfirmate) . ' lezioni).');
            } else {
                cronLog("Errore invio avviso non confermate a {$email}.");
            }
        }
    }
}

cronLog('Cron send-notifications completato.');

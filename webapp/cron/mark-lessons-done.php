#!/usr/bin/env php
<?php
/**
 * cron/mark-lessons-done.php
 *
 * Segna automaticamente come "Svolta" tutte le lezioni (prenotazioni) la cui
 * data è passata (< oggi) e il cui stato è ancora "Programmata", "Riprogrammata"
 * o "Rimandata".
 *
 * Esecuzione consigliata: ogni giorno alle 00:05
 *   5 0 * * *   php /home/utente/public_html/webapp/cron/mark-lessons-done.php >> /home/utente/public_html/webapp/cron/mark-lessons-done.log 2>&1
 *
 * Può anche essere chiamato via HTTP (es. da un servizio esterno di cron)
 * passando ?cron_token=<CRON_SECRET> definito in .env. In quel caso la
 * cartella cron/ deve comunque restare protetta da .htaccess: il token è
 * una protezione aggiuntiva, non l'unica.
 */

declare(strict_types=1);

$__isCli = (PHP_SAPI === 'cli');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

/**
 * Verifica l'autorizzazione quando lo script viene invocato via HTTP.
 * Se CRON_SECRET non è configurato, l'accesso via HTTP resta bloccato
 * dall'.htaccess della cartella cron/ (difesa in profondità).
 */
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
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    echo $line;
}

if (!$__isCli) {
    cronAuthorizeHttp();
    header('Content-Type: text/plain; charset=utf-8');
}

cronLog('Avvio mark-lessons-done.php');

try {
    $pdo = Database::getInstance();

    $stmt = $pdo->prepare(
        "UPDATE prenotazioni
         SET stato = 'Svolta'
         WHERE data < CURDATE()
           AND stato IN ('Programmata', 'Riprogrammata', 'Rimandata')"
    );
    $stmt->execute();
    $updated = $stmt->rowCount();

    cronLog("Completato: {$updated} lezione/i aggiornate a 'Svolta'.");
} catch (Throwable $e) {
    cronLog('ERRORE: ' . $e->getMessage());
    if (!$__isCli) {
        http_response_code(500);
    }
    exit(1);
}

cronLog('Fine mark-lessons-done.php');
exit(0);

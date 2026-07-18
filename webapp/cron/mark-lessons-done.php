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
 *
 * Test via browser (con .htaccess disabilitato):
 *   http://localhost/webapp/cron/mark-lessons-done.php?cron_token=<CRON_SECRET>
 * In caso di errore 500, controllare webapp/cron/php-error.log per i dettagli.
 */

declare(strict_types=1);

$__isCli = (PHP_SAPI === 'cli');

// ── Configurazione errori per modalità HTTP ──────────────────────────────────
// L'output buffering garantisce che gli header HTTP possano essere inviati
// anche se PHP genera notice/warning prima della chiamata a header().
// In CLI l'output buffering non è necessario.
if (!$__isCli) {
    ob_start();
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/php-error.log');
    error_reporting(E_ALL);
}

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

/**
 * Verifica che le variabili d'ambiente obbligatorie siano presenti.
 * Termina lo script con un messaggio chiaro se mancano configurazioni critiche.
 */
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
            error_log('[mark-lessons-done] ' . trim($msg));
        } else {
            fwrite(STDERR, $msg);
        }
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

validateCronEnvironment($__isCli === false);
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
    $errMsg = $e->getMessage();
    cronLog('ERRORE: ' . $errMsg);
    if (!$__isCli) {
        error_log('[mark-lessons-done] ERRORE: ' . $errMsg . ' in ' . $e->getFile() . ':' . $e->getLine());
        http_response_code(500);
    }
    exit(1);
}

cronLog('Fine mark-lessons-done.php');
exit(0);

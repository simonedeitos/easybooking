<?php
/**
 * cron/mark-lessons-done.php
 *
 * Cron job: mark yesterday's lessons that are still "Programmata", "Rimandata"
 * or "Riprogrammata" as "Svolta".
 *
 * Recommended schedule on Hostinger (or any cPanel cron):
 *   5 0 * * *   php /path/to/webapp/cron/mark-lessons-done.php >> /path/to/webapp/cron/mark-lessons-done.log 2>&1
 *
 * That means: every day at 00:05.
 */

declare(strict_types=1);

// ── Guard: only allow CLI or a secret token to prevent web abuse ───────────
if (PHP_SAPI !== 'cli') {
    $token = $_GET['cron_token'] ?? '';
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

// ── Main logic ────────────────────────────────────────────────────────────
$ieri    = (new DateTime('yesterday'))->format('Y-m-d');
$statuses = ['Programmata', 'Rimandata', 'Riprogrammata'];
$placeholders = implode(',', array_fill(0, count($statuses), '?'));

try {
    $pdo  = Database::getInstance();
    $stmt = $pdo->prepare(
        "UPDATE prenotazioni
         SET stato = 'Svolta', updated_at = NOW()
         WHERE data = ?
           AND stato IN ({$placeholders})"
    );
    $stmt->execute(array_merge([$ieri], $statuses));
    $affected = $stmt->rowCount();

    $msg = '[' . date('Y-m-d H:i:s') . '] Cron mark-lessons-done: ' . $affected . ' lezioni aggiornate a "Svolta" per il ' . $ieri . PHP_EOL;
    echo $msg;
    error_log($msg);
} catch (Throwable $e) {
    $msg = '[' . date('Y-m-d H:i:s') . '] Errore cron mark-lessons-done: ' . $e->getMessage() . PHP_EOL;
    echo $msg;
    error_log($msg);
    exit(1);
}

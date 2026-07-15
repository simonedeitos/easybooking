<?php
/**
 * export-insegnante-pdf.php
 * Generates a print-friendly HTML page for a teacher's lesson schedule / compensi.
 * Open in a new tab and use the browser's Print → Save as PDF.
 *
 * Query params:
 *   id   (int)   – teacher ID
 *   tipo (string) – 'futuri' (upcoming) | 'storico' (full history, default)
 */
ini_set('log_errors', '1');
ini_set('display_errors', '0');
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/encryption.php';
require_once __DIR__ . '/../includes/auth.php';
requireAuth();

$pdo          = Database::getInstance();
$insegnanteId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tipo         = ($_GET['tipo'] ?? 'storico') === 'futuri' ? 'futuri' : 'storico';

if ($insegnanteId <= 0) {
    http_response_code(400);
    die('ID insegnante non valido.');
}

// ── Load teacher data ────────────────────────────────────────
$stmt = $pdo->prepare(
    "SELECT i.*, tc.tariffa AS tariffa_coppia,
            GROUP_CONCAT(s.nome ORDER BY s.nome SEPARATOR ', ') AS strumenti_lista
     FROM insegnanti i
     LEFT JOIN tariffe_coppia tc ON tc.insegnante_id = i.id
     LEFT JOIN insegnanti_strumenti is2 ON is2.insegnante_id = i.id
     LEFT JOIN strumenti s ON s.id = is2.strumento_id
     WHERE i.id = ?
     GROUP BY i.id"
);
$stmt->execute([$insegnanteId]);
$insegnante = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$insegnante) {
    http_response_code(404);
    die('Insegnante non trovato.');
}

$nomeIns      = decryptField((string)($insegnante['nome']     ?? ''));
$cognomeIns   = decryptField((string)($insegnante['cognome']  ?? ''));
$nomeCompleto = trim($nomeIns . ' ' . $cognomeIns);
$telefonoIns  = decryptField((string)($insegnante['telefono'] ?? ''));
$emailIns     = decryptField((string)($insegnante['email']    ?? ''));
$tariffaOraria = (float)($insegnante['tariffa_oraria'] ?? 0);
$tariffaCoppia = $insegnante['tariffa_coppia'] !== null ? (float)$insegnante['tariffa_coppia'] : null;
$strumentiLista = $insegnante['strumenti_lista'] ?? '';

// ── Load lessons ─────────────────────────────────────────────
if ($tipo === 'futuri') {
    $stmtLez = $pdo->prepare(
        "SELECT p.*, c.nome AS cli_nome, c.cognome AS cli_cognome
         FROM prenotazioni p
         LEFT JOIN clienti c ON c.id = p.cliente_id
         WHERE p.insegnante_id = ? AND p.data >= CURDATE() AND p.stato = 'Programmata'
         ORDER BY p.data ASC, p.ora_inizio ASC"
    );
    $titoloRiepilogo = 'Riepilogo Appuntamenti Futuri';
} else {
    $stmtLez = $pdo->prepare(
        "SELECT p.*, c.nome AS cli_nome, c.cognome AS cli_cognome
         FROM prenotazioni p
         LEFT JOIN clienti c ON c.id = p.cliente_id
         WHERE p.insegnante_id = ?
         ORDER BY p.data ASC, p.ora_inizio ASC"
    );
    $titoloRiepilogo = 'Riepilogo Storico Lezioni';
}
$stmtLez->execute([$insegnanteId]);
$lezioni = $stmtLez->fetchAll(PDO::FETCH_ASSOC);

$appName       = appName();
$generatedDate = date('d/m/Y H:i');

// ── Calculate totals ─────────────────────────────────────────
$totaleOre    = 0.0;
$totaleCompenso = 0.0;
foreach ($lezioni as $lez) {
    if ($lez['stato'] === 'Svolta') {
        $startSec = strtotime('1970-01-01 ' . $lez['ora_inizio']);
        $endSec   = strtotime('1970-01-01 ' . $lez['ora_fine']);
        if ($startSec !== false && $endSec !== false && $endSec > $startSec) {
            $ore = ($endSec - $startSec) / 3600;
            $totaleOre += $ore;
            $totaleCompenso += $ore * $tariffaOraria;
        }
    }
}

function esc(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function fmt_date(?string $d): string { if (!$d) return '—'; $dt = DateTime::createFromFormat('Y-m-d', $d); return $dt ? $dt->format('d/m/Y') : esc($d); }
function fmt_time(?string $t): string { return $t ? substr($t, 0, 5) : '—'; }
function lesson_duration(?string $start, ?string $end): string {
    if (!$start || !$end) return '—';
    $s = strtotime('1970-01-01 ' . $start);
    $e = strtotime('1970-01-01 ' . $end);
    if ($s === false || $e === false || $e <= $s) return '—';
    $mins = (int)(($e - $s) / 60);
    if ($mins < 60) {
        return $mins . 'min';
    }
    $hours   = (int)floor($mins / 60);
    $minutes = $mins % 60;
    return $minutes > 0 ? $hours . 'h ' . $minutes . 'min' : $hours . 'h';
}
function lesson_compenso(float $tariffa, ?string $start, ?string $end): string {
    if (!$start || !$end) return '—';
    $s = strtotime('1970-01-01 ' . $start);
    $e = strtotime('1970-01-01 ' . $end);
    if ($s === false || $e === false || $e <= $s) return '—';
    $ore = ($e - $s) / 3600;
    return '€ ' . number_format($ore * $tariffa, 2, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title><?= esc($appName) ?> – <?= esc($titoloRiepilogo) ?> – <?= esc($nomeCompleto) ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, Helvetica, sans-serif; font-size: 11pt; color: #111; background: #fff; }
.container { max-width: 800px; margin: 0 auto; padding: 20px; }
h1 { font-size: 18pt; text-align: center; margin-bottom: 4px; }
h2 { font-size: 14pt; text-align: center; color: #444; margin-bottom: 20px; }
h3 { font-size: 11pt; font-weight: bold; text-transform: uppercase; border-bottom: 1px solid #ccc; padding-bottom: 4px; margin: 18px 0 8px; }
.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 16px; margin-bottom: 8px; }
.info-row { display: flex; gap: 6px; font-size: 10pt; }
.info-label { font-weight: bold; min-width: 130px; color: #333; }
table { width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 8px; }
th { background: #4a4a6a; color: #fff; padding: 5px 6px; text-align: left; font-size: 9pt; }
td { padding: 4px 6px; border-bottom: 1px solid #e0e0e0; vertical-align: top; }
tr:nth-child(even) td { background: #f8f8f8; }
.badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 8pt; font-weight: bold; }
.badge-Programmata   { background: #d4d0ff; color: #2d1fa3; }
.badge-Svolta        { background: #d1fae5; color: #065f46; }
.badge-Assente       { background: #fee2e2; color: #991b1b; }
.badge-Rimandata     { background: #fef3c7; color: #92400e; }
.badge-Riprogrammata { background: #cffafe; color: #164e63; }
.summary-box { background: #f8f8ff; border: 1px solid #d0d0ff; border-radius: 4px; padding: 10px 14px; margin-top: 12px; }
.summary-box p { font-size: 10pt; margin-bottom: 4px; }
.summary-box p:last-child { margin-bottom: 0; font-weight: bold; }
.footer { text-align: left; font-size: 8pt; color: #888; margin-top: 30px; padding-top: 8px; border-top: 1px solid #ddd; }
@media print {
    .no-print { display: none !important; }
    body { margin: 0; }
    .container { padding: 0; }
    @page { size: A4; margin: 15mm 12mm 15mm 12mm; }
}
</style>
</head>
<body>
<div class="container">

    <div class="no-print" style="background:#f0f2ff;border:1px solid #7c6af7;border-radius:6px;padding:10px 14px;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;">
        <span style="font-size:10pt;color:#333;">Per salvare come PDF: <strong>Ctrl+P</strong> → "Salva come PDF"</span>
        <button onclick="window.print()" style="background:#7c6af7;color:#fff;border:none;border-radius:4px;padding:6px 14px;cursor:pointer;font-size:10pt;">🖨 Stampa / PDF</button>
    </div>

    <h1><?= esc($appName) ?></h1>
    <h2><?= esc($titoloRiepilogo) ?></h2>

    <h3>Dati Insegnante</h3>
    <div class="info-grid">
        <div class="info-row"><span class="info-label">Nome:</span><span><?= esc($nomeCompleto) ?></span></div>
        <?php if ($telefonoIns): ?><div class="info-row"><span class="info-label">Telefono:</span><span><?= esc($telefonoIns) ?></span></div><?php endif; ?>
        <?php if ($emailIns):    ?><div class="info-row"><span class="info-label">Email:</span><span><?= esc($emailIns) ?></span></div><?php endif; ?>
        <div class="info-row"><span class="info-label">Tariffa Oraria:</span><span>€ <?= number_format($tariffaOraria, 2, ',', '.') ?></span></div>
        <?php if ($tariffaCoppia !== null && $tariffaCoppia !== $tariffaOraria): ?>
        <div class="info-row"><span class="info-label">Tariffa Coppia:</span><span>€ <?= number_format($tariffaCoppia, 2, ',', '.') ?></span></div>
        <?php endif; ?>
        <?php if ($strumentiLista): ?><div class="info-row"><span class="info-label">Strumenti:</span><span><?= esc($strumentiLista) ?></span></div><?php endif; ?>
    </div>

    <h3>Dettaglio Lezioni</h3>
    <?php if ($lezioni): ?>
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Orario</th>
                <th>Cliente</th>
                <th>Strumento</th>
                <th>Durata</th>
                <th>Stato</th>
                <?php if ($tipo === 'storico'): ?>
                <th>Compenso</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lezioni as $lez):
                $cliNome = decryptField((string)($lez['cli_nome']    ?? ''));
                $cliCogn = decryptField((string)($lez['cli_cognome'] ?? ''));
                $cliente = trim($cliNome . ' ' . $cliCogn) ?: 'N/A';
            ?>
            <tr>
                <td><?= fmt_date($lez['data']) ?></td>
                <td><?= fmt_time($lez['ora_inizio']) ?>–<?= fmt_time($lez['ora_fine']) ?></td>
                <td><?= esc($cliente) ?></td>
                <td><?= esc($lez['strumento'] ?? '—') ?: '—' ?></td>
                <td><?= lesson_duration($lez['ora_inizio'], $lez['ora_fine']) ?></td>
                <td><span class="badge badge-<?= esc($lez['stato'] ?? '') ?>"><?= esc($lez['stato'] ?? 'N/A') ?></span></td>
                <?php if ($tipo === 'storico'): ?>
                <td><?= $lez['stato'] === 'Svolta' ? lesson_compenso($tariffaOraria, $lez['ora_inizio'], $lez['ora_fine']) : '—' ?></td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($tipo === 'storico' && $totaleOre > 0): ?>
    <div class="summary-box">
        <p>Totale lezioni: <?= count(array_filter($lezioni, fn($l) => $l['stato'] === 'Svolta')) ?></p>
        <p>Totale ore: <?= number_format($totaleOre, 1, ',', '.') ?></p>
        <p>Totale compenso: € <?= number_format($totaleCompenso, 2, ',', '.') ?></p>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <p style="color:#888;font-size:9pt;">Nessuna lezione trovata.</p>
    <?php endif; ?>

    <div class="footer">
        Generato il: <?= esc($generatedDate) ?> – <?= esc($appName) ?>
    </div>
</div>
</body>
</html>

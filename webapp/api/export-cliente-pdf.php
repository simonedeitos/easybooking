<?php
/**
 * export-cliente-pdf.php
 * Generates a print-friendly HTML page for a client's lesson/purchase history.
 * Open in a new tab and use the browser's Print → Save as PDF.
 *
 * Query params:
 *   id   (int)   – client ID
 *   tipo (string) – 'futuri' (upcoming lessons) | 'storico' (full history, default)
 */
ini_set('log_errors', '1');
ini_set('display_errors', '0');
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/encryption.php';
require_once __DIR__ . '/../includes/auth.php';
requireAuth();

$pdo      = Database::getInstance();
$clienteId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tipo      = ($_GET['tipo'] ?? 'storico') === 'futuri' ? 'futuri' : 'storico';

if ($clienteId <= 0) {
    http_response_code(400);
    die('ID cliente non valido.');
}

// ── Load client data ─────────────────────────────────────────
$stmt = $pdo->prepare('SELECT * FROM clienti WHERE id = ? LIMIT 1');
$stmt->execute([$clienteId]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$cliente) {
    http_response_code(404);
    die('Cliente non trovato.');
}

$nome        = decryptField((string)($cliente['nome']     ?? ''));
$cognome     = decryptField((string)($cliente['cognome']  ?? ''));
$nomeCompleto = trim($nome . ' ' . $cognome);
$telefono    = decryptField((string)($cliente['telefono'] ?? ''));
$email       = decryptField((string)($cliente['email']    ?? ''));
$indirizzo   = decryptField((string)($cliente['indirizzo'] ?? ''));
$cf          = decryptField((string)($cliente['codice_fiscale'] ?? ''));

// ── Load purchases ───────────────────────────────────────────
$stmtAcq = $pdo->prepare(
    "SELECT a.*, p.nome AS pacchetto_nome
     FROM acquisti a
     LEFT JOIN pacchetti p ON a.pacchetto_id = p.id
     WHERE a.cliente_id = ?
     ORDER BY a.data_acquisto ASC"
);
$stmtAcq->execute([$clienteId]);
$acquisti = $stmtAcq->fetchAll(PDO::FETCH_ASSOC);

// ── Load lessons ─────────────────────────────────────────────
if ($tipo === 'futuri') {
    $stmtLez = $pdo->prepare(
        "SELECT p.*, i.nome AS ins_nome, i.cognome AS ins_cognome
         FROM prenotazioni p
         LEFT JOIN insegnanti i ON i.id = p.insegnante_id
         WHERE p.cliente_id = ? AND p.data >= CURDATE() AND p.stato = 'Programmata'
         ORDER BY p.data ASC, p.ora_inizio ASC"
    );
    $titoloRiepilogo = 'Riepilogo Appuntamenti Futuri';
} else {
    $stmtLez = $pdo->prepare(
        "SELECT p.*, i.nome AS ins_nome, i.cognome AS ins_cognome
         FROM prenotazioni p
         LEFT JOIN insegnanti i ON i.id = p.insegnante_id
         WHERE p.cliente_id = ?
         ORDER BY p.data ASC, p.ora_inizio ASC"
    );
    $titoloRiepilogo = 'Riepilogo Storico Completo';
}
$stmtLez->execute([$clienteId]);
$lezioni = $stmtLez->fetchAll(PDO::FETCH_ASSOC);

$appName       = appName();
$generatedDate = date('d/m/Y H:i');

function esc(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function fmt_date(?string $d): string { if (!$d) return '—'; $dt = DateTime::createFromFormat('Y-m-d', $d); return $dt ? $dt->format('d/m/Y') : esc($d); }
function fmt_time(?string $t): string { return $t ? substr($t, 0, 5) : '—'; }
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
.info-label { font-weight: bold; min-width: 110px; color: #333; }
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
.badge-Pagato        { background: #d1fae5; color: #065f46; }
.badge-Parziale      { background: #fef3c7; color: #92400e; }
.badge-NonPagato     { background: #fee2e2; color: #991b1b; }
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

    <h3>Dati Cliente</h3>
    <div class="info-grid">
        <div class="info-row"><span class="info-label">Nome:</span><span><?= esc($nomeCompleto) ?></span></div>
        <?php if ($telefono): ?><div class="info-row"><span class="info-label">Telefono:</span><span><?= esc($telefono) ?></span></div><?php endif; ?>
        <?php if ($email):    ?><div class="info-row"><span class="info-label">Email:</span><span><?= esc($email) ?></span></div><?php endif; ?>
        <?php if ($indirizzo): ?><div class="info-row"><span class="info-label">Indirizzo:</span><span><?= esc($indirizzo) ?></span></div><?php endif; ?>
        <?php if ($cf):       ?><div class="info-row"><span class="info-label">Codice Fiscale:</span><span><?= esc($cf) ?></span></div><?php endif; ?>
    </div>

    <?php if ($tipo === 'storico'): ?>
    <h3>Acquisti</h3>
    <?php if ($acquisti): ?>
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Pacchetto</th>
                <th>Lezioni</th>
                <th>Importo</th>
                <th>Stato Pagamento</th>
                <th>Fattura</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($acquisti as $acq): ?>
            <tr>
                <td><?= fmt_date($acq['data_acquisto']) ?></td>
                <td><?= esc($acq['pacchetto_nome'] ?? ('Pacchetto ID ' . $acq['pacchetto_id'])) ?></td>
                <td><?= esc($acq['numero_lezioni'] ?? 0) ?></td>
                <td>€ <?= number_format((float)($acq['importo_pagato'] ?? 0), 2, ',', '.') ?></td>
                <td><span class="badge badge-<?= esc(str_replace(' ', '', $acq['stato_pagamento'] ?? '')) ?>"><?= esc($acq['stato_pagamento'] ?? 'N/A') ?></span></td>
                <td><?= esc($acq['numero_fattura'] ?? '—') ?: '—' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="color:#888;font-size:9pt;">Nessun acquisto trovato.</p>
    <?php endif; ?>
    <?php endif; ?>

    <h3>Lezioni</h3>
    <?php if ($lezioni): ?>
    <table>
        <thead>
            <tr>
                <th>Data e Orario</th>
                <th>Pacchetto</th>
                <th>Strumento</th>
                <th>Insegnante</th>
                <th>Stato</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lezioni as $lez):
                $insNome = decryptField((string)($lez['ins_nome']    ?? ''));
                $insCogn = decryptField((string)($lez['ins_cognome'] ?? ''));
                $insegnante = trim($insNome . ' ' . $insCogn) ?: 'N/A';
            ?>
            <tr>
                <td><?= fmt_date($lez['data']) ?> <?= fmt_time($lez['ora_inizio']) ?>–<?= fmt_time($lez['ora_fine']) ?></td>
                <td><?= esc($lez['pacchetto_nome'] ?? '—') ?: '—' ?></td>
                <td><?= esc($lez['strumento'] ?? '—') ?: '—' ?></td>
                <td><?= esc($insegnante) ?></td>
                <td><span class="badge badge-<?= esc($lez['stato'] ?? '') ?>"><?= esc($lez['stato'] ?? 'N/A') ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p style="color:#888;font-size:9pt;">Nessuna lezione trovata.</p>
    <?php endif; ?>

    <div class="footer">
        Generato il: <?= esc($generatedDate) ?> – <?= esc($appName) ?>
    </div>
</div>
</body>
</html>

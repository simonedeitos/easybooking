<?php
/**
 * export-report-pdf.php
 * Generates a print-friendly HTML report page.
 * Open in a new tab and use the browser's Print → Save as PDF.
 *
 * Query params: same as report.php (report_type, date_from, date_to, insegnante_id, cliente_id)
 */
ini_set('log_errors', '1');
ini_set('display_errors', '0');
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/encryption.php';
require_once __DIR__ . '/../includes/auth.php';
requireAuth();

$pdo = Database::getInstance();

function esc(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function fmt_date(?string $d): string { if (!$d) return '—'; $dt = DateTime::createFromFormat('Y-m-d', $d); return $dt ? $dt->format('d/m/Y') : esc($d); }
/**
 * Validates that $value is a strictly formatted Y-m-d date string.
 * Returns $value if valid, or $fallback if the format check fails.
 * This prevents SQL injection via date parameters while accepting only canonical dates.
 */
function sanitizeReportDate(string $value, string $fallback): string {
    $value = trim($value);
    $dt = DateTime::createFromFormat('Y-m-d', $value);
    return ($dt instanceof DateTime && $dt->format('Y-m-d') === $value) ? $value : $fallback;
}

$allowedTypes = ['ore_insegnanti', 'sommario_clienti', 'entrate', 'statistiche_lezioni', 'calendario_insegnante'];
$reportType = isset($_GET['report_type']) ? trim($_GET['report_type']) : 'ore_insegnanti';
if (!in_array($reportType, $allowedTypes, true)) { $reportType = 'ore_insegnanti'; }

$defaultFrom = (new DateTime('first day of this month'))->format('Y-m-d');
$defaultTo   = (new DateTime())->format('Y-m-d');
$dateFrom    = sanitizeReportDate(isset($_GET['date_from']) ? trim($_GET['date_from']) : $defaultFrom, $defaultFrom);
$dateTo      = sanitizeReportDate(isset($_GET['date_to'])   ? trim($_GET['date_to'])   : $defaultTo,   $defaultTo);
if ($dateFrom > $dateTo) { [$dateFrom, $dateTo] = [$dateTo, $dateFrom]; }

$insegnanteId = isset($_GET['insegnante_id']) ? (int)$_GET['insegnante_id'] : 0;
$clienteId    = isset($_GET['cliente_id'])    ? (int)$_GET['cliente_id']    : 0;

$reportTitles = [
    'ore_insegnanti'       => 'Ore e Guadagni Insegnanti',
    'sommario_clienti'     => 'Sommario Clienti',
    'entrate'              => 'Entrate',
    'statistiche_lezioni'  => 'Statistiche Lezioni',
    'calendario_insegnante'=> 'Calendario Insegnante',
];
$reportTitle = $reportTitles[$reportType] ?? 'Report';
$appName     = appName();
$generatedDate = date('d/m/Y H:i');

$reportData = [];
$pageError  = '';

try {
    if ($reportType === 'ore_insegnanti') {
        $params = [$dateFrom, $dateTo];
        $joinClient = '';
        if ($clienteId > 0) {
            $joinClient = ' AND p.cliente_id = ?';
            $params[] = $clienteId;
        }
        $whereTeacher = '';
        if ($insegnanteId > 0) {
            $whereTeacher = ' WHERE i.id = ?';
            $params[] = $insegnanteId;
        }
        $stmt = $pdo->prepare(
            'SELECT i.id, i.nome, i.cognome, i.tariffa_oraria,
                    COALESCE(SUM(TIMESTAMPDIFF(MINUTE, p.ora_inizio, p.ora_fine)), 0) AS minuti_totali,
                    COALESCE(SUM(CASE WHEN p.stato = "Programmata" THEN 1 ELSE 0 END), 0) AS programmata,
                    COALESCE(SUM(CASE WHEN p.stato = "Svolta" THEN 1 ELSE 0 END), 0) AS svolta,
                    COALESCE(SUM(CASE WHEN p.stato = "Assente" THEN 1 ELSE 0 END), 0) AS assente,
                    COALESCE(SUM(CASE WHEN p.stato = "Rimandata" THEN 1 ELSE 0 END), 0) AS rimandata,
                    COALESCE(SUM(CASE WHEN p.stato = "Riprogrammata" THEN 1 ELSE 0 END), 0) AS riprogrammata
             FROM insegnanti i
             LEFT JOIN prenotazioni p
                ON p.insegnante_id = i.id
               AND p.data BETWEEN ? AND ?' . $joinClient .
            $whereTeacher . '
             GROUP BY i.id, i.nome, i.cognome, i.tariffa_oraria
             ORDER BY i.cognome ASC, i.nome ASC'
        );
        $stmt->execute($params);
        foreach ($stmt->fetchAll() as $row) {
            $row['guadagno'] = ((int)$row['minuti_totali'] / 60) * (float)$row['tariffa_oraria'];
            $reportData[] = $row;
        }
    } elseif ($reportType === 'sommario_clienti') {
        $params = [$dateFrom, $dateTo];
        $joinTeacher = '';
        if ($insegnanteId > 0) { $joinTeacher = ' AND p.insegnante_id = ?'; $params[] = $insegnanteId; }
        $whereClient = '';
        if ($clienteId > 0) { $whereClient = ' WHERE c.id = ?'; $params[] = $clienteId; }
        $stmt = $pdo->prepare(
            'SELECT c.nome, c.cognome,
                    COUNT(p.id) AS totale,
                    COALESCE(SUM(CASE WHEN p.stato = "Svolta" THEN 1 ELSE 0 END), 0) AS svolta,
                    COALESCE(SUM(CASE WHEN p.stato = "Assente" THEN 1 ELSE 0 END), 0) AS assente,
                    COALESCE(SUM(CASE WHEN p.stato = "Programmata" THEN 1 ELSE 0 END), 0) AS programmata,
                    COALESCE(SUM(CASE WHEN p.stato = "Rimandata" THEN 1 ELSE 0 END), 0) AS rimandata,
                    COALESCE(SUM(CASE WHEN p.stato = "Riprogrammata" THEN 1 ELSE 0 END), 0) AS riprogrammata
             FROM clienti c
             LEFT JOIN prenotazioni p ON p.cliente_id = c.id AND p.data BETWEEN ? AND ?' .
            $joinTeacher . $whereClient . '
             GROUP BY c.id, c.nome, c.cognome
             ORDER BY c.cognome ASC, c.nome ASC'
        );
        $stmt->execute($params);
        $reportData = $stmt->fetchAll();
    } elseif ($reportType === 'entrate') {
        $params = [$dateFrom, $dateTo];
        if ($clienteId > 0) { $params[] = $clienteId; }
        $stmt = $pdo->prepare(
            'SELECT DATE_FORMAT(data_acquisto, "%m/%Y") AS mese_label,
                    SUM(importo_pagato) AS totale
             FROM acquisti
             WHERE stato_pagamento = "Pagato"
               AND data_acquisto BETWEEN ? AND ?' .
            ($clienteId > 0 ? ' AND cliente_id = ?' : '') . '
             GROUP BY DATE_FORMAT(data_acquisto, "%Y-%m"), DATE_FORMAT(data_acquisto, "%m/%Y")
             ORDER BY DATE_FORMAT(data_acquisto, "%Y-%m") ASC'
        );
        $stmt->execute($params);
        $reportData = $stmt->fetchAll();
    } elseif ($reportType === 'statistiche_lezioni') {
        $where  = ['p.data BETWEEN ? AND ?'];
        $params = [$dateFrom, $dateTo];
        if ($insegnanteId > 0) { $where[] = 'p.insegnante_id = ?'; $params[] = $insegnanteId; }
        if ($clienteId    > 0) { $where[] = 'p.cliente_id = ?';    $params[] = $clienteId; }
        $whereSql = ' WHERE ' . implode(' AND ', $where);

        $stmt = $pdo->prepare('SELECT p.stato, COUNT(*) AS totale FROM prenotazioni p' . $whereSql . ' GROUP BY p.stato ORDER BY totale DESC');
        $stmt->execute($params);
        $reportData['by_status'] = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            'SELECT COALESCE(NULLIF(p.strumento, ""), "Non specificato") AS strumento, COUNT(*) AS totale
             FROM prenotazioni p' . $whereSql . '
             GROUP BY COALESCE(NULLIF(p.strumento, ""), "Non specificato")
             ORDER BY totale DESC'
        );
        $stmt->execute($params);
        $reportData['by_strumento'] = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            'SELECT i.nome, i.cognome, COUNT(*) AS totale
             FROM prenotazioni p
             INNER JOIN insegnanti i ON i.id = p.insegnante_id' .
            $whereSql . '
             GROUP BY i.id, i.nome, i.cognome
             ORDER BY totale DESC'
        );
        $stmt->execute($params);
        $reportData['by_insegnante'] = $stmt->fetchAll();
    } elseif ($reportType === 'calendario_insegnante') {
        $selectedTeacher = null;
        if ($insegnanteId > 0) {
            $stmt = $pdo->prepare('SELECT id, nome, cognome FROM insegnanti WHERE id = ? LIMIT 1');
            $stmt->execute([$insegnanteId]);
            $selectedTeacher = $stmt->fetch() ?: null;
        }
        if ($selectedTeacher) {
            $start = new DateTimeImmutable('monday this week');
            $ranges = [];
            for ($i = 0; $i < 3; $i++) {
                $ws = $start->modify('+' . $i . ' week');
                $ranges[] = ['label' => $i === 0 ? 'Settimana corrente' : ($i === 1 ? 'Prossima settimana' : 'Fra due settimane'), 'start' => $ws, 'end' => $ws->modify('+6 days')];
            }
            $calStart = $ranges[0]['start']->format('Y-m-d');
            $calEnd   = $ranges[2]['end']->format('Y-m-d');
            $params = [$insegnanteId, $calStart, $calEnd];
            $cliFilter = '';
            if ($clienteId > 0) { $cliFilter = ' AND p.cliente_id = ?'; $params[] = $clienteId; }
            $stmt = $pdo->prepare(
                'SELECT p.*, c.nome AS cliente_nome, c.cognome AS cliente_cognome
                 FROM prenotazioni p
                 INNER JOIN clienti c ON c.id = p.cliente_id
                 WHERE p.insegnante_id = ? AND p.data BETWEEN ? AND ?' . $cliFilter . '
                 ORDER BY p.data ASC, p.ora_inizio ASC'
            );
            $stmt->execute($params);
            $lessons = $stmt->fetchAll();
            $grouped = [];
            foreach ($lessons as $lez) {
                $grouped[$lez['data']][] = $lez;
            }
            foreach ($ranges as $range) {
                $days = [];
                for ($offset = 0; $offset < 7; $offset++) {
                    $day = $range['start']->modify('+' . $offset . ' day');
                    $dk = $day->format('Y-m-d');
                    $days[] = ['date' => $dk, 'label' => $day->format('D d/m'), 'lessons' => $grouped[$dk] ?? []];
                }
                $reportData['weeks'][] = ['label' => $range['label'], 'days' => $days];
            }
            $reportData['teacher'] = $selectedTeacher;
        }
    }
} catch (PDOException $e) {
    $pageError = 'Impossibile generare il report.';
}

function rptHours(int $minutes): string {
    return sprintf('%dh %02dm', intdiv($minutes, 60), $minutes % 60);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title><?= esc($appName) ?> – <?= esc($reportTitle) ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, Helvetica, sans-serif; font-size: 11pt; color: #111; background: #fff; }
.container { max-width: 900px; margin: 0 auto; padding: 20px; }
h1 { font-size: 18pt; text-align: center; margin-bottom: 4px; }
h2 { font-size: 13pt; text-align: center; color: #444; margin-bottom: 20px; }
h3 { font-size: 11pt; font-weight: bold; text-transform: uppercase; border-bottom: 1px solid #ccc; padding-bottom: 4px; margin: 18px 0 8px; }
.meta { text-align: center; font-size: 9pt; color: #666; margin-bottom: 16px; }
table { width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 12px; }
th { background: #4a4a6a; color: #fff; padding: 5px 6px; text-align: left; font-size: 9pt; }
td { padding: 4px 6px; border-bottom: 1px solid #e0e0e0; vertical-align: top; }
tr:nth-child(even) td { background: #f8f8f8; }
.footer { text-align: left; font-size: 8pt; color: #888; margin-top: 30px; padding-top: 8px; border-top: 1px solid #ddd; }
.no-print { background: #f0f2ff; border: 1px solid #7c6af7; border-radius: 6px; padding: 10px 14px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; }
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

<div class="no-print">
    <span style="font-size:10pt;color:#333;">Per salvare come PDF: <strong>Ctrl+P</strong> → "Salva come PDF"</span>
    <button onclick="window.print()" style="background:#7c6af7;color:#fff;border:none;border-radius:4px;padding:6px 14px;cursor:pointer;font-size:10pt;">🖨 Stampa / PDF</button>
</div>

<h1><?= esc($appName) ?></h1>
<h2><?= esc($reportTitle) ?></h2>
<div class="meta">Periodo: <?= esc(fmt_date($dateFrom)) ?> – <?= esc(fmt_date($dateTo)) ?></div>

<?php if ($pageError !== ''): ?>
<p style="color:#c00;font-size:10pt;"><?= esc($pageError) ?></p>
<?php endif; ?>

<?php if ($reportType === 'ore_insegnanti'): ?>
<h3>Ore e Guadagni Insegnanti</h3>
<?php if ($reportData): ?>
<table>
    <thead>
        <tr>
            <th>Insegnante</th>
            <th>Ore Totali</th>
            <th>Tariffa/h</th>
            <th>Guadagno</th>
            <th>Programmate</th>
            <th>Svolte</th>
            <th>Assenti</th>
            <th>Rimandate</th>
            <th>Riprogrammate</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($reportData as $row): ?>
        <tr>
            <td><?= esc(trim(decryptField((string)$row['cognome']) . ' ' . decryptField((string)$row['nome']))) ?></td>
            <td><?= esc(rptHours((int)$row['minuti_totali'])) ?></td>
            <td>€ <?= esc(number_format((float)$row['tariffa_oraria'], 2, ',', '.')) ?></td>
            <td>€ <?= esc(number_format((float)$row['guadagno'], 2, ',', '.')) ?></td>
            <td><?= esc($row['programmata']) ?></td>
            <td><?= esc($row['svolta']) ?></td>
            <td><?= esc($row['assente']) ?></td>
            <td><?= esc($row['rimandata']) ?></td>
            <td><?= esc($row['riprogrammata']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<p style="color:#888;font-size:9pt;">Nessun dato disponibile per il periodo selezionato.</p>
<?php endif; ?>

<?php elseif ($reportType === 'sommario_clienti'): ?>
<h3>Sommario Clienti</h3>
<?php if ($reportData): ?>
<table>
    <thead>
        <tr>
            <th>Cliente</th>
            <th>Totale</th>
            <th>Svolte</th>
            <th>Assenze</th>
            <th>Programmate</th>
            <th>Rimandate</th>
            <th>Riprogrammate</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($reportData as $row): ?>
        <tr>
            <td><?= esc(trim(decryptField((string)$row['cognome']) . ' ' . decryptField((string)$row['nome']))) ?></td>
            <td><?= esc($row['totale']) ?></td>
            <td><?= esc($row['svolta']) ?></td>
            <td><?= esc($row['assente']) ?></td>
            <td><?= esc($row['programmata']) ?></td>
            <td><?= esc($row['rimandata']) ?></td>
            <td><?= esc($row['riprogrammata']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<p style="color:#888;font-size:9pt;">Nessun dato disponibile.</p>
<?php endif; ?>

<?php elseif ($reportType === 'entrate'): ?>
<h3>Entrate per Mese</h3>
<?php if ($reportData): ?>
<table>
    <thead>
        <tr>
            <th>Mese</th>
            <th>Entrate</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $totale = 0.0;
        foreach ($reportData as $row):
            $totale += (float)$row['totale'];
        ?>
        <tr>
            <td><?= esc($row['mese_label']) ?></td>
            <td>€ <?= esc(number_format((float)$row['totale'], 2, ',', '.')) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr style="font-weight:bold;border-top:2px solid #333;">
            <td>Totale</td>
            <td>€ <?= esc(number_format($totale, 2, ',', '.')) ?></td>
        </tr>
    </tbody>
</table>
<?php else: ?>
<p style="color:#888;font-size:9pt;">Nessuna entrata registrata nel periodo.</p>
<?php endif; ?>

<?php elseif ($reportType === 'statistiche_lezioni'): ?>
<h3>Statistiche per Stato</h3>
<?php if (!empty($reportData['by_status'])): ?>
<table>
    <thead><tr><th>Stato</th><th>Totale</th></tr></thead>
    <tbody>
        <?php foreach ($reportData['by_status'] as $row): ?>
        <tr><td><?= esc($row['stato']) ?></td><td><?= esc($row['totale']) ?></td></tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<h3>Statistiche per Strumento</h3>
<?php if (!empty($reportData['by_strumento'])): ?>
<table>
    <thead><tr><th>Strumento</th><th>Totale</th></tr></thead>
    <tbody>
        <?php foreach ($reportData['by_strumento'] as $row): ?>
        <tr><td><?= esc($row['strumento']) ?></td><td><?= esc($row['totale']) ?></td></tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<h3>Statistiche per Insegnante</h3>
<?php if (!empty($reportData['by_insegnante'])): ?>
<table>
    <thead><tr><th>Insegnante</th><th>Totale</th></tr></thead>
    <tbody>
        <?php foreach ($reportData['by_insegnante'] as $row): ?>
        <tr>
            <td><?= esc(trim(decryptField((string)$row['cognome']) . ' ' . decryptField((string)$row['nome']))) ?></td>
            <td><?= esc($row['totale']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php elseif ($reportType === 'calendario_insegnante'): ?>
<?php if (!empty($reportData['teacher'])): ?>
<h3>Calendario: <?= esc(trim(decryptField((string)$reportData['teacher']['cognome']) . ' ' . decryptField((string)$reportData['teacher']['nome']))) ?></h3>
<?php foreach (($reportData['weeks'] ?? []) as $week): ?>
<h3><?= esc($week['label']) ?></h3>
<table>
    <thead><tr><th>Giorno</th><th>Orario</th><th>Cliente</th><th>Strumento</th><th>Stato</th></tr></thead>
    <tbody>
        <?php
        $hasLessons = false;
        foreach ($week['days'] as $day):
            foreach ($day['lessons'] as $lez):
                $hasLessons = true;
        ?>
        <tr>
            <td><?= esc($day['label']) ?></td>
            <td><?= esc(substr((string)$lez['ora_inizio'], 0, 5)) ?>–<?= esc(substr((string)$lez['ora_fine'], 0, 5)) ?></td>
            <td><?= esc(trim(decryptField((string)($lez['cliente_nome'] ?? '')) . ' ' . decryptField((string)($lez['cliente_cognome'] ?? '')))) ?></td>
            <td><?= esc($lez['strumento'] ?? '—') ?></td>
            <td><?= esc($lez['stato'] ?? '—') ?></td>
        </tr>
        <?php
            endforeach;
        endforeach;
        if (!$hasLessons): ?>
        <tr><td colspan="5" style="color:#888;text-align:center;">Nessuna lezione</td></tr>
        <?php endif; ?>
    </tbody>
</table>
<?php endforeach; ?>
<?php else: ?>
<p style="color:#888;font-size:9pt;">Selezionare un insegnante per visualizzare il calendario.</p>
<?php endif; ?>
<?php endif; ?>

<div class="footer">
    Generato il: <?= esc($generatedDate) ?> – <?= esc($appName) ?>
</div>
</div>
</body>
</html>

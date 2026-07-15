<?php
ob_start();
ini_set('log_errors', '1');
ini_set('display_errors', '0');
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/encryption.php';
require_once __DIR__ . '/includes/auth.php';
requireAuth();
$pdo = Database::getInstance();

function h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function reportDate(string $value, string $fallback): string
{
    $value = trim($value);
    $dt = DateTime::createFromFormat('Y-m-d', $value);
    return ($dt instanceof DateTime && $dt->format('Y-m-d') === $value) ? $value : $fallback;
}

function reportStatuses(): array
{
    return ['Programmata', 'Svolta', 'Assente', 'Rimandata', 'Riprogrammata'];
}

function reportHourString(int $minutes): string
{
    $hours = intdiv($minutes, 60);
    $mins  = $minutes % 60;
    return sprintf('%dh %02dm', $hours, $mins);
}

function reportCsvCell(mixed $value): string
{
    return '"' . str_replace('"', '""', (string)$value) . '"';
}

function reportWeekRanges(): array
{
    $start = new DateTimeImmutable('monday this week');
    $ranges = [];
    for ($i = 0; $i < 3; $i++) {
        $weekStart = $start->modify('+' . $i . ' week');
        $ranges[] = [
            'label' => $i === 0 ? 'Settimana corrente' : ($i === 1 ? 'Prossima settimana' : 'Fra due settimane'),
            'start' => $weekStart,
            'end' => $weekStart->modify('+6 days'),
        ];
    }
    return $ranges;
}

$reportType = get('report_type', 'ore_insegnanti');
$allowedReportTypes = ['ore_insegnanti', 'sommario_clienti', 'entrate', 'statistiche_lezioni', 'calendario_insegnante'];
if (!in_array($reportType, $allowedReportTypes, true)) {
    $reportType = 'ore_insegnanti';
}

$defaultFrom = (new DateTime('first day of this month'))->format('Y-m-d');
$defaultTo = (new DateTime())->format('Y-m-d');
$dateFrom = reportDate(get('date_from', $defaultFrom), $defaultFrom);
$dateTo = reportDate(get('date_to', $defaultTo), $defaultTo);
if ($dateFrom > $dateTo) {
    [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
}

$insegnanteId = sanitizeInt(get('insegnante_id', 0));
$clienteId = sanitizeInt(get('cliente_id', 0));
$exportCsv = get('export') === 'csv';

$teachers = [];
$clients = [];
$pageError = '';
$reportData = [
    'ore_insegnanti' => [],
    'sommario_clienti' => [],
    'entrate' => [],
    'statistiche_lezioni' => ['by_status' => [], 'by_strumento' => [], 'by_insegnante' => []],
    'calendario_insegnante' => ['teacher' => null, 'weeks' => []],
];
$chartLabels = [];
$chartValues = [];

try {
    $stmt = $pdo->prepare('SELECT id, nome, cognome FROM insegnanti ORDER BY cognome ASC, nome ASC');
    $stmt->execute();
    $teachers = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT id, nome, cognome FROM clienti ORDER BY cognome ASC, nome ASC');
    $stmt->execute();
    $clients = $stmt->fetchAll();

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
        $sql =
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
             ORDER BY i.cognome ASC, i.nome ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            $row['guadagno'] = ((int)$row['minuti_totali'] / 60) * (float)$row['tariffa_oraria'];
            $reportData['ore_insegnanti'][] = $row;
        }
    }

    if ($reportType === 'sommario_clienti') {
        $params = [$dateFrom, $dateTo];
        $joinTeacher = '';
        if ($insegnanteId > 0) {
            $joinTeacher = ' AND p.insegnante_id = ?';
            $params[] = $insegnanteId;
        }
        $whereClient = '';
        if ($clienteId > 0) {
            $whereClient = ' WHERE c.id = ?';
            $params[] = $clienteId;
        }
        $stmt = $pdo->prepare(
            'SELECT c.id, c.nome, c.cognome,
                    COUNT(p.id) AS totale,
                    COALESCE(SUM(CASE WHEN p.stato = "Svolta" THEN 1 ELSE 0 END), 0) AS svolta,
                    COALESCE(SUM(CASE WHEN p.stato = "Assente" THEN 1 ELSE 0 END), 0) AS assente,
                    COALESCE(SUM(CASE WHEN p.stato = "Programmata" THEN 1 ELSE 0 END), 0) AS programmata,
                    COALESCE(SUM(CASE WHEN p.stato = "Rimandata" THEN 1 ELSE 0 END), 0) AS rimandata,
                    COALESCE(SUM(CASE WHEN p.stato = "Riprogrammata" THEN 1 ELSE 0 END), 0) AS riprogrammata
             FROM clienti c
             LEFT JOIN prenotazioni p
                ON p.cliente_id = c.id
               AND p.data BETWEEN ? AND ?' . $joinTeacher .
             $whereClient . '
             GROUP BY c.id, c.nome, c.cognome
             ORDER BY c.cognome ASC, c.nome ASC'
        );
        $stmt->execute($params);
        $reportData['sommario_clienti'] = $stmt->fetchAll();
    }

    if ($reportType === 'entrate') {
        $params = [$dateFrom, $dateTo];
        $whereClient = '';
        if ($clienteId > 0) {
            $whereClient = ' AND cliente_id = ?';
            $params[] = $clienteId;
        }
        $stmt = $pdo->prepare(
            'SELECT DATE_FORMAT(data_acquisto, "%Y-%m") AS mese_key,
                    DATE_FORMAT(data_acquisto, "%m/%Y") AS mese_label,
                    SUM(importo_pagato) AS totale
             FROM acquisti
             WHERE stato_pagamento = "Pagato"
               AND data_acquisto BETWEEN ? AND ?' . $whereClient . '
             GROUP BY DATE_FORMAT(data_acquisto, "%Y-%m"), DATE_FORMAT(data_acquisto, "%m/%Y")
             ORDER BY mese_key ASC'
        );
        $stmt->execute($params);
        $reportData['entrate'] = $stmt->fetchAll();
        foreach ($reportData['entrate'] as $row) {
            $chartLabels[] = $row['mese_label'];
            $chartValues[] = (float)$row['totale'];
        }
    }

    if ($reportType === 'statistiche_lezioni') {
        $where = ['p.data BETWEEN ? AND ?'];
        $params = [$dateFrom, $dateTo];
        if ($insegnanteId > 0) {
            $where[] = 'p.insegnante_id = ?';
            $params[] = $insegnanteId;
        }
        if ($clienteId > 0) {
            $where[] = 'p.cliente_id = ?';
            $params[] = $clienteId;
        }
        $whereSql = ' WHERE ' . implode(' AND ', $where);

        $stmt = $pdo->prepare('SELECT p.stato, COUNT(*) AS totale FROM prenotazioni p' . $whereSql . ' GROUP BY p.stato ORDER BY totale DESC');
        $stmt->execute($params);
        $reportData['statistiche_lezioni']['by_status'] = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            'SELECT COALESCE(NULLIF(p.strumento, ""), "Non specificato") AS strumento, COUNT(*) AS totale
             FROM prenotazioni p' . $whereSql . '
             GROUP BY COALESCE(NULLIF(p.strumento, ""), "Non specificato")
             ORDER BY totale DESC, strumento ASC'
        );
        $stmt->execute($params);
        $reportData['statistiche_lezioni']['by_strumento'] = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            'SELECT i.nome, i.cognome, COUNT(*) AS totale
             FROM prenotazioni p
             INNER JOIN insegnanti i ON i.id = p.insegnante_id' .
             $whereSql . '
             GROUP BY i.id, i.nome, i.cognome
             ORDER BY totale DESC, i.cognome ASC, i.nome ASC'
        );
        $stmt->execute($params);
        $reportData['statistiche_lezioni']['by_insegnante'] = $stmt->fetchAll();
    }

    if ($reportType === 'calendario_insegnante') {
        $selectedTeacher = null;
        if ($insegnanteId > 0) {
            $stmt = $pdo->prepare('SELECT id, nome, cognome FROM insegnanti WHERE id = ? LIMIT 1');
            $stmt->execute([$insegnanteId]);
            $selectedTeacher = $stmt->fetch() ?: null;
        }
        $reportData['calendario_insegnante']['teacher'] = $selectedTeacher;

        if ($selectedTeacher) {
            $ranges = reportWeekRanges();
            $calendarStart = $ranges[0]['start']->format('Y-m-d');
            $calendarEnd = $ranges[2]['end']->format('Y-m-d');
            $params = [$insegnanteId, $calendarStart, $calendarEnd];
            $clientFilter = '';
            if ($clienteId > 0) {
                $clientFilter = ' AND p.cliente_id = ?';
                $params[] = $clienteId;
            }

            $stmt = $pdo->prepare(
                'SELECT p.*, c.nome AS cliente_nome, c.cognome AS cliente_cognome
                 FROM prenotazioni p
                 INNER JOIN clienti c ON c.id = p.cliente_id
                 WHERE p.insegnante_id = ?
                   AND p.data BETWEEN ? AND ?' . $clientFilter . '
                 ORDER BY p.data ASC, p.ora_inizio ASC'
            );
            $stmt->execute($params);
            $lessons = $stmt->fetchAll();
            $grouped = [];
            foreach ($lessons as $lesson) {
                $grouped[$lesson['data']][] = $lesson;
            }

            foreach ($ranges as $range) {
                $days = [];
                for ($offset = 0; $offset < 7; $offset++) {
                    $day = $range['start']->modify('+' . $offset . ' day');
                    $dayKey = $day->format('Y-m-d');
                    $days[] = [
                        'date' => $dayKey,
                        'label' => $day->format('D d/m'),
                        'lessons' => $grouped[$dayKey] ?? [],
                    ];
                }
                $reportData['calendario_insegnante']['weeks'][] = [
                    'label' => $range['label'],
                    'range' => formatDate($range['start']->format('Y-m-d')) . ' - ' . formatDate($range['end']->format('Y-m-d')),
                    'days' => $days,
                ];
            }
        }
    }
} catch (PDOException $e) {
    $pageError = 'Impossibile generare il report richiesto.';
}

if ($exportCsv) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename=report-' . $reportType . '-' . date('Ymd-His') . '.csv');
    echo "\xEF\xBB\xBF";

    if ($reportType === 'ore_insegnanti') {
        echo implode(';', [reportCsvCell('Insegnante'), reportCsvCell('Ore'), reportCsvCell('Tariffa oraria'), reportCsvCell('Guadagno'), reportCsvCell('Programmate'), reportCsvCell('Svolte'), reportCsvCell('Assenti'), reportCsvCell('Rimandate'), reportCsvCell('Riprogrammate')]) . "\n";
        foreach ($reportData['ore_insegnanti'] as $row) {
            echo implode(';', [
                reportCsvCell(trim($row['nome'] . ' ' . $row['cognome'])),
                reportCsvCell(reportHourString((int)$row['minuti_totali'])),
                reportCsvCell(number_format((float)$row['tariffa_oraria'], 2, ',', '.')),
                reportCsvCell(number_format((float)$row['guadagno'], 2, ',', '.')),
                reportCsvCell($row['programmata']), reportCsvCell($row['svolta']), reportCsvCell($row['assente']), reportCsvCell($row['rimandata']), reportCsvCell($row['riprogrammata'])
            ]) . "\n";
        }
    } elseif ($reportType === 'sommario_clienti') {
        echo implode(';', [reportCsvCell('Cliente'), reportCsvCell('Totale'), reportCsvCell('Svolte'), reportCsvCell('Assenti'), reportCsvCell('Programmate'), reportCsvCell('Rimandate'), reportCsvCell('Riprogrammate')]) . "\n";
        foreach ($reportData['sommario_clienti'] as $row) {
            echo implode(';', [reportCsvCell(trim($row['nome'] . ' ' . $row['cognome'])), reportCsvCell($row['totale']), reportCsvCell($row['svolta']), reportCsvCell($row['assente']), reportCsvCell($row['programmata']), reportCsvCell($row['rimandata']), reportCsvCell($row['riprogrammata'])]) . "\n";
        }
    } elseif ($reportType === 'entrate') {
        echo implode(';', [reportCsvCell('Mese'), reportCsvCell('Entrate')]) . "\n";
        foreach ($reportData['entrate'] as $row) {
            echo implode(';', [reportCsvCell($row['mese_label']), reportCsvCell(number_format((float)$row['totale'], 2, ',', '.'))]) . "\n";
        }
    } elseif ($reportType === 'statistiche_lezioni') {
        echo implode(';', [reportCsvCell('Sezione'), reportCsvCell('Valore'), reportCsvCell('Totale')]) . "\n";
        foreach ($reportData['statistiche_lezioni']['by_status'] as $row) echo implode(';', [reportCsvCell('Stato'), reportCsvCell($row['stato']), reportCsvCell($row['totale'])]) . "\n";
        foreach ($reportData['statistiche_lezioni']['by_strumento'] as $row) echo implode(';', [reportCsvCell('Strumento'), reportCsvCell($row['strumento']), reportCsvCell($row['totale'])]) . "\n";
        foreach ($reportData['statistiche_lezioni']['by_insegnante'] as $row) echo implode(';', [reportCsvCell('Insegnante'), reportCsvCell(trim($row['nome'] . ' ' . $row['cognome'])), reportCsvCell($row['totale'])]) . "\n";
    } elseif ($reportType === 'calendario_insegnante') {
        echo implode(';', [reportCsvCell('Data'), reportCsvCell('Ora'), reportCsvCell('Cliente'), reportCsvCell('Strumento'), reportCsvCell('Stato')]) . "\n";
        foreach ($reportData['calendario_insegnante']['weeks'] as $week) {
            foreach ($week['days'] as $day) {
                foreach ($day['lessons'] as $lesson) {
                    echo implode(';', [reportCsvCell(formatDate($lesson['data'])), reportCsvCell(substr((string)$lesson['ora_inizio'], 0, 5) . ' - ' . substr((string)$lesson['ora_fine'], 0, 5)), reportCsvCell(trim($lesson['cliente_nome'] . ' ' . $lesson['cliente_cognome'])), reportCsvCell($lesson['strumento'] ?: '—'), reportCsvCell($lesson['stato'])]) . "\n";
                }
            }
        }
    }
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1">Report e statistiche</h2>
        <p class="text-secondary mb-0">Analizza ore, lezioni, entrate e calendario insegnanti.</p>
    </div>
</div>

<?php if ($pageError !== ''): ?>
<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i><?= h($pageError) ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header"><i class="fas fa-filter me-2"></i>Filtri report</div>
    <div class="card-body">
        <form method="get" action="report.php" class="row g-3 align-items-end">
            <div class="col-md-2"><label for="date_from" class="form-label">Dal</label><input type="date" class="form-control" id="date_from" name="date_from" value="<?= h($dateFrom) ?>"></div>
            <div class="col-md-2"><label for="date_to" class="form-label">Al</label><input type="date" class="form-control" id="date_to" name="date_to" value="<?= h($dateTo) ?>"></div>
            <div class="col-md-3">
                <label for="report_type" class="form-label">Tipo report</label>
                <select class="form-select" id="report_type" name="report_type">
                    <option value="ore_insegnanti" <?= $reportType === 'ore_insegnanti' ? 'selected' : '' ?>>Ore insegnanti</option>
                    <option value="sommario_clienti" <?= $reportType === 'sommario_clienti' ? 'selected' : '' ?>>Sommario clienti</option>
                    <option value="entrate" <?= $reportType === 'entrate' ? 'selected' : '' ?>>Entrate</option>
                    <option value="statistiche_lezioni" <?= $reportType === 'statistiche_lezioni' ? 'selected' : '' ?>>Statistiche lezioni</option>
                    <option value="calendario_insegnante" <?= $reportType === 'calendario_insegnante' ? 'selected' : '' ?>>Calendario insegnante</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="insegnante_id" class="form-label">Insegnante</label>
                <select class="form-select" id="insegnante_id" name="insegnante_id">
                    <option value="0">Tutti</option>
                    <?php foreach ($teachers as $teacher): ?>
                    <option value="<?= h((string)$teacher['id']) ?>" <?= $insegnanteId === (int)$teacher['id'] ? 'selected' : '' ?>><?= h(trim($teacher['cognome'] . ' ' . $teacher['nome'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="cliente_id" class="form-label">Cliente</label>
                <select class="form-select" id="cliente_id" name="cliente_id">
                    <option value="0">Tutti</option>
                    <?php foreach ($clients as $client): ?>
                    <option value="<?= h((string)$client['id']) ?>" <?= $clienteId === (int)$client['id'] ? 'selected' : '' ?>><?= h(trim($client['cognome'] . ' ' . $client['nome'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1 d-grid"><button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button></div>
            <div class="col-12 d-flex justify-content-end">
                <a class="btn btn-outline-success" href="<?= h('report.php?' . http_build_query(['report_type' => $reportType, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'insegnante_id' => $insegnanteId, 'cliente_id' => $clienteId, 'export' => 'csv'])) ?>"><i class="fas fa-file-csv me-2"></i>Esporta CSV</a>
            </div>
        </form>
    </div>
</div>

<?php if ($reportType === 'ore_insegnanti'): ?>
<div class="card"><div class="card-header"><i class="fas fa-clock me-2"></i>Ore e guadagni insegnanti</div><div class="card-body"><div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>Insegnante</th><th>Ore</th><th>Tariffa</th><th>Guadagno</th><?php foreach (reportStatuses() as $status): ?><th><?= h($status) ?></th><?php endforeach; ?></tr></thead><tbody><?php foreach ($reportData['ore_insegnanti'] as $row): ?><tr><td><?= h(trim($row['nome'] . ' ' . $row['cognome'])) ?></td><td><?= h(reportHourString((int)$row['minuti_totali'])) ?></td><td>€ <?= h(number_format((float)$row['tariffa_oraria'], 2, ',', '.')) ?></td><td>€ <?= h(number_format((float)$row['guadagno'], 2, ',', '.')) ?></td><td><?= h((string)$row['programmata']) ?></td><td><?= h((string)$row['svolta']) ?></td><td><?= h((string)$row['assente']) ?></td><td><?= h((string)$row['rimandata']) ?></td><td><?= h((string)$row['riprogrammata']) ?></td></tr><?php endforeach; if ($reportData['ore_insegnanti'] === []): ?><tr><td colspan="9" class="text-center text-secondary">Nessun dato disponibile per il periodo selezionato.</td></tr><?php endif; ?></tbody></table></div></div></div>
<?php endif; ?>

<?php if ($reportType === 'sommario_clienti'): ?>
<div class="card"><div class="card-header"><i class="fas fa-users me-2"></i>Sommario clienti</div><div class="card-body"><div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>Cliente</th><th>Totale</th><th>Svolte</th><th>Assenze</th><th>Programmate</th><th>Rimandate</th><th>Riprogrammate</th></tr></thead><tbody><?php foreach ($reportData['sommario_clienti'] as $row): ?><tr><td><?= h(trim($row['nome'] . ' ' . $row['cognome'])) ?></td><td><?= h((string)$row['totale']) ?></td><td><?= h((string)$row['svolta']) ?></td><td><?= h((string)$row['assente']) ?></td><td><?= h((string)$row['programmata']) ?></td><td><?= h((string)$row['rimandata']) ?></td><td><?= h((string)$row['riprogrammata']) ?></td></tr><?php endforeach; if ($reportData['sommario_clienti'] === []): ?><tr><td colspan="7" class="text-center text-secondary">Nessun dato disponibile.</td></tr><?php endif; ?></tbody></table></div></div></div>
<?php endif; ?>

<?php if ($reportType === 'entrate'): ?>
<div class="row g-4">
    <div class="col-lg-5"><div class="card h-100"><div class="card-header"><i class="fas fa-euro-sign me-2"></i>Entrate per mese</div><div class="card-body"><div class="table-responsive"><table class="table table-striped align-middle mb-0"><thead><tr><th>Mese</th><th>Entrate</th></tr></thead><tbody><?php foreach ($reportData['entrate'] as $row): ?><tr><td><?= h((string)$row['mese_label']) ?></td><td>€ <?= h(number_format((float)$row['totale'], 2, ',', '.')) ?></td></tr><?php endforeach; if ($reportData['entrate'] === []): ?><tr><td colspan="2" class="text-center text-secondary">Nessuna entrata registrata nel periodo.</td></tr><?php endif; ?></tbody></table></div></div></div></div>
    <div class="col-lg-7"><div class="card h-100"><div class="card-header"><i class="fas fa-chart-line me-2"></i>Grafico entrate</div><div class="card-body"><canvas id="entrateChart" height="120"></canvas></div></div></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('entrateChart');
    if (!ctx || typeof Chart === 'undefined') return;
    new Chart(ctx, {type: 'bar', data: {labels: <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>, datasets: [{label: 'Entrate', data: <?= json_encode($chartValues, JSON_UNESCAPED_UNICODE) ?>, borderColor: '#4f8cff', backgroundColor: 'rgba(79, 140, 255, 0.35)', borderWidth: 2}]}, options: {responsive: true, maintainAspectRatio: false, scales: {y: {beginAtZero: true, ticks: {callback(value) { return '€ ' + value; }}}}}});
});
</script>
<?php endif; ?>

<?php if ($reportType === 'statistiche_lezioni'): ?>
<div class="row g-4">
    <div class="col-lg-4"><div class="card h-100"><div class="card-header"><i class="fas fa-tags me-2"></i>Per stato</div><div class="card-body"><div class="table-responsive"><table class="table table-striped mb-0"><thead><tr><th>Stato</th><th>Totale</th></tr></thead><tbody><?php foreach ($reportData['statistiche_lezioni']['by_status'] as $row): ?><tr><td><?= statusBadge((string)$row['stato']) ?></td><td><?= h((string)$row['totale']) ?></td></tr><?php endforeach; if ($reportData['statistiche_lezioni']['by_status'] === []): ?><tr><td colspan="2" class="text-center text-secondary">Nessun dato.</td></tr><?php endif; ?></tbody></table></div></div></div></div>
    <div class="col-lg-4"><div class="card h-100"><div class="card-header"><i class="fas fa-guitar me-2"></i>Per strumento</div><div class="card-body"><div class="table-responsive"><table class="table table-striped mb-0"><thead><tr><th>Strumento</th><th>Totale</th></tr></thead><tbody><?php foreach ($reportData['statistiche_lezioni']['by_strumento'] as $row): ?><tr><td><?= h((string)$row['strumento']) ?></td><td><?= h((string)$row['totale']) ?></td></tr><?php endforeach; if ($reportData['statistiche_lezioni']['by_strumento'] === []): ?><tr><td colspan="2" class="text-center text-secondary">Nessun dato.</td></tr><?php endif; ?></tbody></table></div></div></div></div>
    <div class="col-lg-4"><div class="card h-100"><div class="card-header"><i class="fas fa-chalkboard-teacher me-2"></i>Per insegnante</div><div class="card-body"><div class="table-responsive"><table class="table table-striped mb-0"><thead><tr><th>Insegnante</th><th>Totale</th></tr></thead><tbody><?php foreach ($reportData['statistiche_lezioni']['by_insegnante'] as $row): ?><tr><td><?= h(trim($row['cognome'] . ' ' . $row['nome'])) ?></td><td><?= h((string)$row['totale']) ?></td></tr><?php endforeach; if ($reportData['statistiche_lezioni']['by_insegnante'] === []): ?><tr><td colspan="2" class="text-center text-secondary">Nessun dato.</td></tr><?php endif; ?></tbody></table></div></div></div></div>
</div>
<?php endif; ?>

<?php if ($reportType === 'calendario_insegnante'): ?>
<div class="card"><div class="card-header"><i class="fas fa-calendar-week me-2"></i>Calendario insegnante</div><div class="card-body"><?php if (!$reportData['calendario_insegnante']['teacher']): ?><div class="alert alert-info mb-0">Seleziona un insegnante per visualizzare le prossime tre settimane.</div><?php else: ?><div class="mb-3"><h5 class="mb-1"><?= h(trim($reportData['calendario_insegnante']['teacher']['nome'] . ' ' . $reportData['calendario_insegnante']['teacher']['cognome'])) ?></h5><p class="text-secondary mb-0">Vista delle lezioni per settimana.</p></div><?php foreach ($reportData['calendario_insegnante']['weeks'] as $week): ?><div class="mb-4"><div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><h6 class="mb-0"><?= h((string)$week['label']) ?></h6><span class="badge bg-secondary"><?= h((string)$week['range']) ?></span></div><div class="row g-3"><?php foreach ($week['days'] as $day): ?><div class="col-md-6 col-xl"><div class="border rounded p-3 h-100"><div class="fw-semibold mb-2"><?= h((string)$day['label']) ?></div><?php if ($day['lessons'] === []): ?><div class="small text-secondary">Nessuna lezione</div><?php else: ?><div class="d-grid gap-2"><?php foreach ($day['lessons'] as $lesson): ?><div class="p-2 rounded bg-body-tertiary border"><div class="small fw-semibold"><?= h(substr((string)$lesson['ora_inizio'], 0, 5) . ' - ' . substr((string)$lesson['ora_fine'], 0, 5)) ?></div><div><?= h(trim($lesson['cliente_nome'] . ' ' . $lesson['cliente_cognome'])) ?></div><div class="small text-secondary"><?= h($lesson['strumento'] ?: 'Strumento non specificato') ?></div><div class="mt-1"><?= statusBadge((string)$lesson['stato']) ?></div></div><?php endforeach; ?></div><?php endif; ?></div></div><?php endforeach; ?></div></div><?php endforeach; endif; ?></div></div>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php';

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

$totalClients = 0;
$lessonsToday = 0;
$lessonsThisWeek = 0;
$expiringPackagesCount = 0;
$maxExpiringPackages = 10;
$expiringPackageThreshold = 3;
$nextLessons = [];
$expiringPackages = [];
$weekdayChartData = array_fill(0, 7, 0);
$revenueChartData = array_fill(0, 12, 0.0);
$dashboardErrors = [];

function dashboardDecryptName(?string $nome, ?string $cognome): string
{
    return decryptFullName($nome, $cognome, 'N/D');
}

function dashboardWhatsAppNumber(?string $telefono): string
{
    return preg_replace('/[^0-9+]/', '', $telefono === null ? '' : decryptField($telefono));
}

try {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM clienti');
    $stmt->execute();
    $totalClients = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    $dashboardErrors[] = 'Impossibile caricare il totale clienti.';
}

try {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM prenotazioni WHERE data = CURDATE()');
    $stmt->execute();
    $lessonsToday = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    $dashboardErrors[] = 'Impossibile caricare le lezioni di oggi.';
}

try {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM prenotazioni WHERE YEARWEEK(data, 1) = YEARWEEK(CURDATE(), 1)');
    $stmt->execute();
    $lessonsThisWeek = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    $dashboardErrors[] = 'Impossibile caricare le lezioni della settimana.';
}

try {
    $stmt = $pdo->prepare(
        "SELECT p.*,
                COALESCE(c.nome, '') AS nome,
                COALESCE(c.cognome, '') AS cognome,
                COALESCE(i.nome, '') AS ins_nome,
                COALESCE(i.cognome, '') AS ins_cognome
         FROM prenotazioni p
         LEFT JOIN clienti c ON p.cliente_id = c.id
         LEFT JOIN insegnanti i ON p.insegnante_id = i.id
         WHERE p.data >= CURDATE() AND p.stato = 'Programmata'
         ORDER BY p.data ASC, p.ora_inizio ASC
         LIMIT 10"
    );
    $stmt->execute();
    $nextLessons = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Dashboard upcoming lessons error: ' . $e->getMessage());
    $dashboardErrors[] = 'Impossibile caricare le prossime lezioni.';
}

try {
    // NULLIF treats manually imported/stale zero-lesson purchases as "unset" so the
    // package default can be used instead. The HAVING clause intentionally relies on
    // MySQL alias support because this project targets MySQL/MariaDB only.
    $stmt = $pdo->prepare(
        "SELECT
            a.id,
            a.data_acquisto,
            a.stato_pagamento,
            a.importo_pagato,
            a.numero_fattura,
            a.note,
            c.nome,
            c.cognome,
            c.telefono,
            pk.nome AS pacchetto_nome,
            COALESCE(NULLIF(a.numero_lezioni, 0), pk.numero_lezioni, 0) AS lezioni_acquistate,
            COALESCE(ls.lezioni_svolte, 0) AS lezioni_svolte,
            GREATEST(COALESCE(NULLIF(a.numero_lezioni, 0), pk.numero_lezioni, 0) - COALESCE(ls.lezioni_svolte, 0), 0) AS lezioni_rimanenti
         FROM acquisti a
         INNER JOIN clienti c ON a.cliente_id = c.id
         LEFT JOIN pacchetti pk ON a.pacchetto_id = pk.id
         LEFT JOIN (
             SELECT acquisto_id, COUNT(*) AS lezioni_svolte
             FROM prenotazioni
             WHERE stato = 'Svolta' AND acquisto_id IS NOT NULL
             GROUP BY acquisto_id
         ) ls ON ls.acquisto_id = a.id
         WHERE a.stato_pagamento <> 'Rimborso'
         HAVING lezioni_acquistate > 0 AND lezioni_rimanenti > 0 AND lezioni_rimanenti <= ?
         ORDER BY lezioni_rimanenti ASC, a.data_acquisto DESC, a.id DESC"
    );
    $stmt->execute([$expiringPackageThreshold]);
    $expiringPackages = $stmt->fetchAll();
    $expiringPackagesCount = count($expiringPackages);
    $expiringPackages = array_slice($expiringPackages, 0, $maxExpiringPackages);
} catch (PDOException $e) {
    error_log('Dashboard expiring packages error: ' . $e->getMessage());
    $dashboardErrors[] = 'Impossibile caricare i pacchetti quasi esauriti.';
}

try {
    $weekStart = (new DateTimeImmutable('monday this week'))->format('Y-m-d');
    $weekEnd = (new DateTimeImmutable('sunday this week'))->format('Y-m-d');

    $stmt = $pdo->prepare(
        'SELECT WEEKDAY(data) AS weekday_index, COUNT(*) AS cnt
         FROM prenotazioni
         WHERE data BETWEEN ? AND ?
         GROUP BY WEEKDAY(data)'
    );
    $stmt->execute([$weekStart, $weekEnd]);
    foreach ($stmt->fetchAll() as $row) {
        $weekdayIndex = (int)$row['weekday_index'];
        if ($weekdayIndex >= 0 && $weekdayIndex < 7) {
            $weekdayChartData[$weekdayIndex] = (int)$row['cnt'];
        }
    }
} catch (PDOException $e) {
    error_log('Dashboard weekday chart error: ' . $e->getMessage());
    $dashboardErrors[] = 'Impossibile caricare il grafico lezioni per giorno.';
}

try {
    $stmt = $pdo->prepare(
        'SELECT MONTH(data_acquisto) AS m, COALESCE(SUM(importo_pagato), 0) AS totale
         FROM acquisti
         WHERE YEAR(data_acquisto) = YEAR(CURDATE())
           AND stato_pagamento IN (?, ?)
         GROUP BY MONTH(data_acquisto)
         ORDER BY MONTH(data_acquisto) ASC'
    );
    $stmt->execute(['Pagato', 'Parziale']);
    foreach ($stmt->fetchAll() as $row) {
        $monthIndex = ((int)$row['m']) - 1;
        if ($monthIndex >= 0 && $monthIndex < 12) {
            $revenueChartData[$monthIndex] = (float)$row['totale'];
        }
    }
} catch (PDOException $e) {
    error_log('Dashboard revenue chart error: ' . $e->getMessage());
    $dashboardErrors[] = 'Impossibile caricare il grafico ricavi mensili.';
}

$weekdayLabels = ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'];
$monthLabels = ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'];

require_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1">Panoramica</h2>
        <p class="text-secondary mb-0">Stato generale di lezioni, clienti e ricavi.</p>
    </div>
    <span class="badge bg-primary-subtle text-primary-emphasis px-3 py-2">Aggiornato in tempo reale</span>
</div>

<?php if ($dashboardErrors !== []): ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars(implode(' ', $dashboardErrors)) ?>
</div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="stat-icon purple"><i class="fas fa-users"></i></div>
            <div>
                <div class="text-secondary small text-uppercase">Clienti Totali</div>
                <div class="fs-2 fw-bold"><?= htmlspecialchars((string)$totalClients) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="stat-icon blue"><i class="fas fa-calendar-day"></i></div>
            <div>
                <div class="text-secondary small text-uppercase">Lezioni Oggi</div>
                <div class="fs-2 fw-bold"><?= htmlspecialchars((string)$lessonsToday) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="stat-icon green"><i class="fas fa-calendar-week"></i></div>
            <div>
                <div class="text-secondary small text-uppercase">Lezioni Settimana</div>
                <div class="fs-2 fw-bold"><?= htmlspecialchars((string)$lessonsThisWeek) ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="stat-icon orange"><i class="fas fa-box-open"></i></div>
            <div>
                <div class="text-secondary small text-uppercase">Pacchetti in Scadenza</div>
                <div class="fs-2 fw-bold"><?= htmlspecialchars((string)$expiringPackagesCount) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-xl-7">
        <div class="card h-100">
            <div class="card-header">
                <i class="fas fa-clock"></i>
                Prossime 10 lezioni
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Orario</th>
                                <th>Cliente</th>
                                <th>Insegnante</th>
                                <th>Stato</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($nextLessons === []): ?>
                            <tr>
                                <td colspan="5" class="text-center text-secondary py-4">Nessuna lezione programmata.</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($nextLessons as $lesson): ?>
                                <tr>
                                    <td><?= htmlspecialchars(formatDate((string)$lesson['data'])) ?></td>
                                    <td><?= htmlspecialchars(substr((string)$lesson['ora_inizio'], 0, 5) . ' - ' . substr((string)$lesson['ora_fine'], 0, 5)) ?></td>
                                    <td><?= htmlspecialchars(dashboardDecryptName($lesson['nome'], $lesson['cognome'])) ?></td>
                                    <td><?= htmlspecialchars(dashboardDecryptName($lesson['ins_nome'], $lesson['ins_cognome'])) ?></td>
                                    <td><?= statusBadge((string)$lesson['stato']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <div class="card h-100">
            <div class="card-header">
                <i class="fas fa-triangle-exclamation"></i>
                Pacchetti quasi esauriti
            </div>
            <div class="card-body">
                <?php if ($expiringPackages === []): ?>
                <div class="alert alert-success mb-0">
                    <i class="fas fa-check-circle me-2"></i>Nessun pacchetto in scadenza imminente.
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush gap-3">
                    <?php foreach ($expiringPackages as $package):
                        $clienteNome    = dashboardDecryptName($package['nome'], $package['cognome']);
                        $telefonoDigits = dashboardWhatsAppNumber($package['telefono'] ?? null);
                        $pacchettoNome  = (string)($package['pacchetto_nome'] ?: 'Pacchetto sconosciuto');
                        $lezioniRim     = (string)$package['lezioni_rimanenti'];
                        $waMsg          = 'Ciao ' . $clienteNome . ', il tuo pacchetto ' . $pacchettoNome . ' è quasi esaurito (' . $lezioniRim . ' lezioni rimanenti). [messaggio automatico]';
                    ?>
                    <div class="rounded-3 border p-3 package-alert-item">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <div class="fw-semibold"><?= htmlspecialchars($clienteNome) ?></div>
                                <div class="text-secondary small"><?= htmlspecialchars($pacchettoNome) ?></div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <?php if ($telefonoDigits !== ''): ?>
                                <a href="https://wa.me/<?= htmlspecialchars($telefonoDigits) ?>?text=<?= urlencode($waMsg) ?>"
                                   class="btn btn-sm btn-success" target="_blank" rel="noopener noreferrer" title="Contatta via WhatsApp">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                                <?php endif; ?>
                                <span class="badge bg-warning text-dark">
                                    <?= htmlspecialchars($lezioniRim) ?> rim.
                                </span>
                            </div>
                        </div>
                        <div class="small text-secondary mt-2">
                            Svolte: <?= htmlspecialchars((string)$package['lezioni_svolte']) ?> /
                            <?= htmlspecialchars((string)$package['lezioni_acquistate']) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <i class="fas fa-chart-column"></i>
                Lezioni per giorno (settimana corrente)
            </div>
            <div class="card-body">
                <canvas id="weeklyLessonsChart" height="140"></canvas>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <i class="fas fa-chart-line"></i>
                Ricavi mensili (anno corrente)
            </div>
            <div class="card-body">
                <canvas id="monthlyRevenueChart" height="140"></canvas>
            </div>
        </div>
    </div>
</div>

<style>
.text-secondary { color: var(--text-secondary) !important; }
.table-dark {
    --bs-table-bg: var(--bg-card);
    --bs-table-striped-bg: rgba(255,255,255,0.02);
    --bs-table-hover-bg: rgba(124,106,247,0.12);
    --bs-table-color: var(--text-primary);
    --bs-table-border-color: var(--border-color);
}
.package-alert-item {
    background: rgba(255,255,255,0.02);
    border-color: var(--border-color) !important;
}
.stat-icon.orange { background: rgba(249,226,175,0.18); color: #f9e2af; }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const weekdayLabels = <?= json_encode($weekdayLabels, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const weekdayData = <?= json_encode($weekdayChartData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const monthLabels = <?= json_encode($monthLabels, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const revenueData = <?= json_encode($revenueChartData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    const DEFAULT_CHART_TEXT_COLOR = '#a6adc8';
    const cssChartTextColor = getComputedStyle(document.documentElement).getPropertyValue('--text-secondary').trim();
    const chartTextColor = cssChartTextColor && CSS.supports('color', cssChartTextColor)
        ? cssChartTextColor
        : DEFAULT_CHART_TEXT_COLOR;
    const chartGridColor = 'rgba(166, 173, 200, 0.15)';

    function renderChartAvailabilityError(canvas, message) {
        const wrapper = canvas?.parentElement;
        if (!wrapper) {
            return;
        }
        wrapper.innerHTML = `<div class="alert alert-secondary mb-0">${escapeHtml(message)}</div>`;
    }

    const weeklyCanvas = document.getElementById('weeklyLessonsChart');
    if (weeklyCanvas && typeof Chart !== 'undefined') {
        Chart.getChart(weeklyCanvas)?.destroy();
        new Chart(weeklyCanvas, {
            type: 'bar',
            data: {
                labels: weekdayLabels,
                datasets: [{
                    label: 'Lezioni',
                    data: weekdayData,
                    backgroundColor: 'rgba(124, 106, 247, 0.75)',
                    borderColor: 'rgba(124, 106, 247, 1)',
                    borderWidth: 1,
                    borderRadius: 8
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: chartTextColor }, grid: { color: chartGridColor } },
                    y: { beginAtZero: true, ticks: { color: chartTextColor, precision: 0 }, grid: { color: chartGridColor } }
                }
            }
        });
    } else if (weeklyCanvas) {
        renderChartAvailabilityError(weeklyCanvas, 'Grafico lezioni per giorno non disponibile.');
    }

    const revenueCanvas = document.getElementById('monthlyRevenueChart');
    if (revenueCanvas && typeof Chart !== 'undefined') {
        Chart.getChart(revenueCanvas)?.destroy();
        new Chart(revenueCanvas, {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'Ricavi',
                    data: revenueData,
                    borderColor: 'rgba(137, 220, 235, 1)',
                    backgroundColor: 'rgba(137, 220, 235, 0.16)',
                    tension: 0.35,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: 'rgba(137, 220, 235, 1)'
                }]
            },
            options: {
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: (context) => '€ ' + Number(context.raw || 0).toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                        }
                    }
                },
                scales: {
                    x: { ticks: { color: chartTextColor }, grid: { color: chartGridColor } },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: chartTextColor,
                            callback: (value) => '€ ' + Number(value).toLocaleString('it-IT')
                        },
                        grid: { color: chartGridColor }
                    }
                }
            }
        });
    } else if (revenueCanvas) {
        renderChartAvailabilityError(revenueCanvas, 'Grafico ricavi mensili non disponibile.');
    }
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

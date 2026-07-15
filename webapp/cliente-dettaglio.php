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
$clienteId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($clienteId <= 0) {
    setFlash('warning', 'ID cliente non valido.');
    redirect('clienti.php');
}

// ── Helper: normalise HH:MM or HH:MM:SS time strings to HH:MM:SS ──────────────
function normalizeTime(string $t): string {
    return strlen($t) === 5 ? $t . ':00' : substr($t, 0, 8);
}

// ── Handle actions ────────────────────────────────────────────
$requestAction = $_SERVER['REQUEST_METHOD'] === 'POST' ? post('action') : '';

if ($requestAction === 'rinnovo') {
    try {
        verifyCsrf();
        $dataAcquisto   = trim(post('data_acquisto'));
        $pacchettoId    = sanitizeInt(post('pacchetto_id'));
        $importoPagato  = sanitizeFloat(str_replace(',', '.', post('importo_pagato')));
        $statoPagamento = trim(post('stato_pagamento'));
        $numeroFattura  = trim(post('numero_fattura'));
        $note           = trim(post('note'));
        $pianificato    = post('pianificato') === '1' ? 1 : 0;
        $numeroLezioni  = sanitizeInt(post('numero_lezioni'));
        $autoSchedule   = post('auto_schedule') === '1';

        $statiValidi = ['Non Pagato', 'Parziale', 'Pagato', 'Rimborso'];
        if (!in_array($statoPagamento, $statiValidi, true)) {
            setFlash('danger', 'Stato pagamento non valido.');
            redirect("cliente-dettaglio.php?id={$clienteId}&tab=rinnovo");
        }

        $dt = DateTime::createFromFormat('Y-m-d', $dataAcquisto);
        if (!$dt || $dt->format('Y-m-d') !== $dataAcquisto) {
            setFlash('danger', 'Data acquisto non valida.');
            redirect("cliente-dettaglio.php?id={$clienteId}&tab=rinnovo");
        }

        // Get package info for numero_lezioni if not overridden
        if ($pacchettoId > 0 && $numeroLezioni <= 0) {
            $stmtPk = $pdo->prepare('SELECT numero_lezioni FROM pacchetti WHERE id = ? LIMIT 1');
            $stmtPk->execute([$pacchettoId]);
            $pk = $stmtPk->fetch();
            if ($pk) { $numeroLezioni = (int)$pk['numero_lezioni']; }
        }

        $stmt = $pdo->prepare(
            'INSERT INTO acquisti (data_acquisto, cliente_id, pacchetto_id, importo_pagato, stato_pagamento, pianificato, numero_fattura, note, numero_lezioni)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $dataAcquisto, $clienteId,
            $pacchettoId > 0 ? $pacchettoId : null,
            $importoPagato, $statoPagamento, $pianificato,
            $numeroFattura !== '' ? $numeroFattura : null,
            $note !== '' ? $note : null,
            $numeroLezioni,
        ]);
        $nuovoAcquistoId = (int)$pdo->lastInsertId();

        // Auto-schedule lessons if requested
        if ($autoSchedule && $nuovoAcquistoId > 0 && $numeroLezioni > 0) {
            // Get the most recent lessons from the previous package for this client
            $stmtLez = $pdo->prepare(
                'SELECT p.*, a.id AS acq_id
                 FROM prenotazioni p
                 INNER JOIN acquisti a ON a.id = p.acquisto_id
                 WHERE a.cliente_id = ?
                   AND p.acquisto_id != ?
                   AND p.stato = "Programmata"
                 ORDER BY p.data ASC, p.ora_inizio ASC
                 LIMIT ?'
            );
            $stmtLez->execute([$clienteId, $nuovoAcquistoId, $numeroLezioni]);
            $lezioniPrecedenti = $stmtLez->fetchAll();

            if ($lezioniPrecedenti) {
                // Calculate shift: enough full weeks so that the first new lesson
                // falls after the last old lesson, preserving day-of-week patterns.
                $firstDate = new DateTime($lezioniPrecedenti[0]['data']);
                $lastDate  = new DateTime($lezioniPrecedenti[count($lezioniPrecedenti) - 1]['data']);
                $spanInterval = $firstDate->diff($lastDate);
                $spanDays  = $spanInterval->days !== false ? (int)$spanInterval->days : 0;
                // Shift = (number of full weeks in span + 1) * 7 days (DAYS_PER_WEEK)
                $daysPerWeek = 7;
                $shiftDays = ((int)ceil($spanDays / $daysPerWeek) + 1) * $daysPerWeek;

                $stmtIns = $pdo->prepare(
                    'INSERT INTO prenotazioni (data, ora_inizio, ora_fine, cliente_id, insegnante_id, strumento, stato, pacchetto_nome, acquisto_id, note)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );

                // Get package name once if a new package was selected
                $pkName = null;
                if ($pacchettoId > 0) {
                    $stmtPkN = $pdo->prepare('SELECT nome FROM pacchetti WHERE id = ? LIMIT 1');
                    $stmtPkN->execute([$pacchettoId]);
                    $pkRow = $stmtPkN->fetch();
                    if ($pkRow) { $pkName = (string)$pkRow['nome']; }
                }

                $scheduledCount = 0;
                foreach ($lezioniPrecedenti as $lez) {
                    if ($scheduledCount >= $numeroLezioni) { break; }
                    // Shift each lesson by the same number of days to preserve relative spacing
                    $orig = new DateTime($lez['data']);
                    $newLezDate = (clone $orig)->modify("+{$shiftDays} days");

                    $stmtIns->execute([
                        $newLezDate->format('Y-m-d'),
                        $lez['ora_inizio'],
                        $lez['ora_fine'],
                        $clienteId,
                        $lez['insegnante_id'],
                        $lez['strumento'],
                        'Programmata',
                        $pkName ?? $lez['pacchetto_nome'],
                        $nuovoAcquistoId,
                        null,
                    ]);
                    $scheduledCount++;
                }
            }
        }

        setFlash('success', 'Pacchetto rinnovato con successo.');
        redirect("cliente-dettaglio.php?id={$clienteId}&tab=acquisti");
    } catch (PDOException $e) {
        setFlash('danger', 'Errore durante il rinnovo del pacchetto.');
        redirect("cliente-dettaglio.php?id={$clienteId}&tab=rinnovo");
    }
}

if ($requestAction === 'sposta' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrf();
        $lezId          = sanitizeInt(post('prenotazione_id'));
        $nuovaData      = trim(post('nuova_data'));
        $nuovaOraInizio = trim(post('nuova_ora_inizio'));
        $nuovaOraFine   = trim(post('nuova_ora_fine'));

        $dt = DateTime::createFromFormat('Y-m-d', $nuovaData);
        if ($lezId <= 0 || !$dt || $dt->format('Y-m-d') !== $nuovaData) {
            jsonResponse(['success' => false, 'message' => 'Dati non validi.'], 422);
        }

        $nuovaOraInizio = normalizeTime($nuovaOraInizio);
        $nuovaOraFine   = normalizeTime($nuovaOraFine);

        if (strtotime('1970-01-01 ' . $nuovaOraFine) <= strtotime('1970-01-01 ' . $nuovaOraInizio)) {
            jsonResponse(['success' => false, 'message' => 'L\'ora di fine deve essere successiva all\'ora di inizio.'], 422);
        }

        $stmt = $pdo->prepare('UPDATE prenotazioni SET data = ?, ora_inizio = ?, ora_fine = ? WHERE id = ? AND cliente_id = ?');
        $stmt->execute([$nuovaData, $nuovaOraInizio, $nuovaOraFine, $lezId, $clienteId]);

        if ($stmt->rowCount() === 0) {
            jsonResponse(['success' => false, 'message' => 'Lezione non trovata.'], 404);
        }
        jsonResponse(['success' => true, 'message' => 'Lezione spostata con successo.']);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => 'Errore durante lo spostamento.'], 500);
    }
}

// ── Load data ─────────────────────────────────────────────────
$cliente   = null;
$lezioni   = [];
$acquisti  = [];
$pacchetti = [];
$pageError = '';

try {
    $stmt = $pdo->prepare('SELECT * FROM clienti WHERE id = ? LIMIT 1');
    $stmt->execute([$clienteId]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cliente) {
        setFlash('warning', 'Cliente non trovato.');
        redirect('clienti.php');
    }

    $stmt = $pdo->prepare(
        'SELECT pr.*, i.nome AS ins_nome, i.cognome AS ins_cognome
         FROM prenotazioni pr
         LEFT JOIN insegnanti i ON i.id = pr.insegnante_id
         WHERE pr.cliente_id = ?
         ORDER BY pr.data DESC, pr.ora_inizio DESC
         LIMIT 200'
    );
    $stmt->execute([$clienteId]);
    $lezioni = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare(
        'SELECT a.*, p.nome AS pacchetto_nome, p.numero_lezioni AS pk_numero_lezioni,
                COALESCE(ls.lezioni_svolte, 0) AS lezioni_svolte,
                (COALESCE(a.numero_lezioni, p.numero_lezioni, 0) - COALESCE(ls.lezioni_svolte, 0)) AS lezioni_rimanenti
         FROM acquisti a
         LEFT JOIN pacchetti p ON p.id = a.pacchetto_id
         LEFT JOIN (
             SELECT acquisto_id, COUNT(*) AS lezioni_svolte
             FROM prenotazioni
             WHERE stato = "Svolta" AND acquisto_id IS NOT NULL
             GROUP BY acquisto_id
         ) ls ON ls.acquisto_id = a.id
         WHERE a.cliente_id = ?
         ORDER BY a.data_acquisto DESC, a.id DESC'
    );
    $stmt->execute([$clienteId]);
    $acquisti = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare('SELECT id, nome, prezzo, numero_lezioni, strumento FROM pacchetti ORDER BY nome ASC');
    $stmt->execute();
    $pacchetti = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pageError = 'Impossibile caricare i dati del cliente.';
}

// Decrypt client fields
$nomeCliente    = decryptField((string)($cliente['nome']     ?? ''));
$cognomeCliente = decryptField((string)($cliente['cognome']  ?? ''));
$nomeCompleto   = trim($nomeCliente . ' ' . $cognomeCliente);
$telefono       = decryptField((string)($cliente['telefono']       ?? ''));
$email          = decryptField((string)($cliente['email']          ?? ''));
$indirizzo      = decryptField((string)($cliente['indirizzo']      ?? ''));
$cf             = decryptField((string)($cliente['codice_fiscale'] ?? ''));
$telefonoDigits = preg_replace('/[^0-9+]/', '', $telefono);

// Determine last package for rinnovo pre-fill
$ultimoAcquisto = !empty($acquisti) ? $acquisti[0] : null;
$activeTab = get('tab', 'dati');
if (!in_array($activeTab, ['dati', 'lezioni', 'acquisti', 'rinnovo'], true)) { $activeTab = 'dati'; }

$statusOptions = ['Programmata', 'Svolta', 'Assente', 'Rimandata', 'Riprogrammata'];

require_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1">
            <i class="fas fa-user me-2"></i><?= htmlspecialchars($nomeCompleto) ?>
        </h2>
        <p class="text-secondary mb-0">Dettaglio cliente</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="clienti.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Torna ai Clienti
        </a>
        <?php if ($telefono !== '' && $telefonoDigits !== ''): ?>
        <a href="https://wa.me/<?= htmlspecialchars($telefonoDigits) ?>"
           class="btn btn-success" target="_blank" rel="noopener noreferrer">
            <i class="fab fa-whatsapp me-2"></i>WhatsApp
        </a>
        <?php endif; ?>
        <a href="api/export-cliente-pdf.php?id=<?= $clienteId ?>&tipo=futuri"
           class="btn btn-primary" target="_blank">
            <i class="fas fa-file-pdf me-2"></i>PDF Lezioni Future
        </a>
        <a href="api/export-cliente-pdf.php?id=<?= $clienteId ?>&tipo=storico"
           class="btn btn-secondary" target="_blank">
            <i class="fas fa-file-pdf me-2"></i>PDF Storico
        </a>
    </div>
</div>

<?php if ($pageError !== ''): ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($pageError) ?>
</div>
<?php endif; ?>

<!-- Nav Tabs -->
<ul class="nav nav-tabs mb-3" id="clienteTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'dati' ? 'active' : '' ?>"
                data-bs-toggle="tab" data-bs-target="#tab-dati" type="button" role="tab">
            <i class="fas fa-user me-2"></i>Dati
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'lezioni' ? 'active' : '' ?>"
                data-bs-toggle="tab" data-bs-target="#tab-lezioni" type="button" role="tab">
            <i class="fas fa-calendar me-2"></i>Lezioni
            <span class="badge bg-secondary ms-1"><?= count($lezioni) ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'acquisti' ? 'active' : '' ?>"
                data-bs-toggle="tab" data-bs-target="#tab-acquisti" type="button" role="tab">
            <i class="fas fa-shopping-cart me-2"></i>Acquisti
            <span class="badge bg-secondary ms-1"><?= count($acquisti) ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'rinnovo' ? 'active' : '' ?>"
                data-bs-toggle="tab" data-bs-target="#tab-rinnovo" type="button" role="tab">
            <i class="fas fa-sync me-2"></i>Rinnovo Veloce
        </button>
    </li>
</ul>

<div class="tab-content" id="clienteTabsContent">

    <!-- ── Tab Dati ──────────────────────────────────────── -->
    <div class="tab-pane fade <?= $activeTab === 'dati' ? 'show active' : '' ?>" id="tab-dati" role="tabpanel">
        <div class="card">
            <div class="card-body">
                <dl class="row g-3">
                    <dt class="col-sm-3">Nome Completo</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars($nomeCompleto) ?: '—' ?></dd>

                    <dt class="col-sm-3">Telefono</dt>
                    <dd class="col-sm-9">
                        <?= htmlspecialchars($telefono) ?: '—' ?>
                        <?php if ($telefonoDigits): ?>
                        <a href="https://wa.me/<?= htmlspecialchars($telefonoDigits) ?>"
                           class="btn btn-sm btn-success ms-2" target="_blank" rel="noopener noreferrer">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-3">Email</dt>
                    <dd class="col-sm-9">
                        <?php if ($email): ?>
                        <a href="mailto:<?= htmlspecialchars($email) ?>"><?= htmlspecialchars($email) ?></a>
                        <?php else: ?>—<?php endif; ?>
                    </dd>

                    <dt class="col-sm-3">Indirizzo</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars($indirizzo) ?: '—' ?></dd>

                    <dt class="col-sm-3">Codice Fiscale</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars($cf) ?: '—' ?></dd>

                    <?php if (!empty($cliente['note'])): ?>
                    <dt class="col-sm-3">Note</dt>
                    <dd class="col-sm-9"><?= nl2br(htmlspecialchars((string)$cliente['note'])) ?></dd>
                    <?php endif; ?>

                    <?php if (!empty($cliente['mega_cartella_pubblica'])): ?>
                    <dt class="col-sm-3">MEGA Pubblica</dt>
                    <dd class="col-sm-9">
                        <a href="<?= htmlspecialchars((string)$cliente['mega_cartella_pubblica']) ?>"
                           target="_blank" rel="noopener noreferrer">
                            <i class="fas fa-folder-open me-1"></i>Apri cartella
                        </a>
                    </dd>
                    <?php endif; ?>

                    <?php if (!empty($cliente['mega_cartella_locale'])): ?>
                    <dt class="col-sm-3">MEGA Locale</dt>
                    <dd class="col-sm-9">
                        <a href="<?= htmlspecialchars((string)$cliente['mega_cartella_locale']) ?>"
                           target="_blank" rel="noopener noreferrer">
                            <i class="fas fa-desktop me-1"></i>Apri cartella locale
                        </a>
                    </dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>

    <!-- ── Tab Lezioni ──────────────────────────────────── -->
    <div class="tab-pane fade <?= $activeTab === 'lezioni' ? 'show active' : '' ?>" id="tab-lezioni" role="tabpanel">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Orario</th>
                                <th>Insegnante</th>
                                <th>Strumento</th>
                                <th>Stato</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($lezioni === []): ?>
                            <tr><td colspan="6" class="text-center text-secondary py-4">Nessuna lezione trovata.</td></tr>
                            <?php else: foreach ($lezioni as $lez):
                                $insNome = trim(decryptField((string)($lez['ins_nome'] ?? '')) . ' ' . decryptField((string)($lez['ins_cognome'] ?? '')));
                            ?>
                            <tr>
                                <td><?= htmlspecialchars(formatDate((string)$lez['data'])) ?></td>
                                <td><?= htmlspecialchars(substr((string)$lez['ora_inizio'], 0, 5) . '–' . substr((string)$lez['ora_fine'], 0, 5)) ?></td>
                                <td><?= htmlspecialchars($insNome ?: '—') ?></td>
                                <td><?= htmlspecialchars((string)($lez['strumento'] ?? '—')) ?></td>
                                <td><?= statusBadge((string)($lez['stato'] ?? '')) ?></td>
                                <td>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-warning btn-sposta"
                                            data-id="<?= (int)$lez['id'] ?>"
                                            data-data="<?= htmlspecialchars((string)$lez['data']) ?>"
                                            data-inizio="<?= htmlspecialchars(substr((string)$lez['ora_inizio'], 0, 5)) ?>"
                                            data-fine="<?= htmlspecialchars(substr((string)$lez['ora_fine'], 0, 5)) ?>">
                                        <i class="fas fa-arrows-alt me-1"></i>Sposta
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Tab Acquisti ─────────────────────────────────── -->
    <div class="tab-pane fade <?= $activeTab === 'acquisti' ? 'show active' : '' ?>" id="tab-acquisti" role="tabpanel">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Pacchetto</th>
                                <th>Lezioni</th>
                                <th>Importo</th>
                                <th>Stato</th>
                                <th>Fattura</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($acquisti === []): ?>
                            <tr><td colspan="6" class="text-center text-secondary py-4">Nessun acquisto trovato.</td></tr>
                            <?php else: foreach ($acquisti as $acq):
                                $totLez = (int)($acq['numero_lezioni'] ?: $acq['pk_numero_lezioni'] ?? 0);
                                $svolte = (int)$acq['lezioni_svolte'];
                                $rim    = (int)$acq['lezioni_rimanenti'];
                            ?>
                            <tr>
                                <td><?= htmlspecialchars(formatDate((string)$acq['data_acquisto'])) ?></td>
                                <td><?= htmlspecialchars((string)($acq['pacchetto_nome'] ?: 'Pacchetto manuale')) ?></td>
                                <td>
                                    <?= htmlspecialchars((string)$svolte) ?>/<?= htmlspecialchars((string)$totLez) ?>
                                    <?php if ($rim > 0 && $rim <= 3): ?>
                                    <span class="badge bg-warning eb-remaining-badge ms-1"><?= $rim ?> rim.</span>
                                    <?php elseif ($rim <= 0 && $totLez > 0): ?>
                                    <span class="badge bg-success ms-1">Completato</span>
                                    <?php endif; ?>
                                </td>
                                <td>€ <?= htmlspecialchars(number_format((float)$acq['importo_pagato'], 2, ',', '.')) ?></td>
                                <td><?= paymentBadge((string)$acq['stato_pagamento']) ?></td>
                                <td><?= htmlspecialchars((string)($acq['numero_fattura'] ?? '—')) ?: '—' ?></td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Tab Rinnovo ──────────────────────────────────── -->
    <div class="tab-pane fade <?= $activeTab === 'rinnovo' ? 'show active' : '' ?>" id="tab-rinnovo" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-sync me-2"></i>Rinnovo Veloce Pacchetto
            </div>
            <div class="card-body">
                <?php if ($ultimoAcquisto): ?>
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    Ultimo pacchetto: <strong><?= htmlspecialchars((string)($ultimoAcquisto['pacchetto_nome'] ?: 'Pacchetto manuale')) ?></strong>
                    (<?= htmlspecialchars((string)$ultimoAcquisto['lezioni_svolte']) ?>/<?= htmlspecialchars((string)($ultimoAcquisto['numero_lezioni'] ?: $ultimoAcquisto['pk_numero_lezioni'] ?? 0)) ?> lezioni svolte)
                </div>
                <?php endif; ?>

                <form method="post" action="cliente-dettaglio.php?id=<?= $clienteId ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="rinnovo">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="rinnovo_data" class="form-label">Data acquisto *</label>
                            <input type="date" class="form-control" id="rinnovo_data" name="data_acquisto"
                                   value="<?= htmlspecialchars(date('Y-m-d')) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="rinnovo_pacchetto" class="form-label">Pacchetto *</label>
                            <select class="form-select" id="rinnovo_pacchetto" name="pacchetto_id" required>
                                <option value="">Seleziona pacchetto</option>
                                <?php foreach ($pacchetti as $pk): ?>
                                <option value="<?= (int)$pk['id'] ?>"
                                        data-prezzo="<?= (float)$pk['prezzo'] ?>"
                                        data-lezioni="<?= (int)$pk['numero_lezioni'] ?>"
                                    <?= ($ultimoAcquisto && (int)$ultimoAcquisto['pacchetto_id'] === (int)$pk['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string)$pk['nome']) ?> – <?= htmlspecialchars((string)$pk['numero_lezioni']) ?> lez. – € <?= htmlspecialchars(number_format((float)$pk['prezzo'], 2, ',', '.')) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="rinnovo_importo" class="form-label">Importo (€) *</label>
                            <input type="number" class="form-control" id="rinnovo_importo" name="importo_pagato"
                                   step="0.01" min="0"
                                   value="<?= $ultimoAcquisto ? htmlspecialchars(number_format((float)$ultimoAcquisto['importo_pagato'], 2, '.', '')) : '0.00' ?>"
                                   required>
                        </div>
                        <div class="col-md-4">
                            <label for="rinnovo_stato" class="form-label">Stato pagamento *</label>
                            <select class="form-select" id="rinnovo_stato" name="stato_pagamento" required>
                                <?php foreach (['Non Pagato', 'Parziale', 'Pagato'] as $stato): ?>
                                <option value="<?= htmlspecialchars($stato) ?>"><?= htmlspecialchars($stato) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="rinnovo_numero_lezioni" class="form-label">N. Lezioni</label>
                            <input type="number" class="form-control" id="rinnovo_numero_lezioni" name="numero_lezioni"
                                   min="0"
                                   value="<?= $ultimoAcquisto ? htmlspecialchars((string)($ultimoAcquisto['numero_lezioni'] ?: $ultimoAcquisto['pk_numero_lezioni'] ?? 0)) : '0' ?>">
                            <div class="form-text">Lascia 0 per usare il numero di lezioni del pacchetto selezionato.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="rinnovo_fattura" class="form-label">N. Fattura</label>
                            <input type="text" class="form-control" id="rinnovo_fattura" name="numero_fattura">
                        </div>
                        <div class="col-12">
                            <label for="rinnovo_note" class="form-label">Note</label>
                            <textarea class="form-control" id="rinnovo_note" name="note" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="rinnovo_auto_schedule"
                                       name="auto_schedule" value="1">
                                <label class="form-check-label" for="rinnovo_auto_schedule">
                                    Pianifica lezioni automaticamente (copia gli orari del pacchetto precedente)
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sync me-2"></i>Rinnova Pacchetto
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ── Modal Sposta Lezione ──────────────────────────────────── -->
<style>
[data-theme="light"] .eb-remaining-badge { color: #1e1e2e !important; }
[data-theme="dark"] .eb-remaining-badge { color: var(--text-primary) !important; }
</style>

<div class="modal fade" id="spostaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-arrows-alt me-2"></i>Sposta Lezione</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="sposta_id" value="">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="sposta_data" class="form-label">Nuova data *</label>
                        <input type="date" class="form-control" id="sposta_data" required>
                    </div>
                    <div class="col-6">
                        <label for="sposta_ora_inizio" class="form-label">Ora inizio *</label>
                        <input type="time" class="form-control" id="sposta_ora_inizio" required>
                    </div>
                    <div class="col-6">
                        <label for="sposta_ora_fine" class="form-label">Ora fine *</label>
                        <input type="time" class="form-control" id="sposta_ora_fine" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-warning" id="spostaConfirmBtn">
                    <i class="fas fa-arrows-alt me-2"></i>Sposta
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Auto-fill importo when package is selected
    const pacchettoSel = document.getElementById('rinnovo_pacchetto');
    const importoInput = document.getElementById('rinnovo_importo');
    const lezioniInput = document.getElementById('rinnovo_numero_lezioni');
    if (pacchettoSel) {
        pacchettoSel.addEventListener('change', () => {
            const opt = pacchettoSel.options[pacchettoSel.selectedIndex];
            const prezzo  = parseFloat(opt.dataset.prezzo  || 0);
            const lezioni = parseInt(opt.dataset.lezioni || 0, 10);
            if (prezzo  > 0 && importoInput) { importoInput.value = prezzo.toFixed(2); }
            if (lezioni > 0 && lezioniInput) { lezioniInput.value = lezioni; }
        });
    }

    // Sposta buttons
    const spostaModal = new bootstrap.Modal(document.getElementById('spostaModal'));
    document.querySelectorAll('.btn-sposta').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('sposta_id').value         = btn.dataset.id;
            document.getElementById('sposta_data').value       = btn.dataset.data;
            document.getElementById('sposta_ora_inizio').value = btn.dataset.inizio;
            document.getElementById('sposta_ora_fine').value   = btn.dataset.fine;
            spostaModal.show();
        });
    });

    document.getElementById('spostaConfirmBtn')?.addEventListener('click', async () => {
        const id       = document.getElementById('sposta_id').value;
        const data     = document.getElementById('sposta_data').value;
        const oraInizio = document.getElementById('sposta_ora_inizio').value;
        const oraFine   = document.getElementById('sposta_ora_fine').value;
        if (!id || !data || !oraInizio || !oraFine) {
            showToast('Compilare tutti i campi obbligatori.', 'warning');
            return;
        }
        try {
            const fd = new FormData();
            fd.append('action', 'sposta');
            fd.append('prenotazione_id', id);
            fd.append('nuova_data', data);
            fd.append('nuova_ora_inizio', oraInizio);
            fd.append('nuova_ora_fine', oraFine);
            fd.append('csrf_token', getCsrfToken());
            const resp = await fetch('cliente-dettaglio.php?id=<?= $clienteId ?>', { method: 'POST', body: fd });
            const result = await safeJsonResponse(resp);
            if (!result.success) { throw new Error(result.message || 'Errore durante lo spostamento.'); }
            showToast(result.message, 'success');
            spostaModal.hide();
            setTimeout(() => window.location.reload(), 800);
        } catch (err) {
            showToast(err.message, 'danger');
        }
    });
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

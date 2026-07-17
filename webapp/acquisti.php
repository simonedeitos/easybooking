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

function purchaseStatuses(): array
{
    return ['Non Pagato', 'Parziale', 'Pagato', 'Rimborso'];
}

function purchaseNullableString(mixed $value): ?string
{
    $value = trim((string)$value);
    return $value === '' ? null : $value;
}

function purchaseValidDate(string $date): bool
{
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt !== false && $dt->format('Y-m-d') === $date;
}

function purchasePaymentBadge(string $status): string
{
    if ($status === 'Rimborso') {
        return '<span class="badge bg-info">' . htmlspecialchars($status) . '</span>';
    }
    return paymentBadge($status);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestAction = post('action');
    try {
        if ($requestAction === 'save') {
            verifyCsrfOrRedirect('acquisti.php');

            $id = sanitizeInt(post('id'));
            $dataAcquisto = trim(post('data_acquisto'));
            $clienteId = sanitizeInt(post('cliente_id'));
            $pacchettoId = sanitizeInt(post('pacchetto_id'));
            $pacchettoId = $pacchettoId > 0 ? $pacchettoId : null;
            $importoPagato = sanitizeFloat(str_replace(',', '.', post('importo_pagato')));
            $statoPagamento = trim(post('stato_pagamento'));
            $pianificato = post('pianificato') === '1' ? 1 : 0;
            $numeroFattura = purchaseNullableString(post('numero_fattura'));
            $note = purchaseNullableString(post('note'));
            $numeroLezioni = max(0, sanitizeInt(post('numero_lezioni')));

            if (!purchaseValidDate($dataAcquisto)) {
                setFlash('warning', 'Data acquisto non valida.');
                redirect('acquisti.php');
            }
            if ($clienteId <= 0) {
                setFlash('warning', 'Il cliente è obbligatorio.');
                redirect('acquisti.php');
            }
            if (!in_array($statoPagamento, purchaseStatuses(), true)) {
                setFlash('warning', 'Stato pagamento non valido.');
                redirect('acquisti.php');
            }
            if ($importoPagato < 0) {
                setFlash('warning', 'L\'importo pagato non può essere negativo.');
                redirect('acquisti.php');
            }

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM clienti WHERE id = ?');
            $stmt->execute([$clienteId]);
            if ((int)$stmt->fetchColumn() === 0) {
                setFlash('warning', 'Cliente non trovato.');
                redirect('acquisti.php');
            }

            if ($pacchettoId !== null) {
                $stmt = $pdo->prepare('SELECT numero_lezioni FROM pacchetti WHERE id = ? LIMIT 1');
                $stmt->execute([$pacchettoId]);
                $packageLessons = $stmt->fetchColumn();
                if ($packageLessons === false) {
                    setFlash('warning', 'Pacchetto non trovato.');
                    redirect('acquisti.php');
                }
                if ($numeroLezioni <= 0) {
                    $numeroLezioni = (int)$packageLessons;
                }
            }

            if ($numeroLezioni <= 0) {
                setFlash('warning', 'Il numero di lezioni deve essere maggiore di zero.');
                redirect('acquisti.php');
            }

            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE acquisti
                     SET data_acquisto = ?, cliente_id = ?, pacchetto_id = ?, importo_pagato = ?, stato_pagamento = ?, pianificato = ?, numero_fattura = ?, note = ?, numero_lezioni = ?
                     WHERE id = ?'
                );
                $stmt->execute([$dataAcquisto, $clienteId, $pacchettoId, $importoPagato, $statoPagamento, $pianificato, $numeroFattura, $note, $numeroLezioni, $id]);
                setFlash('success', 'Acquisto aggiornato con successo.');
                redirect('acquisti.php');
            }

            $stmt = $pdo->prepare(
                'INSERT INTO acquisti (data_acquisto, cliente_id, pacchetto_id, importo_pagato, stato_pagamento, pianificato, numero_fattura, note, numero_lezioni)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$dataAcquisto, $clienteId, $pacchettoId, $importoPagato, $statoPagamento, $pianificato, $numeroFattura, $note, $numeroLezioni]);
            setFlash('success', 'Acquisto creato con successo.');
            redirect('acquisti.php');
        } elseif ($requestAction === 'delete') {
            verifyCsrfOrRedirect('acquisti.php');
            $id = sanitizeInt(post('id'));
            if ($id <= 0) {
                setFlash('warning', 'Acquisto non valido.');
                redirect('acquisti.php');
            }

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM prenotazioni WHERE acquisto_id = ?');
            $stmt->execute([$id]);
            if ((int)$stmt->fetchColumn() > 0) {
                setFlash('warning', 'Impossibile eliminare l\'acquisto: sono presenti prenotazioni collegate.');
                redirect('acquisti.php');
            }

            $stmt = $pdo->prepare('DELETE FROM acquisti WHERE id = ?');
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 0) {
                setFlash('warning', 'Acquisto non trovato.');
                redirect('acquisti.php');
            }

            setFlash('success', 'Acquisto eliminato con successo.');
            redirect('acquisti.php');
        } elseif ($requestAction === 'update_payment') {
            verifyCsrfOrRedirect('acquisti.php');
            $id = sanitizeInt(post('id'));
            $status = trim(post('stato_pagamento'));
            if ($id <= 0 || !in_array($status, purchaseStatuses(), true)) {
                setFlash('warning', 'Dati non validi per aggiornare il pagamento.');
                redirect('acquisti.php');
            }

            $stmt = $pdo->prepare('UPDATE acquisti SET stato_pagamento = ? WHERE id = ?');
            $stmt->execute([$status, $id]);
            setFlash('success', 'Stato pagamento aggiornato.');
            redirect('acquisti.php');
        }
    } catch (Throwable $e) {
        error_log('[acquisti.php] ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        setFlash('danger', 'Errore durante l\'operazione richiesta.');
        redirect('acquisti.php');
    }
}

$purchases = [];
$clients = [];
$packages = [];
$alerts = [];
$pageError = '';
$statusOptions = purchaseStatuses();

try {
    $stmt = $pdo->prepare(
        'SELECT a.*, c.nome AS cliente_nome, c.cognome AS cliente_cognome, pk.nome AS pacchetto_nome,
                COALESCE(a.numero_lezioni, pk.numero_lezioni, 0) AS totale_lezioni,
                COALESCE(ls.lezioni_svolte, 0) AS lezioni_svolte,
                (COALESCE(a.numero_lezioni, pk.numero_lezioni, 0) - COALESCE(ls.lezioni_svolte, 0)) AS lezioni_rimanenti,
                COALESCE(pk.durata_minuti, 60) AS durata_minuti,
                COALESCE(pk.strumento, \'\') AS strumento
         FROM acquisti a
         INNER JOIN clienti c ON c.id = a.cliente_id
         LEFT JOIN pacchetti pk ON pk.id = a.pacchetto_id
         LEFT JOIN (
             SELECT acquisto_id, COUNT(*) AS lezioni_svolte
             FROM prenotazioni
             WHERE stato = ? AND acquisto_id IS NOT NULL
             GROUP BY acquisto_id
         ) ls ON ls.acquisto_id = a.id
         ORDER BY a.data_acquisto DESC, a.id DESC'
    );
    $stmt->execute(['Svolta']);
    $purchases = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT id, nome, cognome FROM clienti ORDER BY cognome ASC, nome ASC');
    $stmt->execute();
    $clients = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT id, nome, prezzo, numero_lezioni, durata_minuti, strumento FROM pacchetti ORDER BY nome ASC');
    $stmt->execute();
    $packages = $stmt->fetchAll();

    // Load insegnanti with their strumenti for the scheduling modal
    $stmtIns = $pdo->query(
        'SELECT i.id, i.nome, i.cognome,
                GROUP_CONCAT(s.nome ORDER BY s.nome SEPARATOR \',\') AS strumenti
         FROM insegnanti i
         LEFT JOIN insegnanti_strumenti ins ON ins.insegnante_id = i.id
         LEFT JOIN strumenti s ON s.id = ins.strumento_id
         GROUP BY i.id, i.nome, i.cognome
         ORDER BY i.cognome ASC, i.nome ASC'
    );
    $insegnanti = $stmtIns->fetchAll(PDO::FETCH_ASSOC);

    foreach ($purchases as $purchase) {
        $remaining = (int)$purchase['lezioni_rimanenti'];
        $total = (int)$purchase['totale_lezioni'];
        if ($total > 0 && $remaining >= 1 && $remaining <= 3 && (string)$purchase['stato_pagamento'] !== 'Rimborso') {
            $alerts[] = $purchase;
        }
    }
} catch (PDOException $e) {
    $pageError = 'Impossibile caricare gli acquisti.';
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1">Acquisti</h2>
        <p class="text-secondary mb-0">Gestisci vendite pacchetti, pagamenti e lezioni residue.</p>
    </div>
    <button type="button" class="btn btn-primary" id="newPurchaseBtn">
        <i class="fas fa-plus me-2"></i>Nuovo Acquisto
    </button>
</div>

<?php if ($pageError !== ''): ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($pageError) ?>
</div>
<?php endif; ?>


<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="purchase-filter-from" class="form-label">Data da</label>
                <input type="date" class="form-control" id="purchase-filter-from">
            </div>
            <div class="col-md-3">
                <label for="purchase-filter-to" class="form-label">Data a</label>
                <input type="date" class="form-control" id="purchase-filter-to">
            </div>
            <div class="col-md-3">
                <label for="purchase-filter-client" class="form-label">Cliente</label>
                <select class="form-select" id="purchase-filter-client">
                    <option value="">Tutti</option>
                    <?php foreach ($clients as $client): ?>
                    <option value="<?= htmlspecialchars((string)$client['id']) ?>"><?= htmlspecialchars(trim((string)$client['nome'] . ' ' . (string)$client['cognome'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="purchase-filter-status" class="form-label">Stato pagamento</label>
                <select class="form-select" id="purchase-filter-status">
                    <option value="">Tutti</option>
                    <?php foreach ($statusOptions as $statusOption): ?>
                    <option value="<?= htmlspecialchars($statusOption) ?>"><?= htmlspecialchars($statusOption) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-shopping-cart"></i>
        Elenco acquisti
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable align-middle" id="acquistiTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Pacchetto</th>
                        <th>N° Lezioni</th>
                        <th>Importo Pagato</th>
                        <th>Stato Pagamento</th>
                        <th>Fattura</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($purchases as $purchase): ?>
                    <tr
                        data-date="<?= htmlspecialchars((string)$purchase['data_acquisto']) ?>"
                        data-client="<?= htmlspecialchars((string)$purchase['cliente_id']) ?>"
                        data-status="<?= htmlspecialchars((string)$purchase['stato_pagamento']) ?>"
                    >
                        <td><?= htmlspecialchars((string)$purchase['id']) ?></td>
                        <td data-order="<?= htmlspecialchars((string)$purchase['data_acquisto']) ?>">
                            <div><?= htmlspecialchars(formatDate((string)$purchase['data_acquisto'])) ?></div>
                            <?php if ((int)$purchase['pianificato'] === 1): ?>
                            <div class="small text-info">Pianificato</div>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars(trim((string)$purchase['cliente_nome'] . ' ' . (string)$purchase['cliente_cognome'])) ?></td>
                        <td><?= htmlspecialchars((string)($purchase['pacchetto_nome'] ?: 'Pacchetto manuale')) ?></td>
                        <td>
                            <div><?= htmlspecialchars((string)$purchase['lezioni_svolte']) ?> / <?= htmlspecialchars((string)$purchase['totale_lezioni']) ?></div>
                            <div class="small text-secondary">Rimaste: <?= htmlspecialchars((string)$purchase['lezioni_rimanenti']) ?></div>
                        </td>
                        <td>€ <?= htmlspecialchars(number_format((float)$purchase['importo_pagato'], 2, ',', '.')) ?></td>
                        <td>
                            <div class="mb-2"><?= purchasePaymentBadge((string)$purchase['stato_pagamento']) ?></div>
                            <form method="post" action="acquisti.php">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="update_payment">
                                <input type="hidden" name="id" value="<?= htmlspecialchars((string)$purchase['id']) ?>">
                                <select class="form-select form-select-sm" name="stato_pagamento" onchange="this.form.submit()">
                                    <?php foreach ($statusOptions as $statusOption): ?>
                                    <option value="<?= htmlspecialchars($statusOption) ?>" <?= (string)$purchase['stato_pagamento'] === $statusOption ? 'selected' : '' ?>><?= htmlspecialchars($statusOption) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                        <td><?= htmlspecialchars((string)($purchase['numero_fattura'] ?: '—')) ?></td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary btn-edit-purchase"
                                        data-edit="<?= htmlspecialchars(json_encode($purchase), ENT_QUOTES) ?>">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <?php if ((int)$purchase['pianificato'] === 0): ?>
                                <button type="button" class="btn btn-sm btn-outline-success btn-schedule-purchase"
                                        title="Pianifica Lezioni"
                                        data-acquisto-id="<?= htmlspecialchars((string)$purchase['id']) ?>"
                                        data-cliente="<?= htmlspecialchars(trim((string)$purchase['cliente_nome'] . ' ' . (string)$purchase['cliente_cognome'])) ?>"
                                        data-pacchetto="<?= htmlspecialchars((string)($purchase['pacchetto_nome'] ?: 'Pacchetto manuale')) ?>"
                                        data-numero-lezioni="<?= htmlspecialchars((string)$purchase['numero_lezioni']) ?>"
                                        data-strumento="<?= htmlspecialchars((string)($purchase['strumento'] ?? '')) ?>"
                                        data-durata="<?= htmlspecialchars((string)($purchase['durata_minuti'] ?? '60')) ?>">
                                    <i class="fas fa-calendar-plus"></i>
                                </button>
                                <?php else: ?>
                                <span class="badge bg-success align-self-center" title="Lezioni già pianificate">
                                    <i class="fas fa-calendar-check me-1"></i>Pianificato
                                </span>
                                <?php endif; ?>
                                <form method="post" action="acquisti.php" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars((string)$purchase['id']) ?>">
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                            onclick="confirmDelete(this.form, '<?= htmlspecialchars(trim((string)$purchase['cliente_nome'] . ' ' . (string)$purchase['cliente_cognome']), ENT_QUOTES) ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="purchaseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="purchaseForm" method="post" action="acquisti.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="purchaseModalTitle">Nuovo Acquisto</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                </div>
                <div class="modal-body">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="purchase_id" value="">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="purchase_data_acquisto" class="form-label">Data acquisto *</label>
                            <input type="date" class="form-control" id="purchase_data_acquisto" name="data_acquisto" required>
                        </div>
                        <div class="col-md-4">
                            <label for="purchase_cliente_id" class="form-label">Cliente *</label>
                            <select class="form-select" id="purchase_cliente_id" name="cliente_id" required>
                                <option value="">Seleziona cliente</option>
                                <?php foreach ($clients as $client): ?>
                                <option value="<?= htmlspecialchars((string)$client['id']) ?>"><?= htmlspecialchars(trim((string)$client['nome'] . ' ' . (string)$client['cognome'])) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="purchase_pacchetto_id" class="form-label">Pacchetto</label>
                            <select class="form-select" id="purchase_pacchetto_id" name="pacchetto_id">
                                <option value="">Seleziona pacchetto</option>
                                <?php foreach ($packages as $package): ?>
                                <option
                                    value="<?= htmlspecialchars((string)$package['id']) ?>"
                                    data-prezzo="<?= htmlspecialchars(number_format((float)$package['prezzo'], 2, '.', '')) ?>"
                                    data-lezioni="<?= htmlspecialchars((string)$package['numero_lezioni']) ?>"
                                    data-durata="<?= htmlspecialchars((string)$package['durata_minuti']) ?>"
                                    data-strumento="<?= htmlspecialchars((string)($package['strumento'] ?? '')) ?>"
                                >
                                    <?= htmlspecialchars((string)$package['nome']) ?><?= !empty($package['strumento']) ? ' · ' . htmlspecialchars((string)$package['strumento']) : '' ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="purchase_importo_pagato" class="form-label">Importo pagato</label>
                            <input type="number" class="form-control" id="purchase_importo_pagato" name="importo_pagato" min="0" step="0.01" value="0.00">
                        </div>
                        <div class="col-md-4">
                            <label for="purchase_stato_pagamento" class="form-label">Stato pagamento *</label>
                            <select class="form-select" id="purchase_stato_pagamento" name="stato_pagamento" required>
                                <?php foreach ($statusOptions as $statusOption): ?>
                                <option value="<?= htmlspecialchars($statusOption) ?>" <?= $statusOption === 'Non Pagato' ? 'selected' : '' ?>><?= htmlspecialchars($statusOption) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="purchase_numero_lezioni" class="form-label">Numero lezioni *</label>
                            <input type="number" class="form-control" id="purchase_numero_lezioni" name="numero_lezioni" min="1" required>
                        </div>
                        <div class="col-md-6 d-flex align-items-center">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" value="1" id="purchase_pianificato" name="pianificato">
                                <label class="form-check-label" for="purchase_pianificato">Pianificato</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="purchase_numero_fattura" class="form-label">Numero fattura</label>
                            <input type="text" class="form-control" id="purchase_numero_fattura" name="numero_fattura">
                        </div>
                        <div class="col-12">
                            <label for="purchase_note" class="form-label">Note</label>
                            <textarea class="form-control" id="purchase_note" name="note" rows="4"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Salva Acquisto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Scheduling Modal ───────────────────────────────────────────────────── -->
<div class="modal fade" id="scheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scheduleModalTitle">
                    <i class="fas fa-calendar-plus me-2"></i>Pianifica Lezioni
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>
            <div class="modal-body">
                <!-- Phase A: Summary (read-only) -->
                <div id="schedulePhaseA">
                    <h6 class="mb-3 text-info"><i class="fas fa-info-circle me-2"></i>Riepilogo acquisto</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label text-secondary">Cliente</label>
                            <div id="sched_cliente" class="form-control-plaintext fw-semibold"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-secondary">Pacchetto</label>
                            <div id="sched_pacchetto" class="form-control-plaintext fw-semibold"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-secondary">Lezioni da pianificare</label>
                            <div id="sched_numero_lezioni" class="form-control-plaintext fw-semibold"></div>
                        </div>
                    </div>
                    <hr>
                </div>

                <!-- Phase B: Scheduling parameters -->
                <div id="schedulePhaseB">
                    <h6 class="mb-3 text-info"><i class="fas fa-sliders-h me-2"></i>Parametri pianificazione</h6>

                    <input type="hidden" id="sched_acquisto_id">

                    <div class="row g-3">
                        <!-- Strumento -->
                        <div class="col-md-4">
                            <label for="sched_strumento" class="form-label">Strumento</label>
                            <input type="text" class="form-control" id="sched_strumento" placeholder="es. Chitarra"
                                   list="sched_strumento_list">
                            <datalist id="sched_strumento_list">
                                <?php foreach ($packages as $pkg): ?>
                                <?php if (!empty($pkg['strumento'])): ?>
                                <option value="<?= htmlspecialchars((string)$pkg['strumento']) ?>">
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <!-- Insegnante -->
                        <div class="col-md-8">
                            <label for="sched_insegnante_id" class="form-label">Insegnante</label>
                            <select class="form-select" id="sched_insegnante_id">
                                <option value="piu_libero">⭐ Insegnante più libero (automatico)</option>
                                <?php foreach ($insegnanti as $ins): ?>
                                <option value="<?= htmlspecialchars((string)$ins['id']) ?>"
                                        data-strumenti="<?= htmlspecialchars((string)($ins['strumenti'] ?? '')) ?>">
                                    <?= htmlspecialchars(trim((string)$ins['cognome'] . ' ' . (string)$ins['nome'])) ?>
                                    <?php if (!empty($ins['strumenti'])): ?>
                                    · <small class="text-secondary"><?= htmlspecialchars((string)$ins['strumenti']) ?></small>
                                    <?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Frequenza -->
                        <div class="col-md-6">
                            <label for="sched_frequenza" class="form-label">
                                Frequenza
                                <i class="fas fa-question-circle text-secondary ms-1" style="cursor:help;"
                                   title="Settimanale: 1 lezione ogni 7 giorni. Bisettimanale: 1 lezione ogni 14 giorni (ogni 2 settimane, NON 2 lezioni/settimana). Mensile: 1 lezione al mese. Multi-giorno settimanale (NUOVO): 2+ lezioni a settimana su giorni scelti."></i>
                            </label>
                            <select class="form-select" id="sched_frequenza">
                                <option value="Settimanale">Settimanale — 1 lezione ogni 7 giorni</option>
                                <option value="Bisettimanale">Bisettimanale — 1 lezione ogni 14 giorni</option>
                                <option value="Mensile">Mensile — 1 lezione al mese</option>
                                <option value="MultiGiornoSettimanale">Multi-giorno settimanale — 2+ lezioni/settimana su giorni diversi</option>
                            </select>
                        </div>

                        <!-- Data inizio -->
                        <div class="col-md-3">
                            <label for="sched_data_inizio" class="form-label">Data inizio</label>
                            <input type="date" class="form-control" id="sched_data_inizio">
                        </div>

                        <!-- Durata -->
                        <div class="col-md-3">
                            <label for="sched_durata" class="form-label">Durata (minuti)</label>
                            <input type="number" class="form-control" id="sched_durata" min="15" max="480" step="5" value="60">
                        </div>

                        <!-- Giorno singolo (for non-multi) -->
                        <div class="col-md-4" id="sched_giorno_singolo_wrap">
                            <label for="sched_giorno_singolo" class="form-label">Giorno settimana</label>
                            <select class="form-select" id="sched_giorno_singolo">
                                <option value="1">Lunedì</option>
                                <option value="2">Martedì</option>
                                <option value="3">Mercoledì</option>
                                <option value="4">Giovedì</option>
                                <option value="5">Venerdì</option>
                                <option value="6">Sabato</option>
                                <option value="7">Domenica</option>
                            </select>
                            <div class="form-text">Cambiando giorno verrà ricalcolata la data inizio.</div>
                        </div>

                        <!-- Ora inizio singola (non-multi) -->
                        <div class="col-md-4" id="sched_ora_singola_wrap">
                            <label for="sched_ora_inizio" class="form-label">Orario inizio</label>
                            <input type="time" class="form-control" id="sched_ora_inizio" value="15:00">
                        </div>

                        <!-- Numero lezioni -->
                        <div class="col-md-4">
                            <label for="sched_numero_lezioni_form" class="form-label">Numero lezioni</label>
                            <input type="number" class="form-control" id="sched_numero_lezioni_form" min="1" value="1">
                        </div>

                        <!-- Multi-day: checkbox giorni + per-day times -->
                        <div class="col-12" id="sched_multi_giorno_wrap" style="display:none;">
                            <label class="form-label">Giorni della settimana e orari</label>
                            <div class="form-text mb-2">
                                Seleziona 2+ giorni. Puoi impostare un orario diverso per ciascun giorno o usare un orario unico.
                            </div>
                            <div id="sched_multi_giorni_list" class="row g-2">
                                <?php
                                $giorniNomi = ['Lunedì','Martedì','Mercoledì','Giovedì','Venerdì','Sabato','Domenica'];
                                foreach ($giorniNomi as $idx => $nomeGiorno):
                                    $dayNum = $idx + 1;
                                ?>
                                <div class="col-md-6 d-flex align-items-center gap-3">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input sched-multi-day-check" type="checkbox"
                                               id="sched_day_<?= $dayNum ?>" value="<?= $dayNum ?>">
                                        <label class="form-check-label" for="sched_day_<?= $dayNum ?>"
                                               style="min-width:90px;"><?= $nomeGiorno ?></label>
                                    </div>
                                    <input type="time" class="form-control form-control-sm sched-day-time"
                                           id="sched_day_time_<?= $dayNum ?>" value="15:00"
                                           data-day="<?= $dayNum ?>"
                                           disabled style="max-width:130px;">
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Live preview -->
                    <div class="mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0 text-info"><i class="fas fa-eye me-2"></i>Anteprima lezioni</h6>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="sched_btn_preview">
                                <i class="fas fa-sync me-1"></i>Aggiorna anteprima
                            </button>
                        </div>
                        <div id="sched_preview_container">
                            <div class="text-secondary small">Compila i parametri e clicca "Aggiorna anteprima".</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-primary" id="sched_btn_confirm" disabled>
                    <i class="fas fa-calendar-check me-2"></i>Conferma Pianificazione
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.text-secondary { color: var(--text-secondary) !important; }
.sched-preview-row { padding: 4px 8px; border-radius: 4px; margin-bottom: 4px; font-size: .875rem; }
.sched-preview-row.ok   { background: rgba(25,135,84,.12); }
.sched-preview-row.warn { background: rgba(220,53,69,.12); color: var(--bs-danger); }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const purchaseModalEl = document.getElementById('purchaseModal');
    const purchaseModal = new bootstrap.Modal(purchaseModalEl);
    const purchaseForm = document.getElementById('purchaseForm');
    const packageSelect = document.getElementById('purchase_pacchetto_id');
    let table = null;

    function resetPurchaseForm() {
        purchaseForm.reset();
        document.getElementById('purchase_id').value = '';
        document.getElementById('purchaseModalTitle').textContent = 'Nuovo Acquisto';
        document.getElementById('purchase_importo_pagato').value = '0.00';
        document.getElementById('purchase_stato_pagamento').value = 'Non Pagato';
    }

    function applyPackageDefaults(forceLessons = false) {
        const option = packageSelect.selectedOptions[0];
        if (!option || !option.dataset.prezzo) {
            return;
        }
        document.getElementById('purchase_importo_pagato').value = Number(option.dataset.prezzo || 0).toFixed(2);
        const lessonsField = document.getElementById('purchase_numero_lezioni');
        if (forceLessons || !lessonsField.value) {
            lessonsField.value = option.dataset.lezioni || '';
        }
    }

    function initPurchaseTable() {
        if (typeof $ === 'undefined' || typeof $.fn.DataTable === 'undefined') {
            return;
        }
        const searchFn = function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'acquistiTable') {
                return true;
            }
            const row = settings.aoData[dataIndex] ? settings.aoData[dataIndex].nTr : null;
            if (!row) {
                return true;
            }
            const dateValue = row.dataset.date || '';
            const clientValue = row.dataset.client || '';
            const statusValue = row.dataset.status || '';
            const fromValue = document.getElementById('purchase-filter-from').value || '';
            const toValue = document.getElementById('purchase-filter-to').value || '';
            const clientFilter = document.getElementById('purchase-filter-client').value || '';
            const statusFilter = document.getElementById('purchase-filter-status').value || '';

            if (fromValue !== '' && dateValue < fromValue) return false;
            if (toValue !== '' && dateValue > toValue) return false;
            if (clientFilter !== '' && clientValue !== clientFilter) return false;
            if (statusFilter !== '' && statusValue !== statusFilter) return false;
            return true;
        };

        $.fn.dataTable.ext.search.push(searchFn);
        table = $('#acquistiTable').DataTable({
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/it-IT.json' },
            pageLength: 25,
            responsive: true,
            order: [[1, 'desc'], [0, 'desc']],
            dom: '<"row align-items-center mb-3"<"col-sm-6"l><"col-sm-6 text-end"f>>rt<"row mt-3"<"col-sm-6"i><"col-sm-6"p>>',
            columnDefs: [{ orderable: false, targets: [6, 8] }]
        });

        ['purchase-filter-from', 'purchase-filter-to', 'purchase-filter-client', 'purchase-filter-status'].forEach((id) => {
            document.getElementById(id)?.addEventListener('change', () => table.draw());
        });
    }

    document.getElementById('newPurchaseBtn')?.addEventListener('click', () => {
        resetPurchaseForm();
        purchaseModal.show();
    });

    packageSelect.addEventListener('change', () => applyPackageDefaults(true));

    document.querySelectorAll('.btn-edit-purchase').forEach((button) => {
        button.addEventListener('click', () => {
            try {
                const purchase = JSON.parse(button.dataset.edit);
                resetPurchaseForm();
                document.getElementById('purchaseModalTitle').textContent = 'Modifica Acquisto';
                document.getElementById('purchase_id').value = purchase.id || '';
                document.getElementById('purchase_data_acquisto').value = purchase.data_acquisto || '';
                document.getElementById('purchase_cliente_id').value = purchase.cliente_id || '';
                document.getElementById('purchase_pacchetto_id').value = purchase.pacchetto_id || '';
                document.getElementById('purchase_importo_pagato').value = Number(purchase.importo_pagato || 0).toFixed(2);
                document.getElementById('purchase_stato_pagamento').value = purchase.stato_pagamento || 'Non Pagato';
                document.getElementById('purchase_pianificato').checked = Number(purchase.pianificato || 0) === 1;
                document.getElementById('purchase_numero_fattura').value = purchase.numero_fattura || '';
                document.getElementById('purchase_note').value = purchase.note || '';
                document.getElementById('purchase_numero_lezioni').value = purchase.numero_lezioni || '';
                purchaseModal.show();
            } catch (e) {
                showToast('Errore nel caricamento dell\'acquisto.', 'danger');
            }
        });
    });

    purchaseModalEl.addEventListener('hidden.bs.modal', resetPurchaseForm);

    // ── Scheduling Modal ───────────────────────────────────────────────────────
    const scheduleModalEl = document.getElementById('scheduleModal');
    const scheduleModal   = new bootstrap.Modal(scheduleModalEl);
    const csrfTokenValue  = document.querySelector('#purchaseForm input[name="csrf_token"]')?.value || '';

    /** Adjusts sched_data_inizio to the next occurrence of the selected weekday. */
    function adjustDateToDay(dayNum) {
        const dateField = document.getElementById('sched_data_inizio');
        if (!dateField.value) return;
        const d    = new Date(dateField.value + 'T00:00:00');
        const cur  = d.getDay() === 0 ? 7 : d.getDay(); // 1=Mon…7=Sun
        let diff   = dayNum - cur;
        if (diff < 0) diff += 7;
        if (diff > 0) {
            d.setDate(d.getDate() + diff);
            dateField.value = d.toISOString().slice(0, 10);
        }
    }

    /** Toggles visibility of multi-day vs single-day controls based on selected frequency. */
    function updateFrequencyUI() {
        const freq = document.getElementById('sched_frequenza').value;
        const isMulti = freq === 'MultiGiornoSettimanale';
        document.getElementById('sched_giorno_singolo_wrap').style.display = isMulti ? 'none' : '';
        document.getElementById('sched_ora_singola_wrap').style.display    = isMulti ? 'none' : '';
        document.getElementById('sched_multi_giorno_wrap').style.display   = isMulti ? '' : 'none';
        // Reset preview when frequency changes
        resetPreview();
    }

    function resetPreview() {
        document.getElementById('sched_preview_container').innerHTML =
            '<div class="text-secondary small">Compila i parametri e clicca "Aggiorna anteprima".</div>';
        document.getElementById('sched_btn_confirm').disabled = true;
    }

    /** Opens the schedule modal for the given acquisto. */
    function openScheduleModal(btn) {
        const acquistoId    = btn.dataset.acquistoId;
        const cliente       = btn.dataset.cliente;
        const pacchetto     = btn.dataset.pacchetto;
        const numeroLezioni = btn.dataset.numeroLezioni;
        const strumento     = btn.dataset.strumento || '';
        const durata        = btn.dataset.durata || '60';

        // Phase A
        document.getElementById('sched_cliente').textContent        = cliente;
        document.getElementById('sched_pacchetto').textContent      = pacchetto;
        document.getElementById('sched_numero_lezioni').textContent = numeroLezioni + ' lezioni';

        // Phase B defaults
        document.getElementById('sched_acquisto_id').value          = acquistoId;
        document.getElementById('sched_strumento').value            = strumento;
        document.getElementById('sched_durata').value               = durata;
        document.getElementById('sched_numero_lezioni_form').value  = numeroLezioni;
        document.getElementById('sched_insegnante_id').value        = 'piu_libero';
        document.getElementById('sched_frequenza').value            = 'Settimanale';

        // Default data_inizio = today
        const today = new Date();
        document.getElementById('sched_data_inizio').value = today.toISOString().slice(0, 10);

        // Default single day = day of week of today
        const dayNum = today.getDay() === 0 ? 7 : today.getDay();
        document.getElementById('sched_giorno_singolo').value = String(dayNum);

        // Reset multi-day checkboxes and times
        document.querySelectorAll('.sched-multi-day-check').forEach(cb => {
            cb.checked = false;
        });
        document.querySelectorAll('.sched-day-time').forEach(el => {
            el.value   = '15:00';
            el.disabled = true;
        });

        // Reset preview & confirm button
        resetPreview();
        updateFrequencyUI();

        // Filter insegnanti by strumento if known
        filterInsegnanti(strumento);

        scheduleModal.show();
    }

    /** Greys out insegnanti that don't teach the specified strumento. */
    function filterInsegnanti(strumento) {
        const sel = document.getElementById('sched_insegnante_id');
        for (const opt of sel.options) {
            if (opt.value === 'piu_libero') continue;
            if (!strumento) {
                opt.dataset.hidden = '';
                opt.style.display  = '';
                continue;
            }
            const insStrumenti = (opt.dataset.strumenti || '').split(',').map(s => s.trim().toLowerCase());
            const matches = insStrumenti.includes(strumento.toLowerCase());
            opt.dataset.hidden = matches ? '' : '1';
            opt.style.display  = matches ? '' : 'none';
        }
        // Reset to "più libero" if current selection is filtered out
        const selected = sel.selectedOptions[0];
        if (selected && selected.dataset.hidden === '1') {
            sel.value = 'piu_libero';
        }
    }

    /** Collects schedule parameters from the form. */
    function collectScheduleParams() {
        const freq   = document.getElementById('sched_frequenza').value;
        const isMulti = freq === 'MultiGiornoSettimanale';

        let giorni = [];
        let orariPerGiorno = {};
        const oraInizio = document.getElementById('sched_ora_inizio').value || '15:00';

        if (isMulti) {
            document.querySelectorAll('.sched-multi-day-check:checked').forEach(cb => {
                const day = parseInt(cb.value, 10);
                giorni.push(day);
                const timeInput = document.getElementById('sched_day_time_' + day);
                orariPerGiorno[day] = timeInput ? (timeInput.value || oraInizio) : oraInizio;
            });
        } else {
            giorni = [parseInt(document.getElementById('sched_giorno_singolo').value, 10)];
        }

        return {
            acquisto_id    : document.getElementById('sched_acquisto_id').value,
            insegnante_id  : document.getElementById('sched_insegnante_id').value,
            strumento      : document.getElementById('sched_strumento').value,
            frequenza      : freq,
            giorni         : JSON.stringify(giorni),
            ora_inizio     : oraInizio,
            orari_per_giorno: Object.keys(orariPerGiorno).length > 0 ? JSON.stringify(orariPerGiorno) : '',
            data_inizio    : document.getElementById('sched_data_inizio').value,
            durata_minuti  : document.getElementById('sched_durata').value,
            numero_lezioni : document.getElementById('sched_numero_lezioni_form').value,
            csrf_token     : csrfTokenValue,
        };
    }

    /** Calls the preview endpoint and renders the results. */
    function runPreview() {
        const container = document.getElementById('sched_preview_container');
        container.innerHTML = '<div class="text-secondary small"><i class="fas fa-spinner fa-spin me-1"></i>Caricamento anteprima…</div>';
        document.getElementById('sched_btn_confirm').disabled = true;

        const params = collectScheduleParams();

        const freq   = document.getElementById('sched_frequenza').value;
        const isMulti = freq === 'MultiGiornoSettimanale';
        const giorni = JSON.parse(params.giorni || '[]');

        if (isMulti && giorni.length === 0) {
            container.innerHTML = '<div class="text-warning small"><i class="fas fa-exclamation-triangle me-1"></i>Seleziona almeno un giorno per la modalità multi-giorno.</div>';
            return;
        }
        if (!params.data_inizio) {
            container.innerHTML = '<div class="text-warning small">Inserisci una data di inizio.</div>';
            return;
        }

        const body = new URLSearchParams({ ...params, action: 'preview_schedule' });
        fetch('api/pianificazione-api.php', {
            method : 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body   : body.toString(),
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                container.innerHTML = '<div class="text-danger small"><i class="fas fa-times-circle me-1"></i>' +
                    (data.message || 'Errore nella generazione dell\'anteprima.') + '</div>';
                return;
            }

            const lezioni = data.lezioni || [];
            if (lezioni.length === 0) {
                container.innerHTML = '<div class="text-secondary small">Nessuna lezione generata.</div>';
                return;
            }

            const conflitti = lezioni.filter(l => l.conflitto).length;
            let html = '<div class="mb-2 small text-secondary">' + lezioni.length + ' lezioni generate' +
                (conflitti > 0 ? ' · <span class="text-danger">' + conflitti + ' conflitti</span>' : '') + '</div>';

            lezioni.forEach((l, i) => {
                const cls = l.conflitto ? 'warn' : 'ok';
                const ico = l.conflitto
                    ? '<i class="fas fa-exclamation-triangle me-1"></i>'
                    : '<i class="fas fa-check me-1"></i>';
                html += '<div class="sched-preview-row ' + cls + '">' +
                    ico + '<strong>' + (i + 1) + '.</strong> ' +
                    l.giorno + ' ' + (l.data_it || l.data) + ' · ' + l.ora_inizio + '–' + l.ora_fine +
                    (l.conflitto ? ' <em>(conflitto)</em>' : '') + '</div>';
            });

            if (conflitti > 0) {
                html += '<div class="mt-2 small text-warning"><i class="fas fa-info-circle me-1"></i>' +
                    'Le lezioni in conflitto NON saranno inserite. Potrai crearle manualmente in seguito.</div>';
            }

            container.innerHTML = html;
            document.getElementById('sched_btn_confirm').disabled = false;
        })
        .catch(() => {
            container.innerHTML = '<div class="text-danger small"><i class="fas fa-times-circle me-1"></i>Errore di rete. Riprova.</div>';
        });
    }

    /** Calls the confirm endpoint and handles the result. */
    function runConfirm() {
        const btn = document.getElementById('sched_btn_confirm');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Pianificazione…';

        const params = collectScheduleParams();
        const body   = new URLSearchParams({ ...params, action: 'confirm_schedule' });

        fetch('api/pianificazione-api.php', {
            method : 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body   : body.toString(),
        })
        .then(r => r.json())
        .then(data => {
            scheduleModal.hide();
            if (data.success) {
                showToast(data.message || 'Pianificazione completata.', 'success');
                // Reload to reflect updated pianificato status
                setTimeout(() => window.location.reload(), 1200);
            } else {
                showToast(data.message || 'Errore durante la pianificazione.', 'danger');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-calendar-check me-2"></i>Conferma Pianificazione';
            }
        })
        .catch(() => {
            showToast('Errore di rete. Riprova.', 'danger');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-calendar-check me-2"></i>Conferma Pianificazione';
        });
    }

    // ── Event listeners ───────────────────────────────────────────────────────

    // Open modal from table button
    document.querySelectorAll('.btn-schedule-purchase').forEach(btn => {
        btn.addEventListener('click', () => openScheduleModal(btn));
    });

    // Frequency change → show/hide controls
    document.getElementById('sched_frequenza')?.addEventListener('change', updateFrequencyUI);

    // Single-day select → adjust data_inizio
    document.getElementById('sched_giorno_singolo')?.addEventListener('change', function() {
        adjustDateToDay(parseInt(this.value, 10));
        resetPreview();
    });

    // data_inizio manual change → sync giorno singolo
    document.getElementById('sched_data_inizio')?.addEventListener('change', function() {
        if (!this.value) return;
        const d = new Date(this.value + 'T00:00:00');
        const dayNum = d.getDay() === 0 ? 7 : d.getDay();
        document.getElementById('sched_giorno_singolo').value = String(dayNum);
        resetPreview();
    });

    // Multi-day checkboxes → enable/disable per-day time inputs + reset preview
    document.querySelectorAll('.sched-multi-day-check').forEach(cb => {
        cb.addEventListener('change', function() {
            const timeInput = document.getElementById('sched_day_time_' + this.value);
            if (timeInput) timeInput.disabled = !this.checked;
            resetPreview();
        });
    });

    // Any relevant input change → reset preview
    ['sched_strumento', 'sched_insegnante_id', 'sched_durata', 'sched_numero_lezioni_form', 'sched_ora_inizio']
        .forEach(id => {
            document.getElementById(id)?.addEventListener('change', resetPreview);
        });
    document.querySelectorAll('.sched-day-time').forEach(el => {
        el.addEventListener('change', resetPreview);
    });

    // Strumento change → filter insegnanti
    document.getElementById('sched_strumento')?.addEventListener('input', function() {
        filterInsegnanti(this.value);
        resetPreview();
    });

    // Preview button
    document.getElementById('sched_btn_preview')?.addEventListener('click', runPreview);

    // Confirm button
    document.getElementById('sched_btn_confirm')?.addEventListener('click', runConfirm);

    // Reset modal on close
    scheduleModalEl?.addEventListener('hidden.bs.modal', () => {
        resetPreview();
        document.getElementById('sched_btn_confirm').disabled = true;
    });

    initPurchaseTable();
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

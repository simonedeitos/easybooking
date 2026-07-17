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
                (COALESCE(a.numero_lezioni, pk.numero_lezioni, 0) - COALESCE(ls.lezioni_svolte, 0)) AS lezioni_rimanenti
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

    $stmt = $pdo->prepare('SELECT id, nome, prezzo, numero_lezioni, strumento FROM pacchetti ORDER BY nome ASC');
    $stmt->execute();
    $packages = $stmt->fetchAll();

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

<style>
.text-secondary { color: var(--text-secondary) !important; }
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
    initPurchaseTable();
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

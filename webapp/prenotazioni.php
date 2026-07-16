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

function bookingStatuses(): array
{
    return ['Programmata', 'Svolta', 'Assente', 'Rimandata', 'Riprogrammata'];
}

function bookingNullableString(mixed $value): ?string
{
    $value = trim((string)$value);
    return $value === '' ? null : $value;
}

function bookingValidDate(string $date): bool
{
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt !== false && $dt->format('Y-m-d') === $date;
}

function bookingValidTime(string $time): bool
{
    return preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time) === 1;
}

function bookingNormalizeTime(string $time): string
{
    $time = trim($time);
    return strlen($time) === 5 ? $time . ':00' : substr($time, 0, 8);
}

function bookingTimeRangeValid(string $start, string $end): bool
{
    return strtotime('1970-01-01 ' . $end) > strtotime('1970-01-01 ' . $start);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestAction = post('action');
    try {
        if ($requestAction === 'save') {
            verifyCsrfOrRedirect('prenotazioni.php');

            $id = sanitizeInt(post('id'));
            $data = trim(post('data'));
            $oraInizio = bookingNormalizeTime(post('ora_inizio'));
            $oraFine = bookingNormalizeTime(post('ora_fine'));
            $clienteId = sanitizeInt(post('cliente_id'));
            $insegnanteId = sanitizeInt(post('insegnante_id'));
            $stato = trim(post('stato'));
            $strumento = bookingNullableString(post('strumento'));
            $pacchettoNome = bookingNullableString(post('pacchetto_nome'));
            $acquistoId = sanitizeInt(post('acquisto_id'));
            $note = bookingNullableString(post('note'));
            $acquistoId = $acquistoId > 0 ? $acquistoId : null;

            if (!bookingValidDate($data) || !bookingValidTime($oraInizio) || !bookingValidTime($oraFine)) {
                setFlash('warning', 'Data o orario non validi.');
                redirect('prenotazioni.php');
            }
            if ($clienteId <= 0 || $insegnanteId <= 0) {
                setFlash('warning', 'Cliente e insegnante sono obbligatori.');
                redirect('prenotazioni.php');
            }
            if (!in_array($stato, bookingStatuses(), true)) {
                setFlash('warning', 'Stato non valido.');
                redirect('prenotazioni.php');
            }
            if (!bookingTimeRangeValid($oraInizio, $oraFine)) {
                setFlash('warning', 'L\'ora di fine deve essere successiva all\'ora di inizio.');
                redirect('prenotazioni.php');
            }

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM clienti WHERE id = ?');
            $stmt->execute([$clienteId]);
            if ((int)$stmt->fetchColumn() === 0) {
                setFlash('warning', 'Cliente non trovato.');
                redirect('prenotazioni.php');
            }

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM insegnanti WHERE id = ?');
            $stmt->execute([$insegnanteId]);
            if ((int)$stmt->fetchColumn() === 0) {
                setFlash('warning', 'Insegnante non trovato.');
                redirect('prenotazioni.php');
            }

            if ($acquistoId !== null) {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM acquisti WHERE id = ?');
                $stmt->execute([$acquistoId]);
                if ((int)$stmt->fetchColumn() === 0) {
                    setFlash('warning', 'Acquisto collegato non trovato.');
                    redirect('prenotazioni.php');
                }
            }

            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE prenotazioni
                     SET data = ?, ora_inizio = ?, ora_fine = ?, cliente_id = ?, insegnante_id = ?, strumento = ?, stato = ?, pacchetto_nome = ?, acquisto_id = ?, note = ?
                     WHERE id = ?'
                );
                $stmt->execute([$data, $oraInizio, $oraFine, $clienteId, $insegnanteId, $strumento, $stato, $pacchettoNome, $acquistoId, $note, $id]);
                setFlash('success', 'Prenotazione aggiornata con successo.');
                redirect('prenotazioni.php');
            }

            $stmt = $pdo->prepare(
                'INSERT INTO prenotazioni (data, ora_inizio, ora_fine, cliente_id, insegnante_id, strumento, stato, pacchetto_nome, acquisto_id, note)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$data, $oraInizio, $oraFine, $clienteId, $insegnanteId, $strumento, $stato, $pacchettoNome, $acquistoId, $note]);

            setFlash('success', 'Prenotazione creata con successo.');
            redirect('prenotazioni.php');
        } elseif ($requestAction === 'delete') {
            verifyCsrfOrRedirect('prenotazioni.php');
            $id = sanitizeInt(post('id'));
            if ($id <= 0) {
                setFlash('warning', 'Prenotazione non valida.');
                redirect('prenotazioni.php');
            }

            $stmt = $pdo->prepare('DELETE FROM prenotazioni WHERE id = ?');
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 0) {
                setFlash('warning', 'Prenotazione non trovata.');
                redirect('prenotazioni.php');
            }

            setFlash('success', 'Prenotazione eliminata con successo.');
            redirect('prenotazioni.php');
        } elseif ($requestAction === 'update_status') {
            verifyCsrfOrRedirect('prenotazioni.php');
            $id = sanitizeInt(post('id'));
            $stato = trim(post('stato'));
            if ($id <= 0 || !in_array($stato, bookingStatuses(), true)) {
                setFlash('warning', 'Dati non validi per aggiornare lo stato.');
                redirect('prenotazioni.php');
            }

            $stmt = $pdo->prepare('UPDATE prenotazioni SET stato = ? WHERE id = ?');
            $stmt->execute([$stato, $id]);
            setFlash('success', 'Stato aggiornato con successo.');
            redirect('prenotazioni.php');
        } elseif ($requestAction === 'bulk_status') {
            verifyCsrfOrRedirect('prenotazioni.php');
            $stato = trim(post('stato'));
            $ids = $_POST['ids'] ?? [];
            if (!in_array($stato, bookingStatuses(), true)) {
                setFlash('warning', 'Stato non valido.');
                redirect('prenotazioni.php');
            }
            if (!is_array($ids) || $ids === []) {
                setFlash('warning', 'Seleziona almeno una prenotazione.');
                redirect('prenotazioni.php');
            }

            $cleanIds = [];
            foreach ($ids as $bookingId) {
                $cleanId = (int)$bookingId;
                if ($cleanId > 0) {
                    $cleanIds[] = $cleanId;
                }
            }
            $cleanIds = array_values(array_unique($cleanIds));
            if ($cleanIds === []) {
                setFlash('warning', 'Seleziona almeno una prenotazione valida.');
                redirect('prenotazioni.php');
            }

            $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
            $stmt = $pdo->prepare("UPDATE prenotazioni SET stato = ? WHERE id IN ($placeholders)");
            $stmt->execute(array_merge([$stato], $cleanIds));

            setFlash('success', 'Stato aggiornato per le prenotazioni selezionate.');
            redirect('prenotazioni.php');
        }
    } catch (Throwable $e) {
        error_log('[prenotazioni.php] ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        setFlash('danger', 'Errore durante l\'operazione richiesta.');
        redirect('prenotazioni.php');
    }
}

$bookings = [];
$clients = [];
$teachers = [];
$purchases = [];
$pageError = '';
$statusOptions = bookingStatuses();

try {
    $stmt = $pdo->prepare(
        'SELECT p.*, c.nome AS cliente_nome, c.cognome AS cliente_cognome, i.nome AS insegnante_nome, i.cognome AS insegnante_cognome
         FROM prenotazioni p
         INNER JOIN clienti c ON c.id = p.cliente_id
         INNER JOIN insegnanti i ON i.id = p.insegnante_id
         ORDER BY p.data DESC, p.ora_inizio DESC, p.id DESC'
    );
    $stmt->execute();
    $bookings = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT id, nome, cognome FROM clienti ORDER BY cognome ASC, nome ASC');
    $stmt->execute();
    $clients = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT id, nome, cognome FROM insegnanti ORDER BY cognome ASC, nome ASC');
    $stmt->execute();
    $teachers = $stmt->fetchAll();

    $stmt = $pdo->prepare(
        'SELECT a.id, a.cliente_id, a.data_acquisto, COALESCE(pk.nome, "Pacchetto") AS pacchetto_nome,
                COALESCE(a.numero_lezioni, pk.numero_lezioni, 0) AS numero_lezioni
         FROM acquisti a
         LEFT JOIN pacchetti pk ON pk.id = a.pacchetto_id
         ORDER BY a.data_acquisto DESC, a.id DESC'
    );
    $stmt->execute();
    $purchases = $stmt->fetchAll();
} catch (PDOException $e) {
    $pageError = 'Impossibile caricare le prenotazioni.';
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1">Prenotazioni</h2>
        <p class="text-secondary mb-0">Gestisci lezioni, stati e aggiornamenti multipli.</p>
    </div>
    <button type="button" class="btn btn-primary" id="newBookingBtn">
        <i class="fas fa-plus me-2"></i>Nuova Prenotazione
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
                <label for="filter-date-from" class="form-label">Data da</label>
                <input type="date" class="form-control" id="filter-date-from">
            </div>
            <div class="col-md-3">
                <label for="filter-date-to" class="form-label">Data a</label>
                <input type="date" class="form-control" id="filter-date-to">
            </div>
            <div class="col-md-2">
                <label for="filter-status" class="form-label">Stato</label>
                <select class="form-select" id="filter-status">
                    <option value="">Tutti</option>
                    <?php foreach ($statusOptions as $statusOption): ?>
                    <option value="<?= htmlspecialchars($statusOption) ?>"><?= htmlspecialchars($statusOption) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="filter-teacher" class="form-label">Insegnante</label>
                <select class="form-select" id="filter-teacher">
                    <option value="">Tutti</option>
                    <?php foreach ($teachers as $teacher): ?>
                    <option value="<?= htmlspecialchars((string)$teacher['id']) ?>"><?= htmlspecialchars(trim((string)$teacher['nome'] . ' ' . (string)$teacher['cognome'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="filter-client" class="form-label">Cliente</label>
                <select class="form-select" id="filter-client">
                    <option value="">Tutti</option>
                    <?php foreach ($clients as $client): ?>
                    <option value="<?= htmlspecialchars((string)$client['id']) ?>"><?= htmlspecialchars(trim((string)$client['nome'] . ' ' . (string)$client['cognome'])) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div><i class="fas fa-calendar-check"></i> Elenco prenotazioni</div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <form method="post" action="prenotazioni.php" id="bulkStatusForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="bulk_status">
                <input type="hidden" name="stato" id="bulkStatusValue" value="">
                <div id="bulkIdsContainer"></div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <select class="form-select form-select-sm" id="bulkStatusSelect" style="min-width: 180px;">
                        <option value="">Aggiorna stato selezionati</option>
                        <?php foreach ($statusOptions as $statusOption): ?>
                        <option value="<?= htmlspecialchars($statusOption) ?>"><?= htmlspecialchars($statusOption) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="applyBulkStatusBtn">Applica</button>
                </div>
            </form>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable align-middle" id="prenotazioniTable">
                <thead>
                    <tr>
                        <th style="width:40px;"><input type="checkbox" id="selectAllBookings"></th>
                        <th>#</th>
                        <th>Data</th>
                        <th>Ora Inizio-Fine</th>
                        <th>Cliente</th>
                        <th>Insegnante</th>
                        <th>Strumento</th>
                        <th>Stato</th>
                        <th>Pacchetto</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                    <tr
                        data-date="<?= htmlspecialchars((string)$booking['data']) ?>"
                        data-status="<?= htmlspecialchars((string)$booking['stato']) ?>"
                        data-teacher="<?= htmlspecialchars((string)$booking['insegnante_id']) ?>"
                        data-client="<?= htmlspecialchars((string)$booking['cliente_id']) ?>"
                    >
                        <td><input type="checkbox" class="booking-checkbox" value="<?= htmlspecialchars((string)$booking['id']) ?>"></td>
                        <td><?= htmlspecialchars((string)$booking['id']) ?></td>
                        <td><?= htmlspecialchars(formatDate((string)$booking['data'])) ?></td>
                        <td><?= htmlspecialchars(substr((string)$booking['ora_inizio'], 0, 5) . ' - ' . substr((string)$booking['ora_fine'], 0, 5)) ?></td>
                        <td><?= htmlspecialchars(trim((string)$booking['cliente_nome'] . ' ' . (string)$booking['cliente_cognome'])) ?></td>
                        <td><?= htmlspecialchars(trim((string)$booking['insegnante_nome'] . ' ' . (string)$booking['insegnante_cognome'])) ?></td>
                        <td><?= htmlspecialchars((string)($booking['strumento'] ?: '—')) ?></td>
                        <td><?= statusBadge((string)$booking['stato']) ?></td>
                        <td><?= htmlspecialchars((string)($booking['pacchetto_nome'] ?: '—')) ?></td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary btn-edit-booking"
                                        data-edit="<?= htmlspecialchars(json_encode($booking), ENT_QUOTES) ?>">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <form method="post" action="prenotazioni.php" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars((string)$booking['id']) ?>">
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                            onclick="confirmDelete(this.form, '<?= htmlspecialchars(trim((string)$booking['cliente_nome'] . ' ' . (string)$booking['cliente_cognome']), ENT_QUOTES) ?>')">
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

<div class="modal fade" id="bookingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="bookingForm" method="post" action="prenotazioni.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="bookingModalTitle">Nuova Prenotazione</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                </div>
                <div class="modal-body">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="booking_id" value="">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="booking_data" class="form-label">Data *</label>
                            <input type="date" class="form-control" id="booking_data" name="data" required>
                        </div>
                        <div class="col-md-4">
                            <label for="booking_ora_inizio" class="form-label">Ora inizio *</label>
                            <input type="time" class="form-control" id="booking_ora_inizio" name="ora_inizio" required>
                        </div>
                        <div class="col-md-4">
                            <label for="booking_ora_fine" class="form-label">Ora fine *</label>
                            <input type="time" class="form-control" id="booking_ora_fine" name="ora_fine" required>
                        </div>
                        <div class="col-md-6">
                            <label for="booking_cliente_id" class="form-label">Cliente *</label>
                            <select class="form-select" id="booking_cliente_id" name="cliente_id" required>
                                <option value="">Seleziona cliente</option>
                                <?php foreach ($clients as $client): ?>
                                <option value="<?= htmlspecialchars((string)$client['id']) ?>"><?= htmlspecialchars(trim((string)$client['nome'] . ' ' . (string)$client['cognome'])) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="booking_insegnante_id" class="form-label">Insegnante *</label>
                            <select class="form-select" id="booking_insegnante_id" name="insegnante_id" required>
                                <option value="">Seleziona insegnante</option>
                                <?php foreach ($teachers as $teacher): ?>
                                <option value="<?= htmlspecialchars((string)$teacher['id']) ?>"><?= htmlspecialchars(trim((string)$teacher['nome'] . ' ' . (string)$teacher['cognome'])) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="booking_strumento" class="form-label">Strumento</label>
                            <input type="text" class="form-control" id="booking_strumento" name="strumento">
                        </div>
                        <div class="col-md-6">
                            <label for="booking_stato" class="form-label">Stato *</label>
                            <select class="form-select" id="booking_stato" name="stato" required>
                                <?php foreach ($statusOptions as $statusOption): ?>
                                <option value="<?= htmlspecialchars($statusOption) ?>" <?= $statusOption === 'Programmata' ? 'selected' : '' ?>><?= htmlspecialchars($statusOption) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="booking_pacchetto_nome" class="form-label">Pacchetto</label>
                            <input type="text" class="form-control" id="booking_pacchetto_nome" name="pacchetto_nome">
                        </div>
                        <div class="col-md-6">
                            <label for="booking_acquisto_id" class="form-label">Acquisto collegato</label>
                            <select class="form-select" id="booking_acquisto_id" name="acquisto_id">
                                <option value="">Nessuno</option>
                                <?php foreach ($purchases as $purchase): ?>
                                <option value="<?= htmlspecialchars((string)$purchase['id']) ?>" data-cliente-id="<?= htmlspecialchars((string)$purchase['cliente_id']) ?>">
                                    #<?= htmlspecialchars((string)$purchase['id']) ?> · <?= htmlspecialchars((string)$purchase['pacchetto_nome']) ?> · <?= htmlspecialchars(formatDate((string)$purchase['data_acquisto'])) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Gli acquisti vengono filtrati in base al cliente selezionato.</div>
                        </div>
                        <div class="col-12">
                            <label for="booking_note" class="form-label">Note</label>
                            <textarea class="form-control" id="booking_note" name="note" rows="4"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Salva Prenotazione
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.text-secondary { color: var(--text-secondary) !important; }
#prenotazioniTable td, #prenotazioniTable th { vertical-align: middle; }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const bookingModalEl = document.getElementById('bookingModal');
    const bookingModal = new bootstrap.Modal(bookingModalEl);
    const bookingForm = document.getElementById('bookingForm');
    const clientSelect = document.getElementById('booking_cliente_id');
    const purchaseSelect = document.getElementById('booking_acquisto_id');
    const statusOptions = <?= json_encode($statusOptions, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    let table = null;

    function resetBookingForm() {
        bookingForm.reset();
        document.getElementById('booking_id').value = '';
        document.getElementById('bookingModalTitle').textContent = 'Nuova Prenotazione';
        document.getElementById('booking_stato').value = 'Programmata';
        filterPurchasesByClient('');
    }

    function filterPurchasesByClient(clientId, selectedPurchaseId = '') {
        const normalizedClient = String(clientId || '');
        const normalizedPurchase = String(selectedPurchaseId || '');
        Array.from(purchaseSelect.options).forEach((option, index) => {
            if (index === 0) {
                option.hidden = false;
                return;
            }
            const optionClient = option.dataset.clienteId || '';
            const visible = normalizedClient === '' || optionClient === normalizedClient || option.value === normalizedPurchase;
            option.hidden = !visible;
        });
        purchaseSelect.value = normalizedPurchase;
        if (purchaseSelect.value !== normalizedPurchase) {
            purchaseSelect.value = '';
        }
    }

    function initBookingTable() {
        if (typeof $ === 'undefined' || typeof $.fn.DataTable === 'undefined') {
            return;
        }
        const searchFn = function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'prenotazioniTable') {
                return true;
            }
            const row = settings.aoData[dataIndex] ? settings.aoData[dataIndex].nTr : null;
            if (!row) {
                return true;
            }

            const dateValue = row.dataset.date || '';
            const statusValue = row.dataset.status || '';
            const teacherValue = row.dataset.teacher || '';
            const clientValue = row.dataset.client || '';
            const fromValue = document.getElementById('filter-date-from').value || '';
            const toValue = document.getElementById('filter-date-to').value || '';
            const statusFilter = document.getElementById('filter-status').value || '';
            const teacherFilter = document.getElementById('filter-teacher').value || '';
            const clientFilter = document.getElementById('filter-client').value || '';

            if (fromValue !== '' && dateValue < fromValue) return false;
            if (toValue !== '' && dateValue > toValue) return false;
            if (statusFilter !== '' && statusValue !== statusFilter) return false;
            if (teacherFilter !== '' && teacherValue !== teacherFilter) return false;
            if (clientFilter !== '' && clientValue !== clientFilter) return false;
            return true;
        };

        $.fn.dataTable.ext.search.push(searchFn);
        table = $('#prenotazioniTable').DataTable({
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/it-IT.json' },
            pageLength: 25,
            responsive: true,
            order: [[2, 'desc'], [3, 'desc']],
            dom: '<"row align-items-center mb-3"<"col-sm-6"l><"col-sm-6 text-end"f>>rt<"row mt-3"<"col-sm-6"i><"col-sm-6"p>>',
            columnDefs: [
                { orderable: false, targets: [0, 9] }
            ]
        });

        ['filter-date-from', 'filter-date-to', 'filter-status', 'filter-teacher', 'filter-client'].forEach((id) => {
            document.getElementById(id)?.addEventListener('change', () => table.draw());
        });
    }

    document.getElementById('newBookingBtn')?.addEventListener('click', () => {
        resetBookingForm();
        bookingModal.show();
    });

    clientSelect.addEventListener('change', () => {
        filterPurchasesByClient(clientSelect.value, purchaseSelect.value);
    });

    document.querySelectorAll('.btn-edit-booking').forEach((button) => {
        button.addEventListener('click', () => {
            try {
                const booking = JSON.parse(button.dataset.edit);
                resetBookingForm();
                document.getElementById('bookingModalTitle').textContent = 'Modifica Prenotazione';
                document.getElementById('booking_id').value = booking.id || '';
                document.getElementById('booking_data').value = booking.data || '';
                document.getElementById('booking_ora_inizio').value = String(booking.ora_inizio || '').slice(0, 5);
                document.getElementById('booking_ora_fine').value = String(booking.ora_fine || '').slice(0, 5);
                document.getElementById('booking_cliente_id').value = booking.cliente_id || '';
                document.getElementById('booking_insegnante_id').value = booking.insegnante_id || '';
                document.getElementById('booking_strumento').value = booking.strumento || '';
                document.getElementById('booking_stato').value = statusOptions.includes(booking.stato) ? booking.stato : 'Programmata';
                document.getElementById('booking_pacchetto_nome').value = booking.pacchetto_nome || '';
                document.getElementById('booking_note').value = booking.note || '';
                filterPurchasesByClient(booking.cliente_id || '', booking.acquisto_id || '');
                bookingModal.show();
            } catch (e) {
                showToast('Errore nel caricamento della prenotazione.', 'danger');
            }
        });
    });

    document.getElementById('applyBulkStatusBtn')?.addEventListener('click', () => {
        const status = document.getElementById('bulkStatusSelect').value || '';
        const ids = Array.from(document.querySelectorAll('.booking-checkbox:checked')).map((checkbox) => checkbox.value);
        if (!status) {
            showToast('Seleziona uno stato da applicare.', 'warning');
            return;
        }
        if (ids.length === 0) {
            showToast('Seleziona almeno una prenotazione.', 'warning');
            return;
        }

        const container = document.getElementById('bulkIdsContainer');
        container.innerHTML = '';
        ids.forEach((id) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            container.appendChild(input);
        });
        document.getElementById('bulkStatusValue').value = status;
        document.getElementById('bulkStatusForm').submit();
    });

    document.getElementById('selectAllBookings')?.addEventListener('change', (event) => {
        document.querySelectorAll('.booking-checkbox').forEach((checkbox) => {
            checkbox.checked = !!event.target.checked;
        });
    });

    bookingModalEl.addEventListener('hidden.bs.modal', resetBookingForm);
    initBookingTable();
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

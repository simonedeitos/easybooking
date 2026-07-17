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

function calendarStatuses(): array
{
    return ['Programmata', 'Svolta', 'Assente', 'Rimandata', 'Riprogrammata'];
}

function calendarNullableString(mixed $value): ?string
{
    $value = trim((string)$value);
    return $value === '' ? null : $value;
}

function calendarValidDate(string $date): bool
{
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt !== false && $dt->format('Y-m-d') === $date;
}

function calendarValidTime(string $time): bool
{
    return preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time) === 1;
}

function calendarNormalizeTime(string $time): string
{
    $time = trim($time);
    return strlen($time) === 5 ? $time . ':00' : substr($time, 0, 8);
}

function calendarTimeRangeValid(string $start, string $end): bool
{
    return strtotime('1970-01-01 ' . $end) > strtotime('1970-01-01 ' . $start);
}

function calendarSendEvents(array $events): never
{
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode($events, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$requestAction = $_SERVER['REQUEST_METHOD'] === 'POST' ? post('action') : get('action');

if ($requestAction !== '') {
    try {
        // Legacy endpoint — no longer called by the frontend (which uses
        // api/get-eventi-calendario.php instead). Kept for backwards compat
        // in case any external integrations reference it.
        if ($requestAction === 'get_events') {
            $teacherId = sanitizeInt(get('insegnante_id'));
            $sql =
                'SELECT p.id, p.data, p.ora_inizio, p.ora_fine, p.stato, p.strumento, p.note, p.cliente_id, p.insegnante_id, p.pacchetto_nome, p.acquisto_id,
                        c.nome AS cliente_nome, c.cognome AS cliente_cognome, i.nome AS insegnante_nome, i.cognome AS insegnante_cognome
                 FROM prenotazioni p
                 INNER JOIN clienti c ON c.id = p.cliente_id
                 INNER JOIN insegnanti i ON i.id = p.insegnante_id';
            $params = [];
            if ($teacherId > 0) {
                $sql .= ' WHERE p.insegnante_id = ?';
                $params[] = $teacherId;
            }
            $sql .= ' ORDER BY p.data ASC, p.ora_inizio ASC, p.id ASC';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $events = [];
            foreach ($stmt->fetchAll() as $row) {
                $cliente = trim((string)$row['cliente_nome'] . ' ' . (string)$row['cliente_cognome']);
                $insegnante = trim((string)$row['insegnante_nome'] . ' ' . (string)$row['insegnante_cognome']);
                $strumento = trim((string)($row['strumento'] ?? ''));
                $title = $cliente;
                if ($strumento !== '') {
                    $title .= ' - ' . $strumento;
                }

                $events[] = [
                    'id' => (int)$row['id'],
                    'title' => $title,
                    'start' => (string)$row['data'] . 'T' . substr((string)$row['ora_inizio'], 0, 8),
                    'end' => (string)$row['data'] . 'T' . substr((string)$row['ora_fine'], 0, 8),
                    'extendedProps' => [
                        'stato' => (string)$row['stato'],
                        'cliente' => $cliente,
                        'insegnante' => $insegnante,
                        'insegnante_id' => (int)$row['insegnante_id'],
                        'strumento' => $strumento,
                        'note' => (string)($row['note'] ?? ''),
                        'cliente_id' => (int)$row['cliente_id'],
                        'pacchetto_nome' => (string)($row['pacchetto_nome'] ?? ''),
                        'acquisto_id' => $row['acquisto_id'] !== null ? (int)$row['acquisto_id'] : null,
                    ],
                ];
            }

            calendarSendEvents($events);
        }

        if ($requestAction === 'check_conflict') {
            $id = sanitizeInt(get('exclude_id'));
            $data = trim(get('data'));
            $oraInizio = calendarNormalizeTime(get('ora_inizio'));
            $oraFine = calendarNormalizeTime(get('ora_fine'));
            $insegnanteId = sanitizeInt(get('insegnante_id'));
            if ($insegnanteId <= 0 || !calendarValidDate($data) || !calendarValidTime($oraInizio) || !calendarValidTime($oraFine)) {
                jsonResponse(['conflict' => false]);
            }

            $stmt = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM prenotazioni
                 WHERE insegnante_id = ?
                   AND data = ?
                   AND id != ?
                   AND ora_inizio < ?
                   AND ora_fine > ?'
            );
            $stmt->execute([$insegnanteId, $data, $id, $oraFine, $oraInizio]);
            jsonResponse(['conflict' => (int)$stmt->fetchColumn() > 0]);
        }

        if ($requestAction === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            verifyCsrf();

            $id = sanitizeInt(post('id'));
            $data = trim(post('data'));
            $oraInizio = calendarNormalizeTime(post('ora_inizio'));
            $oraFine = calendarNormalizeTime(post('ora_fine'));
            $stato = trim(post('stato'));
            $clienteId = sanitizeInt(post('cliente_id'));
            $insegnanteId = sanitizeInt(post('insegnante_id'));
            $strumento = calendarNullableString(post('strumento'));
            $note = calendarNullableString(post('note'));
            $pacchettoNome = calendarNullableString(post('pacchetto_nome'));
            $acquistoId = sanitizeInt(post('acquisto_id'));
            $acquistoId = $acquistoId > 0 ? $acquistoId : null;
            $tipoEvento = in_array(post('tipo_evento'), ['lezione', 'provino'], true) ? post('tipo_evento') : 'lezione';
            $strumentoId = sanitizeInt(post('strumento_id'));
            $strumentoId = $strumentoId > 0 ? $strumentoId : null;

            if ($id > 0 && ($clienteId <= 0 || $insegnanteId <= 0)) {
                $stmt = $pdo->prepare('SELECT cliente_id, insegnante_id, strumento, strumento_id, pacchetto_nome, acquisto_id FROM prenotazioni WHERE id = ? LIMIT 1');
                $stmt->execute([$id]);
                $existing = $stmt->fetch();
                if (!$existing) {
                    jsonResponse(['success' => false, 'message' => 'Prenotazione non trovata.'], 404);
                }
                if ($clienteId <= 0) {
                    $clienteId = (int)$existing['cliente_id'];
                }
                if ($insegnanteId <= 0) {
                    $insegnanteId = (int)$existing['insegnante_id'];
                }
                if ($strumento === null) {
                    $strumento = $existing['strumento'] !== null ? (string)$existing['strumento'] : null;
                }
                if ($strumentoId === null && $existing['strumento_id'] !== null) {
                    $strumentoId = (int)$existing['strumento_id'];
                }
                if ($pacchettoNome === null) {
                    $pacchettoNome = $existing['pacchetto_nome'] !== null ? (string)$existing['pacchetto_nome'] : null;
                }
                if ($acquistoId === null && $existing['acquisto_id'] !== null) {
                    $acquistoId = (int)$existing['acquisto_id'];
                }
            }

            // For new provini: create the client on the fly if no existing client is selected
            if ($id === 0 && $tipoEvento === 'provino' && $clienteId <= 0) {
                $nomeNuovo = trim(post('cliente_nuovo_nome'));
                $cognomeNuovo = trim(post('cliente_nuovo_cognome'));
                $contattoNuovo = calendarNullableString(post('cliente_nuovo_contatto'));
                if ($nomeNuovo === '' || $cognomeNuovo === '') {
                    jsonResponse(['success' => false, 'message' => 'Nome e cognome del nuovo cliente sono obbligatori.'], 422);
                }
                $stmt = $pdo->prepare('INSERT INTO clienti (nome, cognome, telefono) VALUES (?, ?, ?)');
                $stmt->execute([$nomeNuovo, $cognomeNuovo, $contattoNuovo]);
                $clienteId = (int)$pdo->lastInsertId();
            }

            // Resolve strumento_id: validate existence and populate strumento text
            if ($strumentoId !== null) {
                $stmt = $pdo->prepare('SELECT nome FROM strumenti WHERE id = ? LIMIT 1');
                $stmt->execute([$strumentoId]);
                $strumentoRow = $stmt->fetch();
                if ($strumentoRow === false) {
                    jsonResponse(['success' => false, 'message' => 'Strumento non trovato.'], 404);
                }
                if ($strumento === null) {
                    $strumento = (string)$strumentoRow['nome'];
                }
            }

            if (!calendarValidDate($data) || !calendarValidTime($oraInizio) || !calendarValidTime($oraFine)) {
                jsonResponse(['success' => false, 'message' => 'Data o orario non validi.'], 422);
            }
            if ($clienteId <= 0 || $insegnanteId <= 0) {
                jsonResponse(['success' => false, 'message' => 'Cliente e insegnante sono obbligatori.'], 422);
            }
            if (!in_array($stato, calendarStatuses(), true)) {
                jsonResponse(['success' => false, 'message' => 'Stato non valido.'], 422);
            }
            if (!calendarTimeRangeValid($oraInizio, $oraFine)) {
                jsonResponse(['success' => false, 'message' => 'L\'ora di fine deve essere successiva all\'ora di inizio.'], 422);
            }

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM clienti WHERE id = ?');
            $stmt->execute([$clienteId]);
            if ((int)$stmt->fetchColumn() === 0) {
                jsonResponse(['success' => false, 'message' => 'Cliente non trovato.'], 404);
            }

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM insegnanti WHERE id = ?');
            $stmt->execute([$insegnanteId]);
            if ((int)$stmt->fetchColumn() === 0) {
                jsonResponse(['success' => false, 'message' => 'Insegnante non trovato.'], 404);
            }

            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE prenotazioni
                     SET data = ?, ora_inizio = ?, ora_fine = ?, cliente_id = ?, insegnante_id = ?, strumento = ?, strumento_id = ?, stato = ?, pacchetto_nome = ?, acquisto_id = ?, note = ?
                     WHERE id = ?'
                );
                $stmt->execute([$data, $oraInizio, $oraFine, $clienteId, $insegnanteId, $strumento, $strumentoId, $stato, $pacchettoNome, $acquistoId, $note, $id]);
                jsonResponse(['success' => true, 'message' => 'Evento aggiornato con successo.']);
            }

            $stmt = $pdo->prepare(
                'INSERT INTO prenotazioni (data, ora_inizio, ora_fine, cliente_id, insegnante_id, strumento, strumento_id, stato, pacchetto_nome, acquisto_id, note, tipo_evento)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$data, $oraInizio, $oraFine, $clienteId, $insegnanteId, $strumento, $strumentoId, $stato, $pacchettoNome, $acquistoId, $note, $tipoEvento]);
            jsonResponse(['success' => true, 'message' => 'Evento creato con successo.', 'id' => (int)$pdo->lastInsertId()]);
        }

        if ($requestAction === 'move_event' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            verifyCsrf();
            $id = sanitizeInt(post('id'));
            $data = trim(post('data'));
            $oraInizio = calendarNormalizeTime(post('ora_inizio'));
            $oraFine = calendarNormalizeTime(post('ora_fine'));
            $stato = trim(post('stato'));
            
            if ($id <= 0 || !calendarValidDate($data) || !calendarValidTime($oraInizio) || !calendarValidTime($oraFine) || !calendarTimeRangeValid($oraInizio, $oraFine)) {
                jsonResponse(['success' => false, 'message' => 'Dati di spostamento non validi.'], 422);
            }
            
            // Se è stato fornito uno stato, validalo
            if ($stato && !in_array($stato, calendarStatuses(), true)) {
                jsonResponse(['success' => false, 'message' => 'Stato non valido.'], 422);
            }

            // Se stato è fornito, aggiorna anche lo stato; altrimenti solo la data/ora
            if ($stato) {
                $stmt = $pdo->prepare('UPDATE prenotazioni SET data = ?, ora_inizio = ?, ora_fine = ?, stato = ? WHERE id = ?');
                $stmt->execute([$data, $oraInizio, $oraFine, $stato, $id]);
            } else {
                $stmt = $pdo->prepare('UPDATE prenotazioni SET data = ?, ora_inizio = ?, ora_fine = ? WHERE id = ?');
                $stmt->execute([$data, $oraInizio, $oraFine, $id]);
            }
            jsonResponse(['success' => true, 'message' => 'Evento spostato con successo.']);
        }

        if ($requestAction === 'resize_event' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            verifyCsrf();
            $id = sanitizeInt(post('id'));
            $oraFine = calendarNormalizeTime(post('ora_fine'));
            if ($id <= 0 || !calendarValidTime($oraFine)) {
                jsonResponse(['success' => false, 'message' => 'Ora finale non valida.'], 422);
            }

            $stmt = $pdo->prepare('SELECT ora_inizio FROM prenotazioni WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $startTime = $stmt->fetchColumn();
            if ($startTime === false) {
                jsonResponse(['success' => false, 'message' => 'Evento non trovato.'], 404);
            }
            $startTime = substr((string)$startTime, 0, 8);
            if (!calendarTimeRangeValid($startTime, $oraFine)) {
                jsonResponse(['success' => false, 'message' => 'L\'ora di fine deve essere successiva all\'ora di inizio.'], 422);
            }

            $stmt = $pdo->prepare('UPDATE prenotazioni SET ora_fine = ? WHERE id = ?');
            $stmt->execute([$oraFine, $id]);
            jsonResponse(['success' => true, 'message' => 'Durata aggiornata con successo.']);
        }

        if ($requestAction === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            verifyCsrf();
            $id = sanitizeInt(post('id'));
            if ($id <= 0) {
                jsonResponse(['success' => false, 'message' => 'Evento non valido.'], 422);
            }
            $stmt = $pdo->prepare('DELETE FROM prenotazioni WHERE id = ?');
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 0) {
                jsonResponse(['success' => false, 'message' => 'Evento non trovato.'], 404);
            }
            jsonResponse(['success' => true, 'message' => 'Evento eliminato con successo.']);
        }
    } catch (PDOException $e) {
        if ($requestAction === 'get_events') {
            calendarSendEvents([]);
        }
        jsonResponse(['success' => false, 'message' => 'Errore durante l\'operazione richiesta.'], 500);
    }
}

$clients = [];
$teachers = [];
$instruments = [];
$insegnantiStrumentiRelazioni = [];
$pageError = '';
$statusOptions = calendarStatuses();

try {
    $stmt = $pdo->prepare('SELECT id, nome, cognome FROM clienti ORDER BY cognome ASC, nome ASC');
    $stmt->execute();
    $clients = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT id, nome, cognome FROM insegnanti ORDER BY cognome ASC, nome ASC');
    $stmt->execute();
    $teachers = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT id, nome FROM strumenti ORDER BY nome ASC');
    $stmt->execute();
    $instruments = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT insegnante_id, strumento_id FROM insegnanti_strumenti');
    $stmt->execute();
    $insegnantiStrumentiRelazioni = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pageError = 'Impossibile caricare i dati del calendario.';
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1">Calendario</h2>
        <p class="text-secondary mb-0">Pianifica lezioni, spostamenti e modifiche rapide dal calendario.</p>
    </div>
</div>

<?php if ($pageError !== ''): ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($pageError) ?>
</div>
<?php endif; ?>

<div class="card mb-4" id="cal-toolbar-card">
    <div class="card-body d-flex flex-column gap-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="btn-group" role="group" aria-label="Navigazione calendario">
                <button type="button" class="btn btn-outline-light" id="cal-prev"><i class="fas fa-chevron-left"></i></button>
                <button type="button" class="btn btn-outline-light" id="cal-today">Oggi</button>
                <button type="button" class="btn btn-outline-light" id="cal-next"><i class="fas fa-chevron-right"></i></button>
            </div>
            <span class="fs-5 fw-semibold" id="cal-title">Calendario</span>
            <div class="d-flex align-items-center gap-2">
                <div class="btn-group" role="group" aria-label="Vista calendario">
                    <button type="button" class="btn btn-primary cal-view-btn active" id="cal-view-week">Settimana</button>
                    <button type="button" class="btn btn-outline-light cal-view-btn" id="cal-view-month">Mese</button>
                    <button type="button" class="btn btn-outline-light cal-view-btn" id="cal-view-day">Giorno</button>
                </div>
                <button type="button" class="btn btn-warning" id="nuovoProvinoBtn">
                    <i class="fas fa-star me-1"></i>NUOVO PROVINO
                </button>
            </div>
        </div>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="btn-group" role="group" aria-label="Colorazione eventi">
                <button type="button" class="btn btn-primary" id="color-by-status">Per Stato</button>
                <button type="button" class="btn btn-outline-light" id="color-by-teacher">Per Insegnante</button>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label for="calendarTeacherFilter" class="form-label mb-0">Filtra insegnante</label>
                <select class="form-select" id="calendarTeacherFilter" style="min-width: 240px;">
                    <option value="">Tutti gli insegnanti</option>
                    <?php foreach ($teachers as $teacher): ?>
                    <option value="<?= htmlspecialchars((string)$teacher['id']) ?>"><?= htmlspecialchars(trim((string)$teacher['nome'] . ' ' . (string)$teacher['cognome'])) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn btn-outline-light" id="applyTeacherFilterBtn" title="Applica filtro insegnante" aria-label="Applica filtro insegnante">
                    <i class="fas fa-filter me-1"></i>Applica
                </button>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div id="calendar"></div>
    </div>
</div>

<div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="eventForm" method="post" action="calendario.php">
                <div class="modal-header">
                    <h5 class="modal-title"><span id="event-tipo-text">Modifica Evento</span> <span id="event-tipo-badge" class="badge bg-primary ms-2">Lezione</span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                </div>
                <div class="modal-body">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="event-id" value="">
                    <input type="hidden" name="cliente_id" id="event-cliente-id" value="">
                    <input type="hidden" name="insegnante_id" id="event-insegnante-id" value="">
                    <input type="hidden" name="strumento" id="event-strumento-hidden" value="">
                    <input type="hidden" name="pacchetto_nome" id="event-pacchetto-hidden" value="">
                    <input type="hidden" name="acquisto_id" id="event-acquisto-hidden" value="">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="event-data" class="form-label">Data *</label>
                            <input type="date" class="form-control" id="event-data" name="data" required>
                        </div>
                        <div class="col-md-4">
                            <label for="event-ora-inizio" class="form-label">Ora inizio *</label>
                            <input type="time" class="form-control" id="event-ora-inizio" name="ora_inizio" required>
                        </div>
                        <div class="col-md-4">
                            <label for="event-ora-fine" class="form-label">Ora fine *</label>
                            <input type="time" class="form-control" id="event-ora-fine" name="ora_fine" required>
                        </div>
                        <div class="col-md-6">
                            <label for="event-stato" class="form-label">Stato *</label>
                            <select class="form-select" id="event-stato" name="stato" required>
                                <?php foreach ($statusOptions as $statusOption): ?>
                                <option value="<?= htmlspecialchars($statusOption) ?>"><?= htmlspecialchars($statusOption) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="event-cliente-label" class="form-label">Cliente</label>
                            <input type="text" class="form-control" id="event-cliente-label" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="event-insegnante-label" class="form-label">Insegnante</label>
                            <input type="text" class="form-control" id="event-insegnante-label" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="event-strumento-label" class="form-label">Strumento</label>
                            <input type="text" class="form-control" id="event-strumento-label" readonly>
                        </div>
                        <div class="col-12">
                            <label for="event-note" class="form-label">Note</label>
                            <textarea class="form-control" id="event-note" name="note" rows="4"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-outline-danger" id="deleteEventBtn">
                        <i class="fas fa-trash me-2"></i>Elimina
                    </button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Annulla</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Salva Modifiche
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="newEventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuovo Evento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>
            <div class="modal-body p-0">
                <ul class="nav nav-tabs px-3 pt-3" id="newEventTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-lezione-btn" data-bs-toggle="tab" data-bs-target="#tab-lezione" type="button" role="tab" aria-controls="tab-lezione" aria-selected="true">
                            <i class="fas fa-graduation-cap me-1"></i>NUOVA LEZIONE
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-provino-btn" data-bs-toggle="tab" data-bs-target="#tab-provino" type="button" role="tab" aria-controls="tab-provino" aria-selected="false">
                            <i class="fas fa-star me-1"></i>PROVINO
                        </button>
                    </li>
                </ul>
                <div class="tab-content p-3">
                    <!-- Tab: Nuova Lezione -->
                    <div class="tab-pane fade show active" id="tab-lezione" role="tabpanel" aria-labelledby="tab-lezione-btn">
                        <form id="newEventForm" method="post" action="calendario.php">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="save">
                            <input type="hidden" name="tipo_evento" value="lezione">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="new-event-data" class="form-label">Data *</label>
                                    <input type="date" class="form-control" id="new-event-data" name="data" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="new-event-ora-inizio" class="form-label">Ora inizio *</label>
                                    <input type="time" class="form-control" id="new-event-ora-inizio" name="ora_inizio" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="new-event-ora-fine" class="form-label">Ora fine *</label>
                                    <input type="time" class="form-control" id="new-event-ora-fine" name="ora_fine" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="new-event-cliente-id" class="form-label">Cliente *</label>
                                    <select class="form-select" id="new-event-cliente-id" name="cliente_id" required>
                                        <option value="">Cerca cliente...</option>
                                        <?php foreach ($clients as $client): ?>
                                        <option value="<?= htmlspecialchars((string)$client['id']) ?>"><?= htmlspecialchars(trim((string)$client['cognome'] . ' ' . (string)$client['nome']) . ' (' . (string)$client['id'] . ')') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="new-event-insegnante-id" class="form-label">Insegnante *</label>
                                    <select class="form-select" id="new-event-insegnante-id" name="insegnante_id" required>
                                        <option value="">Seleziona insegnante</option>
                                        <?php foreach ($teachers as $teacher): ?>
                                        <option value="<?= htmlspecialchars((string)$teacher['id']) ?>"><?= htmlspecialchars(trim((string)$teacher['nome'] . ' ' . (string)$teacher['cognome'])) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="new-event-strumento-id" class="form-label">Strumento</label>
                                    <select class="form-select" id="new-event-strumento-id" name="strumento_id">
                                        <option value="">Seleziona strumento</option>
                                        <?php foreach ($instruments as $instr): ?>
                                        <option value="<?= htmlspecialchars((string)$instr['id']) ?>"><?= htmlspecialchars((string)$instr['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="new-event-stato" class="form-label">Stato *</label>
                                    <select class="form-select" id="new-event-stato" name="stato" required>
                                        <?php foreach ($statusOptions as $statusOption): ?>
                                        <option value="<?= htmlspecialchars($statusOption) ?>" <?= $statusOption === 'Programmata' ? 'selected' : '' ?>><?= htmlspecialchars($statusOption) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="new-event-note" class="form-label">Note</label>
                                    <textarea class="form-control" id="new-event-note" name="note" rows="3"></textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                    <!-- Tab: Provino -->
                    <div class="tab-pane fade" id="tab-provino" role="tabpanel" aria-labelledby="tab-provino-btn">
                        <form id="newProvinoForm" method="post" action="calendario.php">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="save">
                            <input type="hidden" name="tipo_evento" value="provino">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="provino-data" class="form-label">Data *</label>
                                    <input type="date" class="form-control" id="provino-data" name="data" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="provino-ora-inizio" class="form-label">Ora inizio *</label>
                                    <input type="time" class="form-control" id="provino-ora-inizio" name="ora_inizio" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="provino-ora-fine" class="form-label">Ora fine *</label>
                                    <input type="time" class="form-control" id="provino-ora-fine" name="ora_fine" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="provino-strumento-id" class="form-label">Strumento *</label>
                                    <select class="form-select" id="provino-strumento-id" name="strumento_id" required>
                                        <option value="">Seleziona strumento</option>
                                        <?php foreach ($instruments as $instr): ?>
                                        <option value="<?= htmlspecialchars((string)$instr['id']) ?>"><?= htmlspecialchars((string)$instr['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="provino-insegnante-id" class="form-label">Insegnante *</label>
                                    <select class="form-select" id="provino-insegnante-id" name="insegnante_id" required>
                                        <option value="">Seleziona insegnante</option>
                                        <?php foreach ($teachers as $teacher): ?>
                                        <option value="<?= htmlspecialchars((string)$teacher['id']) ?>"><?= htmlspecialchars(trim((string)$teacher['nome'] . ' ' . (string)$teacher['cognome'])) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Cliente *</label>
                                    <div class="d-flex gap-3 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="provino_cliente_tipo" id="provino-cliente-existing" value="existing" checked>
                                            <label class="form-check-label" for="provino-cliente-existing">Cliente esistente</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="provino_cliente_tipo" id="provino-cliente-new" value="nuovo">
                                            <label class="form-check-label" for="provino-cliente-new">Nuovo cliente</label>
                                        </div>
                                    </div>
                                    <div id="provino-cliente-existing-section">
                                        <select class="form-select" id="provino-cliente-id" name="cliente_id">
                                            <option value="">Cerca cliente...</option>
                                            <?php foreach ($clients as $client): ?>
                                            <option value="<?= htmlspecialchars((string)$client['id']) ?>"><?= htmlspecialchars(trim((string)$client['cognome'] . ' ' . (string)$client['nome']) . ' (' . (string)$client['id'] . ')') ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div id="provino-cliente-new-section" style="display:none;">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <input type="text" class="form-control" id="provino-cliente-nuovo-nome" name="cliente_nuovo_nome" placeholder="Nome *">
                                            </div>
                                            <div class="col-md-6">
                                                <input type="text" class="form-control" id="provino-cliente-nuovo-cognome" name="cliente_nuovo_cognome" placeholder="Cognome *">
                                            </div>
                                            <div class="col-12">
                                                <input type="text" class="form-control" id="provino-cliente-nuovo-contatto" name="cliente_nuovo_contatto" placeholder="Contatto (facoltativo)">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label for="provino-note" class="form-label">Note</label>
                                    <textarea class="form-control" id="provino-note" name="note" rows="3"></textarea>
                                </div>
                                <input type="hidden" name="stato" value="Programmata">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-primary" id="newEventSubmitBtn">
                    <i class="fas fa-save me-2"></i>Salva
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.text-secondary { color: var(--text-secondary) !important; }
#calendar .fc-toolbar-title { font-size: 1rem; }
</style>

<script>
// Insegnanti-Strumenti relations embedded for bidirectional filtering
const IS_RELATIONS = <?= json_encode(array_map(static fn(array $r): array => [
    'i' => (int)$r['insegnante_id'],
    's' => (int)$r['strumento_id'],
], $insegnantiStrumentiRelazioni), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;

/**
 * Filter insegnanti select to show only those who teach the selected strumento.
 * Passing 0 resets all options to visible.
 */
function filterInsegnantiByStrumento(strumentoId, insegnantesSel) {
    if (!strumentoId || IS_RELATIONS.length === 0) {
        Array.from(insegnantesSel.options).forEach(o => { o.disabled = false; });
        return;
    }
    const allowed = IS_RELATIONS.filter(r => r.s === strumentoId).map(r => r.i);
    Array.from(insegnantesSel.options).forEach(o => {
        if (!o.value) return;
        o.disabled = !allowed.includes(parseInt(o.value, 10));
    });
    if (insegnantesSel.value && !allowed.includes(parseInt(insegnantesSel.value, 10))) {
        insegnantesSel.value = '';
    }
}

/**
 * Filter strumenti select to show only those taught by the selected insegnante.
 * Passing 0 resets all options to visible.
 */
function filterStrumentiByInsegnante(insegnanteId, strumentiSel) {
    if (!insegnanteId || IS_RELATIONS.length === 0) {
        Array.from(strumentiSel.options).forEach(o => { o.disabled = false; });
        return;
    }
    const allowed = IS_RELATIONS.filter(r => r.i === insegnanteId).map(r => r.s);
    Array.from(strumentiSel.options).forEach(o => {
        if (!o.value) return;
        o.disabled = !allowed.includes(parseInt(o.value, 10));
    });
    if (strumentiSel.value && !allowed.includes(parseInt(strumentiSel.value, 10))) {
        strumentiSel.value = '';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const eventModalEl    = document.getElementById('eventModal');
    const newEventModalEl = document.getElementById('newEventModal');
    const eventModal    = bootstrap.Modal.getOrCreateInstance(eventModalEl);
    const newEventModal = bootstrap.Modal.getOrCreateInstance(newEventModalEl);
    const eventForm     = document.getElementById('eventForm');
    const newEventForm  = document.getElementById('newEventForm');
    const newProvinoForm = document.getElementById('newProvinoForm');
    const teacherFilter = document.getElementById('calendarTeacherFilter');
    const lezClienteSel = document.getElementById('new-event-cliente-id');
    const provClienteSel = document.getElementById('provino-cliente-id');
    let currentColorMode = 'status';

    // ── Dropdown references for bidirectional filtering ───────
    const lezStrumentoSel  = document.getElementById('new-event-strumento-id');
    const lezInsegnanteSel = document.getElementById('new-event-insegnante-id');
    const provStrumentoSel  = document.getElementById('provino-strumento-id');
    const provInsegnanteSel = document.getElementById('provino-insegnante-id');

    function caseInsensitiveSelectMatcher(params, data) {
        const term = (params.term || '').trim().toLowerCase();
        if (term === '') {
            return data;
        }
        const text = ((data.text || '') + '').toLowerCase();
        return text.includes(term) ? data : null;
    }

    function initClientSelect(selectEl) {
        if (!selectEl || typeof window.$ === 'undefined' || typeof $.fn.select2 === 'undefined') {
            return;
        }
        $(selectEl).select2({
            placeholder: 'Cerca cliente...',
            allowClear: true,
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: $(newEventModalEl),
            matcher: caseInsensitiveSelectMatcher
        });
    }

    function resetEventForm() {
        eventForm.reset();
        document.getElementById('event-id').value              = '';
        document.getElementById('event-cliente-id').value      = '';
        document.getElementById('event-insegnante-id').value   = '';
        document.getElementById('event-strumento-hidden').value = '';
        document.getElementById('event-pacchetto-hidden').value = '';
        document.getElementById('event-acquisto-hidden').value  = '';
        document.getElementById('event-cliente-label').value   = '';
        document.getElementById('event-insegnante-label').value = '';
        document.getElementById('event-strumento-label').value  = '';
    }

    function resetNewEventForm() {
        newEventForm.reset();
        document.getElementById('new-event-stato').value = 'Programmata';
        if (typeof window.$ !== 'undefined' && lezClienteSel) {
            $(lezClienteSel).val(null).trigger('change');
        }
        filterInsegnantiByStrumento(0, lezInsegnanteSel);
        filterStrumentiByInsegnante(0, lezStrumentoSel);
    }

    function resetProvinoForm() {
        newProvinoForm.reset();
        if (typeof window.$ !== 'undefined' && provClienteSel) {
            $(provClienteSel).val(null).trigger('change');
        }
        document.getElementById('provino-cliente-existing-section').style.display = '';
        document.getElementById('provino-cliente-new-section').style.display      = 'none';
        document.getElementById('provino-cliente-existing').checked = true;
        filterInsegnantiByStrumento(0, provInsegnanteSel);
        filterStrumentiByInsegnante(0, provStrumentoSel);
    }

    function applyTeacherFilter() {
        console.debug(`[EasyBooking] Teacher filter applied → insegnante_id=${teacherFilter?.value || 'tutti'}`);
        renderCalendar();
    }

    function updateColorButtons() {
        document.getElementById('color-by-status')?.classList.toggle('btn-primary', currentColorMode === 'status');
        document.getElementById('color-by-status')?.classList.toggle('btn-outline-light', currentColorMode !== 'status');
        document.getElementById('color-by-teacher')?.classList.toggle('btn-primary', currentColorMode === 'teacher');
        document.getElementById('color-by-teacher')?.classList.toggle('btn-outline-light', currentColorMode !== 'teacher');
    }

    function renderCalendar() {
        const savedView = calendarInstance?.view?.type || 'timeGridWeek';
        if (calendarInstance) {
            calendarInstance.destroy();
            document.getElementById('calendar').innerHTML = '';
        }
        const teacherFilterValue = teacherFilter?.value || '';
        initCalendar({ slotMin: '08:00:00', slotMax: '21:00:00', colorMode: currentColorMode, initialView: savedView, teacherFilterValue });
        document.getElementById('cal-title').textContent = calendarInstance?.view?.title || 'Calendario';
        updateColorButtons();
    }

    async function submitWithConflictCheck(formEl, errorMsg) {
        const fd = new FormData(formEl);
        const id = fd.get('id') || '0';
        const clienteId   = fd.get('cliente_id') || '';
        const insegnanteId = fd.get('insegnante_id') || '';
        const data       = fd.get('data') || '';
        const oraInizio  = fd.get('ora_inizio') || '';
        const oraFine    = fd.get('ora_fine') || '';
        if (!clienteId || !insegnanteId) {
            throw new Error('Cliente e insegnante sono obbligatori.');
        }
        const ok = await checkConflict(id, data, oraInizio, oraFine, insegnanteId);
        if (!ok) {
            throw new Error('Conflitto orario: l\'insegnante ha già una lezione in questo intervallo.');
        }
        const response = await fetch('calendario.php', { method: 'POST', body: fd });
        const result   = await response.json();
        if (!result.success) {
            throw new Error(result.message || errorMsg);
        }
        return result;
    }

    async function submitProvinoForm() {
        const fd = new FormData(newProvinoForm);
        const insegnanteId = fd.get('insegnante_id') || '';
        const strumentoId  = fd.get('strumento_id') || '';
        const data         = fd.get('data') || '';
        const oraInizio    = fd.get('ora_inizio') || '';
        const oraFine      = fd.get('ora_fine') || '';
        const clienteTipo  = document.querySelector('input[name="provino_cliente_tipo"]:checked')?.value || 'existing';
        const clienteId    = fd.get('cliente_id') || '';
        const nomeNuovo    = (fd.get('cliente_nuovo_nome') || '').trim();
        const cognomeNuovo = (fd.get('cliente_nuovo_cognome') || '').trim();

        if (!insegnanteId) throw new Error('Insegnante è obbligatorio per il provino.');
        if (!strumentoId)  throw new Error('Strumento è obbligatorio per il provino.');
        if (!data || !oraInizio || !oraFine) throw new Error('Data e orario sono obbligatori per il provino.');
        if (clienteTipo === 'existing' && !clienteId)  throw new Error('Seleziona un cliente o scegli "Nuovo cliente".');
        if (clienteTipo === 'nuovo' && (!nomeNuovo || !cognomeNuovo)) throw new Error('Nome e cognome del nuovo cliente sono obbligatori.');

        const ok = await checkConflict('0', data, oraInizio, oraFine, insegnanteId);
        if (!ok) throw new Error('Conflitto orario: l\'insegnante ha già un appuntamento in questo intervallo.');

        const response = await fetch('calendario.php', { method: 'POST', body: fd });
        const result   = await response.json();
        if (!result.success) throw new Error(result.message || 'Errore durante il salvataggio del provino.');
        return result;
    }

    function getActiveTab() {
        return newEventModalEl.querySelector('.tab-pane.show.active')?.id || 'tab-lezione';
    }

    // ── Color mode ────────────────────────────────────────────
    document.getElementById('color-by-status')?.addEventListener('click', () => {
        currentColorMode = 'status';
        renderCalendar();
    });
    document.getElementById('color-by-teacher')?.addEventListener('click', () => {
        currentColorMode = 'teacher';
        renderCalendar();
    });

    // ── Teacher filter ────────────────────────────────────────
    teacherFilter.addEventListener('change', applyTeacherFilter);
    document.getElementById('applyTeacherFilterBtn')?.addEventListener('click', applyTeacherFilter);

    // ── NUOVO PROVINO button (toolbar) ────────────────────────
    document.getElementById('nuovoProvinoBtn')?.addEventListener('click', () => {
        resetNewEventForm();
        resetProvinoForm();
        new bootstrap.Tab(document.getElementById('tab-provino-btn')).show();
        newEventModal.show();
    });

    // ── Existing event form submit ────────────────────────────
    eventForm.addEventListener('submit', async (evt) => {
        evt.preventDefault();
        try {
            await submitWithConflictCheck(eventForm, 'Errore durante il salvataggio.');
            showToast('Lezione aggiornata con successo.', 'success');
            eventModal.hide();
            renderCalendar();
        } catch (error) {
            showToast(error.message, 'danger');
        }
    });

    // ── Unified submit button for new event modal ─────────────
    document.getElementById('newEventSubmitBtn')?.addEventListener('click', async () => {
        if (getActiveTab() === 'tab-lezione') {
            try {
                await submitWithConflictCheck(newEventForm, 'Errore durante il salvataggio.');
                showToast('Lezione creata con successo.', 'success');
                bootstrap.Modal.getOrCreateInstance(newEventModalEl).hide();
                renderCalendar();
            } catch (error) {
                showToast(error.message, 'danger');
            }
        } else {
            try {
                await submitProvinoForm();
                showToast('Provino creato con successo.', 'success');
                bootstrap.Modal.getOrCreateInstance(newEventModalEl).hide();
                renderCalendar();
            } catch (error) {
                showToast(error.message, 'danger');
            }
        }
    });

    // ── Delete event ──────────────────────────────────────────
    document.getElementById('deleteEventBtn')?.addEventListener('click', async () => {
        const id = document.getElementById('event-id').value || '';
        if (!id || !confirm('Eliminare questo evento?')) return;
        try {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            formData.append('csrf_token', getCsrfToken());
            const response = await fetch('calendario.php', { method: 'POST', body: formData });
            const result   = await response.json();
            if (!result.success) throw new Error(result.message || 'Errore durante l\'eliminazione.');
            showToast(result.message, 'success');
            eventModal.hide();
            renderCalendar();
        } catch (error) {
            showToast(error.message, 'danger');
        }
    });

    // ── Populate eventModal on show ───────────────────────────
    eventModalEl.addEventListener('shown.bs.modal', () => {
        const eventId = document.getElementById('event-id').value || '';
        const fcEvent = eventId && calendarInstance ? calendarInstance.getEventById(eventId) : null;
        if (!fcEvent) return;
        const props = fcEvent.extendedProps || {};
        document.getElementById('event-cliente-id').value       = props.cliente_id || '';
        document.getElementById('event-insegnante-id').value    = props.insegnante_id || '';
        document.getElementById('event-strumento-hidden').value = props.strumento || '';
        document.getElementById('event-pacchetto-hidden').value = props.pacchetto_nome || '';
        document.getElementById('event-acquisto-hidden').value  = props.acquisto_id || '';
        document.getElementById('event-cliente-label').value    = props.cliente || '';
        document.getElementById('event-insegnante-label').value = props.insegnante || '';
        document.getElementById('event-strumento-label').value  = props.strumento || '';
        const tipo = props.tipo_evento || 'lezione';
        const tipoBadge = document.getElementById('event-tipo-badge');
        if (tipoBadge) {
            tipoBadge.textContent = tipo === 'provino' ? 'Provino' : 'Lezione';
            tipoBadge.className   = tipo === 'provino'
                ? 'badge bg-warning text-dark ms-2'
                : 'badge bg-primary ms-2';
        }
        const tipoText = document.getElementById('event-tipo-text');
        if (tipoText) {
            tipoText.textContent = tipo === 'provino' ? 'Modifica Provino' : 'Modifica Lezione';
        }
    });

    eventModalEl.addEventListener('hidden.bs.modal', resetEventForm);
    newEventModalEl.addEventListener('hidden.bs.modal', () => {
        resetNewEventForm();
        resetProvinoForm();
        new bootstrap.Tab(document.getElementById('tab-lezione-btn')).show();
    });

    // ── Bidirectional filters: NUOVA LEZIONE tab ─────────────
    lezStrumentoSel?.addEventListener('change', function () {
        filterInsegnantiByStrumento(parseInt(this.value, 10) || 0, lezInsegnanteSel);
    });
    lezInsegnanteSel?.addEventListener('change', function () {
        filterStrumentiByInsegnante(parseInt(this.value, 10) || 0, lezStrumentoSel);
    });

    // ── Bidirectional filters: PROVINO tab ────────────────────
    provStrumentoSel?.addEventListener('change', function () {
        filterInsegnantiByStrumento(parseInt(this.value, 10) || 0, provInsegnanteSel);
    });
    provInsegnanteSel?.addEventListener('change', function () {
        filterStrumentiByInsegnante(parseInt(this.value, 10) || 0, provStrumentoSel);
    });

    // ── Provino: cliente tipo toggle ──────────────────────────
    document.querySelectorAll('input[name="provino_cliente_tipo"]').forEach(radio => {
        radio.addEventListener('change', function () {
            const isNew = this.value === 'nuovo';
            document.getElementById('provino-cliente-existing-section').style.display = isNew ? 'none' : '';
            document.getElementById('provino-cliente-new-section').style.display      = isNew ? '' : 'none';
        });
    });

    // ── Init ──────────────────────────────────────────────────
    initClientSelect(lezClienteSel);
    initClientSelect(provClienteSel);
    resetEventForm();
    resetNewEventForm();
    resetProvinoForm();
    renderCalendar();
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

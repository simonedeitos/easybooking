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

function instrumentDayMap(): array
{
    return [
        'lun' => 'LUN',
        'mar' => 'MAR',
        'mer' => 'MER',
        'gio' => 'GIO',
        'ven' => 'VEN',
        'sab' => 'SAB',
        'dom' => 'DOM',
    ];
}

function instrumentFlag(string $key): int
{
    return isset($_POST[$key]) ? 1 : 0;
}

function instrumentNormalizeTime(string $value): ?string
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }
    if (preg_match('/^\d{2}:\d{2}$/', $value) === 1) {
        return $value . ':00';
    }
    if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value) === 1) {
        return substr($value, 0, 8);
    }
    return null;
}

function instrumentTimeLabel(?string $value): string
{
    return $value ? substr($value, 0, 5) : '—';
}

function instrumentScheduleLabel(?string $start, ?string $end): string
{
    if (!$start || !$end) {
        return '—';
    }
    return instrumentTimeLabel($start) . ' - ' . instrumentTimeLabel($end);
}

function instrumentDayBadges(array $instrument): string
{
    $html = '';
    foreach (instrumentDayMap() as $key => $label) {
        $active = !empty($instrument[$key . '_attivo']);
        $class = $active ? 'bg-success' : 'bg-secondary';
        $html .= '<span class="badge ' . $class . ' me-1 mb-1">' . h($label) . '</span>';
    }
    return $html;
}

$requestAction = $_SERVER['REQUEST_METHOD'] === 'POST' ? post('action') : get('action');

if ($requestAction !== '') {
    try {
        if ($requestAction === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            verifyCsrf();

            $id = sanitizeInt(post('id'));
            $nome = trim(post('nome'));
            if ($nome === '') {
                jsonResponse(['success' => false, 'message' => 'Il nome dello strumento è obbligatorio.'], 422);
            }

            $dayValues = [];
            foreach (array_keys(instrumentDayMap()) as $day) {
                $dayValues[$day . '_attivo'] = instrumentFlag($day . '_attivo');
            }

            $mattInizio = instrumentNormalizeTime(post('matt_inizio'));
            $mattFine = instrumentNormalizeTime(post('matt_fine'));
            $pomInizio = instrumentNormalizeTime(post('pom_inizio'));
            $pomFine = instrumentNormalizeTime(post('pom_fine'));

            if ((post('matt_inizio') !== '' || post('matt_fine') !== '') && (!$mattInizio || !$mattFine)) {
                jsonResponse(['success' => false, 'message' => 'Compila correttamente l\'orario della mattina.'], 422);
            }
            if ((post('pom_inizio') !== '' || post('pom_fine') !== '') && (!$pomInizio || !$pomFine)) {
                jsonResponse(['success' => false, 'message' => 'Compila correttamente l\'orario del pomeriggio.'], 422);
            }
            if ($mattInizio && $mattFine && strtotime('1970-01-01 ' . $mattFine) <= strtotime('1970-01-01 ' . $mattInizio)) {
                jsonResponse(['success' => false, 'message' => 'L\'orario di fine mattina deve essere successivo all\'inizio.'], 422);
            }
            if ($pomInizio && $pomFine && strtotime('1970-01-01 ' . $pomFine) <= strtotime('1970-01-01 ' . $pomInizio)) {
                jsonResponse(['success' => false, 'message' => 'L\'orario di fine pomeriggio deve essere successivo all\'inizio.'], 422);
            }

            $checkSql = 'SELECT id FROM strumenti WHERE nome = ?' . ($id > 0 ? ' AND id <> ?' : '') . ' LIMIT 1';
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute($id > 0 ? [$nome, $id] : [$nome]);
            if ($checkStmt->fetch()) {
                jsonResponse(['success' => false, 'message' => 'Esiste già uno strumento con questo nome.'], 409);
            }

            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE strumenti
                     SET nome = ?, lun_attivo = ?, mar_attivo = ?, mer_attivo = ?, gio_attivo = ?, ven_attivo = ?, sab_attivo = ?, dom_attivo = ?,
                         matt_inizio = ?, matt_fine = ?, pom_inizio = ?, pom_fine = ?
                     WHERE id = ?'
                );
                $stmt->execute([
                    $nome,
                    $dayValues['lun_attivo'],
                    $dayValues['mar_attivo'],
                    $dayValues['mer_attivo'],
                    $dayValues['gio_attivo'],
                    $dayValues['ven_attivo'],
                    $dayValues['sab_attivo'],
                    $dayValues['dom_attivo'],
                    $mattInizio,
                    $mattFine,
                    $pomInizio,
                    $pomFine,
                    $id,
                ]);
                jsonResponse(['success' => true, 'message' => 'Strumento aggiornato con successo.']);
            }

            $stmt = $pdo->prepare(
                'INSERT INTO strumenti
                    (nome, lun_attivo, mar_attivo, mer_attivo, gio_attivo, ven_attivo, sab_attivo, dom_attivo, matt_inizio, matt_fine, pom_inizio, pom_fine)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $nome,
                $dayValues['lun_attivo'],
                $dayValues['mar_attivo'],
                $dayValues['mer_attivo'],
                $dayValues['gio_attivo'],
                $dayValues['ven_attivo'],
                $dayValues['sab_attivo'],
                $dayValues['dom_attivo'],
                $mattInizio,
                $mattFine,
                $pomInizio,
                $pomFine,
            ]);

            jsonResponse(['success' => true, 'message' => 'Strumento creato con successo.', 'id' => (int)$pdo->lastInsertId()]);
        } elseif ($requestAction === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            verifyCsrf();
            $id = sanitizeInt(post('id'));
            if ($id <= 0) {
                jsonResponse(['success' => false, 'message' => 'Strumento non valido.'], 422);
            }

            $stmt = $pdo->prepare('SELECT id, nome FROM strumenti WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $instrument = $stmt->fetch();
            if (!$instrument) {
                jsonResponse(['success' => false, 'message' => 'Strumento non trovato.'], 404);
            }

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM insegnanti_strumenti WHERE strumento_id = ?');
            $stmt->execute([$id]);
            $teacherLinks = (int)$stmt->fetchColumn();

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM prenotazioni WHERE strumento = ?');
            $stmt->execute([(string)$instrument['nome']]);
            $bookings = (int)$stmt->fetchColumn();

            if ($teacherLinks > 0 || $bookings > 0) {
                jsonResponse([
                    'success' => false,
                    'message' => 'Impossibile eliminare lo strumento: esistono associazioni con insegnanti o prenotazioni.'
                ], 409);
            }

            $stmt = $pdo->prepare('DELETE FROM strumenti WHERE id = ?');
            $stmt->execute([$id]);
            jsonResponse(['success' => true, 'message' => 'Strumento eliminato con successo.']);
        } elseif ($requestAction === 'get') {
            $id = sanitizeInt($_GET['id'] ?? $_POST['id'] ?? 0);
            if ($id <= 0) {
                jsonResponse(['success' => false, 'message' => 'Strumento non valido.'], 422);
            }

            $stmt = $pdo->prepare('SELECT * FROM strumenti WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $instrument = $stmt->fetch();
            if (!$instrument) {
                jsonResponse(['success' => false, 'message' => 'Strumento non trovato.'], 404);
            }

            jsonResponse(['success' => true, 'strumento' => $instrument]);
        } else {
            jsonResponse(['success' => false, 'message' => 'Azione non riconosciuta.'], 400);
        }
    } catch (Throwable $e) {
        error_log('[strumenti.php] ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        respondOperationResult(false, 'Errore durante la gestione degli strumenti.', 'strumenti.php', 500);
    }
}

$instruments = [];
$pageError = '';

try {
    $stmt = $pdo->prepare(
        'SELECT s.*, COUNT(DISTINCT ins.insegnante_id) AS totale_insegnanti
         FROM strumenti s
         LEFT JOIN insegnanti_strumenti ins ON ins.strumento_id = s.id
         GROUP BY s.id
         ORDER BY s.nome ASC'
    );
    $stmt->execute();
    $instruments = $stmt->fetchAll();
} catch (PDOException $e) {
    $pageError = 'Impossibile caricare l\'elenco strumenti.';
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1">Strumenti</h2>
        <p class="text-secondary mb-0">Gestisci giorni attivi e fasce orarie per ogni strumento musicale.</p>
    </div>
    <button type="button" class="btn btn-primary" id="newInstrumentBtn">
        <i class="fas fa-plus me-2"></i>Nuovo Strumento
    </button>
</div>

<?php if ($pageError !== ''): ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i><?= h($pageError) ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="fas fa-guitar me-2"></i>Elenco strumenti</span>
        <span class="text-secondary small"><?= h((string)count($instruments)) ?> record</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable align-middle" id="strumentiTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nome</th>
                        <th>Giorni Attivi</th>
                        <th>Orario Mattina</th>
                        <th>Orario Pomeriggio</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($instruments as $instrument): ?>
                    <tr>
                        <td><?= h((string)$instrument['id']) ?></td>
                        <td>
                            <div class="fw-semibold"><?= h((string)$instrument['nome']) ?></div>
                            <div class="small text-secondary">Insegnanti collegati: <?= h((string)$instrument['totale_insegnanti']) ?></div>
                        </td>
                        <td><?= instrumentDayBadges($instrument) ?></td>
                        <td><?= h(instrumentScheduleLabel($instrument['matt_inizio'] ?? null, $instrument['matt_fine'] ?? null)) ?></td>
                        <td><?= h(instrumentScheduleLabel($instrument['pom_inizio'] ?? null, $instrument['pom_fine'] ?? null)) ?></td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary btn-edit-instrument" data-id="<?= h((string)$instrument['id']) ?>">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-instrument" data-id="<?= h((string)$instrument['id']) ?>" data-name="<?= h((string)$instrument['nome']) ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="strumentoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="strumentoForm" method="post" action="strumenti.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="strumentoModalTitle">Nuovo Strumento</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                </div>
                <div class="modal-body">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="strumento_id" value="">

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="nome" class="form-label">Nome *</label>
                            <input type="text" class="form-control" id="nome" name="nome" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label d-block">Giorni attivi</label>
                            <div class="d-flex flex-wrap gap-3">
                                <?php foreach (instrumentDayMap() as $dayKey => $dayLabel): ?>
                                <div class="form-check form-check-inline me-0">
                                    <input class="form-check-input" type="checkbox" id="<?= h($dayKey) ?>_attivo" name="<?= h($dayKey) ?>_attivo" value="1">
                                    <label class="form-check-label" for="<?= h($dayKey) ?>_attivo"><?= h($dayLabel) ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Orario mattina</label>
                            <div class="input-group">
                                <input type="time" class="form-control" id="matt_inizio" name="matt_inizio">
                                <span class="input-group-text">–</span>
                                <input type="time" class="form-control" id="matt_fine" name="matt_fine">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Orario pomeriggio</label>
                            <div class="input-group">
                                <input type="time" class="form-control" id="pom_inizio" name="pom_inizio">
                                <span class="input-group-text">–</span>
                                <input type="time" class="form-control" id="pom_fine" name="pom_fine">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Salva
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('strumentoModal');
    const modal = new bootstrap.Modal(modalEl);
    const form = document.getElementById('strumentoForm');
    const titleEl = document.getElementById('strumentoModalTitle');
    const dayKeys = <?= json_encode(array_keys(instrumentDayMap()), JSON_UNESCAPED_UNICODE) ?>;

    function resetForm() {
        form.reset();
        document.getElementById('strumento_id').value = '';
        titleEl.textContent = 'Nuovo Strumento';
        dayKeys.forEach((day) => {
            const input = document.getElementById(day + '_attivo');
            if (input) {
                input.checked = ['lun', 'mar', 'mer', 'gio', 'ven'].includes(day);
            }
        });
    }

    document.getElementById('newInstrumentBtn')?.addEventListener('click', () => {
        resetForm();
        modal.show();
    });

    ajaxForm(form, () => {
        showToast('Strumento salvato con successo.', 'success');
        modal.hide();
        window.location.reload();
    }, (message) => {
        showToast(message, 'danger');
    });

    document.querySelectorAll('.btn-edit-instrument').forEach((button) => {
        button.addEventListener('click', async () => {
            const id = button.dataset.id;
            try {
                const response = await fetch('strumenti.php?action=get&id=' + encodeURIComponent(id));
                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Errore nel caricamento dello strumento.');
                }
                resetForm();
                const item = data.strumento;
                document.getElementById('strumento_id').value = item.id || '';
                document.getElementById('nome').value = item.nome || '';
                document.getElementById('matt_inizio').value = (item.matt_inizio || '').substring(0, 5);
                document.getElementById('matt_fine').value = (item.matt_fine || '').substring(0, 5);
                document.getElementById('pom_inizio').value = (item.pom_inizio || '').substring(0, 5);
                document.getElementById('pom_fine').value = (item.pom_fine || '').substring(0, 5);
                dayKeys.forEach((day) => {
                    const input = document.getElementById(day + '_attivo');
                    if (input) {
                        input.checked = String(item[day + '_attivo']) === '1';
                    }
                });
                titleEl.textContent = 'Modifica Strumento';
                modal.show();
            } catch (error) {
                showToast(error.message || 'Errore nel caricamento dello strumento.', 'danger');
            }
        });
    });

    document.querySelectorAll('.btn-delete-instrument').forEach((button) => {
        button.addEventListener('click', async () => {
            const name = button.dataset.name || 'questo strumento';
            if (!confirm('Eliminare "' + name + '"?')) {
                return;
            }
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', button.dataset.id || '');
            formData.append('csrf_token', getCsrfToken());
            try {
                const response = await fetch('strumenti.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Errore durante l\'eliminazione.');
                }
                showToast(data.message || 'Strumento eliminato.', 'success');
                window.location.reload();
            } catch (error) {
                showToast(error.message || 'Errore durante l\'eliminazione.', 'danger');
            }
        });
    });

    resetForm();
});
</script>
<?php require_once __DIR__ . '/includes/footer.php';

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

function packageNullableString(mixed $value): ?string
{
    $value = trim((string)$value);
    return $value === '' ? null : $value;
}

function packagePreview(?string $value, int $length = 70): string
{
    $value = trim((string)$value);
    if ($value === '') {
        return '—';
    }
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($value, 0, $length, '…', 'UTF-8');
    }
    return strlen($value) > $length ? substr($value, 0, $length - 3) . '...' : $value;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestAction = post('action');
    try {
        if ($requestAction === 'save') {
            verifyCsrfOrRedirect('pacchetti.php');

            $id = sanitizeInt(post('id'));
            $nome = trim(post('nome'));
            $descrizione = packageNullableString(post('descrizione'));
            $numeroLezioni = max(0, sanitizeInt(post('numero_lezioni')));
            $durataMinuti = max(1, sanitizeInt(post('durata_minuti')));
            $frequenza = packageNullableString(post('frequenza'));
            $prezzo = sanitizeFloat(str_replace(',', '.', post('prezzo')));
            $strumento = packageNullableString(post('strumento'));

            if ($nome === '') {
                setFlash('warning', 'Il nome del pacchetto è obbligatorio.');
                redirect('pacchetti.php');
            }
            if ($numeroLezioni <= 0) {
                setFlash('warning', 'Il numero di lezioni deve essere maggiore di zero.');
                redirect('pacchetti.php');
            }
            if ($prezzo < 0) {
                setFlash('warning', 'Il prezzo non può essere negativo.');
                redirect('pacchetti.php');
            }

            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE pacchetti
                     SET nome = ?, descrizione = ?, numero_lezioni = ?, durata_minuti = ?, frequenza = ?, prezzo = ?, strumento = ?
                     WHERE id = ?'
                );
                $stmt->execute([$nome, $descrizione, $numeroLezioni, $durataMinuti, $frequenza, $prezzo, $strumento, $id]);
                setFlash('success', 'Pacchetto aggiornato con successo.');
                redirect('pacchetti.php');
            }

            $stmt = $pdo->prepare(
                'INSERT INTO pacchetti (nome, descrizione, numero_lezioni, durata_minuti, frequenza, prezzo, strumento)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$nome, $descrizione, $numeroLezioni, $durataMinuti, $frequenza, $prezzo, $strumento]);
            setFlash('success', 'Pacchetto creato con successo.');
            redirect('pacchetti.php');
        } elseif ($requestAction === 'delete') {
            verifyCsrfOrRedirect('pacchetti.php');
            $id = sanitizeInt(post('id'));
            if ($id <= 0) {
                setFlash('warning', 'Pacchetto non valido.');
                redirect('pacchetti.php');
            }

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM acquisti WHERE pacchetto_id = ?');
            $stmt->execute([$id]);
            if ((int)$stmt->fetchColumn() > 0) {
                setFlash('warning', 'Impossibile eliminare il pacchetto: esistono acquisti collegati.');
                redirect('pacchetti.php');
            }

            $stmt = $pdo->prepare('DELETE FROM pacchetti WHERE id = ?');
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 0) {
                setFlash('warning', 'Pacchetto non trovato.');
                redirect('pacchetti.php');
            }

            setFlash('success', 'Pacchetto eliminato con successo.');
            redirect('pacchetti.php');
        }
    } catch (Throwable $e) {
        error_log('[pacchetti.php] ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        setFlash('danger', 'Errore durante l\'operazione richiesta.');
        redirect('pacchetti.php');
    }
}

$packages = [];
$instruments = [];
$pageError = '';

try {
    $stmt = $pdo->prepare('SELECT * FROM pacchetti ORDER BY nome ASC, id DESC');
    $stmt->execute();
    $packages = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT id, nome FROM strumenti ORDER BY nome ASC');
    $stmt->execute();
    $instruments = $stmt->fetchAll();
} catch (PDOException $e) {
    $pageError = 'Impossibile caricare i pacchetti.';
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1">Pacchetti</h2>
        <p class="text-secondary mb-0">Configura i pacchetti di lezioni disponibili per la vendita.</p>
    </div>
    <button type="button" class="btn btn-primary" id="newPackageBtn">
        <i class="fas fa-plus me-2"></i>Nuovo Pacchetto
    </button>
</div>

<?php if ($pageError !== ''): ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($pageError) ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <i class="fas fa-box-open"></i>
        Elenco pacchetti
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable align-middle" id="pacchettiTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nome</th>
                        <th>Descrizione</th>
                        <th>N° Lezioni</th>
                        <th>Durata (min)</th>
                        <th>Frequenza</th>
                        <th>Prezzo (€)</th>
                        <th>Strumento</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($packages as $package): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$package['id']) ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars((string)$package['nome']) ?></td>
                        <td><?= htmlspecialchars(packagePreview($package['descrizione'] ?? null)) ?></td>
                        <td><?= htmlspecialchars((string)$package['numero_lezioni']) ?></td>
                        <td><?= htmlspecialchars((string)$package['durata_minuti']) ?></td>
                        <td><?= htmlspecialchars((string)($package['frequenza'] ?: '—')) ?></td>
                        <td>€ <?= htmlspecialchars(number_format((float)$package['prezzo'], 2, ',', '.')) ?></td>
                        <td><?= htmlspecialchars((string)($package['strumento'] ?: '—')) ?></td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary btn-edit-package"
                                        data-edit="<?= htmlspecialchars(json_encode($package), ENT_QUOTES) ?>">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <form method="post" action="pacchetti.php" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars((string)$package['id']) ?>">
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                            onclick="confirmDelete(this.form, '<?= htmlspecialchars(addslashes((string)$package['nome']), ENT_QUOTES) ?>')">
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

<div class="modal fade" id="packageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="packageForm" method="post" action="pacchetti.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="packageModalTitle">Nuovo Pacchetto</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                </div>
                <div class="modal-body">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="package_id" value="">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="package_nome" class="form-label">Nome *</label>
                            <input type="text" class="form-control" id="package_nome" name="nome" required>
                        </div>
                        <div class="col-md-6">
                            <label for="package_strumento" class="form-label">Strumento</label>
                            <input list="strumenti-list" class="form-control" id="package_strumento" name="strumento">
                            <datalist id="strumenti-list">
                                <?php foreach ($instruments as $instrument): ?>
                                <option value="<?= htmlspecialchars((string)$instrument['nome']) ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="col-12">
                            <label for="package_descrizione" class="form-label">Descrizione</label>
                            <textarea class="form-control" id="package_descrizione" name="descrizione" rows="4"></textarea>
                        </div>
                        <div class="col-md-3">
                            <label for="package_numero_lezioni" class="form-label">N° Lezioni *</label>
                            <input type="number" class="form-control" id="package_numero_lezioni" name="numero_lezioni" min="1" required>
                        </div>
                        <div class="col-md-3">
                            <label for="package_durata_minuti" class="form-label">Durata (min) *</label>
                            <input type="number" class="form-control" id="package_durata_minuti" name="durata_minuti" min="1" value="60" required>
                        </div>
                        <div class="col-md-3">
                            <label for="package_frequenza" class="form-label">Frequenza</label>
                            <input type="text" class="form-control" id="package_frequenza" name="frequenza" placeholder="1 volta/settimana">
                        </div>
                        <div class="col-md-3">
                            <label for="package_prezzo" class="form-label">Prezzo (€) *</label>
                            <input type="number" class="form-control" id="package_prezzo" name="prezzo" min="0" step="0.01" value="0.00" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Salva Pacchetto
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
    const packageModalEl = document.getElementById('packageModal');
    const packageModal = new bootstrap.Modal(packageModalEl);
    const packageForm = document.getElementById('packageForm');

    function resetPackageForm() {
        packageForm.reset();
        document.getElementById('package_id').value = '';
        document.getElementById('packageModalTitle').textContent = 'Nuovo Pacchetto';
        document.getElementById('package_durata_minuti').value = '60';
        document.getElementById('package_prezzo').value = '0.00';
    }

    document.getElementById('newPackageBtn')?.addEventListener('click', () => {
        resetPackageForm();
        packageModal.show();
    });

    document.querySelectorAll('.btn-edit-package').forEach((button) => {
        button.addEventListener('click', () => {
            try {
                const pkg = JSON.parse(button.dataset.edit);
                resetPackageForm();
                document.getElementById('packageModalTitle').textContent = 'Modifica Pacchetto';
                document.getElementById('package_id').value = pkg.id || '';
                document.getElementById('package_nome').value = pkg.nome || '';
                document.getElementById('package_descrizione').value = pkg.descrizione || '';
                document.getElementById('package_numero_lezioni').value = pkg.numero_lezioni || '';
                document.getElementById('package_durata_minuti').value = pkg.durata_minuti || '60';
                document.getElementById('package_frequenza').value = pkg.frequenza || '';
                document.getElementById('package_prezzo').value = Number(pkg.prezzo || 0).toFixed(2);
                document.getElementById('package_strumento').value = pkg.strumento || '';
                packageModal.show();
            } catch (e) {
                showToast('Errore nel caricamento del pacchetto.', 'danger');
            }
        });
    });

    packageModalEl.addEventListener('hidden.bs.modal', resetPackageForm);
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

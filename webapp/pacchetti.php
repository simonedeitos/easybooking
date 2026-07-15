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

$requestAction = $_SERVER['REQUEST_METHOD'] === 'POST' ? post('action') : get('action');

if ($requestAction !== '') {
    try {
        if ($requestAction === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            verifyCsrf();

            $id = sanitizeInt(post('id'));
            $nome = trim(post('nome'));
            $descrizione = packageNullableString(post('descrizione'));
            $numeroLezioni = max(0, sanitizeInt(post('numero_lezioni')));
            $durataMinuti = max(1, sanitizeInt(post('durata_minuti')));
            $frequenza = packageNullableString(post('frequenza'));
            $prezzo = sanitizeFloat(str_replace(',', '.', post('prezzo')));
            $strumento = packageNullableString(post('strumento'));

            if ($nome === '') {
                jsonResponse(['success' => false, 'message' => 'Il nome del pacchetto è obbligatorio.'], 422);
            }
            if ($numeroLezioni <= 0) {
                jsonResponse(['success' => false, 'message' => 'Il numero di lezioni deve essere maggiore di zero.'], 422);
            }
            if ($prezzo < 0) {
                jsonResponse(['success' => false, 'message' => 'Il prezzo non può essere negativo.'], 422);
            }

            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE pacchetti
                     SET nome = ?, descrizione = ?, numero_lezioni = ?, durata_minuti = ?, frequenza = ?, prezzo = ?, strumento = ?
                     WHERE id = ?'
                );
                $stmt->execute([$nome, $descrizione, $numeroLezioni, $durataMinuti, $frequenza, $prezzo, $strumento, $id]);
                jsonResponse(['success' => true, 'message' => 'Pacchetto aggiornato con successo.']);
            }

            $stmt = $pdo->prepare(
                'INSERT INTO pacchetti (nome, descrizione, numero_lezioni, durata_minuti, frequenza, prezzo, strumento)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$nome, $descrizione, $numeroLezioni, $durataMinuti, $frequenza, $prezzo, $strumento]);
            jsonResponse(['success' => true, 'message' => 'Pacchetto creato con successo.', 'id' => (int)$pdo->lastInsertId()]);
        }

        if ($requestAction === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            verifyCsrf();
            $id = sanitizeInt(post('id'));
            if ($id <= 0) {
                jsonResponse(['success' => false, 'message' => 'Pacchetto non valido.'], 422);
            }

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM acquisti WHERE pacchetto_id = ?');
            $stmt->execute([$id]);
            if ((int)$stmt->fetchColumn() > 0) {
                jsonResponse(['success' => false, 'message' => 'Impossibile eliminare il pacchetto: esistono acquisti collegati.'], 409);
            }

            $stmt = $pdo->prepare('DELETE FROM pacchetti WHERE id = ?');
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 0) {
                jsonResponse(['success' => false, 'message' => 'Pacchetto non trovato.'], 404);
            }

            jsonResponse(['success' => true, 'message' => 'Pacchetto eliminato con successo.']);
        }

        if ($requestAction === 'get') {
            $id = sanitizeInt($_GET['id'] ?? $_POST['id'] ?? 0);
            if ($id <= 0) {
                jsonResponse(['success' => false, 'message' => 'Pacchetto non valido.'], 422);
            }

            $stmt = $pdo->prepare('SELECT * FROM pacchetti WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $package = $stmt->fetch();
            if (!$package) {
                jsonResponse(['success' => false, 'message' => 'Pacchetto non trovato.'], 404);
            }

            jsonResponse(['success' => true, 'package' => $package]);
        }
    } catch (Throwable $e) {
        error_log('pacchetti.php action error [' . $requestAction . ']: ' . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Errore durante l\'operazione richiesta.'], 500);
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
                                <button type="button" class="btn btn-sm btn-outline-primary btn-edit-package" data-id="<?= htmlspecialchars((string)$package['id']) ?>">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-package" data-id="<?= htmlspecialchars((string)$package['id']) ?>" data-name="<?= htmlspecialchars((string)$package['nome']) ?>">
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

    async function fetchPackage(id) {
        const response = await fetch(`pacchetti.php?action=get&id=${encodeURIComponent(id)}`);
        const data = await safeJsonResponse(response);
        if (!data.success) {
            throw new Error(data.message || 'Errore nel caricamento del pacchetto.');
        }
        return data.package;
    }

    document.getElementById('newPackageBtn')?.addEventListener('click', () => {
        resetPackageForm();
        packageModal.show();
    });

    document.querySelectorAll('.btn-edit-package').forEach((button) => {
        button.addEventListener('click', async () => {
            try {
                const pkg = await fetchPackage(button.dataset.id);
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
            } catch (error) {
                showToast(error.message, 'danger');
            }
        });
    });

    document.querySelectorAll('.btn-delete-package').forEach((button) => {
        button.addEventListener('click', async () => {
            const name = button.dataset.name || 'questo pacchetto';
            if (!confirm(`Eliminare ${name}?`)) {
                return;
            }
            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', button.dataset.id);
                formData.append('csrf_token', getCsrfToken());
                const response = await fetch('pacchetti.php', { method: 'POST', body: formData });
                const data = await safeJsonResponse(response);
                if (!data.success) {
                    throw new Error(data.message || 'Errore durante l\'eliminazione.');
                }
                showToast(data.message, 'success');
                window.location.reload();
            } catch (error) {
                showToast(error.message, 'danger');
            }
        });
    });

    ajaxForm(packageForm, (data) => {
        showToast(data.message || 'Pacchetto salvato.', 'success');
        window.location.reload();
    }, (message) => {
        showToast(message, 'danger');
    });

    packageModalEl.addEventListener('hidden.bs.modal', resetPackageForm);
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

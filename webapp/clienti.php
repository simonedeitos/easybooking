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

function clientNullableString(string $value): ?string
{
    $value = trim($value);
    return $value === '' ? null : $value;
}

function clientPreview(?string $value, int $length = 60): string
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
            verifyCsrfOrRedirect('clienti.php');

            $id = sanitizeInt(post('id'));
            $nome = trim(post('nome'));
            $cognome = trim(post('cognome'));
            $telefono = clientNullableString(post('telefono'));
            $emailRaw = clientNullableString(post('email'));
            $indirizzo = clientNullableString(post('indirizzo'));
            $codiceFiscale = clientNullableString(post('codice_fiscale'));
            $note = clientNullableString(post('note'));
            $megaPubblica = clientNullableString(post('mega_cartella_pubblica'));
            $megaLocale = clientNullableString(post('mega_cartella_locale'));

            if ($nome === '' || $cognome === '') {
                setFlash('warning', 'Nome e cognome sono obbligatori.');
                redirect('clienti.php');
            }

            $email = null;
            if ($emailRaw !== null) {
                if (!filter_var($emailRaw, FILTER_VALIDATE_EMAIL)) {
                    setFlash('warning', 'Inserisci un indirizzo email valido.');
                    redirect('clienti.php');
                }
                $email = $emailRaw;
            }

            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE clienti
                     SET nome = ?, cognome = ?, telefono = ?, email = ?, indirizzo = ?, codice_fiscale = ?, note = ?, mega_cartella_pubblica = ?, mega_cartella_locale = ?
                     WHERE id = ?'
                );
                $stmt->execute([$nome, $cognome, $telefono, $email, $indirizzo, $codiceFiscale, $note, $megaPubblica, $megaLocale, $id]);
                setFlash('success', 'Cliente aggiornato con successo.');
                redirect('clienti.php');
            }

            $stmt = $pdo->prepare(
                'INSERT INTO clienti (nome, cognome, telefono, email, indirizzo, codice_fiscale, note, mega_cartella_pubblica, mega_cartella_locale)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$nome, $cognome, $telefono, $email, $indirizzo, $codiceFiscale, $note, $megaPubblica, $megaLocale]);
            setFlash('success', 'Cliente creato con successo.');
            redirect('clienti.php');
        } elseif ($requestAction === 'delete') {
            verifyCsrfOrRedirect('clienti.php');

            $id = sanitizeInt(post('id'));
            if ($id <= 0) {
                setFlash('warning', 'Cliente non valido.');
                redirect('clienti.php');
            }

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM prenotazioni WHERE cliente_id = ?');
            $stmt->execute([$id]);
            $bookings = (int)$stmt->fetchColumn();

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM acquisti WHERE cliente_id = ?');
            $stmt->execute([$id]);
            $purchases = (int)$stmt->fetchColumn();

            if ($bookings > 0 || $purchases > 0) {
                setFlash('warning', 'Impossibile eliminare il cliente: sono presenti prenotazioni o acquisti collegati.');
                redirect('clienti.php');
            }

            $stmt = $pdo->prepare('DELETE FROM clienti WHERE id = ?');
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                setFlash('warning', 'Cliente non trovato.');
                redirect('clienti.php');
            }

            setFlash('success', 'Cliente eliminato con successo.');
            redirect('clienti.php');
        }
    } catch (Throwable $e) {
        error_log('[clienti.php] ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        setFlash('danger', 'Errore durante l\'operazione richiesta.');
        redirect('clienti.php');
    }
}

$clients = [];
$pageError = '';

try {
    $stmt = $pdo->prepare('SELECT * FROM clienti ORDER BY cognome ASC, nome ASC, id DESC');
    $stmt->execute();
    $clients = $stmt->fetchAll();
} catch (PDOException $e) {
    $pageError = 'Impossibile caricare l\'elenco clienti.';
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1">Clienti</h2>
        <p class="text-secondary mb-0">Gestisci anagrafica, contatti e riferimenti MEGA.</p>
    </div>
    <button type="button" class="btn btn-primary" id="newClientBtn">
        <i class="fas fa-plus me-2"></i>Nuovo Cliente
    </button>
</div>

<?php if ($pageError !== ''): ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($pageError) ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <i class="fas fa-users"></i>
        Elenco clienti
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable align-middle" id="clientiTable">
                <thead>
                    <tr>
                        <th>#ID</th>
                        <th>Nome Cognome</th>
                        <th>Telefono</th>
                        <th>Email</th>
                        <th>Note</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$client['id']) ?></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars(trim((string)$client['nome'] . ' ' . (string)$client['cognome'])) ?></div>
                            <?php if (!empty($client['codice_fiscale'])): ?>
                            <div class="small text-secondary"><?= htmlspecialchars((string)$client['codice_fiscale']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars((string)($client['telefono'] ?? '—')) ?></td>
                        <td><?= htmlspecialchars((string)($client['email'] ?? '—')) ?></td>
                        <td><?= htmlspecialchars(clientPreview($client['note'] ?? null)) ?></td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="cliente-dettaglio.php?id=<?= htmlspecialchars((string)$client['id']) ?>"
                                   class="btn btn-sm btn-outline-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-edit-client"
                                        data-edit="<?= htmlspecialchars(json_encode($client), ENT_QUOTES) ?>">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <form method="post" action="clienti.php" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars((string)$client['id']) ?>">
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                            onclick="confirmDelete(this.form, '<?= htmlspecialchars(addslashes(trim((string)$client['nome'] . ' ' . (string)$client['cognome'])), ENT_QUOTES) ?>')">
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

<div class="modal fade" id="clientModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="clientForm" method="post" action="clienti.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="clientModalTitle">Nuovo Cliente</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                </div>
                <div class="modal-body">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="client_id" value="">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nome" class="form-label">Nome *</label>
                            <input type="text" class="form-control" id="nome" name="nome" required>
                        </div>
                        <div class="col-md-6">
                            <label for="cognome" class="form-label">Cognome *</label>
                            <input type="text" class="form-control" id="cognome" name="cognome" required>
                        </div>
                        <div class="col-md-6">
                            <label for="telefono" class="form-label">Telefono</label>
                            <input type="text" class="form-control" id="telefono" name="telefono">
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        <div class="col-12">
                            <label for="indirizzo" class="form-label">Indirizzo</label>
                            <textarea class="form-control" id="indirizzo" name="indirizzo" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="codice_fiscale" class="form-label">Codice Fiscale</label>
                            <input type="text" class="form-control" id="codice_fiscale" name="codice_fiscale">
                        </div>
                        <div class="col-md-6">
                            <label for="mega_cartella_pubblica" class="form-label">MEGA Cartella Pubblica</label>
                            <input type="url" class="form-control" id="mega_cartella_pubblica" name="mega_cartella_pubblica">
                        </div>
                        <div class="col-12">
                            <label for="mega_cartella_locale" class="form-label">MEGA Cartella Locale</label>
                            <input type="text" class="form-control" id="mega_cartella_locale" name="mega_cartella_locale">
                        </div>
                        <div class="col-12">
                            <label for="note" class="form-label">Note</label>
                            <textarea class="form-control" id="note" name="note" rows="4"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Salva Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Dettaglio Cliente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="detail-box">
                            <div class="detail-label">Nome e cognome</div>
                            <div class="detail-value" id="detail_full_name">—</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-box">
                            <div class="detail-label">Codice fiscale</div>
                            <div class="detail-value" id="detail_cf">—</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-box">
                            <div class="detail-label">Telefono</div>
                            <div class="detail-value" id="detail_phone">—</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-box">
                            <div class="detail-label">Email</div>
                            <div class="detail-value" id="detail_email">—</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="detail-box">
                            <div class="detail-label">Indirizzo</div>
                            <div class="detail-value" id="detail_address">—</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="detail-box">
                            <div class="detail-label">Note</div>
                            <div class="detail-value" id="detail_note">—</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <a href="#" class="btn btn-success d-none" id="detail_whatsapp" target="_blank" rel="noopener noreferrer">
                        <i class="fab fa-whatsapp me-2"></i>WhatsApp
                    </a>
                    <a href="#" class="btn btn-outline-primary d-none" id="detail_mega_public" target="_blank" rel="noopener noreferrer">
                        <i class="fas fa-folder-open me-2"></i>MEGA Pubblica
                    </a>
                    <a href="#" class="btn btn-outline-light d-none" id="detail_mega_local" target="_blank" rel="noopener noreferrer">
                        <i class="fas fa-desktop me-2"></i>MEGA Locale
                    </a>
                    <a href="#" class="btn btn-primary d-none" id="detail_pdf_futuri" target="_blank" rel="noopener noreferrer">
                        <i class="fas fa-file-pdf me-2"></i>PDF Lezioni Future
                    </a>
                    <a href="#" class="btn btn-secondary d-none" id="detail_pdf_storico" target="_blank" rel="noopener noreferrer">
                        <i class="fas fa-file-pdf me-2"></i>PDF Storico
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.text-secondary { color: var(--text-secondary) !important; }
.detail-box {
    background: rgba(255,255,255,0.02);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-sm);
    padding: 14px 16px;
    height: 100%;
}
.detail-label {
    font-size: 0.8rem;
    text-transform: uppercase;
    color: var(--text-muted);
    margin-bottom: 6px;
}
.detail-value {
    white-space: pre-wrap;
    word-break: break-word;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const clientModalEl = document.getElementById('clientModal');
    const clientModal = new bootstrap.Modal(clientModalEl);
    const clientForm = document.getElementById('clientForm');

    function resetClientForm() {
        clientForm.reset();
        document.getElementById('client_id').value = '';
        document.getElementById('clientModalTitle').textContent = 'Nuovo Cliente';
    }

    document.getElementById('newClientBtn').addEventListener('click', () => {
        resetClientForm();
        clientModal.show();
    });

    document.querySelectorAll('.btn-edit-client').forEach((button) => {
        button.addEventListener('click', () => {
            try {
                const client = JSON.parse(button.dataset.edit);
                resetClientForm();
                document.getElementById('clientModalTitle').textContent = 'Modifica Cliente';
                document.getElementById('client_id').value = client.id || '';
                document.getElementById('nome').value = client.nome || '';
                document.getElementById('cognome').value = client.cognome || '';
                document.getElementById('telefono').value = client.telefono || '';
                document.getElementById('email').value = client.email || '';
                document.getElementById('indirizzo').value = client.indirizzo || '';
                document.getElementById('codice_fiscale').value = client.codice_fiscale || '';
                document.getElementById('note').value = client.note || '';
                document.getElementById('mega_cartella_pubblica').value = client.mega_cartella_pubblica || '';
                document.getElementById('mega_cartella_locale').value = client.mega_cartella_locale || '';
                clientModal.show();
            } catch (e) {
                showToast('Errore nel caricamento del cliente.', 'danger');
            }
        });
    });

    clientModalEl.addEventListener('hidden.bs.modal', resetClientForm);
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

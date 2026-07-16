<?php
// cloud.php – Cloud Storage management page

ob_start();
ini_set('log_errors', '1');
ini_set('display_errors', '0');
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/encryption.php';
require_once __DIR__ . '/config/cloud-functions.php';
require_once __DIR__ . '/includes/auth.php';
requireAuth();

$pdo  = Database::getInstance();
$user = currentUser();

function h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Fetch all clients with cloud info
$clients = [];
try {
    $stmt = $pdo->query(
        'SELECT c.id, c.nome, c.cognome, c.email, c.cloud_enabled, c.cloud_hash, c.cloud_cartella,
                (SELECT COALESCE(SUM(cf.dimensione_bytes),0) FROM cloud_files cf WHERE cf.cliente_id = c.id) AS spazio_bytes,
                (SELECT COUNT(*) FROM cloud_files cf WHERE cf.cliente_id = c.id) AS numero_file
         FROM clienti c
         ORDER BY c.cloud_enabled DESC, c.cognome ASC, c.nome ASC'
    );
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException) {
    // silently degrade
}

// Global stats
$stats = ['spazio_totale_bytes' => 0, 'numero_file' => 0, 'numero_clienti' => 0];
try {
    cloudUpdateStats($pdo);
    $s = $pdo->query('SELECT * FROM cloud_stats WHERE id = 1 LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    if ($s) { $stats = $s; }
} catch (PDOException) {}

$spazioUsato = (int)$stats['spazio_totale_bytes'];
$spazioMax   = CLOUD_MAX_BYTES;
$pctUsato    = $spazioMax > 0 ? round($spazioUsato / $spazioMax * 100, 1) : 0;

require_once __DIR__ . '/includes/header.php';
?>

<!-- ── Page header ──────────────────────────────────────────────── -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1"><i class="fas fa-cloud me-2"></i>Cloud Storage</h2>
        <p class="text-secondary mb-0">Gestisci lo spazio cloud per ogni cliente, carica file e condividi link pubblici.</p>
    </div>
</div>

<!-- ── Global quota bar ─────────────────────────────────────────── -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <strong><i class="fas fa-hdd me-2"></i>Spazio globale utilizzato</strong>
            <span id="cloud-stats-text"><?= h(cloudFormatSize($spazioUsato)) ?> / <?= h(cloudFormatSize($spazioMax)) ?> (<?= $pctUsato ?>%)</span>
        </div>
        <div class="progress" style="height:12px;">
            <div id="cloud-stats-bar"
                 class="progress-bar <?= $pctUsato >= 90 ? 'bg-danger' : ($pctUsato >= 70 ? 'bg-warning' : 'bg-primary') ?>"
                 role="progressbar"
                 style="width:<?= $pctUsato ?>%"
                 aria-valuenow="<?= $pctUsato ?>"
                 aria-valuemin="0"
                 aria-valuemax="100">
            </div>
        </div>
        <div id="cloud-quota-alert" class="<?= $pctUsato >= 70 ? 'alert ' . ($pctUsato >= 90 ? 'alert-danger' : 'alert-warning') . ' py-1 mt-2' : 'd-none' ?>">
            <?php if ($pctUsato >= 90): ?>⚠️ Attenzione: spazio quasi esaurito (<?= $pctUsato ?>%).
            <?php elseif ($pctUsato >= 70): ?>⚠️ Lo spazio cloud è al <?= $pctUsato ?>%. Considera di liberare spazio.
            <?php endif; ?>
        </div>
        <div class="row g-3 mt-1">
            <div class="col-auto">
                <small class="text-muted"><i class="fas fa-users me-1"></i><?= (int)$stats['numero_clienti'] ?> clienti con cloud attivo</small>
            </div>
            <div class="col-auto">
                <small class="text-muted"><i class="fas fa-file me-1"></i><?= (int)$stats['numero_file'] ?> file totali</small>
            </div>
        </div>
    </div>
</div>

<!-- ── Clients table ─────────────────────────────────────────────── -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-list me-2"></i>Elenco Clienti
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="cloud-clients-table" class="table table-hover align-middle mb-0 datatable">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Stato Cloud</th>
                        <th>Spazio</th>
                        <th>File</th>
                        <th>Link Pubblico</th>
                        <th class="text-end">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($clients as $c): ?>
                    <tr>
                        <td>
                            <strong><?= h($c['cognome']) ?> <?= h($c['nome']) ?></strong>
                            <?php if ($c['email']): ?>
                            <br><small class="text-muted"><?= h($c['email']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($c['cloud_enabled']): ?>
                                <span class="badge bg-success"><i class="fas fa-check me-1"></i>Attivo</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><i class="fas fa-times me-1"></i>Non attivo</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap">
                            <?= $c['cloud_enabled'] ? h(cloudFormatSize((int)$c['spazio_bytes'])) : '—' ?>
                        </td>
                        <td>
                            <?= $c['cloud_enabled'] ? (int)$c['numero_file'] : '—' ?>
                        </td>
                        <td style="max-width:220px;">
                            <?php if ($c['cloud_enabled'] && $c['cloud_hash']): ?>
                                <?php $shareUrl = cloudShareUrl($c['cloud_hash']); ?>
                                <div class="d-flex align-items-center gap-1">
                                    <small class="text-muted text-truncate" style="max-width:140px;" title="<?= h($shareUrl) ?>">
                                        <?= h($shareUrl) ?>
                                    </small>
                                    <button class="btn btn-xs btn-outline-secondary"
                                            onclick="cloudCopyLink('<?= h($shareUrl) ?>')"
                                            title="Copia link">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <?php if ($c['cloud_enabled']): ?>
                                <button class="btn btn-sm btn-outline-primary me-1"
                                        data-open-cloud="<?= (int)$c['id'] ?>"
                                        data-cliente-nome="<?= h($c['cognome'] . ' ' . $c['nome']) ?>">
                                    <i class="fas fa-folder-open me-1"></i>File
                                </button>
                                <button class="btn btn-sm btn-outline-danger"
                                        data-cloud-disable="<?= (int)$c['id'] ?>">
                                    <i class="fas fa-cloud-slash me-1"></i>Disabilita
                                </button>
                            <?php else: ?>
                                <button class="btn btn-sm btn-outline-success"
                                        data-cloud-enable="<?= (int)$c['id'] ?>">
                                    <i class="fas fa-cloud-upload-alt me-1"></i>Abilita Cloud
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════
     Modal: File manager for a client
══════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="cloudFilesModal" tabindex="-1" aria-labelledby="cloud-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cloud-modal-title">☁️ Cloud</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Upload zone -->
                <div id="cloud-drop-zone" class="cloud-drop-zone mb-3 p-4 text-center border border-dashed rounded"
                     style="cursor:pointer;border-style:dashed!important;min-height:100px;">
                    <i class="fas fa-cloud-upload-alt fa-2x mb-2 text-muted d-block"></i>
                    <span class="text-muted">Trascina i file qui, oppure clicca per selezionarli</span>
                    <input type="file" id="cloud-file-input" multiple class="d-none">
                </div>
                <!-- Upload progress -->
                <div id="cloud-upload-progress" class="d-none mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <small>Caricamento in corso…</small>
                        <small id="cloud-upload-text">0%</small>
                    </div>
                    <div class="progress" style="height:8px;">
                        <div id="cloud-upload-bar" class="progress-bar progress-bar-striped progress-bar-animated"
                             role="progressbar" style="width:0%"></div>
                    </div>
                </div>
                <!-- File list -->
                <div id="cloud-files-list">
                    <div class="text-center text-muted py-4">Seleziona un cliente per vedere i file.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                <button type="button" class="btn btn-primary" id="cloud-upload-btn">
                    <i class="fas fa-upload me-1"></i>Carica file
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════
     Modal: Edit note
══════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="cloudNoteModal" tabindex="-1" aria-labelledby="cloudNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cloudNoteModalLabel"><i class="fas fa-pencil-alt me-2"></i>Modifica Nota</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="cloud-note-file-id">
                <div class="mb-3">
                    <label for="cloud-note-text" class="form-label">Nota / Descrizione</label>
                    <textarea id="cloud-note-text" class="form-control" rows="4" placeholder="Aggiungi una nota descrittiva per questo file…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-primary" id="cloud-note-save-btn">
                    <i class="fas fa-save me-1"></i>Salva
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════
     Modal: Audio player
══════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="cloudAudioModal" tabindex="-1" aria-labelledby="cloudAudioModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cloudAudioModalLabel"><i class="fas fa-music me-2"></i>Player Audio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p id="cloud-audio-title" class="fw-bold mb-3"></p>
                <audio id="cloud-audio-player" controls class="w-100">
                    Il tuo browser non supporta l'audio HTML5.
                </audio>
            </div>
        </div>
    </div>
</div>

<style>
.cloud-drop-zone { transition: background 0.2s; }
.cloud-drop-zone.drag-over { background: var(--bs-primary-bg-subtle, #e8f0fe); }
.btn-xs { padding: 0.15rem 0.35rem; font-size: 0.75rem; }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<script src="assets/js/cloud.js"></script>
<script>
// Init DataTable for clients
document.addEventListener('DOMContentLoaded', function () {
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        $('#cloud-clients-table').DataTable({
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/it-IT.json' },
            pageLength: 25,
            columnDefs: [{ orderable: false, targets: [4, 5] }],
        });
    }
});
</script>

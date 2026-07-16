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

// Fetch clients with cloud enabled (sidebar)
$clientsEnabled = [];
// Fetch clients without cloud (for create modal)
$clientsDisabled = [];
try {
    $stmt = $pdo->query(
        'SELECT c.id, c.nome, c.cognome, c.email, c.cloud_enabled, c.cloud_hash, c.cloud_cartella,
                COALESCE((SELECT SUM(cf.dimensione_bytes) FROM cloud_files cf WHERE cf.cliente_id = c.id), 0) AS spazio_bytes,
                COALESCE((SELECT COUNT(*) FROM cloud_files cf WHERE cf.cliente_id = c.id), 0) AS numero_file
         FROM clienti c
         ORDER BY c.cognome ASC, c.nome ASC'
    );
    $allClients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($allClients as $c) {
        if ($c['cloud_enabled']) {
            $clientsEnabled[] = $c;
        } else {
            $clientsDisabled[] = $c;
        }
    }
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
$quotaClass  = $pctUsato >= 90 ? 'danger' : ($pctUsato >= 70 ? 'warning' : 'success');

require_once __DIR__ . '/includes/header.php';
?>

<!-- ── Page header with compact quota badge ─────────────────────── -->
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
    <div>
        <h2 class="mb-1"><i class="fas fa-cloud me-2" aria-hidden="true"></i>Cloud Storage</h2>
        <p class="text-secondary mb-0 small">Gestisci lo spazio cloud per ogni cliente.</p>
    </div>
    <!-- Compact quota badge (top right) -->
    <div class="cloud-quota-badge" id="cloud-quota-badge">
        <div class="d-flex align-items-center gap-2 mb-1">
            <i class="fas fa-hdd text-<?= $quotaClass ?>" aria-hidden="true"></i>
            <span id="cloud-stats-text" class="fw-semibold small">
                <?= h(cloudFormatSize($spazioUsato)) ?> / <?= h(cloudFormatSize($spazioMax)) ?> (<?= $pctUsato ?>%)
            </span>
        </div>
        <div class="progress cloud-quota-progress">
            <div id="cloud-stats-bar"
                 class="progress-bar bg-<?= $quotaClass ?>"
                 role="progressbar"
                 style="width:<?= $pctUsato ?>%"
                 aria-valuenow="<?= $pctUsato ?>"
                 aria-valuemin="0"
                 aria-valuemax="100">
            </div>
        </div>
    </div>
</div>

<!-- ── Two-column cloud layout ───────────────────────────────────── -->
<div class="cloud-layout">

    <!-- ── Left sidebar: client list ─────────────────────────────── -->
    <aside class="cloud-clients-sidebar">
        <div class="cloud-sidebar-header">
            <i class="fas fa-users me-2" aria-hidden="true"></i>Clienti Cloud
            <span class="badge bg-secondary ms-auto"><?= count($clientsEnabled) ?></span>
        </div>

        <div class="cloud-client-list" id="cloud-client-list">
            <?php if (empty($clientsEnabled)): ?>
                <div class="text-muted small text-center p-3">
                    Nessun cliente con cloud attivo.<br>
                    Usa il pulsante qui sotto.
                </div>
            <?php else: ?>
                <?php foreach ($clientsEnabled as $c): ?>
                <button type="button"
                        class="cloud-client-btn"
                        data-client-id="<?= (int)$c['id'] ?>"
                        data-client-nome="<?= h($c['cognome'] . ' ' . $c['nome']) ?>"
                        data-client-hash="<?= h($c['cloud_hash'] ?? '') ?>">
                    <span class="cloud-client-name"><?= h($c['cognome'] . ' ' . $c['nome']) ?></span>
                    <span class="cloud-client-badges">
                        <span class="badge bg-secondary" title="File">
                            <i class="fas fa-file me-1"></i><?= (int)$c['numero_file'] ?>
                        </span>
                        <span class="badge bg-secondary" title="Spazio">
                            <i class="fas fa-hdd me-1"></i><?= h(cloudFormatSize((int)$c['spazio_bytes'])) ?>
                        </span>
                    </span>
                </button>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="cloud-sidebar-footer">
            <button type="button" id="create-cloud-btn" class="btn btn-success w-100">
                <i class="fas fa-plus me-2"></i>Crea Cloud Cliente
            </button>
        </div>
    </aside>

    <!-- ── Main area ──────────────────────────────────────────────── -->
    <div class="cloud-main-area">

        <!-- Empty state -->
        <div id="cloud-empty-state" class="cloud-empty-state">
            <i class="fas fa-cloud fa-3x mb-3 text-muted" aria-hidden="true"></i>
            <h5 class="text-muted">Seleziona un cliente</h5>
            <p class="text-muted small">Scegli un cliente dalla lista per gestire i suoi file cloud.</p>
        </div>

        <!-- Client detail (hidden by default) -->
        <div id="cloud-client-detail" class="d-none">

            <!-- Client toolbar card -->
            <div class="cloud-toolbar card mb-3">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <div class="flex-grow-1">
                            <h4 class="mb-1" id="cloud-toolbar-name"></h4>
                            <div class="d-flex gap-3 text-muted small">
                                <span><i class="fas fa-folder me-1"></i><span id="cloud-toolbar-files">0</span> file</span>
                                <span><i class="fas fa-hdd me-1"></i><span id="cloud-toolbar-space">0 B</span></span>
                            </div>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="cloud-copy-link-btn">
                                <i class="fas fa-link me-1"></i>Copia Link
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="cloud-settings-btn">
                                <i class="fas fa-cog me-1"></i>Impostazioni
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Drag & drop upload zone -->
            <div id="cloud-drop-zone" class="cloud-drop-zone mb-3">
                <i class="fas fa-cloud-upload-alt fa-2x mb-2 text-muted d-block" aria-hidden="true"></i>
                <span class="text-muted">Trascina i file qui, oppure clicca per selezionarli</span>
                <input type="file" id="cloud-file-input" multiple class="d-none">
            </div>

            <!-- Upload progress -->
            <div id="cloud-upload-progress" class="d-none mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <small>Caricamento in corso…</small>
                    <small id="cloud-upload-text">0%</small>
                </div>
                <div class="progress" style="height:6px;">
                    <div id="cloud-upload-bar" class="progress-bar progress-bar-striped progress-bar-animated"
                         role="progressbar" style="width:0%"></div>
                </div>
            </div>

            <!-- File list -->
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span><i class="fas fa-list me-2"></i>File</span>
                    <button type="button" class="btn btn-sm btn-primary" id="cloud-upload-btn">
                        <i class="fas fa-upload me-1"></i>Carica
                    </button>
                </div>
                <div class="card-body p-0">
                    <div id="cloud-files-list">
                        <div class="text-center text-muted py-4">Caricamento…</div>
                    </div>
                </div>
            </div>

        </div>
    </div><!-- /.cloud-main-area -->
</div><!-- /.cloud-layout -->

<!-- ═══════════════════════════════════════════════════════════════
     Modal: Crea Cloud Cliente
═════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="createCloudModal" tabindex="-1" aria-labelledby="createCloudModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createCloudModalLabel"><i class="fas fa-plus-circle me-2"></i>Crea Cloud Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if (empty($clientsDisabled)): ?>
                    <p class="text-muted mb-0">Tutti i clienti hanno già il cloud abilitato.</p>
                <?php else: ?>
                    <div class="mb-3">
                        <label for="create-cloud-select" class="form-label">Seleziona cliente</label>
                        <select id="create-cloud-select" class="form-select">
                            <option value="">— Scegli un cliente —</option>
                            <?php foreach ($clientsDisabled as $c): ?>
                            <option value="<?= (int)$c['id'] ?>"><?= h($c['cognome'] . ' ' . $c['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <?php if (!empty($clientsDisabled)): ?>
                <button type="button" class="btn btn-success" id="create-cloud-confirm-btn">
                    <i class="fas fa-check me-1"></i>Crea
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     Modal: Audio player
═════════════════════════════════════════════════════════════════ -->
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<script src="assets/js/cloud.js"></script>

<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/encryption.php';
require_once __DIR__ . '/includes/auth.php';
requireAuth();
$pdo = Database::getInstance();
$user = currentUser();

function h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$requestAction = $_SERVER['REQUEST_METHOD'] === 'POST' ? post('action') : get('action');

if ($requestAction === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrf();
        $email = trim(post('email_notifiche'));
        $abilita = isset($_POST['abilita_notifiche']) ? 1 : 0;
        $pacchettoGiorni = max(0, sanitizeInt(post('pacchetto_scadenza_giorni')));
        $lezioneGiorni = max(0, sanitizeInt(post('lezione_reminder_giorni')));
        $sommarioSettimanale = isset($_POST['sommario_settimanale']) ? 1 : 0;
        $sommarioMensile = isset($_POST['sommario_mensile']) ? 1 : 0;

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlash('danger', 'Inserisci un indirizzo email valido per le notifiche.');
            redirect('notifiche.php');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO notifiche_config
                (user_id, email_notifiche, abilita_notifiche, pacchetto_scadenza_giorni, lezione_reminder_giorni, sommario_settimanale, sommario_mensile)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                email_notifiche = VALUES(email_notifiche),
                abilita_notifiche = VALUES(abilita_notifiche),
                pacchetto_scadenza_giorni = VALUES(pacchetto_scadenza_giorni),
                lezione_reminder_giorni = VALUES(lezione_reminder_giorni),
                sommario_settimanale = VALUES(sommario_settimanale),
                sommario_mensile = VALUES(sommario_mensile)'
        );
        $stmt->execute([
            (int)$user['id'],
            $email !== '' ? $email : null,
            $abilita,
            $pacchettoGiorni,
            $lezioneGiorni,
            $sommarioSettimanale,
            $sommarioMensile,
        ]);

        setFlash('success', 'Preferenze notifiche salvate con successo.');
        redirect('notifiche.php');
    } catch (PDOException $e) {
        setFlash('danger', 'Errore durante il salvataggio delle notifiche.');
        redirect('notifiche.php');
    }
}

$config = [
    'abilita_notifiche' => 1,
    'email_notifiche' => (string)($user['email'] ?? ''),
    'pacchetto_scadenza_giorni' => 7,
    'lezione_reminder_giorni' => 1,
    'sommario_settimanale' => 0,
    'sommario_mensile' => 0,
];
$pageError = '';

try {
    $stmt = $pdo->prepare('SELECT * FROM notifiche_config WHERE user_id = ? LIMIT 1');
    $stmt->execute([(int)$user['id']]);
    $row = $stmt->fetch();
    if ($row) {
        $config = array_merge($config, $row);
        if (empty($config['email_notifiche'])) {
            $config['email_notifiche'] = (string)($user['email'] ?? '');
        }
    }
} catch (PDOException $e) {
    $pageError = 'Impossibile caricare la configurazione notifiche.';
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1">Notifiche</h2>
        <p class="text-secondary mb-0">Configura email, promemoria e riepiloghi automatici.</p>
    </div>
</div>

<?php if ($pageError !== ''): ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i><?= h($pageError) ?>
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="fas fa-bell me-2"></i>Configurazione notifiche</div>
            <div class="card-body">
                <form method="post" action="notifiche.php" class="row g-3">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="save">
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="abilita_notifiche" name="abilita_notifiche" value="1" <?= !empty($config['abilita_notifiche']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="abilita_notifiche">Abilita notifiche</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="email_notifiche" class="form-label">Email per notifiche</label>
                        <input type="email" class="form-control" id="email_notifiche" name="email_notifiche" value="<?= h((string)$config['email_notifiche']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="pacchetto_scadenza_giorni" class="form-label">Scadenza pacchetto (giorni prima)</label>
                        <input type="number" class="form-control" id="pacchetto_scadenza_giorni" name="pacchetto_scadenza_giorni" min="0" value="<?= h((string)$config['pacchetto_scadenza_giorni']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="lezione_reminder_giorni" class="form-label">Promemoria lezione (giorni prima)</label>
                        <input type="number" class="form-control" id="lezione_reminder_giorni" name="lezione_reminder_giorni" min="0" value="<?= h((string)$config['lezione_reminder_giorni']) ?>">
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="sommario_settimanale" name="sommario_settimanale" value="1" <?= !empty($config['sommario_settimanale']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="sommario_settimanale">Sommario settimanale</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="sommario_mensile" name="sommario_mensile" value="1" <?= !empty($config['sommario_mensile']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="sommario_mensile">Sommario mensile</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Salva notifiche</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-info">
            <div class="card-header bg-info-subtle"><i class="fas fa-info-circle me-2"></i>Come funzionano</div>
            <div class="card-body">
                <ul class="mb-0 ps-3 d-grid gap-2">
                    <li><strong>Abilita notifiche</strong>: attiva o sospende l'invio per il tuo utente.</li>
                    <li><strong>Email per notifiche</strong>: indirizzo usato per promemoria e riepiloghi.</li>
                    <li><strong>Scadenza pacchetto</strong>: avvisa qualche giorno prima dell'esaurimento lezioni.</li>
                    <li><strong>Promemoria lezione</strong>: invia un alert in anticipo rispetto alla lezione.</li>
                    <li><strong>Sommari</strong>: riepiloghi automatici con panoramica settimanale o mensile.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php';

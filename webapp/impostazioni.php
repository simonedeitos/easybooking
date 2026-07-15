<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/encryption.php';
require_once __DIR__ . '/includes/auth.php';
requireAuth();
$pdo = Database::getInstance();
$user = currentUser();
$isAdmin = ($user['role'] ?? 'user') === 'admin';

function h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function settingsDayMap(): array
{
    return [
        'lun' => 'Lunedì',
        'mar' => 'Martedì',
        'mer' => 'Mercoledì',
        'gio' => 'Giovedì',
        'ven' => 'Venerdì',
        'sab' => 'Sabato',
        'dom' => 'Domenica',
    ];
}

function settingsFlag(string $key): int
{
    return isset($_POST[$key]) ? 1 : 0;
}

function settingsTime(string $value, string $fallback): string
{
    $value = trim($value);
    if (preg_match('/^\d{2}:\d{2}$/', $value) === 1) {
        return $value . ':00';
    }
    if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value) === 1) {
        return substr($value, 0, 8);
    }
    return $fallback;
}

function settingsConfigValue(PDO $pdo, string $key, string $default = ''): string
{
    $stmt = $pdo->prepare('SELECT `value` FROM system_config WHERE `key` = ? LIMIT 1');
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    return is_string($value) ? $value : $default;
}

$activeTab = get('tab', 'generale');
$requestAction = $_SERVER['REQUEST_METHOD'] === 'POST' ? (post('action') ?: get('action')) : get('action');

if ($requestAction !== '') {
    try {
        if ($requestAction === 'save_general' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            verifyCsrf();
            if (!$isAdmin) {
                setFlash('danger', 'Solo un amministratore può modificare le impostazioni generali.');
                redirect('impostazioni.php?tab=generale');
            }

            $durata = max(15, sanitizeInt(post('durata_lezione_default')));
            $stmt = $pdo->prepare(
                'INSERT INTO impostazioni_generali
                    (id, lun_attivo, mar_attivo, mer_attivo, gio_attivo, ven_attivo, sab_attivo, dom_attivo, matt_inizio, matt_fine, pom_inizio, pom_fine, durata_lezione_default)
                 VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    lun_attivo = VALUES(lun_attivo),
                    mar_attivo = VALUES(mar_attivo),
                    mer_attivo = VALUES(mer_attivo),
                    gio_attivo = VALUES(gio_attivo),
                    ven_attivo = VALUES(ven_attivo),
                    sab_attivo = VALUES(sab_attivo),
                    dom_attivo = VALUES(dom_attivo),
                    matt_inizio = VALUES(matt_inizio),
                    matt_fine = VALUES(matt_fine),
                    pom_inizio = VALUES(pom_inizio),
                    pom_fine = VALUES(pom_fine),
                    durata_lezione_default = VALUES(durata_lezione_default)'
            );
            $stmt->execute([
                settingsFlag('lun_attivo'), settingsFlag('mar_attivo'), settingsFlag('mer_attivo'), settingsFlag('gio_attivo'),
                settingsFlag('ven_attivo'), settingsFlag('sab_attivo'), settingsFlag('dom_attivo'),
                settingsTime(post('matt_inizio'), '09:00:00'), settingsTime(post('matt_fine'), '13:00:00'),
                settingsTime(post('pom_inizio'), '15:00:00'), settingsTime(post('pom_fine'), '19:00:00'),
                $durata,
            ]);
            setFlash('success', 'Impostazioni generali aggiornate.');
            redirect('impostazioni.php?tab=generale');
        }

        if ($requestAction === 'save_app' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            verifyCsrf();
            if (!$isAdmin) {
                setFlash('danger', 'Solo un amministratore può modificare le impostazioni applicative.');
                redirect('impostazioni.php?tab=app');
            }

            $appNameValue = trim(post('app_name'));
            $appEmailValue = trim(post('app_email'));
            if ($appNameValue === '') {
                setFlash('danger', 'Il nome applicazione è obbligatorio.');
                redirect('impostazioni.php?tab=app');
            }
            if ($appEmailValue !== '' && !filter_var($appEmailValue, FILTER_VALIDATE_EMAIL)) {
                setFlash('danger', 'Inserisci un indirizzo email valido.');
                redirect('impostazioni.php?tab=app');
            }

            $stmt = $pdo->prepare(
                'INSERT INTO system_config (`key`, `value`) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), updated_at = CURRENT_TIMESTAMP'
            );
            $stmt->execute(['app_name', $appNameValue]);
            $stmt->execute(['app_email', $appEmailValue]);
            setFlash('success', 'Impostazioni applicazione aggiornate.');
            redirect('impostazioni.php?tab=app');
        }

        if ($requestAction === 'save_profile' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            verifyCsrf();
            $username = trim(post('username'));
            $email = trim(post('email'));
            $currentPassword = post('current_password');
            $newPassword = post('new_password');
            $confirmPassword = post('confirm_password');

            if ($username === '' || $email === '') {
                setFlash('danger', 'Username ed email sono obbligatori.');
                redirect('impostazioni.php?tab=profilo');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                setFlash('danger', 'Inserisci un indirizzo email valido.');
                redirect('impostazioni.php?tab=profilo');
            }

            $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([(int)$user['id']]);
            $dbUser = $stmt->fetch();
            if (!$dbUser) {
                setFlash('danger', 'Utente non trovato.');
                redirect('impostazioni.php?tab=profilo');
            }

            $stmt = $pdo->prepare('SELECT id FROM users WHERE (username = ? OR email = ?) AND id <> ? LIMIT 1');
            $stmt->execute([$username, $email, (int)$user['id']]);
            if ($stmt->fetch()) {
                setFlash('danger', 'Username o email già in uso da un altro utente.');
                redirect('impostazioni.php?tab=profilo');
            }

            if ($newPassword !== '' || $confirmPassword !== '' || $currentPassword !== '') {
                if ($newPassword === '' || $confirmPassword === '') {
                    setFlash('danger', 'Compila tutti i campi password per modificare la password.');
                    redirect('impostazioni.php?tab=profilo');
                }
                if (!password_verify($currentPassword, (string)$dbUser['password_hash'])) {
                    setFlash('danger', 'La password attuale non è corretta.');
                    redirect('impostazioni.php?tab=profilo');
                }
                if ($newPassword !== $confirmPassword) {
                    setFlash('danger', 'La nuova password e la conferma non coincidono.');
                    redirect('impostazioni.php?tab=profilo');
                }
                if (strlen($newPassword) < 8) {
                    setFlash('danger', 'La nuova password deve contenere almeno 8 caratteri.');
                    redirect('impostazioni.php?tab=profilo');
                }

                $stmt = $pdo->prepare('UPDATE users SET username = ?, email = ?, password_hash = ? WHERE id = ?');
                $stmt->execute([$username, $email, password_hash($newPassword, PASSWORD_DEFAULT), (int)$user['id']]);
            } else {
                $stmt = $pdo->prepare('UPDATE users SET username = ?, email = ? WHERE id = ?');
                $stmt->execute([$username, $email, (int)$user['id']]);
            }

            $_SESSION['username'] = $username;
            $_SESSION['user_email'] = $email;
            setFlash('success', 'Profilo aggiornato con successo.');
            redirect('impostazioni.php?tab=profilo');
        }

        if ($requestAction === 'set_theme' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            verifyCsrf();
            $theme = post('theme') === 'light' ? 'light' : 'dark';
            $stmt = $pdo->prepare('UPDATE users SET theme_preference = ? WHERE id = ?');
            $stmt->execute([$theme, (int)$user['id']]);
            $_SESSION['user_theme'] = $theme;
            jsonResponse(['success' => true, 'message' => 'Tema aggiornato.', 'theme' => $theme]);
        }
    } catch (PDOException $e) {
        if ($requestAction === 'set_theme') {
            jsonResponse(['success' => false, 'message' => 'Impossibile aggiornare il tema.'], 500);
        }
        setFlash('danger', 'Errore durante il salvataggio delle impostazioni.');
        redirect('impostazioni.php?tab=' . urlencode($activeTab));
    }
}

$generalSettings = [
    'lun_attivo' => 1, 'mar_attivo' => 1, 'mer_attivo' => 1, 'gio_attivo' => 1, 'ven_attivo' => 1, 'sab_attivo' => 0, 'dom_attivo' => 0,
    'matt_inizio' => '09:00:00', 'matt_fine' => '13:00:00', 'pom_inizio' => '15:00:00', 'pom_fine' => '19:00:00', 'durata_lezione_default' => 60,
];
$appConfig = ['app_name' => appName(), 'app_email' => ''];
$pageError = '';

try {
    $stmt = $pdo->prepare('SELECT * FROM impostazioni_generali WHERE id = 1 LIMIT 1');
    $stmt->execute();
    $row = $stmt->fetch();
    if ($row) {
        $generalSettings = array_merge($generalSettings, $row);
    }
    $appConfig['app_name'] = settingsConfigValue($pdo, 'app_name', $appConfig['app_name']);
    $appConfig['app_email'] = settingsConfigValue($pdo, 'app_email', '');
} catch (PDOException $e) {
    $pageError = 'Impossibile caricare le impostazioni correnti.';
}

$themePreference = $_SESSION['user_theme'] ?? ($user['theme'] ?? 'dark');
$validTabs = ['generale', 'app', 'profilo', 'tema'];
if (!$isAdmin && $activeTab === 'app') {
    $activeTab = 'profilo';
}
if (!in_array($activeTab, $validTabs, true)) {
    $activeTab = 'generale';
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1">Impostazioni</h2>
        <p class="text-secondary mb-0">Configura operatività, dati applicativi, profilo utente e tema grafico.</p>
    </div>
</div>

<?php if ($pageError !== ''): ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i><?= h($pageError) ?>
</div>
<?php endif; ?>

<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item"><a class="nav-link <?= $activeTab === 'generale' ? 'active' : '' ?>" href="impostazioni.php?tab=generale">Generale</a></li>
    <?php if ($isAdmin): ?><li class="nav-item"><a class="nav-link <?= $activeTab === 'app' ? 'active' : '' ?>" href="impostazioni.php?tab=app">Applicazione</a></li><?php endif; ?>
    <li class="nav-item"><a class="nav-link <?= $activeTab === 'profilo' ? 'active' : '' ?>" href="impostazioni.php?tab=profilo">Profilo</a></li>
    <li class="nav-item"><a class="nav-link <?= $activeTab === 'tema' ? 'active' : '' ?>" href="impostazioni.php?tab=tema">Tema</a></li>
</ul>

<?php if ($activeTab === 'generale'): ?>
<div class="card">
    <div class="card-header"><i class="fas fa-business-time me-2"></i>Impostazioni generali</div>
    <div class="card-body">
        <?php if (!$isAdmin): ?>
        <div class="alert alert-info">Solo gli amministratori possono modificare questa sezione.</div>
        <?php endif; ?>
        <form method="post" action="impostazioni.php?tab=generale" class="row g-3">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save_general">
            <div class="col-12">
                <label class="form-label d-block">Giorni lavorativi</label>
                <div class="d-flex flex-wrap gap-3">
                    <?php foreach (settingsDayMap() as $dayKey => $dayLabel): ?>
                    <div class="form-check form-check-inline me-0">
                        <input class="form-check-input" type="checkbox" id="<?= h($dayKey) ?>_attivo" name="<?= h($dayKey) ?>_attivo" value="1" <?= !empty($generalSettings[$dayKey . '_attivo']) ? 'checked' : '' ?> <?= !$isAdmin ? 'disabled' : '' ?>>
                        <label class="form-check-label" for="<?= h($dayKey) ?>_attivo"><?= h($dayLabel) ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Orario mattina</label>
                <div class="input-group">
                    <input type="time" class="form-control" name="matt_inizio" value="<?= h(substr((string)$generalSettings['matt_inizio'], 0, 5)) ?>" <?= !$isAdmin ? 'disabled' : '' ?>>
                    <span class="input-group-text">–</span>
                    <input type="time" class="form-control" name="matt_fine" value="<?= h(substr((string)$generalSettings['matt_fine'], 0, 5)) ?>" <?= !$isAdmin ? 'disabled' : '' ?>>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Orario pomeriggio</label>
                <div class="input-group">
                    <input type="time" class="form-control" name="pom_inizio" value="<?= h(substr((string)$generalSettings['pom_inizio'], 0, 5)) ?>" <?= !$isAdmin ? 'disabled' : '' ?>>
                    <span class="input-group-text">–</span>
                    <input type="time" class="form-control" name="pom_fine" value="<?= h(substr((string)$generalSettings['pom_fine'], 0, 5)) ?>" <?= !$isAdmin ? 'disabled' : '' ?>>
                </div>
            </div>
            <div class="col-md-4">
                <label for="durata_lezione_default" class="form-label">Durata lezione predefinita (minuti)</label>
                <input type="number" class="form-control" id="durata_lezione_default" name="durata_lezione_default" min="15" step="5" value="<?= h((string)$generalSettings['durata_lezione_default']) ?>" <?= !$isAdmin ? 'disabled' : '' ?>>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary" <?= !$isAdmin ? 'disabled' : '' ?>><i class="fas fa-save me-2"></i>Salva impostazioni</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($activeTab === 'app' && $isAdmin): ?>
<div class="card">
    <div class="card-header"><i class="fas fa-sliders-h me-2"></i>Impostazioni applicazione</div>
    <div class="card-body">
        <form method="post" action="impostazioni.php?tab=app" class="row g-3">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save_app">
            <div class="col-md-6">
                <label for="app_name" class="form-label">Nome applicazione</label>
                <input type="text" class="form-control" id="app_name" name="app_name" value="<?= h($appConfig['app_name']) ?>" required>
            </div>
            <div class="col-md-6">
                <label for="app_email" class="form-label">Email applicazione</label>
                <input type="email" class="form-control" id="app_email" name="app_email" value="<?= h($appConfig['app_email']) ?>">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Salva applicazione</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($activeTab === 'profilo'): ?>
<div class="card">
    <div class="card-header"><i class="fas fa-user-cog me-2"></i>Profilo utente</div>
    <div class="card-body">
        <form method="post" action="impostazioni.php?tab=profilo" class="row g-3">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save_profile">
            <div class="col-md-6">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="<?= h($_SESSION['username'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= h($_SESSION['user_email'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
                <label for="current_password" class="form-label">Password attuale</label>
                <input type="password" class="form-control" id="current_password" name="current_password">
            </div>
            <div class="col-md-4">
                <label for="new_password" class="form-label">Nuova password</label>
                <input type="password" class="form-control" id="new_password" name="new_password">
            </div>
            <div class="col-md-4">
                <label for="confirm_password" class="form-label">Conferma password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Salva profilo</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($activeTab === 'tema'): ?>
<div class="row g-4">
    <div class="col-md-6">
        <button type="button" class="card w-100 text-start theme-card <?= $themePreference === 'dark' ? 'border-primary' : '' ?>" data-theme-value="dark">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Dark Theme</h5>
                    <i class="fas fa-moon fa-lg"></i>
                </div>
                <div class="rounded p-3" style="background:#131722;color:#f8f9fa;min-height:140px;">
                    <div class="fw-semibold mb-2">Anteprima sidebar</div>
                    <div class="small opacity-75">Palette scura, contrasto elevato e focus sulle attività giornaliere.</div>
                </div>
            </div>
        </button>
    </div>
    <div class="col-md-6">
        <button type="button" class="card w-100 text-start theme-card <?= $themePreference === 'light' ? 'border-primary' : '' ?>" data-theme-value="light">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Light Theme</h5>
                    <i class="fas fa-sun fa-lg"></i>
                </div>
                <div class="rounded p-3 border" style="background:#ffffff;color:#1f2937;min-height:140px;">
                    <div class="fw-semibold mb-2">Anteprima dashboard</div>
                    <div class="small text-secondary">Interfaccia luminosa e pulita per lavoro diurno e reportistica.</div>
                </div>
            </div>
        </button>
    </div>
</div>
<div class="card mt-4">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h6 class="mb-1">Tema attuale</h6>
            <p class="text-secondary mb-0" id="themeCurrentLabel"><?= h($themePreference === 'dark' ? 'Dark Theme' : 'Light Theme') ?></p>
        </div>
        <button type="button" class="btn btn-outline-primary" id="themeToggleSecondary">
            <i class="fas fa-adjust me-2"></i>Usa toggle rapido
        </button>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.theme-card');
    const label = document.getElementById('themeCurrentLabel');
    function applyThemePreview(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        document.body.setAttribute('data-theme', theme);
        const darkLink = document.getElementById('theme-dark-css');
        const lightLink = document.getElementById('theme-light-css');
        if (darkLink) darkLink.disabled = theme !== 'dark';
        if (lightLink) lightLink.disabled = theme !== 'light';
        const icon = document.getElementById('theme-toggle-icon');
        if (icon) icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        localStorage.setItem('eb_theme', theme);
    }

    function updateSelection(theme) {
        cards.forEach((card) => {
            card.classList.toggle('border-primary', card.dataset.themeValue === theme);
        });
        if (label) {
            label.textContent = theme === 'dark' ? 'Dark Theme' : 'Light Theme';
        }
    }

    cards.forEach((card) => {
        card.addEventListener('click', async () => {
            const theme = card.dataset.themeValue || 'dark';
            const body = new URLSearchParams({ theme, csrf_token: getCsrfToken() });
            try {
                const response = await fetch('impostazioni.php?action=set_theme', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body.toString()
                });
                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Impossibile aggiornare il tema.');
                }
                applyThemePreview(theme);
                updateSelection(theme);
                showToast('Tema aggiornato con successo.', 'success');
            } catch (error) {
                showToast(error.message || 'Errore durante il cambio tema.', 'danger');
            }
        });
    });

    document.getElementById('themeToggleSecondary')?.addEventListener('click', () => {
        document.getElementById('theme-toggle')?.click();
        updateSelection(localStorage.getItem('eb_theme') || 'dark');
    });
});
</script>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php';

<?php
/**
 * index.php – Login page
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'httponly' => true,
        'secure' => isset($_SERVER['HTTPS']), 'samesite' => 'Strict',
    ]);
    session_start();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/encryption.php';

// Already logged in?
if (!empty($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

// DB not set up yet?
if (!Database::schemaExists()) {
    redirect('adminsetup.php');
}

$error = '';
$success = '';

// ── Password reset request ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'reset_request') {
    verifyCsrf();
    $email = sanitizeEmail(post('reset_email'));
    if ($email) {
        try {
            $pdo  = Database::getInstance();
            $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user) {
                $token   = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                $pdo->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE id=?")
                    ->execute([$token, $expires, $user['id']]);
                $resetLink = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']
                           . dirname($_SERVER['SCRIPT_NAME']) . '/index.php?reset=' . $token;
                $body = "<p>Ciao {$user['username']},</p>
                         <p>Per reimpostare la password clicca sul link seguente (valido 1 ora):</p>
                         <p><a href='{$resetLink}'>{$resetLink}</a></p>";
                sendEmail($email, 'Reset Password EasyBooking', $body);
            }
            // always show success to avoid email enumeration
            $success = 'Se l\'email è registrata, riceverai le istruzioni a breve.';
        } catch (PDOException $e) {
            $error = 'Errore durante il reset. Riprova più tardi.';
        }
    } else {
        $error = 'Inserisci un indirizzo email valido.';
    }
}

// ── Password reset form ───────────────────────────────────────
$showResetForm = false;
if (!empty($_GET['reset'])) {
    $token = trim($_GET['reset']);
    try {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE reset_token = ? AND reset_expires > NOW() LIMIT 1");
        $stmt->execute([$token]);
        $resetUser = $stmt->fetch();
        if ($resetUser) {
            $showResetForm = true;
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'reset_password') {
                verifyCsrf();
                $pw  = post('new_password');
                $pw2 = post('confirm_password');
                if (strlen($pw) < 8) {
                    $error = 'La password deve essere di almeno 8 caratteri.';
                } elseif ($pw !== $pw2) {
                    $error = 'Le password non coincidono.';
                } else {
                    $hash = password_hash($pw, PASSWORD_BCRYPT);
                    $pdo->prepare("UPDATE users SET password_hash=?, reset_token=NULL, reset_expires=NULL WHERE id=?")
                        ->execute([$hash, $resetUser['id']]);
                    $success = 'Password aggiornata. Puoi ora effettuare il login.';
                    $showResetForm = false;
                }
            }
        } else {
            $error = 'Link di reset non valido o scaduto.';
        }
    } catch (PDOException $e) {
        $error = 'Errore durante il reset.';
    }
}

// ── Login ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'login') {
    verifyCsrf();
    $identifier = post('username');
    $password   = post('password');
    $remember   = !empty($_POST['remember']);

    if (empty($identifier) || empty($password)) {
        $error = 'Inserisci username/email e password.';
    } else {
        try {
            $pdo  = Database::getInstance();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) LIMIT 1");
            $stmt->execute([$identifier, $identifier]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Regenerate session ID to prevent fixation
                session_regenerate_id(true);
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['username']   = $user['username'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role']  = $user['role'];
                $_SESSION['user_theme'] = $user['theme_preference'];

                // Update last login
                $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

                if ($remember) {
                    $remToken  = bin2hex(random_bytes(32));
                    $remExpiry = time() + (30 * 24 * 3600);
                    $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?")
                        ->execute([$remToken, date('Y-m-d H:i:s', $remExpiry), $user['id']]);
                    setcookie('remember_token', $remToken, $remExpiry, '/', '', isset($_SERVER['HTTPS']), true);
                }

                $redirect = $_SESSION['redirect_after_login'] ?? 'dashboard.php';
                unset($_SESSION['redirect_after_login']);
                redirect($redirect);
            } else {
                $error = 'Credenziali non valide. Riprova.';
            }
        } catch (PDOException $e) {
            $error = 'Errore di connessione al database.';
        }
    }
}

// ── Remember-me auto login ────────────────────────────────────
if (empty($_SESSION['user_id']) && !empty($_COOKIE['remember_token'])) {
    try {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW() LIMIT 1");
        $stmt->execute([$_COOKIE['remember_token']]);
        $user = $stmt->fetch();
        if ($user) {
            session_regenerate_id(true);
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['username']   = $user['username'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role']  = $user['role'];
            $_SESSION['user_theme'] = $user['theme_preference'];
            redirect('dashboard.php');
        }
    } catch (PDOException $e) { /* ignore */ }
}

$theme = $_SESSION['user_theme'] ?? 'dark';

// Cache-busting helper: version = file modification time so browsers/CDN always
// fetch the latest asset after a deployment.
require_once __DIR__ . '/includes/asset-helpers.php';
?>
<!DOCTYPE html>
<html lang="it" data-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrfToken() ?>">
    <title>Login – EasyBooking</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Main CSS must load before theme CSS so theme variables override defaults -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link id="theme-dark-css"  rel="stylesheet" href="assets/css/dark-theme.css"  <?= $theme !== 'dark'  ? 'disabled' : '' ?>>
    <link id="theme-light-css" rel="stylesheet" href="assets/css/light-theme.css" <?= $theme !== 'light' ? 'disabled' : '' ?>>
</head>
<body data-theme="<?= $theme ?>">
<div class="login-page">
    <div class="login-card">
        <div class="login-logo">
            <div class="logo-circle"><i class="fas fa-music"></i></div>
            <h1 class="login-title">EasyBooking</h1>
            <p class="login-sub">Gestione Scuola di Musica</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger mb-3">
            <i class="fas fa-times-circle me-2"></i><?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="alert alert-success mb-3">
            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

        <?php if ($showResetForm): ?>
        <!-- ── Reset Password Form ── -->
        <form method="POST" action="?reset=<?= htmlspecialchars($_GET['reset']) ?>">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="reset_password">
            <div class="mb-3">
                <label class="form-label">Nuova Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="new_password" class="form-control" placeholder="Minimo 8 caratteri" required minlength="8">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Conferma Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Ripeti la password" required minlength="8">
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-key me-2"></i>Aggiorna Password
            </button>
            <div class="text-center mt-3">
                <a href="index.php" class="text-muted" style="font-size:0.85rem">← Torna al Login</a>
            </div>
        </form>

        <?php else: ?>
        <!-- ── Login Form ── -->
        <form method="POST" action="index.php" id="loginForm">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="login">
            <div class="mb-3">
                <label class="form-label">Username o Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="Username o email" required autofocus
                           value="<?= htmlspecialchars(post('username')) ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" id="passwordInput" class="form-control" placeholder="Password" required>
                    <button type="button" class="input-group-text" onclick="togglePwd()" title="Mostra/Nascondi password">
                        <i id="eye-icon" class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label" for="remember" style="font-size:0.85rem">Ricordami</label>
                </div>
                <a href="#" data-bs-toggle="modal" data-bs-target="#resetModal" style="font-size:0.85rem">
                    Password dimenticata?
                </a>
            </div>
            <button type="submit" class="btn btn-primary w-100" id="loginBtn">
                <i class="fas fa-sign-in-alt me-2"></i>Accedi
            </button>
        </form>

        <div class="text-center mt-4">
            <a href="adminsetup.php" class="text-muted" style="font-size:0.78rem">
                <i class="fas fa-tools me-1"></i>Configurazione Amministratore
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── Password Reset Modal ─────────────────────────────────── -->
<div class="modal fade" id="resetModal" tabindex="-1" aria-labelledby="resetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resetModalLabel"><i class="fas fa-key me-2"></i>Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="index.php">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="reset_request">
                <div class="modal-body">
                    <p class="text-muted" style="font-size:0.85rem">Inserisci la tua email. Riceverai un link per reimpostare la password.</p>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="reset_email" class="form-control" placeholder="tua@email.it" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-paper-plane me-1"></i>Invia
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js?v=<?= $getAssetVersion('assets/js/main.js') ?>"></script>
<script>
function togglePwd() {
    const input = document.getElementById('passwordInput');
    const icon  = document.getElementById('eye-icon');
    if (input.type === 'password') {
        input.type = 'text'; icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password'; icon.className = 'fas fa-eye';
    }
}
document.getElementById('loginForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('loginBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Accesso in corso...';
});
</script>
</body>
</html>

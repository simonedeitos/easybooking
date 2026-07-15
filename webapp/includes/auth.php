<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

const EASYBOOKING_LOGIN_URL = 'index.php';

function requireAuth(): void {
    if (empty($_SESSION['user_id'])) {
        // For AJAX / API requests (identified by the X-CSRF-Token or
        // X-Requested-With headers that our front-end attaches) return a
        // JSON 401 so the client can show a helpful error message instead
        // of trying to parse an HTML redirect page as JSON.
        $isAjaxRequest = !empty($_SERVER['HTTP_X_CSRF_TOKEN'])
            || (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        if ($isAjaxRequest) {
            while (ob_get_level() > 0) { ob_end_clean(); }
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Sessione scaduta. Ricarica la pagina e accedi di nuovo.']);
            exit;
        }

        // Store requested URL for post-login redirect
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . EASYBOOKING_LOGIN_URL);
        exit;
    }
    // Refresh session activity
    $_SESSION['last_activity'] = time();
}

function requireAdmin(): void {
    requireAuth();
    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        $isAjaxRequest = !empty($_SERVER['HTTP_X_CSRF_TOKEN'])
            || (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        if ($isAjaxRequest) {
            while (ob_get_level() > 0) { ob_end_clean(); }
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Accesso negato: solo gli amministratori possono eseguire questa operazione.']);
            exit;
        }
        http_response_code(403);
        die('<h1>403 – Accesso negato</h1><p>Solo gli amministratori possono accedere a questa pagina.</p>');
    }
}

function currentUser(): array {
    return [
        'id'       => $_SESSION['user_id']       ?? 0,
        'username' => $_SESSION['username']       ?? '',
        'email'    => $_SESSION['user_email']     ?? '',
        'role'     => $_SESSION['user_role']      ?? 'user',
        'theme'    => $_SESSION['user_theme']     ?? 'dark',
    ];
}

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

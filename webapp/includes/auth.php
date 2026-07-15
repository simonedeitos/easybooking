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

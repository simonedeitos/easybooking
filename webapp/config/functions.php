<?php
// ─── CSRF ──────────────────────────────────────────────────────────────────
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        jsonResponse(['success' => false, 'message' => 'Token CSRF non valido.'], 403);
    }
}

// ─── FLASH MESSAGES ────────────────────────────────────────────────────────
function setFlash(string $type, string $message): void {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function getFlashMessages(): array {
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function renderFlashMessages(): string {
    $html = '';
    foreach (getFlashMessages() as $flash) {
        $type = htmlspecialchars($flash['type']);
        $msg  = htmlspecialchars($flash['message']);
        $icon = match($flash['type']) {
            'success' => 'fa-check-circle',
            'danger'  => 'fa-times-circle',
            'warning' => 'fa-exclamation-triangle',
            default   => 'fa-info-circle',
        };
        $html .= <<<HTML
        <div class="alert alert-{$type} alert-dismissible fade show" role="alert">
            <i class="fas {$icon} me-2"></i>{$msg}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        HTML;
    }
    return $html;
}

// ─── INPUT SANITIZATION ────────────────────────────────────────────────────
function sanitize(mixed $value): string {
    return htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
}

function sanitizeInt(mixed $value): int {
    return (int)filter_var($value, FILTER_SANITIZE_NUMBER_INT);
}

function sanitizeFloat(mixed $value): float {
    return (float)filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
}

function sanitizeEmail(mixed $value): string {
    return filter_var(trim((string)$value), FILTER_SANITIZE_EMAIL);
}

function post(string $key, mixed $default = ''): string {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

function get(string $key, mixed $default = ''): string {
    return isset($_GET[$key]) ? trim($_GET[$key]) : $default;
}

// ─── PAGINATION ────────────────────────────────────────────────────────────
function paginate(int $total, int $perPage, int $currentPage): array {
    $totalPages = max(1, (int)ceil($total / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    return [
        'total'        => $total,
        'per_page'     => $perPage,
        'current_page' => $currentPage,
        'total_pages'  => $totalPages,
        'offset'       => $offset,
        'has_prev'     => $currentPage > 1,
        'has_next'     => $currentPage < $totalPages,
    ];
}

function renderPagination(array $pagination, string $baseUrl = ''): string {
    if ($pagination['total_pages'] <= 1) return '';
    $cp = $pagination['current_page'];
    $tp = $pagination['total_pages'];
    $sep = str_contains($baseUrl, '?') ? '&' : '?';
    $html = '<nav><ul class="pagination justify-content-center flex-wrap">';
    $html .= '<li class="page-item' . ($cp <= 1 ? ' disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . $baseUrl . $sep . 'page=' . max(1, $cp - 1) . '">‹</a></li>';
    $start = max(1, $cp - 2);
    $end   = min($tp, $cp + 2);
    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . $sep . 'page=1">1</a></li>';
        if ($start > 2) $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
    }
    for ($i = $start; $i <= $end; $i++) {
        $html .= '<li class="page-item' . ($i === $cp ? ' active' : '') . '">';
        $html .= '<a class="page-link" href="' . $baseUrl . $sep . 'page=' . $i . '">' . $i . '</a></li>';
    }
    if ($end < $tp) {
        if ($end < $tp - 1) $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . $sep . 'page=' . $tp . '">' . $tp . '</a></li>';
    }
    $html .= '<li class="page-item' . ($cp >= $tp ? ' disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . $baseUrl . $sep . 'page=' . min($tp, $cp + 1) . '">›</a></li>';
    $html .= '</ul></nav>';
    return $html;
}

// ─── DATE HELPERS ──────────────────────────────────────────────────────────
function formatDate(string $date, string $format = 'd/m/Y'): string {
    if (empty($date) || $date === '0000-00-00') return '—';
    try {
        return (new DateTime($date))->format($format);
    } catch (Exception) {
        return $date;
    }
}

function formatDateTime(string $dt, string $format = 'd/m/Y H:i'): string {
    return formatDate($dt, $format);
}

function italianDate(string $date): string {
    if (empty($date)) return '—';
    try {
        $d = new DateTime($date);
        $days   = ['Domenica','Lunedì','Martedì','Mercoledì','Giovedì','Venerdì','Sabato'];
        $months = ['','Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
        return $days[(int)$d->format('w')] . ' ' . $d->format('j') . ' ' . $months[(int)$d->format('n')] . ' ' . $d->format('Y');
    } catch (Exception) {
        return $date;
    }
}

function italianDayName(string $dayKey): string {
    return match($dayKey) {
        'lun' => 'Lunedì', 'mar' => 'Martedì', 'mer' => 'Mercoledì',
        'gio' => 'Giovedì', 'ven' => 'Venerdì', 'sab' => 'Sabato',
        'dom' => 'Domenica', default => $dayKey,
    };
}

// ─── EMAIL HELPER ──────────────────────────────────────────────────────────
function sendEmail(string $to, string $subject, string $body, string $from = ''): bool {
    if (empty($from)) {
        try {
            $pdo  = Database::getInstance();
            $stmt = $pdo->prepare("SELECT `value` FROM system_config WHERE `key` = 'app_email' LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch();
            $from = $row ? $row['value'] : 'noreply@easybooking.local';
        } catch (Exception) {
            $from = 'noreply@easybooking.local';
        }
    }
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: EasyBooking <{$from}>\r\n";
    $headers .= "Reply-To: {$from}\r\n";
    return mail($to, $subject, $body, $headers);
}

// ─── MISC ──────────────────────────────────────────────────────────────────
function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

function isAjax(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function wantsJsonResponse(): bool
{
    $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
    return isAjax() || str_contains($accept, 'application/json');
}

function jsonResponse(array $data, int $status = 200): never {
    // Discard any buffered output (PHP warnings, notices, HTML fragments)
    // so only clean JSON is sent to the client.
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function respondOperationResult(
    bool $success,
    string $message,
    string $redirectUrl,
    int $status = 200,
    array $payload = []
): never {
    if (wantsJsonResponse()) {
        jsonResponse(array_merge(['success' => $success, 'message' => $message], $payload), $status);
    }

    setFlash($success ? 'success' : ($status >= 500 ? 'danger' : 'warning'), $message);
    redirect($redirectUrl);
}

function appName(): string {
    try {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("SELECT `value` FROM system_config WHERE `key` = 'app_name' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ? $row['value'] : 'EasyBooking';
    } catch (Exception) {
        return 'EasyBooking';
    }
}

function activePage(string $page): string {
    $current = basename($_SERVER['PHP_SELF'], '.php');
    return $current === $page ? 'active' : '';
}

function statusBadge(string $stato): string {
    $map = [
        'Programmata'   => 'primary',
        'Svolta'        => 'success',
        'Assente'       => 'danger',
        'Rimandata'     => 'warning',
        'Riprogrammata' => 'info',
    ];
    $color = $map[$stato] ?? 'secondary';
    return '<span class="badge bg-' . $color . '">' . htmlspecialchars($stato) . '</span>';
}

function paymentBadge(string $stato): string {
    $map = [
        'Pagato'         => 'success',
        'Non Pagato'     => 'danger',
        'Parziale'       => 'warning',
        'In Attesa'      => 'secondary',
    ];
    $color = $map[$stato] ?? 'secondary';
    return '<span class="badge bg-' . $color . '">' . htmlspecialchars($stato) . '</span>';
}

function decryptFullName(?string $nome, ?string $cognome, string $fallback = ''): string
{
    $parts = [];
    if ($nome !== null && $nome !== '') {
        $parts[] = decryptField($nome);
    }
    if ($cognome !== null && $cognome !== '') {
        $parts[] = decryptField($cognome);
    }

    $fullName = trim(implode(' ', array_filter($parts, static fn(string $part): bool => $part !== '')));
    return $fullName !== '' ? $fullName : $fallback;
}

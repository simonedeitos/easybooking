<?php
/**
 * API endpoint: get-eventi-calendario.php
 * Returns calendar events in FullCalendar JSON format.
 */
ini_set('log_errors', '1');
ini_set('display_errors', '0');
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/encryption.php';
require_once __DIR__ . '/../includes/auth.php';
requireAuth();

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
header('Pragma: no-cache');
header('Expires: 0');

function calendarRequestDate(?string $value): ?string
{
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return null;
    }

    return date('Y-m-d', $timestamp);
}

function calendarContrastColor(string $hexColor): string
{
    $hex = ltrim($hexColor, '#');
    if (strlen($hex) !== 6) {
        return '#ffffff';
    }

    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

    return $luminance > 0.5 ? '#000000' : '#ffffff';
}

try {
    $pdo = Database::getInstance();

    $teacherId = isset($_GET['insegnante_id']) ? (int)$_GET['insegnante_id'] : 0;

    // FullCalendar sends start/end as ISO strings (e.g. 2025-01-01T00:00:00)
    $startDate = calendarRequestDate($_GET['start'] ?? null);
    $endDate   = calendarRequestDate($_GET['end'] ?? null);

    $sql =
        'SELECT p.id, p.data, p.ora_inizio, p.ora_fine, p.stato, p.strumento, p.note,
                p.cliente_id, p.insegnante_id, p.pacchetto_nome, p.acquisto_id,
                p.tipo_evento, p.strumento_id,
                COALESCE(c.nome, \'\') AS cliente_nome, COALESCE(c.cognome, \'\') AS cliente_cognome,
                COALESCE(i.nome, \'\') AS insegnante_nome, COALESCE(i.cognome, \'\') AS insegnante_cognome,
                COALESCE(s.nome, \'\') AS strumento_nome
         FROM prenotazioni p
         LEFT JOIN clienti c ON c.id = p.cliente_id
         LEFT JOIN insegnanti i ON i.id = p.insegnante_id
         LEFT JOIN strumenti s ON s.id = p.strumento_id
         WHERE 1=1';

    $params = [];
    if ($teacherId > 0) {
        $sql .= ' AND p.insegnante_id = ?';
        $params[] = $teacherId;
    }
    if ($startDate !== null) {
        $sql .= ' AND p.data >= ?';
        $params[] = $startDate;
    }
    if ($endDate !== null) {
        $sql .= ' AND p.data <= ?';
        $params[] = $endDate;
    }
    $sql .= ' ORDER BY p.data ASC, p.ora_inizio ASC, p.id ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $teacherColors = [
        '#7c6af7', '#89dceb', '#a6e3a1', '#f9e2af', '#f38ba8',
        '#cba6f7', '#fab387', '#94e2d5', '#eba0ac', '#b4befe',
    ];
    $unassignedTeacherColor = '#6c757d';
    $colorCount = count($teacherColors);

    $events = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $cliente    = decryptFullName($row['cliente_nome'], $row['cliente_cognome'], 'N/D');
        $insegnante = decryptFullName($row['insegnante_nome'], $row['insegnante_cognome'], 'N/D');
        // Prefer the strumenti table name when a strumento_id FK is set
        $strumento  = trim((string)($row['strumento_nome'] !== '' ? $row['strumento_nome'] : ($row['strumento'] ?? '')));
        $stato      = (string)$row['stato'];
        $tipoEvento = (string)($row['tipo_evento'] ?? 'lezione');
        $title      = $cliente;
        if ($strumento !== '') {
            $title .= ' – ' . $strumento;
        }

        $insegnanteId = (int)$row['insegnante_id'];
        $bgColor = $insegnanteId > 0 ? $teacherColors[abs($insegnanteId) % $colorCount] : $unassignedTeacherColor;
        $textColor = calendarContrastColor($bgColor);

        $events[] = [
            'id'              => (int)$row['id'],
            'title'           => $title,
            'start'           => (string)$row['data'] . 'T' . substr((string)$row['ora_inizio'], 0, 8),
            'end'             => (string)$row['data'] . 'T' . substr((string)$row['ora_fine'], 0, 8),
            'backgroundColor' => $bgColor,
            'borderColor'     => $bgColor,
            'textColor'       => $textColor,
            'extendedProps'   => [
                'stato'         => $stato,
                'cliente'       => $cliente,
                'insegnante'    => $insegnante,
                'insegnante_id' => $insegnanteId,
                'strumento'     => $strumento,
                'strumento_id'  => $row['strumento_id'] !== null ? (int)$row['strumento_id'] : null,
                'tipo_evento'   => $tipoEvento,
                'note'          => (string)($row['note'] ?? ''),
                'cliente_id'    => (int)$row['cliente_id'],
                'pacchetto_nome'=> (string)($row['pacchetto_nome'] ?? ''),
                'acquisto_id'   => $row['acquisto_id'] !== null ? (int)$row['acquisto_id'] : null,
            ],
        ];
    }

    echo json_encode($events, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Exception $e) {
    error_log('Calendar API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Errore nel caricamento degli eventi.'], JSON_UNESCAPED_UNICODE);
}
exit;

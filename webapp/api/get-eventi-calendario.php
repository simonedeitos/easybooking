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
require_once __DIR__ . '/../includes/auth.php';
requireAuth();

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

try {
    $pdo = Database::getInstance();

    $teacherId = isset($_GET['insegnante_id']) ? (int)$_GET['insegnante_id'] : 0;

    // FullCalendar sends start/end as ISO strings (e.g. 2025-01-01T00:00:00)
    $startDate = isset($_GET['start']) ? date('Y-m-d', strtotime($_GET['start'])) : null;
    $endDate   = isset($_GET['end'])   ? date('Y-m-d', strtotime($_GET['end']))   : null;

    $sql =
        'SELECT p.id, p.data, p.ora_inizio, p.ora_fine, p.stato, p.strumento, p.note,
                p.cliente_id, p.insegnante_id, p.pacchetto_nome, p.acquisto_id,
                c.nome AS cliente_nome, c.cognome AS cliente_cognome,
                i.nome AS insegnante_nome, i.cognome AS insegnante_cognome
         FROM prenotazioni p
         INNER JOIN clienti c ON c.id = p.cliente_id
         INNER JOIN insegnanti i ON i.id = p.insegnante_id
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

    $statusColors = [
        'Programmata'   => '#7c6af7',
        'Svolta'        => '#a6e3a1',
        'Assente'       => '#f38ba8',
        'Rimandata'     => '#f9e2af',
        'Riprogrammata' => '#89dceb',
    ];

    $events = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $cliente    = trim((string)$row['cliente_nome'] . ' ' . (string)$row['cliente_cognome']);
        $insegnante = trim((string)$row['insegnante_nome'] . ' ' . (string)$row['insegnante_cognome']);
        $strumento  = trim((string)($row['strumento'] ?? ''));
        $stato      = (string)$row['stato'];
        $title      = $cliente;
        if ($strumento !== '') {
            $title .= ' – ' . $strumento;
        }

        $bgColor = $statusColors[$stato] ?? '#7c6af7';

        $events[] = [
            'id'              => (int)$row['id'],
            'title'           => $title,
            'start'           => (string)$row['data'] . 'T' . substr((string)$row['ora_inizio'], 0, 8),
            'end'             => (string)$row['data'] . 'T' . substr((string)$row['ora_fine'], 0, 8),
            'backgroundColor' => $bgColor,
            'borderColor'     => $bgColor,
            'extendedProps'   => [
                'stato'         => $stato,
                'cliente'       => $cliente,
                'insegnante'    => $insegnante,
                'insegnante_id' => (int)$row['insegnante_id'],
                'strumento'     => $strumento,
                'note'          => (string)($row['note'] ?? ''),
                'cliente_id'    => (int)$row['cliente_id'],
                'pacchetto_nome'=> (string)($row['pacchetto_nome'] ?? ''),
                'acquisto_id'   => $row['acquisto_id'] !== null ? (int)$row['acquisto_id'] : null,
            ],
        ];
    }

    echo json_encode($events, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Errore nel caricamento degli eventi.'], JSON_UNESCAPED_UNICODE);
}
exit;

<?php
// api/pianificazione-api.php – REST API for lesson scheduling (pianificazione lezioni)

declare(strict_types=1);

ob_start();
ini_set('log_errors', '1');
ini_set('display_errors', '0');
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    ob_end_clean();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito.']);
    exit;
}

$pdo    = Database::getInstance();
$action = post('action');

/**
 * Terminates with a JSON response.
 * @param array<string,mixed> $data
 */
function schedApiResponse(array $data, int $status = 200): never
{
    ob_end_clean();
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Terminates with a JSON error response. */
function schedApiError(string $message, int $status = 400): never
{
    schedApiResponse(['success' => false, 'message' => $message], $status);
}

// ── CSRF verification ─────────────────────────────────────────────────────────
verifyCsrf();

// ── Route dispatcher ──────────────────────────────────────────────────────────
switch ($action) {

    // ── Preview: generate dates & check conflicts (no DB writes) ─────────────
    case 'preview_schedule':
        $params = parseScheduleParams();
        $slots  = buildSlots($params);

        $result = [];
        foreach ($slots as $slot) {
            $oraFine  = calculateEndTime($slot['ora'], $params['durata_minuti']);
            $conflitto = false;
            if ($params['insegnante_id'] > 0) {
                $conflitto = hasConflict($pdo, $params['insegnante_id'], $slot['data'], $slot['ora'], $oraFine);
            }
            $result[] = [
                'data'      => $slot['data'],
                'data_it'   => italianDate($slot['data']),
                'giorno'    => italianDayNumber($slot['giorno_settimana']),
                'ora_inizio' => $slot['ora'],
                'ora_fine'  => $oraFine,
                'conflitto' => $conflitto,
            ];
        }

        schedApiResponse([
            'success' => true,
            'lezioni' => $result,
            'totale'  => count($result),
        ]);

    // ── Confirm: insert lessons & update acquisto ─────────────────────────────
    case 'confirm_schedule':
        $params = parseScheduleParams();

        // If "piu_libero" was requested, resolve the best teacher now
        if ($params['insegnante_id'] === 0) {
            $candidati = getInsegnantiByStrumento($pdo, $params['strumento']);
            if (empty($candidati)) {
                schedApiError('Nessun insegnante trovato per lo strumento indicato.');
            }
            $slots = buildSlots($params);
            $bestId = findBestTeacher($pdo, $candidati, $slots, $params['durata_minuti']);
            if ($bestId === 0) {
                schedApiError('Nessun insegnante risulta disponibile per gli slot generati. Scegli un insegnante manualmente o modifica i parametri.');
            }
            $params['insegnante_id'] = $bestId;
        }

        // Verify acquisto exists and belongs to a real client
        $stmt = $pdo->prepare(
            'SELECT a.id, a.cliente_id, a.pianificato, a.numero_lezioni,
                    pk.nome AS pacchetto_nome, pk.strumento AS pacchetto_strumento
             FROM acquisti a
             LEFT JOIN pacchetti pk ON pk.id = a.pacchetto_id
             WHERE a.id = ? LIMIT 1'
        );
        $stmt->execute([$params['acquisto_id']]);
        $acquisto = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$acquisto) {
            schedApiError('Acquisto non trovato.');
        }

        $slots       = buildSlots($params);
        $inserted    = 0;
        $conflitti   = 0;
        $slotsDetail = [];

        // Use the pacchetto_nome from the acquisto for booking records
        $pacchettoNome = $acquisto['pacchetto_nome'] ?? null;
        // Use provided strumento or fall back to package strumento
        $strumento = $params['strumento'] !== ''
            ? $params['strumento']
            : ($acquisto['pacchetto_strumento'] ?? null);

        try {
            $pdo->beginTransaction();

            $insertStmt = $pdo->prepare(
                'INSERT INTO prenotazioni
                    (data, ora_inizio, ora_fine, cliente_id, insegnante_id,
                     strumento, stato, pacchetto_nome, acquisto_id)
                 VALUES (?, ?, ?, ?, ?, ?, \'Programmata\', ?, ?)'
            );

            foreach ($slots as $slot) {
                $oraFine  = calculateEndTime($slot['ora'], $params['durata_minuti']);
                $conflict = hasConflict($pdo, $params['insegnante_id'], $slot['data'], $slot['ora'], $oraFine);

                if ($conflict) {
                    $conflitti++;
                    $slotsDetail[] = [
                        'data'      => $slot['data'],
                        'ora'       => $slot['ora'],
                        'inserita'  => false,
                        'conflitto' => true,
                    ];
                    continue;
                }

                $insertStmt->execute([
                    $slot['data'],
                    $slot['ora'],
                    $oraFine,
                    (int)$acquisto['cliente_id'],
                    $params['insegnante_id'],
                    $strumento,
                    $pacchettoNome,
                    $params['acquisto_id'],
                ]);
                $inserted++;
                $slotsDetail[] = [
                    'data'      => $slot['data'],
                    'ora'       => $slot['ora'],
                    'inserita'  => true,
                    'conflitto' => false,
                ];
            }

            // Mark acquisto as pianificato ONLY when ALL requested lessons were created
            // without conflicts; otherwise leave pianificato = 0 and inform the user.
            if ($conflitti === 0 && $inserted === count($slots)) {
                $pdo->prepare('UPDATE acquisti SET pianificato = 1 WHERE id = ?')
                    ->execute([$params['acquisto_id']]);
                $pianificatoAggiornato = true;
            } else {
                $pianificatoAggiornato = false;
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[pianificazione-api.php] ' . get_class($e) . ': ' . $e->getMessage());
            schedApiError('Errore durante il salvataggio delle lezioni. Riprova.', 500);
        }

        $message = $inserted . ' lezione/i pianificate con successo.';
        if ($conflitti > 0) {
            $message .= ' ' . $conflitti . ' lezione/i saltate per conflitti d\'orario.';
            if (!$pianificatoAggiornato) {
                $message .= ' L\'acquisto NON è stato marcato come pianificato perché alcune lezioni non sono state create.';
            }
        }

        schedApiResponse([
            'success'              => $inserted > 0,
            'inserted'             => $inserted,
            'conflitti'            => $conflitti,
            'pianificato_aggiornato' => $pianificatoAggiornato,
            'message'              => $message,
            'dettaglio'            => $slotsDetail,
        ]);

    default:
        schedApiError('Azione non riconosciuta.');
}

// ── Helper functions ───────────────────────────────────────────────────────────

/**
 * Parses and validates scheduling parameters from POST.
 * @return array<string,mixed>
 */
function parseScheduleParams(): array
{
    $acquistoId   = sanitizeInt(post('acquisto_id'));
    $insegnanteId = post('insegnante_id'); // may be 'piu_libero' or a numeric string
    $strumento    = trim(post('strumento'));
    $frequenza    = trim(post('frequenza'));
    $dataInizio   = trim(post('data_inizio'));
    $durataMinuti = max(1, sanitizeInt(post('durata_minuti')));
    $numeroLezioni = max(1, sanitizeInt(post('numero_lezioni')));

    // Validate required fields
    if ($acquistoId <= 0) {
        schedApiError('ID acquisto non valido.');
    }
    if (!in_array($frequenza, ['Settimanale', 'Bisettimanale', 'Mensile', 'MultiGiornoSettimanale'], true)) {
        schedApiError('Frequenza non valida.');
    }

    // Parse data_inizio
    $dt = DateTime::createFromFormat('Y-m-d', $dataInizio);
    if ($dt === false || $dt->format('Y-m-d') !== $dataInizio) {
        schedApiError('Data inizio non valida (usa formato YYYY-MM-DD).');
    }

    // Resolve insegnante_id
    if ($insegnanteId === 'piu_libero' || $insegnanteId === '') {
        $resolvedInsegnanteId = 0; // will be resolved later in confirm or reported in preview
    } else {
        $resolvedInsegnanteId = max(0, (int)$insegnanteId);
    }

    // Parse giorni (array of ISO day numbers 1=Mon...7=Sun)
    $giorniRaw = post('giorni');
    $giorni = [];
    if ($giorniRaw !== '') {
        $decoded = json_decode($giorniRaw, true);
        if (is_array($decoded)) {
            foreach ($decoded as $g) {
                $g = (int)$g;
                if ($g >= 1 && $g <= 7) {
                    $giorni[] = $g;
                }
            }
        }
    }
    if (empty($giorni)) {
        // Fall back to the day of data_inizio
        $giorni = [(int)$dt->format('N')];
    }

    // Parse orari per giorno (JSON object: {"1":"HH:MM","3":"HH:MM"} or empty)
    $orariPerGiornoRaw = post('orari_per_giorno');
    $orariPerGiorno = [];
    if ($orariPerGiornoRaw !== '') {
        $decoded = json_decode($orariPerGiornoRaw, true);
        if (is_array($decoded)) {
            foreach ($decoded as $dayKey => $ora) {
                $dayKey = (int)$dayKey;
                if ($dayKey >= 1 && $dayKey <= 7 && preg_match('/^\d{2}:\d{2}$/', (string)$ora)) {
                    $orariPerGiorno[$dayKey] = (string)$ora;
                }
            }
        }
    }

    // Single ora_inizio fallback
    $oraInizio = trim(post('ora_inizio'));
    if (!preg_match('/^\d{2}:\d{2}$/', $oraInizio)) {
        schedApiError('Orario inizio non valido (usa formato HH:MM).');
    }

    return [
        'acquisto_id'    => $acquistoId,
        'insegnante_id'  => $resolvedInsegnanteId,
        'strumento'      => $strumento,
        'frequenza'      => $frequenza,
        'giorni'         => $giorni,
        'data_inizio'    => $dt,
        'durata_minuti'  => $durataMinuti,
        'numero_lezioni' => $numeroLezioni,
        'ora_inizio'     => $oraInizio,
        'orari_per_giorno' => $orariPerGiorno,
    ];
}

/**
 * Builds an ordered list of lesson slots (date + time) from scheduling parameters.
 * @param array<string,mixed> $params From parseScheduleParams()
 * @return array<array{data: string, ora: string, giorno_settimana: int}>
 */
function buildSlots(array $params): array
{
    $dates = generateLessonDates(
        $params['frequenza'],
        $params['giorni'],
        $params['data_inizio'],
        $params['numero_lezioni']
    );

    $slots = [];
    foreach ($dates as $dateEntry) {
        // Resolve the time for this slot:
        // If per-day orari are provided and this day has a specific time, use it;
        // otherwise fall back to the single ora_inizio.
        $dayNum = $dateEntry['giorno_settimana'];
        $ora    = $params['orari_per_giorno'][$dayNum] ?? $params['ora_inizio'];
        $slots[] = [
            'data'            => $dateEntry['data'],
            'ora'             => $ora,
            'giorno_settimana' => $dayNum,
        ];
    }
    return $slots;
}

/**
 * Generates lesson dates based on scheduling frequency.
 *
 * @param string $frequenza   One of: Settimanale | Bisettimanale | Mensile | MultiGiornoSettimanale
 * @param int[]  $giorniSettimana  ISO day numbers (1=Mon … 7=Sun).
 *                            For multi-day: multiple values; for others: single element.
 * @param DateTime $dataInizio Start date (not modified, cloned internally)
 * @param int    $numeroLezioni Number of lessons to generate
 * @return array<array{data: string, giorno_settimana: int}>
 */
function generateLessonDates(
    string $frequenza,
    array $giorniSettimana,
    DateTime $dataInizio,
    int $numeroLezioni
): array {
    $dates = [];

    if ($frequenza === 'MultiGiornoSettimanale') {
        // Sort day numbers ascending (Mon=1 first) to emit lessons in chronological order
        sort($giorniSettimana);

        // Find the Monday of the week that contains $dataInizio
        $startClone  = clone $dataInizio;
        $dayOfWeek   = (int)$startClone->format('N'); // 1=Mon … 7=Sun
        $weekMonday  = clone $startClone;
        if ($dayOfWeek > 1) {
            $weekMonday->modify('-' . ($dayOfWeek - 1) . ' days');
        }
        $currentMonday = clone $weekMonday;

        // Advance week-by-week, emitting one slot per selected day per week,
        // skipping dates before $dataInizio (first partial week may be trimmed).
        while (count($dates) < $numeroLezioni) {
            foreach ($giorniSettimana as $dayNum) {
                if (count($dates) >= $numeroLezioni) {
                    break;
                }
                $lessonDate = clone $currentMonday;
                $lessonDate->modify('+' . ($dayNum - 1) . ' days');

                // Skip dates that fall before the requested start date
                if ($lessonDate < $dataInizio) {
                    continue;
                }

                $dates[] = [
                    'data'            => $lessonDate->format('Y-m-d'),
                    'giorno_settimana' => (int)$lessonDate->format('N'),
                ];
            }
            $currentMonday->modify('+7 days');
        }
    } else {
        $current = clone $dataInizio;

        for ($i = 0; $i < $numeroLezioni; $i++) {
            if ($i > 0) {
                switch ($frequenza) {
                    case 'Settimanale':
                        // +7 days per iteration: one lesson per week on the same weekday
                        $current->modify('+7 days');
                        break;
                    case 'Bisettimanale':
                        // +14 days per iteration: ONE lesson every two weeks on the same weekday.
                        // "Bisettimanale" here means fortnightly, NOT twice a week.
                        // For 2+ lessons per week on different days, use MultiGiornoSettimanale.
                        $current->modify('+14 days');
                        break;
                    case 'Mensile':
                        // +1 month using PHP's DateTime::modify('+1 month').
                        // PHP overflows short months the same way most calendar libraries do:
                        // e.g. Jan 31 + 1 month → Mar 2 (or Mar 3 on leap years) because
                        // February doesn't have 31 days.  No additional normalization is applied.
                        $current->modify('+1 month');
                        break;
                    default:
                        $current->modify('+7 days');
                }
            }
            $dates[] = [
                'data'            => $current->format('Y-m-d'),
                'giorno_settimana' => (int)$current->format('N'),
            ];
        }
    }

    return $dates;
}

/**
 * Returns Italian weekday name for ISO day number (1=Mon … 7=Sun).
 */
function italianDayNumber(int $dayNum): string
{
    return match($dayNum) {
        1 => 'Lunedì',
        2 => 'Martedì',
        3 => 'Mercoledì',
        4 => 'Giovedì',
        5 => 'Venerdì',
        6 => 'Sabato',
        7 => 'Domenica',
        default => '',
    };
}

/**
 * Calculates lesson end time.
 * Handles midnight overflow: e.g. 23:30 + 60 min → 00:30 (next day representation).
 *
 * @param string $oraInizio "HH:MM"
 * @param int    $durataMinuti Duration in minutes
 * @return string "HH:MM"
 */
function calculateEndTime(string $oraInizio, int $durataMinuti): string
{
    [$h, $m]      = array_map('intval', explode(':', $oraInizio));
    $totalMinutes = $h * 60 + $m + $durataMinuti;
    // Wrap around midnight (modulo 24h) — consistent with exe normalization
    $endH = intdiv($totalMinutes, 60) % 24;
    $endM = $totalMinutes % 60;
    return sprintf('%02d:%02d', $endH, $endM);
}

/**
 * Returns true if there is a scheduling conflict for the given teacher/slot.
 * Overlap: existing.start < new.end AND existing.end > new.start
 * Excludes 'Rimandata' bookings (rescheduled lessons are considered vacated slots).
 *
 * @param string $oraInizio "HH:MM"
 * @param string $oraFine   "HH:MM"
 */
function hasConflict(PDO $pdo, int $insegnanteId, string $data, string $oraInizio, string $oraFine): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM prenotazioni
         WHERE insegnante_id = ?
           AND data = ?
           AND ora_inizio < ?
           AND ora_fine > ?
           AND stato NOT IN (\'Rimandata\')'
    );
    $stmt->execute([$insegnanteId, $data, $oraFine, $oraInizio]);
    return (int)$stmt->fetchColumn() > 0;
}

/**
 * Returns IDs of insegnanti who teach the given strumento.
 * If $strumento is empty, returns all insegnante IDs.
 *
 * @return int[]
 */
function getInsegnantiByStrumento(PDO $pdo, string $strumento): array
{
    if ($strumento === '') {
        $stmt = $pdo->query('SELECT id FROM insegnanti ORDER BY id');
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    $stmt = $pdo->prepare(
        'SELECT DISTINCT i.id
         FROM insegnanti i
         JOIN insegnanti_strumenti ins ON ins.insegnante_id = i.id
         JOIN strumenti s ON s.id = ins.strumento_id
         WHERE s.nome = ?
         ORDER BY i.id'
    );
    $stmt->execute([$strumento]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

/**
 * Finds the insegnante with the most free slots among candidates.
 * Equivalent to TrovaInsegnanteMenoOccupato in the exe.
 *
 * Returns 0 if no teacher has any free slot (caller should surface an error instead
 * of silently falling back to a random teacher — safer than the exe behaviour).
 *
 * @param int[] $insegnantiIds Candidate teacher IDs
 * @param array<array{data:string,ora:string,giorno_settimana:int}> $slots Lesson slots
 */
function findBestTeacher(PDO $pdo, array $insegnantiIds, array $slots, int $durataMinuti): int
{
    if (empty($insegnantiIds)) {
        return 0;
    }

    $best         = 0;
    $bestFreeSlots = -1;

    foreach ($insegnantiIds as $id) {
        $freeSlots = 0;
        foreach ($slots as $slot) {
            $oraFine = calculateEndTime($slot['ora'], $durataMinuti);
            if (!hasConflict($pdo, $id, $slot['data'], $slot['ora'], $oraFine)) {
                $freeSlots++;
            }
        }
        if ($freeSlots > $bestFreeSlots) {
            $bestFreeSlots = $freeSlots;
            $best          = $id;
        }
    }

    // Return 0 (no suitable teacher) if no teacher has even one free slot
    return ($bestFreeSlots > 0) ? $best : 0;
}

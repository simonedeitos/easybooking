<?php

function emailTemplateDirectory(): string
{
    return dirname(__DIR__) . '/templates/email';
}

function emailTemplateValue(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function emailTemplateRead(string $templateName): string
{
    $fileName = str_ends_with($templateName, '.html') ? $templateName : ($templateName . '.html');
    $path = emailTemplateDirectory() . '/' . ltrim($fileName, '/');
    if (!is_file($path)) {
        throw new RuntimeException('Template email non trovato: ' . $fileName);
    }

    $content = file_get_contents($path);
    if ($content === false) {
        throw new RuntimeException('Impossibile leggere il template email: ' . $fileName);
    }

    return $content;
}

function emailTemplateRender(string $template, array $replacements): string
{
    $tokens = [];
    foreach ($replacements as $key => $value) {
        $tokens['{{' . $key . '}}'] = (string)$value;
    }
    return strtr($template, $tokens);
}

function emailTemplateSummaryCards(array $cards): string
{
    if ($cards === []) {
        return '';
    }

    $html = '<div class="summary-grid">';
    foreach ($cards as $card) {
        $label = emailTemplateValue($card['label'] ?? '');
        $value = emailTemplateValue($card['value'] ?? '');
        $detail = trim((string)($card['detail'] ?? ''));
        $html .= '<div class="summary-card">'
            . '<span class="summary-label">' . $label . '</span>'
            . '<span class="summary-value">' . $value . '</span>';
        if ($detail !== '') {
            $html .= '<span class="summary-detail">' . emailTemplateValue($detail) . '</span>';
        }
        $html .= '</div>';
    }
    $html .= '</div>';

    return $html;
}

function emailTemplateTable(array $columns, array $rows, string $emptyMessage = 'Nessun dato disponibile.'): string
{
    if ($rows === []) {
        return '<div class="empty-state">' . emailTemplateValue($emptyMessage) . '</div>';
    }

    $html = '<table class="data-table"><thead><tr>';
    foreach ($columns as $column) {
        $align = $column['align'] ?? 'left';
        $html .= '<th class="align-' . emailTemplateValue($align) . '">'
            . emailTemplateValue($column['label'] ?? '')
            . '</th>';
    }
    $html .= '</tr></thead><tbody>';

    foreach ($rows as $row) {
        $html .= '<tr>';
        foreach ($columns as $column) {
            $key = (string)($column['key'] ?? '');
            $align = $column['align'] ?? 'left';
            $html .= '<td class="align-' . emailTemplateValue($align) . '">'
                . emailTemplateValue($row[$key] ?? '')
                . '</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';
    return $html;
}

function emailTemplateList(array $items, string $emptyMessage = 'Nessun elemento disponibile.'): string
{
    if ($items === []) {
        return '<div class="empty-state">' . emailTemplateValue($emptyMessage) . '</div>';
    }

    $html = '<ul class="bullet-list">';
    foreach ($items as $item) {
        $html .= '<li>' . emailTemplateValue($item) . '</li>';
    }
    $html .= '</ul>';

    return $html;
}

function emailTemplateTextFromHtml(string $html): string
{
    $search = [
        '</p>', '<br>', '<br/>', '<br />', '</div>', '</li>', '</tr>', '</table>',
        '<li>', '<th>', '</th>', '<td>', '</td>',
    ];
    $replace = [
        "\n\n", "\n", "\n", "\n", "\n", "\n", "\n", "\n",
        '• ', '', "\t", '', "\t",
    ];

    $text = str_ireplace($search, $replace, $html);
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    $text = preg_replace("/[ \t]+\n/", "\n", $text) ?? $text;
    $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

    return trim($text);
}

function emailTemplateAsciiBars(array $series, int $width = 16): string
{
    if ($series === []) {
        return '';
    }

    $max = 0.0;
    foreach ($series as $value) {
        $max = max($max, (float)$value);
    }
    if ($max <= 0) {
        return '';
    }

    $lines = [];
    foreach ($series as $label => $value) {
        $value = (float)$value;
        $bars = (int)round(($value / $max) * $width);
        $lines[] = str_pad(mb_substr((string)$label, 0, 12), 12) . ' | '
            . str_repeat('█', max(0, $bars))
            . ' ' . rtrim(rtrim(number_format($value, 2, ',', ''), '0'), ',');
    }

    return implode("\n", $lines);
}

function emailTemplateBuilder(string $templateName, array $data = []): array
{
    $appName = trim((string)($data['app_name'] ?? (function_exists('appName') ? appName() : 'EasyBooking')));
    $title = trim((string)($data['title'] ?? 'Notifica EasyBooking'));
    $generatedAt = trim((string)($data['generated_at'] ?? date('d/m/Y H:i')));
    $footerNote = trim((string)($data['footer_note'] ?? 'Questo messaggio è stato generato automaticamente.'));

    $content = emailTemplateRender(emailTemplateRead($templateName), [
        'eyebrow' => emailTemplateValue($data['eyebrow'] ?? 'Notifica'),
        'title' => emailTemplateValue($title),
        'intro_html' => (string)($data['intro_html'] ?? ''),
        'summary_cards' => (string)($data['summary_cards_html'] ?? ''),
        'content_html' => (string)($data['content_html'] ?? ''),
        'secondary_html' => (string)($data['secondary_html'] ?? ''),
    ]);

    $html = emailTemplateRender(emailTemplateRead('base.html'), [
        'app_name' => emailTemplateValue($appName),
        'email_title' => emailTemplateValue($title),
        'preheader' => emailTemplateValue($data['preheader'] ?? $title),
        'content' => $content,
        'generated_at' => emailTemplateValue($generatedAt),
        'footer_note' => emailTemplateValue($footerNote),
    ]);

    return [
        'html' => $html,
        'text' => trim((string)($data['text_body'] ?? emailTemplateTextFromHtml($content))),
    ];
}

function notificationPreviewTypes(): array
{
    return [
        'reminder_lezioni' => 'Promemoria lezioni',
        'report_settimanale' => 'Report settimanale',
        'report_mensile' => 'Report mensile',
        'avviso_scadenza' => 'Avviso scadenza',
        'avviso_non_confermata' => 'Avviso non confermata',
    ];
}

function notificationEmailDateLabel(string $date): string
{
    try {
        return italianDate((new DateTimeImmutable($date))->format('Y-m-d'));
    } catch (Throwable) {
        return $date;
    }
}

function notificationEmailTimeRange(string $start, string $end): string
{
    return substr($start, 0, 5) . '–' . substr($end, 0, 5);
}

function notificationEmailClientName(array $row, string $firstKey = 'cliente_nome', string $lastKey = 'cliente_cognome', string $fallback = 'Cliente'): string
{
    $first = (string)($row[$firstKey] ?? '');
    $last = (string)($row[$lastKey] ?? '');
    if (function_exists('decryptFullName')) {
        $fullName = decryptFullName($first, $last, $fallback);
        return $fullName !== '' ? $fullName : $fallback;
    }

    $fullName = trim($first . ' ' . $last);
    return $fullName !== '' ? $fullName : $fallback;
}

function buildReminderLezioniEmail(PDO $pdo, array $user, array $config, DateTimeImmutable $now): array
{
    $giorniFuturi = max(1, (int)($config['reminder_lezioni_giorni_futuri'] ?? 7));
    $oggi = $now->format('Y-m-d');
    $dataLimite = $now->modify('+' . $giorniFuturi . ' days')->format('Y-m-d');

    $stmt = $pdo->prepare(
        "SELECT p.data, p.ora_inizio, p.ora_fine, p.strumento, c.nome AS cliente_nome, c.cognome AS cliente_cognome
         FROM prenotazioni p
         INNER JOIN clienti c ON c.id = p.cliente_id
         WHERE p.data BETWEEN ? AND ?
           AND p.stato = 'Programmata'
         ORDER BY p.data ASC, p.ora_inizio ASC"
    );
    $stmt->execute([$oggi, $dataLimite]);
    $lezioni = $stmt->fetchAll();

    $rows = [];
    foreach ($lezioni as $lezione) {
        $rows[] = [
            'data' => notificationEmailDateLabel((string)$lezione['data']),
            'orario' => notificationEmailTimeRange((string)$lezione['ora_inizio'], (string)$lezione['ora_fine']),
            'cliente' => notificationEmailClientName($lezione),
            'strumento' => (string)($lezione['strumento'] ?: '—'),
        ];
    }

    $intro = $rows === []
        ? '<p>Nessuna lezione programmata nei prossimi ' . $giorniFuturi . ' giorni.</p>'
        : '<p>Ecco le lezioni programmate nei prossimi <strong>' . $giorniFuturi . ' giorni</strong>.</p>';

    $payload = buildHtmlEmail('reminder-lezioni', [
        'eyebrow' => 'Promemoria lezioni',
        'title' => 'Promemoria lezioni',
        'preheader' => 'Lezioni programmate dal ' . formatDate($oggi) . ' al ' . formatDate($dataLimite),
        'intro_html' => $intro,
        'summary_cards_html' => emailTemplateSummaryCards([
            ['label' => 'Lezioni', 'value' => count($rows)],
            ['label' => 'Finestra', 'value' => $giorniFuturi . ' giorni'],
        ]),
        'content_html' => emailTemplateTable(
            [
                ['key' => 'data', 'label' => 'Data'],
                ['key' => 'orario', 'label' => 'Orario'],
                ['key' => 'cliente', 'label' => 'Cliente'],
                ['key' => 'strumento', 'label' => 'Strumento'],
            ],
            $rows,
            'Nessuna lezione da segnalare.'
        ),
        'text_body' => trim("Promemoria lezioni\nPeriodo: " . formatDate($oggi) . ' - ' . formatDate($dataLimite) . "\n"
            . ($rows === [] ? "\nNessuna lezione da segnalare." : "\n" . implode("\n", array_map(
                static fn(array $row): string => '- ' . implode(' | ', $row),
                $rows
            )))),
    ]);

    return [
        'type' => 'reminder_lezioni',
        'subject' => 'Promemoria lezioni – EasyBooking',
        'summary' => count($rows) . ' lezioni tra ' . formatDate($oggi) . ' e ' . formatDate($dataLimite),
        'should_send' => $rows !== [],
        'item_count' => count($rows),
        'html' => $payload['html'],
        'text' => $payload['text'],
    ];
}

function buildReportMetrics(PDO $pdo, string $tipo, DateTimeImmutable $start, DateTimeImmutable $end): array
{
    $from = $start->format('Y-m-d');
    $to = $end->format('Y-m-d');

    if ($tipo === 'clienti') {
        $summaryStmt = $pdo->prepare(
            "SELECT
                (SELECT COUNT(*) FROM clienti WHERE DATE(created_at) BETWEEN ? AND ?) AS nuovi_clienti,
                (SELECT COUNT(DISTINCT cliente_id) FROM prenotazioni WHERE data BETWEEN ? AND ?) AS clienti_attivi,
                (SELECT COUNT(*) FROM clienti) AS clienti_totali"
        );
        $summaryStmt->execute([$from, $to, $from, $to]);
        $summary = $summaryStmt->fetch() ?: ['nuovi_clienti' => 0, 'clienti_attivi' => 0, 'clienti_totali' => 0];

        $rowsStmt = $pdo->prepare(
            "SELECT c.nome AS cliente_nome, c.cognome AS cliente_cognome,
                    COALESCE(pl.lezioni, 0) AS lezioni,
                    COALESCE(ai.incassi, 0) AS incassi
             FROM clienti c
             LEFT JOIN (
                SELECT cliente_id, COUNT(*) AS lezioni
                FROM prenotazioni
                WHERE data BETWEEN ? AND ?
                GROUP BY cliente_id
             ) pl ON pl.cliente_id = c.id
             LEFT JOIN (
                SELECT cliente_id, SUM(importo_pagato) AS incassi
                FROM acquisti
                WHERE data_acquisto BETWEEN ? AND ?
                GROUP BY cliente_id
             ) ai ON ai.cliente_id = c.id
             WHERE COALESCE(pl.lezioni, 0) > 0 OR COALESCE(ai.incassi, 0) > 0
             ORDER BY lezioni DESC, incassi DESC, c.cognome ASC, c.nome ASC
             LIMIT 12"
        );
        $rowsStmt->execute([$from, $to, $from, $to]);
        $rows = [];
        foreach ($rowsStmt->fetchAll() as $row) {
            $rows[] = [
                'cliente' => notificationEmailClientName($row),
                'lezioni' => (int)$row['lezioni'],
                'incassi' => '€ ' . number_format((float)$row['incassi'], 2, ',', '.'),
            ];
        }

        return [
            'cards' => [
                ['label' => 'Clienti attivi', 'value' => (int)$summary['clienti_attivi']],
                ['label' => 'Nuovi clienti', 'value' => (int)$summary['nuovi_clienti']],
                ['label' => 'Archivio clienti', 'value' => (int)$summary['clienti_totali']],
            ],
            'tables' => [
                [
                    'title' => 'Clienti coinvolti',
                    'html' => emailTemplateTable(
                        [
                            ['key' => 'cliente', 'label' => 'Cliente'],
                            ['key' => 'lezioni', 'label' => 'Lezioni', 'align' => 'right'],
                            ['key' => 'incassi', 'label' => 'Incassi', 'align' => 'right'],
                        ],
                        $rows,
                        'Nessun cliente coinvolto nel periodo.'
                    ),
                ],
            ],
            'text_chart' => '',
            'item_count' => count($rows),
        ];
    }

    if ($tipo === 'incassi') {
        $summaryStmt = $pdo->prepare(
            "SELECT COUNT(*) AS pagamenti, COALESCE(SUM(importo_pagato), 0) AS totale, COALESCE(AVG(importo_pagato), 0) AS medio
             FROM acquisti
             WHERE stato_pagamento IN ('Pagato', 'Parziale')
               AND data_acquisto BETWEEN ? AND ?"
        );
        $summaryStmt->execute([$from, $to]);
        $summary = $summaryStmt->fetch() ?: ['pagamenti' => 0, 'totale' => 0, 'medio' => 0];

        $rowsStmt = $pdo->prepare(
            "SELECT a.data_acquisto, a.importo_pagato, a.stato_pagamento, a.numero_fattura,
                    c.nome AS cliente_nome, c.cognome AS cliente_cognome
             FROM acquisti a
             INNER JOIN clienti c ON c.id = a.cliente_id
             WHERE a.stato_pagamento IN ('Pagato', 'Parziale')
               AND a.data_acquisto BETWEEN ? AND ?
             ORDER BY a.data_acquisto ASC, a.id ASC"
        );
        $rowsStmt->execute([$from, $to]);
        $rows = [];
        $chartSeries = [];
        foreach ($rowsStmt->fetchAll() as $row) {
            $label = formatDate((string)$row['data_acquisto']);
            $chartSeries[$label] = ($chartSeries[$label] ?? 0) + (float)$row['importo_pagato'];
            $rows[] = [
                'data' => $label,
                'cliente' => notificationEmailClientName($row),
                'importo' => '€ ' . number_format((float)$row['importo_pagato'], 2, ',', '.'),
                'stato' => (string)$row['stato_pagamento'],
                'fattura' => (string)($row['numero_fattura'] ?: '—'),
            ];
        }

        return [
            'cards' => [
                ['label' => 'Incasso totale', 'value' => '€ ' . number_format((float)$summary['totale'], 2, ',', '.')],
                ['label' => 'Pagamenti', 'value' => (int)$summary['pagamenti']],
                ['label' => 'Ticket medio', 'value' => '€ ' . number_format((float)$summary['medio'], 2, ',', '.')],
            ],
            'tables' => [
                [
                    'title' => 'Movimenti registrati',
                    'html' => emailTemplateTable(
                        [
                            ['key' => 'data', 'label' => 'Data'],
                            ['key' => 'cliente', 'label' => 'Cliente'],
                            ['key' => 'importo', 'label' => 'Importo', 'align' => 'right'],
                            ['key' => 'stato', 'label' => 'Stato'],
                            ['key' => 'fattura', 'label' => 'Fattura'],
                        ],
                        $rows,
                        'Nessun incasso registrato nel periodo.'
                    ),
                ],
            ],
            'text_chart' => emailTemplateAsciiBars($chartSeries),
            'item_count' => count($rows),
        ];
    }

    $summaryStmt = $pdo->prepare(
        "SELECT
            COUNT(*) AS totale,
            COALESCE(SUM(TIMESTAMPDIFF(MINUTE, ora_inizio, ora_fine)), 0) AS minuti,
            COUNT(DISTINCT cliente_id) AS clienti,
            COUNT(DISTINCT insegnante_id) AS insegnanti
         FROM prenotazioni
         WHERE data BETWEEN ? AND ?"
    );
    $summaryStmt->execute([$from, $to]);
    $summary = $summaryStmt->fetch() ?: ['totale' => 0, 'minuti' => 0, 'clienti' => 0, 'insegnanti' => 0];

    $statusStmt = $pdo->prepare(
        "SELECT stato, COUNT(*) AS totale
         FROM prenotazioni
         WHERE data BETWEEN ? AND ?
         GROUP BY stato
         ORDER BY totale DESC, stato ASC"
    );
    $statusStmt->execute([$from, $to]);
    $statusRows = [];
    $statusSeries = [];
    foreach ($statusStmt->fetchAll() as $row) {
        $statusRows[] = [
            'stato' => (string)$row['stato'],
            'totale' => (int)$row['totale'],
        ];
        $statusSeries[(string)$row['stato']] = (int)$row['totale'];
    }

    $dailyStmt = $pdo->prepare(
        "SELECT data, COUNT(*) AS totale, COALESCE(SUM(TIMESTAMPDIFF(MINUTE, ora_inizio, ora_fine)), 0) AS minuti
         FROM prenotazioni
         WHERE data BETWEEN ? AND ?
         GROUP BY data
         ORDER BY data ASC"
    );
    $dailyStmt->execute([$from, $to]);
    $dailyRows = [];
    foreach ($dailyStmt->fetchAll() as $row) {
        $dailyRows[] = [
            'data' => notificationEmailDateLabel((string)$row['data']),
            'lezioni' => (int)$row['totale'],
            'ore' => number_format(((int)$row['minuti']) / 60, 1, ',', ''),
        ];
    }

    return [
        'cards' => [
            ['label' => 'Lezioni', 'value' => (int)$summary['totale']],
            ['label' => 'Ore totali', 'value' => number_format(((int)$summary['minuti']) / 60, 1, ',', '')],
            ['label' => 'Clienti', 'value' => (int)$summary['clienti']],
            ['label' => 'Insegnanti', 'value' => (int)$summary['insegnanti']],
        ],
        'tables' => [
            [
                'title' => 'Stato lezioni',
                'html' => emailTemplateTable(
                    [
                        ['key' => 'stato', 'label' => 'Stato'],
                        ['key' => 'totale', 'label' => 'Totale', 'align' => 'right'],
                    ],
                    $statusRows,
                    'Nessuna lezione registrata nel periodo.'
                ),
            ],
            [
                'title' => 'Andamento giornaliero',
                'html' => emailTemplateTable(
                    [
                        ['key' => 'data', 'label' => 'Data'],
                        ['key' => 'lezioni', 'label' => 'Lezioni', 'align' => 'right'],
                        ['key' => 'ore', 'label' => 'Ore', 'align' => 'right'],
                    ],
                    $dailyRows,
                    'Nessun andamento disponibile.'
                ),
            ],
        ],
        'text_chart' => emailTemplateAsciiBars($statusSeries),
        'item_count' => (int)$summary['totale'],
    ];
}

function buildPeriodicReportEmail(PDO $pdo, array $user, array $config, DateTimeImmutable $now, string $period): array
{
    $isMonthly = $period === 'mensile';
    $days = $isMonthly ? 30 : 7;
    $end = $now;
    $start = $now->modify('-' . ($days - 1) . ' days');
    $tipo = (string)($config[$isMonthly ? 'report_mensile_tipo' : 'report_settimanale_tipo'] ?? 'lezioni');
    $metrics = buildReportMetrics($pdo, $tipo, $start, $end);
    $periodLabel = formatDate($start->format('Y-m-d')) . ' – ' . formatDate($end->format('Y-m-d'));
    $title = $isMonthly ? 'Report mensile' : 'Report settimanale';
    $template = $isMonthly ? 'report-mensile' : 'report-settimanale';

    $contentHtml = '';
    foreach ($metrics['tables'] as $table) {
        $contentHtml .= '<h2>' . emailTemplateValue($table['title'] ?? '') . '</h2>' . ($table['html'] ?? '');
    }
    $secondaryHtml = '';
    if (!empty($metrics['text_chart'])) {
        $secondaryHtml = '<h2>Grafico sintetico</h2><div class="code-block">' . nl2br(emailTemplateValue($metrics['text_chart'])) . '</div>';
    }

    $payload = buildHtmlEmail($template, [
        'eyebrow' => $title,
        'title' => $title . ' • ' . ucfirst($tipo),
        'preheader' => $title . ' con dati reali dal ' . $periodLabel,
        'intro_html' => '<p>Periodo analizzato: <strong>' . emailTemplateValue($periodLabel) . '</strong>.</p>',
        'summary_cards_html' => emailTemplateSummaryCards($metrics['cards']),
        'content_html' => $contentHtml,
        'secondary_html' => $secondaryHtml,
        'text_body' => trim($title . ' (' . $tipo . ')' . "\nPeriodo: " . $periodLabel
            . ($metrics['text_chart'] !== '' ? "\n\n" . $metrics['text_chart'] : '')),
    ]);

    return [
        'type' => $isMonthly ? 'report_mensile' : 'report_settimanale',
        'subject' => $title . ' – EasyBooking',
        'summary' => $tipo . ' • ' . $periodLabel,
        'should_send' => ($metrics['item_count'] ?? 0) > 0,
        'item_count' => (int)($metrics['item_count'] ?? 0),
        'html' => $payload['html'],
        'text' => $payload['text'],
    ];
}

function buildExpiringPackagesEmail(PDO $pdo, array $user, array $config, DateTimeImmutable $now): array
{
    $threshold = max(1, (int)($config['avviso_scadenza_giorni'] ?? 7));
    $stmt = $pdo->prepare(
        "SELECT
            a.data_acquisto,
            c.nome AS cliente_nome,
            c.cognome AS cliente_cognome,
            COALESCE(pk.nome, 'Pacchetto') AS pacchetto_nome,
            COALESCE(NULLIF(a.numero_lezioni, 0), pk.numero_lezioni, 0) AS lezioni_acquistate,
            COALESCE(ls.lezioni_svolte, 0) AS lezioni_svolte,
            GREATEST(COALESCE(NULLIF(a.numero_lezioni, 0), pk.numero_lezioni, 0) - COALESCE(ls.lezioni_svolte, 0), 0) AS lezioni_rimanenti
         FROM acquisti a
         INNER JOIN clienti c ON a.cliente_id = c.id
         LEFT JOIN pacchetti pk ON a.pacchetto_id = pk.id
         LEFT JOIN (
            SELECT acquisto_id, COUNT(*) AS lezioni_svolte
            FROM prenotazioni
            WHERE (stato = 'Svolta' OR stato = 'Assente') AND acquisto_id IS NOT NULL
            GROUP BY acquisto_id
         ) ls ON ls.acquisto_id = a.id
         WHERE a.stato_pagamento <> 'Rimborso'
         HAVING lezioni_acquistate > 0 AND lezioni_rimanenti > 0 AND lezioni_rimanenti <= ?
         ORDER BY lezioni_rimanenti ASC, a.data_acquisto DESC, a.id DESC"
    );
    $stmt->execute([$threshold]);
    $rows = [];
    foreach ($stmt->fetchAll() as $row) {
        $rows[] = [
            'cliente' => notificationEmailClientName($row),
            'pacchetto' => (string)$row['pacchetto_nome'],
            'rimanenti' => (int)$row['lezioni_rimanenti'],
            'acquisto' => formatDate((string)$row['data_acquisto']),
        ];
    }

    $payload = buildHtmlEmail('avviso-scadenza', [
        'eyebrow' => 'Avviso scadenza',
        'title' => 'Pacchetti in esaurimento',
        'preheader' => 'Pacchetti con ' . $threshold . ' o meno lezioni residue',
        'intro_html' => '<p>Sono stati trovati i pacchetti con <strong>' . $threshold . '</strong> o meno lezioni residue.</p>',
        'summary_cards_html' => emailTemplateSummaryCards([
            ['label' => 'Pacchetti critici', 'value' => count($rows)],
            ['label' => 'Soglia', 'value' => $threshold . ' lezioni'],
        ]),
        'content_html' => emailTemplateTable(
            [
                ['key' => 'cliente', 'label' => 'Cliente'],
                ['key' => 'pacchetto', 'label' => 'Pacchetto'],
                ['key' => 'rimanenti', 'label' => 'Lezioni residue', 'align' => 'right'],
                ['key' => 'acquisto', 'label' => 'Data acquisto'],
            ],
            $rows,
            'Nessun pacchetto in esaurimento.'
        ),
        'text_body' => trim("Pacchetti in esaurimento\nSoglia: {$threshold} lezioni\n"
            . ($rows === [] ? "\nNessun pacchetto da segnalare." : "\n" . implode("\n", array_map(
                static fn(array $row): string => '- ' . $row['cliente'] . ' | ' . $row['pacchetto'] . ' | residue: ' . $row['rimanenti'],
                $rows
            )))),
    ]);

    return [
        'type' => 'avviso_scadenza',
        'subject' => 'Avviso pacchetti in esaurimento – EasyBooking',
        'summary' => count($rows) . ' pacchetti entro soglia ' . $threshold,
        'should_send' => $rows !== [],
        'item_count' => count($rows),
        'html' => $payload['html'],
        'text' => $payload['text'],
    ];
}

function buildUnconfirmedLessonsEmail(PDO $pdo, array $user, array $config, DateTimeImmutable $now): array
{
    $days = max(0, (int)($config['avviso_non_confermata_giorni'] ?? 2));
    $from = $now->format('Y-m-d');
    $to = $now->modify('+' . $days . ' days')->format('Y-m-d');

    $stmt = $pdo->prepare(
        "SELECT p.data, p.ora_inizio, p.ora_fine, p.strumento, c.nome AS cliente_nome, c.cognome AS cliente_cognome,
                i.nome AS insegnante_nome, i.cognome AS insegnante_cognome
         FROM prenotazioni p
         INNER JOIN clienti c ON c.id = p.cliente_id
         INNER JOIN insegnanti i ON i.id = p.insegnante_id
         WHERE p.data BETWEEN ? AND ?
           AND p.stato = 'Programmata'
         ORDER BY p.data ASC, p.ora_inizio ASC"
    );
    $stmt->execute([$from, $to]);
    $rows = [];
    foreach ($stmt->fetchAll() as $row) {
        $rows[] = [
            'data' => notificationEmailDateLabel((string)$row['data']),
            'orario' => notificationEmailTimeRange((string)$row['ora_inizio'], (string)$row['ora_fine']),
            'cliente' => notificationEmailClientName($row),
            'insegnante' => notificationEmailClientName($row, 'insegnante_nome', 'insegnante_cognome', 'Insegnante'),
        ];
    }

    $payload = buildHtmlEmail('avviso-non-confermata', [
        'eyebrow' => 'Avviso non confermata',
        'title' => 'Lezioni programmate da verificare',
        'preheader' => 'Lezioni programmate entro ' . $days . ' giorni',
        'intro_html' => '<p>Questa anteprima usa le lezioni con stato <strong>Programmata</strong> come elementi da verificare entro i prossimi <strong>' . $days . ' giorni</strong>.</p>',
        'summary_cards_html' => emailTemplateSummaryCards([
            ['label' => 'Lezioni da verificare', 'value' => count($rows)],
            ['label' => 'Finestra', 'value' => $days . ' giorni'],
        ]),
        'content_html' => emailTemplateTable(
            [
                ['key' => 'data', 'label' => 'Data'],
                ['key' => 'orario', 'label' => 'Orario'],
                ['key' => 'cliente', 'label' => 'Cliente'],
                ['key' => 'insegnante', 'label' => 'Insegnante'],
            ],
            $rows,
            'Nessuna lezione da verificare.'
        ),
        'text_body' => trim("Lezioni da verificare\nPeriodo: " . formatDate($from) . ' - ' . formatDate($to) . "\n"
            . ($rows === [] ? "\nNessuna lezione da verificare." : "\n" . implode("\n", array_map(
                static fn(array $row): string => '- ' . implode(' | ', $row),
                $rows
            )))),
    ]);

    return [
        'type' => 'avviso_non_confermata',
        'subject' => 'Avviso lezioni da verificare – EasyBooking',
        'summary' => count($rows) . ' lezioni entro ' . $days . ' giorni',
        'should_send' => $rows !== [],
        'item_count' => count($rows),
        'html' => $payload['html'],
        'text' => $payload['text'],
    ];
}

function buildNotificationEmailPreview(PDO $pdo, array $user, array $config, string $type, ?DateTimeImmutable $now = null): array
{
    $now = $now ?? new DateTimeImmutable('now');

    return match ($type) {
        'reminder_lezioni' => buildReminderLezioniEmail($pdo, $user, $config, $now),
        'report_settimanale' => buildPeriodicReportEmail($pdo, $user, $config, $now, 'settimanale'),
        'report_mensile' => buildPeriodicReportEmail($pdo, $user, $config, $now, 'mensile'),
        'avviso_scadenza' => buildExpiringPackagesEmail($pdo, $user, $config, $now),
        'avviso_non_confermata' => buildUnconfirmedLessonsEmail($pdo, $user, $config, $now),
        default => throw new InvalidArgumentException('Tipo notifica non supportato: ' . $type),
    };
}

<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/encryption.php';

const EASYBOOKING_WEBMASTER_CF = 'DTSSMN93E20F471O';
const EASYBOOKING_SCHEMA_FILE = __DIR__ . '/database-schema.sql';
const EASYBOOKING_IMPORT_DIR = __DIR__ . '/.adminsetup-imports';

function h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function cleanText(mixed $value, int $maxLength = 0): string
{
    $value = trim((string)$value);
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
    if ($maxLength > 0) {
        $value = mb_substr($value, 0, $maxLength);
    }
    return $value;
}

function cleanNullableText(mixed $value, int $maxLength = 0): ?string
{
    $value = cleanText($value, $maxLength);
    return $value === '' ? null : $value;
}

function cleanEmailAddress(mixed $value): ?string
{
    $email = filter_var(trim((string)$value), FILTER_SANITIZE_EMAIL);
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }
    return $email;
}

function normalizeTab(string $tab): string
{
    $allowed = ['setup', 'verify', 'import', 'reset', 'key'];
    return in_array($tab, $allowed, true) ? $tab : 'setup';
}

function adminSetupUrl(string $tab = 'setup'): string
{
    return 'adminsetup.php?tab=' . urlencode(normalizeTab($tab));
}

function safeSchemaExists(): bool
{
    try {
        return Database::schemaExists();
    } catch (Throwable) {
        return false;
    }
}

function safeTableExists(string $table): bool
{
    try {
        return Database::tableExists($table);
    } catch (Throwable) {
        return false;
    }
}

function safeTableCount(string $table): int
{
    try {
        return Database::countRows($table);
    } catch (Throwable) {
        return -1;
    }
}

function safeUsersCount(): int
{
    if (!safeTableExists('users')) {
        return 0;
    }
    $count = safeTableCount('users');
    return max(0, $count);
}

function currentPdo(): ?PDO
{
    try {
        return Database::getInstance();
    } catch (Throwable) {
        return null;
    }
}

function requireLoginForAdminSetup(bool $loginRequired): void
{
    if ($loginRequired && empty($_SESSION['user_id'])) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? adminSetupUrl();
        redirect('index.php');
    }
}

function fetchStoredEncryptionKeyBase64(): ?string
{
    if (!safeTableExists('system_config')) {
        return null;
    }

    try {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT `value` FROM system_config WHERE `key` = 'encryption_key' LIMIT 1");
        $stmt->execute();
        $value = $stmt->fetchColumn();
        return is_string($value) ? $value : null;
    } catch (Throwable) {
        return null;
    }
}

function fetchStoredEncryptionKeyHex(): ?string
{
    $base64 = fetchStoredEncryptionKeyBase64();
    if ($base64 === null || $base64 === '') {
        return null;
    }

    $decoded = base64_decode($base64, true);
    if ($decoded === false || $decoded === '') {
        return null;
    }

    return strtoupper(bin2hex($decoded));
}

function expectedTables(): array
{
    return [
        'users',
        'system_config',
        'strumenti',
        'clienti',
        'insegnanti',
        'insegnanti_strumenti',
        'pacchetti',
        'acquisti',
        'prenotazioni',
        'impostazioni_generali',
        'notifiche_config',
        'tariffe_coppia',
    ];
}

function expectedXmlFiles(): array
{
    return [
        'clienti.xml' => 'Clienti',
        'insegnanti.xml' => 'Insegnanti',
        'prenotazioni.xml' => 'Prenotazioni',
        'acquisti.xml' => 'Acquisti',
        'pacchetti.xml' => 'Pacchetti',
        'strumenti.xml' => 'Strumenti',
        'impostazioni-generali.xml' => 'Impostazioni generali',
        'tariffe_coppia.xml' => 'Tariffe di coppia',
    ];
}

function xmlProcessingOrder(): array
{
    return [
        'strumenti.xml',
        'clienti.xml',
        'insegnanti.xml',
        'pacchetti.xml',
        'acquisti.xml',
        'prenotazioni.xml',
        'impostazioni-generali.xml',
        'tariffe_coppia.xml',
    ];
}

function xmlImportHandlers(): array
{
    return [
        'clienti.xml' => 'importClientiXml',
        'insegnanti.xml' => 'importInsegnantiXml',
        'prenotazioni.xml' => 'importPrenotazioniXml',
        'acquisti.xml' => 'importAcquistiXml',
        'pacchetti.xml' => 'importPacchettiXml',
        'strumenti.xml' => 'importStrumentiXml',
        'impostazioni-generali.xml' => 'importImpostazioniGeneraliXml',
        'tariffe_coppia.xml' => 'importTariffeCoppiaXml',
    ];
}

function sqlPreview(string $statement): string
{
    $statement = preg_replace('/\s+/', ' ', trim($statement)) ?? trim($statement);
    return mb_substr($statement, 0, 120);
}

function splitSqlStatements(string $sql): array
{
    $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql) ?? $sql;
    $lines = preg_split('/\R/', $sql) ?: [];
    $filtered = [];
    foreach ($lines as $line) {
        if (preg_match('/^\s*--/', $line)) {
            continue;
        }
        $filtered[] = $line;
    }
    $sql = implode("\n", $filtered);

    $statements = [];
    $buffer = '';
    $inSingle = false;
    $inDouble = false;
    $inBacktick = false;
    $length = strlen($sql);

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $prev = $i > 0 ? $sql[$i - 1] : '';

        if ($char === "'" && !$inDouble && !$inBacktick && $prev !== '\\') {
            $inSingle = !$inSingle;
        } elseif ($char === '"' && !$inSingle && !$inBacktick && $prev !== '\\') {
            $inDouble = !$inDouble;
        } elseif ($char === '`' && !$inSingle && !$inDouble) {
            $inBacktick = !$inBacktick;
        }

        if ($char === ';' && !$inSingle && !$inDouble && !$inBacktick) {
            $statement = trim($buffer);
            if ($statement !== '') {
                $statements[] = $statement;
            }
            $buffer = '';
            continue;
        }

        $buffer .= $char;
    }

    $tail = trim($buffer);
    if ($tail !== '') {
        $statements[] = $tail;
    }

    return $statements;
}

function runSchemaFromFile(PDO $pdo, string $schemaFile): int
{
    if (!is_file($schemaFile) || !is_readable($schemaFile)) {
        throw new RuntimeException('File schema non trovato o non leggibile.');
    }

    $content = file_get_contents($schemaFile);
    if ($content === false) {
        throw new RuntimeException('Impossibile leggere il file schema.');
    }

    $statements = splitSqlStatements($content);
    if ($statements === []) {
        throw new RuntimeException('Nessuna istruzione SQL trovata nel file schema.');
    }

    $executed = 0;
    foreach ($statements as $statement) {
        try {
            $pdo->exec($statement);
            $executed++;
        } catch (PDOException $exception) {
            throw new RuntimeException(
                'Errore SQL su: ' . sqlPreview($statement) . ' — ' . $exception->getMessage(),
                0,
                $exception
            );
        }
    }

    return $executed;
}

function parseXmlBool(mixed $value): int
{
    $value = strtolower(cleanText($value));
    return in_array($value, ['1', 'true', 'yes', 'si', 'sì'], true) ? 1 : 0;
}

function parseXmlInt(mixed $value): int
{
    return (int)cleanText($value);
}

function parseXmlFloat(mixed $value): float
{
    $value = str_replace(',', '.', cleanText($value));
    return is_numeric($value) ? (float)$value : 0.0;
}

function parseXmlDate(mixed $value): ?string
{
    $value = cleanText($value, 25);
    if ($value === '') {
        return null;
    }
    $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'];
    foreach ($formats as $format) {
        $date = DateTime::createFromFormat($format, $value);
        if ($date instanceof DateTime) {
            return $date->format('Y-m-d');
        }
    }
    try {
        return (new DateTime($value))->format('Y-m-d');
    } catch (Throwable) {
        return null;
    }
}

function parseXmlTime(mixed $value): ?string
{
    $value = cleanText($value, 12);
    if ($value === '') {
        return null;
    }
    $formats = ['H:i:s', 'H:i'];
    foreach ($formats as $format) {
        $time = DateTime::createFromFormat($format, $value);
        if ($time instanceof DateTime) {
            return $time->format('H:i:s');
        }
    }
    return null;
}

function xmlField(SimpleXMLElement $node, string $field): string
{
    return cleanText((string)($node->{$field} ?? ''));
}

function xmlNullableField(SimpleXMLElement $node, string $field, int $maxLength = 0): ?string
{
    return cleanNullableText((string)($node->{$field} ?? ''), $maxLength);
}

function parseTeacherInstrumentNames(SimpleXMLElement $node): array
{
    $names = [];
    if (!isset($node->Strumenti)) {
        return [];
    }

    $strumenti = $node->Strumenti;
    if ($strumenti->count() > 0) {
        foreach ($strumenti->children() as $child) {
            $value = cleanText((string)$child, 100);
            if ($value !== '') {
                $names[] = $value;
            }
        }
    } else {
        $raw = cleanText((string)$strumenti);
        if ($raw !== '') {
            foreach (preg_split('/\s*[,;|]\s*/', $raw) ?: [] as $piece) {
                $piece = cleanText($piece, 100);
                if ($piece !== '') {
                    $names[] = $piece;
                }
            }
        }
    }

    return array_values(array_unique($names));
}

function ensureImportDirectory(): string
{
    if (!is_dir(EASYBOOKING_IMPORT_DIR) && !mkdir(EASYBOOKING_IMPORT_DIR, 0700, true) && !is_dir(EASYBOOKING_IMPORT_DIR)) {
        throw new RuntimeException('Impossibile creare la directory temporanea di importazione.');
    }
    return EASYBOOKING_IMPORT_DIR;
}

function moveUploadedXmlToProject(array $file): string
{
    $directory = ensureImportDirectory();
    $originalName = cleanText($file['name'] ?? 'upload.xml', 200);
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $extension = $extension !== '' ? $extension : 'xml';
    $targetPath = $directory . '/' . date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;

    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Upload non valido per ' . $originalName . '.');
    }

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new RuntimeException('Impossibile spostare il file caricato ' . $originalName . '.');
    }

    @chmod($targetPath, 0600);
    return $targetPath;
}

function fixXmlEncoding(string $content, string &$detectedEncoding): string
{
    // Detect encoding via BOM (most reliable)
    if (str_starts_with($content, "\xFF\xFE")) {
        $detectedEncoding = 'UTF-16LE';
        $content = mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16LE');
    } elseif (str_starts_with($content, "\xFE\xFF")) {
        $detectedEncoding = 'UTF-16BE';
        $content = mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16BE');
    } elseif (str_starts_with($content, "\xEF\xBB\xBF")) {
        $detectedEncoding = 'UTF-8';
        $content = substr($content, 3);
    } else {
        $enc = mb_detect_encoding($content, ['UTF-8', 'UTF-16LE', 'UTF-16BE', 'UTF-16', 'Windows-1252', 'ISO-8859-1'], true);
        $detectedEncoding = $enc ?: 'UTF-8';
        if ($enc && $enc !== 'UTF-8') {
            $converted = mb_convert_encoding($content, 'UTF-8', $enc);
            if ($converted !== false) {
                $content = $converted;
            }
        }
    }
    // Fix wrong encoding declaration (e.g. UTF-16 declared but content is now UTF-8)
    $content = preg_replace(
        '/<\?xml\s+version="1\.0"\s+encoding="UTF-16[^"]*"\s*\?>/i',
        '<?xml version="1.0" encoding="UTF-8"?>',
        $content
    ) ?? $content;
    return $content;
}

function loadXmlContentFromEncryptedFile(string $filePath, string $originalName, array &$log, string &$detectedEncoding = ''): string
{
    $detectedEncoding = 'UTF-8';
    $content = decryptXMLFile($filePath);
    if ($content !== false) {
        $content = fixXmlEncoding($content, $detectedEncoding);
        $log[] = '🔍 ' . $originalName . ': encoding rilevato: ' . $detectedEncoding . ($detectedEncoding !== 'UTF-8' ? ' → convertito a UTF-8' : '');
        if (str_starts_with(ltrim($content), '<')) {
            $log[] = '🔐 ' . $originalName . ': decrittazione completata.';
            return $content;
        }
    }

    $fallback = file_get_contents($filePath);
    if ($fallback === false) {
        throw new RuntimeException('Impossibile leggere il file ' . $originalName . '.');
    }

    $fallback = fixXmlEncoding($fallback, $detectedEncoding);
    $log[] = '🔍 ' . $originalName . ': encoding rilevato: ' . $detectedEncoding . ($detectedEncoding !== 'UTF-8' ? ' → convertito a UTF-8' : '');
    if (!str_starts_with(ltrim($fallback), '<')) {
        throw new RuntimeException('Il file ' . $originalName . ' non contiene XML valido dopo la decrittazione.');
    }

    $log[] = 'ℹ️ ' . $originalName . ': importato come XML non cifrato.';
    return $fallback;
}

function parseXmlDocument(string $content, string $originalName, string $detectedEncoding = ''): SimpleXMLElement
{
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($content, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
    if ($xml === false) {
        $messages = [];
        foreach (libxml_get_errors() as $error) {
            $messages[] = trim($error->message);
        }
        libxml_clear_errors();
        $suffix = $detectedEncoding !== '' ? ' (Encoding rilevato: ' . $detectedEncoding . ')' : '';
        throw new RuntimeException('XML non valido per ' . $originalName . ': ' . implode('; ', $messages) . $suffix);
    }
    libxml_clear_errors();
    return $xml;
}

function importClientiXml(PDO $pdo, SimpleXMLElement $xml, array &$log): int
{
    $count = 0;
    $stmt = $pdo->prepare(
        'INSERT INTO clienti
            (id, nome, cognome, telefono, email, indirizzo, codice_fiscale, note, mega_cartella_pubblica, mega_cartella_locale)
         VALUES
            (:id, :nome, :cognome, :telefono, :email, :indirizzo, :codice_fiscale, :note, :mega_pubblica, :mega_locale)
         ON DUPLICATE KEY UPDATE
            nome = VALUES(nome),
            cognome = VALUES(cognome),
            telefono = VALUES(telefono),
            email = VALUES(email),
            indirizzo = VALUES(indirizzo),
            codice_fiscale = VALUES(codice_fiscale),
            note = VALUES(note),
            mega_cartella_pubblica = VALUES(mega_cartella_pubblica),
            mega_cartella_locale = VALUES(mega_cartella_locale)'
    );

    foreach ($xml->Cliente as $cliente) {
        $id = parseXmlInt($cliente->Id ?? '0');
        if ($id <= 0) {
            $log[] = '⚠️ Cliente ignorato: Id mancante o non valido.';
            continue;
        }

        $stmt->execute([
            ':id' => $id,
            ':nome' => cleanText(xmlField($cliente, 'Nome'), 100),
            ':cognome' => cleanText(xmlField($cliente, 'Cognome'), 100),
            ':telefono' => cleanNullableText(xmlField($cliente, 'Telefono'), 50),
            ':email' => cleanEmailAddress(xmlField($cliente, 'Email')),
            ':indirizzo' => xmlNullableField($cliente, 'Indirizzo'),
            ':codice_fiscale' => cleanNullableText(xmlField($cliente, 'CodiceFiscale'), 50),
            ':note' => xmlNullableField($cliente, 'Note'),
            ':mega_pubblica' => xmlNullableField($cliente, 'MegaCartellaPubblica'),
            ':mega_locale' => xmlNullableField($cliente, 'MegaCartellaLocale'),
        ]);
        $count++;
    }

    $log[] = '✅ Clienti importati/aggiornati: ' . $count;
    return $count;
}

function ensureInstrumentByName(PDO $pdo, string $name, array &$log): int
{
    $name = cleanText($name, 100);
    if ($name === '') {
        throw new RuntimeException('Nome strumento non valido.');
    }

    $find = $pdo->prepare('SELECT id FROM strumenti WHERE nome = ? LIMIT 1');
    $find->execute([$name]);
    $existingId = $find->fetchColumn();
    if ($existingId !== false) {
        return (int)$existingId;
    }

    $insert = $pdo->prepare(
        'INSERT INTO strumenti
            (nome, lun_attivo, mar_attivo, mer_attivo, gio_attivo, ven_attivo, sab_attivo, dom_attivo, matt_inizio, matt_fine, pom_inizio, pom_fine)
         VALUES
            (?, 1, 1, 1, 1, 1, 0, 0, ?, ?, ?, ?)'
    );
    $insert->execute([$name, '09:00:00', '13:00:00', '15:00:00', '19:00:00']);
    $newId = (int)$pdo->lastInsertId();
    $log[] = 'ℹ️ Creato strumento mancante: ' . $name;
    return $newId;
}

function importInsegnantiXml(PDO $pdo, SimpleXMLElement $xml, array &$log): int
{
    $count = 0;
    $teacherStmt = $pdo->prepare(
        'INSERT INTO insegnanti
            (id, nome, cognome, telefono, email, tariffa_oraria)
         VALUES
            (:id, :nome, :cognome, :telefono, :email, :tariffa)
         ON DUPLICATE KEY UPDATE
            nome = VALUES(nome),
            cognome = VALUES(cognome),
            telefono = VALUES(telefono),
            email = VALUES(email),
            tariffa_oraria = VALUES(tariffa_oraria)'
    );
    $clearLinks = $pdo->prepare('DELETE FROM insegnanti_strumenti WHERE insegnante_id = ?');
    $linkStmt = $pdo->prepare('INSERT IGNORE INTO insegnanti_strumenti (insegnante_id, strumento_id) VALUES (?, ?)');

    foreach ($xml->Insegnante as $insegnante) {
        $id = parseXmlInt($insegnante->Id ?? '0');
        if ($id <= 0) {
            $log[] = '⚠️ Insegnante ignorato: Id mancante o non valido.';
            continue;
        }

        $teacherStmt->execute([
            ':id' => $id,
            ':nome' => cleanText(xmlField($insegnante, 'Nome'), 100),
            ':cognome' => cleanText(xmlField($insegnante, 'Cognome'), 100),
            ':telefono' => cleanNullableText(xmlField($insegnante, 'Telefono'), 50),
            ':email' => cleanEmailAddress(xmlField($insegnante, 'Email')),
            ':tariffa' => parseXmlFloat($insegnante->TariffaOraria ?? '0'),
        ]);

        $clearLinks->execute([$id]);
        foreach (parseTeacherInstrumentNames($insegnante) as $instrumentName) {
            $instrumentId = ensureInstrumentByName($pdo, $instrumentName, $log);
            $linkStmt->execute([$id, $instrumentId]);
        }

        $count++;
    }

    $log[] = '✅ Insegnanti importati/aggiornati: ' . $count;
    return $count;
}

function importPrenotazioniXml(PDO $pdo, SimpleXMLElement $xml, array &$log): int
{
    $count = 0;
    $stmt = $pdo->prepare(
        'INSERT INTO prenotazioni
            (id, data, ora_inizio, ora_fine, cliente_id, insegnante_id, strumento, stato, pacchetto_nome, acquisto_id)
         VALUES
            (:id, :data, :ora_inizio, :ora_fine, :cliente_id, :insegnante_id, :strumento, :stato, :pacchetto_nome, :acquisto_id)
         ON DUPLICATE KEY UPDATE
            data = VALUES(data),
            ora_inizio = VALUES(ora_inizio),
            ora_fine = VALUES(ora_fine),
            cliente_id = VALUES(cliente_id),
            insegnante_id = VALUES(insegnante_id),
            strumento = VALUES(strumento),
            stato = VALUES(stato),
            pacchetto_nome = VALUES(pacchetto_nome),
            acquisto_id = VALUES(acquisto_id)'
    );
    $allowedStatus = ['Programmata', 'Svolta', 'Assente', 'Rimandata', 'Riprogrammata'];

    foreach ($xml->Prenotazione as $prenotazione) {
        $id = parseXmlInt($prenotazione->Id ?? '0');
        $date = parseXmlDate($prenotazione->Data ?? '');
        $start = parseXmlTime($prenotazione->OraInizioStr ?? '');
        $end = parseXmlTime($prenotazione->OraFineStr ?? '');
        $status = cleanText(xmlField($prenotazione, 'Stato'), 30);
        if (!in_array($status, $allowedStatus, true)) {
            $status = 'Programmata';
        }

        if ($id <= 0 || $date === null || $start === null || $end === null) {
            $log[] = '⚠️ Prenotazione ignorata: dati obbligatori mancanti.';
            continue;
        }

        $acquistoId = parseXmlInt($prenotazione->AcquistoId ?? '0');
        $stmt->execute([
            ':id' => $id,
            ':data' => $date,
            ':ora_inizio' => $start,
            ':ora_fine' => $end,
            ':cliente_id' => parseXmlInt($prenotazione->ClienteId ?? '0'),
            ':insegnante_id' => parseXmlInt($prenotazione->InsegnanteId ?? '0'),
            ':strumento' => cleanNullableText(xmlField($prenotazione, 'Strumento'), 100),
            ':stato' => $status,
            ':pacchetto_nome' => cleanNullableText(xmlField($prenotazione, 'PacchettoNome'), 150),
            ':acquisto_id' => $acquistoId > 0 ? $acquistoId : null,
        ]);
        $count++;
    }

    $log[] = '✅ Prenotazioni importate/aggiornate: ' . $count;
    return $count;
}

function importAcquistiXml(PDO $pdo, SimpleXMLElement $xml, array &$log): int
{
    $count = 0;
    $stmt = $pdo->prepare(
        'INSERT INTO acquisti
            (id, data_acquisto, cliente_id, pacchetto_id, importo_pagato, stato_pagamento, pianificato, numero_fattura, note, numero_lezioni)
         VALUES
            (:id, :data_acquisto, :cliente_id, :pacchetto_id, :importo_pagato, :stato_pagamento, :pianificato, :numero_fattura, :note, :numero_lezioni)
         ON DUPLICATE KEY UPDATE
            data_acquisto = VALUES(data_acquisto),
            cliente_id = VALUES(cliente_id),
            pacchetto_id = VALUES(pacchetto_id),
            importo_pagato = VALUES(importo_pagato),
            stato_pagamento = VALUES(stato_pagamento),
            pianificato = VALUES(pianificato),
            numero_fattura = VALUES(numero_fattura),
            note = VALUES(note),
            numero_lezioni = VALUES(numero_lezioni)'
    );

    foreach ($xml->Acquisto as $acquisto) {
        $id = parseXmlInt($acquisto->Id ?? '0');
        $date = parseXmlDate($acquisto->DataAcquisto ?? '');
        if ($id <= 0 || $date === null) {
            $log[] = '⚠️ Acquisto ignorato: dati obbligatori mancanti.';
            continue;
        }

        $pacchettoId = parseXmlInt($acquisto->PacchettoId ?? '0');
        $stmt->execute([
            ':id' => $id,
            ':data_acquisto' => $date,
            ':cliente_id' => parseXmlInt($acquisto->ClienteId ?? '0'),
            ':pacchetto_id' => $pacchettoId > 0 ? $pacchettoId : null,
            ':importo_pagato' => parseXmlFloat($acquisto->ImportoPagato ?? '0'),
            ':stato_pagamento' => cleanText(xmlField($acquisto, 'StatoPagamento'), 50),
            ':pianificato' => parseXmlBool($acquisto->Pianificato ?? 'false'),
            ':numero_fattura' => cleanNullableText(xmlField($acquisto, 'NumeroFattura'), 100),
            ':note' => xmlNullableField($acquisto, 'Note'),
            ':numero_lezioni' => parseXmlInt($acquisto->NumeroLezioni ?? '0'),
        ]);
        $count++;
    }

    $log[] = '✅ Acquisti importati/aggiornati: ' . $count;
    return $count;
}

function importPacchettiXml(PDO $pdo, SimpleXMLElement $xml, array &$log): int
{
    $count = 0;
    $stmt = $pdo->prepare(
        'INSERT INTO pacchetti
            (id, nome, descrizione, numero_lezioni, durata_minuti, frequenza, prezzo, strumento)
         VALUES
            (:id, :nome, :descrizione, :numero_lezioni, :durata_minuti, :frequenza, :prezzo, :strumento)
         ON DUPLICATE KEY UPDATE
            nome = VALUES(nome),
            descrizione = VALUES(descrizione),
            numero_lezioni = VALUES(numero_lezioni),
            durata_minuti = VALUES(durata_minuti),
            frequenza = VALUES(frequenza),
            prezzo = VALUES(prezzo),
            strumento = VALUES(strumento)'
    );

    foreach ($xml->Pacchetto as $pacchetto) {
        $id = parseXmlInt($pacchetto->Id ?? '0');
        if ($id <= 0) {
            $log[] = '⚠️ Pacchetto ignorato: Id mancante o non valido.';
            continue;
        }

        $stmt->execute([
            ':id' => $id,
            ':nome' => cleanText(xmlField($pacchetto, 'Nome'), 150),
            ':descrizione' => xmlNullableField($pacchetto, 'Descrizione'),
            ':numero_lezioni' => parseXmlInt($pacchetto->NumeroLezioni ?? '0'),
            ':durata_minuti' => parseXmlInt($pacchetto->DurataMinuti ?? '60'),
            ':frequenza' => cleanNullableText(xmlField($pacchetto, 'Frequenza'), 100),
            ':prezzo' => parseXmlFloat($pacchetto->Prezzo ?? '0'),
            ':strumento' => cleanNullableText(xmlField($pacchetto, 'Strumento'), 100),
        ]);
        $count++;
    }

    $log[] = '✅ Pacchetti importati/aggiornati: ' . $count;
    return $count;
}

function importStrumentiXml(PDO $pdo, SimpleXMLElement $xml, array &$log): int
{
    $count = 0;
    $stmt = $pdo->prepare(
        'INSERT INTO strumenti
            (id, nome, lun_attivo, mar_attivo, mer_attivo, gio_attivo, ven_attivo, sab_attivo, dom_attivo, matt_inizio, matt_fine, pom_inizio, pom_fine)
         VALUES
            (:id, :nome, :lun, :mar, :mer, :gio, :ven, :sab, :dom, :matt_inizio, :matt_fine, :pom_inizio, :pom_fine)
         ON DUPLICATE KEY UPDATE
            nome = VALUES(nome),
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
            pom_fine = VALUES(pom_fine)'
    );

    foreach ($xml->Strumento as $strumento) {
        $id = parseXmlInt($strumento->Id ?? '0');
        if ($id <= 0) {
            $log[] = '⚠️ Strumento ignorato: Id mancante o non valido.';
            continue;
        }

        $stmt->execute([
            ':id' => $id,
            ':nome' => cleanText(xmlField($strumento, 'Nome'), 100),
            ':lun' => parseXmlBool($strumento->LunAttivo ?? 'false'),
            ':mar' => parseXmlBool($strumento->MarAttivo ?? 'false'),
            ':mer' => parseXmlBool($strumento->MerAttivo ?? 'false'),
            ':gio' => parseXmlBool($strumento->GioAttivo ?? 'false'),
            ':ven' => parseXmlBool($strumento->VenAttivo ?? 'false'),
            ':sab' => parseXmlBool($strumento->SabAttivo ?? 'false'),
            ':dom' => parseXmlBool($strumento->DomAttivo ?? 'false'),
            ':matt_inizio' => parseXmlTime($strumento->MattInizio ?? '') ?? '09:00:00',
            ':matt_fine' => parseXmlTime($strumento->MattFine ?? '') ?? '13:00:00',
            ':pom_inizio' => parseXmlTime($strumento->PomInizio ?? '') ?? '15:00:00',
            ':pom_fine' => parseXmlTime($strumento->PomFine ?? '') ?? '19:00:00',
        ]);
        $count++;
    }

    $log[] = '✅ Strumenti importati/aggiornati: ' . $count;
    return $count;
}

function importImpostazioniGeneraliXml(PDO $pdo, SimpleXMLElement $xml, array &$log): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO impostazioni_generali
            (id, lun_attivo, mar_attivo, mer_attivo, gio_attivo, ven_attivo, sab_attivo, dom_attivo, matt_inizio, matt_fine, pom_inizio, pom_fine, durata_lezione_default)
         VALUES
            (1, :lun, :mar, :mer, :gio, :ven, :sab, :dom, :matt_inizio, :matt_fine, :pom_inizio, :pom_fine, :durata)
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
        ':lun' => parseXmlBool($xml->LunAttivo ?? 'false'),
        ':mar' => parseXmlBool($xml->MarAttivo ?? 'false'),
        ':mer' => parseXmlBool($xml->MerAttivo ?? 'false'),
        ':gio' => parseXmlBool($xml->GioAttivo ?? 'false'),
        ':ven' => parseXmlBool($xml->VenAttivo ?? 'false'),
        ':sab' => parseXmlBool($xml->SabAttivo ?? 'false'),
        ':dom' => parseXmlBool($xml->DomAttivo ?? 'false'),
        ':matt_inizio' => parseXmlTime($xml->MattInizio ?? '') ?? '09:00:00',
        ':matt_fine' => parseXmlTime($xml->MattFine ?? '') ?? '13:00:00',
        ':pom_inizio' => parseXmlTime($xml->PomInizio ?? '') ?? '15:00:00',
        ':pom_fine' => parseXmlTime($xml->PomFine ?? '') ?? '19:00:00',
        ':durata' => parseXmlInt($xml->DurataLezioneDefault ?? '60'),
    ]);

    $log[] = '✅ Impostazioni generali importate/aggiornate.';
    return 1;
}

function importTariffeCoppiaXml(PDO $pdo, SimpleXMLElement $xml, array &$log): int
{
    $count = 0;
    $stmt = $pdo->prepare(
        'INSERT INTO tariffe_coppia (insegnante_id, tariffa)
         VALUES (:insegnante_id, :tariffa)
         ON DUPLICATE KEY UPDATE tariffa = VALUES(tariffa)'
    );

    foreach ($xml->Tariffa as $tariffa) {
        $insegnanteId = parseXmlInt($tariffa->InsegnanteId ?? '0');
        if ($insegnanteId <= 0) {
            $log[] = '⚠️ Tariffa di coppia ignorata: InsegnanteId non valido.';
            continue;
        }

        $stmt->execute([
            ':insegnante_id' => $insegnanteId,
            ':tariffa' => parseXmlFloat($tariffa->Tariffa ?? '0'),
        ]);
        $count++;
    }

    $log[] = '✅ Tariffe di coppia importate/aggiornate: ' . $count;
    return $count;
}

function replacementTablesForUploadedFiles(array $fileNames): array
{
    $tables = [];
    foreach ($fileNames as $fileName) {
        switch ($fileName) {
            case 'clienti.xml':
                $tables = array_merge($tables, ['prenotazioni', 'acquisti', 'clienti']);
                break;
            case 'insegnanti.xml':
                $tables = array_merge($tables, ['prenotazioni', 'tariffe_coppia', 'insegnanti_strumenti', 'insegnanti']);
                break;
            case 'prenotazioni.xml':
                $tables[] = 'prenotazioni';
                break;
            case 'acquisti.xml':
                $tables = array_merge($tables, ['prenotazioni', 'acquisti']);
                break;
            case 'pacchetti.xml':
                $tables = array_merge($tables, ['prenotazioni', 'acquisti', 'pacchetti']);
                break;
            case 'strumenti.xml':
                $tables = array_merge($tables, ['insegnanti_strumenti', 'strumenti']);
                break;
            case 'impostazioni-generali.xml':
                $tables[] = 'impostazioni_generali';
                break;
            case 'tariffe_coppia.xml':
                $tables[] = 'tariffe_coppia';
                break;
        }
    }

    $order = ['prenotazioni', 'acquisti', 'tariffe_coppia', 'insegnanti_strumenti', 'insegnanti', 'clienti', 'pacchetti', 'strumenti', 'impostazioni_generali'];
    $tables = array_unique($tables);
    return array_values(array_intersect($order, $tables));
}

function clearTablesForReplace(PDO $pdo, array $fileNames, array &$log): void
{
    $tables = replacementTablesForUploadedFiles($fileNames);
    if ($tables === []) {
        return;
    }

    foreach ($tables as $table) {
        if (!safeTableExists($table)) {
            continue;
        }
        $pdo->exec('DELETE FROM `' . str_replace('`', '', $table) . '`');
        $log[] = '🧹 Tabella svuotata: ' . $table;
    }
}

function resetApplicationData(PDO $pdo, array &$log): int
{
    $tables = [
        'prenotazioni',
        'acquisti',
        'tariffe_coppia',
        'insegnanti_strumenti',
        'pacchetti',
        'strumenti',
        'insegnanti',
        'clienti',
    ];

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    try {
        $resetCount = 0;
        foreach ($tables as $table) {
            if (!safeTableExists($table)) {
                continue;
            }
            $pdo->exec('TRUNCATE TABLE `' . str_replace('`', '', $table) . '`');
            $log[] = '🗑️ Tabella azzerata: ' . $table;
            $resetCount++;
        }
        return $resetCount;
    } finally {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
}

function normalizeUploadedFileName(string $name): string
{
    $name = strtolower(cleanText($name, 200));
    return basename($name);
}

function collectUploadedXmlFiles(array $files): array
{
    $collected = [];
    if (!isset($files['name']) || !is_array($files['name'])) {
        return $collected;
    }

    $totalFiles = count($files['name']);
    for ($i = 0; $i < $totalFiles; $i++) {
        $error = $files['error'][$i] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Errore di upload per il file #' . ($i + 1) . '.');
        }

        $name = normalizeUploadedFileName((string)($files['name'][$i] ?? ''));
        if ($name === '') {
            continue;
        }

        $collected[$name] = [
            'name' => $name,
            'type' => (string)($files['type'][$i] ?? ''),
            'tmp_name' => (string)($files['tmp_name'][$i] ?? ''),
            'error' => $error,
            'size' => (int)($files['size'][$i] ?? 0),
        ];
    }

    return $collected;
}

function removeRuntimeFile(?string $path): void
{
    if ($path !== null && $path !== '' && is_file($path)) {
        @unlink($path);
    }
}

function cleanupImportDirectory(): void
{
    if (!is_dir(EASYBOOKING_IMPORT_DIR)) {
        return;
    }

    $items = scandir(EASYBOOKING_IMPORT_DIR);
    if ($items === false) {
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = EASYBOOKING_IMPORT_DIR . '/' . $item;
        if (is_file($path)) {
            @unlink($path);
        }
    }

    $remaining = scandir(EASYBOOKING_IMPORT_DIR);
    if (is_array($remaining) && count($remaining) <= 2) {
        @rmdir(EASYBOOKING_IMPORT_DIR);
    }
}

function handleXmlImportAction(): never
{
    verifyCsrf();

    if (!safeSchemaExists()) {
        jsonResponse([
            'success' => false,
            'log' => [],
            'errors' => ['La struttura database non è ancora disponibile. Esegui prima il setup del DB.'],
        ], 422);
    }

    $expectedFiles = expectedXmlFiles();
    $handlers = xmlImportHandlers();
    $log = [];
    $errors = [];

    try {
        $uploadedFiles = collectUploadedXmlFiles($_FILES['xml_files'] ?? []);
    } catch (Throwable $exception) {
        jsonResponse([
            'success' => false,
            'log' => [],
            'errors' => [$exception->getMessage()],
        ], 422);
    }

    if ($uploadedFiles === []) {
        jsonResponse([
            'success' => false,
            'log' => [],
            'errors' => ['Seleziona almeno un file XML da importare.'],
        ], 422);
    }

    $unexpected = array_diff(array_keys($uploadedFiles), array_keys($expectedFiles));
    if ($unexpected !== []) {
        jsonResponse([
            'success' => false,
            'log' => [],
            'errors' => ['File non riconosciuti: ' . implode(', ', $unexpected)],
        ], 422);
    }

    $replaceMode = post('replace_mode') === 'replace' ? 'replace' : 'update';
    $processingMode = post('processing_mode') === 'continue' ? 'continue' : 'atomic';

    $pdo = currentPdo();
    if (!$pdo instanceof PDO) {
        jsonResponse([
            'success' => false,
            'log' => [],
            'errors' => ['Connessione al database non disponibile.'],
        ], 500);
    }

    $log[] = '📦 File ricevuti: ' . implode(', ', array_keys($uploadedFiles));
    $log[] = '⚙️ Modalità record: ' . ($replaceMode === 'replace' ? 'Sostituisci' : 'Aggiorna');
    $log[] = '🛡️ Modalità elaborazione: ' . ($processingMode === 'atomic' ? 'Transazione unica' : 'Continua su errore');

    try {
        if ($processingMode === 'atomic') {
            $pdo->beginTransaction();
        }

        if ($replaceMode === 'replace') {
            clearTablesForReplace($pdo, array_keys($uploadedFiles), $log);
        }

        foreach (xmlProcessingOrder() as $expectedName) {
            if (!isset($uploadedFiles[$expectedName])) {
                continue;
            }

            $temporaryPath = null;
            try {
                if ($processingMode === 'continue' && !$pdo->inTransaction()) {
                    $pdo->beginTransaction();
                    if ($replaceMode === 'replace') {
                        // already cleared once before loop
                    }
                }

                $log[] = '➡️ Avvio importazione: ' . $expectedName;
                $temporaryPath = moveUploadedXmlToProject($uploadedFiles[$expectedName]);
                $detectedEncoding = '';
                $xmlContent = loadXmlContentFromEncryptedFile($temporaryPath, $expectedName, $log, $detectedEncoding);
                $xml = parseXmlDocument($xmlContent, $expectedName, $detectedEncoding);
                $handler = $handlers[$expectedName] ?? null;
                if ($handler === null || !function_exists($handler)) {
                    throw new RuntimeException('Handler mancante per ' . $expectedName . '.');
                }
                $handler($pdo, $xml, $log);
                $log[] = '✔️ Importazione completata: ' . $expectedName;

                if ($processingMode === 'continue' && $pdo->inTransaction()) {
                    $pdo->commit();
                }
            } catch (Throwable $exception) {
                if ($processingMode === 'atomic') {
                    throw $exception;
                }
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = $expectedName . ': ' . $exception->getMessage();
                $log[] = '❌ Errore su ' . $expectedName . ': ' . $exception->getMessage();
            } finally {
                removeRuntimeFile($temporaryPath);
            }
        }

        if ($processingMode === 'atomic' && $pdo->inTransaction()) {
            $pdo->commit();
        }
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $errors[] = $exception->getMessage();
        $log[] = '⛔ Importazione annullata: ' . $exception->getMessage();
    } finally {
        cleanupImportDirectory();
    }

    jsonResponse([
        'success' => $errors === [],
        'log' => $log,
        'errors' => $errors,
    ], $errors === [] ? 200 : 422);
}

$activeTab = normalizeTab(get('tab', 'setup'));
$errorMessage = '';
$successMessage = '';
$recentGeneratedKey = $_SESSION['generated_key_b64'] ?? '';
unset($_SESSION['generated_key_b64']);

$schemaExists = safeSchemaExists();
$usersCount = $schemaExists ? safeUsersCount() : 0;
$loginRequired = $schemaExists && $usersCount > 0;
$cfVerified = !empty($_SESSION['cf_verified']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post('action');

    if ($action === 'verify_cf') {
        verifyCsrf();
        $submittedCf = strtoupper(cleanText(post('webmaster_cf'), 16));
        if (hash_equals(EASYBOOKING_WEBMASTER_CF, $submittedCf)) {
            session_regenerate_id(true);
            $_SESSION['cf_verified'] = true;
            $cfVerified = true;
            $successMessage = 'Codice fiscale verificato correttamente.';

            if ($loginRequired && empty($_SESSION['user_id'])) {
                $_SESSION['redirect_after_login'] = adminSetupUrl($activeTab);
                redirect('index.php');
            }
        } else {
            $errorMessage = 'Codice fiscale non valido.';
        }
    } else {
        if (!$cfVerified) {
            http_response_code(403);
            $errorMessage = 'Verifica prima il codice fiscale del webmaster.';
        } else {
            requireLoginForAdminSetup($loginRequired);

            switch ($action) {
                case 'run_schema':
                    verifyCsrf();
                    $pdo = currentPdo();
                    if (!$pdo instanceof PDO) {
                        $errorMessage = 'Connessione al database non disponibile.';
                        break;
                    }
                    try {
                        $executed = runSchemaFromFile($pdo, EASYBOOKING_SCHEMA_FILE);
                        $schemaExists = safeSchemaExists();
                        $usersCount = $schemaExists ? safeUsersCount() : 0;
                        $loginRequired = $schemaExists && $usersCount > 0;
                        setFlash('success', 'Schema eseguito correttamente. Istruzioni SQL applicate: ' . $executed . '.');
                    } catch (Throwable $exception) {
                        setFlash('danger', $exception->getMessage());
                    }
                    redirect(adminSetupUrl('setup'));
                    break;

                case 'create_admin':
                    verifyCsrf();
                    $activeTab = 'setup';
                    if (!$schemaExists || !safeTableExists('users')) {
                        $errorMessage = 'Esegui prima la creazione della struttura database.';
                        break;
                    }

                    $username = cleanText(post('username'), 64);
                    $email = cleanEmailAddress(post('email'));
                    $password = (string)post('password');
                    $confirmPassword = (string)post('confirm_password');

                    if ($username === '' || $email === null || $password === '') {
                        $errorMessage = 'Compila tutti i campi obbligatori con valori validi.';
                        break;
                    }
                    if (!preg_match('/^[A-Za-z0-9._-]{3,64}$/', $username)) {
                        $errorMessage = 'Username non valido. Usa 3-64 caratteri alfanumerici, punto, trattino o underscore.';
                        break;
                    }
                    if (strlen($password) < 8) {
                        $errorMessage = 'La password deve contenere almeno 8 caratteri.';
                        break;
                    }
                    if (!hash_equals($password, $confirmPassword)) {
                        $errorMessage = 'Le password non coincidono.';
                        break;
                    }

                    $pdo = currentPdo();
                    if (!$pdo instanceof PDO) {
                        $errorMessage = 'Connessione al database non disponibile.';
                        break;
                    }

                    try {
                        $usersBefore = safeUsersCount();
                        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
                        $stmt->execute([$username, $email]);
                        if ($stmt->fetch()) {
                            $errorMessage = 'Esiste già un utente con questo username o email.';
                            break;
                        }

                        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                        $insert = $pdo->prepare(
                            'INSERT INTO users (username, email, password_hash, role, theme_preference, created_at)
                             VALUES (?, ?, ?, ?, ?, NOW())'
                        );
                        $insert->execute([$username, $email, $passwordHash, 'admin', 'dark']);
                        $newUserId = (int)$pdo->lastInsertId();

                        if (safeTableExists('system_config')) {
                            $cfg = $pdo->prepare(
                                "INSERT INTO system_config (`key`, `value`) VALUES ('setup_complete', '1')
                                 ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)"
                            );
                            $cfg->execute();
                        }

                        if ($usersBefore === 0) {
                            session_regenerate_id(true);
                            $_SESSION['user_id'] = $newUserId;
                            $_SESSION['username'] = $username;
                            $_SESSION['user_email'] = $email;
                            $_SESSION['user_role'] = 'admin';
                            $_SESSION['user_theme'] = 'dark';
                        }

                        setFlash('success', 'Utente amministratore creato correttamente.');
                        redirect(adminSetupUrl('setup'));
                    } catch (Throwable $exception) {
                        $errorMessage = 'Errore durante la creazione dell\'amministratore: ' . $exception->getMessage();
                    }
                    break;

                case 'generate_key':
                    verifyCsrf();
                    $activeTab = 'setup';
                    try {
                        $generatedKey = generateAndStoreEncryptionKey();
                        $_SESSION['generated_key_b64'] = $generatedKey;
                        setFlash('success', 'Chiave generata con successo.');
                    } catch (Throwable $exception) {
                        setFlash('danger', 'Errore nella generazione della chiave: ' . $exception->getMessage());
                    }
                    redirect(adminSetupUrl('setup'));
                    break;

                case 'reset_data':
                    verifyCsrf();
                    $activeTab = 'reset';
                    if (cleanText(post('reset_confirmation'), 10) !== 'RESET') {
                        $errorMessage = 'Digita RESET per confermare l\'operazione.';
                        break;
                    }
                    if (!$schemaExists) {
                        $errorMessage = 'La struttura database non è ancora disponibile.';
                        break;
                    }
                    $pdo = currentPdo();
                    if (!$pdo instanceof PDO) {
                        $errorMessage = 'Connessione al database non disponibile.';
                        break;
                    }
                    try {
                        $resetLog = [];
                        $count = resetApplicationData($pdo, $resetLog);
                        foreach ($resetLog as $message) {
                            setFlash('warning', $message);
                        }
                        setFlash('success', 'Reset completato. Tabelle azzerate: ' . $count . '.');
                        redirect(adminSetupUrl('reset'));
                    } catch (Throwable $exception) {
                        $errorMessage = 'Errore durante il reset: ' . $exception->getMessage();
                    }
                    break;

                case 'import_xml':
                    requireLoginForAdminSetup($loginRequired);
                    handleXmlImportAction();
                    break;
            }
        }
    }
}

$schemaExists = safeSchemaExists();
$usersCount = $schemaExists ? safeUsersCount() : 0;
$loginRequired = $schemaExists && $usersCount > 0;

if ($cfVerified) {
    requireLoginForAdminSetup($loginRequired);
}

$tableStatus = [];
foreach (expectedTables() as $table) {
    $exists = safeTableExists($table);
    $tableStatus[] = [
        'name' => $table,
        'exists' => $exists,
        'rows' => $exists ? safeTableCount($table) : null,
    ];
}

$currentEncryptionKeyBase64 = fetchStoredEncryptionKeyBase64();
$currentEncryptionKeyHex = fetchStoredEncryptionKeyHex();
$flashHtml = renderFlashMessages();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= h(csrfToken()) ?>">
    <title>Admin Setup – EasyBooking</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root {
            --bg-main: #1e1e2e;
            --bg-sidebar: #252537;
            --bg-card: #2a2a3e;
            --bg-input: #1e1e2e;
            --bg-table-row: #2a2a3e;
            --bg-table-hover: #33334d;
            --bg-navbar: #252537;
            --text-primary: #cdd6f4;
            --text-secondary: #a6adc8;
            --text-muted: #6c7086;
            --accent: #7c6af7;
            --accent-hover: #6a57e0;
            --accent-light: rgba(124, 106, 247, 0.15);
            --border-color: #363655;
            --shadow: 0 4px 24px rgba(0, 0, 0, 0.3);
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.2);
            --radius: 14px;
            --radius-sm: 8px;
            --success: #22c55e;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #38bdf8;
            --font: 'Inter', system-ui, -apple-system, sans-serif;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: var(--font);
            background:
                radial-gradient(circle at top right, rgba(124, 106, 247, 0.22), transparent 26%),
                linear-gradient(180deg, #171723 0%, #1e1e2e 100%);
            color: var(--text-primary);
        }
        a { color: var(--accent); }
        .setup-shell {
            max-width: 1220px;
            margin: 0 auto;
            padding: 40px 18px 56px;
        }
        .hero-card,
        .content-card,
        .stat-card,
        .warning-box,
        .log-panel {
            background: rgba(42, 42, 62, 0.92);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }
        .hero-card {
            padding: 28px;
            margin-bottom: 22px;
            position: relative;
            overflow: hidden;
        }
        .hero-card::after {
            content: '';
            position: absolute;
            inset: auto -70px -70px auto;
            width: 180px;
            height: 180px;
            background: radial-gradient(circle, rgba(124, 106, 247, 0.34), transparent 70%);
        }
        .hero-title {
            font-size: clamp(1.8rem, 3vw, 2.5rem);
            font-weight: 700;
            letter-spacing: -0.03em;
        }
        .hero-subtitle,
        .muted-text {
            color: var(--text-secondary);
        }
        .content-card {
            padding: 24px;
        }
        .nav-tabs {
            border-bottom: 1px solid var(--border-color);
            gap: 8px;
            flex-wrap: wrap;
        }
        .nav-tabs .nav-link {
            border: 1px solid transparent;
            border-radius: 999px;
            color: var(--text-secondary);
            background: transparent;
            padding: 10px 16px;
            font-weight: 600;
        }
        .nav-tabs .nav-link.active,
        .nav-tabs .nav-link:hover {
            color: #fff;
            border-color: rgba(124, 106, 247, 0.35);
            background: var(--accent-light);
        }
        .tab-pane {
            padding-top: 22px;
        }
        .section-title {
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 14px;
        }
        .form-control,
        .form-select,
        .form-check-input {
            background-color: var(--bg-input);
            border-color: var(--border-color);
            color: var(--text-primary);
        }
        .form-control:focus,
        .form-select:focus,
        .form-check-input:focus {
            background-color: var(--bg-input);
            color: var(--text-primary);
            border-color: rgba(124, 106, 247, 0.55);
            box-shadow: 0 0 0 0.2rem rgba(124, 106, 247, 0.2);
        }
        .form-control::placeholder { color: var(--text-muted); }
        .input-group-text {
            background: var(--bg-sidebar);
            border-color: var(--border-color);
            color: var(--text-secondary);
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--accent) 0%, #9b8cff 100%);
            border: none;
            box-shadow: 0 10px 30px rgba(124, 106, 247, 0.25);
        }
        .btn-primary:hover { background: linear-gradient(135deg, var(--accent-hover) 0%, #8471ff 100%); }
        .btn-outline-light,
        .btn-outline-warning,
        .btn-outline-danger,
        .btn-outline-info {
            border-color: var(--border-color);
        }
        .table {
            --bs-table-bg: transparent;
            --bs-table-color: var(--text-primary);
            --bs-table-border-color: var(--border-color);
            margin-bottom: 0;
        }
        .table tbody tr {
            background: rgba(255, 255, 255, 0.01);
        }
        .table tbody tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }
        .badge-soft {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(255,255,255,0.05);
            color: var(--text-primary);
            font-weight: 600;
        }
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }
        .stat-card {
            padding: 18px;
        }
        .stat-card .label {
            color: var(--text-muted);
            font-size: 0.88rem;
            margin-bottom: 8px;
        }
        .stat-card .value {
            font-size: 1.7rem;
            font-weight: 700;
        }
        .warning-box {
            padding: 18px;
            border-left: 4px solid var(--warning);
            background: rgba(245, 158, 11, 0.1);
        }
        .log-panel {
            padding: 16px;
            margin-top: 18px;
        }
        .log-output,
        .key-preview {
            min-height: 180px;
            max-height: 340px;
            overflow: auto;
            padding: 14px;
            border-radius: var(--radius-sm);
            background: #161621;
            border: 1px solid rgba(255,255,255,0.06);
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.86rem;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .progress {
            height: 12px;
            background: rgba(255, 255, 255, 0.07);
        }
        .progress-bar {
            background: linear-gradient(90deg, var(--accent) 0%, #9b8cff 100%);
        }
        .alert {
            border: none;
            border-radius: var(--radius-sm);
        }
        .cf-card {
            max-width: 520px;
            margin: 8vh auto 0;
        }
        .list-clean {
            padding-left: 1rem;
            color: var(--text-secondary);
        }
        .small-note {
            font-size: 0.84rem;
            color: var(--text-muted);
        }
        .status-icon-success { color: var(--success); }
        .status-icon-danger { color: var(--danger); }
        @media (max-width: 767.98px) {
            .setup-shell { padding-top: 20px; }
            .hero-card, .content-card { padding: 18px; }
        }
    </style>
</head>
<body>
<div class="setup-shell">
    <?php if (!$cfVerified): ?>
        <div class="hero-card cf-card text-center">
            <div class="mb-3"><i class="fa-solid fa-shield-halved fa-3x text-info"></i></div>
            <h1 class="hero-title mb-2">Verifica Webmaster</h1>
            <p class="hero-subtitle mb-4">Inserisci il codice fiscale del webmaster per accedere alla configurazione amministrativa.</p>
            <?php if ($errorMessage !== ''): ?>
                <div class="alert alert-danger text-start"><i class="fa-solid fa-circle-xmark me-2"></i><?= h($errorMessage) ?></div>
            <?php endif; ?>
            <?php if ($successMessage !== ''): ?>
                <div class="alert alert-success text-start"><i class="fa-solid fa-circle-check me-2"></i><?= h($successMessage) ?></div>
            <?php endif; ?>
            <form method="POST" action="<?= h(adminSetupUrl()) ?>" class="text-start">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="verify_cf">
                <div class="mb-3">
                    <label for="webmaster_cf" class="form-label">Codice Fiscale</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-id-card"></i></span>
                        <input type="text" id="webmaster_cf" name="webmaster_cf" class="form-control" maxlength="16" required autocomplete="off" placeholder="Inserisci CF webmaster">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa-solid fa-unlock-keyhole me-2"></i>Verifica accesso
                </button>
            </form>
        </div>
    <?php else: ?>
        <div class="hero-card">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <div class="badge-soft mb-3"><i class="fa-solid fa-sliders"></i> Pannello di configurazione iniziale</div>
                    <h1 class="hero-title mb-2">EasyBooking Admin Setup</h1>
                    <p class="hero-subtitle mb-0">Configura il database, crea l'amministratore, importa i dati XML e verifica la chiave di cifratura.</p>
                </div>
                <div class="text-lg-end">
                    <div class="small-note mb-2">Stato accesso</div>
                    <div class="badge-soft mb-2"><i class="fa-solid fa-shield-check"></i> CF verificato</div><br>
                    <div class="badge-soft"><i class="fa-solid fa-user-lock"></i> <?= $loginRequired ? 'Login richiesto' : 'Setup iniziale aperto' ?></div>
                </div>
            </div>
        </div>

        <div class="stat-grid">
            <div class="stat-card">
                <div class="label">Schema database</div>
                <div class="value"><?= $schemaExists ? 'Pronto' : 'Da inizializzare' ?></div>
                <div class="small-note mt-2">File schema: <?= h(basename(EASYBOOKING_SCHEMA_FILE)) ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Utenti registrati</div>
                <div class="value"><?= h((string)$usersCount) ?></div>
                <div class="small-note mt-2">L'accesso login scatta quando esiste almeno un utente.</div>
            </div>
            <div class="stat-card">
                <div class="label">Chiave cifratura</div>
                <div class="value"><?= $currentEncryptionKeyHex !== null ? 'Presente' : 'Assente' ?></div>
                <div class="small-note mt-2">Persistenza in <code>system_config</code>.</div>
            </div>
        </div>

        <div class="content-card">
            <?= $flashHtml ?>
            <?php if ($errorMessage !== ''): ?>
                <div class="alert alert-danger"><i class="fa-solid fa-circle-xmark me-2"></i><?= h($errorMessage) ?></div>
            <?php endif; ?>
            <?php if ($successMessage !== ''): ?>
                <div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i><?= h($successMessage) ?></div>
            <?php endif; ?>
            <?php if ($recentGeneratedKey !== ''): ?>
                <div class="alert alert-info">
                    <div class="fw-semibold mb-1"><i class="fa-solid fa-key me-2"></i>Chiave generata</div>
                    <code><?= h($recentGeneratedKey) ?></code>
                </div>
            <?php endif; ?>

            <ul class="nav nav-tabs" id="setupTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $activeTab === 'setup' ? 'active' : '' ?>" id="setup-tab" data-bs-toggle="tab" data-bs-target="#tab-setup" type="button" role="tab" data-tab="setup">
                        <i class="fa-solid fa-database me-2"></i>Setup Database
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $activeTab === 'verify' ? 'active' : '' ?>" id="verify-tab" data-bs-toggle="tab" data-bs-target="#tab-verify" type="button" role="tab" data-tab="verify">
                        <i class="fa-solid fa-list-check me-2"></i>Verifica Database
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $activeTab === 'import' ? 'active' : '' ?>" id="import-tab" data-bs-toggle="tab" data-bs-target="#tab-import" type="button" role="tab" data-tab="import">
                        <i class="fa-solid fa-file-arrow-up me-2"></i>Importa XML
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $activeTab === 'reset' ? 'active' : '' ?>" id="reset-tab" data-bs-toggle="tab" data-bs-target="#tab-reset" type="button" role="tab" data-tab="reset">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>Reset Dati
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $activeTab === 'key' ? 'active' : '' ?>" id="key-tab" data-bs-toggle="tab" data-bs-target="#tab-key" type="button" role="tab" data-tab="key">
                        <i class="fa-solid fa-key me-2"></i>Chiave Cifratura
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="setupTabsContent">
                <div class="tab-pane fade <?= $activeTab === 'setup' ? 'show active' : '' ?>" id="tab-setup" role="tabpanel" aria-labelledby="setup-tab">
                    <div class="row g-4">
                        <div class="col-12 col-xl-5">
                            <div class="section-title">Struttura database</div>
                            <p class="muted-text">Applica o aggiorna lo schema completo definito in <code>database-schema.sql</code>.</p>
                            <form method="POST" action="<?= h(adminSetupUrl('setup')) ?>" class="mb-3">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="run_schema">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fa-solid fa-wand-magic-sparkles me-2"></i>Crea/Aggiorna struttura DB
                                </button>
                            </form>

                            <div class="section-title mt-4">Chiave di cifratura</div>
                            <p class="muted-text">Genera una nuova chiave AES-256 e salvala in <code>system_config</code>.</p>
                            <form method="POST" action="<?= h(adminSetupUrl('setup')) ?>">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="generate_key">
                                <button type="submit" class="btn btn-outline-info w-100">
                                    <i class="fa-solid fa-key me-2"></i>Genera chiave cifratura
                                </button>
                            </form>
                        </div>
                        <div class="col-12 col-xl-7">
                            <div class="section-title">Crea primo amministratore</div>
                            <p class="muted-text">Usa questo form per inizializzare un account admin sicuro.</p>
                            <form method="POST" action="<?= h(adminSetupUrl('setup')) ?>">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="create_admin">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="username">Username</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                                            <input type="text" class="form-control" id="username" name="username" maxlength="64" required value="<?= h(post('username')) ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="email">Email</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                                            <input type="email" class="form-control" id="email" name="email" maxlength="255" required value="<?= h(post('email')) ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="password">Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                                            <input type="password" class="form-control" id="password" name="password" minlength="8" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="confirm_password">Conferma password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="small-note mt-3">La password viene salvata con hashing bcrypt.</div>
                                <button type="submit" class="btn btn-primary mt-3">
                                    <i class="fa-solid fa-user-shield me-2"></i>Crea amministratore
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade <?= $activeTab === 'verify' ? 'show active' : '' ?>" id="tab-verify" role="tabpanel" aria-labelledby="verify-tab">
                    <div class="section-title">Stato tabelle attese</div>
                    <p class="muted-text">Verifica la presenza delle tabelle principali e il numero di record tramite <code>Database::countRows()</code>.</p>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Tabella</th>
                                    <th>Stato</th>
                                    <th class="text-end">Record</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($tableStatus as $table): ?>
                                <tr>
                                    <td><code><?= h($table['name']) ?></code></td>
                                    <td>
                                        <?php if ($table['exists']): ?>
                                            <span class="status-icon-success"><i class="fa-solid fa-circle-check me-2"></i>Presente</span>
                                        <?php else: ?>
                                            <span class="status-icon-danger"><i class="fa-solid fa-circle-xmark me-2"></i>Assente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end"><?= $table['exists'] ? h((string)$table['rows']) : '—' ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade <?= $activeTab === 'import' ? 'show active' : '' ?>" id="tab-import" role="tabpanel" aria-labelledby="import-tab">
                    <div class="row g-4">
                        <div class="col-12 col-xl-7">
                            <div class="section-title">Importazione XML cifrati</div>
                            <p class="muted-text">Carica uno o più file esportati dal gestionale desktop. I file vengono decrittati in memoria/progetto, validati e importati con query preparate.</p>
                            <form id="xmlImportForm" method="POST" action="<?= h(adminSetupUrl('import')) ?>" enctype="multipart/form-data">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="import_xml">
                                <div class="mb-3">
                                    <label for="xml_files" class="form-label">File XML</label>
                                    <input type="file" class="form-control" id="xml_files" name="xml_files[]" accept=".xml" multiple required>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label d-block">Modalità record</label>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="replace_mode" id="mode_update" value="update" checked>
                                            <label class="form-check-label" for="mode_update">Aggiorna (upsert senza cancellare)</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="replace_mode" id="mode_replace" value="replace">
                                            <label class="form-check-label" for="mode_replace">Sostituisci (svuota le tabelle coinvolte)</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="processing_mode">Selettore modalità import</label>
                                        <select class="form-select" id="processing_mode" name="processing_mode">
                                            <option value="atomic" selected>Transazione unica (consigliato)</option>
                                            <option value="continue">Continua su errore per file</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="small-note mt-3">In modalità <strong>Sostituisci</strong> vengono svuotate solo le tabelle dipendenti dai file caricati.</div>
                                <button type="submit" class="btn btn-primary mt-3" id="importSubmitBtn">
                                    <i class="fa-solid fa-upload me-2"></i>Importa file XML
                                </button>
                            </form>
                        </div>
                        <div class="col-12 col-xl-5">
                            <div class="section-title">File supportati</div>
                            <ul class="list-clean mb-0">
                                <?php foreach (expectedXmlFiles() as $fileName => $label): ?>
                                    <li><code><?= h($fileName) ?></code> — <?= h($label) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <div class="log-panel mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="section-title mb-0">Avanzamento importazione</div>
                            <span id="importStatusText" class="small-note">In attesa di import.</span>
                        </div>
                        <div class="progress mb-3" role="progressbar" aria-label="Import progress" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" id="importProgressBar" style="width: 0%">0%</div>
                        </div>
                        <div id="importErrors" class="alert alert-danger d-none"></div>
                        <div class="log-output" id="importLogOutput">Nessuna operazione eseguita.</div>
                    </div>
                </div>

                <div class="tab-pane fade <?= $activeTab === 'reset' ? 'show active' : '' ?>" id="tab-reset" role="tabpanel" aria-labelledby="reset-tab">
                    <div class="warning-box mb-4">
                        <div class="fw-semibold mb-2"><i class="fa-solid fa-triangle-exclamation me-2"></i>Attenzione</div>
                        <p class="mb-0">Questa azione elimina tutti i dati operativi da <code>clienti</code>, <code>insegnanti</code>, <code>strumenti</code>, <code>pacchetti</code>, <code>acquisti</code>, <code>prenotazioni</code>, <code>tariffe_coppia</code> e <code>insegnanti_strumenti</code>. <strong>Non</strong> tocca <code>users</code> né <code>system_config</code>.</p>
                    </div>
                    <form method="POST" action="<?= h(adminSetupUrl('reset')) ?>" class="row g-3 align-items-end">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="reset_data">
                        <div class="col-md-5">
                            <label class="form-label" for="reset_confirmation">Conferma digitando RESET</label>
                            <input type="text" class="form-control" id="reset_confirmation" name="reset_confirmation" maxlength="10" required placeholder="RESET">
                        </div>
                        <div class="col-md-auto">
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="fa-solid fa-trash-can me-2"></i>Tronca dati applicativi
                            </button>
                        </div>
                    </form>
                </div>

                <div class="tab-pane fade <?= $activeTab === 'key' ? 'show active' : '' ?>" id="tab-key" role="tabpanel" aria-labelledby="key-tab">
                    <div class="section-title">Chiave corrente in chiaro</div>
                    <p class="muted-text">Valore letto da <code>system_config.encryption_key</code> e mostrato come esadecimale del contenuto base64 decodificato.</p>
                    <div class="row g-4">
                        <div class="col-12 col-lg-6">
                            <div class="small-note mb-2">Base64 salvato</div>
                            <div class="key-preview"><?= $currentEncryptionKeyBase64 !== null && $currentEncryptionKeyBase64 !== '' ? h($currentEncryptionKeyBase64) : 'Nessuna chiave salvata.' ?></div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="small-note mb-2">Hex decodificato</div>
                            <div class="key-preview"><?= $currentEncryptionKeyHex !== null ? h($currentEncryptionKeyHex) : 'Nessuna chiave disponibile.' ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
    const tabs = document.querySelectorAll('#setupTabs button[data-tab]');
    tabs.forEach((tab) => {
        tab.addEventListener('shown.bs.tab', () => {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab.dataset.tab);
            window.history.replaceState({}, '', url.toString());
        });
    });

    const form = document.getElementById('xmlImportForm');
    if (!form) {
        return;
    }

    const submitBtn = document.getElementById('importSubmitBtn');
    const progressBar = document.getElementById('importProgressBar');
    const logOutput = document.getElementById('importLogOutput');
    const errorsBox = document.getElementById('importErrors');
    const statusText = document.getElementById('importStatusText');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const setProgress = (value, label) => {
        const normalized = Math.max(0, Math.min(100, Math.round(value)));
        progressBar.style.width = normalized + '%';
        progressBar.textContent = normalized + '%';
        progressBar.setAttribute('aria-valuenow', String(normalized));
        if (label) {
            statusText.textContent = label;
        }
    };

    const renderLines = (lines) => {
        if (!Array.isArray(lines) || lines.length === 0) {
            logOutput.textContent = 'Nessun log disponibile.';
            return;
        }
        logOutput.textContent = lines.join("\n");
    };

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        errorsBox.classList.add('d-none');
        errorsBox.textContent = '';
        logOutput.textContent = 'Preparazione upload...';
        setProgress(5, 'Avvio caricamento file...');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Importazione in corso';

        const xhr = new XMLHttpRequest();
        xhr.open('POST', form.getAttribute('action') || 'adminsetup.php?tab=import', true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        if (csrfToken) {
            xhr.setRequestHeader('X-CSRF-Token', csrfToken);
        }

        xhr.upload.addEventListener('progress', (e) => {
            if (!e.lengthComputable) {
                setProgress(30, 'Caricamento file...');
                return;
            }
            const percent = (e.loaded / e.total) * 70;
            setProgress(percent, 'Upload in corso...');
        });

        xhr.onreadystatechange = () => {
            if (xhr.readyState === XMLHttpRequest.HEADERS_RECEIVED) {
                setProgress(80, 'Elaborazione server...');
            }
            if (xhr.readyState !== XMLHttpRequest.DONE) {
                return;
            }

            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-upload me-2"></i>Importa file XML';

            let response;
            try {
                response = JSON.parse(xhr.responseText);
            } catch (error) {
                setProgress(100, 'Risposta non valida');
                errorsBox.textContent = 'Impossibile interpretare la risposta del server.';
                errorsBox.classList.remove('d-none');
                logOutput.textContent = xhr.responseText || 'Nessun dettaglio disponibile.';
                return;
            }

            renderLines(response.log || []);
            if (response.success) {
                setProgress(100, 'Importazione completata');
            } else {
                setProgress(100, 'Importazione completata con errori');
            }

            const errorLines = Array.isArray(response.errors) ? response.errors.slice() : [];
            if (!response.success && response.message) {
                errorLines.push(response.message);
            }
            if (errorLines.length > 0) {
                errorsBox.replaceChildren(...errorLines.map((item) => {
                    const div = document.createElement('div');
                    div.textContent = item;
                    return div;
                }));
                errorsBox.classList.remove('d-none');
            }
        };

        xhr.onerror = () => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-upload me-2"></i>Importa file XML';
            setProgress(100, 'Errore di rete');
            errorsBox.textContent = 'Errore di rete durante l\'importazione.';
            errorsBox.classList.remove('d-none');
            logOutput.textContent = 'La richiesta AJAX non è andata a buon fine.';
        };

        xhr.send(new FormData(form));
    });
})();
</script>
</body>
</html>

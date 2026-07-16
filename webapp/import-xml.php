<?php
ob_start();
ini_set('log_errors', '1');
ini_set('display_errors', '0');
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/encryption.php';
require_once __DIR__ . '/includes/auth.php';
requireAuth();
requireAdmin();
$pdo = Database::getInstance();

const EASYBOOKING_XML_IMPORT_DIR = __DIR__ . '/.xml-import-runtime';

function h(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function xmlImportCleanText(mixed $value, int $maxLength = 0): string { $value = trim((string)$value); $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? ''; return $maxLength > 0 ? mb_substr($value, 0, $maxLength) : $value; }
function xmlImportNullableText(mixed $value, int $maxLength = 0): ?string { $value = xmlImportCleanText($value, $maxLength); return $value === '' ? null : $value; }
function xmlImportEmail(mixed $value): ?string { $email = filter_var(trim((string)$value), FILTER_SANITIZE_EMAIL); return ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) ? $email : null; }
function xmlImportBool(mixed $value): int { return in_array(strtolower(xmlImportCleanText($value)), ['1', 'true', 'yes', 'si', 'sì'], true) ? 1 : 0; }
function xmlImportInt(mixed $value): int { return (int)xmlImportCleanText($value); }
function xmlImportFloat(mixed $value): float { $value = str_replace(',', '.', xmlImportCleanText($value)); return is_numeric($value) ? (float)$value : 0.0; }
function xmlImportDate(mixed $value): ?string { $value = xmlImportCleanText($value, 25); if ($value === '') return null; foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'] as $format) { $date = DateTime::createFromFormat($format, $value); if ($date instanceof DateTime) return $date->format('Y-m-d'); } try { return (new DateTime($value))->format('Y-m-d'); } catch (Throwable) { return null; } }
function xmlImportTime(mixed $value): ?string { $value = xmlImportCleanText($value, 12); if ($value === '') return null; foreach (['H:i:s', 'H:i'] as $format) { $time = DateTime::createFromFormat($format, $value); if ($time instanceof DateTime) return $time->format('H:i:s'); } return null; }
function xmlImportProcessingOrder(): array { return ['strumenti', 'clienti', 'insegnanti', 'pacchetti', 'acquisti', 'prenotazioni', 'impostazioni_generali', 'tariffe_coppia']; }
function xmlImportHandlers(): array { return ['clienti' => 'xmlImportClienti', 'insegnanti' => 'xmlImportInsegnanti', 'prenotazioni' => 'xmlImportPrenotazioni', 'acquisti' => 'xmlImportAcquisti', 'pacchetti' => 'xmlImportPacchetti', 'strumenti' => 'xmlImportStrumenti', 'impostazioni_generali' => 'xmlImportImpostazioniGenerali', 'tariffe_coppia' => 'xmlImportTariffeCoppia']; }

function xmlImportSplitTeacherInstruments(SimpleXMLElement $node): array {
    $names = [];
    if (!isset($node->Strumenti)) return [];
    if ($node->Strumenti->count() > 0) {
        foreach ($node->Strumenti->children() as $child) { $value = xmlImportCleanText((string)$child, 100); if ($value !== '') $names[] = $value; }
    } else {
        foreach (preg_split('/\s*[,;|]\s*/', xmlImportCleanText((string)$node->Strumenti)) ?: [] as $piece) { $piece = xmlImportCleanText($piece, 100); if ($piece !== '') $names[] = $piece; }
    }
    return array_values(array_unique($names));
}

function xmlImportEnsureRuntimeDir(): string { if (!is_dir(EASYBOOKING_XML_IMPORT_DIR) && !mkdir(EASYBOOKING_XML_IMPORT_DIR, 0700, true) && !is_dir(EASYBOOKING_XML_IMPORT_DIR)) throw new RuntimeException('Impossibile creare la directory di importazione XML.'); return EASYBOOKING_XML_IMPORT_DIR; }
function xmlImportMoveUpload(array $file): string { $dir = xmlImportEnsureRuntimeDir(); $safe = preg_replace('/[^a-zA-Z0-9._-]/', '-', basename((string)($file['name'] ?? 'upload.xml'))) ?: 'upload.xml'; $target = $dir . '/' . date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '_' . $safe; if (!move_uploaded_file((string)$file['tmp_name'], $target)) throw new RuntimeException('Impossibile spostare il file caricato ' . $safe . '.'); @chmod($target, 0600); return $target; }
function xmlImportLoadContent(string $path, string $originalName, array &$log): string { $content = decryptXMLFile($path); if ($content !== false) { $content = fixXMLEncoding($content); if (str_starts_with(ltrim($content), '<')) { $log[] = '🔐 ' . $originalName . ': decrittazione completata'; return $content; } } $raw = file_get_contents($path); if ($raw === false) throw new RuntimeException('Impossibile leggere il file ' . $originalName . '.'); $raw = fixXMLEncoding($raw); if (!str_starts_with(ltrim($raw), '<')) throw new RuntimeException('Il file ' . $originalName . ' non contiene XML valido.'); $log[] = 'ℹ️ ' . $originalName . ': importato come XML non cifrato'; return $raw; }
function xmlImportParseDocument(string $content, string $name): SimpleXMLElement { libxml_use_internal_errors(true); $xml = simplexml_load_string($content, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA); if ($xml === false) { $errors = []; foreach (libxml_get_errors() as $error) $errors[] = trim($error->message); libxml_clear_errors(); throw new RuntimeException('XML non valido per ' . $name . ': ' . implode('; ', $errors)); } libxml_clear_errors(); return $xml; }
function xmlImportDetectType(SimpleXMLElement $xml, string $fileName): ?string { $root = strtolower($xml->getName()); $byRoot = ['clienti' => 'clienti', 'insegnanti' => 'insegnanti', 'prenotazioni' => 'prenotazioni', 'acquisti' => 'acquisti', 'pacchetti' => 'pacchetti', 'strumentilist' => 'strumenti', 'tariffedicoppia' => 'tariffe_coppia', 'impostazionigenerali' => 'impostazioni_generali']; if (isset($byRoot[$root])) return $byRoot[$root]; $byFile = ['clienti.xml' => 'clienti', 'insegnanti.xml' => 'insegnanti', 'prenotazioni.xml' => 'prenotazioni', 'acquisti.xml' => 'acquisti', 'pacchetti.xml' => 'pacchetti', 'strumenti.xml' => 'strumenti', 'impostazioni-generali.xml' => 'impostazioni_generali', 'tariffe_coppia.xml' => 'tariffe_coppia']; return $byFile[strtolower(basename($fileName))] ?? null; }
function xmlImportEnsureInstrumentByName(PDO $pdo, string $name, array &$log): int { $name = xmlImportCleanText($name, 100); if ($name === '') throw new RuntimeException('Nome strumento non valido.'); $stmt = $pdo->prepare('SELECT id FROM strumenti WHERE nome = ? LIMIT 1'); $stmt->execute([$name]); $id = $stmt->fetchColumn(); if ($id !== false) return (int)$id; $stmt = $pdo->prepare('INSERT INTO strumenti (nome, lun_attivo, mar_attivo, mer_attivo, gio_attivo, ven_attivo, sab_attivo, dom_attivo, matt_inizio, matt_fine, pom_inizio, pom_fine) VALUES (?, 1, 1, 1, 1, 1, 0, 0, "09:00:00", "13:00:00", "15:00:00", "19:00:00")'); $stmt->execute([$name]); $log[] = 'ℹ️ Creato strumento mancante: ' . $name; return (int)$pdo->lastInsertId(); }

function xmlImportClienti(PDO $pdo, SimpleXMLElement $xml, string $mode, array &$log): int {
    $sql = 'INSERT INTO clienti (id, nome, cognome, telefono, email, indirizzo, codice_fiscale, note, mega_cartella_pubblica, mega_cartella_locale) VALUES (:id, :nome, :cognome, :telefono, :email, :indirizzo, :codice_fiscale, :note, :mega_pubblica, :mega_locale)';
    if ($mode === 'update') $sql .= ' ON DUPLICATE KEY UPDATE nome = VALUES(nome), cognome = VALUES(cognome), telefono = VALUES(telefono), email = VALUES(email), indirizzo = VALUES(indirizzo), codice_fiscale = VALUES(codice_fiscale), note = VALUES(note), mega_cartella_pubblica = VALUES(mega_cartella_pubblica), mega_cartella_locale = VALUES(mega_cartella_locale)';
    $stmt = $pdo->prepare($sql); $count = 0;
    foreach ($xml->Cliente as $cliente) { $id = xmlImportInt($cliente->Id ?? '0'); if ($id <= 0) { $log[] = '⚠️ Cliente ignorato: Id non valido'; continue; } $stmt->execute([':id' => $id, ':nome' => xmlImportCleanText($cliente->Nome ?? '', 100), ':cognome' => xmlImportCleanText($cliente->Cognome ?? '', 100), ':telefono' => xmlImportNullableText($cliente->Telefono ?? '', 50), ':email' => xmlImportEmail($cliente->Email ?? ''), ':indirizzo' => xmlImportNullableText($cliente->Indirizzo ?? ''), ':codice_fiscale' => xmlImportNullableText($cliente->CodiceFiscale ?? '', 50), ':note' => xmlImportNullableText($cliente->Note ?? ''), ':mega_pubblica' => xmlImportNullableText($cliente->MegaCartellaPubblica ?? ''), ':mega_locale' => xmlImportNullableText($cliente->MegaCartellaLocale ?? '')]); $count++; }
    $log[] = '✅ Clienti importati: ' . $count; return $count;
}
function xmlImportInsegnanti(PDO $pdo, SimpleXMLElement $xml, string $mode, array &$log): int {
    $sql = 'INSERT INTO insegnanti (id, nome, cognome, telefono, email, tariffa_oraria) VALUES (:id, :nome, :cognome, :telefono, :email, :tariffa)';
    if ($mode === 'update') $sql .= ' ON DUPLICATE KEY UPDATE nome = VALUES(nome), cognome = VALUES(cognome), telefono = VALUES(telefono), email = VALUES(email), tariffa_oraria = VALUES(tariffa_oraria)';
    $stmt = $pdo->prepare($sql); $deleteLinks = $pdo->prepare('DELETE FROM insegnanti_strumenti WHERE insegnante_id = ?'); $insertLink = $pdo->prepare('INSERT IGNORE INTO insegnanti_strumenti (insegnante_id, strumento_id) VALUES (?, ?)'); $count = 0;
    foreach ($xml->Insegnante as $insegnante) { $id = xmlImportInt($insegnante->Id ?? '0'); if ($id <= 0) { $log[] = '⚠️ Insegnante ignorato: Id non valido'; continue; } $stmt->execute([':id' => $id, ':nome' => xmlImportCleanText($insegnante->Nome ?? '', 100), ':cognome' => xmlImportCleanText($insegnante->Cognome ?? '', 100), ':telefono' => xmlImportNullableText($insegnante->Telefono ?? '', 50), ':email' => xmlImportEmail($insegnante->Email ?? ''), ':tariffa' => xmlImportFloat($insegnante->TariffaOraria ?? '0')]); $deleteLinks->execute([$id]); foreach (xmlImportSplitTeacherInstruments($insegnante) as $name) { $strumentoId = xmlImportEnsureInstrumentByName($pdo, $name, $log); $insertLink->execute([$id, $strumentoId]); } $count++; }
    $log[] = '✅ Insegnanti importati: ' . $count; return $count;
}
function xmlImportPrenotazioni(PDO $pdo, SimpleXMLElement $xml, string $mode, array &$log): int {
    // Load valid acquisto IDs to prevent FK violations when acquisti are not in this import batch
    $validAcquistoIds = [];
    try {
        $stmtA = $pdo->query('SELECT id FROM acquisti');
        foreach ($stmtA->fetchAll(PDO::FETCH_COLUMN) as $aid) {
            $validAcquistoIds[(int)$aid] = true;
        }
    } catch (PDOException $e) {
        $log[] = '⚠️ Impossibile verificare acquisto_id: tutti saranno impostati a NULL';
    }
    $sql = 'INSERT INTO prenotazioni (id, data, ora_inizio, ora_fine, cliente_id, insegnante_id, strumento, stato, pacchetto_nome, acquisto_id, note) VALUES (:id, :data, :ora_inizio, :ora_fine, :cliente_id, :insegnante_id, :strumento, :stato, :pacchetto_nome, :acquisto_id, :note)';
    if ($mode === 'update') $sql .= ' ON DUPLICATE KEY UPDATE data = VALUES(data), ora_inizio = VALUES(ora_inizio), ora_fine = VALUES(ora_fine), cliente_id = VALUES(cliente_id), insegnante_id = VALUES(insegnante_id), strumento = VALUES(strumento), stato = VALUES(stato), pacchetto_nome = VALUES(pacchetto_nome), acquisto_id = VALUES(acquisto_id), note = VALUES(note)';
    $stmt = $pdo->prepare($sql); $count = 0; $nullifiedCount = 0;
    foreach ($xml->Prenotazione as $item) { $id = xmlImportInt($item->Id ?? '0'); $date = xmlImportDate($item->Data ?? ''); $start = xmlImportTime($item->OraInizioStr ?? ''); $end = xmlImportTime($item->OraFineStr ?? ''); if ($id <= 0 || !$date || !$start || !$end) { $log[] = '⚠️ Prenotazione ignorata: dati obbligatori mancanti'; continue; } $acquistoId = xmlImportInt($item->AcquistoId ?? '0'); if ($acquistoId > 0 && !isset($validAcquistoIds[$acquistoId])) { $acquistoId = 0; $nullifiedCount++; } $stmt->execute([':id' => $id, ':data' => $date, ':ora_inizio' => $start, ':ora_fine' => $end, ':cliente_id' => xmlImportInt($item->ClienteId ?? '0'), ':insegnante_id' => xmlImportInt($item->InsegnanteId ?? '0'), ':strumento' => xmlImportNullableText($item->Strumento ?? '', 100), ':stato' => xmlImportCleanText($item->Stato ?? 'Programmata', 30) ?: 'Programmata', ':pacchetto_nome' => xmlImportNullableText($item->PacchettoNome ?? '', 150), ':acquisto_id' => $acquistoId > 0 ? $acquistoId : null, ':note' => xmlImportNullableText($item->Note ?? '')]); $count++; }
    if ($nullifiedCount > 0) $log[] = '⚠️ ' . $nullifiedCount . ' acquisto_id non trovati → impostati a NULL (importare acquisti.xml per ripristinare i collegamenti)';
    $log[] = '✅ Prenotazioni importate: ' . $count; return $count;
}
function xmlImportAcquisti(PDO $pdo, SimpleXMLElement $xml, string $mode, array &$log): int {
    $sql = 'INSERT INTO acquisti (id, data_acquisto, cliente_id, pacchetto_id, importo_pagato, stato_pagamento, pianificato, numero_fattura, note, numero_lezioni) VALUES (:id, :data_acquisto, :cliente_id, :pacchetto_id, :importo_pagato, :stato_pagamento, :pianificato, :numero_fattura, :note, :numero_lezioni)';
    if ($mode === 'update') $sql .= ' ON DUPLICATE KEY UPDATE data_acquisto = VALUES(data_acquisto), cliente_id = VALUES(cliente_id), pacchetto_id = VALUES(pacchetto_id), importo_pagato = VALUES(importo_pagato), stato_pagamento = VALUES(stato_pagamento), pianificato = VALUES(pianificato), numero_fattura = VALUES(numero_fattura), note = VALUES(note), numero_lezioni = VALUES(numero_lezioni)';
    $stmt = $pdo->prepare($sql); $count = 0;
    foreach ($xml->Acquisto as $item) { $id = xmlImportInt($item->Id ?? '0'); $date = xmlImportDate($item->DataAcquisto ?? ''); if ($id <= 0 || !$date) { $log[] = '⚠️ Acquisto ignorato: dati obbligatori mancanti'; continue; } $pacchettoId = xmlImportInt($item->PacchettoId ?? '0'); $stmt->execute([':id' => $id, ':data_acquisto' => $date, ':cliente_id' => xmlImportInt($item->ClienteId ?? '0'), ':pacchetto_id' => $pacchettoId > 0 ? $pacchettoId : null, ':importo_pagato' => xmlImportFloat($item->ImportoPagato ?? '0'), ':stato_pagamento' => xmlImportCleanText($item->StatoPagamento ?? '', 50), ':pianificato' => xmlImportBool($item->Pianificato ?? 'false'), ':numero_fattura' => xmlImportNullableText($item->NumeroFattura ?? '', 100), ':note' => xmlImportNullableText($item->Note ?? ''), ':numero_lezioni' => xmlImportInt($item->NumeroLezioni ?? '0')]); $count++; }
    $log[] = '✅ Acquisti importati: ' . $count; return $count;
}
function xmlImportPacchetti(PDO $pdo, SimpleXMLElement $xml, string $mode, array &$log): int {
    $sql = 'INSERT INTO pacchetti (id, nome, descrizione, numero_lezioni, durata_minuti, frequenza, prezzo, strumento) VALUES (:id, :nome, :descrizione, :numero_lezioni, :durata_minuti, :frequenza, :prezzo, :strumento)';
    if ($mode === 'update') $sql .= ' ON DUPLICATE KEY UPDATE nome = VALUES(nome), descrizione = VALUES(descrizione), numero_lezioni = VALUES(numero_lezioni), durata_minuti = VALUES(durata_minuti), frequenza = VALUES(frequenza), prezzo = VALUES(prezzo), strumento = VALUES(strumento)';
    $stmt = $pdo->prepare($sql); $count = 0;
    foreach ($xml->Pacchetto as $item) { $id = xmlImportInt($item->Id ?? '0'); if ($id <= 0) { $log[] = '⚠️ Pacchetto ignorato: Id non valido'; continue; } $stmt->execute([':id' => $id, ':nome' => xmlImportCleanText($item->Nome ?? '', 150), ':descrizione' => xmlImportNullableText($item->Descrizione ?? ''), ':numero_lezioni' => xmlImportInt($item->NumeroLezioni ?? '0'), ':durata_minuti' => xmlImportInt($item->DurataMinuti ?? '60'), ':frequenza' => xmlImportNullableText($item->Frequenza ?? '', 100), ':prezzo' => xmlImportFloat($item->Prezzo ?? '0'), ':strumento' => xmlImportNullableText($item->Strumento ?? '', 100)]); $count++; }
    $log[] = '✅ Pacchetti importati: ' . $count; return $count;
}
function xmlImportStrumenti(PDO $pdo, SimpleXMLElement $xml, string $mode, array &$log): int {
    $sql = 'INSERT INTO strumenti (id, nome, lun_attivo, mar_attivo, mer_attivo, gio_attivo, ven_attivo, sab_attivo, dom_attivo, matt_inizio, matt_fine, pom_inizio, pom_fine) VALUES (:id, :nome, :lun, :mar, :mer, :gio, :ven, :sab, :dom, :matt_inizio, :matt_fine, :pom_inizio, :pom_fine)';
    if ($mode === 'update') $sql .= ' ON DUPLICATE KEY UPDATE nome = VALUES(nome), lun_attivo = VALUES(lun_attivo), mar_attivo = VALUES(mar_attivo), mer_attivo = VALUES(mer_attivo), gio_attivo = VALUES(gio_attivo), ven_attivo = VALUES(ven_attivo), sab_attivo = VALUES(sab_attivo), dom_attivo = VALUES(dom_attivo), matt_inizio = VALUES(matt_inizio), matt_fine = VALUES(matt_fine), pom_inizio = VALUES(pom_inizio), pom_fine = VALUES(pom_fine)';
    $stmt = $pdo->prepare($sql); $count = 0;
    foreach ($xml->Strumento as $item) { $id = xmlImportInt($item->Id ?? '0'); if ($id <= 0) { $log[] = '⚠️ Strumento ignorato: Id non valido'; continue; } $stmt->execute([':id' => $id, ':nome' => xmlImportCleanText($item->Nome ?? '', 100), ':lun' => xmlImportBool($item->LunAttivo ?? 'false'), ':mar' => xmlImportBool($item->MarAttivo ?? 'false'), ':mer' => xmlImportBool($item->MerAttivo ?? 'false'), ':gio' => xmlImportBool($item->GioAttivo ?? 'false'), ':ven' => xmlImportBool($item->VenAttivo ?? 'false'), ':sab' => xmlImportBool($item->SabAttivo ?? 'false'), ':dom' => xmlImportBool($item->DomAttivo ?? 'false'), ':matt_inizio' => xmlImportTime($item->MattInizio ?? '') ?? '09:00:00', ':matt_fine' => xmlImportTime($item->MattFine ?? '') ?? '13:00:00', ':pom_inizio' => xmlImportTime($item->PomInizio ?? '') ?? '15:00:00', ':pom_fine' => xmlImportTime($item->PomFine ?? '') ?? '19:00:00']); $count++; }
    $log[] = '✅ Strumenti importati: ' . $count; return $count;
}
function xmlImportImpostazioniGenerali(PDO $pdo, SimpleXMLElement $xml, string $mode, array &$log): int { $stmt = $pdo->prepare('INSERT INTO impostazioni_generali (id, lun_attivo, mar_attivo, mer_attivo, gio_attivo, ven_attivo, sab_attivo, dom_attivo, matt_inizio, matt_fine, pom_inizio, pom_fine, durata_lezione_default) VALUES (1, :lun, :mar, :mer, :gio, :ven, :sab, :dom, :matt_inizio, :matt_fine, :pom_inizio, :pom_fine, :durata) ON DUPLICATE KEY UPDATE lun_attivo = VALUES(lun_attivo), mar_attivo = VALUES(mar_attivo), mer_attivo = VALUES(mer_attivo), gio_attivo = VALUES(gio_attivo), ven_attivo = VALUES(ven_attivo), sab_attivo = VALUES(sab_attivo), dom_attivo = VALUES(dom_attivo), matt_inizio = VALUES(matt_inizio), matt_fine = VALUES(matt_fine), pom_inizio = VALUES(pom_inizio), pom_fine = VALUES(pom_fine), durata_lezione_default = VALUES(durata_lezione_default)'); $stmt->execute([':lun' => xmlImportBool($xml->LunAttivo ?? 'false'), ':mar' => xmlImportBool($xml->MarAttivo ?? 'false'), ':mer' => xmlImportBool($xml->MerAttivo ?? 'false'), ':gio' => xmlImportBool($xml->GioAttivo ?? 'false'), ':ven' => xmlImportBool($xml->VenAttivo ?? 'false'), ':sab' => xmlImportBool($xml->SabAttivo ?? 'false'), ':dom' => xmlImportBool($xml->DomAttivo ?? 'false'), ':matt_inizio' => xmlImportTime($xml->MattInizio ?? '') ?? '09:00:00', ':matt_fine' => xmlImportTime($xml->MattFine ?? '') ?? '13:00:00', ':pom_inizio' => xmlImportTime($xml->PomInizio ?? '') ?? '15:00:00', ':pom_fine' => xmlImportTime($xml->PomFine ?? '') ?? '19:00:00', ':durata' => xmlImportInt($xml->DurataLezioneDefault ?? '60')]); $log[] = '✅ Impostazioni generali importate'; return 1; }
function xmlImportTariffeCoppia(PDO $pdo, SimpleXMLElement $xml, string $mode, array &$log): int { $sql = 'INSERT INTO tariffe_coppia (insegnante_id, tariffa) VALUES (:insegnante_id, :tariffa)'; if ($mode === 'update') $sql .= ' ON DUPLICATE KEY UPDATE tariffa = VALUES(tariffa)'; $stmt = $pdo->prepare($sql); $count = 0; foreach ($xml->Tariffa as $item) { $insegnanteId = xmlImportInt($item->InsegnanteId ?? '0'); if ($insegnanteId <= 0) { $log[] = '⚠️ Tariffa coppia ignorata: InsegnanteId non valido'; continue; } $stmt->execute([':insegnante_id' => $insegnanteId, ':tariffa' => xmlImportFloat($item->Tariffa ?? '0')]); $count++; } $log[] = '✅ Tariffe coppia importate: ' . $count; return $count; }

function xmlImportReplacementTables(array $types): array { $tables = []; foreach ($types as $type) { switch ($type) { case 'clienti': $tables = array_merge($tables, ['prenotazioni', 'acquisti', 'clienti']); break; case 'insegnanti': $tables = array_merge($tables, ['prenotazioni', 'tariffe_coppia', 'insegnanti_strumenti', 'insegnanti']); break; case 'prenotazioni': $tables[] = 'prenotazioni'; break; case 'acquisti': $tables = array_merge($tables, ['prenotazioni', 'acquisti']); break; case 'pacchetti': $tables = array_merge($tables, ['prenotazioni', 'acquisti', 'pacchetti']); break; case 'strumenti': $tables = array_merge($tables, ['insegnanti_strumenti', 'strumenti']); break; case 'impostazioni_generali': $tables[] = 'impostazioni_generali'; break; case 'tariffe_coppia': $tables[] = 'tariffe_coppia'; break; } } return array_values(array_intersect(['prenotazioni', 'acquisti', 'tariffe_coppia', 'insegnanti_strumenti', 'insegnanti', 'clienti', 'pacchetti', 'strumenti', 'impostazioni_generali'], array_unique($tables))); }
function xmlImportClearTables(PDO $pdo, array $types, array &$log): void { $tables = xmlImportReplacementTables($types); if ($tables === []) return; foreach ($tables as $table) { $pdo->exec('DELETE FROM `' . str_replace('`', '', $table) . '`'); $log[] = '🧹 Tabella svuotata: ' . $table; } }
function xmlImportCleanupDirectory(): void { if (!is_dir(EASYBOOKING_XML_IMPORT_DIR)) return; $items = scandir(EASYBOOKING_XML_IMPORT_DIR); if (!is_array($items)) return; foreach ($items as $item) { if ($item === '.' || $item === '..') continue; $path = EASYBOOKING_XML_IMPORT_DIR . '/' . $item; if (is_file($path)) @unlink($path); } $remaining = scandir(EASYBOOKING_XML_IMPORT_DIR); if (is_array($remaining) && count($remaining) <= 2) @rmdir(EASYBOOKING_XML_IMPORT_DIR); }

$requestAction = $_SERVER['REQUEST_METHOD'] === 'POST' ? post('action') : get('action');
if ($requestAction === 'import_xml' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $log = [];
    $errors = [];
    $mode = post('import_mode') === 'replace' ? 'replace' : 'update';
    $processedFiles = [];
    $currentRuntimePath = null;
    try {
        if (!isset($_FILES['xml_files']['name']) || !is_array($_FILES['xml_files']['name'])) jsonResponse(['success' => false, 'log' => [], 'errors' => ['Seleziona almeno un file XML.']], 422);
        $uploaded = [];
        $countFiles = count($_FILES['xml_files']['name']);
        for ($i = 0; $i < $countFiles; $i++) {
            $error = $_FILES['xml_files']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
            if ($error === UPLOAD_ERR_NO_FILE) continue;
            if ($error !== UPLOAD_ERR_OK) throw new RuntimeException('Errore di upload per il file #' . ($i + 1) . '.');
            $uploaded[] = ['name' => (string)$_FILES['xml_files']['name'][$i], 'tmp_name' => (string)$_FILES['xml_files']['tmp_name'][$i]];
        }
        if ($uploaded === []) jsonResponse(['success' => false, 'log' => [], 'errors' => ['Seleziona almeno un file XML da importare.']], 422);

        foreach ($uploaded as $file) {
            $currentRuntimePath = xmlImportMoveUpload($file);
            $content = xmlImportLoadContent($currentRuntimePath, $file['name'], $log);
            $xml = xmlImportParseDocument($content, $file['name']);
            $type = xmlImportDetectType($xml, $file['name']);
            if ($type === null) throw new RuntimeException('Tipo XML non riconosciuto per ' . $file['name']);
            $processedFiles[] = ['name' => $file['name'], 'type' => $type, 'xml' => $xml, 'path' => $currentRuntimePath];
            $currentRuntimePath = null;
        }
        usort($processedFiles, static fn(array $a, array $b): int => (array_flip(xmlImportProcessingOrder())[$a['type']] ?? 999) <=> (array_flip(xmlImportProcessingOrder())[$b['type']] ?? 999));

        $pdo->beginTransaction();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        if ($mode === 'replace') xmlImportClearTables($pdo, array_column($processedFiles, 'type'), $log);
        $handlers = xmlImportHandlers();
        foreach ($processedFiles as $file) {
            $handler = $handlers[$file['type']] ?? null;
            if ($handler === null || !function_exists($handler)) throw new RuntimeException('Handler mancante per ' . $file['type']);
            $log[] = '➡️ Importazione: ' . $file['name'] . ' (' . $file['type'] . ')';
            $handler($pdo, $file['xml'], $mode, $log);
            $log[] = '✔️ Completato: ' . $file['name'];
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        $pdo->commit();
        foreach ($processedFiles as $file) if (is_file($file['path'])) @unlink($file['path']);
        xmlImportCleanupDirectory();
        jsonResponse(['success' => true, 'log' => $log, 'errors' => $errors]);
    } catch (Throwable $e) {
        try { $pdo->exec('SET FOREIGN_KEY_CHECKS = 1'); } catch (Throwable $fkErr) { error_log('import-xml.php: failed to re-enable FK checks: ' . $fkErr->getMessage()); }
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ($currentRuntimePath && is_file($currentRuntimePath)) @unlink($currentRuntimePath);
        foreach ($processedFiles as $file) if (!empty($file['path']) && is_file($file['path'])) @unlink($file['path']);
        xmlImportCleanupDirectory();
        $errors[] = $e->getMessage();
        jsonResponse(['success' => false, 'log' => $log, 'errors' => $errors], 422);
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1">Importa XML</h2>
        <p class="text-secondary mb-0">Importa clienti, insegnanti, prenotazioni e configurazioni da file XML cifrati o in chiaro.</p>
    </div>
</div>

<div class="card mb-4"><div class="card-header"><i class="fas fa-file-import me-2"></i>Importazione XML</div><div class="card-body"><form id="xmlImportForm" action="import-xml.php" method="post" enctype="multipart/form-data"><?= csrfField() ?><input type="hidden" name="action" value="import_xml"><div class="row g-3"><div class="col-12"><label for="xml_files" class="form-label">File XML</label><input type="file" class="form-control" id="xml_files" name="xml_files[]" accept=".xml" multiple required><div class="form-text">Puoi selezionare più file nello stesso caricamento.</div></div><div class="col-12"><label class="form-label d-block">Modalità importazione</label><div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="import_mode" id="mode_replace" value="replace"><label class="form-check-label" for="mode_replace">Sostituisci</label></div><div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="import_mode" id="mode_update" value="update" checked><label class="form-check-label" for="mode_update">Aggiorna</label></div></div><div class="col-12"><button type="submit" class="btn btn-primary" id="xmlImportSubmit"><i class="fas fa-upload me-2"></i>Importa file XML</button></div></div></form></div></div>
<div class="card mb-4"><div class="card-header"><i class="fas fa-tasks me-2"></i>Progresso importazione</div><div class="card-body"><div class="progress mb-3" style="height:24px;"><div class="progress-bar progress-bar-striped progress-bar-animated" id="xmlImportProgress" role="progressbar" style="width:0%;">0%</div></div><div class="small text-secondary">Il progresso mostra caricamento e completamento della procedura AJAX.</div></div></div>
<div class="card"><div class="card-header"><i class="fas fa-scroll me-2"></i>Log output</div><div class="card-body"><div id="xmlImportLog" class="border rounded p-3 bg-body-tertiary" style="min-height:260px; max-height:420px; overflow:auto; white-space:pre-wrap; font-family:monospace;">Pronto per l'importazione.</div></div></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('xmlImportForm');
    const progressBar = document.getElementById('xmlImportProgress');
    const logEl = document.getElementById('xmlImportLog');
    const submitBtn = document.getElementById('xmlImportSubmit');
    function setProgress(percent, text) { progressBar.style.width = percent + '%'; progressBar.textContent = text || (percent + '%'); progressBar.setAttribute('aria-valuenow', String(percent)); }
    function writeLog(lines, errors = []) { const all = []; if (Array.isArray(lines)) all.push(...lines); if (Array.isArray(errors) && errors.length) { all.push('', 'ERRORI:'); all.push(...errors.map((item) => '✖ ' + item)); } logEl.textContent = all.join('\n') || 'Nessun dettaglio disponibile.'; logEl.scrollTop = logEl.scrollHeight; }
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!document.getElementById('xml_files').files.length) { showToast('Seleziona almeno un file XML.', 'warning'); return; }
        submitBtn.disabled = true; setProgress(5, '5%'); logEl.textContent = 'Avvio importazione...';
        const xhr = new XMLHttpRequest(); xhr.open('POST', 'import-xml.php', true); xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest'); xhr.setRequestHeader('X-CSRF-Token', getCsrfToken());
        xhr.upload.addEventListener('progress', (e) => { if (e.lengthComputable) { const percent = Math.min(80, Math.max(10, Math.round((e.loaded / e.total) * 80))); setProgress(percent, percent + '%'); } });
        xhr.onreadystatechange = () => { if (xhr.readyState !== 4) return; submitBtn.disabled = false; let data = null; try { data = JSON.parse(xhr.responseText); } catch (error) { setProgress(100, 'Errore'); logEl.textContent = 'Risposta non valida dal server.'; showToast('Importazione fallita.', 'danger'); return; } if (data.success) { setProgress(100, '100%'); writeLog(data.log || [], data.errors || []); showToast('Importazione XML completata.', 'success'); } else { setProgress(100, 'Errore'); writeLog(data.log || [], data.errors || ['Errore sconosciuto']); showToast('Importazione XML non completata.', 'danger'); } };
        xhr.send(new FormData(form));
    });
});
</script>
<?php require_once __DIR__ . '/includes/footer.php';

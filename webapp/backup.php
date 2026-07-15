<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/encryption.php';
require_once __DIR__ . '/includes/auth.php';
requireAuth();
requireAdmin();
$pdo = Database::getInstance();

function h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function backupConfigUpsert(PDO $pdo, string $key, string $value): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO system_config (`key`, `value`) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), updated_at = CURRENT_TIMESTAMP'
    );
    $stmt->execute([$key, $value]);
}

function backupSqlPreview(string $statement): string
{
    $statement = preg_replace('/\s+/', ' ', trim($statement)) ?? trim($statement);
    return mb_substr($statement, 0, 140);
}

function backupSplitSqlStatements(string $sql): array
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

function backupBuildSql(PDO $pdo): string
{
    $dump = [];
    $dump[] = '-- EasyBooking SQL Backup';
    $dump[] = '-- Generated at: ' . date('Y-m-d H:i:s');
    $dump[] = 'SET NAMES utf8mb4;';
    $dump[] = 'SET FOREIGN_KEY_CHECKS = 0;';
    $dump[] = '';

    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) ?: [];
    foreach ($tables as $table) {
        $tableName = str_replace('`', '', (string)$table);
        $createStmt = $pdo->query('SHOW CREATE TABLE `' . $tableName . '`')->fetch(PDO::FETCH_ASSOC);
        if (!$createStmt) {
            continue;
        }
        $createSql = $createStmt['Create Table'] ?? array_values($createStmt)[1] ?? '';
        $dump[] = '-- ------------------------------------------------------------';
        $dump[] = '-- Table: ' . $tableName;
        $dump[] = 'DROP TABLE IF EXISTS `' . $tableName . '`;';
        $dump[] = $createSql . ';';

        $rows = $pdo->query('SELECT * FROM `' . $tableName . '`', PDO::FETCH_ASSOC);
        if ($rows instanceof PDOStatement) {
            foreach ($rows as $row) {
                $columns = [];
                $values = [];
                foreach ($row as $column => $value) {
                    $columns[] = '`' . str_replace('`', '', (string)$column) . '`';
                    $values[] = $value === null ? 'NULL' : $pdo->quote((string)$value);
                }
                $dump[] = 'INSERT INTO `' . $tableName . '` (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ');';
            }
        }
        $dump[] = '';
    }

    $dump[] = 'SET FOREIGN_KEY_CHECKS = 1;';
    return implode("\n", $dump) . "\n";
}

$requestAction = $_SERVER['REQUEST_METHOD'] === 'POST' ? post('action') : get('action');
$importLog = [];
$importSuccess = null;
$pageError = '';

if ($requestAction === 'export' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrf();
        $sql = backupBuildSql($pdo);
        $filename = 'easybooking-backup-' . date('Ymd-His') . '.sql';
        backupConfigUpsert($pdo, 'last_backup_at', date('Y-m-d H:i:s'));
        backupConfigUpsert($pdo, 'last_backup_type', 'export');
        backupConfigUpsert($pdo, 'last_backup_filename', $filename);
        backupConfigUpsert($pdo, 'last_backup_user', (string)(currentUser()['username'] ?? ''));

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Content-Length: ' . strlen($sql));
        echo $sql;
        exit;
    } catch (Throwable $e) {
        $pageError = 'Impossibile generare il backup SQL.';
    }
}

if ($requestAction === 'import' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrf();
        if (!isset($_FILES['sql_file']) || ($_FILES['sql_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Carica un file .sql valido per il ripristino.');
        }
        $originalName = (string)($_FILES['sql_file']['name'] ?? 'backup.sql');
        if (strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) !== 'sql') {
            throw new RuntimeException('Il file selezionato deve avere estensione .sql.');
        }
        $content = file_get_contents((string)$_FILES['sql_file']['tmp_name']);
        if ($content === false || trim($content) === '') {
            throw new RuntimeException('Il file di backup è vuoto o non leggibile.');
        }

        $statements = backupSplitSqlStatements($content);
        if ($statements === []) {
            throw new RuntimeException('Nessuna istruzione SQL trovata nel file selezionato.');
        }

        $pdo->beginTransaction();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $executed = 0;
        foreach ($statements as $statement) {
            try {
                $pdo->exec($statement);
                $executed++;
                if (count($importLog) < 25) {
                    $importLog[] = '✔ ' . backupSqlPreview($statement);
                }
            } catch (PDOException $e) {
                throw new RuntimeException('Errore SQL su: ' . backupSqlPreview($statement) . ' — ' . $e->getMessage(), 0, $e);
            }
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        $pdo->commit();

        backupConfigUpsert($pdo, 'last_backup_at', date('Y-m-d H:i:s'));
        backupConfigUpsert($pdo, 'last_backup_type', 'import');
        backupConfigUpsert($pdo, 'last_backup_filename', $originalName);
        backupConfigUpsert($pdo, 'last_backup_user', (string)(currentUser()['username'] ?? ''));

        $importSuccess = 'Ripristino completato con successo. Istruzioni eseguite: ' . $executed;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
            try { $pdo->exec('SET FOREIGN_KEY_CHECKS = 1'); } catch (Throwable) {}
        }
        $importSuccess = false;
        $importLog[] = '✖ ' . $e->getMessage();
    }
}

$lastBackup = [
    'last_backup_at' => '',
    'last_backup_type' => '',
    'last_backup_filename' => '',
    'last_backup_user' => '',
];

try {
    $stmt = $pdo->prepare('SELECT `key`, `value` FROM system_config WHERE `key` IN ("last_backup_at", "last_backup_type", "last_backup_filename", "last_backup_user")');
    $stmt->execute();
    foreach ($stmt->fetchAll() as $row) {
        $lastBackup[$row['key']] = (string)$row['value'];
    }
} catch (PDOException $e) {
    if ($pageError === '') {
        $pageError = 'Impossibile leggere le informazioni sull\'ultimo backup.';
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1">Backup e ripristino</h2>
        <p class="text-secondary mb-0">Esporta un dump SQL completo oppure ripristina un backup esistente.</p>
    </div>
</div>

<?php if ($pageError !== ''): ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i><?= h($pageError) ?>
</div>
<?php endif; ?>

<?php if (is_string($importSuccess) && $importSuccess !== ''): ?>
<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= h($importSuccess) ?></div>
<?php elseif ($importSuccess === false): ?>
<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Il ripristino non è andato a buon fine.</div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="fas fa-download me-2"></i>Esporta backup</div>
            <div class="card-body d-flex flex-column">
                <p class="text-secondary">Genera un file SQL completo con struttura e dati di tutte le tabelle del sistema.</p>
                <form method="post" action="backup.php" class="mt-auto">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="export">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-file-download me-2"></i>Scarica Backup SQL</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="fas fa-upload me-2"></i>Import / Restore</div>
            <div class="card-body d-flex flex-column">
                <p class="text-secondary">Carica un file <code>.sql</code> precedentemente esportato per ripristinare il database.</p>
                <form method="post" action="backup.php" enctype="multipart/form-data" class="mt-auto">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="import">
                    <div class="mb-3">
                        <label for="sql_file" class="form-label">File SQL</label>
                        <input type="file" class="form-control" id="sql_file" name="sql_file" accept=".sql" required>
                    </div>
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Il ripristino sovrascriverà i dati esistenti. Continuare?');">
                        <i class="fas fa-database me-2"></i>Ripristina
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-danger">
    <i class="fas fa-shield-alt me-2"></i>
    <strong>Attenzione:</strong> il ripristino esegue direttamente il contenuto del file SQL caricato. Usa solo backup affidabili e verifica di avere una copia recente prima di procedere.
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><i class="fas fa-history me-2"></i>Ultima attività backup</div>
            <div class="card-body">
                <?php if ($lastBackup['last_backup_at'] !== ''): ?>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Data</dt>
                    <dd class="col-sm-7"><?= h(formatDate($lastBackup['last_backup_at'], 'd/m/Y H:i')) ?></dd>
                    <dt class="col-sm-5">Operazione</dt>
                    <dd class="col-sm-7"><?= h($lastBackup['last_backup_type'] ?: '—') ?></dd>
                    <dt class="col-sm-5">File</dt>
                    <dd class="col-sm-7"><?= h($lastBackup['last_backup_filename'] ?: '—') ?></dd>
                    <dt class="col-sm-5">Utente</dt>
                    <dd class="col-sm-7"><?= h($lastBackup['last_backup_user'] ?: '—') ?></dd>
                </dl>
                <?php else: ?>
                <div class="text-secondary">Nessuna informazione di backup disponibile.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header"><i class="fas fa-list-ul me-2"></i>Log import</div>
            <div class="card-body">
                <div class="border rounded p-3 bg-body-tertiary" style="min-height:220px; max-height:360px; overflow:auto; font-family:monospace; white-space:pre-wrap;">
                    <?php if ($importLog === []): ?>
                    <span class="text-secondary">Il log del ripristino apparirà qui dopo un'importazione.</span>
                    <?php else: ?>
                    <?= h(implode("\n", $importLog)) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php';

<?php
// Load optional .env file (if present, takes precedence over defaults below)
$_envFile = dirname(__DIR__) . '/.env';
if (is_file($_envFile)) {
    foreach (file($_envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $_line) {
        $_line = trim($_line);
        if ($_line === '' || $_line[0] === '#' || strpos($_line, '=') === false) {
            continue;
        }
        [$_k, $_v] = array_map('trim', explode('=', $_line, 2));
        if (!isset($_SERVER[$_k]) && !isset($_ENV[$_k])) {
            putenv("{$_k}={$_v}");
            $_ENV[$_k] = $_v;
        }
    }
    unset($_envFile, $_line, $_k, $_v);
} else {
    unset($_envFile);
}

define('DB_HOST',         getenv('DB_HOST')     ?: 'mysql');
define('DB_HOST_FALLBACK', getenv('DB_HOST_FALLBACK') ?: 'localhost');
define('DB_NAME',         getenv('DB_NAME')     ?: 'u362062795_easybooking');
define('DB_USER',         getenv('DB_USER')     ?: 'u362062795_easybooking');
// Password MUST be set via .env file or environment variable; no default provided here.
define('DB_PASS',         getenv('DB_PASS')     ?: '');
define('DB_CHARSET',      'utf8mb4');

if (!defined('CLOUD_PUBLIC_BASE_URL')) {
    $_cloudPublicBaseUrl = trim(getenv('CLOUD_PUBLIC_BASE_URL') ?: '', " \t\n\r\0\x0B\"'");
    if ($_cloudPublicBaseUrl !== '') {
        define('CLOUD_PUBLIC_BASE_URL', rtrim($_cloudPublicBaseUrl, '/'));
    }
    unset($_cloudPublicBaseUrl);
}

class Database {
    private static ?PDO $instance = null;

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // Try fallback host
                try {
                    $dsn = 'mysql:host=' . DB_HOST_FALLBACK . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
                    self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
                } catch (PDOException $e2) {
                    throw new PDOException('Connessione al database fallita: ' . $e2->getMessage());
                }
            }
        }
        return self::$instance;
    }

    /** Check if a table exists */
    public static function tableExists(string $table): bool {
        try {
            $pdo = self::getInstance();
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM information_schema.tables 
                 WHERE table_schema = ? AND table_name = ?"
            );
            $stmt->execute([DB_NAME, $table]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /** Check if the core schema has been initialised */
    public static function schemaExists(): bool {
        return self::tableExists('users') && self::tableExists('clienti') && self::tableExists('prenotazioni');
    }

    /** Count rows in a table (returns -1 on error) */
    public static function countRows(string $table): int {
        try {
            $pdo = self::getInstance();
            // table name is whitelisted – no user input
            $stmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return -1;
        }
    }
}

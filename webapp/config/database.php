<?php
define('DB_HOST', 'mysql');
define('DB_HOST_FALLBACK', 'localhost');
define('DB_NAME', 'u362062795_easybooking');
define('DB_USER', 'u362062795_easybooking');
define('DB_PASS', 'D4tabas3-EasyB00k1ng-vocefutura');
define('DB_CHARSET', 'utf8mb4');

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

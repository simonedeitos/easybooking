<?php
require_once __DIR__ . '/database.php';

// These constants are fixed for compatibility with the C# EasyBooking desktop app.
// They replicate the Rfc2898DeriveBytes(password, UTF8("EasyBookingSalt")) defaults
// used by the WinForms exe to AES-encrypt the XML data files.
// They MUST NOT be changed – they are algorithmic constants, not secrets.
define('XML_ENCRYPTION_PASSWORD', 'EasyBooking!2025');
define('XML_ENCRYPTION_SALT',     'EasyBookingSalt');
define('XML_PBKDF2_ITERATIONS',   1000);
define('XML_PBKDF2_LENGTH',       48);   // 32 key + 16 IV

/**
 * Decrypt a file that was encrypted by the C# EasyBooking desktop app.
 * Algorithm: PBKDF2-SHA1 → AES-256-CBC (Rfc2898DeriveBytes defaults).
 */
function decryptXMLFile(string $filePath): string|false {
    if (!file_exists($filePath)) {
        return false;
    }
    $ciphertext = file_get_contents($filePath);
    if ($ciphertext === false || $ciphertext === '') {
        return false;
    }
    $derived = hash_pbkdf2(
        'sha1',
        XML_ENCRYPTION_PASSWORD,
        XML_ENCRYPTION_SALT,
        XML_PBKDF2_ITERATIONS,
        XML_PBKDF2_LENGTH,
        true   // raw output
    );
    $key = substr($derived, 0, 32);
    $iv  = substr($derived, 32, 16);
    $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    return $decrypted;
}

/**
 * Encrypt arbitrary data with a given 32-byte key using AES-256-CBC.
 * Returns base64-encoded   IV + ciphertext.
 */
function encryptData(string $data, string $key): string {
    $iv         = random_bytes(16);
    $ciphertext = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $ciphertext);
}

/**
 * Decrypt data previously encrypted with encryptData().
 */
function decryptData(string $encrypted, string $key): string|false {
    $raw = base64_decode($encrypted, true);
    if ($raw === false || strlen($raw) < 17) {
        return false;
    }
    $iv         = substr($raw, 0, 16);
    $ciphertext = substr($raw, 16);
    return openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
}

/**
 * Retrieve (and cache in session) the application encryption key from system_config.
 * The key is stored as base64 in the DB.
 */
function getEncryptionKey(): string|false {
    if (isset($_SESSION['app_enc_key'])) {
        return $_SESSION['app_enc_key'];
    }
    try {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("SELECT `value` FROM system_config WHERE `key` = 'encryption_key' LIMIT 1");
        $stmt->execute();
        $row  = $stmt->fetch();
        if (!$row) {
            return false;
        }
        $key = base64_decode($row['value'], true);
        if ($key === false || strlen($key) !== 32) {
            return false;
        }
        $_SESSION['app_enc_key'] = $key;
        return $key;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Encrypt a single DB field value.  Returns the base64 blob or the original value on failure.
 */
function encryptField(string $value): string {
    $key = getEncryptionKey();
    if ($key === false || $value === '') {
        return $value;
    }
    return encryptData($value, $key);
}

/**
 * Decrypt a single DB field value previously encrypted with encryptField().
 */
function decryptField(string $encrypted): string {
    if ($encrypted === '') {
        return $encrypted;
    }
    $key = getEncryptionKey();
    if ($key === false) {
        return $encrypted;
    }
    $result = decryptData($encrypted, $key);
    return ($result === false) ? $encrypted : $result;
}

/**
 * Generate a fresh 32-byte random key, store it in system_config and session.
 */
function generateAndStoreEncryptionKey(): string {
    $key    = random_bytes(32);
    $b64Key = base64_encode($key);
    try {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            "INSERT INTO system_config (`key`, `value`) VALUES ('encryption_key', ?)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)"
        );
        $stmt->execute([$b64Key]);
    } catch (PDOException $e) {
        // non-fatal – return key anyway
    }
    $_SESSION['app_enc_key'] = $key;
    return $b64Key;
}

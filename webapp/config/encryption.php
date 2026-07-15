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
 * Normalise XML content encoding before passing to simplexml_load_string().
 *
 * The C# XmlSerializer can write a UTF-16 encoding declaration even when the
 * underlying byte stream is UTF-8 (StringWriter reports UTF-16 internally).
 * After AES decryption, PHP ends up with UTF-8 bytes whose <?xml?> header
 * still declares encoding="utf-16", causing libxml to reject the document
 * with "Document labelled UTF-16 but has UTF-8 content".
 *
 * Steps:
 *  1. Strip any UTF-8 BOM (\xEF\xBB\xBF).
 *  2. Detect a UTF-16 LE/BE BOM, convert the content to UTF-8.
 *  3. Rewrite the XML encoding declaration to "UTF-8".
 */
function fixXMLEncoding(string $content): string
{
    // 1. Strip UTF-8 BOM
    if (str_starts_with($content, "\xEF\xBB\xBF")) {
        $content = substr($content, 3);
    }

    // 2. Convert UTF-16 LE (FF FE) or UTF-16 BE (FE FF) content to UTF-8
    if (str_starts_with($content, "\xFF\xFE")) {
        $converted = mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16LE');
        if ($converted !== false) {
            $content = $converted;
        }
    } elseif (str_starts_with($content, "\xFE\xFF")) {
        $converted = mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16BE');
        if ($converted !== false) {
            $content = $converted;
        }
    } else {
        // 2b. Detect UTF-16 without BOM by verifying the alternating-null-byte
        //     pattern across the first 4 bytes (two UTF-16 code units).
        //     UTF-16 LE: bytes at odd  positions (1, 3) are 0x00 for ASCII chars.
        //     UTF-16 BE: bytes at even positions (0, 2) are 0x00 for ASCII chars.
        //     The converted result must contain valid XML-like content as an
        //     additional safeguard against false positives on binary data.
        $sample = substr($content, 0, 4);
        if (strlen($sample) === 4) {
            $isUtf16LE = ($sample[1] === "\x00" && $sample[3] === "\x00");
            $isUtf16BE = ($sample[0] === "\x00" && $sample[2] === "\x00");
            if ($isUtf16LE || $isUtf16BE) {
                $encoding = $isUtf16BE ? 'UTF-16BE' : 'UTF-16LE';
                $converted = mb_convert_encoding($content, 'UTF-8', $encoding);
                // Accept conversion only when the result looks like XML content.
                if ($converted !== false && strlen($converted) > 0 && str_contains(ltrim($converted), '<')) {
                    $content = $converted;
                }
            }
        }
    }

    // 3. Rewrite the XML encoding declaration to UTF-8 (handles utf-16,
    //    UTF-16, Windows-1252, ISO-8859-1, etc.)
    $content = preg_replace_callback(
        '/<\?xml\b[^?]*\?>/i',
        static function (array $m): string {
            return preg_replace(
                '/\bencoding=["\'][^"\']*["\']/i',
                'encoding="UTF-8"',
                $m[0]
            ) ?? $m[0];
        },
        $content,
        1
    ) ?? $content;

    // 4. Safety net: if the declaration still references utf-16 (the regex
    //    above may fail on malformed declarations), replace it directly.
    if (preg_match('/encoding=["\']utf-?16/i', substr($content, 0, 200))) {
        $content = preg_replace(
            '/(<\?xml\b[^?]*?)encoding=["\']utf-?16[a-z]*["\']/i',
            '$1encoding="UTF-8"',
            $content,
            1
        ) ?? $content;
    }

    return $content;
}

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

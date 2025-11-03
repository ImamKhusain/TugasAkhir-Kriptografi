<?php
class SecurityConfig {
    private const AES_KEY = '1234567890abcdef'; // 16 byte
    private const AES_IV  = 'abcdef1234567890'; // 16 byte

    const MIN_PASSWORD_LENGTH = 8;
    const PASSWORD_REQUIRES_NUMBERS = true;
    const PASSWORD_REQUIRES_SPECIAL_CHARS = true;

    // Jalankan session aman
    public static function secureSession() {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 0);
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    // Validasi kekuatan password
    public static function validatePassword($password) {
        if (strlen($password) < self::MIN_PASSWORD_LENGTH) return false;
        if (self::PASSWORD_REQUIRES_NUMBERS && !preg_match('/[0-9]/', $password)) return false;
        if (self::PASSWORD_REQUIRES_SPECIAL_CHARS && !preg_match('/[^A-Za-z0-9]/', $password)) return false;
        return true;
    }

    // Sanitasi input
    public static function sanitizeInput($input) {
        return htmlspecialchars(strip_tags(trim($input)));
    }

    // AES-128 Encryption
    public static function encryptAES($data) {
        return base64_encode(openssl_encrypt($data, 'AES-128-CBC', self::AES_KEY, OPENSSL_RAW_DATA, self::AES_IV));
    }

    // AES-128 Decryption
    public static function decryptAES($data) {
        return openssl_decrypt(base64_decode($data), 'AES-128-CBC', self::AES_KEY, OPENSSL_RAW_DATA, self::AES_IV);
    }
}

// CSRF Token
function generateToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>

<?php
// KONFIGURASI DATABASE & KUNCI

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_kripto');

// AES-128 Key untuk enkripsi database
define('AES_KEY', '0123456789abcdef0123456789abcdef');
define('AES_METHOD', 'aes-128-cbc');

// ChaCha20 Key untuk super enkripsi
define('CHACHA20_KEY', 'abcdefghijklmnopqrstuvwxyz123456');


// FUNGSI KEAMANAN


class SecurityConfig {
    const MIN_PASSWORD_LENGTH = 8;
    const PASSWORD_REQUIRES_NUMBERS = true;
    const PASSWORD_REQUIRES_SPECIAL_CHARS = true;

    // Jalankan session
    public static function secureSession() {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 0);
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
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
}

// Menjalankan session di awal untuk semua file
SecurityConfig::secureSession();

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


// 3. FUNGSI DATABASE


// Koneksi Database
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }
    return $conn;
}


// 4. FUNGSI KRIPTOGRAFI


// AES-128 (Untuk Enkripsi Field Database)
function aesEncrypt($data) {
    $key = hex2bin(AES_KEY);
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($data, AES_METHOD, $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $encrypted);
}

function aesDecrypt($data) {
    $key = hex2bin(AES_KEY);
    $data = base64_decode($data);
    if ($data === false) return false;
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
    return openssl_decrypt($encrypted, AES_METHOD, $key, OPENSSL_RAW_DATA, $iv);
}

// Super Enkripsi (Rail Fence + ChaCha20)
function railFenceEncrypt($text, $rails = 3) {
    if ($rails <= 1) return $text;
    $fence = array_fill(0, $rails, '');
    $rail = 0; $direction = 1;
    for ($i = 0; $i < strlen($text); $i++) {
        $fence[$rail] .= $text[$i];
        $rail += $direction;
        if ($rail == 0 || $rail == $rails - 1) $direction *= -1;
    }
    return implode('', $fence);
}

function railFenceDecrypt($cipher, $rails = 3) {
    if ($rails <= 1) return $cipher;
    $fence = array_fill(0, $rails, array_fill(0, strlen($cipher), ''));
    $rail = 0; $direction = 1;
    for ($i = 0; $i < strlen($cipher); $i++) {
        $fence[$rail][$i] = '*';
        $rail += $direction;
        if ($rail == 0 || $rail == $rails - 1) $direction *= -1;
    }
    $index = 0;
    for ($r = 0; $r < $rails; $r++) {
        for ($c = 0; $c < strlen($cipher); $c++) {
            if ($fence[$r][$c] == '*' && $index < strlen($cipher)) {
                $fence[$r][$c] = $cipher[$index++];
            }
        }
    }
    $result = ''; $rail = 0; $direction = 1;
    for ($i = 0; $i < strlen($cipher); $i++) {
        $result .= $fence[$rail][$i];
        $rail += $direction;
        if ($rail == 0 || $rail == $rails - 1) $direction *= -1;
    }
    return $result;
}

function chacha20Encrypt($plaintext, $key = CHACHA20_KEY) {
    $nonce = random_bytes(12);
    $encrypted = openssl_encrypt($plaintext, 'chacha20', $key, OPENSSL_RAW_DATA, $nonce);
    return base64_encode($nonce . $encrypted);
}

function chacha20Decrypt($ciphertext, $key = CHACHA20_KEY) {
    $data = base64_decode($ciphertext);
    if ($data === false) return false;
    $nonce = substr($data, 0, 12);
    $encrypted = substr($data, 12);
    return openssl_decrypt($encrypted, 'chacha20', $key, OPENSSL_RAW_DATA, $nonce);
}

function superEncrypt($text, $rails = 3) {
    $railEncrypted = railFenceEncrypt($text, $rails);
    return chacha20Encrypt($railEncrypted);
}

function superDecrypt($cipher, $rails = 3) {
    $chachaDecrypted = chacha20Decrypt($cipher);
    if ($chachaDecrypted === false) return false;
    return railFenceDecrypt($chachaDecrypted, $rails);
}

// Enkripsi file dengan AES-256-CTR
function fileEncryptAES256($data, $password) {
    $salt = random_bytes(16);
    $key = hash_pbkdf2("sha256", $password, $salt, 10000, 32, true);

    $iv = random_bytes(16); 

    $encrypted = openssl_encrypt($data, 'aes-256-ctr', $key, OPENSSL_RAW_DATA, $iv);

    return base64_encode($salt . $iv . $encrypted);
}

function fileDecryptAES256($encrypted_data, $password) {
    $data = base64_decode($encrypted_data);
    if ($data === false || strlen($data) < 32) return false;
    
    $salt = substr($data, 0, 16);
    $iv = substr($data, 16, 16);
    $encrypted = substr($data, 32);
    
    $key = hash_pbkdf2("sha256", $password, $salt, 10000, 32, true);
    
    return openssl_decrypt($encrypted, 'aes-256-ctr', $key, OPENSSL_RAW_DATA, $iv);
}

// --- Algo 5: LSB Steganografi ---
// (Logika LSB Anda biarkan di dalam steganografi.php karena sudah self-contained)

?>
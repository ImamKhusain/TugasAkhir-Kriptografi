<?php
require_once '../koneksi/conn.php';
require_once 'security_config.php';

SecurityConfig::secureSession();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyToken($token)) die('Token tidak valid!');

    $username = SecurityConfig::sanitizeInput($_POST['username']);
    $password = SecurityConfig::sanitizeInput($_POST['password']);
    $hash_input = hash('sha256', $password);

    $stmt = $conn->prepare("SELECT * FROM users WHERE username=? AND is_active=1");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && hash_equals($user['password'], $hash_input)) {
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $user['role'];

        $update = $conn->prepare("UPDATE users SET last_login=NOW() WHERE username=?");
        $update->bind_param('s', $username);
        $update->execute();

        header('Location: ../dashboard.php');
        exit;
    } else {
        header('Location: login.php?pesan=gagal');
        exit;
    }
}
?>

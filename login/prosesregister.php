<?php
require_once '../koneksi/conn.php';
require_once 'security_config.php';

SecurityConfig::secureSession();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyToken($token)) die('Token tidak valid!');

    $username = SecurityConfig::sanitizeInput($_POST['username']);
    $password = SecurityConfig::sanitizeInput($_POST['password']);

    if (!SecurityConfig::validatePassword($password)) {
        header('Location: register.php?pesan=gagal');
        exit;
    }

    $check = $conn->prepare("SELECT id FROM users WHERE username=?");
    $check->bind_param('s', $username);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        header('Location: register.php?pesan=gagal');
        exit;
    }

    // Hash password SHA-256
    $hashed = hash('sha256', $password);

    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'pegawai')");
    $stmt->bind_param('ss', $username, $hashed);

    if ($stmt->execute()) {
        header('Location: register.php?pesan=berhasil');
    } else {
        header('Location: register.php?pesan=gagal');
    }
}
?>

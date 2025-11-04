<?php
require_once '../config.php'; // Menggunakan config terpusat

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyToken($token)) die('Token tidak valid!');

    $conn = getDBConnection(); // Menggunakan fungsi koneksi dari config.php

    $username = SecurityConfig::sanitizeInput($_POST['username']);
    $password = SecurityConfig::sanitizeInput($_POST['password']);
    
    // Algo 1: SHA-256
    $hash_input = hash('sha256', $password);

    $stmt = $conn->prepare("SELECT * FROM users WHERE username=? AND is_active=1");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && hash_equals($user['password'], $hash_input)) {
        // Regenerate session ID untuk keamanan
        session_regenerate_id(true); 
        
        // --- PERUBAHAN PENTING ---
        $_SESSION['user_id'] = $user['id']; // Simpan ID user
        $_SESSION['username'] = $user['username']; // Simpan username
        // -------------------------

        $update = $conn->prepare("UPDATE users SET last_login=NOW() WHERE username=?");
        $update->bind_param('s', $username);
        $update->execute();
        
        $conn->close();
        header('Location: ../dashboard.php?pesan=login');
        exit;
    } else {
        $conn->close();
        header('Location: login.php?pesan=gagal');
        exit;
    }
}
?>
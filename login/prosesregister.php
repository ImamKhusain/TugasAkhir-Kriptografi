<?php
require_once '../config.php'; // Menggunakan config terpusat

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyToken($token)) die('Token tidak valid!');

    $conn = getDBConnection(); // Menggunakan fungsi koneksi dari config.php

    $username = SecurityConfig::sanitizeInput($_POST['username']);
    $password = SecurityConfig::sanitizeInput($_POST['password']);

    if (!SecurityConfig::validatePassword($password)) {
        $conn->close();
        header('Location: register.php?pesan=gagal'); // Password tidak kuat
        exit;
    }

    $check = $conn->prepare("SELECT id FROM users WHERE username=?");
    $check->bind_param('s', $username);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $check->close();
        $conn->close();
        header('Location: register.php?pesan=gagal'); // Username sudah ada
        exit;
    }
    $check->close();

    // Algo 1: Hash password SHA-256
    $hashed = hash('sha256', $password);

    $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->bind_param('ss', $username, $hashed);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        // Redirect ke login dengan pesan sukses
        header('Location: login.php?pesan=berhasil_reg'); 
    } else {
        $stmt->close();
        $conn->close();
        header('Location: register.php?pesan=gagal');
    }
}
?>
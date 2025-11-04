<?php
require_once '../config.php'; // Menggunakan config terpusat

// Hapus semua variabel session
$_SESSION = array();

// Hancurkan session
session_destroy();

// Redirect ke login
header("Location: login.php");
exit;
?>
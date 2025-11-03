<?php
require_once 'security_config.php';
SecurityConfig::secureSession();
session_destroy();
header("Location: login.php");
exit;
?>

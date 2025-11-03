<?php
require_once 'security_config.php';
SecurityConfig::secureSession();
$token = generateToken();
$pesan = ($_GET['pesan'] ?? '') === 'gagal' ? "Username atau password salah!" : "";
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Login | Manajemen Karyawan</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="bg">
    <div class="login-container">
        <h2><span class="highlight">Manajemen</span><br>Karyawan<br><span class="login-text">Login</span></h2>
        <?php if ($pesan): ?><div class="error-box"><?= $pesan ?></div><?php endif; ?>
        <form action="proseslogin.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $token ?>">
        <label>Username</label>
        <input type="text" name="username" placeholder="Username" required>
        <label>Password</label>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" class="btn-login">LOGIN</button>
        </form>
        <p class="register-text">Belum punya akun? <a href="register.php">Register</a></p>
    </div>
</div>
</body>
</html>

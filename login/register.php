<?php
require_once '../config.php';

$code = $_GET['pesan'] ?? '';
$map = [
    'gagal'           => 'Registrasi gagal! Pastikan password kuat & username belum digunakan.',
    'gagal_hash'      => 'Registrasi gagal! Server tidak dikonfigurasi untuk Argon2i.',
    'gagal_validasi'  => 'Password tidak memenuhi kriteria (min 8, ada angka & simbol).',
    'gagal_username'  => 'Username sudah digunakan.',
    'gagal_db'        => 'Terjadi kesalahan saat menyimpan ke database.',
];
$pesan = $map[$code] ?? '';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Register | Aplikasi Kriptografi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <div class="bg">
        <div class="login-container">
            <h2><span class="highlight">Silahkan Membuat</span><br>Akun Baru</h2>
            <?php if ($pesan): ?><div class="error-box"><?= htmlspecialchars($pesan) ?></div><?php endif; ?>

            <form action="prosesregister.php" method="POST">
                <label>Username</label>
                <input type="text" name="username" placeholder="Username" required>
                <label>Password</label>
                <input type="password" name="password" placeholder="Password (Min 8 char, 1 angka, 1 simbol)" required>
                <button type="submit" class="btn-login">REGISTER</button>
            </form>
            <p class="register-text">Sudah punya akun? <a href="login.php">Login</a></p>
        </div>
    </div>
</body>

</html>
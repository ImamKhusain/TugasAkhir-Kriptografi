<?php
require_once 'config.php'; // Menggunakan config terpusat

if (!isset($_SESSION['user_id'])) {
    header("Location: login/login.php?pesan=belum_login");
    exit;
}
$username = $_SESSION['username'];

// Fungsi aktif link (ditambahkan agar 'Dashboard' bisa aktif)
$current = basename($_SERVER['PHP_SELF']);
function is_active($file, $current) {
    return $current === $file ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Aplikasi Kriptografi</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/fitur.css">
</head>
<body>

    <header>
        <div class="header-inner">
            <div class="welcome-text">
                <a href="dashboard.php">
                    <h5>WELCOME <br><span>(<?= htmlspecialchars($username); ?>)</span></h5>
                </a>
            </div>
            <nav class="nav-center">
                <a href="steganografi.php" class="<?= is_active('steganografi.php', $current); ?>">STEGANOGRAFI</a>
                <a href="super_enkripsi.php" class="<?= is_active('super_enkripsi.php', $current); ?>">SUPER ENKRIPSI</a>
                <a href="enkripsi_file.php" class="<?= is_active('enkripsi_file.php', $current); ?>">ENKRIPSI FILE</a>
            </nav>
            <div class="logout-btn">
                <a href="login/logout.php" class="btn btn-light btn-sm px-3">LogOut</a>
            </div>
        </div>
    </header>

    <div class="main-container">
        
        <?php if (isset($_GET['pesan']) && $_GET['pesan'] == 'login'): ?>
            <div class="alert alert-success alert-dismissible fade show text-center fw-semibold" role="alert">
                <strong>Berhasil Login!</strong> Selamat datang, <?= htmlspecialchars($username); ?>.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="page-title">
            <h1>Dashboard</h1>
            <p>Selamat datang di Aplikasi Kriptografi</p>
        </div>

        <div class="content-card">
            <h4 class="mb-3">Navigasi Cepat</h4>
            <div class="list-group">
                <a href="steganografi.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    Steganografi (LSB)
                    <span class="badge bg-primary rounded-pill">🔒</span>
                </a>
                <a href="super_enkripsi.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    Super Enkripsi (RailFence + ChaCha20)
                    <span class="badge bg-primary rounded-pill">🔑</span>
                </a>
                <a href="enkripsi_file.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    Enkripsi File (AES-256-CTR)
                    <span class="badge bg-primary rounded-pill">📁</span>
                </a>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script untuk menutup alert
        <?php if (isset($_GET['pesan']) && $_GET['pesan'] == 'login'): ?>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                const alert = document.querySelector('.alert');
                if (alert) {
                    new bootstrap.Alert(alert).close();
                }
            }, 3000);
        });
        <?php endif; ?>
    </script>
</body>
</html>
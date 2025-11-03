<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login/login.php?pesan=belum_login");
    exit;
}
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Manajemen Pegawai</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/custom.css">
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar fixed-top px-4">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <div class="welcome-text">
                <h5>WELCOME <br><span>(<?= htmlspecialchars($username); ?>)</span></h5>
            </div>

            <div class="menu-links">
                <a href="#">STEGANOGRAFI</a>
                <a href="#">DECRYPT STEGANOGRAFI</a>
                <a href="#">ENCRYPT FILE</a>
                <a href="#">DECRYPT FILE</a>
            </div>

            <div class="logout-btn">
                <a href="login/logout.php" class="btn btn-light btn-sm px-3">LogOut</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section d-flex justify-content-center align-items-center text-center">
        <div class="overlay"></div>
        <div class="hero-content text-white">
            <h1>Selamat Datang</h1>
            <h2>di Website Manajemen<br>Pegawai Kami!</h2>
        </div>
    </section>

    <?php if (isset($_GET['pesan']) && $_GET['pesan'] == 'login'): ?>
        <div class="alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-5 w-75 text-center fw-semibold" role="alert" style="z-index:9999;">
            <strong>Berhasil Login!</strong> Selamat datang, <?= htmlspecialchars($username); ?>.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <script>
            setTimeout(() => {
                document.querySelector('.alert').remove();
            }, 3000);
        </script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login/login.php?pesan=belum_login");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

$conn = getDBConnection();
$stmt = $conn->prepare("SELECT nama_lengkap FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();
$conn->close();

$nama_terenkripsi = $user_data['nama_lengkap'];

if (empty($nama_terenkripsi)) {
    header("Location: profil.php");
    exit;
}

$nama_asli = dbDecryptAES128($nama_terenkripsi, DB_ENCRYPTION_KEY);
if ($nama_asli === false) {
    $nama_asli = $username;
}


$current = basename($_SERVER['PHP_SELF']);
function is_active($file, $current)
{
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
                    <h5>Cryptopedia</h5>
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
                <strong>Berhasil Login!</strong> Selamat datang, <?= htmlspecialchars($nama_asli); ?>.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif (isset($_GET['pesan']) && $_GET['pesan'] == 'profil_disimpan'): ?>
            <div class="alert alert-success alert-dismissible fade show text-center fw-semibold" role="alert">
                <strong>Profil Disimpan!</strong> Selamat datang di Aplikasi Kriptografi, <?= htmlspecialchars($nama_asli); ?>.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="hero-section">
            <h1>Selamat Datang, <?= htmlspecialchars($nama_asli); ?>!</h1>
            <p>Amankan data Anda dengan teknologi enkripsi modern dan steganografi. Lindungi informasi penting dengan mudah, cepat, dan aman.</p>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🖼️</div>
                <h3>Steganografi</h3>
                <p>Sembunyikan pesan rahasia di dalam gambar tanpa menimbulkan kecurigaan. Teknik menyisipkan data ke dalam media digital dengan aman.</p>
                <a href="steganografi.php" class="feature-btn">Mulai Sekarang</a>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🔐</div>
                <h3>Super Enkripsi</h3>
                <p>Enkripsi berlapis untuk keamanan maksimal. Kombinasi algoritma kriptografi untuk perlindungan data yang lebih kuat dan terpercaya.</p>
                <a href="super_enkripsi.php" class="feature-btn">Mulai Sekarang</a>
            </div>

            <div class="feature-card">
                <div class="feature-icon">📁</div>
                <h3>Enkripsi File</h3>
                <p>Enkripsi berbagai jenis file dan dokumen penting. Lindungi data Anda dengan password yang aman dan sistem enkripsi terjamin.</p>
                <a href="enkripsi_file.php" class="feature-btn">Mulai Sekarang</a>
            </div>
        </div>

        <div class="getting-started">
            <h3>Cara Memulai</h3>
            <div class="getting-started-content">
                <div class="step-box">
                    <span class="step-number">1</span>
                    <h5>Pilih Fitur</h5>
                    <p>Pilih salah satu fitur yang sesuai dengan kebutuhan Anda: Steganografi, Super Enkripsi, atau Enkripsi File.</p>
                </div>

                <div class="step-box">
                    <span class="step-number">2</span>
                    <h5>Upload Data</h5>
                    <p>Upload file atau masukkan teks yang ingin Anda enkripsi atau sembunyikan dengan aman.</p>
                </div>

                <div class="step-box">
                    <span class="step-number">3</span>
                    <h5>Proses & Download</h5>
                    <p>Klik tombol proses, tunggu beberapa saat, dan download hasil enkripsi atau steganografi Anda.</p>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if (isset($_GET['pesan'])): ?>
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
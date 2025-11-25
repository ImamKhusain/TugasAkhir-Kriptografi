<?php
require_once 'config.php';

// Cek jika user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login/login.php?pesan=belum_login");
    exit;
}

$current = 'super_enkripsi.php';
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
    <title>Teori Super Enkripsi | Cryptopedia</title>

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
            <nav class="nav-center" role="navigation" aria-label="Main navigation">
                <a href="steganografi.php" class="<?= is_active('steganografi.php', $current); ?>">STEGANOGRAFI</a>
                <a href="super_enkripsi.php" class="<?= is_active('super_enkripsi.php', $current); ?>">SUPER ENKRIPSI</a>
                <a href="enkripsi_file.php" class="<?= is_active('enkripsi_file.php',  $current); ?>">ENKRIPSI FILE</a>
            </nav>
            <div class="logout-btn">
                <a href="login/logout.php" class="btn btn-light btn-sm px-3">LogOut</a>
            </div>
        </div>
    </header>

    <div class="main-container">
        <div class="page-title">
            <h1>TEORI SUPER ENKRIPSI</h1>
            <p>Memahami Konsep Keamanan Berlapis (AES + LSB Steganography)</p>
        </div>

        <div class="content-card">
            <h4 class="mb-3">💡 Apa itu Super Enkripsi?</h4>
            <p>
                <strong>Super Enkripsi</strong> adalah metode keamanan kustom yang menggabungkan dua atau lebih algoritma kriptografi
                secara berlapis untuk menciptakan perlindungan yang jauh lebih kuat daripada satu algoritma saja.
            </p>
            <p>
                Dalam konteks aplikasi ini, "Super Enkripsi" adalah gabungan dari:
            </p>
            <ul>
                <li><strong>AES-256 (Advanced Encryption Standard):</strong> Algoritma enkripsi simetris modern yang sangat kuat untuk mengenkripsi pesan rahasia Anda.</li>
                <li><strong>LSB Steganografi (Least Significant Bit):</strong> Teknik untuk menyembunyikan data (dalam hal ini, pesan yang *sudah terenkripsi*) ke dalam sebuah gambar.</li>
            </ul>

            <hr class="my-4">

            <h4 class="mb-3">Bagaimana Cara Kerjanya?</h4>

            <h5 class="mt-3">Proses Enkripsi (Menyembunyikan)</h5>
            <ol>
                <li><strong>Input:</strong> Anda memberikan sebuah pesan rahasia, sebuah kata sandi, dan sebuah gambar sampul (cover image).</li>
                <li><strong>Langkah 1: Enkripsi AES</strong>
                    <br>Pesan rahasia Anda dienkripsi terlebih dahulu menggunakan <strong>AES-256</strong> dengan kata sandi yang Anda berikan. Hasilnya adalah *ciphertext* (teks acak yang tidak bisa dibaca).
                </li>
                <li><strong>Langkah 2: Steganografi LSB</strong>
                    <br>*Ciphertext* dari Langkah 1 kemudian disembunyikan (disisipkan) ke dalam bit-bit piksel gambar sampul menggunakan <strong>LSB Steganografi</strong>.
                </li>
                <li><strong>Output:</strong> Anda mendapatkan sebuah gambar baru (file `.png`) yang terlihat identik dengan aslinya, namun kini berisi pesan terenkripsi Anda.</li>
            </ol>

            <h5 class="mt-4">Proses Dekripsi (Mengungkap)</h5>
            <ol>
                <li><strong>Input:</strong> Anda memberikan gambar yang berisi pesan (stego-image) dan kata sandi yang benar.</li>
                <li><strong>Langkah 1: Ekstraksi LSB</strong>
                    <br>Aplikasi membaca bit-bit piksel dari stego-image untuk mengekstrak data tersembunyi, yaitu *ciphertext* AES.
                </li>
                <li><strong>Langkah 2: Dekripsi AES</strong>
                    <br>*Ciphertext* yang didapat dari Langkah 1 kemudian didekripsi menggunakan <strong>AES-256</strong> dengan kata sandi yang Anda masukkan.
                </li>
                <li><strong>Output:</strong> Jika kata sandi benar, pesan asli Anda akan ditampilkan.</li>
            </ol>

            <div class="alert alert-info mt-4">
                <strong>Mengapa ini lebih aman?</strong>
                <br>
                Metode ini memberikan dua lapis perlindungan:
                <ol class="mb-0">
                    <li><strong>Perlindungan dari Deteksi:</strong> Secara visual, tidak ada yang tahu ada pesan di dalam gambar (kelebihan steganografi).</li>
                    <li><strong>Perlindungan dari Pembacaan:</strong> Sekalipun seseorang berhasil mendeteksi dan mengekstrak data, mereka hanya akan mendapatkan *ciphertext* acak. Mereka masih harus memecahkan enkripsi AES-256 yang sangat kuat untuk bisa membacanya.</li>
                </ol>
            </div>

            <hr class="my-4">
            <div class="text-center">
                <a href="super_enkripsi.php" class="btn btn-success btn-lg">Coba Sekarang</a>
                <a href="dashboard.php" class="btn btn-secondary btn-lg ms-2">Kembali ke Dashboard</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
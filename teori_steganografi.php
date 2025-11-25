<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login/login.php?pesan=belum_login");
    exit;
}

$current = 'steganografi.php';
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
    <title>Teori Steganografi | Cryptopedia</title>

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
            <h1>TEORI STEGANOGRAFI</h1>
            <p>Memahami Seni Menyembunyikan Pesan dalam Gambar (LSB)</p>
        </div>

        <div class="content-card">
            <h4 class="mb-3">🖼️ Apa itu Steganografi?</h4>
            <p>
                <strong>Steganografi</strong> adalah seni dan ilmu menyembunyikan pesan, gambar, atau file di dalam pesan, gambar, atau file lain.
                Namanya berasal dari bahasa Yunani "steganos" (tersembunyi) dan "graphein" (menulis).
            </p>
            <p>
                Perbedaan utamanya dengan <strong>Kriptografi</strong> adalah:
            </p>
            <ul>
                <li><strong>Kriptografi</strong>: Mengubah pesan agar tidak bisa dibaca (data diacak). Tujuannya adalah <strong>kerahasiaan</strong>.</li>
                <li><strong>Steganografi</strong>: Menyembunyikan keberadaan pesan itu sendiri. Tujuannya adalah <strong>kerahasiaan lokasi</strong> (agar tidak terdeteksi).</li>
            </ul>

            <hr class="my-4">

            <h4 class="mb-3">Bagaimana Cara Kerja Metode LSB?</h4>
            <p>
                Metode yang digunakan di aplikasi ini adalah <strong>LSB (Least Significant Bit)</strong>. Ini adalah salah satu teknik steganografi paling umum pada gambar digital.
            </p>

            <h5 class="mt-3">1. Konsep Dasar: Piksel dan Bit</h5>
            <p>
                Setiap gambar digital terdiri dari ribuan piksel. Setiap piksel memiliki nilai warna yang ditentukan oleh 3 saluran (channel): <strong>Red (R)</strong>, <strong>Green (G)</strong>, dan <strong>Blue (B)</strong>.
                Setiap saluran ini memiliki nilai intensitas dari 0 hingga 255.
            </p>
            <p>
                Nilai 255 dalam biner (8-bit) adalah <code>11111111</code>.
                <br>
                Nilai 254 dalam biner (8-bit) adalah <code>11111110</code>.
            </p>
            <p>
                Bit yang paling kanan (<code>0</code> atau <code>1</code>) disebut <strong>Least Significant Bit (LSB)</strong> karena ia memiliki dampak paling kecil pada nilai total. Mengubah 255 (<code>...1</code>) menjadi 254 (<code>...0</code>) hanya mengubah warna piksel secara sangat-sangat sedikit, sehingga <strong>tidak terlihat oleh mata manusia</strong>.
            </p>

            <h5 class="mt-4">2. Proses Embed (Menyembunyikan)</h5>
            <ol>
                <li><strong>Pesan diubah ke Biner:</strong> Pesan rahasia (misal "Hai") diubah menjadi urutan bit.
                    <br><code>H = 01001000</code>, <code>a = 01100001</code>, <code>i = 01101001</code>
                </li>
                <li><strong>Bit Pesan Disisipkan:</strong> Aplikasi akan membaca piksel gambar satu per satu.
                    <ul>
                        <li>Bit pertama pesan (<code>0</code>) disisipkan ke LSB saluran <strong>Red</strong> piksel pertama.</li>
                        <li>Bit kedua pesan (<code>1</code>) disisipkan ke LSB saluran <strong>Green</strong> piksel pertama.</li>
                        <li>Bit ketiga pesan (<code>0</code>) disisipkan ke LSB saluran <strong>Blue</strong> piksel pertama.</li>
                        <li>Bit keempat pesan (<code>0</code>) disisipkan ke LSB saluran <strong>Red</strong> piksel kedua.</li>
                        <li>...dan begitu seterusnya.</li>
                    </ul>
                </li>
                <li><strong>Penanda Akhir (End Marker):</strong> Setelah semua bit pesan disisipkan, sebuah penanda khusus (di kode ini: <code>###END###</code>) juga disisipkan untuk memberitahu program di mana pesan berakhir.</li>
            </ol>
            
            <h5 class="mt-4">3. Proses Extract (Mengungkap)</h5>
            <ol>
                <li><strong>Bit LSB Dibaca:</strong> Aplikasi membaca LSB dari setiap saluran R, G, B di setiap piksel, satu per satu.</li>
                <li><strong>Bit Dirangkai:</strong> Bit-bit yang diekstrak (<code>0</code>, <code>1</code>, <code>0</code>, <code>0</code>, ...) dikumpulkan kembali.</li>
                <li><strong>Dikonversi ke Teks:</strong> Setiap 8 bit diubah kembali menjadi karakter. (<code>01001000</code> -> <code>H</code>, dst.)</li>
                <li><strong>Berhenti di Penanda:</strong> Proses ekstraksi berhenti ketika aplikasi menemukan penanda T<code>###END###</code>T. Pesan pun berhasil diungkap.</li>
            </ol>

             <div class="alert alert-warning mt-4">
                 <strong>Penting: Mengapa Harus PNG?</strong>
                 <br>
                 Metode LSB sangat rentan terhadap kompresi. Format seperti <strong>JPEG</strong> menggunakan kompresi *lossy*, yang artinya data piksel diubah dan dibuang untuk memperkecil ukuran file. Proses ini akan <strong>menghancurkan</strong> bit-bit LSB yang berisi pesan rahasia.
                 <br><br>
                 Format <strong>PNG</strong> menggunakan kompresi *lossless* (tanpa hilang data), sehingga nilai piksel tetap utuh dan data LSB aman. Inilah sebabnya aplikasi ini menyimpan hasilnya sebagai file PNG.
             </div>

             <hr class="my-4">
             <div class="text-center">
                <a href="steganografi.php" class="btn btn-primary btn-lg">Coba Sekarang</a> <a href="dashboard.php" class="btn btn-secondary btn-lg ms-2">Kembali ke Dashboard</a>
             </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
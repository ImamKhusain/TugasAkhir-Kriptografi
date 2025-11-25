<?php
require_once 'config.php';

// Cek jika user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login/login.php?pesan=belum_login");
    exit;
}

// Untuk menandai navigasi yang aktif
$current = 'enkripsi_file.php'; // Kita set ini agar 'ENKRIPSI FILE' tetap aktif di nav
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
    <title>Teori Enkripsi File | Cryptopedia</title>

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
            <h1>TEORI ENKRIPSI FILE</h1>
            <p>Memahami Konsep AES-256 (Advanced Encryption Standard)</p>
        </div>

        <div class="content-card">
            <h4 class="mb-3">🔒 Apa itu AES-256?</h4>
            <p>
                <strong>AES (Advanced Encryption Standard)</strong> adalah standar emas global untuk enkripsi simetris. Ini adalah algoritma yang sama yang digunakan oleh pemerintah, bank, dan sistem keamanan tingkat tinggi di seluruh dunia untuk melindungi data sensitif.
            </p>
            <ul>
                <li><strong>Enkripsi Simetris:</strong> Ini berarti <strong>satu kunci yang sama</strong> (dalam kasus ini, password Anda) digunakan untuk mengenkripsi (mengunci) dan mendekripsi (membuka) file.</li>
                <li><strong>256-bit:</strong> Angka ini mengacu pada ukuran kunci. 256-bit adalah ukuran kunci terpanjang dan terkuat yang tersedia untuk AES. Jumlah kombinasi kuncinya sangat besar (angka 1 diikuti 77 angka nol) sehingga mustahil untuk dipecahkan dengan paksa (*brute-force*) menggunakan teknologi komputer saat ini.</li>
            </ul>
            
            <p>Algoritma yang digunakan di aplikasi ini adalah <strong>AES-256-CTR</strong>. CTR (Counter Mode) adalah mode operasi modern yang sangat cepat dan aman, ideal untuk mengenkripsi file atau aliran data.</p>

            <hr class="my-4">

            <h4 class="mb-3">Bagaimana Cara Kerjanya?</h4>
            
            
            <h5 class="mt-3">Proses Enkripsi (Mengunci File)</h5>
            <ol>
                <li><strong>Input:</strong> Anda meng-upload file (misalnya `Tugas_Besar.pkt`) dan memasukkan sebuah password.</li>
                <li><strong>Key Derivation (Pembentukan Kunci):</strong> Password Anda tidak digunakan secara langsung. Untuk keamanan, password tersebut "di-hash" dan diproses melalui algoritma turunan kunci (seperti PBKDF2) untuk menghasilkan <strong>kunci enkripsi 256-bit</strong> yang sesungguhnya.</li>
                <li><strong>Pembuatan IV:</strong> Sistem membuat sebuah <strong>Initialization Vector (IV)</strong>, yaitu angka acak unik yang digunakan hanya sekali. IV memastikan bahwa meskipun Anda mengenkripsi file yang sama dengan password yang sama berulang kali, hasilnya akan selalu berbeda. Ini penting untuk mencegah pola.</li>
                <li><strong>Proses Enkripsi:</strong> Menggunakan Kunci 256-bit (dari Langkah 2) dan IV (dari Langkah 3), algoritma AES-256-CTR mengenkripsi seluruh isi file Anda, mengubahnya dari data yang bisa dibaca menjadi data biner acak (*ciphertext*).</li>
                <li><strong>Penyimpanan:</strong> Data terenkripsi (ciphertext) dan IV-nya digabungkan, lalu diubah menjadi format <strong>Base64</strong> (format teks yang aman) untuk disimpan di database.</li>
            </ol>

            <h5 class="mt-4">Proses Dekripsi (Membuka File)</h5>
            <ol>
                <li><strong>Input:</strong> Anda memilih file dan memasukkan password yang Anda gunakan untuk mengenkripsi.</li>
                <li><strong>Key Derivation (Pembentukan Kunci):</strong> Sistem melakukan proses yang *sama persis* seperti di Langkah 2 enkripsi. Jika password yang Anda masukkan benar, ia akan menghasilkan <strong>kunci enkripsi 256-bit yang identik</strong>.</li>
                <li><strong>Pengambilan Data:</strong> Aplikasi mengambil data Base64 dari database, mengubahnya kembali ke biner, dan memisahkan IV dari *ciphertext*.</li>
                <li><strong>Proses Dekripsi:</strong> Menggunakan Kunci 256-bit (dari Langkah 2) dan IV (dari Langkah 3), algoritma AES-256-CTR membalikkan proses enkripsi.</li>
                <li><strong>Output:</strong> Jika password benar, *ciphertext* berhasil diubah kembali menjadi file asli (`Tugas_Besar.pkt`) dan siap untuk diunduh. Jika password salah, kunci yang dihasilkan akan salah, dan proses dekripsi akan gagal, menghasilkan data yang korup.</li>
            </ol>

             <div class="alert alert-danger mt-4">
                 <strong>PENTING: Jangan Lupakan Password Anda!</strong>
                 <br>
                 Karena kekuatan enkripsi AES-256, <strong>tidak ada cara</strong> untuk memulihkan file Anda jika Anda lupa passwordnya. Password Anda adalah satu-satunya kunci untuk membuka file tersebut.
             </div>

             <hr class="my-4">
             <div class="text-center">
                <a href="enkripsi_file.php" class="btn btn-danger btn-lg">Coba Sekarang</a> <a href="dashboard.php" class="btn btn-secondary btn-lg ms-2">Kembali ke Dashboard</a>
             </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
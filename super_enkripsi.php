<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login/login.php?pesan=belum_login");
    exit;
}
$username = $_SESSION['username'];

// Rail Fence Cipher
function railFenceCipher($text, $rails) {
    if ($rails <= 1) return $text;
    $fence = array_fill(0, $rails, '');
    $rail = 0; $direction = 1;

    for ($i = 0; $i < strlen($text); $i++) {
        $fence[$rail] .= $text[$i];
        $rail += $direction;
        if ($rail == 0 || $rail == $rails - 1) $direction = -$direction;
    }
    return implode('', $fence);
}

// ChaCha20 simulasi XOR
function chacha20Cipher($text) {
    $key = "SECRETKEY1234567";
    $result = "";
    $keyLen = strlen($key);
    for ($i = 0; $i < strlen($text); $i++) {
        $result .= chr(ord($text[$i]) ^ ord($key[$i % $keyLen]));
    }
    return base64_encode($result);
}

// Proses Enkripsi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['encrypt'])) {
    $judul = $_POST['judul'] ?? '';
    $pesan = $_POST['pesan'] ?? '';
    $rails = intval($_POST['rails'] ?? 3);

    if (!empty($judul) && !empty($pesan)) {
        $layer1 = railFenceCipher($pesan, $rails);
        $layer2 = chacha20Cipher($layer1);
        $encrypted_message = $layer2;

        $filename = 'encrypted_' . time() . '.txt';
        $content = "Judul: $judul\nRails: $rails\nPesan Asli: $pesan\nEnkripsi 1: $layer1\nEnkripsi 2: $layer2\nTanggal: " . date('Y-m-d H:i:s');
        file_put_contents($filename, $content);
        $message = "Pesan berhasil dienkripsi dan disimpan ke file: $filename";
    } else {
        $message = "Judul dan Pesan harus diisi!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Enkripsi</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        * { font-family: 'Poppins', sans-serif; }
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
            min-height: 100vh;
            padding-top: 84px;
        }

        /* ==== NAVBAR ==== */
        header {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1030;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #dee2e6;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }
        .header-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 12px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            min-height: 60px;
        }

        .welcome-text h5 {
            margin: 0;
            font-weight: 600;
            color: #2c3e50;
            font-size: 13px;
            line-height: 1.3;
        }
        .welcome-text h5 span {
            color: #0d6efd;
            font-weight: 700;
            font-size: 13px;
        }
        .welcome-text a { text-decoration: none; }

        .nav-center {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 50px;
        }
        .nav-center a {
            text-decoration: none;
            color: #6c757d;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 8px 4px;
            border-bottom: 3px solid transparent;
            transition: color .2s ease, border-color .2s ease;
        }
        .nav-center a:hover {
            color: #0d6efd;
            border-bottom-color: rgba(13,110,253,.4);
        }
        .nav-center a.active {
            color: #0d6efd;
            border-bottom-color: #0d6efd;
        }

        .logout-btn .btn {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            font-weight: 600;
            padding: 6px 20px;
        }

        @media (max-width: 900px) {
            .nav-center { position: static; transform: none; justify-content: center; gap: 24px; }
            .header-inner { padding: 10px 16px; }
        }

        /* ==== KONTEN ==== */
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        h1 {
            text-align: center;
            font-size: 42px;
            color: #2c3e50;
            font-weight: 700;
        }
        .subtitle {
            text-align: center;
            color: #7f8c8d;
            margin-bottom: 40px;
            font-size: 15px;
        }

        .card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        /* ==== INFO BOX ==== */
        .info-box {
            background: #fff8dc;
            border-left: 4px solid #f0ad4e;
            padding: 20px 25px;
            margin-bottom: 30px;
            border-radius: 6px;
        }
        .info-box h3 {
            color: #856404;
            margin-bottom: 12px;
            font-size: 15px; /* 🔹 dikecilkan agar seragam */
            font-weight: 600;
        }
        .info-box ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .info-box li {
            color: #856404;
            margin-bottom: 6px;
            font-size: 13.5px; /* 🔹 dikecilkan agar sama seperti halaman steganografi */
            line-height: 1.6;
        }

        label { font-weight: 600; color: #2c3e50; font-size: 14px; }
        input, textarea, select {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px;
            width: 100%;
            font-size: 14px;
        }
        textarea { min-height: 150px; resize: vertical; }
        .submit-btn {
            background: #0d6efd;
            color: white;
            padding: 12px 28px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
        }
        .submit-btn:hover { background: #0b5ed7; }

        .alert { border-radius: 8px; font-weight: 500; font-size: 14px; }
        .result-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            border: 1px solid #dee2e6;
        }
        .encrypted-text {
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 12px;
            word-wrap: break-word;
            font-family: monospace;
            font-size: 13px;
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <header>
        <div class="header-inner">
            <!-- LEFT -->
            <div class="welcome-text">
                <a href="dashboard.php">
                    <h5>WELCOME <br><span>(<?= htmlspecialchars($username); ?>)</span></h5>
                </a>
            </div>

            <!-- CENTER -->
            <nav class="nav-center">
                <a href="steganografi.php">STEGANOGRAFI</a>
                <a href="super_enkripsi.php" class="active">SUPER ENKRIPSI</a>
                <a href="enkripsi_file.php">ENKRIPSI FILE</a>
            </nav>

            <!-- RIGHT -->
            <div class="logout-btn">
                <a href="login/logout.php" class="btn btn-light btn-sm px-3">LogOut</a>
            </div>
        </div>
    </header>

    <!-- CONTENT -->
    <div class="container">
        <h1>SUPER ENKRIPSI</h1>
        <p class="subtitle">Rail Fence Cipher + ChaCha20 Stream Cipher</p>

        <div class="card">
            <div class="info-box">
                <h3>💡 Tentang Super Enkripsi</h3>
                <ul>
                    <li><strong>Layer 1:</strong> Rail Fence Cipher (Algoritma klasik transposisi)</li>
                    <li><strong>Layer 2:</strong> ChaCha20 (Algoritma modern stream cipher)</li>
                    <li><strong>Keuntungan:</strong> Kombinasi dua algoritma berbeda untuk keamanan berlapis</li>
                    <li><strong>Rails:</strong> Jumlah "rel" untuk Rail Fence (3–10 rails)</li>
                </ul>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert <?= strpos($message, 'berhasil') ? 'alert-success' : 'alert-danger'; ?>">
                    <?= htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <h4 style="font-size: 16px; font-weight: 600;">Enkripsi Pesan</h4>
            <form method="POST" action="">
                <div class="mb-3">
                    <label>Judul Pesan</label>
                    <input type="text" name="judul" required placeholder="Masukkan judul pesan">
                </div>

                <div class="mb-3">
                    <label>Isi Pesan</label>
                    <textarea name="pesan" id="pesan" required placeholder="Masukkan pesan yang akan dienkripsi..." oninput="updateCharCount()"></textarea>
                    <div style="text-align:right;font-size:12px;color:#7f8c8d;">Karakter: <span id="charCount">0</span></div>
                </div>

                <div class="mb-3">
                    <label>Jumlah Rails</label>
                    <select name="rails">
                        <?php for ($i=3; $i<=10; $i++): ?>
                            <option value="<?= $i; ?>"><?= $i; ?> Rails</option>
                        <?php endfor; ?>
                    </select>
                </div>

                <button type="submit" name="encrypt" class="submit-btn">🔒 Super Enkripsi & Simpan</button>
            </form>

            <?php if (!empty($encrypted_message)): ?>
                <div class="result-box">
                    <strong>✅ Hasil Enkripsi:</strong>
                    <div class="encrypted-text"><?= htmlspecialchars($encrypted_message); ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function updateCharCount() {
            const text = document.getElementById('pesan');
            document.getElementById('charCount').textContent = text.value.length;
        }
    </script>

</body>
</html>

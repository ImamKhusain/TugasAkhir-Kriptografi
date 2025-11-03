<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$message = "";
$encrypted_message = "";

// Rail Fence Cipher Function
function railFenceCipher($text, $rails) {
    if ($rails <= 1) return $text;
    
    $fence = array_fill(0, $rails, '');
    $rail = 0;
    $direction = 1;
    
    for ($i = 0; $i < strlen($text); $i++) {
        $fence[$rail] .= $text[$i];
        $rail += $direction;
        
        if ($rail == 0 || $rail == $rails - 1) {
            $direction = -$direction;
        }
    }
    
    return implode('', $fence);
}

// ChaCha20 Simulation (Simple substitution for demonstration)
function chacha20Cipher($text) {
    $key = "SECRETKEY1234567"; // 16 byte key untuk simulasi
    $result = "";
    $keyLen = strlen($key);
    
    for ($i = 0; $i < strlen($text); $i++) {
        $result .= chr(ord($text[$i]) ^ ord($key[$i % $keyLen]));
    }
    
    return base64_encode($result);
}

// Proses enkripsi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['encrypt'])) {
    $judul = $_POST['judul'] ?? '';
    $pesan = $_POST['pesan'] ?? '';
    $rails = intval($_POST['rails'] ?? 3);
    
    if (!empty($judul) && !empty($pesan)) {
        // Layer 1: Rail Fence Cipher
        $layer1 = railFenceCipher($pesan, $rails);
        
        // Layer 2: ChaCha20 (simulasi)
        $layer2 = chacha20Cipher($layer1);
        
        $encrypted_message = $layer2;
        
        // Simpan ke file
        $filename = 'encrypted_' . time() . '.txt';
        $content = "Judul: $judul\n";
        $content .= "Rails: $rails\n";
        $content .= "Pesan Asli: $pesan\n";
        $content .= "Enkripsi Layer 1 (Rail Fence): $layer1\n";
        $content .= "Enkripsi Layer 2 (ChaCha20): $layer2\n";
        $content .= "Tanggal: " . date('Y-m-d H:i:s') . "\n";
        
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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }

        header {
            background: white;
            border-bottom: 1px solid #e0e0e0;
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .welcome {
            font-size: 14px;
            color: #333;
        }

        .welcome span {
            color: #3498db;
            font-weight: 600;
        }

        nav {
            display: flex;
            gap: 40px;
            align-items: center;
        }

        nav a {
            color: #666;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: color 0.3s;
        }

        nav a:hover,
        nav a.active {
            color: #3498db;
        }

        .logout-btn {
            background: white;
            color: #333;
            padding: 8px 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
        }

        .logout-btn:hover {
            background: #f8f9fa;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        h1 {
            text-align: center;
            font-size: 42px;
            color: #2c3e50;
            margin-bottom: 10px;
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
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .info-box {
            background: #fff8dc;
            border-left: 4px solid #f0ad4e;
            padding: 20px 25px;
            margin-bottom: 30px;
            border-radius: 4px;
        }

        .info-box h3 {
            color: #856404;
            margin-bottom: 15px;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-box ul {
            list-style: none;
            padding-left: 0;
        }

        .info-box li {
            color: #856404;
            margin-bottom: 8px;
            line-height: 1.6;
            font-size: 14px;
        }

        .info-box li strong {
            font-weight: 600;
            color: #6f5504;
        }

        h2 {
            color: #2c3e50;
            margin-bottom: 25px;
            font-size: 18px;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 14px;
        }

        input[type="text"],
        textarea,
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
        }

        input[type="text"]:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #3498db;
        }

        textarea {
            min-height: 150px;
            resize: vertical;
        }

        input::placeholder,
        textarea::placeholder {
            color: #999;
        }

        .char-count {
            text-align: right;
            color: #7f8c8d;
            font-size: 12px;
            margin-top: 5px;
        }

        .submit-btn {
            background: #3498db;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .submit-btn:hover {
            background: #2980b9;
        }

        .submit-btn::before {
            content: '🔒 ';
        }

        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-size: 14px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .result-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin-top: 20px;
            border: 1px solid #e0e0e0;
        }

        .result-box strong {
            display: block;
            margin-bottom: 15px;
            color: #2c3e50;
            font-size: 15px;
        }

        .result-box .encrypted-text {
            background: white;
            padding: 15px;
            border-radius: 4px;
            border: 1px solid #ddd;
            word-wrap: break-word;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
            color: #333;
        }
    </style>
</head>
<body>
    <header>
        <div class="welcome">
            <a href="dashboard.php"><h5>WELCOME <br><span>(<?= htmlspecialchars($username); ?>)</span></h5></a>
        </div>
        <nav>
            <a href="steganografi.php">STEGANOGRAFI</a>
            <a href="superenkripsi.php" class="active">SUPER ENKRIPSI</a>
            <a href="enkripsifile.php">ENKRIPSI FILE</a>
            <a href="logout.php" class="logout-btn">LogOut</a>
        </nav>
    </header>

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
                    <li><strong>Rails:</strong> Jumlah "rel" untuk Rail Fence (3-10 rails)</li>
                </ul>
            </div>

            <?php if ($message): ?>
                <div class="alert <?php echo strpos($message, 'berhasil') !== false ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <h2>Enkripsi Pesan dengan Super Enkripsi</h2>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Judul Pesan</label>
                    <input type="text" name="judul" placeholder="Masukkan judul pesan" required>
                </div>

                <div class="form-group">
                    <label>Isi Pesan</label>
                    <textarea name="pesan" id="pesan" placeholder="Masukkan pesan yang akan dienkripsi..." required oninput="updateCharCount()"></textarea>
                    <div class="char-count">Karakter: <span id="charCount">0</span></div>
                </div>

                <div class="form-group">
                    <label>Jumlah Rails (3-10)</label>
                    <select name="rails">
                        <option value="3">3 Rails</option>
                        <option value="4">4 Rails</option>
                        <option value="5">5 Rails</option>
                        <option value="6">6 Rails</option>
                        <option value="7">7 Rails</option>
                        <option value="8">8 Rails</option>
                        <option value="9">9 Rails</option>
                        <option value="10">10 Rails</option>
                    </select>
                </div>

                <button type="submit" name="encrypt" class="submit-btn">Super Enkripsi & Simpan</button>
            </form>

            <?php if ($encrypted_message): ?>
                <div class="result-box">
                    <strong>✅ Hasil Enkripsi:</strong>
                    <div class="encrypted-text">
                        <?php echo htmlspecialchars($encrypted_message); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function updateCharCount() {
            const textarea = document.getElementById('pesan');
            const charCount = document.getElementById('charCount');
            charCount.textContent = textarea.value.length;
        }
    </script>
</body>
</html>
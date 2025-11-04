<?php
require_once 'config.php'; // Menggunakan config terpusat

if (!isset($_SESSION['user_id'])) { // Cek user_id
    header("Location: login/login.php?pesan=belum_login");
    exit;
}
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username']; // Masih digunakan untuk sapaan
$conn = getDBConnection();

$message = '';
$encrypted_message_display = ''; // Untuk ditampilkan di box hasil
$decrypted_message = '';
$error_decrypt = '';

// Fungsi aktif link
$current = basename($_SERVER['PHP_SELF']);
function is_active($file, $current) {
    return $current === $file ? 'active' : '';
}

// Proses Enkripsi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['encrypt'])) {
    $title = $_POST['judul'] ?? '';
    $pesan = $_POST['pesan'] ?? '';
    $rails = intval($_POST['rails'] ?? 3);

    if (!empty($title) && !empty($pesan)) {
        
        // 1. Lakukan Super Enkripsi (Rail Fence + ChaCha20)
        $encrypted_message = superEncrypt($pesan, $rails);
        $encrypted_message_display = $encrypted_message; // Simpan untuk ditampilkan

        // 2. Simpan ke database (Sesuai struktur baru)
        $stmt = $conn->prepare("INSERT INTO encrypted_messages (user_id, title, encrypted_message, rails, encrypted_method, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $method = 'RailFence+ChaCha20';
        $stmt->bind_param('issis', $user_id, $title, $encrypted_message, $rails, $method);
        
        if ($stmt->execute()) {
            $message = "Pesan berhasil dienkripsi dan disimpan ke database!";
        } else {
            $message = "Gagal menyimpan ke database: " . $stmt->error;
        }
        $stmt->close();

    } else {
        $message = "Judul dan Pesan harus diisi!";
    }
}

// Proses Dekripsi (Tab Dekripsi)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['decrypt'])) {
    $cipher_text = $_POST['cipher_text'] ?? '';
    $rails_decrypt = intval($_POST['rails_decrypt'] ?? 3);

    if (!empty($cipher_text)) {
        $result = superDecrypt($cipher_text, $rails_decrypt);
        
        if ($result !== false) {
            $decrypted_message = $result;
        } else {
            $error_decrypt = "Dekripsi gagal! Pastikan ciphertext dan jumlah rails benar.";
        }
    } else {
        $error_decrypt = "Ciphertext tidak boleh kosong!";
    }
}

// Ambil daftar pesan
$stmt_list = $conn->prepare("SELECT id, title, encrypted_message, rails, created_at FROM encrypted_messages WHERE user_id = ? ORDER BY created_at DESC");
$stmt_list->bind_param('i', $user_id);
$stmt_list->execute();
$result_list = $stmt_list->get_result();
$conn->close();
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
        /* (CSS tidak berubah dari file asli Anda) */
        * { font-family: 'Poppins', sans-serif; }
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
            min-height: 100vh;
            padding-top: 84px;
        }
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
        .main-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .page-title { text-align: center; margin-bottom: 30px; }
        .page-title h1 { font-weight: 700; color: #2c3e50; font-size: 2.5rem; margin-bottom: 10px; }
        .page-title p { color: #7f8c8d; font-size: 1rem; }
        .content-card { background: #fff; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,.08); padding: 40px; margin-bottom: 30px; }
        
        .info-box {
            background: #fff8dc; border-left: 4px solid #f0ad4e;
            padding: 20px 25px; margin-bottom: 30px; border-radius: 6px;
        }
        .info-box h5 {
            color: #856404; margin-bottom: 12px;
            font-size: 15px; font-weight: 600;
        }
        .info-box ul { list-style: none; padding: 0; margin: 0; }
        .info-box li {
            color: #856404; margin-bottom: 6px;
            font-size: 13.5px; line-height: 1.6;
        }
        
        .form-label { font-weight: 600; color: #2c3e50; margin-bottom: 10px; }
        .form-control, .form-control:focus, .form-select { border-radius: 10px; border: 2px solid #e9ecef; padding: 12px 15px; }
        .form-control:focus { border-color: #3498db; box-shadow: 0 0 0 .2rem rgba(52,152,219,.15); }
        textarea.form-control { min-height: 120px; }
        
        .btn-custom {
            background: #0d6efd; color: white; padding: 12px 28px;
            border: none; border-radius: 10px; font-weight: 600;
        }
        .btn-custom:hover { background: #0b5ed7; }
        .btn-custom-secondary {
            background: #6c757d; color: white; padding: 12px 28px;
            border: none; border-radius: 10px; font-weight: 600;
        }
        
        .result-box {
            background: #f8f9fa; padding: 20px; border-radius: 8px;
            margin-top: 20px; border: 1px solid #dee2e6;
        }
        .result-text {
            background: white; border: 1px solid #ddd; border-radius: 6px;
            padding: 12px; word-wrap: break-word; font-family: monospace;
            font-size: 13px;
        }
        .nav-tabs .nav-link { font-weight: 600; }
        .file-table { width: 100%; margin-top: 20px; }
        .file-table th { background: #f8f9fa; padding: 12px; font-weight: 600; }
        .file-table td { padding: 12px; border-bottom: 1px solid #e9ecef; vertical-align: middle; }
    </style>
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
        <div class="page-title">
            <h1>SUPER ENKRIPSI</h1>
            <p>Rail Fence Cipher + ChaCha20 Stream Cipher</p>
        </div>

        <div class="content-card">
            <div class="info-box">
                <h5>💡 Tentang Super Enkripsi</h5>
                <ul>
                    <li><strong>Algoritma:</strong> Rail Fence Cipher + ChaCha20</li>
                    <li><strong>Penyimpanan:</strong> Hasil enkripsi disimpan di database.</li>
                </ul>
            </div>

            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="encrypt-tab" data-bs-toggle="tab" data-bs-target="#encrypt-pane" type="button" role="tab">🔒 Enkripsi</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="decrypt-tab" data-bs-toggle="tab" data-bs-target="#decrypt-pane" type="button" role="tab">🔓 Dekripsi</button>
                </li>
            </ul>

            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="encrypt-pane" role="tabpanel" style="padding-top: 25px;">
                    <?php if (!empty($message) && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['encrypt'])): ?>
                        <div class="alert <?= strpos($message, 'berhasil') !== false ? 'alert-success' : 'alert-danger'; ?>">
                            <?= htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <h4 style="font-size: 16px; font-weight: 600;">Enkripsi Pesan Baru</h4>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Judul Pesan</label>
                            <input type="text" name="judul" class="form-control" required placeholder="Masukkan judul pesan">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Isi Pesan</label>
                            <textarea name="pesan" id="pesan" class="form-control" required placeholder="Masukkan pesan yang akan dienkripsi..." oninput="updateCharCount()"></textarea>
                            <div style="text-align:right;font-size:12px;color:#7f8c8d;">Karakter: <span id="charCount">0</span></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jumlah Rails</label>
                            <select name="rails" class="form-select">
                                <?php for ($i=3; $i<=10; $i++): ?>
                                    <option value="<?= $i; ?>"><?= $i; ?> Rails</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <button type="submit" name="encrypt" class="btn-custom">🔒 Super Enkripsi & Simpan</button>
                    </form>

                    <?php if (!empty($encrypted_message_display)): ?>
                        <div class="result-box">
                            <strong>✅ Hasil Super Enkripsi (Untuk di-copy):</strong>
                            <div class="result-text"><?= htmlspecialchars($encrypted_message_display); ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="tab-pane fade" id="decrypt-pane" role="tabpanel" style="padding-top: 25px;">
                    <?php if (!empty($error_decrypt) && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['decrypt'])): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error_decrypt); ?>
                        </div>
                    <?php endif; ?>

                    <h4 style="font-size: 16px; font-weight: 600;">Dekripsi Pesan (Super Enkripsi)</h4>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Ciphertext (Teks Super Enkripsi)</label>
                            <textarea name="cipher_text" class="form-control" required placeholder="Masukkan teks yang akan didekripsi..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jumlah Rails</label>
                            <select name="rails_decrypt" class="form-select">
                                <?php for ($i=3; $i<=10; $i++): ?>
                                    <option value="<?= $i; ?>"><?= $i; ?> Rails</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <button type="submit" name="decrypt" class="btn-custom-secondary">🔓 Dekripsi Pesan</button>
                    </form>

                    <?php if (!empty($decrypted_message)): ?>
                        <div class="result-box">
                            <strong>✅ Hasil Dekripsi:</strong>
                            <div class="result-text" style="font-family: 'Poppins', sans-serif; font-size: 14px;">
                                <?= nl2br(htmlspecialchars($decrypted_message)); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="content-card">
            <h4 class="mb-4">Pesan Tersimpan Anda</h4>
            <?php if ($result_list->num_rows > 0): ?>
                <table class="file-table table table-hover">
                    <thead>
                        <tr>
                            <th>Judul</th>
                            <th>Ciphertext (Klik untuk copy)</th>
                            <th>Rails</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result_list->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['title']); ?></td>
                                <td style="font-family: monospace; font-size: 12px; max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <span title="Klik untuk menyalin" style="cursor: pointer;"
                                          onclick="copyToClipboard('<?= htmlspecialchars($row['encrypted_message']); ?>', this)">
                                        <?= htmlspecialchars($row['encrypted_message']); ?>
                                    </span>
                                </td>
                                <td><?= $row['rails']; ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">Belum ada pesan terenkripsi.</div>
            <?php endif; ?>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateCharCount() {
            const text = document.getElementById('pesan');
            document.getElementById('charCount').textContent = text.value.length;
        }

        function copyToClipboard(text, element) {
            navigator.clipboard.writeText(text).then(function() {
                const originalText = element.innerHTML;
                element.innerHTML = "✅ Tersalin!";
                setTimeout(() => {
                    element.innerHTML = originalText;
                }, 1500);
            }, function(err) {
                console.error('Gagal menyalin: ', err);
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            let activeTab = '<?php echo (isset($_POST['decrypt'])) ? "#decrypt-tab" : "#encrypt-tab"; ?>';
            let tab = document.querySelector(activeTab);
            if (tab) {
                new bootstrap.Tab(tab).show();
            }
        });
    </script>
</body>
</html>
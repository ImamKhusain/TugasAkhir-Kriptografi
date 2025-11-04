<?php
require_once 'config.php'; // Menggunakan config terpusat

if (!isset($_SESSION['user_id'])) { // Cek user_id
    header("Location: login/login.php?pesan=belum_login");
    exit;
}
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$conn = getDBConnection();

$current = basename($_SERVER['PHP_SELF']);
function is_active($file, $current) {
    return $current === $file ? 'active' : '';
}

/* ==============================
   Proses EMBED (LSB) - Diperbarui dengan Download
============================== */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'embed') {
    if (isset($_FILES['gambar']) && isset($_POST['pesan'])) {
        $gambar = $_FILES['gambar'];
        $pesan  = $_POST['pesan'];
        $allowed = ['image/png', 'image/jpeg', 'image/jpg'];

        if (in_array($gambar['type'], $allowed)) {
            $img = ($gambar['type'] == 'image/png')
                ? @imagecreatefrompng($gambar['tmp_name'])
                : @imagecreatefromjpeg($gambar['tmp_name']);

            if ($img) {
                $width  = imagesx($img);
                $height = imagesy($img);
                
                $pesan_full = $pesan . '###END###'; // Delimiter
                $pesan_binary = '';
                for ($i = 0; $i < strlen($pesan_full); $i++) {
                    $pesan_binary .= str_pad(decbin(ord($pesan_full[$i])), 8, '0', STR_PAD_LEFT);
                }

                $pesan_length = strlen($pesan_binary);
                $max_capacity = $width * $height * 3; 

                if ($pesan_length <= $max_capacity) {
                    $index = 0;
                    for ($y = 0; $y < $height && $index < $pesan_length; $y++) {
                        for ($x = 0; $x < $width && $index < $pesan_length; $x++) {
                            $rgb = imagecolorat($img, $x, $y);
                            $r = ($rgb >> 16) & 0xFF;
                            $g = ($rgb >> 8) & 0xFF;
                            $b = $rgb & 0xFF;
                            if ($index < $pesan_length) { $r = ($r & 0xFE) | intval($pesan_binary[$index]); $index++; }
                            if ($index < $pesan_length) { $g = ($g & 0xFE) | intval($pesan_binary[$index]); $index++; }
                            if ($index < $pesan_length) { $b = ($b & 0xFE) | intval($pesan_binary[$index]); $index++; }
                            $new_color = imagecolorallocate($img, $r, $g, $b);
                            imagesetpixel($img, $x, $y, $new_color);
                        }
                    }

                    // --- LOGIKA BARU: Simpan ke DB + Force Download ---
                    $original_filename = SecurityConfig::sanitizeInput($gambar['name']);
                    $stego_filename = 'stego_' . $user_id . '_' . time() . '.png';
                    $output_dir = 'uploads/';
                    $output_path = $output_dir . $stego_filename;
                    
                    if (!file_exists($output_dir)) { 
                        mkdir($output_dir, 0777, true); 
                    }
                    
                    imagepng($img, $output_path); // Simpan file ke server
                    imagedestroy($img);

                    // Siapkan data untuk DB
                    $message_preview = substr($pesan, 0, 100);
                    $method = 'LSB';
                    
                    $stmt = $conn->prepare("INSERT INTO steganografi (user_id, original_filename, stego_filename, stego_path, hidden_message_preview, method, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param('isssss', $user_id, $original_filename, $stego_filename, $output_path, $message_preview, $method);
                    
                    if ($stmt->execute()) {
                        // Berhasil simpan DB, paksa download
                        $stmt->close();
                        $conn->close();
                        
                        header('Content-Description: File Transfer');
                        header('Content-Type: image/png');
                        header('Content-Disposition: attachment; filename="' . basename($stego_filename) . '"');
                        header('Expires: 0');
                        header('Cache-Control: must-revalidate');
                        header('Pragma: public');
                        header('Content-Length: ' . filesize($output_path));
                        flush(); // Flush system output buffer
                        readfile($output_path);
                        exit; // Stop script

                    } else {
                        $error_embed = "Gagal menyimpan ke database: " . $stmt->error;
                        unlink($output_path); // Hapus file jika gagal simpan DB
                    }
                    $stmt->close();
                    
                } else {
                    $error_embed = "Pesan terlalu panjang untuk gambar ini!";
                }
            } else {
                $error_embed = "Gagal membaca gambar.";
            }
        } else {
            $error_embed = "Format file tidak didukung! Gunakan PNG/JPG.";
        }
    }
}

/* ==============================
   Proses EXTRACT (LSB)
============================== */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'extract') {
    if (isset($_FILES['gambar_extract'])) {
        $gambar  = $_FILES['gambar_extract'];
        $allowed = ['image/png', 'image/jpeg', 'image/jpg'];

        if (in_array($gambar['type'], $allowed)) {
            $img = ($gambar['type'] == 'image/png')
                ? @imagecreatefrompng($gambar['tmp_name'])
                : @imagecreatefromjpeg($gambar['tmp_name']);

            if ($img) {
                $width  = imagesx($img);
                $height = imagesy($img);
                $binary_data = '';
                for ($y = 0; $y < $height; $y++) {
                    for ($x = 0; $x < $width; $x++) {
                        $rgb = imagecolorat($img, $x, $y);
                        $r = ($rgb >> 16) & 0xFF;
                        $g = ($rgb >> 8) & 0xFF;
                        $b = $rgb & 0xFF;
                        $binary_data .= ($r & 1);
                        $binary_data .= ($g & 1);
                        $binary_data .= ($b & 1);
                    }
                }
                $extracted_message = '';
                for ($i = 0; $i < strlen($binary_data); $i += 8) {
                    $byte = substr($binary_data, $i, 8);
                    if (strlen($byte) == 8) {
                        $char = chr(bindec($byte));
                        $extracted_message .= $char;
                        if (strpos($extracted_message, '###END###') !== false) {
                            $extracted_message = str_replace('###END###', '', $extracted_message);
                            break;
                        }
                    }
                }
                imagedestroy($img);
                if (!empty($extracted_message)) {
                    $success_extract = $extracted_message;
                } else {
                    $error_extract = "Tidak ada pesan yang ditemukan dalam gambar ini!";
                }
            } else {
                $error_extract = "Gagal membaca gambar.";
            }
        } else {
            $error_extract = "Format file tidak didukung! Gunakan PNG/JPG.";
        }
    }
}

// Ambil daftar file steganografi
$stmt_list = $conn->prepare("SELECT * FROM steganografi WHERE user_id = ? ORDER BY created_at DESC");
$stmt_list->bind_param('i', $user_id);
$stmt_list->execute();
$stego_files = $stmt_list->get_result();
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Steganografi | LSB Method</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
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
        .welcome-text { line-height: 1.2; }
        .welcome-text h5 {
            margin: 0;
            font-weight: 600;
            color: #2c3e50;
            font-size: 13px;
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
        .main-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .page-title { text-align: center; margin-bottom: 30px; }
        .page-title h1 { font-weight: 700; color: #2c3e50; font-size: 2.5rem; margin-bottom: 10px; }
        .page-title p { color: #7f8c8d; font-size: 1rem; }
        .nav-tabs { border: none; margin-bottom: 30px; }
        .nav-tabs .nav-link {
            border: none; color: #7f8c8d; font-weight: 600;
            padding: 12px 30px; margin-right: 10px;
            border-radius: 10px 10px 0 0; transition: .3s;
        }
        .nav-tabs .nav-link:hover { color: #3498db; background: rgba(52,152,219,.1); }
        .nav-tabs .nav-link.active { color: #3498db; background: #fff; border-bottom: 3px solid #3498db; }
        .content-card { background: #fff; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,.08); padding: 40px; margin-bottom: 30px; }
        .info-box { background: #fce4ec; border: 1px solid #f8bbd0; border-radius: 10px; padding: 20px; margin-bottom: 30px; }
        .info-box h5 { color: #c2185b; font-weight: 600; margin-bottom: 15px; }
        .info-box ul { margin: 0; padding-left: 20px; }
        .info-box li { color: #880e4f; font-size: 14px; margin-bottom: 8px; }
        .form-label { font-weight: 600; color: #2c3e50; margin-bottom: 10px; }
        .form-control, .form-control:focus { border-radius: 10px; border: 2px solid #e9ecef; padding: 12px 15px; }
        .form-control:focus { border-color: #3498db; box-shadow: 0 0 0 .2rem rgba(52,152,219,.15); }
        .btn-primary-custom { background: #3498db; border: none; border-radius: 10px; padding: 12px 30px; font-weight: 600; color: #fff; transition: .3s; }
        .btn-primary-custom:hover { background: #2980b9; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(52,152,219,.3); }
        
        /* Menggunakan style tombol dari enkripsi_file.php agar konsisten */
        .btn-success-custom { 
            background: #27ae60; border: none; border-radius: 10px; 
            padding: 8px 20px; /* Disesuaikan agar pas di tabel */
            font-weight: 600; color: #fff; text-decoration: none; display: inline-block;
        }
        .btn-success-custom:hover { background: #229954; transform: translateY(-2px); }
        .btn-success-custom.btn-lg { /* Tombol besar untuk form ekstrak */
             padding: 12px 30px; 
        }

        .preview-container { border: 2px dashed #e9ecef; border-radius: 10px; padding: 20px; margin-top: 20px; }
        .preview-container img { max-width: 100%; max-height: 400px; border-radius: 10px; }
        .result-box { background: #e8f5e9; border: 1px solid #c8e6c9; border-radius: 10px; padding: 20px; margin-top: 20px; }
        .result-box h5 { color: #2e7d32; font-weight: 600; margin-bottom: 15px; }
        .result-content { background: #fff; padding: 15px; border-radius: 8px; color: #2c3e50; word-wrap: break-word; }
        .info-section { background: #e3f2fd; border: 1px solid #bbdefb; border-radius: 10px; padding: 25px; margin-top: 30px; }
        .info-section h5 { color: #1565c0; font-weight: 600; margin-bottom: 15px; }
        .info-section p { color: #1976d2; font-size: 14px; line-height: 1.8; }
        .file-name, .char-count { color: #7f8c8d; font-size: 14px; margin-top: 8px; }
        .file-table { width: 100%; margin-top: 20px; }
        .file-table th { background: #f8f9fa; padding: 12px; font-weight: 600; }
        .file-table td { padding: 12px; border-bottom: 1px solid #e9ecef; vertical-align: middle; }
        @media (max-width: 900px) {
            .nav-center { position: static; transform: none; justify-content: center; gap: 24px; }
            .header-inner { padding: 10px 16px; }
        }
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
            <nav class="nav-center" role="navigation" aria-label="Main navigation">
                <a href="steganografi.php"   class="<?= is_active('steganografi.php', $current); ?>">STEGANOGRAFI</a>
                <a href="super_enkripsi.php" class="<?= is_active('super_enkripsi.php', $current); ?>">SUPER ENKRIPSI</a>
                <a href="enkripsi_file.php"  class="<?= is_active('enkripsi_file.php', $current); ?>">ENKRIPSI FILE</a>
            </nav>
            <div class="logout-btn">
                <a href="login/logout.php" class="btn btn-light btn-sm px-3">LogOut</a>
            </div>
        </div>
    </header>

    <div class="main-container">
        <div class="page-title">
            <h1>STEGANOGRAFI</h1>
            <p>LSB (Least Significant Bit) Steganography</p>
        </div>

        <ul class="nav nav-tabs" id="steganografiTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="sembunyikan-tab" data-bs-toggle="tab" data-bs-target="#sembunyikan" type="button" role="tab">
                    🔒 Sembunyikan Pesan
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="ekstrak-tab" data-bs-toggle="tab" data-bs-target="#ekstrak" type="button" role="tab">
                    🔓 Ekstrak Pesan
                </button>
            </li>
        </ul>

        <div class="tab-content" id="steganografiTabContent">
            <div class="tab-pane fade show active" id="sembunyikan" role="tabpanel">
                <div class="content-card">
                    <div class="info-box">
                        <h5>ℹ️ Tentang LSB Steganografi</h5>
                        <ul>
                            <li><strong>Method:</strong> LSB (Least Significant Bit) pada pixel gambar</li>
                            <li><strong>Penyimpanan:</strong> File stego disimpan di folder /uploads dan dicatat di database.</li>
                            <li><strong>Download:</strong> File akan otomatis terdownload setelah berhasil dibuat.</li>
                        </ul>
                    </div>

                    <?php if (isset($error_embed)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error_embed); ?></div>
                    <?php endif; ?>
                    
                    <h4 class="mb-4">Sembunyikan Pesan dalam Gambar</h4>

                    <form action="" method="POST" enctype="multipart/form-data" id="embedForm">
                        <input type="hidden" name="action" value="embed">
                        <div class="mb-4">
                            <label class="form-label">Upload Gambar (PNG/JPG/JPEG)</label>
                            <input type="file" class="form-control" name="gambar" id="gambarInput" accept="image/png,image/jpeg,image/jpg" required onchange="previewImage(this, 'previewEmbed')">
                            <div id="fileNameEmbed" class="file-name"></div>
                        </div>
                        <div id="previewEmbed" class="preview-container" style="display:none;">
                            <label class="form-label">Preview Gambar</label>
                            <div class="text-center">
                                <img id="imgPreviewEmbed" src="" alt="Preview">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Pesan Rahasia</label>
                            <textarea class="form-control" name="pesan" id="pesanInput" rows="5" placeholder="Masukkan pesan yang ingin disembunyikan..." required oninput="countChars()"></textarea>
                            <div id="charCount" class="char-count">Karakter: 0</div>
                        </div>
                        <button type="submit" class="btn btn-primary-custom w-100">🔒 Sembunyikan, Simpan & Download</button>
                    </form>
                </div>
            </div>

            <div class="tab-pane fade" id="ekstrak" role="tabpanel">
                <div class="content-card">
                    <h4 class="mb-4">Ekstrak Pesan dari Gambar</h4>
                    <?php if (isset($error_extract)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error_extract); ?></div>
                    <?php endif; ?>

                    <form action="" method="POST" enctype="multipart/form-data" id="extractForm">
                        <input type="hidden" name="action" value="extract">
                        <div class="mb-4">
                            <label class="form-label">Upload Gambar (PNG/JPG/JPEG)</label>
                            <input type="file" class="form-control" name="gambar_extract" id="gambarExtractInput" accept="image/png,image/jpeg,image/jpg" required onchange="previewImage(this, 'previewExtract')">
                            <div id="fileNameExtract" class="file-name"></div>
                        </div>
                        <div id="previewExtract" class="preview-container" style="display:none;">
                            <label class="form-label">Preview Gambar</label>
                            <div class="text-center">
                                <img id="imgPreviewExtract" src="" alt="Preview">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success-custom btn-lg w-100">🔓 Ekstrak Pesan</button>
                    </form>

                    <?php if (isset($success_extract)): ?>
                        <div class="result-box">
                            <h5>👁️ Pesan Berhasil Diekstrak</h5>
                            <div class="result-content">
                                <?= nl2br(htmlspecialchars($success_extract)); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="content-card">
            <h4 class="mb-4">File Steganografi Tersimpan Anda</h4>
            <?php if ($stego_files->num_rows > 0): ?>
                <table class="file-table table table-hover">
                    <thead>
                        <tr>
                            <th>File Asli</th>
                            <th>File Stego (Klik untuk download)</th>
                            <th>Preview Pesan</th>
                            <th>Metode</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($file = $stego_files->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($file['original_filename']); ?></td>
                                <td>
                                    <a href="<?= htmlspecialchars($file['stego_path']); ?>" download>
                                        <?= htmlspecialchars($file['stego_filename']); ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($file['hidden_message_preview']); ?>...</td>
                                <td><?= htmlspecialchars($file['method']); ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($file['created_at'])); ?></td>
                                <td>
                                    <a href="<?= htmlspecialchars($file['stego_path']); ?>" class="btn btn-success-custom" download>
                                        📥 Download
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">Belum ada file steganografi yang disimpan.</div>
            <?php endif; ?>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            const img = document.getElementById('imgPreview' + previewId.charAt(7).toUpperCase() + previewId.slice(8));
            const fileName = document.getElementById('fileName' + previewId.charAt(7).toUpperCase() + previewId.slice(8));

            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    img.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
                fileName.textContent = '📁 ' + input.files[0].name;
            }
        }

        function countChars() {
            const textarea = document.getElementById('pesanInput');
            const charCount = document.getElementById('charCount');
            if (textarea) {
                charCount.textContent = 'Karakter: ' + (textarea.value.length || 0);
            }
        }
        
        const pesanInput = document.getElementById('pesanInput');
        if (pesanInput) {
            pesanInput.addEventListener('input', countChars);
            countChars(); // Panggil saat load
        }

        <?php if (isset($success_extract)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const extractTab = new bootstrap.Tab(document.getElementById('ekstrak-tab'));
            extractTab.show();
        });
        <?php endif; ?>
    </script>
</body>
</html>
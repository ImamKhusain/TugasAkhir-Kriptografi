<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login/login.php?pesan=belum_login");
    exit;
}
$username = $_SESSION['username'];

// Proses Embed Message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'embed') {
    if (isset($_FILES['gambar']) && isset($_POST['pesan'])) {
        $gambar = $_FILES['gambar'];
        $pesan = $_POST['pesan'];
        
        // Validasi file
        $allowed = ['image/png', 'image/jpeg', 'image/jpg'];
        if (in_array($gambar['type'], $allowed)) {
            // Baca gambar
            $img = null;
            if ($gambar['type'] == 'image/png') {
                $img = imagecreatefrompng($gambar['tmp_name']);
            } else {
                $img = imagecreatefromjpeg($gambar['tmp_name']);
            }
            
            if ($img) {
                $width = imagesx($img);
                $height = imagesy($img);
                
                // Tambahkan delimiter untuk menandai akhir pesan
                $pesan = $pesan . '###END###';
                $pesan_binary = '';
                
                // Konversi pesan ke binary
                for ($i = 0; $i < strlen($pesan); $i++) {
                    $pesan_binary .= str_pad(decbin(ord($pesan[$i])), 8, '0', STR_PAD_LEFT);
                }
                
                $pesan_length = strlen($pesan_binary);
                $max_capacity = $width * $height * 3; // RGB channels
                
                if ($pesan_length <= $max_capacity) {
                    $index = 0;
                    
                    // Embed pesan ke dalam pixel
                    for ($y = 0; $y < $height && $index < $pesan_length; $y++) {
                        for ($x = 0; $x < $width && $index < $pesan_length; $x++) {
                            $rgb = imagecolorat($img, $x, $y);
                            $r = ($rgb >> 16) & 0xFF;
                            $g = ($rgb >> 8) & 0xFF;
                            $b = $rgb & 0xFF;
                            
                            // Embed bit ke LSB dari R
                            if ($index < $pesan_length) {
                                $r = ($r & 0xFE) | intval($pesan_binary[$index]);
                                $index++;
                            }
                            
                            // Embed bit ke LSB dari G
                            if ($index < $pesan_length) {
                                $g = ($g & 0xFE) | intval($pesan_binary[$index]);
                                $index++;
                            }
                            
                            // Embed bit ke LSB dari B
                            if ($index < $pesan_length) {
                                $b = ($b & 0xFE) | intval($pesan_binary[$index]);
                                $index++;
                            }
                            
                            $new_color = imagecolorallocate($img, $r, $g, $b);
                            imagesetpixel($img, $x, $y, $new_color);
                        }
                    }
                    
                    // Simpan gambar hasil
                    $output_path = 'uploads/stego_' . time() . '.png';
                    if (!file_exists('uploads')) {
                        mkdir('uploads', 0777, true);
                    }
                    imagepng($img, $output_path);
                    imagedestroy($img);
                    
                    // Download file
                    header('Content-Type: image/png');
                    header('Content-Disposition: attachment; filename="stego_image.png"');
                    readfile($output_path);
                    unlink($output_path);
                    exit;
                } else {
                    $error_embed = "Pesan terlalu panjang untuk gambar ini!";
                }
            }
        } else {
            $error_embed = "Format file tidak didukung! Gunakan PNG/JPG.";
        }
    }
}

// Proses Extract Message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'extract') {
    if (isset($_FILES['gambar_extract'])) {
        $gambar = $_FILES['gambar_extract'];
        
        // Validasi file
        $allowed = ['image/png', 'image/jpeg', 'image/jpg'];
        if (in_array($gambar['type'], $allowed)) {
            // Baca gambar
            $img = null;
            if ($gambar['type'] == 'image/png') {
                $img = imagecreatefrompng($gambar['tmp_name']);
            } else {
                $img = imagecreatefromjpeg($gambar['tmp_name']);
            }
            
            if ($img) {
                $width = imagesx($img);
                $height = imagesy($img);
                $binary_data = '';
                
                // Ekstrak bit dari pixel
                for ($y = 0; $y < $height; $y++) {
                    for ($x = 0; $x < $width; $x++) {
                        $rgb = imagecolorat($img, $x, $y);
                        $r = ($rgb >> 16) & 0xFF;
                        $g = ($rgb >> 8) & 0xFF;
                        $b = $rgb & 0xFF;
                        
                        // Ambil LSB dari setiap channel
                        $binary_data .= ($r & 1);
                        $binary_data .= ($g & 1);
                        $binary_data .= ($b & 1);
                    }
                }
                
                // Konversi binary ke text
                $extracted_message = '';
                for ($i = 0; $i < strlen($binary_data); $i += 8) {
                    $byte = substr($binary_data, $i, 8);
                    if (strlen($byte) == 8) {
                        $char = chr(bindec($byte));
                        $extracted_message .= $char;
                        
                        // Cek delimiter
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
            }
        } else {
            $error_extract = "Format file tidak didukung! Gunakan PNG/JPG.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Steganografi | LSB Method</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
            min-height: 100vh;
            padding-top: 80px;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: 70px;
        }

        .welcome-text h5 {
            margin: 0;
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }

        .welcome-text span {
            color: #3498db;
            font-size: 16px;
        }

        .menu-links a {
            color: #2c3e50;
            text-decoration: none;
            margin: 0 15px;
            font-weight: 500;
            font-size: 14px;
            transition: color 0.3s;
        }

        .menu-links a:hover,
        .menu-links a.active {
            color: #3498db;
        }

        .main-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-title {
            text-align: center;
            margin-bottom: 30px;
        }

        .page-title h1 {
            font-weight: 700;
            color: #2c3e50;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .page-title p {
            color: #7f8c8d;
            font-size: 1rem;
        }

        .nav-tabs {
            border: none;
            margin-bottom: 30px;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #7f8c8d;
            font-weight: 600;
            padding: 12px 30px;
            margin-right: 10px;
            border-radius: 10px 10px 0 0;
            transition: all 0.3s;
        }

        .nav-tabs .nav-link:hover {
            color: #3498db;
            background-color: rgba(52, 152, 219, 0.1);
        }

        .nav-tabs .nav-link.active {
            color: #3498db;
            background-color: white;
            border-bottom: 3px solid #3498db;
        }

        .content-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            padding: 40px;
        }

        .info-box {
            background: #fce4ec;
            border: 1px solid #f8bbd0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .info-box h5 {
            color: #c2185b;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .info-box ul {
            margin: 0;
            padding-left: 20px;
        }

        .info-box li {
            color: #880e4f;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .form-control, .form-control:focus {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
        }

        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.15);
        }

        .btn-primary-custom {
            background: #3498db;
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            color: white;
            transition: all 0.3s;
        }

        .btn-primary-custom:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        .btn-success-custom {
            background: #27ae60;
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            color: white;
            transition: all 0.3s;
        }

        .btn-success-custom:hover {
            background: #229954;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }

        .preview-container {
            border: 2px dashed #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }

        .preview-container img {
            max-width: 100%;
            max-height: 400px;
            border-radius: 10px;
        }

        .result-box {
            background: #e8f5e9;
            border: 1px solid #c8e6c9;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }

        .result-box h5 {
            color: #2e7d32;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .result-content {
            background: white;
            padding: 15px;
            border-radius: 8px;
            color: #2c3e50;
            word-wrap: break-word;
        }

        .info-section {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 10px;
            padding: 25px;
            margin-top: 30px;
        }

        .info-section h5 {
            color: #1565c0;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .info-section p {
            color: #1976d2;
            font-size: 14px;
            line-height: 1.8;
        }

        .file-name {
            color: #7f8c8d;
            font-size: 14px;
            margin-top: 10px;
        }

        .char-count {
            color: #7f8c8d;
            font-size: 14px;
            margin-top: 8px;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar fixed-top px-4">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <div class="welcome-text">
                <a href="dashboard.php"><h5>WELCOME <br><span>(<?= htmlspecialchars($username); ?>)</span></h5></a>
            </div>

            <div class="menu-links">
                <a href="steganografi.php" class="active">STEGANOGRAFI</a>
                <a href="super_enkripsi.php">SUPER ENKRIPSI</a>
                <a href="enkripsi_file.php">ENKRIPSI FILE</a>
            </div>

            <div class="logout-btn">
                <a href="login/logout.php" class="btn btn-light btn-sm px-3">LogOut</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-container">
        <div class="page-title">
            <h1>STEGANOGRAFI</h1>
            <p>LSB (Least Significant Bit) Steganography</p>
        </div>

        <!-- Tabs -->
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

        <!-- Tab Content -->
        <div class="tab-content" id="steganografiTabContent">
            
            <!-- Sembunyikan Pesan Tab -->
            <div class="tab-pane fade show active" id="sembunyikan" role="tabpanel">
                <div class="content-card">
                    <!-- Info Box -->
                    <div class="info-box">
                        <h5>ℹ️ Tentang LSB Steganografi</h5>
                        <ul>
                            <li><strong>Method:</strong> LSB (Least Significant Bit) pada pixel gambar</li>
                            <li><strong>Format:</strong> PNG/JPG (PNG lebih baik untuk hasil lossless)</li>
                            <li><strong>Keuntungan:</strong> Tidak terlihat oleh mata manusia</li>
                            <li><strong>Format:</strong> PNG dan JPG tidak dikompresi ke PNG</li>
                        </ul>
                    </div>

                    <?php if (isset($error_embed)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Error!</strong> <?= htmlspecialchars($error_embed); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <h4 class="mb-4">Sembunyikan Pesan dalam Gambar</h4>

                    <form action="" method="POST" enctype="multipart/form-data" id="embedForm">
                        <input type="hidden" name="action" value="embed">
                        
                        <!-- Upload Gambar -->
                        <div class="mb-4">
                            <label class="form-label">Upload Gambar (PNG/JPG/JPEG)</label>
                            <input type="file" class="form-control" name="gambar" id="gambarInput" accept="image/png,image/jpeg,image/jpg" required onchange="previewImage(this, 'previewEmbed')">
                            <div id="fileNameEmbed" class="file-name"></div>
                        </div>

                        <!-- Preview -->
                        <div id="previewEmbed" class="preview-container" style="display:none;">
                            <label class="form-label">Preview Gambar</label>
                            <div class="text-center">
                                <img id="imgPreviewEmbed" src="" alt="Preview">
                            </div>
                        </div>

                        <!-- Pesan Rahasia -->
                        <div class="mb-4">
                            <label class="form-label">Pesan Rahasia</label>
                            <textarea class="form-control" name="pesan" id="pesanInput" rows="5" placeholder="Masukkan pesan yang ingin disembunyikan..." required oninput="countChars()"></textarea>
                            <div id="charCount" class="char-count">Karakter: 0</div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary-custom w-100">
                            🔒 Sembunyikan Pesan
                        </button>
                    </form>
                </div>
            </div>

            <!-- Ekstrak Pesan Tab -->
            <div class="tab-pane fade" id="ekstrak" role="tabpanel">
                <div class="content-card">
                    <h4 class="mb-4">Ekstrak Pesan dari Gambar</h4>

                    <?php if (isset($error_extract)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Error!</strong> <?= htmlspecialchars($error_extract); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" enctype="multipart/form-data" id="extractForm">
                        <input type="hidden" name="action" value="extract">
                        
                        <!-- Upload Gambar -->
                        <div class="mb-4">
                            <label class="form-label">Upload Gambar (PNG/JPG/JPEG)</label>
                            <input type="file" class="form-control" name="gambar_extract" id="gambarExtractInput" accept="image/png,image/jpeg,image/jpg" required onchange="previewImage(this, 'previewExtract')">
                            <div id="fileNameExtract" class="file-name"></div>
                        </div>

                        <!-- Preview -->
                        <div id="previewExtract" class="preview-container" style="display:none;">
                            <label class="form-label">Preview Gambar</label>
                            <div class="text-center">
                                <img id="imgPreviewExtract" src="" alt="Preview">
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-success-custom w-100">
                            🔓 Ekstrak Pesan
                        </button>
                    </form>

                    <!-- Result Box -->
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

        <!-- Info Section -->
        <div class="info-section">
            <h5>📚 Cara Kerja LSB Steganografi</h5>
            <p>
                LSB (Least Significant Bit) Steganography menyembunyikan data dengan memodifikasi bit 
                terakhir dari setiap byte pixel gambar. Karena perubahan pada bit terakhir sangat kecil, 
                mata manusia tidak dapat membedakan gambar asli dengan gambar yang sudah berisi pesan tersembunyi.
            </p>
            <div class="row mt-3">
                <div class="col-md-6">
                    <strong>Kelebihan:</strong>
                    <ul class="mt-2">
                        <li>Tidak terdeteksi secara visual</li>
                        <li>Mudah diimplementasikan</li>
                        <li>Kapasitas data cukup besar</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <strong>Catatan Penting:</strong>
                    <ul class="mt-2">
                        <li>Gunakan format PNG untuk hasil terbaik</li>
                        <li>Hindari kompresi ulang gambar</li>
                        <li>Simpan kunci rahasia dengan aman</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
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
            charCount.textContent = 'Karakter: ' + textarea.value.length;
        }

        <?php if (isset($success_extract)): ?>
        // Auto switch to extract tab if extraction successful
        document.addEventListener('DOMContentLoaded', function() {
            const extractTab = new bootstrap.Tab(document.getElementById('ekstrak-tab'));
            extractTab.show();
        });
        <?php endif; ?>
    </script>
</body>
</html>
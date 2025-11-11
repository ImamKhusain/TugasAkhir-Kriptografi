<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login/login.php?pesan=belum_login");
    exit;
}
$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'];
$conn     = getDBConnection();

$current = basename($_SERVER['PHP_SELF']);
function is_active($file, $current)
{
    return $current === $file ? 'active' : '';
}

const END_MARKER   = '###END###';
const ALLOWED_MIME = ['image/png', 'image/jpeg', 'image/jpg'];

function safeSanitize($s)
{
    if (class_exists('SecurityConfig') && method_exists('SecurityConfig', 'sanitizeInput')) {
        return SecurityConfig::sanitizeInput($s);
    }
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function detectMime($path)
{
    if (!is_file($path)) return null;
    if (function_exists('finfo_open')) {
        $f = finfo_open(FILEINFO_MIME_TYPE);
        $m = finfo_file($f, $path);
        finfo_close($f);
        return $m;
    }
    return mime_content_type($path);
}

function openImage(string $path, ?string $mime)
{
    if (!$mime) $mime = detectMime($path);
    if ($mime === 'image/png') return @imagecreatefrompng($path);
    if ($mime === 'image/jpeg' || $mime === 'image/jpg') return @imagecreatefromjpeg($path);
    return false;
}

function savePng($img, string $path): bool
{
    imagealphablending($img, true);
    imagesavealpha($img, true);
    return imagepng($img, $path);
}

function bytesFromString(string $text): array
{
    $out = [];
    $len = strlen($text);
    for ($i = 0; $i < $len; $i++) $out[] = ord($text[$i]);
    return $out;
}

function embedLSB($img, string $message)
{
    $w = imagesx($img);
    $h = imagesy($img);

    $payload = $message . END_MARKER;
    $bytes   = bytesFromString($payload);
    $bits    = [];
    foreach ($bytes as $byte) {
        for ($b = 7; $b >= 0; $b--) {
            $bits[] = ($byte >> $b) & 1;
        }
    }

    $capacity = $w * $h * 3;
    if (count($bits) > $capacity) {
        return "Pesan terlalu panjang untuk gambar ini! (kapasitas {$capacity} bit, butuh " . count($bits) . " bit)";
    }

    $k = 0;
    $total = count($bits);
    for ($y = 0; $y < $h && $k < $total; $y++) {
        for ($x = 0; $x < $w && $k < $total; $x++) {
            $rgb = imagecolorat($img, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8)  & 0xFF;
            $b =  $rgb        & 0xFF;

            if ($k < $total) {
                $r = ($r & 0xFE) | $bits[$k++];
            }
            if ($k < $total) {
                $g = ($g & 0xFE) | $bits[$k++];
            }
            if ($k < $total) {
                $b = ($b & 0xFE) | $bits[$k++];
            }

            $col = imagecolorallocate($img, $r, $g, $b);
            imagesetpixel($img, $x, $y, $col);
        }
    }
    return true;
}

function extractLSB($img): string
{
    $w = imagesx($img);
    $h = imagesy($img);
    $buffer = '';
    $byte = 0;
    $bitsIn = 0;

    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $rgb = imagecolorat($img, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8)  & 0xFF;
            $b =  $rgb        & 0xFF;

            foreach ([($r & 1), ($g & 1), ($b & 1)] as $bit) {
                $byte = ($byte << 1) | $bit;
                $bitsIn++;
                if ($bitsIn === 8) {
                    $buffer .= chr($byte);
                    if (substr($buffer, -strlen(END_MARKER)) === END_MARKER) {
                        return substr($buffer, 0, -strlen(END_MARKER));
                    }
                    $byte = 0;
                    $bitsIn = 0;
                }
            }
        }
    }
    return '';
}

function insertStegoRecord(mysqli $conn, int $user_id, string $original_filename, string $stego_filename, string $stego_path, string $message_preview): bool
{
    $sql = "INSERT INTO steganografi (user_id, original_filename, stego_filename, stego_path, hidden_message_preview, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('issss', $user_id, $original_filename, $stego_filename, $stego_path, $message_preview);
    $ok = $stmt->execute();
    if (!$ok) {
        error_log('Stego insert error: ' . $stmt->error);
    }
    $stmt->close();
    return $ok;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'embed') {
    if (!empty($_FILES['gambar']['tmp_name']) && isset($_POST['pesan'])) {
        $tmp   = $_FILES['gambar']['tmp_name'];
        $mime  = detectMime($tmp);
        $pesan = $_POST['pesan'];

        if (!in_array($mime, ALLOWED_MIME, true)) {
            $error_embed = "Format file tidak didukung! Gunakan PNG/JPG.";
        } else {
            $img = openImage($tmp, $mime);
            if (!$img) {
                $error_embed = "Gagal membaca gambar. Pastikan ekstensi GD aktif.";
            } else {
                $embedRes = embedLSB($img, $pesan);
                if ($embedRes === true) {
                    $original_filename = safeSanitize($_FILES['gambar']['name']);
                    $stego_filename    = 'stego_' . $user_id . '_' . time() . '.png';
                    $output_dir        = 'uploads/';
                    if (!file_exists($output_dir)) mkdir($output_dir, 0777, true);
                    $output_path       = $output_dir . $stego_filename;

                    savePng($img, $output_path);
                    imagedestroy($img);

                    $message_preview = substr($pesan, 0, 100);

                    // simpan DB
                    if (!insertStegoRecord($conn, $user_id, $original_filename, $stego_filename, $output_path, $message_preview)) {
                        $error_embed = "Gagal menyimpan ke database.";
                        if (is_file($output_path)) unlink($output_path);
                    } else {
                        // unduh file & keluar
                        $conn->close();
                        header('Content-Description: File Transfer');
                        header('Content-Type: image/png');
                        header('Content-Disposition: attachment; filename="' . basename($stego_filename) . '"');
                        header('Content-Length: ' . filesize($output_path));
                        header('Cache-Control: no-cache, must-revalidate');
                        header('Pragma: public');
                        readfile($output_path);
                        exit;
                    }
                } else {
                    $error_embed = $embedRes;
                    imagedestroy($img);
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'extract') {
    if (!empty($_FILES['gambar_extract']['tmp_name'])) {
        $tmp  = $_FILES['gambar_extract']['tmp_name'];
        $mime = detectMime($tmp);

        if (!in_array($mime, ALLOWED_MIME, true)) {
            $error_extract = "Format file tidak didukung! Gunakan PNG/JPG.";
        } else {
            $img = openImage($tmp, $mime);
            if (!$img) {
                $error_extract = "Gagal membaca gambar. Pastikan ekstensi GD aktif.";
            } else {
                $msg = extractLSB($img);
                imagedestroy($img);
                if ($msg === '') {
                    $error_extract = "Tidak ada pesan yang ditemukan dalam gambar ini!";
                } else {
                    $success_extract = $msg;
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int) $_POST['delete_id'];

    $stmt_del = $conn->prepare("SELECT stego_path FROM steganografi WHERE id = ? AND user_id = ?");
    $stmt_del->bind_param('ii', $delete_id, $user_id);
    $stmt_del->execute();
    $res = $stmt_del->get_result();
    if ($res->num_rows > 0) {
        $file = $res->fetch_assoc();
        $path = $file['stego_path'];
        if (is_file($path)) unlink($path);
    }
    $stmt_del->close();

    $stmt_del2 = $conn->prepare("DELETE FROM steganografi WHERE id = ? AND user_id = ?");
    $stmt_del2->bind_param('ii', $delete_id, $user_id);
    $stmt_del2->execute();
    $stmt_del2->close();

    header("Location: steganografi.php");
    exit;
}


$stmt_list = $conn->prepare("SELECT id, original_filename, stego_filename, stego_path, hidden_message_preview, created_at FROM steganografi WHERE user_id = ? ORDER BY created_at DESC");
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
            <h1>STEGANOGRAFI</h1>
            <p>LSB (Least Significant Bit) Steganography</p>
        </div>

        <div class="content-card">
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

            <div class="tab-content" id="steganografiTabContent" style="padding-top: 25px;">
                <!-- TAB EMBED -->
                <div class="tab-pane fade show active" id="sembunyikan" role="tabpanel">
                    <?php if (isset($error_embed)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error_embed); ?></div>
                    <?php endif; ?>

                    <h4 class="mb-4" style="font-size: 16px; font-weight: 600;">Sembunyikan Pesan dalam Gambar</h4>

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
                        <div class="mb-2">
                            <label class="form-label">Pesan Rahasia</label>
                            <textarea class="form-control" name="pesan" id="pesanInput" rows="5" placeholder="Masukkan pesan yang ingin disembunyikan..." required></textarea>
                        </div>
                        <button type="submit" class="btn-primary-custom w-100">Sembunyikan</button>
                    </form>
                </div>

                <!-- TAB EXTRACT -->
                <div class="tab-pane fade" id="ekstrak" role="tabpanel">
                    <h4 class="mb-4" style="font-size: 16px; font-weight: 600;">Ekstrak Pesan dari Gambar</h4>
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
                        <button type="submit" class="btn btn-success-custom btn-lg w-100">Ekstrak Pesan</button>
                    </form>

                    <?php if (isset($success_extract)): ?>
                        <div class="result-box">
                            <h5>Pesan Berhasil Diekstrak</h5>
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
                            <th>File Stego</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($file = $stego_files->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($file['original_filename']); ?></td>
                                <td><?= htmlspecialchars($file['stego_filename']); ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($file['created_at'])); ?></td>
                                <td class="d-flex gap-2">
                                    <a href="<?= htmlspecialchars($file['stego_path']); ?>" class="btn-success-custom" download>
                                        Download
                                    </a>
                                    <form action="" method="POST" onsubmit="return confirm('Yakin ingin menghapus file ini?');">
                                        <input type="hidden" name="delete_id" value="<?= $file['id']; ?>">
                                        <button type="submit" class="btn-danger-custom btn-sm">Hapus</button>
                                    </form>
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
            const imgId = 'imgPreview' + previewId.replace('preview', '');
            const nameId = 'fileName' + previewId.replace('preview', '');
            const img = document.getElementById(imgId);
            const fileName = document.getElementById(nameId);

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

        function updateCount() {
            const textarea = document.getElementById('pesanInput');
            const counter = document.getElementById('charCount');
            if (textarea && counter) counter.textContent = 'Karakter: ' + (textarea.value.length || 0);
        }
        const pesanInput = document.getElementById('pesanInput');
        if (pesanInput) {
            pesanInput.addEventListener('input', updateCount);
            updateCount();
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
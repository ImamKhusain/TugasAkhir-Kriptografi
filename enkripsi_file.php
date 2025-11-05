<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login/login.php?pesan=belum_login");
    exit;
}
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$conn = getDBConnection();

// Fungsi aktif link
$current = basename($_SERVER['PHP_SELF']);
function is_active($file, $current)
{
    return $current === $file ? 'active' : '';
}

// Proses Enkripsi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'encrypt') {
    if (isset($_FILES['file']) && isset($_POST['password'])) {
        $file = $_FILES['file'];
        $password = $_POST['password'];

        if ($file['error'] === UPLOAD_ERR_OK) {
            $file_content = file_get_contents($file['tmp_name']);
            $filename = $file['name'];
            $file_type = $file['type'];

            $method = 'AES-256-CTR';

            try {
                $encrypted_content = fileEncryptAES256($file_content, $password);

                $stmt = $conn->prepare("INSERT INTO encrypted_files (user_id, filename, encrypted_content, file_type, encrypted_method, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param('issss', $user_id, $filename, $encrypted_content, $file_type, $method);

                if ($stmt->execute()) {
                    $success_encrypt = "File berhasil dienkripsi dan disimpan ke database!";
                } else {
                    $error_encrypt = "Gagal menyimpan ke database: " . $stmt->error;
                }
            } catch (Exception $e) {
                $error_encrypt = "Error enkripsi: " . $e->getMessage();
            }
        } else {
            $error_encrypt = "Error upload file!";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'decrypt') {
    $file_id = $_POST['file_id'] ?? 0;
    $password = $_POST['password_decrypt'] ?? '';

    if ($file_id > 0 && !empty($password)) {
        $stmt = $conn->prepare("SELECT * FROM encrypted_files WHERE id = ? AND user_id = ?");
        $stmt->bind_param('ii', $file_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($file = $result->fetch_assoc()) {
            try {
                $decrypted_content = fileDecryptAES256($file['encrypted_content'], $password);

                if ($decrypted_content !== false) {
                    header('Content-Type: ' . $file['file_type']);
                    header('Content-Disposition: attachment; filename="' . $file['filename'] . '"');
                    header('Content-Length: ' . strlen($decrypted_content));
                    echo $decrypted_content;
                    exit;
                } else {
                    $error_decrypt = "Password salah atau file corrupt!";
                }
            } catch (Exception $e) {
                $error_decrypt = "Error dekripsi: " . $e->getMessage();
            }
        } else {
            $error_decrypt = "File tidak ditemukan!";
        }
    }
}

if (isset($_GET['delete'])) {
    $file_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM encrypted_files WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $file_id, $user_id);
    if ($stmt->execute()) {
        header("Location: enkripsi_file.php?pesan=deleted");
        exit;
    }
}

$stmt = $conn->prepare("SELECT id, filename, file_type, encrypted_method, created_at FROM encrypted_files WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$files = $stmt->get_result();
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enkripsi File | AES-256</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
            min-height: 100vh;
            padding-top: 84px;
        }

        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #dee2e6;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
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
        }

        .welcome-text a {
            text-decoration: none;
        }

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
            border-bottom-color: rgba(13, 110, 253, .4);
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
            .nav-center {
                position: static;
                transform: none;
                justify-content: center;
                gap: 24px;
            }

            .header-inner {
                padding: 10px 16px;
            }
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

        .content-card {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, .08);
            padding: 40px;
            margin-bottom: 30px;
        }

        .info-box {
            background: #e8f5e9;
            border: 1px solid #c8e6c9;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .info-box h5 {
            color: #2e7d32;
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 15px;
        }

        .info-box ul {
            margin: 0;
            padding-left: 20px;
        }

        .info-box li {
            color: #388e3c;
            font-size: 13.5px;
            margin-bottom: 8px;
        }

        .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
        }

        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 .2rem rgba(52, 152, 219, .15);
        }

        .btn-primary-custom {
            background: #3498db;
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            color: #fff;
            transition: .3s;
        }

        .btn-primary-custom:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .btn-success-custom {
            background: #27ae60;
            border: none;
            border-radius: 10px;
            padding: 8px 20px;
            font-weight: 600;
            color: #fff;
        }

        .btn-danger-custom {
            background: #e74c3c;
            border: none;
            border-radius: 10px;
            padding: 8px 20px;
            font-weight: 600;
            color: #fff;
        }

        .file-table {
            width: 100%;
            margin-top: 20px;
        }

        .file-table th {
            background: #f8f9fa;
            padding: 12px;
            font-weight: 600;
        }

        .file-table td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
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
            <h1>ENKRIPSI FILE</h1>
            <p>AES-256-CTR File Encryption & Decryption</p>
        </div>

        <div class="content-card">
            <div class="info-box">
                <h5>ℹ️ Tentang Enkripsi File</h5>
                <ul>
                    <li><strong>Algoritma:</strong> AES-256-CTR (Counter Mode)</li>
                    <li><strong>Keamanan:</strong> Password akan di-hash menggunakan PBKDF2</li>
                    <li><strong>Penyimpanan:</strong> File terenkripsi disimpan di database</li>
                </ul>
            </div>

            <?php if (isset($success_encrypt)): ?>
                <div class="alert alert-success"><?= $success_encrypt; ?></div>
            <?php endif; ?>
            <?php if (isset($error_encrypt)): ?>
                <div class="alert alert-danger"><?= $error_encrypt; ?></div>
            <?php endif; ?>

            <h4 class="mb-4">Upload & Enkripsi File</h4>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="encrypt">

                <div class="mb-3">
                    <label class="form-label">Pilih File</label>
                    <input type="file" class="form-control" name="file" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password Enkripsi</label>
                    <input type="password" class="form-control" name="password" placeholder="Masukkan password kuat" required>
                </div>

                <button type="submit" class="btn btn-primary-custom w-100">🔒 Enkripsi & Simpan</button>
            </form>
        </div>

        <div class="content-card">
            <h4 class="mb-4">File Terenkripsi Anda</h4>

            <?php if (isset($error_decrypt)): ?>
                <div class="alert alert-danger"><?= $error_decrypt; ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['pesan']) && $_GET['pesan'] == 'deleted'): ?>
                <div class="alert alert-success">File berhasil dihapus!</div>
            <?php endif; ?>

            <?php if ($files->num_rows > 0): ?>
                <table class="file-table table table-hover">
                    <thead>
                        <tr>
                            <th>Nama File</th>
                            <th>Tipe File</th>
                            <th>Metode</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($file = $files->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($file['filename']); ?></td>
                                <td><?= htmlspecialchars($file['file_type']); ?></td>
                                <td><?= htmlspecialchars($file['encrypted_method']); ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($file['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-success-custom btn-sm" onclick="showDecryptModal(<?= $file['id']; ?>, '<?= htmlspecialchars($file['filename']); ?>')">🔓 Dekripsi</button>
                                    <a href="?delete=<?= $file['id']; ?>" class="btn btn-danger-custom btn-sm" onclick="return confirm('Yakin hapus file ini?')">🗑️</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">Belum ada file terenkripsi.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal fade" id="decryptModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Dekripsi File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="decrypt">
                        <input type="hidden" name="file_id" id="decrypt_file_id">

                        <p>File: <strong id="decrypt_filename"></strong></p>

                        <div class="mb-3">
                            <label class="form-label">Masukkan Password</label>
                            <input type="password" class="form-control" name="password_decrypt" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success-custom">Dekripsi & Download</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showDecryptModal(fileId, filename) {
            document.getElementById('decrypt_file_id').value = fileId;
            document.getElementById('decrypt_filename').textContent = filename;
            new bootstrap.Modal(document.getElementById('decryptModal')).show();
        }
    </script>
</body>

</html>
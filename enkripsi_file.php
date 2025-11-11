<?php
require_once 'config.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: login/login.php?pesan=belum_login");
    exit;
}
$user_id = (int)$_SESSION['user_id'];
$current = basename($_SERVER['PHP_SELF']);
function is_active($file, $current)
{
    return $current === $file ? 'active' : '';
}

function db()
{
    static $conn = null;
    if ($conn === null) $conn = getDBConnection();
    return $conn;
}

function close_db()
{
    $conn = db();
    if ($conn) $conn->close();
}

function force_download($binary, $filename, $type = 'application/octet-stream')
{
    if (ob_get_length()) @ob_end_clean();
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $type);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . strlen($binary));
    echo $binary;
    exit;
}

function act_encrypt($user_id): ?string
{
    if (!isset($_FILES['file'], $_POST['password'])) return "Input tidak lengkap.";
    $f = $_FILES['file'];
    if ($f['error'] !== UPLOAD_ERR_OK) return "Error upload file!";

    $password  = (string)$_POST['password'];
    $filename  = $f['name'];
    $file_type = $f['type'];
    $bytes     = file_get_contents($f['tmp_name']);
    if ($bytes === false) return "Tidak bisa membaca file.";

    try {
        $enc_b64 = fileEncryptAES256($bytes, $password);
        if (!$enc_b64) return "Gagal mengenkripsi file.";

        $stmt = db()->prepare("INSERT INTO encrypted_files (user_id, filename, encrypted_content, file_type, created_at)
                               VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param('isss', $user_id, $filename, $enc_b64, $file_type);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            return "Gagal menyimpan ke database: $err";
        }
        $stmt->close();

        $binary = base64_decode($enc_b64);
        close_db();
        force_download($binary, $filename, $file_type);
        return null;
    } catch (Throwable $e) {
        return "Error enkripsi: " . $e->getMessage();
    }
}

function act_decrypt($user_id): ?string
{
    $file_id  = (int)($_POST['file_id'] ?? 0);
    $password = (string)($_POST['password_decrypt'] ?? '');
    if ($file_id <= 0 || $password === '') return "Input tidak valid.";

    $stmt = db()->prepare("SELECT filename, file_type, encrypted_content FROM encrypted_files WHERE id=? AND user_id=?");
    $stmt->bind_param('ii', $file_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$res) return "File tidak ditemukan!";

    try {
        $plain = fileDecryptAES256($res['encrypted_content'], $password);
        if ($plain === false) return "Password salah atau file corrupt!";

        close_db();
        if (ob_get_length()) @ob_end_clean();
        header('Content-Type: ' . $res['file_type']);
        header('Content-Disposition: attachment; filename="' . $res['filename'] . '"');
        header('Content-Length: ' . strlen($plain));
        echo $plain;
        exit;
    } catch (Throwable $e) {
        return "Error dekripsi: " . $e->getMessage();
    }
}

function act_delete($user_id)
{
    $id = (int)($_GET['delete'] ?? 0);
    if ($id <= 0) return;
    $stmt = db()->prepare("DELETE FROM encrypted_files WHERE id=? AND user_id=?");
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    $stmt->close();
    close_db();
    header("Location: enkripsi_file.php?pesan=deleted");
    exit;
}

$error_encrypt = $error_decrypt = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'encrypt') $error_encrypt = act_encrypt($user_id);
    if ($action === 'decrypt') $error_decrypt = act_decrypt($user_id);
}
if (isset($_GET['delete'])) act_delete($user_id);

$stmt = db()->prepare("SELECT id, filename, file_type, created_at FROM encrypted_files WHERE user_id=? ORDER BY created_at DESC");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$files = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Enkripsi File | AES-256</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/fitur.css">
</head>

<body>
    <header>
        <div class="header-inner">
            <div class="welcome-text"><a href="dashboard.php">
                    <h5>Cryptopedia</h5>
                </a></div>
            <nav class="nav-center">
                <a href="steganografi.php" class="<?= is_active('steganografi.php', $current) ?>">STEGANOGRAFI</a>
                <a href="super_enkripsi.php" class="<?= is_active('super_enkripsi.php', $current) ?>">SUPER ENKRIPSI</a>
                <a href="enkripsi_file.php" class="<?= is_active('enkripsi_file.php', $current) ?>">ENKRIPSI FILE</a>
            </nav>
            <div class="logout-btn"><a href="login/logout.php" class="btn btn-light btn-sm px-3">LogOut</a></div>
        </div>
    </header>

    <div class="main-container">
        <div class="page-title">
            <h1>ENKRIPSI FILE</h1>
            <p>AES-256-CTR File Encryption & Decryption</p>
        </div>

        <div class="content-card">
            <?php if ($error_encrypt): ?><div class="alert alert-danger"><?= $error_encrypt ?></div><?php endif; ?>
            <h4 class="mb-4">Upload & Enkripsi File</h4>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="encrypt">
                <div class="mb-3"><label class="form-label">Pilih File</label><input type="file" class="form-control" name="file" required></div>
                <div class="mb-3"><label class="form-label">Password Enkripsi</label><input type="password" class="form-control" name="password" required></div>
                <button type="submit" class="btn-primary-custom w-100">Enkripsi</button>
            </form>
            <small class="text-muted d-block mt-2"></small>
        </div>

        <div class="content-card">
            <h4 class="mb-4">File Terenkripsi Anda</h4>
            <?php if ($error_decrypt): ?><div class="alert alert-danger"><?= $error_decrypt ?></div><?php endif; ?>
            <?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'deleted'): ?><div class="alert alert-success">File berhasil dihapus!</div><?php endif; ?>

            <?php if ($files->num_rows > 0): ?>
                <table class="file-table table table-hover">
                    <thead>
                        <tr>
                            <th>Nama File</th>
                            <th>Tipe</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($f = $files->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($f['filename']) ?></td>
                                <td><?= htmlspecialchars($f['file_type']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($f['created_at'])) ?></td>
                                <td>
                                    <button class="btn-success-custom btn-sm" onclick="showDecryptModal(<?= $f['id'] ?>,'<?= htmlspecialchars($f['filename'], ENT_QUOTES) ?>')">Dekripsi</button>
                                    <a href="?delete=<?= $f['id'] ?>" class="btn-danger-custom btn-sm" onclick="return confirm('Yakin hapus file ini?')">Hapus</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?><div class="alert alert-info">Belum ada file terenkripsi.</div><?php endif; ?>
        </div>
    </div>

    <div class="modal fade" id="decryptModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Dekripsi File</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="decrypt">
                        <input type="hidden" name="file_id" id="decrypt_file_id">
                        <p>File: <strong id="decrypt_filename"></strong></p>
                        <div class="mb-3"><label class="form-label">Masukkan Password</label><input type="password" class="form-control" name="password_decrypt" required></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-success-custom">Dekripsi & Download</button></div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showDecryptModal(id, name) {
            document.getElementById('decrypt_file_id').value = id;
            document.getElementById('decrypt_filename').textContent = name;
            new bootstrap.Modal(document.getElementById('decryptModal')).show();
        }
    </script>
</body>

</html>
<?php close_db(); ?>
<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login/login.php?pesan=belum_login");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama_lengkap = SecurityConfig::sanitizeInput($_POST['nama_lengkap'] ?? '');
    $peran        = SecurityConfig::sanitizeInput($_POST['peran'] ?? '');
    $tujuan       = SecurityConfig::sanitizeInput($_POST['tujuan'] ?? '');
    $user_id      = $_SESSION['user_id'];

    if ($nama_lengkap === '' || $peran === '' || $tujuan === '') {
        die("Semua data wajib diisi.");
    }

    $nama_terenkripsi   = dbEncryptAES128($nama_lengkap, DB_ENCRYPTION_KEY);
    $peran_terenkripsi  = dbEncryptAES128($peran, DB_ENCRYPTION_KEY);
    $tujuan_terenkripsi = dbEncryptAES128($tujuan, DB_ENCRYPTION_KEY);

    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE users SET nama_lengkap = ?, peran = ?, tujuan = ? WHERE id = ?");
    $stmt->bind_param("sssi", $nama_terenkripsi, $peran_terenkripsi, $tujuan_terenkripsi, $user_id);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: dashboard.php?pesan=profil_disimpan");
        exit;
    } else {
        $err = $stmt->error;
        $stmt->close();
        $conn->close();
        die("Gagal menyimpan profil: " . $err);
    }
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/fitur.css">
</head>

<body>
    <div class="profile-wrapper">
        <div class="profile-card">
            <h1>Lengkapi Profil Anda</h1>
            <p>Silakan lengkapi data diri Anda.</p>

            <form action="" method="POST">
                <div class="mb-3">
                    <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required>
                </div>

                <div class="mb-3">
                    <label for="peran" class="form-label">Peran Anda</label>
                    <select class="form-select" id="peran" name="peran" required>
                        <option value="" disabled selected>-- Pilih Peran --</option>
                        <option value="Mahasiswa">Mahasiswa</option>
                        <option value="Pengajar">Pengajar</option>
                        <option value="Developer">Developer</option>
                        <option value="Hobi / Umum">Hobi / Umum</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="tujuan" class="form-label">Tujuan Penggunaan Aplikasi</label>
                    <textarea class="form-control" id="tujuan" name="tujuan" rows="3" placeholder="Contoh: Untuk tugas akhir mata kuliah Kriptografi" required></textarea>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn-primary-custom">Simpan Profil & Mulai</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
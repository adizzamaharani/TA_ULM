<?php
session_start();
require '../config/database.php';

// Proteksi halaman: hanya admin yang bisa akses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php?error=access_denied');
    exit();
}

// --- LOGIKA UNTUK UPDATE STATUS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['surat_id']) && isset($_POST['status'])) {
        $surat_id = $_POST['surat_id'];
        $new_status = $_POST['status'];

        // Ambil status surat saat ini dan nomor surat yang ada
        $stmt_check = $pdo->prepare("SELECT status, nomor_surat FROM surat WHERE id = ?");
        $stmt_check->execute([$surat_id]);
        $current_surat = $stmt_check->fetch(PDO::FETCH_ASSOC);

        $nomor_surat_baru = $current_surat['nomor_surat']; // Defaultnya pakai nomor lama

        // --- LOGIKA GENERATE NOMOR SURAT OTOMATIS ---
        // Generate nomor HANYA JIKA status baru adalah 'Selesai' DAN nomor surat belum pernah dibuat.
        if ($new_status == 'Selesai' && empty($current_surat['nomor_surat'])) {
            $tahun_sekarang = date('Y');
            
            // 1. Cari nomor urut terakhir di tahun ini
            $stmt_last_num = $pdo->prepare("SELECT nomor_surat FROM surat WHERE nomor_surat LIKE ? ORDER BY id DESC LIMIT 1");
            $stmt_last_num->execute(["%/$tahun_sekarang"]);
            $last_nomor_surat = $stmt_last_num->fetchColumn();

            $nomor_urut_berikutnya = 1;
            if ($last_nomor_surat) {
                $parts = explode('/', $last_nomor_surat);
                $nomor_urut_berikutnya = (int)$parts[0] + 1;
            }

            // 2. Format nomor surat (Contoh: 001/UN8.1.2/KM/2024)
            // Anda bisa menyesuaikan kode 'UN8.1.2/KM' sesuai kebutuhan
            $nomor_surat_baru = sprintf("%03d", $nomor_urut_berikutnya) . "/UN8.1.2/KM/" . $tahun_sekarang;
        }

        try {
            // Update status dan nomor surat (jika ada yang baru)
            $stmt = $pdo->prepare("UPDATE surat SET status = ?, nomor_surat = ? WHERE id = ?");
            $stmt->execute([$new_status, $nomor_surat_baru, $surat_id]);

            // Arahkan kembali ke dashboard admin dengan pesan sukses
            header("Location: index.php?status=update_sukses");
            exit();
        } catch (PDOException $e) {
            // Handle error jika gagal update
            die("Error: " . $e->getMessage());
        }
    }
}

// --- LOGIKA UNTUK MENGAMBIL DATA SURAT ---
// Pastikan ID ada di URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$surat_id = $_GET['id'];

// Ambil detail surat dari database
$stmt = $pdo->prepare("SELECT * FROM surat WHERE id = ?");
$stmt->execute([$surat_id]);
$surat = $stmt->fetch(PDO::FETCH_ASSOC);

// Jika surat dengan ID tersebut tidak ditemukan, kembalikan ke dashboard
if (!$surat) {
    header('Location: index.php?error=not_found');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail & Verifikasi Surat - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="../assets/css/responsive.css?v=<?= time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="../assets/img/logo_ulm.png" alt="Logo ULM" width="30" height="30" class="d-inline-block align-text-top me-2">
            Admin Dashboard
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <span class="navbar-text me-3">Selamat datang, <?= htmlspecialchars($_SESSION['username']); ?></span>
                </li>
                <li class="nav-item">
                    <a href="../auth/logout.php" class="btn btn-danger btn-sm">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header card-header-custom">
                    <i class="bi bi-file-earmark-text me-2"></i>Detail Pengajuan Surat
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">Nama Pengaju</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($surat['nama_lengkap']); ?></dd>

                        <dt class="col-sm-4">NIM</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($surat['nim']); ?></dd>

                        <dt class="col-sm-4">Semester</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($surat['semester']); ?></dd>

                        <dt class="col-sm-4">Jenis Surat</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($surat['jenis_surat']); ?></dd>

                        <dt class="col-sm-4">Tanggal Pengajuan</dt>
                        <dd class="col-sm-8">
                            <?= date('d F Y, H:i', strtotime($surat['tanggal_pengajuan'])); ?>
                            <small class="d-block text-muted realtime-timestamp" data-timestamp="<?= date('c', strtotime($surat['tanggal_pengajuan'])); ?>">
                                <!-- Waktu akan diisi oleh JavaScript -->
                            </small>
                        </dd>

                        <dt class="col-sm-4">Status Saat Ini</dt>
                        <dd class="col-sm-8"><span class="badge badge-status-<?= strtolower($surat['status']); ?>"><?= htmlspecialchars($surat['status']); ?></span></dd>

                        <?php if (!empty($surat['nomor_surat'])): ?>
                            <dt class="col-sm-4">Nomor Surat</dt>
                            <dd class="col-sm-8"><strong><?= htmlspecialchars($surat['nomor_surat']); ?></strong></dd>
                        <?php endif; ?>

                        <?php if (!empty($surat['dosen_pembimbing'])): ?>
                            <dt class="col-sm-4">Dosen Pembimbing</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars($surat['dosen_pembimbing']); ?></dd>
                        <?php endif; ?>

                        <?php if (!empty($surat['tanggal_mulai']) && !empty($surat['tanggal_selesai'])): ?>
                            <dt class="col-sm-4">Tanggal Pelaksanaan</dt>
                            <dd class="col-sm-8">
                                <?= date('d M Y', strtotime($surat['tanggal_mulai'])); ?> s/d <?= date('d M Y', strtotime($surat['tanggal_selesai'])); ?>
                            </dd>
                        <?php endif; ?>

                        <?php if (!empty($surat['durasi_magang'])): ?>
                            <dt class="col-sm-4">Durasi Magang</dt>
                            <dd class="col-sm-8">
                                <?= htmlspecialchars($surat['durasi_magang']); ?>
                            </dd>
                        <?php endif; ?>

                        <dt class="col-sm-4">Keperluan</dt>
                        <dd class="col-sm-8"><p><?= nl2br(htmlspecialchars($surat['keperluan'])); ?></p></dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header card-header-custom">
                    <i class="bi bi-check-circle me-2"></i>Aksi Verifikasi
                </div>
                <div class="card-body">
                    <form action="verifikasi.php?id=<?= $surat_id; ?>" method="POST">
                        <input type="hidden" name="surat_id" value="<?= $surat_id; ?>">
                        <div class="mb-3">
                            <label for="status" class="form-label">Ubah Status Menjadi:</label>
                            <select name="status" id="status" class="form-select" required>
                                <!-- Opsi ini bisa disesuaikan tergantung alur kerja -->
                                <option value="Pending" <?= $surat['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Diproses" <?= $surat['status'] == 'Diproses' ? 'selected' : ''; ?>>Diproses</option>
                                <option value="Menunggu Persetujuan" <?= $surat['status'] == 'Menunggu Persetujuan' ? 'selected' : ''; ?>>Teruskan ke Dekan</option>
                                <option value="Disetujui" <?= $surat['status'] == 'Disetujui' ? 'selected' : ''; ?>>Disetujui oleh Dekan</option>
                                <option value="Selesai" <?= $surat['status'] == 'Selesai' ? 'selected' : ''; ?>>Selesai</option>
                                <option value="Ditolak" <?= $surat['status'] == 'Ditolak' ? 'selected' : ''; ?> class="text-danger">Ditolak</option>
                            </select>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary-custom">Update Status</button>
                        </div>
                    </form>
                    <hr>
                    <?php if ($surat['status'] == 'Selesai' || $surat['status'] == 'Disetujui'): ?>
                        <div class="d-grid mb-2">
                             <a href="generate_pdf.php?id=<?= $surat_id; ?>" class="btn btn-success" target="_blank">
                                <i class="bi bi-printer me-2"></i>Cetak PDF
                            </a>
                        </div>
                    <?php endif; ?>
                    <a href="index.php" class="btn btn-secondary w-100">Kembali ke Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // --- Fungsi untuk Timestamp Real-time ---
    function timeAgo(date) {
        const seconds = Math.floor((new Date() - new Date(date)) / 1000);

        let interval = seconds / 31536000;
        if (interval > 1) return Math.floor(interval) + " tahun yang lalu";
        
        interval = seconds / 2592000;
        if (interval > 1) return Math.floor(interval) + " bulan yang lalu";
        
        interval = seconds / 86400;
        if (interval > 1) return Math.floor(interval) + " hari yang lalu";
        
        interval = seconds / 3600;
        if (interval > 1) return Math.floor(interval) + " jam yang lalu";
        
        interval = seconds / 60;
        if (interval > 1) return Math.floor(interval) + " menit yang lalu";
        
        if (seconds < 10) return "baru saja";

        return Math.floor(seconds) + " detik yang lalu";
    }

    function updateTimestamps() {
        const elements = document.querySelectorAll('.realtime-timestamp');
        elements.forEach(function(element) {
            const timestamp = element.getAttribute('data-timestamp');
            if (timestamp) {
                element.textContent = timeAgo(timestamp);
            }
        });
    }

    // Panggil fungsi saat halaman dimuat
    document.addEventListener('DOMContentLoaded', function() {
        updateTimestamps();
        // Perbarui timestamp setiap 30 detik
        setInterval(updateTimestamps, 30000);
    });
</script>
<script src="../assets/js/validations.js"></script>
</body>
</html>
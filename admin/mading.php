<?php
session_start();
require '../config/database.php';

// Proteksi halaman: hanya admin yang bisa akses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php?error=access_denied');
    exit();
}

// Ambil semua data mading, diurutkan dari yang terbaru
$stmt = $pdo->query("SELECT m.*, u.nama_lengkap as admin_name FROM mading m JOIN users u ON m.user_id = u.id ORDER BY m.tanggal_dibuat DESC");
$pengumuman_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Mading - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="../assets/css/responsive.css?v=<?= time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { display: flex; min-height: 100vh; flex-direction: column; }
        .main-container { display: flex; flex: 1; }
        .sidebar { width: 280px; background: #fff; border-right: 1px solid #e9ecef; transition: width 0.3s; box-shadow: 2px 0 10px rgba(0,0,0,0.03); }
        .content { flex: 1; padding: 2rem; background: #f5f6fa; }
        .sidebar .sidebar-heading { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1.5px; color: #999; font-weight: 700; }
        .sidebar .nav-link { color: #555 !important; font-size: 0.92rem; font-weight: 500; padding: 0.7rem 1rem; border-radius: 10px; margin-bottom: 4px; transition: all 0.2s ease; display: flex; align-items: center; }
        .sidebar .nav-link .bi { font-size: 1.15rem; width: 24px; text-align: center; margin-right: 12px; color: #888; }
        .sidebar .nav-link:hover { background: #FFF8DC; color: #1a1a2e !important; transform: translateX(4px); }
        .sidebar .nav-link:hover .bi { color: #C7A600; }
        .sidebar .nav-link.active { background: linear-gradient(135deg, #FFD700, #E6C300) !important; color: #1a1a2e !important; font-weight: 700; box-shadow: 0 3px 12px rgba(255, 215, 0, 0.3); }
        .sidebar .nav-link.active .bi { color: #1a1a2e !important; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container-fluid">
        <div class="d-flex align-items-center">
            <button class="btn me-2" id="sidebar-toggle"><i class="bi bi-list fs-5"></i></button>
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="../assets/img/logo_ulm.png?v=<?= filemtime('../assets/img/logo_ulm.png'); ?>" alt="Logo ULM" width="32" height="32" class="d-inline-block align-text-top me-2">
                Admin Panel
            </a>
        </div>
        <ul class="navbar-nav ms-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['username']); ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item text-danger" href="../auth/logout.php">Logout</a></li>
                </ul>
            </li>
        </ul>
    </div>
</nav>

<div class="main-container">
    <div class="sidebar p-3">
        <div class="mb-3 px-2">
            <span class="sidebar-heading"><span class="link-text">Menu Navigasi</span></span>
        </div>
        <ul class="nav flex-column" id="sidebar-nav">
            <li class="nav-item">
                <a class="nav-link" href="index.php"><i class="bi bi-list-task"></i> <span class="link-text">Daftar Pengajuan</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="mading.php"><i class="bi bi-megaphone-fill"></i> <span class="link-text">Kelola Mading</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="rekap.php"><i class="bi bi-file-earmark-bar-graph"></i> <span class="link-text">Rekap Laporan</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="statistik.php"><i class="bi bi-graph-up"></i> <span class="link-text">Statistik</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="backup_restore.php"><i class="bi bi-cloud-arrow-down"></i> <span class="link-text">Backup & Restore</span></a>
            </li>
        </ul>
    </div>
    <div class="content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3><i class="bi bi-megaphone-fill me-2"></i>Kelola Mading Pengumuman</h3>
        <!-- Tombol untuk memunculkan modal tambah -->
        <button type="button" class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#tambahMadingModal">
            <i class="bi bi-plus-circle me-1"></i> Tambah Pengumuman
        </button>
    </div>
    <hr>

    <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
        <div class="alert alert-success">Pengumuman berhasil ditambahkan.</div>
    <?php elseif (isset($_GET['status']) && $_GET['status'] == 'gagal'): ?>
        <div class="alert alert-danger">Gagal menambahkan pengumuman. Cek kembali file dan input Anda.</div>
    <?php endif; ?>

    <!-- Daftar Pengumuman -->
    <?php if (empty($pengumuman_list)): ?>
        <div class="alert alert-info text-center">Belum ada pengumuman.</div>
    <?php else: ?>
        <?php foreach ($pengumuman_list as $p): ?>
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <?php if (!empty($p['gambar'])): ?>
                    <div class="col-md-3">
                        <img src="../assets/uploads/<?= htmlspecialchars($p['gambar']); ?>" class="img-fluid rounded" alt="Gambar Pengumuman">
                    </div>
                    <div class="col-md-9">
                    <?php else: ?>
                    <div class="col-md-12">
                    <?php endif; ?>
                        <h5 class="card-title"><?= htmlspecialchars($p['judul']); ?></h5>
                        <p class="card-text"><?= nl2br(htmlspecialchars($p['isi'])); ?></p>
                        <p class="card-text"><small class="text-muted">Diposting oleh: <?= htmlspecialchars($p['admin_name']); ?> pada <?= date('d M Y, H:i', strtotime($p['tanggal_dibuat'])); ?></small></p>
                        <!-- Tombol Edit dan Hapus bisa ditambahkan di sini -->
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </div>
</div>

<!-- Modal Tambah Mading -->
<div class="modal fade" id="tambahMadingModal" tabindex="-1" aria-labelledby="tambahMadingModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header card-header-custom">
        <h5 class="modal-title" id="tambahMadingModalLabel">Form Tambah Pengumuman</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="proses_mading.php" method="POST" enctype="multipart/form-data">
        <div class="modal-body">
            <div class="mb-3">
                <label for="judul" class="form-label">Judul Pengumuman</label>
                <input type="text" class="form-control" id="judul" name="judul" required>
            </div>
            <div class="mb-3">
                <label for="isi" class="form-label">Isi Pengumuman</label>
                <textarea class="form-control" id="isi" name="isi" rows="5" required></textarea>
            </div>
            <div class="mb-3">
                <label for="gambar" class="form-label">Unggah Gambar (Opsional)</label>
                <input class="form-control" type="file" id="gambar" name="gambar" accept="image/png, image/jpeg, image/gif">
                <small class="form-text text-muted">Ukuran file maksimal 2MB.</small>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary-custom">Simpan Pengumuman</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/validations.js"></script>
</body>
</html>
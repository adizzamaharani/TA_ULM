<?php
session_start();
require '../config/database.php';
date_default_timezone_set('Asia/Makassar');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dosen') {
    header('Location: ../auth/login.php?error=access_denied');
    exit();
}

$user_id = $_SESSION['user_id'];
$kegiatan_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil data user
$user_stmt = $pdo->prepare("SELECT username, nama_lengkap, foto_profil FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Ambil data kegiatan
$stmt = $pdo->prepare("SELECT * FROM kegiatan_akademik WHERE id = ? AND dosen_id = ?");
$stmt->execute([$kegiatan_id, $user_id]);
$kegiatan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$kegiatan) {
    die("Data kegiatan tidak ditemukan.");
}

// Ambil data mahasiswa yang terlibat
$stmt_mhs = $pdo->prepare("
    SELECT u.username as nim, u.nama_lengkap, p.nama_prodi 
    FROM kegiatan_mahasiswa km 
    JOIN users u ON km.mahasiswa_id = u.id 
    LEFT JOIN program_studi p ON u.prodi_id = p.id
    WHERE km.kegiatan_id = ?
");
$stmt_mhs->execute([$kegiatan_id]);
$mahasiswa_list = $stmt_mhs->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Kegiatan Akademik - Sistem Surat ULM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="../assets/css/responsive.css?v=<?= time(); ?>">
    <style>
        body { display: flex; min-height: 100vh; flex-direction: column; }
        .main-container { display: flex; flex: 1; }
        .sidebar { width: 280px; background: #fff; border-right: 1px solid #e9ecef; transition: width 0.3s; box-shadow: 2px 0 10px rgba(0,0,0,0.03); }
        .content { flex: 1; padding: 2rem; background: #f5f6fa; transition: margin-left 0.3s; }
        .sidebar .nav-link { color: #555 !important; font-size: 0.92rem; font-weight: 500; padding: 0.65rem 1rem; border-radius: 10px; margin-bottom: 3px; display: flex; align-items: center; }
        .sidebar .nav-link .bi { font-size: 1.15rem; width: 24px; text-align: center; margin-right: 12px; color: #888; flex-shrink: 0; }
        .sidebar .nav-link:hover { background: #FFF8DC; color: #1a1a2e !important; }
        .sidebar .nav-link:hover .bi { color: #C7A600; }
        .sidebar .nav-link.active { background: linear-gradient(135deg, #FFD700, #E6C300) !important; color: #1a1a2e !important; font-weight: 700; box-shadow: 0 3px 12px rgba(255, 215, 0, 0.3); }
        .sidebar .nav-link.active .bi { color: #1a1a2e !important; }
        .badge-status { font-size: 0.8rem; padding: 0.4rem 0.8rem; border-radius: 50px; }
        .badge-status.pending { background-color: #fff3cd; color: #856404; }
        .badge-status.diproses { background-color: #cce5ff; color: #004085; }
        .badge-status.selesai { background-color: #d4edda; color: #155724; }
        .badge-status.ditolak { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container-fluid">
        <div class="d-flex align-items-center">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="../assets/img/logo_ulm.png" alt="Logo ULM" width="32" height="32" class="d-inline-block align-text-top me-2">
                <span class="d-none d-sm-inline">Sistem Surat Faperta - Dosen</span>
            </a>
        </div>
        <ul class="navbar-nav ms-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                    <?= htmlspecialchars($user_data['nama_lengkap']); ?>
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
        <h5 class="sidebar-heading text-muted mb-3" style="font-size: 0.75rem; text-transform: uppercase;">Menu Navigasi</h5>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="index.php">
                    <i class="bi bi-house-door-fill"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="buat_kegiatan.php">
                    <i class="bi bi-plus-circle-fill"></i> Buat Kegiatan
                </a>
            </li>
        </ul>
    </div>

    <div class="content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold text-dark">Detail Kegiatan Akademik</h3>
            <a href="index.php" class="btn btn-outline-secondary rounded-pill"><i class="bi bi-arrow-left"></i> Kembali</a>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <small class="text-muted fw-bold d-block">Nomor Surat</small>
                        <span class="fs-5 fw-bold"><?= $kegiatan['nomor_surat'] ? htmlspecialchars($kegiatan['nomor_surat']) : '<span class="text-secondary fst-italic">Belum ada</span>' ?></span>
                    </div>
                    <div class="col-md-6 mb-3 text-md-end">
                        <small class="text-muted fw-bold d-block">Status</small>
                        <?php
                            $status = strtolower($kegiatan['status']);
                            $badge_class = 'badge-status ' . $status;
                        ?>
                        <span class="<?= $badge_class ?> fs-6"><?= $kegiatan['status'] ?></span>
                        
                        <?php if ($kegiatan['status'] == 'Selesai'): ?>
                            <div class="mt-2">
                                <a href="../generate_pdf_kegiatan.php?id=<?= $kegiatan['id'] ?>" target="_blank" class="btn btn-danger btn-sm rounded-pill"><i class="bi bi-file-pdf"></i> Download PDF</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <hr>
                <div class="row mt-3">
                    <div class="col-md-4 mb-3">
                        <small class="text-muted fw-bold">Nama Kegiatan</small>
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars($kegiatan['nama_kegiatan']) ?></p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <small class="text-muted fw-bold">Jenis Kegiatan</small>
                        <p class="mb-0"><?= htmlspecialchars($kegiatan['jenis_kegiatan']) ?></p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <small class="text-muted fw-bold">Tanggal Kegiatan</small>
                        <p class="mb-0"><?= date('d F Y', strtotime($kegiatan['tanggal_kegiatan'])) ?></p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <small class="text-muted fw-bold">Lokasi</small>
                        <p class="mb-0"><?= htmlspecialchars($kegiatan['lokasi']) ?></p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <small class="text-muted fw-bold">Tanggal Pengajuan</small>
                        <p class="mb-0"><?= date('d F Y, H:i', strtotime($kegiatan['tanggal_pengajuan'])) ?></p>
                    </div>
                    <div class="col-md-4 mb-3">
                        <small class="text-muted fw-bold">Lampiran</small>
                        <?php if ($kegiatan['lampiran']): ?>
                            <p class="mb-0"><a href="../assets/uploads/lampiran/<?= htmlspecialchars($kegiatan['lampiran']) ?>" target="_blank" class="btn btn-sm btn-outline-info"><i class="bi bi-paperclip"></i> Lihat Lampiran</a></p>
                        <?php else: ?>
                            <p class="mb-0 text-muted fst-italic">Tidak ada</p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-12 mb-3">
                        <small class="text-muted fw-bold">Deskripsi / Tujuan Kegiatan</small>
                        <p class="mb-0 p-3 bg-light rounded"><?= nl2br(htmlspecialchars($kegiatan['deskripsi'])) ?></p>
                    </div>
                    <?php if ($kegiatan['catatan_admin']): ?>
                    <div class="col-md-12 mb-3">
                        <small class="text-danger fw-bold">Catatan Admin/Dekan</small>
                        <p class="mb-0 p-3 bg-danger-subtle rounded text-danger"><?= nl2br(htmlspecialchars($kegiatan['catatan_admin'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                <h5 class="fw-bold mb-0"><i class="bi bi-people-fill me-2 text-primary"></i>Daftar Mahasiswa Terlibat</h5>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">No</th>
                                <th>NIM</th>
                                <th>Nama Mahasiswa</th>
                                <th>Program Studi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no=1; foreach ($mahasiswa_list as $mhs): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($mhs['nim']) ?></td>
                                    <td><?= htmlspecialchars($mhs['nama_lengkap']) ?></td>
                                    <td><?= htmlspecialchars($mhs['nama_prodi'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

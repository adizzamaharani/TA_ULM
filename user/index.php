<?php
session_start();
require '../config/database.php';

// 1. SET TIMEZONE WITA (Sesuai Banjarbaru)
date_default_timezone_set('Asia/Makassar');

// Proteksi halaman: hanya user yang bisa akses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php?error=access_denied');
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data user termasuk foto profil
$user_stmt = $pdo->prepare("SELECT username, foto_profil FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Konfigurasi Pagination
$limit = 10; // Jumlah data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Hitung total data
$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM surat WHERE user_id = ?");
$stmt_count->execute([$user_id]);
$total_records = $stmt_count->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Ambil riwayat surat dengan limit
$stmt = $pdo->prepare("SELECT * FROM surat WHERE user_id = ? ORDER BY tanggal_pengajuan DESC LIMIT $limit OFFSET $offset");
$stmt->execute([$user_id]);
$riwayat_surat = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil 3 pengumuman terbaru dari mading
$mading_stmt = $pdo->query("SELECT * FROM mading ORDER BY tanggal_dibuat DESC LIMIT 3");
$mading_list = $mading_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard User - Sistem Surat ULM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="../assets/css/responsive.css?v=<?= time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }
        .main-container {
            display: flex;
            flex: 1;
        }
        .sidebar {
            width: 280px;
            background: #fff;
            border-right: 1px solid #e9ecef;
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 2px 0 10px rgba(0,0,0,0.03);
        }
        .content {
            flex: 1;
            padding: 2rem;
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: #f5f6fa;
        }

        /* Sidebar styling */
        .sidebar .sidebar-heading {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #999;
            font-weight: 700;
        }
        .sidebar .nav-link {
            color: #555 !important;
            font-size: 0.92rem;
            font-weight: 500;
            padding: 0.65rem 1rem;
            border-radius: 10px;
            margin-bottom: 3px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            white-space: nowrap;
            overflow: hidden;
        }
        .sidebar .nav-link .bi {
            font-size: 1.15rem;
            width: 24px;
            text-align: center;
            margin-right: 12px;
            color: #888;
            flex-shrink: 0;
        }
        .sidebar .nav-link:hover {
            background: #FFF8DC;
            color: #1a1a2e !important;
            transform: translateX(4px);
        }
        .sidebar .nav-link:hover .bi {
            color: #C7A600;
        }
        .sidebar .nav-link.active {
            background: linear-gradient(135deg, #FFD700, #E6C300) !important;
            color: #1a1a2e !important;
            font-weight: 700;
            box-shadow: 0 3px 12px rgba(255, 215, 0, 0.3);
        }
        .sidebar .nav-link.active .bi {
            color: #1a1a2e !important;
        }
        .sidebar .nav-link .link-text {
            transition: opacity 0.2s ease-in-out, transform 0.2s ease-in-out;
            transform: translateX(0); 
        }
        .sidebar .text-muted {
            font-size: 0.7rem !important;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 700 !important;
            color: #aaa !important;
            padding: 0.3rem 0.5rem;
        }

        /* Sidebar toggled */
        body.sidebar-toggled .sidebar {
            width: 80px;
        }
        body.sidebar-toggled .sidebar .nav-link .link-text,
        body.sidebar-toggled .sidebar .sidebar-heading,
        body.sidebar-toggled .sidebar .text-muted span {
            opacity: 0;
            transform: translateX(-10px);
            pointer-events: none;
        }
        body.sidebar-toggled .sidebar .nav-link {
            justify-content: center;
        }
        body.sidebar-toggled .sidebar .nav-link:hover {
            transform: none;
        }
        body.sidebar-toggled .sidebar .nav-link .bi {
            margin-right: 0;
        }

        /* Toggle button */
        #sidebar-toggle {
            border: none;
            background: rgba(0,0,0,0.06);
            border-radius: 8px;
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        #sidebar-toggle:hover {
            background: rgba(0,0,0,0.12);
        }
        #sidebar-toggle i {
            transition: transform 0.3s ease-in-out;
        }
        body.sidebar-toggled #sidebar-toggle i {
            transform: rotate(90deg);
        }

        /* Card menu hover */
        .card-menu {
            border: 1px solid #e9ecef;
            border-radius: 12px;
            transition: all 0.25s ease;
        }
        .card-menu:hover {
            transform: translateY(-6px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border-color: #FFD700;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container-fluid">
        <div class="d-flex align-items-center">
            <button class="btn me-2" id="sidebar-toggle"><i class="bi bi-list fs-5"></i></button>
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="../assets/img/logo_ulm.png?v=<?= filemtime('../assets/img/logo_ulm.png'); ?>" alt="Logo ULM" width="32" height="32" class="d-inline-block align-text-top me-2">
                <span class="d-none d-sm-inline">Sistem Surat Faperta</span>
            </a>
        </div>

        <ul class="navbar-nav ms-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php
                        $foto_profil_nav = '../assets/uploads/profil/' . ($user_data['foto_profil'] ?? 'default.png');
                        if (!file_exists($foto_profil_nav) || empty($user_data['foto_profil'])) {
                            $foto_profil_nav = '../assets/img/default_avatar.png';
                        }
                    ?>
                    <img src="<?= $foto_profil_nav ?>" alt="Foto Profil" class="rounded-circle me-2" style="width: 25px; height: 25px; object-fit: cover;">
                    
                    <?= htmlspecialchars($user_data['username']); ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                    <li><a class="dropdown-item" href="profil.php">Profil Saya</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="../auth/logout.php">Logout</a></li>
                </ul>
            </li>
        </ul>
    </div>
</nav>

<div class="main-container">
    <div class="sidebar p-3">
        <h5 class="sidebar-heading mb-3"><span class="link-text">Menu Navigasi</span></h5>
        <ul class="nav flex-column" id="sidebar-nav">
            <li class="nav-item">
                <a class="nav-link active" href="index.php">
                    <i class="bi bi-house-door-fill"></i>
                    <span class="link-text">Dashboard</span>
                </a>
            </li>
            <li class="nav-item mt-2">
                <span class="nav-link text-muted sidebar-heading"><span class="link-text">Pengajuan Surat</span></span>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="ajukan_surat_aktif.php"><i class="bi bi-file-earmark-person"></i><span class="link-text"> Surat Mahasiswa Aktif</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="ajukan_surat_penelitian.php"><i class="bi bi-flask"></i><span class="link-text"> Surat Izin Penelitian</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="ajukan_surat_pkl.php"><i class="bi bi-building"></i><span class="link-text"> Surat Pengantar PKL</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="ajukan_surat_izin.php"><i class="bi bi-calendar-x"></i><span class="link-text"> Surat Izin Kuliah</span></a>
            </li>
            <li class="nav-item mt-2">
                <span class="nav-link text-muted sidebar-heading"><span class="link-text">Layanan Lainnya</span></span>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="ajukan_surat_alumni.php"><i class="bi bi-patch-check"></i><span class="link-text"> Keterangan Alumni</span></a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="ajukan_surat_keterlambatan_ukt.php"><i class="bi bi-clock-history"></i><span class="link-text"> Keterlambatan UKT</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="ajukan_surat_keterangan_lulus.php"><i class="bi bi-mortarboard"></i><span class="link-text"> Keterangan Lulus</span></a>
            </li>
            <li class="nav-item mt-3 border-top pt-2">
                <a class="nav-link" href="profil.php">
                    <i class="bi bi-gear-fill"></i>
                    <span class="link-text">Profil Saya</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="content">
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h4><i class="bi bi-hand-wave me-2"></i>Selamat Datang, <?= htmlspecialchars($_SESSION['nama_lengkap']); ?>!</h4>
                    <p>Kelola pengajuan surat akademik Anda dengan mudah dan cepat.</p>
                </div>
                <i class="bi bi-mortarboard-fill d-none d-md-block" style="font-size: 2.5rem; opacity: 0.5;"></i>
            </div>
        </div>

        <div class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3><i class="bi bi-pin-angle-fill me-2"></i>Mading Fakultas</h3>
            </div>
            <hr>
            <?php if (empty($mading_list)): ?>
                <div class="alert alert-info text-center">Saat ini belum ada pengumuman.</div>
            <?php else: ?>
                <div class="row g-4">
                <?php foreach ($mading_list as $mading): ?>
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <?php if (!empty($mading['gambar'])): ?>
                                    <img src="../assets/uploads/<?= htmlspecialchars($mading['gambar']); ?>" class="img-fluid rounded mb-3" style="max-height: 300px; width: 100%; object-fit: cover;" alt="Gambar Pengumuman">
                                <?php endif; ?>
                                <h5 class="card-title"><?= htmlspecialchars($mading['judul']); ?></h5>
                                <p class="card-text"><?= nl2br(htmlspecialchars($mading['isi'])); ?></p>
                                
                                <p class="card-text"><small class="text-muted">Diposting pada: 
                                <?php 
                                    if (!empty($mading['tanggal_dibuat']) && substr($mading['tanggal_dibuat'], 0, 4) != '0000') {
                                        echo date('d F Y, H:i', strtotime($mading['tanggal_dibuat'])) . ' WITA';
                                    } else {
                                        echo '-';
                                    }
                                ?>
                                </small></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ALUR PENGAJUAN SURAT -->
        <div class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3><i class="bi bi-diagram-3-fill me-2"></i>Alur Pengajuan Surat</h3>
            </div>
            <hr>
            <div class="row text-center g-3">
                <div class="col-md-3">
                    <div class="card bg-light border-0 h-100 p-3">
                        <div class="card-body">
                            <i class="bi bi-1-circle-fill text-primary" style="font-size: 2.5rem;"></i>
                            <h5 class="mt-2">Pilih Surat</h5>
                            <p class="text-muted small">Pilih jenis surat dari menu navigasi atau menu cepat.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light border-0 h-100 p-3">
                        <div class="card-body">
                            <i class="bi bi-2-circle-fill text-warning" style="font-size: 2.5rem;"></i>
                            <h5 class="mt-2">Isi Form</h5>
                            <p class="text-muted small">Lengkapi data keperluan Anda pada form yang disediakan.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light border-0 h-100 p-3">
                        <div class="card-body">
                            <i class="bi bi-3-circle-fill text-info" style="font-size: 2.5rem;"></i>
                            <h5 class="mt-2">Verifikasi Admin</h5>
                            <p class="text-muted small">Tunggu admin memproses dan menyetujui pengajuan Anda.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light border-0 h-100 p-3">
                        <div class="card-body">
                            <i class="bi bi-4-circle-fill text-success" style="font-size: 2.5rem;"></i>
                            <h5 class="mt-2">Selesai & Unduh</h5>
                            <p class="text-muted small">Unduh surat PDF yang telah bertanda tangan Dekan & QR Code.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Riwayat Pengajuan Surat Anda</h3>
        </div>
        <?php
        if (isset($_GET['status']) && $_GET['status'] == 'pengajuan_sukses') {
            echo '<div class="alert alert-success">Pengajuan surat Anda telah berhasil dikirim.</div>';
        }
        ?>
        <hr>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Jenis Surat</th>
                        <th>Keperluan</th>
                        <th>Waktu Pengajuan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($riwayat_surat)): ?>
                        <tr>
                            <td colspan="6" class="text-center">Belum ada riwayat pengajuan.</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = $offset + 1; foreach ($riwayat_surat as $surat): ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><?= htmlspecialchars($surat['jenis_surat']); ?></td>
                            <td><?= htmlspecialchars($surat['keperluan']); ?></td>
                            <td>
                                <?php 
                                    if (!empty($surat['tanggal_pengajuan']) && substr($surat['tanggal_pengajuan'], 0, 4) != '0000') {
                                        echo date('d F Y, H:i:s', strtotime($surat['tanggal_pengajuan'])) . ' WITA';
                                    } else {
                                        echo '-';
                                    }
                                ?>
                            </td>
                            <td>
                                <span class="badge badge-status-<?= strtolower($surat['status']); ?>"><?= htmlspecialchars($surat['status']); ?></span>
                            </td>
                            <td>
                                <?php if ($surat['status'] == 'Selesai'): ?>
                                    <a href="../admin/generate_pdf.php?id=<?= $surat['id']; ?>" class="btn btn-success btn-sm" target="_blank">
                                        <i class="bi bi-download me-1"></i> PDF
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?= $page - 1; ?>">Sebelumnya</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= ($page == $i) ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?= $i; ?>"><?= $i; ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?= $page + 1; ?>">Selanjutnya</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>

        <div class="mt-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Menu Cepat Layanan Mahasiswa</h3>
            </div>
            <hr>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <a href="ajukan_surat_aktif.php" class="text-decoration-none">
                        <div class="card text-center h-100 shadow-sm card-menu">
                            <div class="card-body d-flex flex-column justify-content-center">
                                <i class="bi bi-file-earmark-person display-4 text-primary"></i>
                                <h5 class="card-title mt-3">Surat Mahasiswa Aktif</h5>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-4 col-md-6">
                    <a href="ajukan_surat_penelitian.php" class="text-decoration-none">
                        <div class="card text-center h-100 shadow-sm card-menu">
                            <div class="card-body d-flex flex-column justify-content-center">
                                <i class="bi bi-flask display-4 text-success"></i>
                                <h5 class="card-title mt-3">Surat Izin Penelitian</h5>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-4 col-md-6">
                    <a href="ajukan_surat_pkl.php" class="text-decoration-none">
                        <div class="card text-center h-100 shadow-sm card-menu">
                            <div class="card-body d-flex flex-column justify-content-center">
                                <i class="bi bi-building display-4 text-warning"></i>
                                <h5 class="card-title mt-3">Surat Pengantar PKL</h5>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-4 col-md-6">
                    <a href="ajukan_surat_izin.php" class="text-decoration-none">
                        <div class="card text-center h-100 shadow-sm card-menu">
                            <div class="card-body d-flex flex-column justify-content-center">
                                <i class="bi bi-calendar-x display-4 text-danger"></i>
                                <h5 class="card-title mt-3">Surat Izin Kuliah</h5>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-4 col-md-6">
                    <a href="ajukan_surat_alumni.php" class="text-decoration-none">
                        <div class="card text-center h-100 shadow-sm card-menu">
                            <div class="card-body d-flex flex-column justify-content-center">
                                <i class="bi bi-patch-check display-4 text-info"></i>
                                <h5 class="card-title mt-3">Keterangan Alumni</h5>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-lg-4 col-md-6">
                    <a href="ajukan_surat_keterlambatan_ukt.php" class="text-decoration-none">
                        <div class="card text-center h-100 shadow-sm card-menu">
                            <div class="card-body d-flex flex-column justify-content-center">
                                <i class="bi bi-clock-history display-4" style="color:#e67e22"></i>
                                <h5 class="card-title mt-3">Keterlambatan UKT</h5>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-4 col-md-6">
                    <a href="ajukan_surat_keterangan_lulus.php" class="text-decoration-none">
                        <div class="card text-center h-100 shadow-sm card-menu">
                            <div class="card-body d-flex flex-column justify-content-center">
                                <i class="bi bi-mortarboard display-4" style="color:#2ecc71"></i>
                                <h5 class="card-title mt-3">Keterangan Lulus</h5>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

<?php 
// Sertakan footer (contoh)
// include '../includes/footer.php'; 
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('sidebar-toggle').addEventListener('click', function() {
        document.body.classList.toggle('sidebar-toggled');
    });

</script>
<script src="../assets/js/validations.js"></script>
</body>
</html>
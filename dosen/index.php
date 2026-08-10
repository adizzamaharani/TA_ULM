<?php
session_start();
require '../config/database.php';

// SET TIMEZONE WITA (Sesuai Banjarbaru)
date_default_timezone_set('Asia/Makassar');

// Proteksi halaman: hanya dosen yang bisa akses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dosen') {
    header('Location: ../auth/login.php?error=access_denied');
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data user
$user_stmt = $pdo->prepare("SELECT username, nama_lengkap, foto_profil FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Konfigurasi Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Hitung total data
$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM kegiatan_akademik WHERE dosen_id = ?");
$stmt_count->execute([$user_id]);
$total_records = $stmt_count->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Ambil riwayat kegiatan akademik dengan limit
$stmt = $pdo->prepare("
    SELECT k.*, 
    (SELECT COUNT(*) FROM kegiatan_mahasiswa km WHERE km.kegiatan_id = k.id) as jumlah_mahasiswa 
    FROM kegiatan_akademik k 
    WHERE k.dosen_id = ? 
    ORDER BY k.tanggal_pengajuan DESC 
    LIMIT $limit OFFSET $offset
");
$stmt->execute([$user_id]);
$riwayat_kegiatan = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Dosen - Sistem Surat ULM</title>
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
        <h3 class="mb-4 fw-bold text-dark">Dashboard Dosen</h3>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                <h5 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-warning"></i>Riwayat Kegiatan Akademik</h5>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Nomor Surat</th>
                                <th>Nama Kegiatan</th>
                                <th>Jenis</th>
                                <th>Jml Mahasiswa</th>
                                <th>Status</th>
                                <th>Tgl Pengajuan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($riwayat_kegiatan) > 0): ?>
                                <?php $no = $offset + 1; foreach ($riwayat_kegiatan as $row): ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td>
                                            <?php if ($row['nomor_surat']): ?>
                                                <span class="badge bg-secondary"><?= htmlspecialchars($row['nomor_surat']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted fst-italic">Belum ada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($row['nama_kegiatan']); ?></td>
                                        <td><?= htmlspecialchars($row['jenis_kegiatan']); ?></td>
                                        <td><span class="badge bg-info text-dark"><?= $row['jumlah_mahasiswa'] ?> Orang</span></td>
                                        <td>
                                            <?php
                                                $status = strtolower($row['status']);
                                                $badge_class = 'badge-status ' . $status;
                                            ?>
                                            <span class="<?= $badge_class ?>"><?= $row['status'] ?></span>
                                        </td>
                                        <td><?= date('d M Y, H:i', strtotime($row['tanggal_pengajuan'])) ?></td>
                                        <td>
                                            <a href="detail_kegiatan.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                                                <i class="bi bi-eye"></i> Detail
                                            </a>
                                            <?php if ($row['status'] == 'Selesai'): ?>
                                                <a href="../generate_pdf_kegiatan.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-danger rounded-pill">
                                                    <i class="bi bi-file-pdf"></i> PDF
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">Belum ada kegiatan akademik yang diajukan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?>">Sebelumnya</a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>">Selanjutnya</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
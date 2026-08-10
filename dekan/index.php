<?php
session_start();
require '../config/database.php';

// Proteksi halaman: hanya dekan atau wakil dekan yang bisa akses
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['dekan', 'wakil_dekan'])) {
    header('Location: ../auth/login.php?error=access_denied');
    exit();
}

// Konfigurasi Pagination
$limit = 10; // Jumlah data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filter dan Pencarian
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$where_clauses = [];
$params = [];

if (!empty($search)) {
    $where_clauses[] = "(u.nama_lengkap LIKE ? OR u.username LIKE ? OR s.jenis_surat LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    $where_clauses[] = "s.status = ?";
    $params[] = $status_filter;
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Hitung total data untuk pagination
$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM surat s JOIN users u ON s.user_id = u.id $where_sql");
$stmt_count->execute($params);
$total_records = $stmt_count->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Ambil data dengan limit dan offset
$query = "SELECT s.*, u.nama_lengkap, u.username as nim 
          FROM surat s 
          JOIN users u ON s.user_id = u.id 
          $where_sql 
          ORDER BY s.tanggal_pengajuan DESC 
          LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$daftar_surat = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Aktivitas - <?= ucfirst($_SESSION['role']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="../assets/css/responsive.css?v=<?= time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="#">
            <img src="../assets/img/logo_ulm.png?v=<?= filemtime('../assets/img/logo_ulm.png'); ?>" alt="Logo ULM" width="32" height="32" class="d-inline-block align-text-top me-2">
            Dashboard Pimpinan
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <span class="navbar-text me-3"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['nama_lengkap']); ?></span>
                </li>
                <li class="nav-item">
                    <a href="../auth/logout.php" class="btn btn-sm" style="background: rgba(0,0,0,0.1); font-weight: 600; border-radius: 8px; padding: 0.4rem 1rem;">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <?php
    if (isset($_GET['status']) && $_GET['status'] == 'pengajuan_sukses') {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                Pengajuan Surat Tugas Dosen berhasil dikirim.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
    }
    ?>
    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h4><i class="bi bi-shield-check me-2"></i>Dashboard <?= ucfirst($_SESSION['role']) == 'Wakil_dekan' ? 'Wakil Dekan' : 'Dekan' ?></h4>
                <p>Pantau dan kelola aktivitas pengajuan surat mahasiswa.</p>
            </div>
            <i class="bi bi-journal-bookmark-fill d-none d-md-block" style="font-size: 2.5rem; opacity: 0.5;"></i>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h3 class="mb-0"><i class="bi bi-journal-text me-2"></i>Daftar Aktivitas Pengajuan Surat</h3>
        <div>
            <a href="kegiatan_akademik.php" class="btn btn-outline-primary me-2">
                <i class="bi bi-briefcase-fill me-1"></i> Kegiatan Akademik
            </a>
            <a href="ajukan_surat_tugas_dosen.php" class="btn btn-primary-custom">
                <i class="bi bi-person-badge me-1"></i> Ajukan Surat Tugas
            </a>
        </div>
    </div>
    <hr>

    <!-- Fitur Pencarian dan Filter -->
    <form action="" method="GET" class="row g-3 mb-4">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Cari Nama, NIM, atau Jenis Surat..." value="<?= htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">- Semua Status -</option>
                <option value="Menunggu Persetujuan" <?= $status_filter == 'Menunggu Persetujuan' ? 'selected' : ''; ?>>Menunggu Persetujuan</option>
                <option value="Disetujui" <?= $status_filter == 'Disetujui' ? 'selected' : ''; ?>>Disetujui</option>
                <option value="Ditolak" <?= $status_filter == 'Ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                <option value="Selesai" <?= $status_filter == 'Selesai' ? 'selected' : ''; ?>>Selesai</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary-custom w-100"><i class="bi bi-search"></i> Cari</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th>No</th>
                    <th>Nama Pengaju</th>
                    <th>NIM</th>
                    <th>Jenis Surat</th>
                    <th>Keperluan</th>
                    <th>Tanggal Diajukan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_surat)): ?>
                    <tr>
                        <td colspan="8" class="text-center">Tidak ada data yang ditemukan.</td>
                    </tr>
                <?php else: ?>
                    <?php $no = $offset + 1; foreach ($daftar_surat as $surat): ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($surat['nama_lengkap']); ?></td>
                        <td><?= htmlspecialchars($surat['nim']); ?></td>
                        <td><?= htmlspecialchars($surat['jenis_surat']); ?></td>
                        <td><?= htmlspecialchars($surat['keperluan']); ?></td>
                        <td><?= date('d M Y, H:i', strtotime($surat['tanggal_pengajuan'])); ?></td>
                        <td>
                            <?php
                            $badge_class = 'bg-secondary';
                            if ($surat['status'] == 'Menunggu Persetujuan') $badge_class = 'bg-warning text-dark';
                            elseif ($surat['status'] == 'Disetujui' || $surat['status'] == 'Selesai') $badge_class = 'bg-success';
                            elseif ($surat['status'] == 'Ditolak') $badge_class = 'bg-danger';
                            ?>
                            <span class="badge <?= $badge_class; ?>"><?= htmlspecialchars($surat['status']); ?></span>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <!-- Tombol Lihat Detail -->
                                <a href="detail_surat.php?id=<?= $surat['id']; ?>" class="btn btn-sm btn-primary" title="Lihat Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <!-- Tombol Pratinjau PDF -->
                                <a href="cetak_surat.php?id=<?= $surat['id']; ?>" class="btn btn-sm btn-info" target="_blank" title="Lihat Pratinjau PDF">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?= $page - 1; ?>&search=<?= urlencode($search); ?>&status=<?= urlencode($status_filter); ?>">Sebelumnya</a>
            </li>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?= ($page == $i) ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?= $i; ?>&search=<?= urlencode($search); ?>&status=<?= urlencode($status_filter); ?>"><?= $i; ?></a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?= $page + 1; ?>&search=<?= urlencode($search); ?>&status=<?= urlencode($status_filter); ?>">Selanjutnya</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/validations.js"></script>
</body>
</html>

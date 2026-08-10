<?php
session_start();
require '../config/database.php';
date_default_timezone_set('Asia/Makassar');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php?error=access_denied');
    exit();
}

$filter = $_GET['filter'] ?? 'semua';
$kondisi_where = "";
$params_select = [];
if ($filter !== 'semua') {
    $kondisi_where = "WHERE k.status = ?";
    $params_select[] = $filter;
}

$query = "
    SELECT k.*, u.nama_lengkap as dosen_nama,
    (SELECT COUNT(*) FROM kegiatan_mahasiswa km WHERE km.kegiatan_id = k.id) as jumlah_mahasiswa 
    FROM kegiatan_akademik k
    JOIN users u ON k.dosen_id = u.id
    $kondisi_where
    ORDER BY k.tanggal_pengajuan DESC
";
$stmt = $pdo->prepare($query);
$stmt->execute($params_select);
$daftar_kegiatan = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kegiatan Akademik - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="../assets/css/responsive.css?v=<?= time(); ?>">
    <style>
        body { display: flex; min-height: 100vh; flex-direction: column; }
        .main-container { display: flex; flex: 1; }
        .sidebar { width: 280px; background: #fff; border-right: 1px solid #e9ecef; transition: width 0.3s; box-shadow: 2px 0 10px rgba(0,0,0,0.03); }
        .content { flex: 1; padding: 2rem; background: #f5f6fa; transition: margin-left 0.3s; }
        .sidebar .nav-link { color: #555 !important; font-size: 0.92rem; font-weight: 500; padding: 0.7rem 1rem; border-radius: 10px; margin-bottom: 4px; display: flex; align-items: center; }
        .sidebar .nav-link .bi { font-size: 1.15rem; width: 24px; text-align: center; margin-right: 12px; color: #888; }
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
        <a class="navbar-brand" href="index.php">Admin Panel - Kegiatan Akademik</a>
        <ul class="navbar-nav ms-auto">
            <li class="nav-item">
                <a class="nav-link" href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </li>
        </ul>
    </div>
</nav>

<div class="main-container">
    <div class="sidebar p-3">
        <div class="mb-3 px-2"><span class="sidebar-heading">Menu Navigasi</span></div>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-list-task"></i> Daftar Pengajuan</a></li>
            <li class="nav-item"><a class="nav-link active" href="kegiatan_akademik.php"><i class="bi bi-briefcase-fill"></i> Kegiatan Akademik</a></li>
            <li class="nav-item"><a class="nav-link" href="mading.php"><i class="bi bi-megaphone-fill"></i> Kelola Mading</a></li>
        </ul>
    </div>

    <div class="content">
        <h3><i class="bi bi-briefcase-fill me-2"></i>Manajemen Kegiatan Akademik Dosen</h3>
        <hr>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'update_sukses'): ?>
            <div class="alert alert-success">Status kegiatan berhasil diperbarui.</div>
        <?php endif; ?>

        <div class="d-flex align-items-center mb-3">
            <span class="me-3 fw-bold">Filter Status:</span>
            <div class="btn-group">
                <a href="kegiatan_akademik.php?filter=semua" class="btn btn-sm <?= $filter == 'semua' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Semua</a>
                <a href="kegiatan_akademik.php?filter=Pending" class="btn btn-sm <?= $filter == 'Pending' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Pending</a>
                <a href="kegiatan_akademik.php?filter=Diproses" class="btn btn-sm <?= $filter == 'Diproses' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Diproses</a>
                <a href="kegiatan_akademik.php?filter=Selesai" class="btn btn-sm <?= $filter == 'Selesai' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Selesai</a>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Dosen</th>
                                <th>Nama Kegiatan</th>
                                <th>Jenis</th>
                                <th>Jml Mhs</th>
                                <th>Status</th>
                                <th>Tgl Pengajuan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no=1; foreach ($daftar_kegiatan as $row): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($row['dosen_nama']) ?></td>
                                    <td><?= htmlspecialchars($row['nama_kegiatan']) ?></td>
                                    <td><?= htmlspecialchars($row['jenis_kegiatan']) ?></td>
                                    <td><?= $row['jumlah_mahasiswa'] ?></td>
                                    <td><span class="badge-status <?= strtolower($row['status']) ?>"><?= $row['status'] ?></span></td>
                                    <td><?= date('d M Y, H:i', strtotime($row['tanggal_pengajuan'])) ?></td>
                                    <td>
                                        <a href="verifikasi_kegiatan.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary rounded-pill">Verifikasi</a>
                                    </td>
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

<?php
session_start();
require '../config/database.php';
date_default_timezone_set('Asia/Makassar');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['dekan', 'wakil_dekan'])) {
    header('Location: ../auth/login.php?error=access_denied');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    if ($_POST['action'] == 'approve') {
        $stmt = $pdo->prepare("UPDATE kegiatan_akademik SET status = 'Selesai' WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: kegiatan_akademik.php?status=approved');
        exit();
    }
}

$query = "
    SELECT k.*, u.nama_lengkap as dosen_nama,
    (SELECT COUNT(*) FROM kegiatan_mahasiswa km WHERE km.kegiatan_id = k.id) as jumlah_mahasiswa 
    FROM kegiatan_akademik k
    JOIN users u ON k.dosen_id = u.id
    WHERE k.status = 'Diproses' OR k.status = 'Selesai'
    ORDER BY k.tanggal_pengajuan DESC
";
$stmt = $pdo->query($query);
$daftar_kegiatan = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Approval Kegiatan Akademik - Dashboard Dekan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="../assets/css/responsive.css?v=<?= time(); ?>">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
        <a class="navbar-brand" href="index.php">Dashboard Pimpinan</a>
        <ul class="navbar-nav ms-auto">
            <li class="nav-item">
                <a class="nav-link" href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </li>
        </ul>
    </div>
</nav>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"><i class="bi bi-briefcase-fill me-2"></i>Persetujuan Kegiatan Akademik</h3>
        <a href="index.php" class="btn btn-outline-secondary">Kembali ke Dashboard</a>
    </div>
    <hr>

    <?php if (isset($_GET['status']) && $_GET['status'] == 'approved'): ?>
        <div class="alert alert-success">Kegiatan akademik berhasil disetujui.</div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>No. Surat</th>
                            <th>Dosen</th>
                            <th>Nama Kegiatan</th>
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
                                <td><?= htmlspecialchars($row['nomor_surat']) ?></td>
                                <td><?= htmlspecialchars($row['dosen_nama']) ?></td>
                                <td><?= htmlspecialchars($row['nama_kegiatan']) ?></td>
                                <td><?= $row['jumlah_mahasiswa'] ?></td>
                                <td>
                                    <?php if ($row['status'] == 'Diproses'): ?>
                                        <span class="badge bg-warning text-dark">Perlu Persetujuan</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Selesai</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d M Y', strtotime($row['tanggal_pengajuan'])) ?></td>
                                <td>
                                    <a href="approval_kegiatan.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary rounded-pill">Detail & Action</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (count($daftar_kegiatan) == 0): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">Tidak ada kegiatan akademik yang perlu diproses.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>

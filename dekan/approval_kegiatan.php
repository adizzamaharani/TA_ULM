<?php
session_start();
require '../config/database.php';
date_default_timezone_set('Asia/Makassar');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['dekan', 'wakil_dekan'])) {
    header('Location: ../auth/login.php?error=access_denied');
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT k.*, u.nama_lengkap as dosen_nama FROM kegiatan_akademik k JOIN users u ON k.dosen_id = u.id WHERE k.id = ?");
$stmt->execute([$id]);
$kegiatan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$kegiatan) {
    die("Kegiatan tidak ditemukan.");
}

$stmt_mhs = $pdo->prepare("SELECT u.username as nim, u.nama_lengkap, p.nama_prodi FROM kegiatan_mahasiswa km JOIN users u ON km.mahasiswa_id = u.id LEFT JOIN program_studi p ON u.prodi_id = p.id WHERE km.kegiatan_id = ?");
$stmt_mhs->execute([$id]);
$mahasiswa_list = $stmt_mhs->fetchAll(PDO::FETCH_ASSOC);

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

<div class="container py-5">
    <div class="card shadow-sm rounded-4 border-0">
        <div class="card-header bg-white border-bottom-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-center">
            <h4 class="mb-0 fw-bold">Detail & Approval Kegiatan</h4>
            <a href="kegiatan_akademik.php" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </div>
        <div class="card-body p-4">
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <small class="text-muted fw-bold d-block">Nomor Surat</small>
                    <span class="fs-5 fw-bold"><?= htmlspecialchars($kegiatan['nomor_surat']) ?></span>
                </div>
                <div class="col-md-6 mb-3 text-md-end">
                    <small class="text-muted fw-bold d-block">Status</small>
                    <?php if ($kegiatan['status'] == 'Diproses'): ?>
                        <span class="badge bg-warning text-dark fs-6">Menunggu Persetujuan</span>
                    <?php else: ?>
                        <span class="badge bg-success fs-6">Selesai / Disetujui</span>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 mb-3">
                    <small class="text-muted fw-bold d-block">Dosen Pengaju</small>
                    <p class="mb-0 fw-semibold"><?= htmlspecialchars($kegiatan['dosen_nama']) ?></p>
                </div>
                <div class="col-md-4 mb-3">
                    <small class="text-muted fw-bold d-block">Nama Kegiatan</small>
                    <p class="mb-0"><?= htmlspecialchars($kegiatan['nama_kegiatan']) ?></p>
                </div>
                <div class="col-md-4 mb-3">
                    <small class="text-muted fw-bold d-block">Jenis Kegiatan</small>
                    <p class="mb-0"><?= htmlspecialchars($kegiatan['jenis_kegiatan']) ?></p>
                </div>
                <div class="col-md-12 mb-3">
                    <small class="text-muted fw-bold d-block">Deskripsi</small>
                    <p class="mb-0 p-3 bg-light rounded border"><?= nl2br(htmlspecialchars($kegiatan['deskripsi'])) ?></p>
                </div>
                <?php if ($kegiatan['lampiran']): ?>
                <div class="col-md-12 mb-3">
                    <small class="text-muted fw-bold d-block">Lampiran</small>
                    <a href="../assets/uploads/lampiran/<?= htmlspecialchars($kegiatan['lampiran']) ?>" target="_blank" class="btn btn-sm btn-info"><i class="bi bi-paperclip"></i> Lihat Lampiran</a>
                </div>
                <?php endif; ?>
            </div>

            <h5 class="fw-bold mb-3">Daftar Mahasiswa Terlibat</h5>
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>NIM</th>
                            <th>Nama Mahasiswa</th>
                            <th>Program Studi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mahasiswa_list as $mhs): ?>
                            <tr>
                                <td><?= htmlspecialchars($mhs['nim']) ?></td>
                                <td><?= htmlspecialchars($mhs['nama_lengkap']) ?></td>
                                <td><?= htmlspecialchars($mhs['nama_prodi'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <hr>
            <?php if ($kegiatan['status'] == 'Diproses'): ?>
                <form action="kegiatan_akademik.php" method="POST" class="text-end">
                    <input type="hidden" name="id" value="<?= $kegiatan['id'] ?>">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="btn btn-success btn-lg rounded-pill px-5" onclick="return confirm('Apakah Anda yakin ingin menyetujui surat kegiatan ini?')">
                        <i class="bi bi-check-circle-fill"></i> Approve (Setujui)
                    </button>
                </form>
            <?php elseif ($kegiatan['status'] == 'Selesai'): ?>
                <div class="text-end">
                    <a href="../generate_pdf_kegiatan.php?id=<?= $kegiatan['id'] ?>" target="_blank" class="btn btn-danger btn-lg rounded-pill px-5">
                        <i class="bi bi-file-pdf"></i> Lihat / Cetak PDF
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

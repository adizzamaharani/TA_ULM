<?php
session_start();
require '../config/database.php';
date_default_timezone_set('Asia/Makassar');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php?error=access_denied');
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $status_baru = $_POST['status_baru'];
    $catatan_admin = $_POST['catatan_admin'] ?? '';
    
    // Auto generate nomor surat if status is Diproses
    $nomor_surat = null;
    if ($status_baru == 'Diproses' || $status_baru == 'Selesai') {
        // Simple auto generation, format: 00X/UN8.1.2/KA/2026
        $stmt_count = $pdo->query("SELECT COUNT(*) FROM kegiatan_akademik WHERE nomor_surat IS NOT NULL");
        $count = $stmt_count->fetchColumn() + 1;
        $nomor_surat = sprintf("%03d/UN8.1.2/KA/%s", $count, date('Y'));
    }

    if ($nomor_surat) {
        $stmt = $pdo->prepare("UPDATE kegiatan_akademik SET status = ?, catatan_admin = ?, nomor_surat = IFNULL(nomor_surat, ?) WHERE id = ?");
        $stmt->execute([$status_baru, $catatan_admin, $nomor_surat, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE kegiatan_akademik SET status = ?, catatan_admin = ? WHERE id = ?");
        $stmt->execute([$status_baru, $catatan_admin, $id]);
    }

    header('Location: kegiatan_akademik.php?status=update_sukses');
    exit();
}

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
    <title>Verifikasi Kegiatan Akademik - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="card shadow-sm rounded-4 border-0">
        <div class="card-header bg-white border-bottom-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-center">
            <h4 class="mb-0 fw-bold">Verifikasi Kegiatan Akademik</h4>
            <a href="kegiatan_akademik.php" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </div>
        <div class="card-body p-4">
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <small class="text-muted fw-bold d-block">Dosen Pengaju</small>
                    <span class="fs-6 fw-bold"><?= htmlspecialchars($kegiatan['dosen_nama']) ?></span>
                </div>
                <div class="col-md-6 mb-3 text-md-end">
                    <small class="text-muted fw-bold d-block">Status Saat Ini</small>
                    <span class="badge bg-secondary fs-6"><?= $kegiatan['status'] ?></span>
                </div>
                <div class="col-md-6 mb-3">
                    <small class="text-muted fw-bold d-block">Nama Kegiatan</small>
                    <p class="mb-0 fw-semibold"><?= htmlspecialchars($kegiatan['nama_kegiatan']) ?></p>
                </div>
                <div class="col-md-6 mb-3">
                    <small class="text-muted fw-bold d-block">Jenis Kegiatan</small>
                    <p class="mb-0"><?= htmlspecialchars($kegiatan['jenis_kegiatan']) ?></p>
                </div>
                <div class="col-md-12 mb-3">
                    <small class="text-muted fw-bold d-block">Deskripsi</small>
                    <p class="mb-0 p-2 bg-light rounded border"><?= nl2br(htmlspecialchars($kegiatan['deskripsi'])) ?></p>
                </div>
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
            <h5 class="fw-bold mb-3">Tindakan Admin</h5>
            <form action="" method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">Ubah Status</label>
                    <select name="status_baru" class="form-select" required>
                        <option value="Pending" <?= $kegiatan['status'] == 'Pending' ? 'selected' : '' ?>>Pending (Belum Diproses)</option>
                        <option value="Diproses" <?= $kegiatan['status'] == 'Diproses' ? 'selected' : '' ?>>Diproses (Teruskan ke Dekan)</option>
                        <option value="Ditolak" <?= $kegiatan['status'] == 'Ditolak' ? 'selected' : '' ?>>Ditolak</option>
                    </select>
                    <small class="text-muted">Jika diubah ke "Diproses", sistem akan otomatis menggenerate Nomor Surat.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Catatan Admin (Opsional)</label>
                    <textarea name="catatan_admin" class="form-control" rows="3"><?= htmlspecialchars($kegiatan['catatan_admin'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100">Simpan Perubahan</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

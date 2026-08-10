<?php
session_start();
require '../config/database.php';

// Proteksi halaman: hanya dekan atau wakil dekan yang bisa akses
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['dekan', 'wakil_dekan'])) {
    header('Location: ../auth/login.php?error=access_denied');
    exit();
}

// Logika untuk update status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['surat_id']) && isset($_POST['aksi'])) {
    $surat_id = $_POST['surat_id'];
    $new_status = ($_POST['aksi'] == 'setujui') ? 'Disetujui' : 'Ditolak';

    $stmt = $pdo->prepare("UPDATE surat SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $surat_id]);

    header("Location: index.php?status=update_sukses");
    exit();
}

// Ambil semua surat yang menunggu persetujuan
$stmt = $pdo->prepare("SELECT * FROM surat WHERE status = 'Menunggu Persetujuan' ORDER BY tanggal_pengajuan ASC");
$stmt->execute();
$daftar_surat = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Persetujuan - <?= ucfirst($_SESSION['role']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="#">
            <img src="../assets/img/logo_ulm.png?v=<?= filemtime('../assets/img/logo_ulm.png'); ?>" alt="Logo ULM" width="30" height="30" class="d-inline-block align-text-top me-2">
            Dashboard Persetujuan
        </a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <span class="navbar-text me-3">Selamat datang, <?= htmlspecialchars($_SESSION['nama_lengkap']); ?></span>
                </li>
                <li class="nav-item">
                    <a href="../auth/logout.php" class="btn btn-danger btn-sm">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <h3><i class="bi bi-journal-check me-2"></i>Daftar Surat Menunggu Persetujuan</h3>
    <hr>
    <?php
    if (isset($_GET['status']) && $_GET['status'] == 'update_sukses') {
        echo '<div class="alert alert-success">Status pengajuan berhasil diperbarui.</div>';
    }
    ?>

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
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_surat)): ?>
                    <tr>
                        <td colspan="7" class="text-center">Tidak ada surat yang menunggu persetujuan saat ini.</td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($daftar_surat as $surat): ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($surat['nama_lengkap']); ?></td>
                        <td><?= htmlspecialchars($surat['nim']); ?></td>
                        <td><?= htmlspecialchars($surat['jenis_surat']); ?></td>
                        <td><?= htmlspecialchars($surat['keperluan']); ?></td>
                        <td><?= date('d M Y, H:i', strtotime($surat['tanggal_pengajuan'])); ?></td>
                        <td>
                            <div class="d-flex gap-2">
                                <!-- Tombol Pratinjau PDF -->
                                <a href="../admin/generate_pdf.php?id=<?= $surat['id']; ?>" class="btn btn-sm btn-info" target="_blank" title="Lihat Pratinjau PDF">
                                    <i class="bi bi-eye-fill"></i>
                                </a>
                                <!-- Form untuk Setujui -->
                                <form action="index.php" method="POST" onsubmit="return confirm('Anda yakin ingin menyetujui surat ini?');">
                                    <input type="hidden" name="surat_id" value="<?= $surat['id']; ?>">
                                    <input type="hidden" name="aksi" value="setujui">
                                    <button type="submit" class="btn btn-sm btn-success" title="Setujui">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                </form>
                                <!-- Form untuk Tolak -->
                                <form action="index.php" method="POST" onsubmit="return confirm('Anda yakin ingin menolak surat ini?');">
                                    <input type="hidden" name="surat_id" value="<?= $surat['id']; ?>">
                                    <input type="hidden" name="aksi" value="tolak">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Tolak">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/validations.js"></script>
</body>
</html>

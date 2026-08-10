<?php
session_start();
require '../config/database.php';

// Cek akses: hanya dekan atau wakil dekan
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['dekan', 'wakil_dekan'])) {
    header('Location: ../auth/login.php');
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "<script>alert('ID Surat tidak valid!'); window.location='index.php';</script>";
    exit();
}

// Ambil data surat lengkap
$query = "SELECT s.*, u.nama_lengkap, u.username as nim 
          FROM surat s 
          JOIN users u ON s.user_id = u.id 
          WHERE s.id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$id]);
$surat = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$surat) {
    echo "Data surat tidak ditemukan.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Surat - <?= htmlspecialchars($surat['jenis_surat']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Detail Pengajuan Surat</h5>
            <a href="index.php" class="btn btn-sm btn-light"><i class="bi bi-arrow-left"></i> Kembali</a>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="30%">Nama Mahasiswa</th>
                            <td>: <?= htmlspecialchars($surat['nama_lengkap']); ?></td>
                        </tr>
                        <tr>
                            <th>NIM</th>
                            <td>: <?= htmlspecialchars($surat['nim']); ?></td>
                        </tr>
                        <tr>
                            <th>Tanggal Pengajuan</th>
                            <td>: <?= date('d F Y, H:i', strtotime($surat['tanggal_pengajuan'])); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="30%">Jenis Surat</th>
                            <td>: <?= htmlspecialchars($surat['jenis_surat']); ?></td>
                        </tr>
                        <tr>
                            <th>Keperluan</th>
                            <td>: <?= htmlspecialchars($surat['keperluan']); ?></td>
                        </tr>
                        <tr>
                            <th>Status Saat Ini</th>
                            <td>: 
                                <span class="badge bg-<?= $surat['status'] == 'Disetujui' ? 'success' : ($surat['status'] == 'Ditolak' ? 'danger' : 'warning'); ?>">
                                    <?= htmlspecialchars($surat['status']); ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <hr>
            
            <div class="d-flex gap-2 justify-content-end">
                <!-- Tombol Aksi -->
                <a href="cetak_surat.php?id=<?= $surat['id']; ?>" target="_blank" class="btn btn-info text-white"><i class="bi bi-printer"></i> Pratinjau PDF</a>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/validations.js"></script>
</body>
</html>
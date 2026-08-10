<?php
session_start();
require '../config/database.php';

// Proteksi halaman: hanya user yang bisa akses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php?error=access_denied');
    exit();
}

// Ambil data user yang sedang login
$stmt = $pdo->prepare("SELECT nama_lengkap, username FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
if (!$user) {
    header('Location: ../auth/logout.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Surat Izin Sakit - Sistem Surat ULM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="../assets/img/logo_ulm.png" alt="Logo ULM" width="30" height="30" class="d-inline-block align-text-top me-2">
            <span class="d-none d-sm-inline">Dashboard Mahasiswa</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <span class="navbar-text me-3">Selamat datang, <?= htmlspecialchars($_SESSION['username']); ?></span>
                </li>
                <li class="nav-item">
                    <a href="../auth/logout.php" class="btn btn-danger btn-sm">
                        <i class="bi bi-box-arrow-right d-lg-none"></i>
                        <span class="d-none d-lg-inline">Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div class="card">
        <div class="card-header card-header-custom">
            Formulir Pengajuan Surat Izin Sakit
        </div>
        <div class="card-body">
            <form action="proses_ajukan.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="jenis_surat" value="Surat Izin Sakit">
                
                <div class="mb-3">
                    <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?= htmlspecialchars($user['nama_lengkap']); ?>" readonly>
                </div>
                <div class="mb-3">
                    <label for="nim" class="form-label">NIM</label>
                    <input type="text" class="form-control" id="nim" name="nim" value="<?= htmlspecialchars($user['username']); ?>" readonly>
                </div>
                <div class="mb-3">
                    <label for="keperluan" class="form-label">Keterangan Sakit dan Tanggal Izin</label>
                    <textarea class="form-control" id="keperluan" name="keperluan" rows="3" required placeholder="Contoh: Izin tidak mengikuti perkuliahan pada tanggal 1-2 Juni 2024 karena sakit demam."></textarea>
                </div>
                <div class="alert alert-warning">Fitur unggah file bukti sakit sedang dalam pengembangan. Untuk saat ini, silakan serahkan bukti fisik ke bagian akademik.</div>
                <button type="submit" class="btn btn-primary-custom">Ajukan Surat</button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/validations.js"></script>
</body>
</html>
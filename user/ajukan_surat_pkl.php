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
    // Jika user tidak ditemukan, logout untuk keamanan
    header('Location: ../auth/logout.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Surat Pengantar PKL/Magang - Sistem Surat ULM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
        <a class="navbar-brand" href="index.php">User Dashboard ULM</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <span class="navbar-text me-3">Selamat datang, <?= htmlspecialchars($_SESSION['username']); ?></span>
                </li>
                <li class="nav-item">
                    <a href="../auth/logout.php" class="btn btn-danger">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div class="card">
        <div class="card-header card-header-custom">
            Formulir Pengajuan Surat Pengantar PKL/Magang
        </div>
        <div class="card-body">
            <form action="proses_ajukan.php" method="POST">
                <!-- Hidden input untuk jenis surat -->
                <input type="hidden" name="jenis_surat" value="Surat Pengantar PKL/Magang">
                
                <div class="mb-3">
                    <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?= htmlspecialchars($user['nama_lengkap']); ?>" readonly>
                </div>
                <div class="mb-3">
                    <label for="nim" class="form-label">NIM</label>
                    <input type="text" class="form-control" id="nim" name="nim" value="<?= htmlspecialchars($user['username']); ?>" readonly>
                </div>
                <div class="mb-3">
                    <label for="semester" class="form-label">Semester</label>
                    <input type="number" class="form-control" id="semester" name="semester" required min="1" max="14" placeholder="Masukkan semester Anda saat ini">
                </div>
                <div class="mb-3">
                    <label for="keperluan" class="form-label">Instansi Tujuan dan Periode</label>
                    <textarea class="form-control" id="keperluan" name="keperluan" rows="3" required placeholder="Contoh: Ditujukan kepada HRD PT. Telkom Indonesia untuk periode magang Juli - Desember 2024"></textarea>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="durasi_magang" class="form-label">Durasi Magang</label>
                        <select class="form-select" id="durasi_magang" name="durasi_magang" required>
                            <option value="">-- Pilih Durasi --</option>
                            <option value="1 Bulan">1 Bulan</option>
                            <option value="2 Bulan">2 Bulan</option>
                            <option value="3 Bulan">3 Bulan</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
                        <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="tanggal_selesai" class="form-label">Tanggal Selesai</label>
                        <input type="date" class="form-control" id="tanggal_selesai" name="tanggal_selesai" required>
                    </div>
                </div>
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
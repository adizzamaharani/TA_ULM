<?php
session_start();
require '../config/database.php';

// Proteksi halaman: hanya user yang bisa akses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php?error=access_denied');
    exit();
}

// Sertakan header
// include '../includes/header.php'; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulir Pengajuan Surat - Sistem Surat ULM</title>
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
            Formulir Pengajuan Surat
        </div>
        <div class="card-body">
            <form action="proses_ajukan.php" method="POST">
                <div class="mb-3">
                    <label for="jenis_surat" class="form-label">Jenis Surat</label>
                    <select class="form-select" id="jenis_surat" name="jenis_surat" required>
                        <option value="">-- Pilih Jenis Surat --</option>
                        <option value="Surat Mahasiswa Aktif">Surat Mahasiswa Aktif</option>
                        <option value="Surat Izin Penelitian">Surat Izin Penelitian</option>
                        <option value="Surat Pengantar PKL/Magang">Surat Pengantar PKL/Magang</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="keperluan" class="form-label">Keperluan</label>
                    <textarea class="form-control" id="keperluan" name="keperluan" rows="4" required placeholder="Contoh: Untuk mengajukan beasiswa Bank Indonesia"></textarea>
                </div>
                <button type="submit" class="btn btn-primary-custom">Ajukan Surat</button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php 
// Sertakan footer
// include '../includes/footer.php'; 
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/validations.js"></script>
</body>
</html>

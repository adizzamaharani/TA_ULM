<?php
session_start();
require '../config/database.php';

// Proteksi halaman: hanya user yang bisa akses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php?error=access_denied');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informasi Dosen Pembimbing - Sistem Surat ULM</title>
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
            Informasi Dosen Pembimbing
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                Fitur ini sedang dalam pengembangan.
            </div>
            <a href="index.php" class="btn btn-secondary">Kembali ke Dashboard</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/validations.js"></script>
</body>
</html>
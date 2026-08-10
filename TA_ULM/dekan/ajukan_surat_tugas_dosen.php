<?php
session_start();
require '../config/database.php';

// Proteksi halaman: hanya dekan/wakil_dekan yang bisa akses
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['dekan', 'wakil_dekan'])) {
    header('Location: ../auth/login.php?error=access_denied');
    exit();
}

// Ambil data dosen yang sedang login
$stmt = $pdo->prepare("
    SELECT u.nama_lengkap, u.username, p.nama_prodi 
    FROM users u
    LEFT JOIN program_studi p ON u.prodi_id = p.id
    WHERE u.id = ?");
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
    <title>Pengajuan Surat Tugas Dosen - Sistem Surat ULM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="../assets/css/responsive.css?v=<?= time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .form-card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .form-card .card-header {
            background: linear-gradient(135deg, #FFD700 0%, #E6C300 100%);
            padding: 1.25rem 1.5rem;
            border-bottom: none;
        }
        .form-card .card-header h5 {
            margin: 0;
            font-weight: 700;
            color: #343a40;
        }
        .form-card .card-header p {
            margin: 0.25rem 0 0;
            font-size: 0.875rem;
            color: #555;
        }
        .form-card .card-body {
            padding: 2rem;
        }
        .form-label {
            font-weight: 600;
            color: #444;
            margin-bottom: 0.35rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #FFD700;
            box-shadow: 0 0 0 0.2rem rgba(255, 215, 0, 0.25);
        }
        .form-control[readonly] {
            background-color: #f8f9fa;
        }
        .btn-submit {
            background: linear-gradient(135deg, #FFD700 0%, #E6C300 100%);
            border: none;
            color: #343a40;
            font-weight: 700;
            padding: 0.6rem 2rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 215, 0, 0.4);
            color: #343a40;
        }
        .section-divider {
            border: none;
            border-top: 2px dashed #e9ecef;
            margin: 1.5rem 0;
        }
        .info-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #fff8dc;
            border: 1px solid #FFD700;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            color: #856404;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="../assets/img/logo_ulm.png?v=<?= filemtime('../assets/img/logo_ulm.png'); ?>" alt="Logo ULM" width="30" height="30" class="d-inline-block align-text-top me-2">
            <span class="d-none d-sm-inline">Dashboard Pimpinan</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <span class="navbar-text me-3">Selamat datang, <?= htmlspecialchars($_SESSION['nama_lengkap']); ?></span>
                </li>
                <li class="nav-item">
                    <a href="../auth/logout.php" class="btn btn-sm" style="background: rgba(0,0,0,0.1); font-weight: 600; border-radius: 8px; padding: 0.4rem 1rem;">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4 mb-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none"><i class="bi bi-house-door"></i> Dashboard</a></li>
            <li class="breadcrumb-item active">Surat Tugas Dosen</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card form-card">
                <div class="card-header">
                    <h5><i class="bi bi-person-badge me-2"></i>Formulir Pengajuan Surat Tugas Dosen</h5>
                    <p>Lengkapi data berikut untuk mengajukan surat tugas dosen pembimbing/penguji</p>
                </div>
                <div class="card-body">
                    <div class="info-badge">
                        <i class="bi bi-info-circle-fill"></i>
                        Data nama dan NIP diambil otomatis dari akun Anda
                    </div>

                    <form action="../user/proses_ajukan.php" method="POST">
                        <input type="hidden" name="jenis_surat" value="Surat Tugas Dosen">
                        <input type="hidden" name="from_dekan" value="1">
                        
                        <h6 class="fw-bold text-muted mb-3"><i class="bi bi-person me-1"></i> Data Dosen</h6>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?= htmlspecialchars($user['nama_lengkap']); ?>" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="nim" class="form-label">NIP</label>
                                <input type="text" class="form-control" id="nim" name="nim" value="<?= htmlspecialchars($user['username']); ?>" readonly>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="prodi" class="form-label">Program Studi</label>
                                <input type="text" class="form-control" id="prodi" value="<?= htmlspecialchars($user['nama_prodi'] ?? 'Belum diatur'); ?>" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="semester" class="form-label">Semester</label>
                                <input type="number" class="form-control" id="semester" name="semester" required min="1" max="14" placeholder="Masukkan semester saat ini">
                            </div>
                        </div>

                        <hr class="section-divider">
                        <h6 class="fw-bold text-muted mb-3"><i class="bi bi-briefcase me-1"></i> Detail Penugasan</h6>

                        <div class="mb-3">
                            <label for="dosen_pembimbing" class="form-label">Nama Dosen yang Ditugaskan</label>
                            <input type="text" class="form-control" id="dosen_pembimbing" name="dosen_pembimbing" required placeholder="Contoh: Dr. Muhammad Syaiful Anwar, S.P, M.P">
                        </div>
                        <div class="mb-3">
                            <label for="keperluan" class="form-label">Uraian Tugas</label>
                            <textarea class="form-control" id="keperluan" name="keperluan" rows="3" required placeholder="Contoh: Pembimbing skripsi mahasiswa dengan judul 'Analisis Pertumbuhan Tanaman Padi...'"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="tanggal_mulai" class="form-label">Tanggal Mulai Tugas</label>
                                <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="tanggal_selesai" class="form-label">Tanggal Selesai Tugas</label>
                                <input type="date" class="form-control" id="tanggal_selesai" name="tanggal_selesai" required>
                            </div>
                        </div>

                        <hr class="section-divider">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-submit"><i class="bi bi-send me-1"></i> Ajukan Surat</button>
                            <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/validations.js"></script>
</body>
</html>

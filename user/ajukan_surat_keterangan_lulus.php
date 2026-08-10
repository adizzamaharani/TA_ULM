<?php
session_start();
require '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php?error=access_denied');
    exit();
}

$stmt = $pdo->prepare("
    SELECT u.nama_lengkap, u.username, p.nama_prodi 
    FROM users u
    LEFT JOIN program_studi p ON u.prodi_id = p.id
    WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
if (!$user) { header('Location: ../auth/logout.php'); exit(); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Surat Keterangan Lulus - Sistem Surat ULM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .form-card{border:none;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08)}
        .form-card .card-header{background:linear-gradient(135deg,#FFD700,#E6C300);padding:1.25rem 1.5rem;border-bottom:none}
        .form-card .card-header h5{margin:0;font-weight:700;color:#343a40}
        .form-card .card-header p{margin:.25rem 0 0;font-size:.875rem;color:#555}
        .form-card .card-body{padding:2rem}
        .form-label{font-weight:600;color:#444;margin-bottom:.35rem}
        .form-control:focus{border-color:#FFD700;box-shadow:0 0 0 .2rem rgba(255,215,0,.25)}
        .form-control[readonly]{background-color:#f8f9fa}
        .btn-submit{background:linear-gradient(135deg,#FFD700,#E6C300);border:none;color:#343a40;font-weight:700;padding:.6rem 2rem;border-radius:8px;transition:all .3s}
        .btn-submit:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(255,215,0,.4);color:#343a40}
        .section-divider{border:none;border-top:2px dashed #e9ecef;margin:1.5rem 0}
        .info-badge{display:inline-flex;align-items:center;gap:.5rem;background:#fff8dc;border:1px solid #FFD700;border-radius:8px;padding:.5rem 1rem;font-size:.85rem;color:#856404;margin-bottom:1.5rem}
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="../assets/img/logo_ulm.png" alt="Logo ULM" width="30" height="30" class="me-2">
            <span class="d-none d-sm-inline">Dashboard Mahasiswa</span>
        </a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><span class="navbar-text me-3">Selamat datang, <?= htmlspecialchars($_SESSION['username']); ?></span></li>
                <li class="nav-item"><a href="../auth/logout.php" class="btn btn-danger btn-sm">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4 mb-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none"><i class="bi bi-house-door"></i> Dashboard</a></li>
            <li class="breadcrumb-item active">Surat Keterangan Lulus</li>
        </ol>
    </nav>
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card form-card">
                <div class="card-header">
                    <h5><i class="bi bi-mortarboard me-2"></i>Formulir Pengajuan Surat Keterangan Lulus</h5>
                    <p>Ajukan surat keterangan kelulusan dari Fakultas Pertanian ULM</p>
                </div>
                <div class="card-body">
                    <div class="info-badge"><i class="bi bi-info-circle-fill"></i> Data nama dan NIM diambil otomatis dari akun Anda</div>

                    <form action="proses_ajukan.php" method="POST">
                        <input type="hidden" name="jenis_surat" value="Surat Keterangan Lulus">
                        <h6 class="fw-bold text-muted mb-3"><i class="bi bi-person me-1"></i> Data Mahasiswa</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?= htmlspecialchars($user['nama_lengkap']); ?>" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="nim" class="form-label">NIM</label>
                                <input type="text" class="form-control" id="nim" name="nim" value="<?= htmlspecialchars($user['username']); ?>" readonly>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="prodi" class="form-label">Program Studi</label>
                            <input type="text" class="form-control" id="prodi" value="<?= htmlspecialchars($user['nama_prodi'] ?? 'Belum diatur'); ?>" readonly>
                        </div>

                        <hr class="section-divider">
                        <h6 class="fw-bold text-muted mb-3"><i class="bi bi-mortarboard me-1"></i> Detail Keperluan</h6>
                        <div class="mb-3">
                            <label for="keperluan" class="form-label">Keperluan Surat Keterangan Lulus</label>
                            <textarea class="form-control" id="keperluan" name="keperluan" rows="3" required placeholder="Contoh: Untuk melamar pekerjaan di PT. Astra Agro Lestari"></textarea>
                            <div class="form-text">Jelaskan untuk keperluan apa surat keterangan lulus ini dibutuhkan.</div>
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

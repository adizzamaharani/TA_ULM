<?php
session_start();
require '../config/database.php';

// Proteksi halaman: hanya user yang bisa akses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php?error=access_denied');
    exit();
}

// Ambil data lengkap user yang sedang login
$stmt = $pdo->prepare("
    SELECT u.*, p.nama_prodi, u.foto_profil
    FROM users u 
    LEFT JOIN program_studi p ON u.prodi_id = p.id 
    WHERE u.id = ?
");
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
    <title>Profil Pengguna - Sistem Surat ULM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }
        .main-container {
            display: flex;
            flex: 1;
        }
        .sidebar {
            width: 280px;
            background-color: #f8f9fa;
            border-right: 1px solid #dee2e6;
            transition: width 0.3s ease;
        }
        .content {
            flex: 1;
            padding: 2rem;
            transition: margin-left 0.3s ease;
        }
        .sidebar .nav-link {
            color: #333;
            font-size: 1rem;
            white-space: nowrap; /* Mencegah teks turun baris */
            overflow: hidden; /* Sembunyikan teks yang meluber */
        }
        .sidebar .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }
        .sidebar .nav-link:hover {
            background-color: #e9ecef;
        }
        .sidebar .nav-link .bi {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        .sidebar .nav-link .link-text {
            transition: opacity 0.3s ease;
        }

        /* Style untuk sidebar yang diperkecil */
        body.sidebar-toggled .sidebar {
            width: 80px;
        }
        body.sidebar-toggled .sidebar .nav-link .link-text,
        body.sidebar-toggled .sidebar .sidebar-heading {
            opacity: 0;
        }
        body.sidebar-toggled .sidebar .nav-link {
            justify-content: center;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">User Dashboard ULM</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav">
                <li class="nav-item"><button class="btn" id="sidebar-toggle"><i class="bi bi-list"></i></button></li>
            </ul>
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

<div class="main-container">
    <!-- Sidebar -->
    <div class="sidebar p-3">
        <h5 class="sidebar-heading mb-3"><span class="link-text">Menu Navigasi</span></h5>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="index.php">
                    <i class="bi bi-house-door-fill"></i>
                    <span class="link-text">Dashboard</span>
                </a>
            </li>
            <li class="nav-item mt-2">
                <span class="nav-link text-muted sidebar-heading"><span class="link-text">Pengajuan Surat</span></span>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="ajukan_surat_aktif.php"><i class="bi bi-file-earmark-person"></i><span class="link-text"> Surat Mahasiswa Aktif</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="ajukan_surat_penelitian.php"><i class="bi bi-flask"></i><span class="link-text"> Surat Izin Penelitian</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="ajukan_surat_pkl.php"><i class="bi bi-building"></i><span class="link-text"> Surat Pengantar PKL</span></a>
            </li>
            <li class="nav-item mt-3 border-top pt-2">
                <a class="nav-link active" href="profil.php">
                    <i class="bi bi-gear-fill"></i>
                    <span class="link-text">Profil Saya</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="content">
        <div class="card">
            <div class="card-header card-header-custom">
                <i class="bi bi-person-circle me-2"></i>Profil Pengguna
            </div>
            <div class="card-body">
                <?php 
                if (isset($_GET['status']) && $_GET['status'] == 'update_success') {
                    echo '<div class="alert alert-success">Profil berhasil diperbarui.</div>';
                }
                if (isset($_GET['status']) && $_GET['status'] == 'update_failed') {
                    echo '<div class="alert alert-danger">Gagal memperbarui profil.</div>';
                }
                ?>
                
                <form action="proses_update_profil.php" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <!-- Kolom Foto Profil -->
                        <div class="col-md-4 text-center">
                            <?php
                                $foto_path = '../assets/uploads/profil/' . ($user['foto_profil'] ?? 'default.png');
                                if (!file_exists($foto_path) || empty($user['foto_profil'])) {
                                    $foto_path = '../assets/img/default_avatar.png'; // Gambar placeholder
                                }
                            ?>
                            <img src="<?= $foto_path ?>" alt="Foto Profil" class="img-thumbnail rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                            <div class="mb-3">
                                <label for="foto_profil" class="form-label">Ganti Foto Profil</label>
                                <input class="form-control form-control-sm" type="file" id="foto_profil" name="foto_profil" accept="image/png, image/jpeg">
                                <small class="text-muted">Maks. 2MB (JPG, PNG)</small>
                            </div>
                        </div>
                        
                        <!-- Kolom Data Pengguna -->
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" id="nama_lengkap" value="<?= htmlspecialchars($user['nama_lengkap']); ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="nim" class="form-label">NIM</label>
                                <input type="text" class="form-control" id="nim" value="<?= htmlspecialchars($user['username']); ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="prodi" class="form-label">Program Studi</label>
                                <input type="text" class="form-control" id="prodi" value="<?= htmlspecialchars($user['nama_prodi'] ?? 'Belum diatur'); ?>" readonly>
                            </div>
                            <hr>
                            <p>Anda dapat mengubah informasi kontak di bawah ini:</p>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? ''); ?>" placeholder="Masukkan alamat email Anda">
                            </div>
                            <div class="mb-3">
                                <label for="telepon" class="form-label">Nomor Telepon</label>
                                <input type="tel" class="form-control" id="telepon" name="telepon" value="<?= htmlspecialchars($user['telepon'] ?? ''); ?>" placeholder="Masukkan nomor telepon Anda">
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 text-end">
                        <button type="submit" class="btn btn-primary-custom">Simpan Perubahan</button>
                        <a href="index.php" class="btn btn-secondary">Kembali ke Dashboard</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Card untuk Ubah Password -->
        <div class="card mt-4">
            <div class="card-header card-header-custom">
                <i class="bi bi-key-fill me-2"></i>Ubah Password
            </div>
            <div class="card-body">
                <?php 
                if (isset($_GET['password_status'])) {
                    if ($_GET['password_status'] == 'success') {
                        echo '<div class="alert alert-success">Password berhasil diubah.</div>';
                    } elseif ($_GET['password_status'] == 'error_mismatch') {
                        echo '<div class="alert alert-danger">Konfirmasi password baru tidak cocok.</div>';
                    } elseif ($_GET['password_status'] == 'error_old_password') {
                        echo '<div class="alert alert-danger">Password lama yang Anda masukkan salah.</div>';
                    } elseif ($_GET['password_status'] == 'error_short') {
                        echo '<div class="alert alert-danger">Password baru harus minimal 6 karakter.</div>';
                    } else {
                        echo '<div class="alert alert-danger">Terjadi kesalahan saat mengubah password.</div>';
                    }
                }
                ?>
                <form action="proses_ubah_password.php" method="POST">
                    <div class="mb-3">
                        <label for="password_lama" class="form-label">Password Lama</label>
                        <input type="password" class="form-control" id="password_lama" name="password_lama" required>
                    </div>
                    <div class="mb-3">
                        <label for="password_baru" class="form-label">Password Baru (minimal 6 karakter)</label>
                        <input type="password" class="form-control" id="password_baru" name="password_baru" required>
                    </div>
                    <div class="mb-3">
                        <label for="konfirmasi_password" class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" class="form-control" id="konfirmasi_password" name="konfirmasi_password" required>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary-custom">Ubah Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('sidebar-toggle').addEventListener('click', function() {
        document.body.classList.toggle('sidebar-toggled');
    });
</script>
<script src="../assets/js/validations.js"></script>
</body>
</html>
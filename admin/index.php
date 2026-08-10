<?php
session_start();
require '../config/database.php';

// 1. SET TIMEZONE WITA (Sesuai Banjarbaru)
date_default_timezone_set('Asia/Makassar');

// Proteksi halaman: hanya admin yang bisa akses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php?error=access_denied');
    exit();
}

// Logika untuk update status surat
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['surat_id']) && isset($_POST['status_baru'])) {
    $surat_id = $_POST['surat_id'];
    $status_baru = $_POST['status_baru'];
    $catatan_admin = $_POST['catatan_admin'] ?? null;

    // Default query update
    $query_update = "UPDATE surat SET status = ?, catatan_admin = ?, tanggal_penyelesaian = NULL WHERE id = ?";
    $params = [$status_baru, $catatan_admin, $surat_id]; // Set tanggal penyelesaian ke NULL jika status bukan 'Selesai'

    // Jika status diubah menjadi 'Selesai', set tanggal selesai otomatis
    // Jika status diubah dari 'Selesai' ke status lain, tanggal penyelesaian akan di-NULL-kan oleh query default di atas.
    if ($status_baru == 'Selesai') {
        // Gunakan kolom baru 'tanggal_penyelesaian'
        $query_update = "UPDATE surat SET status = ?, catatan_admin = ?, tanggal_penyelesaian = NOW() WHERE id = ?";
    }

    $stmt = $pdo->prepare($query_update);
    $stmt->execute($params);

    // Redirect kembali ke halaman dengan filter yang sama
    $filter_status = $_POST['current_filter'] ?? 'semua';
    header("Location: index.php?status=update_sukses&filter=" . $filter_status);
    exit();
}

// Logika untuk tambah pengguna baru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_user'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $role = $_POST['role'];
    $prodi_id = !empty($_POST['prodi_id']) ? $_POST['prodi_id'] : null;

    // Cek apakah username sudah ada
    $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $check->execute([$username]);
    if ($check->rowCount() > 0) {
        header("Location: index.php?status=error_username_exists");
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role, prodi_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, $password, $nama_lengkap, $role, $prodi_id]);
        header("Location: index.php?status=user_added");
    }
    exit();
}

// Logika untuk filter status
$filter = $_GET['filter'] ?? 'semua'; 
$kondisi_where = "";
$params_select = [];

if ($filter !== 'semua') {
    $kondisi_where = "WHERE s.status = ?";
    $params_select[] = $filter;
}

// Ambil semua data surat
$query_select = "
    SELECT s.*, u.username as nim, p.nama_prodi 
    FROM surat s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN program_studi p ON u.prodi_id = p.id
    $kondisi_where
    ORDER BY s.tanggal_pengajuan DESC
";
$stmt = $pdo->prepare($query_select);
$stmt->execute($params_select);
$daftar_surat = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil data prodi untuk dropdown tambah user
$stmt_prodi = $pdo->prepare("SELECT * FROM program_studi ORDER BY nama_prodi ASC");
$stmt_prodi->execute();
$list_prodi = $stmt_prodi->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Sistem Surat Faperta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="../assets/css/responsive.css?v=<?= time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { display: flex; min-height: 100vh; flex-direction: column; }
        .main-container { display: flex; flex: 1; }
        .sidebar { width: 280px; background: #fff; border-right: 1px solid #e9ecef; transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 2px 0 10px rgba(0,0,0,0.03); }
        .content { flex: 1; padding: 2rem; transition: margin-left 0.3s; background: #f5f6fa; }

        /* Sidebar styling */
        .sidebar .sidebar-heading {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #999;
            font-weight: 700;
        }
        .sidebar .nav-link {
            color: #555 !important;
            font-size: 0.92rem;
            font-weight: 500;
            padding: 0.7rem 1rem;
            border-radius: 10px;
            margin-bottom: 4px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
        }
        .sidebar .nav-link .bi {
            font-size: 1.15rem;
            width: 24px;
            text-align: center;
            margin-right: 12px;
            color: #888;
        }
        .sidebar .nav-link:hover {
            background: #FFF8DC;
            color: #1a1a2e !important;
            transform: translateX(4px);
        }
        .sidebar .nav-link:hover .bi {
            color: #C7A600;
        }
        .sidebar .nav-link.active {
            background: linear-gradient(135deg, #FFD700, #E6C300) !important;
            color: #1a1a2e !important;
            font-weight: 700;
            box-shadow: 0 3px 12px rgba(255, 215, 0, 0.3);
        }
        .sidebar .nav-link.active .bi {
            color: #1a1a2e !important;
        }

        /* Detail modal */
        .detail-label { font-weight: 600; color: #555; }
        .detail-value { color: #212529; }
        .modal-body .row { margin-bottom: 0.75rem; }
        .form-keperluan { white-space: pre-wrap; background-color: #f8f9fa; border-radius: 10px; padding: 12px 15px; border: 1px solid #e9ecef; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container-fluid">
        <div class="d-flex align-items-center">
            <button class="btn me-2" id="sidebar-toggle"><i class="bi bi-list fs-5"></i></button>
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="../assets/img/logo_ulm.png?v=<?= filemtime('../assets/img/logo_ulm.png'); ?>" alt="Logo ULM" width="32" height="32" class="d-inline-block align-text-top me-2">
                Admin Panel
            </a>
        </div>
        <ul class="navbar-nav ms-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['username']); ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                    <li><a class="dropdown-item" href="../auth/logout.php">Logout</a></li>
                </ul>
            </li>
        </ul>
    </div>
</nav>

<div class="main-container">
    <div class="sidebar p-3">
        <div class="mb-3 px-2">
            <span class="sidebar-heading"><span class="link-text">Menu Navigasi</span></span>
        </div>
        <ul class="nav flex-column" id="sidebar-nav">
            <li class="nav-item">
                <a class="nav-link active" href="index.php"><i class="bi bi-list-task"></i> <span class="link-text">Daftar Pengajuan</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="kegiatan_akademik.php"><i class="bi bi-briefcase-fill"></i> <span class="link-text">Kegiatan Akademik Dosen</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="mading.php"><i class="bi bi-megaphone-fill"></i> <span class="link-text">Kelola Mading</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="rekap.php"><i class="bi bi-file-earmark-bar-graph"></i> <span class="link-text">Rekap Laporan</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="statistik.php"><i class="bi bi-graph-up"></i> <span class="link-text">Statistik</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="backup_restore.php"><i class="bi bi-cloud-arrow-down"></i> <span class="link-text">Backup & Restore</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#tambahUserModal"><i class="bi bi-person-plus-fill"></i> <span class="link-text">Tambah Pengguna</span></a>
            </li>
        </ul>
    </div>
<div class="content">
    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h4><i class="bi bi-gear-wide-connected me-2"></i>Panel Administrator</h4>
                <p>Kelola pengajuan surat, verifikasi, dan manajemen pengguna.</p>
            </div>
            <i class="bi bi-clipboard2-data-fill d-none d-md-block" style="font-size: 2.5rem; opacity: 0.5;"></i>
        </div>
    </div>

    <h3><i class="bi bi-envelope-paper-fill me-2"></i>Manajemen Pengajuan Surat</h3>
    <hr>

    <?php
    if (isset($_GET['status']) && $_GET['status'] == 'update_sukses') {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                Status surat berhasil diperbarui.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
    } elseif (isset($_GET['status']) && $_GET['status'] == 'user_added') {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                Pengguna baru berhasil ditambahkan.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
    } elseif (isset($_GET['status']) && $_GET['status'] == 'error_username_exists') {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                Gagal menambahkan: Username/NIM sudah terdaftar.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
    }
    ?>

    <div class="d-flex align-items-center mb-3">
        <span class="me-3 fw-bold">Filter Status:</span>
        <div class="btn-group" role="group" aria-label="Filter Status Surat">
            <a href="index.php?filter=semua" class="btn btn-sm <?= $filter == 'semua' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Semua</a>
            <a href="index.php?filter=Diproses" class="btn btn-sm <?= $filter == 'Diproses' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Diproses</a>
            <a href="index.php?filter=Selesai" class="btn btn-sm <?= $filter == 'Selesai' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Selesai</a>
            <a href="index.php?filter=Ditolak" class="btn btn-sm <?= $filter == 'Ditolak' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Ditolak</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>No</th>
                    <th>Nama / NIM</th>
                    <th>Program Studi</th>
                    <th>Jenis Surat</th>
                    <th>Waktu Pengajuan</th>
                    <th>Status Saat Ini</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_surat)): ?>
                    <tr>
                        <td colspan="7" class="text-center">Tidak ada data untuk filter ini.</td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($daftar_surat as $surat): 
                        // --- LOGIKA TANGGAL PENGAJUAN (DIPERBAIKI) ---
                        // Ambil data mentah
                        $raw_tgl_aju = $surat['tanggal_pengajuan'];
                        
                        // Cek apakah data valid
                        if (!empty($raw_tgl_aju) && substr($raw_tgl_aju, 0, 4) != '0000') {
                            // Format Tanggal Real Time
                            $tgl_aju_tampil = date('d F Y, H:i:s', strtotime($raw_tgl_aju)) . ' WITA';
                        } else {
                            // Jika kosong di DB
                            $tgl_aju_tampil = '-'; 
                        }

                        // --- LOGIKA TANGGAL SELESAI ---
                        $raw_tgl_penyelesaian = $surat['tanggal_penyelesaian'];
                        if (!empty($raw_tgl_penyelesaian) && substr($raw_tgl_penyelesaian, 0, 4) != '0000') {
                             $tgl_selesai_tampil = date('d F Y, H:i:s', strtotime($raw_tgl_penyelesaian)) . ' WITA';
                        } else {
                             $tgl_selesai_tampil = 'Belum Selesai';
                        }
                    ?>
                    <tr 
                        data-id="<?= $surat['id']; ?>"
                        data-nama="<?= htmlspecialchars($surat['nama_lengkap']); ?>"
                        data-nim="<?= htmlspecialchars($surat['nim']); ?>"
                        data-prodi="<?= htmlspecialchars($surat['nama_prodi']); ?>"
                        data-jenis-surat="<?= htmlspecialchars($surat['jenis_surat']); ?>"
                        
                        data-tanggal-pengajuan="<?= $tgl_aju_tampil; ?>"
                        
                        data-keperluan="<?= htmlspecialchars($surat['keperluan']); ?>"
                        data-status="<?= htmlspecialchars($surat['status']); ?>"
                        data-catatan-admin="<?= htmlspecialchars($surat['catatan_admin'] ?? ''); ?>"
                        
                        data-tanggal-selesai="<?= $tgl_selesai_tampil; ?>"
                        data-tanggal-mulai="<?= htmlspecialchars($surat['tanggal_mulai'] ?? ''); ?>"
                        data-tanggal-selesai-kegiatan="<?= htmlspecialchars($surat['tanggal_selesai'] ?? ''); ?>"
                        
                        data-file-pendukung="<?= htmlspecialchars($surat['file_pendukung'] ?? ''); ?>"
                    >
                        <td><?= $no++; ?></td>
                        <td>
                            <?= htmlspecialchars($surat['nama_lengkap']); ?><br>
                            <small class="text-muted"><?= htmlspecialchars($surat['nim']); ?></small>
                        </td>
                        <td><?= htmlspecialchars($surat['nama_prodi']); ?></td>
                        <td>
                            <?= htmlspecialchars($surat['jenis_surat']); ?><br>
                            <small class="text-muted fst-italic">"<?= htmlspecialchars(substr($surat['keperluan'], 0, 50)); ?>..."</small>
                        </td>
                        
                        <td><?= $tgl_aju_tampil; ?></td>
                        
                        <td>
                            <span class="badge badge-status-<?= strtolower($surat['status']); ?>"><?= htmlspecialchars($surat['status']); ?></span>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary btn-sm detail-btn" data-bs-toggle="modal" data-bs-target="#detailSuratModal" title="Lihat Detail">
                                    <i class="bi bi-search"></i> Detail
                                </button>
                                <?php if ($surat['status'] == 'Selesai'): ?>
                                    <a href="generate_pdf.php?id=<?= $surat['id']; ?>" class="btn btn-success btn-sm" target="_blank" title="Cetak PDF"><i class="bi bi-printer"></i></a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="detailSuratModal" tabindex="-1" aria-labelledby="detailSuratModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header card-header-custom">
                <h5 class="modal-title" id="detailSuratModalLabel">Detail Pengajuan Surat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-3 detail-label">Nama Mahasiswa</div>
                    <div class="col-md-9 detail-value" id="detail-nama"></div>
                </div>
                <div class="row">
                    <div class="col-md-3 detail-label">NIM</div>
                    <div class="col-md-9 detail-value" id="detail-nim"></div>
                </div>
                <div class="row">
                    <div class="col-md-3 detail-label">Program Studi</div>
                    <div class="col-md-9 detail-value" id="detail-prodi"></div>
                </div>
                <div class="row">
                    <div class="col-md-3 detail-label">Jenis Surat</div>
                    <div class="col-md-9 detail-value" id="detail-jenis-surat"></div>
                </div>
                <!-- Container untuk tanggal kegiatan, disembunyikan secara default -->
                <div class="row" id="detail-tanggal-kegiatan-container" style="display: none;">
                    <div class="col-md-3 detail-label" id="detail-tanggal-label">Tanggal Pelaksanaan</div>
                    <div class="col-md-9 detail-value">
                        <span id="detail-tanggal-mulai"></span> s/d <span id="detail-tanggal-selesai-kegiatan"></span>
                    </div>
                </div>
                <!-- Akhir container tanggal kegiatan -->
                <div class="row">
                    <div class="col-md-3 detail-label">Waktu Pengajuan</div>
                    <div class="col-md-9 detail-value" id="detail-tanggal-pengajuan"></div>
                </div>
                 <div class="row">
                    <div class="col-md-3 detail-label">Waktu Selesai</div>
                    <div class="col-md-9 detail-value" id="detail-tanggal-selesai"></div>
                </div>
                <div class="row">
                    <div class="col-md-3 detail-label">File Pendukung</div>
                    <div class="col-md-9 detail-value" id="detail-file-pendukung"></div>
                </div>
                <hr>
                <p class="detail-label">Keperluan:</p>
                <p class="form-keperluan" id="detail-keperluan"></p>
                
                <hr class="mt-4">
                <h5 class="mb-3">Update Status</h5>
                <form action="index.php" method="POST">
                    <input type="hidden" name="surat_id" id="form-surat-id">
                    <input type="hidden" name="current_filter" value="<?= $filter; ?>">
                    
                    <div class="row align-items-center">
                        <div class="col-md-6 mb-3">
                            <label for="status_baru" class="form-label">Ubah Status Menjadi:</label>
                            <select name="status_baru" id="form-status-baru" class="form-select">
                                <option value="Diproses">Diproses</option>
                                <option value="Selesai">Selesai</option>
                                <option value="Ditolak">Ditolak</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label for="catatan_admin" class="form-label">Catatan Admin (Opsional):</label>
                            <textarea name="catatan_admin" id="form-catatan-admin" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer mt-4 pb-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary-custom"><i class="bi bi-check-lg"></i> Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Pengguna -->
<div class="modal fade" id="tambahUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header card-header-custom">
                <h5 class="modal-title"><i class="bi bi-person-plus-fill me-2"></i>Tambah Pengguna Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="tambah_user" value="1">
                    <div class="mb-3">
                        <label class="form-label">Username / NIM / NIP</label>
                        <input type="text" name="username" class="form-control" required placeholder="Masukkan NIM atau NIP">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required placeholder="Masukkan Password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-control" required placeholder="Nama Lengkap Pengguna">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select" required>
                            <option value="admin">Admin</option>
                            <option value="user">Mahasiswa</option>
                            <option value="dekan">Dekan</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Program Studi (Khusus Mahasiswa)</label>
                        <select name="prodi_id" class="form-select">
                            <option value="">- Pilih Program Studi -</option>
                            <?php foreach ($list_prodi as $prodi): ?>
                                <option value="<?= $prodi['id']; ?>"><?= htmlspecialchars($prodi['nama_prodi']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-custom">Simpan Pengguna</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const detailModal = document.getElementById('detailSuratModal');
    detailModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const row = button.closest('tr');

        // Ambil data dari atribut data-* di <tr>
        const data = row.dataset;

        // Isi konten modal dengan data yang sudah diformat di PHP
        document.getElementById('detail-nama').textContent = data.nama;
        document.getElementById('detail-nim').textContent = data.nim;
        document.getElementById('detail-prodi').textContent = data.prodi;
        document.getElementById('detail-jenis-surat').textContent = data.jenisSurat;
        document.getElementById('detail-tanggal-pengajuan').textContent = data.tanggalPengajuan;
        document.getElementById('detail-tanggal-selesai').textContent = data.tanggalSelesai;
        document.getElementById('detail-keperluan').textContent = data.keperluan;
        document.getElementById('detail-file-pendukung').innerHTML = data.filePendukung ? `<a href="../uploads/files/${data.filePendukung}" target="_blank">${data.filePendukung}</a>` : 'Tidak ada';

        // Logika untuk menampilkan tanggal kegiatan
        const tanggalContainer = document.getElementById('detail-tanggal-kegiatan-container');
        const tanggalMulaiEl = document.getElementById('detail-tanggal-mulai');
        const tanggalSelesaiEl = document.getElementById('detail-tanggal-selesai-kegiatan');
        const tanggalLabel = document.getElementById('detail-tanggal-label');

        if (data.jenisSurat === 'Surat Pengantar PKL/Magang' || data.jenisSurat === 'Surat Izin Penelitian' || data.jenisSurat === 'Surat Tugas Dosen') {
            if (data.jenisSurat === 'Surat Izin Penelitian') {
                tanggalLabel.textContent = 'Tanggal Penelitian';
            } else if (data.jenisSurat === 'Surat Tugas Dosen') {
                tanggalLabel.textContent = 'Tanggal Penugasan';
            } else {
                tanggalLabel.textContent = 'Tanggal PKL/Magang';
            }
            tanggalMulaiEl.textContent = data.tanggalMulai ? new Date(data.tanggalMulai).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-';
            tanggalSelesaiEl.textContent = data.tanggalSelesaiKegiatan ? new Date(data.tanggalSelesaiKegiatan).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-';
            tanggalContainer.style.display = 'flex';
        } else {
            tanggalContainer.style.display = 'none';
        }

        // Isi form update status
        document.getElementById('form-surat-id').value = data.id;
        document.getElementById('form-status-baru').value = data.status;
        document.getElementById('form-catatan-admin').value = data.catatanAdmin;
    });
});
</script>
<script src="../assets/js/validations.js"></script>
</body>
</html>
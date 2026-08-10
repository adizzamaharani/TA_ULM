<?php
session_start();
require '../config/database.php';
date_default_timezone_set('Asia/Makassar');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php?error=access_denied');
    exit();
}

// Cek direktori backup
$backup_dir = __DIR__ . '/../assets/uploads/backups';
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}

// List existing backups
$backups = [];
$files = glob($backup_dir . '/*.sql');
if ($files) {
    foreach ($files as $file) {
        $backups[] = [
            'name' => basename($file),
            'size' => filesize($file),
            'date' => filemtime($file)
        ];
    }
    usort($backups, function($a, $b) { return $b['date'] - $a['date']; });
}

function format_size($bytes) {
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return round($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup & Restore - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="../assets/css/responsive.css?v=<?= time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body{display:flex;min-height:100vh;flex-direction:column}
        .main-container{display:flex;flex:1}
        .sidebar{width:280px;background:#fff;border-right:1px solid #e9ecef;box-shadow:2px 0 10px rgba(0,0,0,0.03)}
        .content{flex:1;padding:2rem;background:#f5f6fa}
        .sidebar .sidebar-heading{font-size:0.7rem;text-transform:uppercase;letter-spacing:1.5px;color:#999;font-weight:700}
        .sidebar .nav-link{color:#555 !important;font-size:0.92rem;font-weight:500;padding:0.7rem 1rem;border-radius:10px;margin-bottom:4px;transition:all 0.2s ease;display:flex;align-items:center}
        .sidebar .nav-link .bi{font-size:1.15rem;width:24px;text-align:center;margin-right:12px;color:#888}
        .sidebar .nav-link:hover{background:#FFF8DC;color:#1a1a2e !important;transform:translateX(4px)}
        .sidebar .nav-link:hover .bi{color:#C7A600}
        .sidebar .nav-link.active{background:linear-gradient(135deg,#FFD700,#E6C300) !important;color:#1a1a2e !important;font-weight:700;box-shadow:0 3px 12px rgba(255,215,0,0.3)}
        .sidebar .nav-link.active .bi{color:#1a1a2e !important}

        .action-card{border:none;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,.08);transition:transform .2s,box-shadow .2s;overflow:hidden}
        .action-card:hover{transform:translateY(-4px);box-shadow:0 8px 30px rgba(0,0,0,.12)}
        .action-card .card-header{padding:1.5rem;border:none;color:#fff}
        .action-card .card-body{padding:1.5rem}
        .bg-backup{background:linear-gradient(135deg,#11998e,#38ef7d)}
        .bg-restore{background:linear-gradient(135deg,#F2994A,#F2C94C)}
        .action-icon{font-size:2.5rem;margin-bottom:.5rem}

        .backup-list .list-group-item{border-left:4px solid #11998e;transition:background .2s}
        .backup-list .list-group-item:hover{background:#f8f9fa}

        .info-card{border:none;border-radius:12px;background:linear-gradient(135deg,#667eea20,#764ba220);border:1px solid #667eea30}
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container-fluid">
        <div class="d-flex align-items-center">
            <button class="btn me-2" id="sidebar-toggle"><i class="bi bi-list fs-5"></i></button>
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="../assets/img/logo_ulm.png?v=<?= filemtime('../assets/img/logo_ulm.png'); ?>" alt="Logo ULM" width="32" height="32" class="me-2">Admin Panel
            </a>
        </div>
        <ul class="navbar-nav ms-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['username']); ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end"><li><a class="dropdown-item text-danger" href="../auth/logout.php">Logout</a></li></ul>
            </li>
        </ul>
    </div>
</nav>

<div class="main-container">
    <div class="sidebar p-3">
        <div class="mb-3 px-2">
            <span class="sidebar-heading"><span class="link-text">Menu Navigasi</span></span>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-list-task"></i> <span class="link-text">Daftar Pengajuan</span></a></li>
            <li class="nav-item"><a class="nav-link" href="mading.php"><i class="bi bi-megaphone-fill"></i> <span class="link-text">Kelola Mading</span></a></li>
            <li class="nav-item"><a class="nav-link" href="rekap.php"><i class="bi bi-file-earmark-bar-graph"></i> <span class="link-text">Rekap Laporan</span></a></li>
            <li class="nav-item"><a class="nav-link" href="statistik.php"><i class="bi bi-graph-up"></i> <span class="link-text">Statistik</span></a></li>
            <li class="nav-item"><a class="nav-link active" href="backup_restore.php"><i class="bi bi-cloud-arrow-down"></i> <span class="link-text">Backup & Restore</span></a></li>
        </ul>
    </div>

    <div class="content">
        <h3 class="mb-3"><i class="bi bi-shield-check me-2"></i>Backup & Restore Database</h3>
        <hr>

        <!-- Info Card -->
        <div class="card info-card mb-4">
            <div class="card-body">
                <div class="d-flex align-items-start">
                    <i class="bi bi-info-circle-fill text-primary me-3 fs-4"></i>
                    <div>
                        <h6 class="mb-1 fw-bold">Informasi Penting</h6>
                        <p class="mb-0 text-muted small">
                            <strong>Backup</strong> akan mengekspor seluruh data (surat, pengguna, mading, program studi) ke file <code>.sql</code>.<br>
                            <strong>Restore</strong> akan menghapus semua data yang ada dan menggantinya dengan data dari file backup. Pastikan Anda yakin sebelum melakukan restore.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Cards -->
        <div class="row mb-4 g-4">
            <!-- Backup Card -->
            <div class="col-lg-6">
                <div class="card action-card h-100">
                    <div class="card-header bg-backup text-center">
                        <div class="action-icon"><i class="bi bi-cloud-arrow-down-fill"></i></div>
                        <h4 class="mb-0">Backup Database</h4>
                        <small class="opacity-75">Simpan salinan data ke file .sql</small>
                    </div>
                    <div class="card-body text-center">
                        <p class="text-muted mb-3">Klik tombol di bawah untuk membuat backup seluruh database. File akan otomatis terunduh.</p>
                        <form action="proses_backup.php" method="POST" id="formBackup">
                            <div class="mb-3">
                                <label class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="mode" value="download" checked>
                                    <span class="form-check-label">Download Langsung</span>
                                </label>
                                <label class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="mode" value="save">
                                    <span class="form-check-label">Simpan di Server</span>
                                </label>
                            </div>
                            <button type="submit" class="btn btn-lg text-white px-5" style="background:linear-gradient(135deg,#11998e,#38ef7d);border:none">
                                <i class="bi bi-download me-2"></i>Backup Sekarang
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Restore Card -->
            <div class="col-lg-6">
                <div class="card action-card h-100">
                    <div class="card-header bg-restore text-center">
                        <div class="action-icon"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                        <h4 class="mb-0">Restore Database</h4>
                        <small class="opacity-75">Pulihkan data dari file .sql</small>
                    </div>
                    <div class="card-body text-center">
                        <p class="text-muted mb-3">Pilih file backup <code>.sql</code> untuk memulihkan data. Semua data saat ini akan diganti.</p>
                        <form action="proses_restore.php" method="POST" enctype="multipart/form-data" id="formRestore">
                            <div class="mb-3">
                                <input type="file" name="sql_file" class="form-control" accept=".sql" required id="sqlFileInput">
                                <small class="form-text text-muted">Maks. 10MB, hanya file .sql</small>
                            </div>
                            <button type="submit" class="btn btn-lg text-dark px-5" style="background:linear-gradient(135deg,#F2994A,#F2C94C);border:none">
                                <i class="bi bi-upload me-2"></i>Restore Sekarang
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Backup History -->
        <div class="card" style="border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.08)">
            <div class="card-header card-header-custom d-flex justify-content-between align-items-center">
                <span><i class="bi bi-clock-history me-2"></i>Riwayat Backup di Server</span>
                <span class="badge bg-dark"><?= count($backups); ?> file</span>
            </div>
            <div class="card-body">
                <?php if (empty($backups)): ?>
                    <p class="text-center text-muted my-3"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Belum ada backup tersimpan di server.</p>
                <?php else: ?>
                    <div class="list-group backup-list">
                        <?php foreach ($backups as $bk): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-file-earmark-code text-success me-2"></i>
                                <strong><?= htmlspecialchars($bk['name']); ?></strong>
                                <br><small class="text-muted">
                                    <i class="bi bi-calendar3 me-1"></i><?= date('d M Y, H:i:s', $bk['date']); ?> WITA
                                    &bull; <i class="bi bi-hdd me-1"></i><?= format_size($bk['size']); ?>
                                </small>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="proses_backup.php?download=<?= urlencode($bk['name']); ?>" class="btn btn-sm btn-outline-success" title="Download">
                                    <i class="bi bi-download"></i>
                                </a>
                                <button class="btn btn-sm btn-outline-warning btn-restore-server" data-file="<?= htmlspecialchars($bk['name']); ?>" title="Restore dari file ini">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger btn-delete-backup" data-file="<?= htmlspecialchars($bk['name']); ?>" title="Hapus backup">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Konfirmasi Restore dari upload
document.getElementById('formRestore').addEventListener('submit', function(e) {
    e.preventDefault();
    const file = document.getElementById('sqlFileInput').files[0];
    if (!file) { Swal.fire('Error', 'Pilih file .sql terlebih dahulu!', 'error'); return; }
    if (!file.name.endsWith('.sql')) { Swal.fire('Error', 'Hanya file .sql yang diizinkan!', 'error'); return; }
    if (file.size > 10 * 1024 * 1024) { Swal.fire('Error', 'Ukuran file melebihi 10MB!', 'error'); return; }

    Swal.fire({
        title: 'Konfirmasi Restore',
        html: '<div class="text-start"><p class="text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i>PERINGATAN!</p><p>Seluruh data saat ini akan <strong>dihapus</strong> dan diganti dengan data dari file backup:</p><p class="fw-bold">' + file.name + '</p><p>Tindakan ini <strong>tidak dapat dibatalkan</strong>!</p></div>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-check-lg"></i> Ya, Restore!',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({title:'Memproses restore...',allowOutsideClick:false,didOpen:()=>{Swal.showLoading();}});
            this.submit();
        }
    });
});

// Restore dari server
document.querySelectorAll('.btn-restore-server').forEach(btn => {
    btn.addEventListener('click', function() {
        const filename = this.dataset.file;
        Swal.fire({
            title: 'Restore dari Backup Server?',
            html: '<p>Restore dari file: <strong>' + filename + '</strong></p><p class="text-danger">Seluruh data saat ini akan diganti!</p>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: '<i class="bi bi-check-lg"></i> Ya, Restore!',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({title:'Memproses restore...',allowOutsideClick:false,didOpen:()=>{Swal.showLoading();}});
                window.location.href = 'proses_restore.php?server_file=' + encodeURIComponent(filename);
            }
        });
    });
});

// Hapus backup
document.querySelectorAll('.btn-delete-backup').forEach(btn => {
    btn.addEventListener('click', function() {
        const filename = this.dataset.file;
        Swal.fire({
            title: 'Hapus backup ini?',
            text: 'File: ' + filename,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'proses_backup.php?delete=' + encodeURIComponent(filename);
            }
        });
    });
});
</script>
<script src="../assets/js/validations.js"></script>
<script>
<?php if (isset($_GET['status'])): ?>
document.addEventListener('DOMContentLoaded', function(){
    <?php if ($_GET['status'] === 'backup_saved'): ?>
    Swal.fire({icon:'success',title:'Backup Berhasil!',text:'File backup berhasil disimpan di server.',timer:3000,showConfirmButton:false});
    <?php elseif ($_GET['status'] === 'restore_success'): ?>
    Swal.fire({icon:'success',title:'Restore Berhasil!',text:'Database berhasil dipulihkan dari file backup.',timer:3000,showConfirmButton:false});
    <?php elseif ($_GET['status'] === 'restore_error'): ?>
    Swal.fire({icon:'error',title:'Restore Gagal!',text:'<?= addslashes($_GET['msg'] ?? 'Terjadi kesalahan.'); ?>'});
    <?php elseif ($_GET['status'] === 'deleted'): ?>
    Swal.fire({icon:'success',title:'Dihapus!',text:'File backup berhasil dihapus.',timer:2000,showConfirmButton:false});
    <?php elseif ($_GET['status'] === 'file_not_found'): ?>
    Swal.fire({icon:'error',title:'File Tidak Ditemukan',text:'File backup yang diminta tidak ada.'});
    <?php endif; ?>
});
<?php endif; ?>
</script>
</body>
</html>

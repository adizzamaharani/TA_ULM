<?php
session_start();
require '../config/database.php';

date_default_timezone_set('Asia/Makassar');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dosen') {
    header('Location: ../auth/login.php?error=access_denied');
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data user
$user_stmt = $pdo->prepare("SELECT username, nama_lengkap, foto_profil FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Ambil data mahasiswa untuk tabel pilihan
$stmt_mhs = $pdo->query("
    SELECT u.id, u.username as nim, u.nama_lengkap, p.nama_prodi, 
    COALESCE((SELECT semester FROM surat s WHERE s.user_id = u.id ORDER BY s.id DESC LIMIT 1), '-') as semester_terakhir
    FROM users u
    LEFT JOIN program_studi p ON u.prodi_id = p.id
    WHERE u.role = 'user'
    ORDER BY u.nama_lengkap ASC
");
$mahasiswa_list = $stmt_mhs->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Kegiatan Akademik - Sistem Surat ULM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="../assets/css/responsive.css?v=<?= time(); ?>">
    <style>
        body { display: flex; min-height: 100vh; flex-direction: column; }
        .main-container { display: flex; flex: 1; }
        .sidebar { width: 280px; background: #fff; border-right: 1px solid #e9ecef; transition: width 0.3s; box-shadow: 2px 0 10px rgba(0,0,0,0.03); }
        .content { flex: 1; padding: 2rem; background: #f5f6fa; transition: margin-left 0.3s; }
        .sidebar .nav-link { color: #555 !important; font-size: 0.92rem; font-weight: 500; padding: 0.65rem 1rem; border-radius: 10px; margin-bottom: 3px; display: flex; align-items: center; }
        .sidebar .nav-link .bi { font-size: 1.15rem; width: 24px; text-align: center; margin-right: 12px; color: #888; flex-shrink: 0; }
        .sidebar .nav-link:hover { background: #FFF8DC; color: #1a1a2e !important; }
        .sidebar .nav-link:hover .bi { color: #C7A600; }
        .sidebar .nav-link.active { background: linear-gradient(135deg, #FFD700, #E6C300) !important; color: #1a1a2e !important; font-weight: 700; box-shadow: 0 3px 12px rgba(255, 215, 0, 0.3); }
        .sidebar .nav-link.active .bi { color: #1a1a2e !important; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container-fluid">
        <div class="d-flex align-items-center">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="../assets/img/logo_ulm.png" alt="Logo ULM" width="32" height="32" class="d-inline-block align-text-top me-2">
                <span class="d-none d-sm-inline">Sistem Surat Faperta - Dosen</span>
            </a>
        </div>
        <ul class="navbar-nav ms-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                    <?= htmlspecialchars($user_data['nama_lengkap']); ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item text-danger" href="../auth/logout.php">Logout</a></li>
                </ul>
            </li>
        </ul>
    </div>
</nav>

<div class="main-container">
    <div class="sidebar p-3">
        <h5 class="sidebar-heading text-muted mb-3" style="font-size: 0.75rem; text-transform: uppercase;">Menu Navigasi</h5>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="index.php">
                    <i class="bi bi-house-door-fill"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="buat_kegiatan.php">
                    <i class="bi bi-plus-circle-fill"></i> Buat Kegiatan
                </a>
            </li>
        </ul>
    </div>

    <div class="content">
        <h3 class="mb-4 fw-bold text-dark">Buat Kegiatan Akademik Baru</h3>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <form action="proses_kegiatan.php" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Nama Kegiatan</label>
                            <input type="text" name="nama_kegiatan" class="form-control" required placeholder="Contoh: Penelitian Lahan Gambut">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Jenis Kegiatan</label>
                            <select name="jenis_kegiatan" class="form-select" required>
                                <option value="" disabled selected>Pilih Jenis Kegiatan...</option>
                                <option value="Penelitian">Penelitian</option>
                                <option value="Seminar">Seminar</option>
                                <option value="Surat Tugas">Surat Tugas</option>
                                <option value="Bimbingan Akademik">Bimbingan Akademik</option>
                                <option value="PKL/Magang">PKL atau Magang</option>
                                <option value="Asisten Laboratorium">Asisten Laboratorium</option>
                                <option value="Proyek Kampus">Proyek Kampus</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Deskripsi Kegiatan</label>
                        <textarea name="deskripsi" class="form-control" rows="3" required placeholder="Jelaskan tujuan dan ruang lingkup kegiatan..."></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Tanggal Kegiatan</label>
                            <input type="date" name="tanggal_kegiatan" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Lokasi</label>
                            <input type="text" name="lokasi" class="form-control" required placeholder="Contoh: Lab Agroteknologi">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Lampiran (Opsional)</label>
                            <input type="file" name="lampiran" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted">PDF atau Gambar maks 2MB</small>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-people-fill text-primary"></i> Pilih Mahasiswa Terlibat</h5>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover w-100" id="tableMahasiswa">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" width="5%">
                                        <input class="form-check-input" type="checkbox" id="selectAll">
                                    </th>
                                    <th>NIM</th>
                                    <th>Nama Mahasiswa</th>
                                    <th>Program Studi</th>
                                    <th>Semester</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mahasiswa_list as $mhs): ?>
                                <tr>
                                    <td class="text-center">
                                        <input class="form-check-input mhs-checkbox" type="checkbox" name="mahasiswa_id[]" value="<?= $mhs['id'] ?>">
                                    </td>
                                    <td><?= htmlspecialchars($mhs['nim']) ?></td>
                                    <td><?= htmlspecialchars($mhs['nama_lengkap']) ?></td>
                                    <td><?= htmlspecialchars($mhs['nama_prodi'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($mhs['semester_terakhir'] ?? '-') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 text-end">
                        <a href="index.php" class="btn btn-secondary rounded-pill me-2">Batal</a>
                        <button type="submit" class="btn btn-primary rounded-pill px-4"><i class="bi bi-send-fill"></i> Ajukan Kegiatan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    var table = $('#tableMahasiswa').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json"
        },
        "pageLength": 10,
        "ordering": false // Disable ordering so checkbox doesn't act weird on sort
    });

    // Handle Select All
    $('#selectAll').on('click', function(){
        // Get all rows with search applied
        var rows = table.rows({ 'search': 'applied' }).nodes();
        // Check/uncheck checkboxes for all rows in the table
        $('input[type="checkbox"]', rows).prop('checked', this.checked);
    });

    // Handle form submit to include all checked checkboxes across pages
    $('form').on('submit', function(e){
        var form = this;
        // Iterate over all checkboxes in the table
        table.$('input[type="checkbox"]').each(function(){
            // If checkbox is checked and doesn't exist in DOM (hidden by pagination)
            if(!$.contains(document, this)){
                if(this.checked){
                    // Create a hidden element
                    $(form).append(
                        $('<input>')
                            .attr('type', 'hidden')
                            .attr('name', this.name)
                            .val(this.value)
                    );
                }
            }
        });
    });
});
</script>
</body>
</html>

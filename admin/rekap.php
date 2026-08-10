<?php
session_start();
require '../config/database.php';

// Set zona waktu default ke Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');

// Proteksi halaman: hanya admin yang bisa akses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php?error=access_denied');
    exit();
}

// Tentukan range tanggal yang dipilih
$tanggal_dari = $_POST['tanggal_dari'] ?? date('Y-m-01'); // Default: awal bulan ini
$tanggal_sampai = $_POST['tanggal_sampai'] ?? date('Y-m-d'); // Default: hari ini

// Ambil data rekap berdasarkan filter range tanggal
$query = "
    SELECT s.*, u.username as nim, p.nama_prodi 
    FROM surat s 
    JOIN users u ON s.user_id = u.id
    LEFT JOIN program_studi p ON u.prodi_id = p.id
    WHERE DATE(s.tanggal_pengajuan) BETWEEN ? AND ?
    ORDER BY s.tanggal_pengajuan ASC
";
$stmt = $pdo->prepare($query);
$stmt->execute([$tanggal_dari, $tanggal_sampai]);
$rekap_surat = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Array nama bulan untuk tampilan
$nama_bulan = [
   '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
   '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
   '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

// Format label tanggal untuk tampilan
function format_tanggal_indo($tgl) {
    $nama_bulan_arr = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];
    $parts = explode('-', $tgl);
    return $parts[2] . ' ' . ($nama_bulan_arr[$parts[1]] ?? $parts[1]) . ' ' . $parts[0];
}
$periode_label = format_tanggal_indo($tanggal_dari) . ' s/d ' . format_tanggal_indo($tanggal_sampai);

// --- LOGIKA UNTUK LAPORAN REKAP ---

// Inisialisasi array untuk rekap
$rekap_status = [];
$rekap_prodi = [];
$rekap_jenis_surat = [];
$rekap_per_tanggal = [];
$waktu_proses = []; // Untuk laporan baru

$total_selesai = 0;
$total_waktu_detik = 0;


if (!empty($rekap_surat)) {
    foreach ($rekap_surat as $surat) {
        // 1. Rekap berdasarkan status
        $status = $surat['status'];
        $rekap_status[$status] = ($rekap_status[$status] ?? 0) + 1;

        // 2. Rekap berdasarkan program studi
        $prodi = $surat['nama_prodi'] ?? 'Tidak Terdaftar';
        $rekap_prodi[$prodi] = ($rekap_prodi[$prodi] ?? 0) + 1;

        // 3. Rekap berdasarkan jenis surat
        $jenis = $surat['jenis_surat'];
        $rekap_jenis_surat[$jenis] = ($rekap_jenis_surat[$jenis] ?? 0) + 1;

        // 4. Rekap berdasarkan tanggal pengajuan
        $tanggal = date('d-m-Y', strtotime($surat['tanggal_pengajuan']));
        $rekap_per_tanggal[$tanggal] = ($rekap_per_tanggal[$tanggal] ?? 0) + 1;

        // 5. Rekap Waktu Proses (HANYA UNTUK SURAT YANG SUDAH SELESAI)
        if ($surat['status'] == 'Selesai' && !empty($surat['tanggal_penyelesaian'])) {
            $tgl_aju = new DateTime($surat['tanggal_pengajuan']);
            $tgl_selesai = new DateTime($surat['tanggal_penyelesaian']);
            $selisih = $tgl_selesai->getTimestamp() - $tgl_aju->getTimestamp(); // Selisih dalam detik
            $waktu_proses[] = $selisih;
            $total_selesai++;
            $total_waktu_detik += $selisih;
        }
    }
    ksort($rekap_per_tanggal); // Urutkan berdasarkan tanggal
}

// Fungsi helper untuk format durasi dari detik
function format_durasi($detik) {
    if ($detik < 0) return 'N/A';
    $hari = floor($detik / (3600 * 24));
    $jam = floor(($detik % (3600 * 24)) / 3600);
    $menit = floor(($detik % 3600) / 60);

    $output = '';
    if ($hari > 0) $output .= $hari . ' hari ';
    if ($jam > 0) $output .= $jam . ' jam ';
    if ($menit > 0) $output .= $menit . ' menit';
    return trim($output) ?: 'Kurang dari 1 menit';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Laporan - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="../assets/css/responsive.css?v=<?= time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { display: flex; min-height: 100vh; flex-direction: column; }
        .main-container { display: flex; flex: 1; }
        .sidebar { width: 280px; background: #fff; border-right: 1px solid #e9ecef; box-shadow: 2px 0 10px rgba(0,0,0,0.03); }
        .content { flex: 1; padding: 2rem; background: #f5f6fa; }
        .sidebar .sidebar-heading { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1.5px; color: #999; font-weight: 700; }
        .sidebar .nav-link { color: #555 !important; font-size: 0.92rem; font-weight: 500; padding: 0.7rem 1rem; border-radius: 10px; margin-bottom: 4px; transition: all 0.2s ease; display: flex; align-items: center; }
        .sidebar .nav-link .bi { font-size: 1.15rem; width: 24px; text-align: center; margin-right: 12px; color: #888; }
        .sidebar .nav-link:hover { background: #FFF8DC; color: #1a1a2e !important; transform: translateX(4px); }
        .sidebar .nav-link:hover .bi { color: #C7A600; }
        .sidebar .nav-link.active { background: linear-gradient(135deg, #FFD700, #E6C300) !important; color: #1a1a2e !important; font-weight: 700; box-shadow: 0 3px 12px rgba(255, 215, 0, 0.3); }
        .sidebar .nav-link.active .bi { color: #1a1a2e !important; }

        @media print {
            body, .main-container { display: block; }
            .sidebar, .navbar, .filter-form, .btn-print { display: none !important; }
            .content { padding: 0; }
            .table { font-size: 12px; }
            .table th, .table td { padding: 0.4rem; }
            .print-header { display: block !important; text-align: center; margin-bottom: 20px; }
            .kop-surat { display: flex; align-items: center; justify-content: center; border-bottom: 4px solid black; padding-bottom: 15px; margin-bottom: 15px; }
            .kop-logo { width: 90px; height: auto; margin-right: 25px; }
            .kop-text { text-align: center; line-height: 1.3; }
            .kop-text strong { font-size: 1.2em; }
        }
        .print-header { display: none; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container-fluid">
        <div class="d-flex align-items-center">
            <button class="btn me-2" id="sidebar-toggle"><i class="bi bi-list fs-5"></i></button>
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="../assets/img/logo_ulm.png?v=<?= filemtime('../assets/img/logo_ulm.png'); ?>" alt="Logo ULM" width="32" height="32" class="me-2">
                Admin Panel
            </a>
        </div>
        <ul class="navbar-nav ms-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['username']); ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item text-danger" href="../auth/logout.php">Logout</a></li>
                </ul>
            </li>
        </ul>
    </div>
</nav>

<div class="main-container">
    <!-- Sidebar -->
    <div class="sidebar p-3">
        <div class="mb-3 px-2">
            <span class="sidebar-heading"><span class="link-text">Menu Navigasi</span></span>
        </div>
        <ul class="nav flex-column" id="sidebar-nav">
            <li class="nav-item">
                <a class="nav-link" href="index.php"><i class="bi bi-list-task"></i> <span class="link-text">Daftar Pengajuan</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="mading.php"><i class="bi bi-megaphone-fill"></i> <span class="link-text">Kelola Mading</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="rekap.php"><i class="bi bi-file-earmark-bar-graph"></i> <span class="link-text">Rekap Laporan</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="statistik.php"><i class="bi bi-graph-up"></i> <span class="link-text">Statistik</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="backup_restore.php"><i class="bi bi-cloud-arrow-down"></i> <span class="link-text">Backup & Restore</span></a>
            </li>
        </ul>
    </div>

    <div class="content">
        <div class="d-flex justify-content-between align-items-center">
            <h3><i class="bi bi-file-earmark-bar-graph me-2"></i>Rekapitulasi Laporan</h3>
            <button class="btn btn-success btn-print" onclick="window.print()"><i class="bi bi-printer"></i> Cetak Laporan</button>
        </div>
        <hr>

        <!-- Filter Form -->
        <div class="card mb-4 filter-form">
            <div class="card-body">
                <form action="rekap.php" method="POST" class="row g-3 align-items-end">
                    <div class="col-md-4 col-sm-6">
                        <label for="tanggal_dari" class="form-label"><i class="bi bi-calendar-event me-1"></i>Dari Tanggal</label>
                        <input type="date" class="form-control" id="tanggal_dari" name="tanggal_dari" value="<?= htmlspecialchars($tanggal_dari); ?>" required>
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <label for="tanggal_sampai" class="form-label"><i class="bi bi-calendar-event me-1"></i>Sampai Tanggal</label>
                        <input type="date" class="form-control" id="tanggal_sampai" name="tanggal_sampai" value="<?= htmlspecialchars($tanggal_sampai); ?>" required>
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <button type="submit" class="btn btn-primary-custom w-100"><i class="bi bi-funnel me-1"></i>Filter</button>
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <a href="rekap.php" class="btn btn-outline-secondary w-100"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Header untuk Cetak -->
        <div class="print-header">
            <div class="kop-surat">
                <img src="../assets/img/logo_ulm.png" alt="Logo ULM" class="kop-logo">
                <div class="kop-text">
                    KEMENTERIAN PENDIDIKAN, KEBUDAYAAN, RISET, DAN TEKNOLOGI<br>
                    UNIVERSITAS LAMBUNG MANGKURAT<br>
                    <strong>FAKULTAS PERTANIAN</strong><br>
                    <small style="font-size: 0.9em;">Jalan Jenderal Ahmad Yani KM. 36, Banjarbaru, Kalimantan Selatan 70714</small><br>
                    <small style="font-size: 0.9em;">Telepon/Fax (0511) 4772212, Laman: faperta.ulm.ac.id</small>
                </div>
            </div>
            <h4>Rekapitulasi Pengajuan Surat</h4>
            <h5>Periode: <?= htmlspecialchars($periode_label); ?></h5>
        </div>

        <!-- Laporan Ringkasan -->
        <div class="row mb-4">
            <!-- Grafik Ringkasan per Status -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header card-header-custom d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-check2-circle me-2"></i>Ringkasan per Status</span>
                        <a href="cetak_rekap_pdf.php?report=status&tanggal_dari=<?= $tanggal_dari ?>&tanggal_sampai=<?= $tanggal_sampai ?>" target="_blank" class="btn btn-sm btn-outline-danger" title="Cetak PDF">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </a>
                    </div>
                    <div class="card-body d-flex justify-content-center align-items-center">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Grafik Ringkasan per Program Studi -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header card-header-custom d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-building me-2"></i>Ringkasan per Prodi</span>
                         <a href="cetak_rekap_pdf.php?report=prodi&tanggal_dari=<?= $tanggal_dari ?>&tanggal_sampai=<?= $tanggal_sampai ?>" target="_blank" class="btn btn-sm btn-outline-danger" title="Cetak PDF">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </a>
                    </div>
                    <div class="card-body">
                        <canvas id="prodiChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Grafik Ringkasan per Jenis Surat -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header card-header-custom d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-envelope-paper me-2"></i>Ringkasan per Jenis Surat</span>
                         <a href="cetak_rekap_pdf.php?report=jenis_surat&tanggal_dari=<?= $tanggal_dari ?>&tanggal_sampai=<?= $tanggal_sampai ?>" target="_blank" class="btn btn-sm btn-outline-danger" title="Cetak PDF">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </a>
                    </div>
                    <div class="card-body">
                        <canvas id="jenisSuratChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Grafik Ringkasan per Tanggal -->
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header card-header-custom d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-calendar-day me-2"></i>Ringkasan per Tanggal</span>
                        <a href="cetak_rekap_pdf.php?report=tanggal&tanggal_dari=<?= $tanggal_dari ?>&tanggal_sampai=<?= $tanggal_sampai ?>" target="_blank" class="btn btn-sm btn-outline-danger" title="Cetak PDF">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </a>
                    </div>
                    <div class="card-body">
                        <canvas id="tanggalChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Laporan Waktu Proses -->
            <div class="col-lg-8 col-md-12 mb-4">
                <div class="card h-100">
                    <div class="card-header card-header-custom d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-hourglass-split me-2"></i>Analisis Waktu Proses (Untuk Surat Selesai)</span>
                        <a href="cetak_rekap_pdf.php?report=waktu_proses&tanggal_dari=<?= $tanggal_dari ?>&tanggal_sampai=<?= $tanggal_sampai ?>" target="_blank" class="btn btn-sm btn-outline-danger" title="Cetak PDF">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if ($total_selesai > 0): ?>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Rata-rata Waktu Penyelesaian
                                    <span class="badge bg-primary rounded-pill"><?= format_durasi((int)($total_waktu_detik / $total_selesai)); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Waktu Tercepat
                                    <span class="badge bg-success rounded-pill"><?= format_durasi(min($waktu_proses)); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Waktu Terlama
                                    <span class="badge bg-danger rounded-pill"><?= format_durasi(max($waktu_proses)); ?></span>
                                </li>
                            </ul>
                        <?php else: ?>
                            <p class="text-center text-muted mt-3">Tidak ada surat yang diselesaikan pada periode ini.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Laporan Detail Pengajuan (Laporan Utama) -->
        <div class="card" id="laporan-detail">
            <div class="card-header card-header-custom d-flex justify-content-between align-items-center">
                <span>Laporan Detail: <?= htmlspecialchars($periode_label); ?></span>
                <div>
                    <span class="badge bg-dark me-2">Total: <?= count($rekap_surat); ?></span>
                    <a href="cetak_rekap_pdf.php?report=detail&tanggal_dari=<?= $tanggal_dari ?>&tanggal_sampai=<?= $tanggal_sampai ?>" target="_blank" class="btn btn-sm btn-danger" title="Cetak PDF"><i class="bi bi-file-earmark-pdf me-1"></i> Cetak Detail</a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Tgl Pengajuan</th>
                                <th>Nama / NIM</th>
                                <th>Program Studi</th>
                                <th>Jenis Surat</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rekap_surat)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">Tidak ada data pengajuan pada periode ini.</td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($rekap_surat as $surat): ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= date('d-m-Y, H:i', strtotime($surat['tanggal_pengajuan'])); ?></td>
                                    <td>
                                        <?= htmlspecialchars($surat['nama_lengkap']); ?><br>
                                        <small class="text-muted"><?= htmlspecialchars($surat['nim']); ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($surat['nama_prodi']); ?></td>
                                    <td><?= htmlspecialchars($surat['jenis_surat']); ?></td>
                                    <td><span class="badge badge-status-<?= strtolower($surat['status']); ?>"><?= htmlspecialchars($surat['status']); ?></span></td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <button class="btn btn-info btn-sm text-white" data-bs-toggle="modal" data-bs-target="#modalDetailRekap" onclick="showRekapDetail(<?= htmlspecialchars(json_encode($surat)); ?>)" title="Lihat Detail">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <?php if ($surat['status'] == 'Selesai'): ?>
                                            <a href="generate_pdf.php?id=<?= $surat['id']; ?>" target="_blank" class="btn btn-danger btn-sm" title="Cetak PDF">
                                                <i class="bi bi-file-earmark-pdf"></i>
                                            </a>
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
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Modal Detail Surat dari Rekap -->
<div class="modal fade" id="modalDetailRekap" tabindex="-1" aria-labelledby="modalDetailRekapLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #FFD700, #E6C300);">
                <h5 class="modal-title fw-bold" id="modalDetailRekapLabel"><i class="bi bi-file-text me-2"></i>Detail Surat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-2">
                    <div class="col-4 fw-bold text-muted">Jenis Surat</div>
                    <div class="col-8" id="rekap-jenis"></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 fw-bold text-muted">Nama Lengkap</div>
                    <div class="col-8" id="rekap-nama"></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 fw-bold text-muted">NIM</div>
                    <div class="col-8" id="rekap-nim"></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 fw-bold text-muted">Program Studi</div>
                    <div class="col-8" id="rekap-prodi"></div>
                </div>
                <div class="row mb-2" id="rekap-semester-row">
                    <div class="col-4 fw-bold text-muted">Semester</div>
                    <div class="col-8" id="rekap-semester"></div>
                </div>
                <div class="row mb-2" id="rekap-dosen-row">
                    <div class="col-4 fw-bold text-muted">Dosen Pembimbing</div>
                    <div class="col-8" id="rekap-dosen"></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 fw-bold text-muted">Keperluan</div>
                    <div class="col-8" id="rekap-keperluan"></div>
                </div>
                <div class="row mb-2" id="rekap-tanggal-row">
                    <div class="col-4 fw-bold text-muted" id="rekap-tanggal-label">Tanggal Kegiatan</div>
                    <div class="col-8" id="rekap-tanggal"></div>
                </div>
                <hr>
                <div class="row mb-2">
                    <div class="col-4 fw-bold text-muted">Tanggal Pengajuan</div>
                    <div class="col-8" id="rekap-tgl-ajuan"></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 fw-bold text-muted">Status</div>
                    <div class="col-8" id="rekap-status"></div>
                </div>
                <div class="row mb-2" id="rekap-catatan-row">
                    <div class="col-4 fw-bold text-muted">Catatan Admin</div>
                    <div class="col-8" id="rekap-catatan"></div>
                </div>
            </div>
            <div class="modal-footer">
                <span id="rekap-pdf-link"></span>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
function showRekapDetail(surat) {
    document.getElementById('rekap-jenis').textContent = surat.jenis_surat || '-';
    document.getElementById('rekap-nama').textContent = surat.nama_lengkap || '-';
    document.getElementById('rekap-nim').textContent = surat.nim || '-';
    document.getElementById('rekap-prodi').textContent = surat.nama_prodi || '-';
    document.getElementById('rekap-keperluan').textContent = surat.keperluan || '-';

    // Semester
    var semRow = document.getElementById('rekap-semester-row');
    if (surat.semester) {
        document.getElementById('rekap-semester').textContent = surat.semester;
        semRow.style.display = 'flex';
    } else { semRow.style.display = 'none'; }

    // Dosen
    var dosenRow = document.getElementById('rekap-dosen-row');
    if (surat.dosen_pembimbing) {
        document.getElementById('rekap-dosen').textContent = surat.dosen_pembimbing;
        dosenRow.style.display = 'flex';
    } else { dosenRow.style.display = 'none'; }

    // Tanggal kegiatan
    var tglRow = document.getElementById('rekap-tanggal-row');
    if (surat.tanggal_mulai) {
        var mulai = new Date(surat.tanggal_mulai).toLocaleDateString('id-ID', {day:'numeric',month:'long',year:'numeric'});
        var selesai = surat.tanggal_selesai ? new Date(surat.tanggal_selesai).toLocaleDateString('id-ID', {day:'numeric',month:'long',year:'numeric'}) : '-';
        document.getElementById('rekap-tanggal').textContent = mulai + ' s/d ' + selesai;
        tglRow.style.display = 'flex';
    } else { tglRow.style.display = 'none'; }

    // Tanggal pengajuan
    if (surat.tanggal_pengajuan) {
        document.getElementById('rekap-tgl-ajuan').textContent = new Date(surat.tanggal_pengajuan).toLocaleDateString('id-ID', {day:'numeric',month:'long',year:'numeric',hour:'2-digit',minute:'2-digit'});
    } else { document.getElementById('rekap-tgl-ajuan').textContent = '-'; }

    // Status
    var statusColors = {'Pending':'secondary','Diproses':'primary','Selesai':'success','Ditolak':'danger'};
    var badgeColor = statusColors[surat.status] || 'secondary';
    document.getElementById('rekap-status').innerHTML = '<span class="badge bg-' + badgeColor + '">' + surat.status + '</span>';

    // Catatan admin
    var catatanRow = document.getElementById('rekap-catatan-row');
    if (surat.catatan_admin) {
        document.getElementById('rekap-catatan').textContent = surat.catatan_admin;
        catatanRow.style.display = 'flex';
    } else { catatanRow.style.display = 'none'; }

    // PDF link
    var pdfLink = document.getElementById('rekap-pdf-link');
    if (surat.status === 'Selesai') {
        pdfLink.innerHTML = '<a href="generate_pdf.php?id=' + surat.id + '" target="_blank" class="btn btn-danger"><i class="bi bi-file-earmark-pdf me-1"></i> Cetak PDF</a>';
    } else { pdfLink.innerHTML = ''; }
}
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Konversi data PHP ke JavaScript
    const rekapStatus = <?= json_encode($rekap_status); ?>;
    const rekapProdi = <?= json_encode($rekap_prodi); ?>;
    const rekapJenisSurat = <?= json_encode($rekap_jenis_surat); ?>;
    const rekapPerTanggal = <?= json_encode($rekap_per_tanggal); ?>;

    // --- 1. Grafik Status (Doughnut Chart) ---
    const ctxStatus = document.getElementById('statusChart');
    if (Object.keys(rekapStatus).length > 0) {
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: Object.keys(rekapStatus),
                datasets: [{
                    label: 'Jumlah',
                    data: Object.values(rekapStatus),
                    backgroundColor: [
                        'rgba(255, 159, 64, 0.7)', // Oranye (Diproses)
                        'rgba(75, 192, 192, 0.7)', // Hijau (Selesai)
                        'rgba(255, 99, 132, 0.7)', // Merah (Ditolak)
                        'rgba(54, 162, 235, 0.7)'  // Biru (Lainnya)
                    ],
                    borderColor: [
                        'rgba(255, 159, 64, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: false
                    }
                }
            }
        });
    } else {
        ctxStatus.parentElement.innerHTML = '<p class="text-center text-muted m-auto">Tidak ada data.</p>';
    }

    // --- 2. Grafik Program Studi (Horizontal Bar Chart) ---
    const ctxProdi = document.getElementById('prodiChart');
    if (Object.keys(rekapProdi).length > 0) {
        new Chart(ctxProdi, {
            type: 'bar',
            data: {
                labels: Object.keys(rekapProdi),
                datasets: [{
                    label: 'Jumlah Pengajuan',
                    data: Object.values(rekapProdi),
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y', // Membuat bar menjadi horizontal
                responsive: true,
                plugins: {
                    legend: { display: false }
                }
            }
        });
    } else {
        ctxProdi.parentElement.innerHTML = '<p class="text-center text-muted m-auto">Tidak ada data.</p>';
    }

    // --- 3. Grafik Jenis Surat (Horizontal Bar Chart) ---
    const ctxJenisSurat = document.getElementById('jenisSuratChart');
    if (Object.keys(rekapJenisSurat).length > 0) {
        new Chart(ctxJenisSurat, {
            type: 'bar',
            data: {
                labels: Object.keys(rekapJenisSurat),
                datasets: [{
                    label: 'Jumlah Pengajuan',
                    data: Object.values(rekapJenisSurat),
                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: {
                    legend: { display: false }
                }
            }
        });
    } else {
        ctxJenisSurat.parentElement.innerHTML = '<p class="text-center text-muted m-auto">Tidak ada data.</p>';
    }

    // --- 4. Grafik per Tanggal (Line Chart) ---
    const ctxTanggal = document.getElementById('tanggalChart');
    if (Object.keys(rekapPerTanggal).length > 0) {
        new Chart(ctxTanggal, {
            type: 'line',
            data: {
                labels: Object.keys(rekapPerTanggal),
                datasets: [{
                    label: 'Jumlah Pengajuan per Hari',
                    data: Object.values(rekapPerTanggal),
                    fill: false,
                    borderColor: 'rgb(255, 99, 132)',
                    tension: 0.1
                }]
            }
        });
    } else {
        ctxTanggal.parentElement.innerHTML = '<p class="text-center text-muted m-auto">Tidak ada data.</p>';
    }
});
</script>
<script src="../assets/js/validations.js"></script>
</body>
</html>

<?php
session_start();
require '../config/database.php';
date_default_timezone_set('Asia/Makassar');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php?error=access_denied');
    exit();
}

// 1. Surat masuk per bulan (12 bulan terakhir)
$stmt = $pdo->prepare("
    SELECT DATE_FORMAT(tanggal_pengajuan, '%Y-%m') as bulan, COUNT(*) as total
    FROM surat WHERE tanggal_pengajuan >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY bulan ORDER BY bulan ASC
");
$stmt->execute();
$data_masuk = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Rata-rata waktu penyelesaian per bulan
$stmt = $pdo->prepare("
    SELECT DATE_FORMAT(tanggal_pengajuan, '%Y-%m') as bulan,
           AVG(TIMESTAMPDIFF(HOUR, tanggal_pengajuan, tanggal_penyelesaian)) as avg_jam
    FROM surat WHERE status = 'Selesai' AND tanggal_penyelesaian IS NOT NULL
    AND tanggal_pengajuan >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY bulan ORDER BY bulan ASC
");
$stmt->execute();
$data_waktu = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Jenis surat terbanyak
$stmt = $pdo->prepare("
    SELECT jenis_surat, COUNT(*) as total FROM surat
    WHERE tanggal_pengajuan >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY jenis_surat ORDER BY total DESC
");
$stmt->execute();
$data_jenis = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Ringkasan bulan ini
$bulan_ini = date('Y-m');
$stmt = $pdo->prepare("SELECT COUNT(*) FROM surat WHERE DATE_FORMAT(tanggal_pengajuan, '%Y-%m') = ?");
$stmt->execute([$bulan_ini]);
$total_bulan_ini = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT AVG(TIMESTAMPDIFF(HOUR, tanggal_pengajuan, tanggal_penyelesaian)) FROM surat WHERE status='Selesai' AND tanggal_penyelesaian IS NOT NULL AND DATE_FORMAT(tanggal_pengajuan,'%Y-%m')=?");
$stmt->execute([$bulan_ini]);
$avg_jam_bulan_ini = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT jenis_surat, COUNT(*) as total FROM surat WHERE DATE_FORMAT(tanggal_pengajuan,'%Y-%m')=? GROUP BY jenis_surat ORDER BY total DESC LIMIT 1");
$stmt->execute([$bulan_ini]);
$jenis_terbanyak = $stmt->fetch(PDO::FETCH_ASSOC);

// 5. Jenis surat per bulan (grouped)
$stmt = $pdo->prepare("
    SELECT DATE_FORMAT(tanggal_pengajuan, '%Y-%m') as bulan, jenis_surat, COUNT(*) as total
    FROM surat WHERE tanggal_pengajuan >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY bulan, jenis_surat ORDER BY bulan ASC, total DESC
");
$stmt->execute();
$data_jpb_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

$all_bulan = array_column($data_masuk, 'bulan');
$jenis_bulan_map = [];
foreach ($data_jpb_raw as $row) {
    $jenis_bulan_map[$row['jenis_surat']][$row['bulan']] = (int)$row['total'];
}

$nb = ['01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'Mei','06'=>'Jun','07'=>'Jul','08'=>'Agu','09'=>'Sep','10'=>'Okt','11'=>'Nov','12'=>'Des'];
$labels_bulan = [];
foreach ($all_bulan as $b) { $p = explode('-', $b); $labels_bulan[] = $nb[$p[1]] . ' ' . $p[0]; }
$waktu_labels = [];
foreach ($data_waktu as $r) { $p = explode('-', $r['bulan']); $waktu_labels[] = $nb[$p[1]] . ' ' . $p[0]; }

function format_jam($jam) {
    if ($jam === null || $jam === false) return '-';
    $jam = round($jam, 1);
    if ($jam < 1) return '< 1 jam';
    if ($jam < 24) return round($jam,1) . ' jam';
    return floor($jam/24) . ' hari ' . round($jam%24) . ' jam';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistik - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="../assets/css/responsive.css?v=<?= time(); ?>">
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
        .stat-card{border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.08);transition:transform .2s,box-shadow .2s}
        .stat-card:hover{transform:translateY(-4px);box-shadow:0 6px 20px rgba(0,0,0,.12)}
        .stat-icon{width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem}
        .stat-number{font-size:1.75rem;font-weight:700;line-height:1.2}
        .stat-label{font-size:.85rem;color:#6c757d}
        .bg-gp{background:linear-gradient(135deg,#667eea,#764ba2)}
        .bg-gs{background:linear-gradient(135deg,#11998e,#38ef7d)}
        .bg-gw{background:linear-gradient(135deg,#F2994A,#F2C94C)}
        .bg-gi{background:linear-gradient(135deg,#2193b0,#6dd5ed)}
        .chart-card{border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.08)}
        .chart-card .card-header{background:linear-gradient(135deg,#FFD700,#E6C300);border-radius:12px 12px 0 0!important;font-weight:600;color:#343a40;padding:1rem 1.25rem}
        .chart-card .card-body{padding:1.5rem}
        .period-badge{background:linear-gradient(135deg,#FFD700,#E6C300);color:#343a40;padding:6px 16px;border-radius:20px;font-weight:600;font-size:.85rem}
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
            <li class="nav-item"><a class="nav-link active" href="statistik.php"><i class="bi bi-graph-up"></i> <span class="link-text">Statistik</span></a></li>
            <li class="nav-item"><a class="nav-link" href="backup_restore.php"><i class="bi bi-cloud-arrow-down"></i> <span class="link-text">Backup & Restore</span></a></li>
        </ul>
    </div>

    <div class="content">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h3 class="mb-0"><i class="bi bi-graph-up me-2"></i>Statistik Pengajuan Surat</h3>
            <span class="period-badge"><i class="bi bi-calendar3 me-1"></i>12 Bulan Terakhir</span>
        </div>
        <hr>

        <!-- Summary Cards -->
        <div class="row mb-4 g-3">
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card h-100"><div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-gp text-white me-3"><i class="bi bi-envelope-paper-fill"></i></div>
                        <div><div class="stat-label">Surat Masuk Bulan Ini</div><div class="stat-number"><?= $total_bulan_ini; ?></div></div>
                    </div>
                </div></div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card h-100"><div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-gs text-white me-3"><i class="bi bi-hourglass-split"></i></div>
                        <div><div class="stat-label">Rata-rata Waktu Proses</div><div class="stat-number" style="font-size:1.15rem"><?= format_jam($avg_jam_bulan_ini); ?></div></div>
                    </div>
                </div></div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card h-100"><div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-gw text-white me-3"><i class="bi bi-trophy-fill"></i></div>
                        <div><div class="stat-label">Jenis Terbanyak Bulan Ini</div>
                            <div class="stat-number" style="font-size:.95rem;font-weight:600"><?= $jenis_terbanyak ? htmlspecialchars($jenis_terbanyak['jenis_surat']) : '-'; ?></div>
                            <?php if($jenis_terbanyak): ?><small class="text-muted"><?= $jenis_terbanyak['total']; ?> pengajuan</small><?php endif; ?>
                        </div>
                    </div>
                </div></div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card h-100"><div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-gi text-white me-3"><i class="bi bi-collection-fill"></i></div>
                        <div><div class="stat-label">Total Jenis Surat</div><div class="stat-number"><?= count($data_jenis); ?></div><small class="text-muted">kategori aktif</small></div>
                    </div>
                </div></div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="row mb-4 g-3">
            <div class="col-lg-7">
                <div class="card chart-card h-100">
                    <div class="card-header"><i class="bi bi-bar-chart-line me-2"></i>Jumlah Surat Masuk per Bulan</div>
                    <div class="card-body"><canvas id="chartSuratMasuk"></canvas></div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card chart-card h-100">
                    <div class="card-header"><i class="bi bi-clock-history me-2"></i>Rata-rata Waktu Penyelesaian (Jam)</div>
                    <div class="card-body"><canvas id="chartWaktuProses"></canvas></div>
                </div>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="row mb-4 g-3">
            <div class="col-lg-5">
                <div class="card chart-card h-100">
                    <div class="card-header"><i class="bi bi-pie-chart-fill me-2"></i>Distribusi Jenis Surat</div>
                    <div class="card-body d-flex justify-content-center align-items-center"><canvas id="chartJenisSurat"></canvas></div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card chart-card h-100">
                    <div class="card-header"><i class="bi bi-bar-chart-steps me-2"></i>Jenis Surat Terbanyak per Bulan</div>
                    <div class="card-body"><canvas id="chartJenisPerBulan"></canvas></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const lblB = <?= json_encode($labels_bulan); ?>;
    const dM = <?= json_encode(array_map('intval', array_column($data_masuk,'total'))); ?>;
    const wL = <?= json_encode($waktu_labels); ?>;
    const wV = <?= json_encode(array_map(function($r){return round((float)$r['avg_jam'],1);}, $data_waktu)); ?>;
    const jL = <?= json_encode(array_column($data_jenis,'jenis_surat')); ?>;
    const jV = <?= json_encode(array_map('intval', array_column($data_jenis,'total'))); ?>;
    const jMap = <?= json_encode($jenis_bulan_map); ?>;
    const aB = <?= json_encode($all_bulan); ?>;

    const dc = ['#667eea','#764ba2','#11998e','#38ef7d','#F2994A','#F2C94C','#2193b0','#6dd5ed','#ff6384','#9966ff','#ff9f40','#4bc0c0'];

    // 1. Surat Masuk
    const c1 = document.getElementById('chartSuratMasuk');
    if(dM.length>0){
        new Chart(c1,{type:'bar',data:{labels:lblB,datasets:[{label:'Jumlah Surat',data:dM,backgroundColor:dc.slice(0,dM.length),borderRadius:6,borderSkipped:false}]},options:{responsive:true,plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>c.parsed.y+' surat'}}},scales:{y:{beginAtZero:true,ticks:{stepSize:1,precision:0},grid:{color:'rgba(0,0,0,.05)'}},x:{grid:{display:false}}}}});
    } else { c1.parentElement.innerHTML='<p class="text-center text-muted m-auto">Tidak ada data.</p>'; }

    // 2. Waktu Proses
    const c2 = document.getElementById('chartWaktuProses');
    if(wV.length>0){
        new Chart(c2,{type:'line',data:{labels:wL,datasets:[{label:'Rata-rata (Jam)',data:wV,borderColor:'#11998e',backgroundColor:'rgba(17,153,142,.1)',borderWidth:3,fill:true,tension:.4,pointBackgroundColor:'#11998e',pointBorderColor:'#fff',pointBorderWidth:2,pointRadius:5,pointHoverRadius:7}]},options:{responsive:true,plugins:{legend:{display:false},tooltip:{callbacks:{label:function(c){const v=c.parsed.y;if(v<1)return'< 1 jam';if(v<24)return v+' jam';return Math.floor(v/24)+' hari '+Math.round(v%24)+' jam';}}}},scales:{y:{beginAtZero:true,title:{display:true,text:'Jam'},grid:{color:'rgba(0,0,0,.05)'}},x:{grid:{display:false}}}}});
    } else { c2.parentElement.innerHTML='<p class="text-center text-muted m-auto">Tidak ada data.</p>'; }

    // 3. Jenis Surat Doughnut
    const c3 = document.getElementById('chartJenisSurat');
    if(jL.length>0){
        new Chart(c3,{type:'doughnut',data:{labels:jL,datasets:[{data:jV,backgroundColor:dc.slice(0,jL.length),borderWidth:2,borderColor:'#fff',hoverOffset:8}]},options:{responsive:true,plugins:{legend:{position:'bottom',labels:{padding:12,usePointStyle:true,pointStyle:'circle',font:{size:11}}},tooltip:{callbacks:{label:function(c){const t=c.dataset.data.reduce((a,b)=>a+b,0);return c.label+': '+c.parsed+' ('+((c.parsed/t)*100).toFixed(1)+'%)';}}}}}}); 
    } else { c3.parentElement.innerHTML='<p class="text-center text-muted m-auto">Tidak ada data.</p>'; }

    // 4. Jenis per Bulan Stacked
    const c4 = document.getElementById('chartJenisPerBulan');
    if(Object.keys(jMap).length>0){
        const ds=[];let ci=0;
        for(const[j,bd] of Object.entries(jMap)){ds.push({label:j,data:aB.map(b=>bd[b]||0),backgroundColor:dc[ci%dc.length],borderRadius:4,borderSkipped:false});ci++;}
        new Chart(c4,{type:'bar',data:{labels:lblB,datasets:ds},options:{responsive:true,plugins:{legend:{position:'bottom',labels:{padding:10,usePointStyle:true,pointStyle:'rect',font:{size:10}}}},scales:{x:{stacked:true,grid:{display:false}},y:{stacked:true,beginAtZero:true,ticks:{stepSize:1,precision:0},grid:{color:'rgba(0,0,0,.05)'}}}}});
    } else { c4.parentElement.innerHTML='<p class="text-center text-muted m-auto">Tidak ada data.</p>'; }
});
</script>
<script src="../assets/js/validations.js"></script>
</body>
</html>

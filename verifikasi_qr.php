<?php
require 'config/database.php';

$surat = null;
$kegiatan = null;
$valid = false;
$id = null;
$token = null;
$type = 'surat';

// --- FORMAT BARU: URL Pendek dengan parameter type, id, t ---
if (isset($_GET['type']) && isset($_GET['id']) && isset($_GET['t'])) {
    $type = $_GET['type'];
    $id = $_GET['id'];
    $short_token = $_GET['t'];
    
    $secret_key = 'ULM_FAPERTA_2025_SECRET';
    
    if ($type == 'surat') {
        $stmt = $pdo->prepare("
            SELECT s.*, u.username as nim, p.nama_prodi
            FROM surat s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN program_studi p ON u.prodi_id = p.id
            WHERE s.id = ? AND s.status = 'Selesai'
        ");
        $stmt->execute([$id]);
        $surat = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($surat) {
            $expected_full_token = hash('sha256', $surat['id'] . $surat['nim'] . $surat['jenis_surat'] . $secret_key);
            $expected_short = substr($expected_full_token, 0, 16);
            if (hash_equals($expected_short, $short_token)) {
                $valid = true;
            }
        }
    } else if ($type == 'kegiatan') {
        $stmt = $pdo->prepare("
            SELECT k.*, u.username as nip, u.nama_lengkap as dosen_nama
            FROM kegiatan_akademik k
            JOIN users u ON k.dosen_id = u.id
            WHERE k.id = ? AND k.status = 'Selesai'
        ");
        $stmt->execute([$id]);
        $kegiatan = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($kegiatan) {
            $expected_full_token = hash('sha256', $kegiatan['id'] . $kegiatan['nip'] . 'Kegiatan_Akademik' . $secret_key);
            $expected_short = substr($expected_full_token, 0, 16);
            if (hash_equals($expected_short, $short_token)) {
                $valid = true;
                
                $stmt_mhs = $pdo->prepare("SELECT u.username as nim, u.nama_lengkap, p.nama_prodi FROM kegiatan_mahasiswa km JOIN users u ON km.mahasiswa_id = u.id LEFT JOIN program_studi p ON u.prodi_id = p.id WHERE km.kegiatan_id = ?");
                $stmt_mhs->execute([$id]);
                $kegiatan['mahasiswa_list'] = $stmt_mhs->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    } else if ($type == 'rekap') {
        $expected_full_token = hash('sha256', 'Rekap' . $id . $secret_key);
        $expected_short = substr($expected_full_token, 0, 16);
        if (hash_equals($expected_short, $short_token)) {
            $valid = true;
            $surat = [
                'jenis_surat' => 'Laporan Rekapitulasi Pengajuan Surat',
                'periode' => $id,
                'admin' => 'Admin',
                'tanggal_penyelesaian' => date('Y-m-d')
            ];
        }
    }
}
// --- FORMAT LAMA: Fallback ke dekripsi AES-256 (backward compatible) ---
else {
    $encrypted_base64 = $_GET['data'] ?? null;

    if ($encrypted_base64) {
        $aes_key = 'KUNCI_RAHASIA_SKRIPSI_FAPERTA_26';
        $aes_iv = '1234567890123456';

        $encrypted_data = base64_decode(urldecode($encrypted_base64));
        $decrypted_data = openssl_decrypt($encrypted_data, 'aes-256-cbc', $aes_key, 0, $aes_iv);
        
        if ($decrypted_data !== false) {
            $parsed_data = json_decode($decrypted_data, true);
            if ($parsed_data && isset($parsed_data['id']) && isset($parsed_data['token'])) {
                $id = $parsed_data['id'];
                $token = $parsed_data['token'];
                $type = $parsed_data['type'] ?? 'surat';
            }
        }
    }

    if ($id && $token) {
        $secret_key = 'ULM_FAPERTA_2025_SECRET';
        
        if ($type == 'surat') {
            $stmt = $pdo->prepare("
                SELECT s.*, u.username as nim, p.nama_prodi
                FROM surat s
                JOIN users u ON s.user_id = u.id
                LEFT JOIN program_studi p ON u.prodi_id = p.id
                WHERE s.id = ? AND s.status = 'Selesai'
            ");
            $stmt->execute([$id]);
            $surat = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($surat) {
                $expected_token = hash('sha256', $surat['id'] . $surat['nim'] . $surat['jenis_surat'] . $secret_key);
                if (hash_equals($expected_token, $token)) {
                    $valid = true;
                }
            }
        } else if ($type == 'kegiatan') {
            $stmt = $pdo->prepare("
                SELECT k.*, u.username as nip, u.nama_lengkap as dosen_nama
                FROM kegiatan_akademik k
                JOIN users u ON k.dosen_id = u.id
                WHERE k.id = ? AND k.status = 'Selesai'
            ");
            $stmt->execute([$id]);
            $kegiatan = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($kegiatan) {
                $expected_token = hash('sha256', $kegiatan['id'] . $kegiatan['nip'] . 'Kegiatan_Akademik' . $secret_key);
                if (hash_equals($expected_token, $token)) {
                    $valid = true;
                    
                    $stmt_mhs = $pdo->prepare("SELECT u.username as nim, u.nama_lengkap, p.nama_prodi FROM kegiatan_mahasiswa km JOIN users u ON km.mahasiswa_id = u.id LEFT JOIN program_studi p ON u.prodi_id = p.id WHERE km.kegiatan_id = ?");
                    $stmt_mhs->execute([$id]);
                    $kegiatan['mahasiswa_list'] = $stmt_mhs->fetchAll(PDO::FETCH_ASSOC);
                }
            }
        } else if ($type == 'rekap') {
            $expected_token = hash('sha256', 'Rekap' . $id . $secret_key);
            if (hash_equals($expected_token, $token)) {
                $valid = true;
                $surat = [
                    'jenis_surat' => 'Laporan Rekapitulasi Pengajuan Surat',
                    'periode' => $id,
                    'admin' => $parsed_data['admin'] ?? 'Admin',
                    'tanggal_penyelesaian' => date('Y-m-d')
                ];
            }
        }
    }
}

// Fungsi format tanggal Indonesia
function tgl_indo($tanggal) {
    if (empty($tanggal) || $tanggal === '0000-00-00') return '-';
    $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $pecah = explode('-', date('Y-m-d', strtotime($tanggal)));
    return $pecah[2] . ' ' . $bulan[(int)$pecah[1]] . ' ' . $pecah[0];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Surat - Fakultas Pertanian ULM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .verify-card {
            max-width: 520px;
            width: 100%;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideUp 0.5s ease-out;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .verify-header {
            padding: 2rem;
            text-align: center;
            color: white;
        }
        .verify-header.valid {
            background: linear-gradient(135deg, #00b894, #00cec9);
        }
        .verify-header.invalid {
            background: linear-gradient(135deg, #e17055, #d63031);
        }
        .verify-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
        }
        .verify-header h4 { margin: 0; font-weight: 700; }
        .verify-header p { margin: 0.5rem 0 0; opacity: 0.9; font-size: 0.9rem; }
        .verify-body {
            background: white;
            padding: 2rem;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label {
            font-weight: 600;
            color: #666;
            font-size: 0.85rem;
            flex-shrink: 0;
            margin-right: 1rem;
        }
        .info-value {
            font-weight: 500;
            color: #222;
            text-align: right;
            font-size: 0.9rem;
        }
        .verify-footer {
            background: #f8f9fa;
            padding: 1.25rem 2rem;
            text-align: center;
        }
        .verify-footer img { width: 40px; margin-bottom: 0.5rem; }
        .verify-footer p { margin: 0; font-size: 0.75rem; color: #999; }
        .badge-verified {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: #d4edda;
            color: #155724;
            border-radius: 50px;
            padding: 0.35rem 1rem;
            font-size: 0.8rem;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="verify-card">
    <?php if ($valid && $type == 'surat' && $surat): ?>
    <!-- SURAT TERVERIFIKASI -->
    <div class="verify-header valid">
        <div class="verify-icon">
            <i class="bi bi-shield-check"></i>
        </div>
        <h4>Surat Terverifikasi</h4>
        <p>Dokumen ini adalah surat resmi yang sah</p>
    </div>
    <div class="verify-body">
        <div class="text-center mb-3">
            <span class="badge-verified mb-2"><i class="bi bi-check-circle-fill"></i> Dokumen Asli & Sah</span>
            <br>
            <span class="badge bg-dark" style="font-size:0.75rem;"><i class="bi bi-lock-fill text-warning me-1"></i>Secured by AES-256 Cryptography</span>
        </div>

        <div class="info-row">
            <span class="info-label">Jenis Surat</span>
            <span class="info-value"><?= htmlspecialchars($surat['jenis_surat']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Nama Mahasiswa</span>
            <span class="info-value"><?= htmlspecialchars($surat['nama_lengkap']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">NIM</span>
            <span class="info-value"><?= htmlspecialchars($surat['nim']); ?></span>
        </div>
        <?php if (!empty($surat['nama_prodi'])): ?>
        <div class="info-row">
            <span class="info-label">Program Studi</span>
            <span class="info-value"><?= htmlspecialchars($surat['nama_prodi']); ?></span>
        </div>
        <?php endif; ?>
        <div class="info-row">
            <span class="info-label">Keperluan</span>
            <span class="info-value"><?= htmlspecialchars($surat['keperluan']); ?></span>
        </div>
        
        <?php if ($surat['jenis_surat'] == 'Surat Izin Tidak Masuk Kuliah' || $surat['jenis_surat'] == 'Surat Izin Penelitian' || $surat['jenis_surat'] == 'Surat Pengantar PKL/Magang'): ?>
            <div class="info-row">
                <span class="info-label">Masa Berlaku</span>
                <span class="info-value">
                    <?= tgl_indo($surat['tanggal_mulai']) ?> s.d <?= tgl_indo($surat['tanggal_selesai']) ?>
                </span>
            </div>
        <?php endif; ?>

        <div class="info-row">
            <span class="info-label">Disahkan Tanggal</span>
            <span class="info-value"><?= tgl_indo($surat['tanggal_penyelesaian']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Oleh</span>
            <span class="info-value">Dekan Fakultas Pertanian ULM</span>
        </div>
        <?php if (!empty($surat['nomor_surat'])): ?>
        <div class="info-row">
            <span class="info-label">Nomor Surat</span>
            <span class="info-value"><?= htmlspecialchars($surat['nomor_surat']); ?></span>
        </div>
        <?php endif; ?>
        <div class="info-row">
            <span class="info-label">Status</span>
            <span class="info-value"><span class="badge bg-success">Selesai</span></span>
        </div>

        <hr>
        <h6 class="fw-bold text-muted mb-2" style="font-size:0.85rem">Ditandatangani oleh:</h6>
        <div class="info-row">
            <span class="info-label">Nama</span>
            <span class="info-value" style="font-size:0.8rem">Prof. Ir. Akhmad Rizali Saidy, S.P., M.Ag.Sc., Ph.D., IPM.</span>
        </div>
        <div class="info-row">
            <span class="info-label">Jabatan</span>
            <span class="info-value">Dekan Fakultas Pertanian</span>
        </div>
        <div class="info-row">
            <span class="info-label">NIP</span>
            <span class="info-value">19700609 199512 1 001</span>
        </div>
    </div>
    <div class="verify-footer">
        <img src="assets/img/logo_ulm.png" alt="Logo ULM">
        <p>Fakultas Pertanian — Universitas Lambung Mangkurat<br>Sistem Verifikasi Surat Digital</p>
    </div>

    <?php elseif ($valid && $type == 'kegiatan' && $kegiatan): ?>
    <!-- KEGIATAN TERVERIFIKASI -->
    <div class="verify-header valid">
        <div class="verify-icon">
            <i class="bi bi-shield-check"></i>
        </div>
        <h4>Kegiatan Akademik Terverifikasi</h4>
        <p>Dokumen ini adalah surat resmi yang sah</p>
    </div>
    <div class="verify-body">
        <div class="text-center mb-3">
            <span class="badge-verified mb-2"><i class="bi bi-check-circle-fill"></i> Dokumen Asli & Sah</span>
            <br>
            <span class="badge bg-dark" style="font-size:0.75rem;"><i class="bi bi-lock-fill text-warning me-1"></i>Secured by AES-256 Cryptography</span>
        </div>

        <div class="info-row">
            <span class="info-label">Nomor Surat</span>
            <span class="info-value"><?= htmlspecialchars($kegiatan['nomor_surat']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Nama Kegiatan</span>
            <span class="info-value"><?= htmlspecialchars($kegiatan['nama_kegiatan']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Jenis Kegiatan</span>
            <span class="info-value"><?= htmlspecialchars($kegiatan['jenis_kegiatan']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Dosen Pengaju</span>
            <span class="info-value"><?= htmlspecialchars($kegiatan['dosen_nama']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Tanggal Pelaksanaan</span>
            <span class="info-value"><?= tgl_indo($kegiatan['tanggal_kegiatan']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Lokasi</span>
            <span class="info-value"><?= htmlspecialchars($kegiatan['lokasi']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Mahasiswa Terlibat</span>
            <span class="info-value"><?= count($kegiatan['mahasiswa_list']) ?> Orang</span>
        </div>
        <div class="info-row">
            <span class="info-label">Disahkan Tanggal</span>
            <span class="info-value"><?= tgl_indo($kegiatan['updated_at']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Status</span>
            <span class="info-value"><span class="badge bg-success">Selesai</span></span>
        </div>

        <hr>
        <h6 class="fw-bold text-muted mb-2" style="font-size:0.85rem">Ditandatangani oleh:</h6>
        <div class="info-row">
            <span class="info-label">Nama</span>
            <span class="info-value" style="font-size:0.8rem">Prof. Ir. Akhmad Rizali Saidy, S.P., M.Ag.Sc., Ph.D., IPM.</span>
        </div>
        <div class="info-row">
            <span class="info-label">Jabatan</span>
            <span class="info-value">Dekan Fakultas Pertanian</span>
        </div>
    </div>
    <div class="verify-footer">
        <img src="assets/img/logo_ulm.png" alt="Logo ULM">
        <p>Fakultas Pertanian — Universitas Lambung Mangkurat<br>Sistem Verifikasi Surat Digital</p>
    </div>

    <?php elseif ($valid && $type == 'rekap' && $surat): ?>
    <!-- REKAP TERVERIFIKASI -->
    <div class="verify-header valid">
        <div class="verify-icon">
            <i class="bi bi-shield-check"></i>
        </div>
        <h4>Laporan Terverifikasi</h4>
        <p>Dokumen Laporan Rekapitulasi Resmi</p>
    </div>
    <div class="verify-body">
        <div class="text-center mb-3">
            <span class="badge-verified mb-2"><i class="bi bi-check-circle-fill"></i> Laporan Asli & Sah</span>
            <br>
            <span class="badge bg-dark" style="font-size:0.75rem;"><i class="bi bi-lock-fill text-warning me-1"></i>Secured by AES-256 Cryptography</span>
        </div>

        <div class="info-row">
            <span class="info-label">Jenis Dokumen</span>
            <span class="info-value"><?= htmlspecialchars($surat['jenis_surat']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Periode</span>
            <span class="info-value"><?= htmlspecialchars($surat['periode']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Dicetak Oleh</span>
            <span class="info-value"><?= htmlspecialchars($surat['admin']); ?></span>
        </div>
        <hr>
        <h6 class="fw-bold text-muted mb-2" style="font-size:0.85rem">Ditandatangani oleh:</h6>
        <div class="info-row">
            <span class="info-label">Nama</span>
            <span class="info-value" style="font-size:0.8rem">Prof. Ir. Akhmad Rizali Saidy, S.P., M.Ag.Sc., Ph.D., IPM.</span>
        </div>
        <div class="info-row">
            <span class="info-label">Jabatan</span>
            <span class="info-value">Dekan Fakultas Pertanian</span>
        </div>
    </div>
    <div class="verify-footer">
        <img src="assets/img/logo_ulm.png" alt="Logo ULM">
        <p>Fakultas Pertanian — Universitas Lambung Mangkurat<br>Sistem Verifikasi Surat Digital</p>
    </div>

    <?php else: ?>
    <!-- SURAT TIDAK VALID -->
    <div class="verify-header invalid">
        <div class="verify-icon">
            <i class="bi bi-shield-x"></i>
        </div>
        <h4>Verifikasi Gagal</h4>
        <p>Dokumen tidak dapat diverifikasi</p>
    </div>
    <div class="verify-body text-center">
        <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size:3rem"></i>
        <h5 class="mt-3 fw-bold">Surat Tidak Ditemukan</h5>
        <p class="text-muted">QR Code ini tidak cocok dengan surat manapun di sistem kami. Kemungkinan dokumen ini <strong>tidak sah</strong> atau telah dipalsukan.</p>
        <hr>
        <p class="text-muted" style="font-size:0.85rem">Jika Anda yakin ini adalah surat resmi, silakan hubungi bagian akademik Fakultas Pertanian ULM.</p>
    </div>
    <div class="verify-footer">
        <img src="assets/img/logo_ulm.png" alt="Logo ULM">
        <p>Fakultas Pertanian — Universitas Lambung Mangkurat<br>Sistem Verifikasi Surat Digital</p>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/validations.js"></script>
</body>
</html>

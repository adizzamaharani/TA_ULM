<?php
session_start();
require '../config/database.php';
require '../libs/fpdf/fpdf.php';

// Set zona waktu default ke Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');

// Header untuk mencegah caching browser (agar logo baru langsung muncul)
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Proteksi halaman: hanya admin yang bisa akses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php?error=access_denied');
    exit();
}

// Ambil parameter dari URL
$jenis_laporan = $_GET['report'] ?? 'detail';
$tanggal_dari = $_GET['tanggal_dari'] ?? date('Y-m-01');
$tanggal_sampai = $_GET['tanggal_sampai'] ?? date('Y-m-d');

// Ambil data rekap berdasarkan filter
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

function format_tanggal_indo($tgl) {
    $nama_bulan_arr = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];
    $parts = explode('-', $tgl);
    if (count($parts) === 3) {
        return $parts[2] . ' ' . ($nama_bulan_arr[$parts[1]] ?? $parts[1]) . ' ' . $parts[0];
    }
    return $tgl;
}

$periode_teks = "Periode: " . format_tanggal_indo($tanggal_dari) . " s/d " . format_tanggal_indo($tanggal_sampai);

// --- Custom PDF Class dengan Header & Footer ---
class PDF extends FPDF
{
    private $periode;
    private $judul;

    function setReportDetails($judul, $periode) {
        $this->judul = $judul;
        $this->periode = $periode;
    }

    // Page header
    function Header()
    {
        // Logo
        $this->Image('../assets/img/logo_ulm.png', 10, 8, 25);
        
        // Mengatur posisi X agar teks tidak menimpa logo
        $this->SetX(40);
        
        // Judul Kop
        $this->SetFont('Times', 'B', 14);
        $this->Cell(0, 7, 'KEMENTERIAN PENDIDIKAN, KEBUDAYAAN,', 0, 1, 'C');
        $this->SetX(40);
        $this->Cell(0, 7, 'RISET, DAN TEKNOLOGI', 0, 1, 'C');
        $this->SetFont('Times', 'B', 16);
        $this->SetX(40);
        $this->Cell(0, 7, 'UNIVERSITAS LAMBUNG MANGKURAT', 0, 1, 'C');
        $this->SetFont('Times', 'B', 18);
        $this->SetX(40);
        $this->Cell(0, 7, 'FAKULTAS PERTANIAN', 0, 1, 'C');
        
        // Alamat
        $this->SetFont('Times', '', 10);
        $this->SetX(40);
        $this->Cell(0, 5, 'Jalan Jenderal Achmad Yani KM. 36, Banjarbaru, Kalimantan Selatan 70714', 0, 1, 'C');
        $this->SetX(40);
        $this->Cell(0, 5, 'Telepon (0511) 4772212, Faksimile (0511) 4781589', 0, 1, 'C');
        
        // Garis Bawah Kop Surat
        $y_pos = $this->GetY();
        $this->SetLineWidth(1);
        $this->Line(10, $y_pos + 2, $this->GetPageWidth() - 10, $y_pos + 2);
        $this->SetLineWidth(0.2);
        $this->Line(10, $y_pos + 3, $this->GetPageWidth() - 10, $y_pos + 3);
        
        $this->Ln(10);

        // Judul Laporan
        $this->SetFont('Times', 'B', 12);
        $this->Cell(0, 7, strtoupper($this->judul), 0, 1, 'C');
        $this->SetFont('Times', '', 10);
        $this->Cell(0, 5, $this->periode, 0, 1, 'C');
        $this->Ln(5);
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

    // Page footer
    function Footer()
    {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Times', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Halaman ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

// --- Logika Pembuatan PDF ---
$pdf = new PDF();
$pdf->AliasNbPages();

$judul_laporan = '';
$nama_file = 'laporan_rekap_';

switch ($jenis_laporan) {
    case 'status':
        $judul_laporan = 'Rekapitulasi Laporan per Status';
        $nama_file .= 'status_';
        $pdf->setReportDetails($judul_laporan, $periode_teks);
        $pdf->AddPage();
        $pdf->SetFont('Times', 'B', 10);
        $pdf->Cell(130, 7, 'Status', 1);
        $pdf->Cell(60, 7, 'Jumlah', 1, 1, 'C');
        $pdf->SetFont('Times', '', 10);
        $rekap_status = [];
        foreach ($rekap_surat as $s) { $rekap_status[$s['status']] = ($rekap_status[$s['status']] ?? 0) + 1; }
        foreach ($rekap_status as $status => $jumlah) {
            $pdf->Cell(130, 7, $status, 1);
            $pdf->Cell(60, 7, $jumlah, 1, 1, 'C');
        }
        break;

    case 'prodi':
        $judul_laporan = 'Rekapitulasi Laporan per Program Studi';
        $nama_file .= 'prodi_';
        $pdf->setReportDetails($judul_laporan, $periode_teks);
        $pdf->AddPage();
        $pdf->SetFont('Times', 'B', 10);
        $pdf->Cell(130, 7, 'Program Studi', 1);
        $pdf->Cell(60, 7, 'Jumlah', 1, 1, 'C');
        $pdf->SetFont('Times', '', 10);
        $rekap_prodi = [];
        foreach ($rekap_surat as $s) { $prodi = $s['nama_prodi'] ?? 'Tidak Terdaftar'; $rekap_prodi[$prodi] = ($rekap_prodi[$prodi] ?? 0) + 1; }
        foreach ($rekap_prodi as $prodi => $jumlah) {
            $pdf->Cell(130, 7, $prodi, 1);
            $pdf->Cell(60, 7, $jumlah, 1, 1, 'C');
        }
        break;

    case 'jenis_surat':
        $judul_laporan = 'Rekapitulasi Laporan per Jenis Surat';
        $nama_file .= 'jenis_surat_';
        $pdf->setReportDetails($judul_laporan, $periode_teks);
        $pdf->AddPage();
        $pdf->SetFont('Times', 'B', 10);
        $pdf->Cell(130, 7, 'Jenis Surat', 1);
        $pdf->Cell(60, 7, 'Jumlah', 1, 1, 'C');
        $pdf->SetFont('Times', '', 10);
        $rekap_jenis_surat = [];
        foreach ($rekap_surat as $s) { $rekap_jenis_surat[$s['jenis_surat']] = ($rekap_jenis_surat[$s['jenis_surat']] ?? 0) + 1; }
        foreach ($rekap_jenis_surat as $jenis => $jumlah) {
            $pdf->Cell(130, 7, $jenis, 1);
            $pdf->Cell(60, 7, $jumlah, 1, 1, 'C');
        }
        break;

    case 'tanggal':
        $judul_laporan = 'Rekapitulasi Laporan per Tanggal';
        $nama_file .= 'tanggal_';
        $pdf->setReportDetails($judul_laporan, $periode_teks);
        $pdf->AddPage();
        $pdf->SetFont('Times', 'B', 10);
        $pdf->Cell(130, 7, 'Tanggal Pengajuan', 1);
        $pdf->Cell(60, 7, 'Jumlah', 1, 1, 'C');
        $pdf->SetFont('Times', '', 10);
        $rekap_per_tanggal = [];
        foreach ($rekap_surat as $s) { 
            $tanggal = date('d-m-Y', strtotime($s['tanggal_pengajuan']));
            $rekap_per_tanggal[$tanggal] = ($rekap_per_tanggal[$tanggal] ?? 0) + 1;
        }
        ksort($rekap_per_tanggal);
        foreach ($rekap_per_tanggal as $tanggal => $jumlah) {
            $pdf->Cell(130, 7, $tanggal, 1);
            $pdf->Cell(60, 7, $jumlah, 1, 1, 'C');
        }
        break;

    case 'waktu_proses':
        $judul_laporan = 'Analisis Waktu Proses Penyelesaian Surat';
        $nama_file .= 'waktu_proses_';
        $pdf->setReportDetails($judul_laporan, $periode_teks);
        $pdf->AddPage();

        $waktu_proses = [];
        $total_selesai = 0;
        $total_waktu_detik = 0;

        foreach ($rekap_surat as $surat) {
            if ($surat['status'] == 'Selesai' && !empty($surat['tanggal_penyelesaian'])) {
                $tgl_aju = new DateTime($surat['tanggal_pengajuan']);
                $tgl_selesai = new DateTime($surat['tanggal_penyelesaian']);
                $selisih = $tgl_selesai->getTimestamp() - $tgl_aju->getTimestamp();
                $waktu_proses[] = $selisih;
                $total_selesai++;
                $total_waktu_detik += $selisih;
            }
        }

        if ($total_selesai > 0) {
            $pdf->SetFont('Times', 'B', 10);
            $pdf->Cell(130, 7, 'Metrik', 1);
            $pdf->Cell(60, 7, 'Durasi', 1, 1, 'C');
            $pdf->SetFont('Times', '', 10);
            $pdf->Cell(130, 7, 'Rata-rata Waktu Penyelesaian', 1);
            $pdf->Cell(60, 7, $pdf->format_durasi($total_waktu_detik / $total_selesai), 1, 1, 'C');
            $pdf->Cell(130, 7, 'Waktu Tercepat', 1);
            $pdf->Cell(60, 7, $pdf->format_durasi(min($waktu_proses)), 1, 1, 'C');
            $pdf->Cell(130, 7, 'Waktu Terlama', 1);
            $pdf->Cell(60, 7, $pdf->format_durasi(max($waktu_proses)), 1, 1, 'C');
        } else {
            $pdf->Cell(190, 10, 'Tidak ada surat yang diselesaikan pada periode ini.', 1, 1, 'C');
        }
        break;

    case 'detail':
    default:
        $judul_laporan = 'Laporan Detail Pengajuan Surat';
        $nama_file .= 'detail_';
        $pdf->setReportDetails($judul_laporan, $periode_teks);
        $pdf->AddPage('L'); // Landscape
        $pdf->SetFont('Times', 'B', 9);
        // Header Tabel
        $pdf->Cell(10, 7, 'No', 1, 0, 'C');
        $pdf->Cell(35, 7, 'Tgl Pengajuan', 1, 0, 'C');
        $pdf->Cell(60, 7, 'Nama / NIM', 1, 0, 'C');
        $pdf->Cell(70, 7, 'Program Studi', 1, 0, 'C');
        $pdf->Cell(65, 7, 'Jenis Surat', 1, 0, 'C');
        $pdf->Cell(30, 7, 'Status', 1, 1, 'C');
        
        $pdf->SetFont('Times', '', 8);
        if (empty($rekap_surat)) {
            $pdf->Cell(270, 10, 'Tidak ada data pengajuan pada periode ini.', 1, 1, 'C');
        } else {
            $no = 1;
            foreach ($rekap_surat as $surat) {
                $pdf->Cell(10, 10, $no++, 1, 0, 'C');
                $pdf->Cell(35, 10, date('d-m-Y, H:i', strtotime($surat['tanggal_pengajuan'])), 1, 0);
                
                // MultiCell untuk Nama dan NIM
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $pdf->MultiCell(60, 5, htmlspecialchars_decode($surat['nama_lengkap']) . "\n" . htmlspecialchars_decode($surat['nim']), 1, 'L');
                $pdf->SetXY($x + 60, $y);

                $pdf->Cell(70, 10, htmlspecialchars_decode($surat['nama_prodi']), 1, 0);
                $pdf->Cell(65, 10, htmlspecialchars_decode($surat['jenis_surat']), 1, 0);
                $pdf->Cell(30, 10, htmlspecialchars_decode($surat['status']), 1, 1, 'C');
            }
        }
        break;
}

// --- Tanda Tangan Dekan ---
$pdf->Ln(10);
$x_pos = $pdf->GetPageWidth() - 90; // Posisi tanda tangan menyesuaikan lebar halaman
$pdf->SetX($x_pos);
$pdf->SetFont('Times', '', 12);
$pdf->Cell(0, 7, 'Banjarbaru, ' . date('d') . ' ' . ($nama_bulan[date('m')] ?? date('F')) . ' ' . date('Y'), 0, 1, 'L');
$pdf->SetX($x_pos);
$pdf->Cell(0, 7, 'Dekan,', 0, 1, 'L');

// TTD Digital
$path_ttd = '../assets/img/ttd_dekan.png'; 
if (file_exists($path_ttd)) {
    $pdf->Image($path_ttd, $x_pos + 5, $pdf->GetY() + 1, 25);
}
$pdf->Ln(25);

$pdf->SetX($x_pos);
$pdf->SetFont('Times', 'BU', 11);
$pdf->Cell(0, 7, 'Prof. Ir. Akhmad Rizali Saidy, S.P., M.Ag.Sc., Ph.D., IPM.', 0, 1, 'L');
$pdf->SetX($x_pos);
$pdf->SetFont('Times', '', 12);
$pdf->Cell(0, 7, 'NIP. 19700609 199512 1 001', 0, 1, 'L');

$nama_file .= $tanggal_dari . '_to_' . $tanggal_sampai . '.pdf';
$pdf->Output('D', $nama_file);
exit;
?>
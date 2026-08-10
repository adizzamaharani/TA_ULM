<?php
ob_start();
session_start();
require 'config/database.php';
require 'libs/fpdf/fpdf.php';
// Suppress PHP 8 deprecation warnings dari library phpqrcode
$old_error_reporting = error_reporting(E_ALL & ~E_DEPRECATED);
require 'libs/phpqrcode/qrlib.php';
error_reporting($old_error_reporting);

$kegiatan_id = $_GET['id'] ?? null;
if (!$kegiatan_id) {
    die('ID Kegiatan tidak ditemukan.');
}

// Ambil data kegiatan dari database
$stmt = $pdo->prepare("
    SELECT k.*, u.nama_lengkap as dosen_nama, u.username as nip
    FROM kegiatan_akademik k
    JOIN users u ON k.dosen_id = u.id
    WHERE k.id = ? AND k.status = 'Selesai'
");
$stmt->execute([$kegiatan_id]);
$kegiatan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$kegiatan) {
    die('Kegiatan tidak ditemukan atau belum berstatus "Selesai".');
}

// Proteksi halaman
if (!isset($_SESSION['user_id'])) {
    die('Akses ditolak. Anda harus login untuk melihat surat ini.');
}

class PDF extends FPDF
{
    // Kop Surat
    function Header()
    {
        $this->Image('assets/img/logo_ulm.png', 15, 10, 22);
        $this->SetX(40);
        $this->SetFont('Times', 'B', 12);
        $this->Cell(155, 6, 'KEMENTERIAN PENDIDIKAN TINGGI,', 0, 1, 'C');
        $this->SetX(40);
        $this->Cell(155, 6, 'SAINS DAN TEKNOLOGI', 0, 1, 'C');
        $this->SetX(40);
        $this->SetFont('Times', '', 12);
        $this->Cell(155, 6, 'UNIVERSITAS LAMBUNG MANGKURAT', 0, 1, 'C');
        $this->SetX(40);
        $this->SetFont('Times', 'B', 14);
        $this->Cell(155, 7, 'FAKULTAS PERTANIAN', 0, 1, 'C');
        $this->SetX(40);
        $this->SetFont('Times', '', 9);
        $this->Cell(155, 4.5, 'Jalan Jendral Ahmad Yani Km. 36 Kotak Pos 1028 Banjarbaru 70714', 0, 1, 'C');
        $this->SetX(40);
        $this->Cell(155, 4.5, 'Telp/Fax (0511) 4772254', 0, 1, 'C');
        $this->SetX(40);
        $this->SetFont('Times', 'B', 9);
        $this->Cell(155, 4.5, 'Email: faperta@ulm.ac.id, Website: www.faperta.ulm.ac.id', 0, 1, 'C');
        $y_pos = $this->GetY();
        $this->SetLineWidth(0.8);
        $this->Line(10, $y_pos + 2, 200, $y_pos + 2);
        $this->SetLineWidth(0.2);
        $this->Line(10, $y_pos + 3.5, 200, $y_pos + 3.5);
        $this->Ln(15);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Halaman ' . $this->PageNo(), 0, 0, 'C');
    }
}

$pdf = new PDF('P', 'mm', 'A4');
$pdf->SetMargins(25, 10, 25);
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 25);

function tanggal_indonesia($tanggal) {
    if (empty($tanggal) || $tanggal === '0000-00-00') return '-';
    $bulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $pecahkan = explode('-', date('Y-m-d', strtotime($tanggal)));    
    return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
}

$pdf->SetFont('Times', 'B', 14);
$pdf->Cell(0, 6, 'SURAT KETERANGAN KEGIATAN AKADEMIK', 0, 1, 'C');

$pdf->SetFont('Times', '', 12);
$pdf->Cell(0, 6, 'Nomor : ' . $kegiatan['nomor_surat'], 0, 1, 'C');
$pdf->Ln(10);

$pdf->MultiCell(0, 7, 'Dekan Fakultas Pertanian Universitas Lambung Mangkurat dengan ini menerangkan bahwa:', 0, 'J');
$pdf->Ln(3);

$pdf->SetFont('Times', 'B', 12);
$pdf->Cell(40, 7, 'Nama', 0, 0);
$pdf->Cell(5, 7, ':', 0, 0);
$pdf->SetFont('Times', '', 12);
$pdf->Cell(0, 7, $kegiatan['dosen_nama'], 0, 1);

$pdf->SetFont('Times', 'B', 12);
$pdf->Cell(40, 7, 'NIP/NIDN', 0, 0);
$pdf->Cell(5, 7, ':', 0, 0);
$pdf->SetFont('Times', '', 12);
$pdf->Cell(0, 7, $kegiatan['nip'], 0, 1);
$pdf->Ln(5);

$pdf->MultiCell(0, 7, 'Benar telah/akan melaksanakan kegiatan akademik dengan rincian sebagai berikut:', 0, 'J');
$pdf->Ln(3);

$pdf->SetX(25);
$pdf->Cell(40, 7, 'Nama Kegiatan', 0, 0);
$pdf->Cell(5, 7, ':', 0, 0);
$x = $pdf->GetX();
$pdf->SetLeftMargin($x);
$pdf->MultiCell(0, 7, $kegiatan['nama_kegiatan'], 0, 'J');
$pdf->SetLeftMargin(25);

$pdf->SetX(25);
$pdf->Cell(40, 7, 'Jenis Kegiatan', 0, 0);
$pdf->Cell(5, 7, ':', 0, 0);
$x = $pdf->GetX();
$pdf->SetLeftMargin($x);
$pdf->MultiCell(0, 7, $kegiatan['jenis_kegiatan'], 0, 'J');
$pdf->SetLeftMargin(25);

$pdf->SetX(25);
$pdf->Cell(40, 7, 'Tanggal', 0, 0);
$pdf->Cell(5, 7, ':', 0, 0);
$x = $pdf->GetX();
$pdf->SetLeftMargin($x);
$pdf->MultiCell(0, 7, tanggal_indonesia($kegiatan['tanggal_kegiatan']), 0, 'J');
$pdf->SetLeftMargin(25);

$pdf->SetX(25);
$pdf->Cell(40, 7, 'Lokasi', 0, 0);
$pdf->Cell(5, 7, ':', 0, 0);
$x = $pdf->GetX();
$pdf->SetLeftMargin($x);
$pdf->MultiCell(0, 7, $kegiatan['lokasi'], 0, 'J');
$pdf->SetLeftMargin(25);

$pdf->SetX(25);
$pdf->Cell(40, 7, 'Deskripsi', 0, 0);
$pdf->Cell(5, 7, ':', 0, 0);
$x = $pdf->GetX();
$pdf->SetLeftMargin($x);
$pdf->MultiCell(0, 7, $kegiatan['deskripsi'], 0, 'J');
$pdf->SetLeftMargin(25);
$pdf->Ln(5);

// Tabel Mahasiswa
$stmt_mhs = $pdo->prepare("SELECT u.username as nim, u.nama_lengkap, p.nama_prodi FROM kegiatan_mahasiswa km JOIN users u ON km.mahasiswa_id = u.id LEFT JOIN program_studi p ON u.prodi_id = p.id WHERE km.kegiatan_id = ?");
$stmt_mhs->execute([$kegiatan_id]);
$mahasiswa_list = $stmt_mhs->fetchAll(PDO::FETCH_ASSOC);

if (count($mahasiswa_list) > 0) {
    $pdf->MultiCell(0, 7, 'Kegiatan tersebut melibatkan mahasiswa sebanyak ' . count($mahasiswa_list) . ' orang, yaitu:', 0, 'J');
    $pdf->Ln(2);

    $pdf->SetFont('Times', 'B', 10);
    $pdf->Cell(10, 7, 'No', 1, 0, 'C');
    $pdf->Cell(40, 7, 'NIM', 1, 0, 'C');
    $pdf->Cell(65, 7, 'Nama Mahasiswa', 1, 0, 'C');
    $pdf->Cell(45, 7, 'Program Studi', 1, 1, 'C');
    
    $pdf->SetFont('Times', '', 10);
    $no = 1;
    foreach ($mahasiswa_list as $mhs) {
        $pdf->Cell(10, 7, $no++, 1, 0, 'C');
        $pdf->Cell(40, 7, $mhs['nim'], 1, 0, 'C');
        $pdf->Cell(65, 7, $mhs['nama_lengkap'], 1, 0, 'L');
        $pdf->Cell(45, 7, $mhs['nama_prodi'] ?? '-', 1, 1, 'L');
    }
}
$pdf->Ln(10);
$pdf->SetFont('Times', '', 12);
$pdf->MultiCell(0, 7, 'Demikian surat keterangan ini dibuat agar dapat dipergunakan sebagaimana mestinya.', 0, 'J');
$pdf->Ln(15);

// Tanda Tangan Dekan
$dekan_stmt = $pdo->query("SELECT nama_lengkap, username FROM users WHERE role = 'dekan' LIMIT 1");
$dekan = $dekan_stmt->fetch(PDO::FETCH_ASSOC);
$nama_dekan = $dekan ? $dekan['nama_lengkap'] : 'Prof. Dr. Dekan Faperta, M.Si';
$nip_dekan = $dekan ? $dekan['username'] : '19700101 200001 1 001';

$tanggal_sekarang = tanggal_indonesia(date('Y-m-d'));
$pdf->SetX(120);
$pdf->Cell(0, 6, 'Banjarbaru, ' . $tanggal_sekarang, 0, 1, 'L');
$pdf->SetX(120);
$pdf->Cell(0, 6, 'Dekan,', 0, 1, 'L');

// QR Code — URL pendek agar QR tidak terlalu padat dan bisa discan HP
$secret_key = 'ULM_FAPERTA_2025_SECRET';
$full_token = hash('sha256', $kegiatan['id'] . $kegiatan['nip'] . 'Kegiatan_Akademik' . $secret_key);
$short_token = substr($full_token, 0, 16); // 16 karakter pertama cukup untuk verifikasi

// Deteksi base URL — gunakan IP jaringan lokal agar QR bisa di-scan dari device lain
$server_host = $_SERVER['HTTP_HOST'];
if (strpos($server_host, 'localhost') !== false || strpos($server_host, '127.0.0.1') !== false) {
    $local_ip = getHostByName(getHostName());
    if ($local_ip && $local_ip !== '127.0.0.1') {
        $port = '';
        if (strpos($server_host, ':') !== false) {
            $port = ':' . explode(':', $server_host)[1];
        }
        $server_host = $local_ip . $port;
    }
}

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http');
$base_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
if ($base_dir === '/') $base_dir = '';
$base_url = rtrim($protocol . '://' . $server_host . $base_dir, '/');

// URL pendek: ~80 karakter vs ~250+ sebelumnya
$verify_url = $base_url . "/verifikasi_qr.php?type=kegiatan&id=" . $kegiatan['id'] . "&t=" . $short_token;

$qr_temp_file = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
$old_er = error_reporting(E_ALL & ~E_DEPRECATED);
QRcode::png($verify_url, $qr_temp_file, QR_ECLEVEL_M, 6, 2);
error_reporting($old_er);

if (file_exists($qr_temp_file)) {
    $pdf->Image($qr_temp_file, 120, $pdf->GetY() + 1, 30);
    register_shutdown_function(function() use ($qr_temp_file) {
        if (file_exists($qr_temp_file)) unlink($qr_temp_file);
    });
}
$pdf->Ln(31);
$pdf->SetX(120);
$pdf->SetFont('Times', 'B', 12);
$pdf->Cell(0, 6, $nama_dekan, 0, 1, 'L');
$pdf->SetX(120);
$pdf->SetFont('Times', '', 12);
$pdf->Cell(0, 6, 'NIP. ' . $nip_dekan, 0, 1, 'L');

ob_end_clean();
$pdf->Output('I', 'Surat_Kegiatan_' . str_replace(' ', '_', $kegiatan['nama_kegiatan']) . '.pdf');
?>

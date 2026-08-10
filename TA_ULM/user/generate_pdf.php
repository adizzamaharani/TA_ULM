<?php
session_start();
require '../config/database.php';
require '../libs/fpdf/fpdf.php';

// Proteksi: Pastikan ada user yang login atau ada ID surat yang valid
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    die("Akses ditolak atau ID surat tidak valid.");
}

$surat_id = $_GET['id'];

// Ambil data surat dari database
$stmt = $pdo->prepare("
    SELECT s.*, u.username as nim, p.nama_prodi 
    FROM surat s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN program_studi p ON u.prodi_id = p.id
    WHERE s.id = ?
");
$stmt->execute([$surat_id]);
$surat = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$surat) {
    die("Data surat tidak ditemukan.");
}

// --- Kustomisasi Kelas PDF dengan Header Fakultas Pertanian ---
class PDF extends FPDF
{
    // Header Halaman
    function Header()
    {
        // Logo ULM, posisi x=10, y=8, lebar=25mm
        $this->Image('../assets/img/logo_ulm.png', 10, 8, 25);
        
        // Mengatur posisi X agar teks tidak menimpa logo
        $this->SetX(40);
        
        // Judul Kop Surat
        $this->SetFont('Times', 'B', 14);
        $this->Cell(160, 7, 'KEMENTERIAN PENDIDIKAN, KEBUDAYAAN,', 0, 1, 'C');
        $this->SetX(40);
        $this->Cell(160, 7, 'RISET, DAN TEKNOLOGI', 0, 1, 'C');
        $this->SetFont('Times', 'B', 16);
        $this->SetX(40);
        $this->Cell(160, 7, 'UNIVERSITAS LAMBUNG MANGKURAT', 0, 1, 'C');
        $this->SetFont('Times', 'B', 18);
        $this->SetX(40);
        $this->Cell(160, 7, 'FAKULTAS PERTANIAN', 0, 1, 'C');
        
        // Alamat
        $this->SetFont('Times', '', 10);
        $this->SetX(40);
        $this->Cell(160, 5, 'Jalan Jenderal Achmad Yani KM. 36, Banjarbaru, Kalimantan Selatan 70714', 0, 1, 'C');
        $this->SetX(40);
        $this->Cell(160, 5, 'Telepon (0511) 4772212, Faksimile (0511) 4781589', 0, 1, 'C');
        
        // Mengambil posisi Y saat ini setelah teks alamat
        $y_pos = $this->GetY();

        // Garis Bawah Kop Surat
        $this->SetLineWidth(1);
        $this->Line(10, $y_pos + 1, 200, $y_pos + 1);
        $this->SetLineWidth(0.2);
        $this->Line(10, $y_pos + 2, 200, $y_pos + 2);
        
        // Line break
        $this->Ln(15);
    }

    // Footer Halaman
    function Footer()
    {
        // Posisi 1.5 cm dari bawah
        $this->SetY(-15);
        // Font Times Italic 8
        $this->SetFont('Times', 'I', 8);
        // Nomor halaman
        $this->Cell(0, 10, 'Halaman ' . $this->PageNo(), 0, 0, 'C');
    }
}

// --- Pembuatan Dokumen PDF ---

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Times', '', 12);

// --- Fungsi untuk format tanggal Indonesia ---
function tanggal_indonesia($tanggal) {
    if (empty($tanggal) || $tanggal === '0000-00-00') return '-';
    $bulan = array (
        1 =>   'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    );
    $pecahkan = explode('-', date('Y-m-d', strtotime($tanggal)));
    return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
}

// --- Menentukan Judul Surat Secara Dinamis ---
$judul_surat = strtoupper($surat['jenis_surat']); // Default: uppercase dari jenis surat
switch ($surat['jenis_surat']) {
    case 'Surat Mahasiswa Aktif':
        $judul_surat = 'SURAT KETERANGAN MAHASISWA AKTIF';
        break;
    case 'Surat Keterangan Benar Alumni':
        $judul_surat = 'SURAT KETERANGAN BENAR ALUMNI';
        break;
    case 'Surat Izin Penelitian':
        $judul_surat = 'SURAT IZIN PENELITIAN';
        break;
    case 'Surat Izin Tidak Masuk Kuliah':
        $judul_surat = 'SURAT IZIN TIDAK MASUK KULIAH';
        break;
    case 'Surat Pengantar PKL/Magang':
        $judul_surat = 'SURAT PENGANTAR PKL/MAGANG';
        break;
    // Untuk jenis surat lain, default sudah cukup (misal: "SURAT IZIN PENELITIAN")
}

// --- Judul Surat ---
$pdf->SetFont('Times', 'BU', 14);
$pdf->Cell(0, 7, $judul_surat, 0, 1, 'C');
$pdf->SetFont('Times', '', 12);
$pdf->Cell(0, 7, 'Nomor: .../UN8.1.25/KM/'.date('Y'), 0, 1, 'C');
$pdf->Ln(10);

$keterangan_tambahan = '';
switch ($surat['jenis_surat']) {
    case 'Surat Mahasiswa Aktif':
    case 'Surat Izin Penelitian':
    case 'Surat Pengantar PKL/Magang':
    case 'Surat Izin Tidak Masuk Kuliah':
        // --- Isi Surat ---
        $pdf->MultiCell(0, 7, 'Yang bertanda tangan di bawah ini, Dekan Fakultas Pertanian Universitas Lambung Mangkurat, menerangkan bahwa mahasiswa di bawah ini:', 0, 'J');
        $pdf->Ln(2);
        $pdf->Cell(20); $pdf->Cell(35, 7, 'Nama', 0, 0); $pdf->Cell(5, 7, ':', 0, 0); $pdf->MultiCell(0, 7, $surat['nama_lengkap'], 0, 'L');
        $pdf->Cell(20); $pdf->Cell(35, 7, 'NIM', 0, 0); $pdf->Cell(5, 7, ':', 0, 0); $pdf->MultiCell(0, 7, $surat['nim'], 0, 'L');
        $pdf->Cell(20); $pdf->Cell(35, 7, 'Program Studi', 0, 0); $pdf->Cell(5, 7, ':', 0, 0); $pdf->MultiCell(0, 7, $surat['nama_prodi'], 0, 'L');

        if ($surat['semester']) {
            $pdf->Cell(20); $pdf->Cell(35, 7, 'Semester', 0, 0); $pdf->Cell(5, 7, ':', 0, 0); $pdf->MultiCell(0, 7, $surat['semester'], 0, 'L');
        }
        $pdf->Ln(5);

        if ($surat['jenis_surat'] == 'Surat Mahasiswa Aktif') {
        $pdf->MultiCell(0, 7, 'adalah benar mahasiswa aktif Fakultas Pertanian Universitas Lambung Mangkurat Tahun Akademik ' . (date('Y')).'/'.(date('Y')+1) . '.', 0, 'J');
        $pdf->Ln(5);
        $pdf->Write(7, 'Surat keterangan ini dibuat untuk keperluan: ');
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Write(7, lcfirst($surat['keperluan']));
        $pdf->SetFont('Times', '', 12);
        $pdf->Write(7, '.');
        } elseif ($surat['jenis_surat'] == 'Surat Izin Penelitian') {
        $pdf->MultiCell(0, 7, 'adalah benar mahasiswa aktif Fakultas Pertanian Universitas Lambung Mangkurat yang akan melaksanakan penelitian.', 0, 'J');
        $pdf->Ln(5);
        $pdf->Write(7, 'Mahasiswa yang bersangkutan akan melaksanakan penelitian untuk keperluan Skripsi dengan judul: "');
        $pdf->SetFont('Times', 'BI', 12);
        $pdf->Write(7, $surat['keperluan']);
        $pdf->SetFont('Times', '', 12);
        $pdf->Write(7, '".');
        if (!empty($surat['tanggal_mulai']) && !empty($surat['tanggal_selesai'])) {
            $pdf->Ln(7);
            $pdf->Write(7, 'Penelitian ini direncanakan akan dilaksanakan mulai tanggal ' . tanggal_indonesia($surat['tanggal_mulai']) . ' sampai dengan ' . tanggal_indonesia($surat['tanggal_selesai']) . '.');
        }
        if ($surat['dosen_pembimbing']) {
            $pdf->Write(7, ' Dosen pembimbing: ' . $surat['dosen_pembimbing'] . '.');
        }
        } elseif ($surat['jenis_surat'] == 'Surat Pengantar PKL/Magang') {
        $pdf->MultiCell(0, 7, 'adalah benar mahasiswa aktif Fakultas Pertanian Universitas Lambung Mangkurat yang akan melaksanakan Praktik Kerja Lapangan (PKL)/Magang.', 0, 'J');
        $pdf->Ln(2);
        $pdf->Write(7, 'Sehubungan dengan hal tersebut, kami memohon kesediaan Bapak/Ibu Pimpinan ');
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Write(7, $surat['keperluan']);
        $pdf->SetFont('Times', '', 12);
        $pdf->Write(7, ' untuk dapat menerima mahasiswa yang bersangkutan melaksanakan PKL/Magang pada instansi/perusahaan yang Bapak/Ibu pimpin, yang direncanakan pada tanggal ');
        $pdf->SetFont('Times', 'IU', 12); // Italic + Underline
        $pdf->Write(7, tanggal_indonesia($surat['tanggal_mulai']) . ' sampai dengan ' . tanggal_indonesia($surat['tanggal_selesai']));
        $pdf->SetFont('Times', '', 12); // Kembalikan font ke normal
        $pdf->Write(7, '.');
        } elseif ($surat['jenis_surat'] == 'Surat Izin Tidak Masuk Kuliah') {
        $pdf->MultiCell(0, 7, 'adalah benar mahasiswa aktif Fakultas Pertanian Universitas Lambung Mangkurat.', 0, 'J');
        $pdf->Ln(5);
        $pdf->Write(7, 'Mahasiswa yang bersangkutan memohon izin tidak dapat mengikuti perkuliahan karena ');
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Write(7, lcfirst($surat['keperluan']));
        $pdf->SetFont('Times', '', 12);
        $pdf->Write(7, '.');
        }
        $pdf->Ln();
        break;

    case 'Surat Keterangan Benar Alumni':
        $pdf->MultiCell(0, 7, 'Yang bertanda tangan di bawah ini, Dekan Fakultas Pertanian Universitas Lambung Mangkurat, menerangkan bahwa nama di bawah ini:', 0, 'J');
        $pdf->Ln(2);
        $pdf->Cell(20); $pdf->Cell(35, 7, 'Nama', 0, 0); $pdf->Cell(5, 7, ':', 0, 0); $pdf->MultiCell(0, 7, $surat['nama_lengkap'], 0, 'L');
        $pdf->Cell(20); $pdf->Cell(35, 7, 'NIM', 0, 0); $pdf->Cell(5, 7, ':', 0, 0); $pdf->MultiCell(0, 7, $surat['nim'], 0, 'L');
        $pdf->Cell(20); $pdf->Cell(35, 7, 'Program Studi', 0, 0); $pdf->Cell(5, 7, ':', 0, 0); $pdf->MultiCell(0, 7, $surat['nama_prodi'], 0, 'L');
        $pdf->Ln(5);
        $pdf->MultiCell(0, 7, 'adalah benar alumni dari Fakultas Pertanian Universitas Lambung Mangkurat. Surat ini dibuat untuk keperluan: ' . lcfirst($surat['keperluan']) . '.', 0, 'J');
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Ln();
        break;
    default:
        $keterangan_tambahan = "Surat ini dibuat untuk keperluan " . lcfirst($surat['keperluan']) . ".";
        break;
}

$pdf->Ln(5);
$pdf->MultiCell(0, 7, 'Demikian surat keterangan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.', 0, 'J');
$pdf->Ln(15);

// --- Tanda Tangan ---
$pdf->SetX(100); // Geser ke kanan (disesuaikan agar nama panjang muat)
$pdf->Cell(0, 7, 'Banjarbaru, ' . tanggal_indonesia(date('Y-m-d')), 0, 1, 'L');
$pdf->SetX(100);
$pdf->Cell(0, 7, 'Dekan,', 0, 1, 'L');

// TTD Digital (jika ada)
$path_ttd = '../assets/img/ttd_dekan.png'; 
if (file_exists($path_ttd)) {
    $pdf->Image($path_ttd, 105, $pdf->GetY() + 1, 25);
}
$pdf->Ln(26); // Spasi untuk TTD, ditambah agar tidak tumpang tindih

$pdf->SetX(100);
$pdf->SetFont('Times', 'BU', 11); // Font sedikit diperkecil agar nama panjang muat 1 baris
$pdf->Cell(0, 7, 'Prof. Ir. Akhmad Rizali Saidy, S.P., M.Ag.Sc., Ph.D., IPM.', 0, 1, 'L');
$pdf->SetFont('Times', '', 12);
$pdf->SetX(100);
$pdf->Cell(0, 7, 'NIP. 19700609 199512 1 001', 0, 1, 'L');

// Output PDF
$nama_file = 'Surat_Keterangan_' . str_replace(' ', '_', $surat['nama_lengkap']) . '.pdf';
$pdf->Output('I', $nama_file);

?>
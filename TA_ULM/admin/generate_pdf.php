<?php
session_start();
require '../config/database.php';
require '../libs/fpdf/fpdf.php';
// Suppress PHP 8 deprecation warnings dari library phpqrcode (library lama)
$old_error_reporting = error_reporting(E_ALL & ~E_DEPRECATED);
require '../libs/phpqrcode/qrlib.php';
error_reporting($old_error_reporting);

$surat_id = $_GET['id'] ?? null;
if (!$surat_id) {
    die('ID Surat tidak ditemukan.');
}

// Ambil data surat dari database
$stmt = $pdo->prepare("
    SELECT s.*, u.username as nim, p.nama_prodi
    FROM surat s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN program_studi p ON u.prodi_id = p.id
    WHERE s.id = ? AND s.status = 'Selesai'
");
$stmt->execute([$surat_id]);
$surat = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$surat) {
    die('Surat tidak ditemukan atau belum berstatus "Selesai".');
}

// --- LOGIKA PROTEKSI HALAMAN BARU ---
// Aturan:
// 1. User harus login.
// 2. Jika role adalah 'admin', izinkan akses.
// 3. Jika role adalah 'user', izinkan akses HANYA JIKA user_id di sesi sama dengan user_id pemilik surat.
if (!isset($_SESSION['user_id'])) {
    die('Akses ditolak. Anda harus login untuk melihat surat ini.');
}
if ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] != $surat['user_id']) {
    die('Akses ditolak. Anda tidak memiliki izin untuk melihat surat ini.');
}

class PDF extends FPDF
{
    // Kop Surat
    function Header()
    {
        // Logo ULM, posisi x=10, y=8, lebar=25mm
        $this->Image('../assets/img/logo_ulm.png', 15, 10, 22);

        // Teks kop surat di sebelah kanan logo
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
        
        // Alamat
        $this->SetX(40);
        $this->SetFont('Times', '', 9);
        $this->Cell(155, 4.5, 'Jalan Jendral Ahmad Yani Km. 36 Kotak Pos 1028 Banjarbaru 70714', 0, 1, 'C');
        $this->SetX(40);
        $this->Cell(155, 4.5, 'Telp/Fax (0511) 4772254', 0, 1, 'C');
        
        // Email dan Website
        $this->SetX(40);
        $this->SetFont('Times', 'B', 9);
        $this->Cell(155, 4.5, 'Email: faperta@ulm.ac.id, Website: www.faperta.ulm.ac.id', 0, 1, 'C');

        // Mengambil posisi Y saat ini setelah teks alamat
        $y_pos = $this->GetY();

        // Garis bawah kop surat (garis tebal + tipis)
        $this->SetLineWidth(0.8);
        $this->Line(10, $y_pos + 2, 200, $y_pos + 2);
        $this->SetLineWidth(0.2);
        $this->Line(10, $y_pos + 3.5, 200, $y_pos + 3.5);

        // Spasi setelah kop
        $this->Ln(15);
    }

    // Page footer
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Halaman ' . $this->PageNo(), 0, 0, 'C');
    }
}

// Buat objek PDF
$pdf = new PDF('P', 'mm', 'A4');
$pdf->SetMargins(25, 10, 25);
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 25);

// --- Fungsi untuk format tanggal Indonesia ---
function tanggal_indonesia($tanggal) {
    $bulan = array (
        1 =>   'Januari',
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
    if (empty($tanggal) || $tanggal === '0000-00-00') return '-';
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
    case 'Surat Tugas Dosen':
        $judul_surat = 'SURAT TUGAS DOSEN';
        break;
    case 'Surat Keterlambatan UKT':
        $judul_surat = 'SURAT KETERANGAN KETERLAMBATAN PEMBAYARAN UKT';
        break;
    case 'Surat Keterangan Lulus':
        $judul_surat = 'SURAT KETERANGAN LULUS';
        break;
}

// Judul Surat
$pdf->SetFont('Times', 'BU', 14);
$pdf->Cell(0, 7, $judul_surat, 0, 1, 'C');

// --- NOMOR SURAT OTOMATIS ---
// Jika surat sudah punya nomor surat di database, pakai itu
// Jika belum, generate otomatis dan simpan ke database
$nomor_surat = $surat['nomor_surat'];

if (empty($nomor_surat)) {
    // Generate nomor surat baru
    $tahun_sekarang = date('Y');
    
    // Cari nomor urut terakhir di tahun ini
    $stmt_last = $pdo->prepare("SELECT nomor_surat FROM surat WHERE nomor_surat IS NOT NULL AND nomor_surat != '' AND nomor_surat LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt_last->execute(["%/SP/$tahun_sekarang"]);
    $last_nomor = $stmt_last->fetchColumn();
    
    $nomor_urut = 1;
    if ($last_nomor) {
        $parts = explode('/', $last_nomor);
        $nomor_urut = (int)$parts[0] + 1;
    }
    
    // Format: 001/UN8.1.23/SP/2026
    $nomor_surat = sprintf("%03d", $nomor_urut) . "/UN8.1.23/SP/" . $tahun_sekarang;
    
    // Simpan nomor surat ke database
    $stmt_update = $pdo->prepare("UPDATE surat SET nomor_surat = ? WHERE id = ?");
    $stmt_update->execute([$nomor_surat, $surat['id']]);
}

$pdf->SetFont('Times', '', 12);
$pdf->Cell(0, 7, 'Nomor: ' . $nomor_surat, 0, 1, 'C');
$pdf->Ln(10);

// --- Logika Paragraf Penjelasan Sesuai Jenis Surat ---
switch ($surat['jenis_surat']) {
    case 'Surat Mahasiswa Aktif':
    case 'Surat Izin Penelitian':
    case 'Surat Pengantar PKL/Magang':
    case 'Surat Izin Tidak Masuk Kuliah':
        // Isi Surat
        $pdf->SetFont('Times', '', 12);
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
        $pdf->MultiCell(0, 7, 'Adalah benar mahasiswa aktif Fakultas Pertanian Universitas Lambung Mangkurat pada Semester ' . (date('m') <= 6 ? 'Genap' : 'Ganjil') . ' Tahun Akademik ' . date('Y') . '/' . (date('Y') + 1) . '.', 0, 'J');
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
        break;
    case 'Surat Tugas Dosen':
        $pdf->SetFont('Times', '', 12);
        $pdf->MultiCell(0, 7, 'Yang bertanda tangan di bawah ini, Dekan Fakultas Pertanian Universitas Lambung Mangkurat, memberikan tugas kepada:', 0, 'J');
        $pdf->Ln(2);
        $pdf->Cell(20); $pdf->Cell(35, 7, 'Nama Dosen', 0, 0); $pdf->Cell(5, 7, ':', 0, 0); $pdf->MultiCell(0, 7, $surat['dosen_pembimbing'], 0, 'L');
        $pdf->Ln(5);
        $pdf->MultiCell(0, 7, 'Untuk melaksanakan tugas sebagai berikut:', 0, 'J');
        $pdf->Ln(2);
        $pdf->SetFont('Times', 'B', 12);
        $pdf->MultiCell(0, 7, $surat['keperluan'], 0, 'J');
        $pdf->SetFont('Times', '', 12);
        $pdf->Ln(2);
        $pdf->MultiCell(0, 7, 'Berkaitan dengan mahasiswa:', 0, 'J');
        $pdf->Ln(2);
        $pdf->Cell(20); $pdf->Cell(35, 7, 'Nama', 0, 0); $pdf->Cell(5, 7, ':', 0, 0); $pdf->MultiCell(0, 7, $surat['nama_lengkap'], 0, 'L');
        $pdf->Cell(20); $pdf->Cell(35, 7, 'NIM', 0, 0); $pdf->Cell(5, 7, ':', 0, 0); $pdf->MultiCell(0, 7, $surat['nim'], 0, 'L');
        $pdf->Cell(20); $pdf->Cell(35, 7, 'Program Studi', 0, 0); $pdf->Cell(5, 7, ':', 0, 0); $pdf->MultiCell(0, 7, $surat['nama_prodi'], 0, 'L');
        if (!empty($surat['tanggal_mulai']) && !empty($surat['tanggal_selesai'])) {
            $pdf->Ln(5);
            $pdf->Write(7, 'Tugas ini dilaksanakan mulai tanggal ' . tanggal_indonesia($surat['tanggal_mulai']) . ' sampai dengan ' . tanggal_indonesia($surat['tanggal_selesai']) . '.');
        }
        $pdf->Ln();
        break;
    case 'Surat Keterlambatan UKT':
        $pdf->SetFont('Times', '', 12);
        $pdf->MultiCell(0, 7, 'Yang bertanda tangan di bawah ini, Dekan Fakultas Pertanian Universitas Lambung Mangkurat, menerangkan bahwa mahasiswa di bawah ini:', 0, 'J');
        $pdf->Ln(2);
        $pdf->Cell(20); $pdf->Cell(35, 7, 'Nama', 0, 0); $pdf->Cell(5, 7, ':', 0, 0); $pdf->MultiCell(0, 7, $surat['nama_lengkap'], 0, 'L');
        $pdf->Cell(20); $pdf->Cell(35, 7, 'NIM', 0, 0); $pdf->Cell(5, 7, ':', 0, 0); $pdf->MultiCell(0, 7, $surat['nim'], 0, 'L');
        $pdf->Cell(20); $pdf->Cell(35, 7, 'Program Studi', 0, 0); $pdf->Cell(5, 7, ':', 0, 0); $pdf->MultiCell(0, 7, $surat['nama_prodi'], 0, 'L');
        if ($surat['semester']) {
            $pdf->Cell(20); $pdf->Cell(35, 7, 'Semester', 0, 0); $pdf->Cell(5, 7, ':', 0, 0); $pdf->MultiCell(0, 7, $surat['semester'], 0, 'L');
        }
        $pdf->Ln(5);
        $pdf->MultiCell(0, 7, 'adalah benar mahasiswa aktif Fakultas Pertanian Universitas Lambung Mangkurat yang mengajukan permohonan keterlambatan pembayaran Uang Kuliah Tunggal (UKT) pada Semester ' . (date('m') <= 6 ? 'Genap' : 'Ganjil') . ' Tahun Akademik ' . date('Y') . '/' . (date('Y') + 1) . '.', 0, 'J');
        $pdf->Ln(5);
        $pdf->Write(7, 'Adapun alasan keterlambatan pembayaran: ');
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Write(7, lcfirst($surat['keperluan']));
        $pdf->SetFont('Times', '', 12);
        $pdf->Write(7, '.');
        $pdf->Ln(7);
        $pdf->MultiCell(0, 7, 'Untuk itu kami mohon agar mahasiswa yang bersangkutan dapat diberikan kelonggaran waktu dalam pembayaran UKT.', 0, 'J');
        break;
    case 'Surat Keterangan Lulus':
        $pdf->SetFont('Times', '', 12);
        $pdf->MultiCell(0, 7, 'Yang bertanda tangan di bawah ini, Dekan Fakultas Pertanian Universitas Lambung Mangkurat, menerangkan bahwa:', 0, 'J');
        $pdf->Ln(2);
        $pdf->Cell(20); $pdf->Cell(35, 7, 'Nama', 0, 0); $pdf->Cell(5, 7, ':', 0, 0); $pdf->MultiCell(0, 7, $surat['nama_lengkap'], 0, 'L');
        $pdf->Cell(20); $pdf->Cell(35, 7, 'NIM', 0, 0); $pdf->Cell(5, 7, ':', 0, 0); $pdf->MultiCell(0, 7, $surat['nim'], 0, 'L');
        $pdf->Cell(20); $pdf->Cell(35, 7, 'Program Studi', 0, 0); $pdf->Cell(5, 7, ':', 0, 0); $pdf->MultiCell(0, 7, $surat['nama_prodi'], 0, 'L');
        $pdf->Ln(5);
        $pdf->MultiCell(0, 7, 'adalah benar telah menyelesaikan seluruh program studi dan dinyatakan LULUS dari Program Studi ' . ($surat['nama_prodi'] ?? '-') . ', Fakultas Pertanian, Universitas Lambung Mangkurat.', 0, 'J');
        $pdf->Ln(5);
        $pdf->Write(7, 'Surat keterangan lulus ini dibuat untuk keperluan: ');
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Write(7, lcfirst($surat['keperluan']));
        $pdf->SetFont('Times', '', 12);
        $pdf->Write(7, '.');
        $pdf->Ln();
        break;
}
$pdf->Ln(5);

$pdf->MultiCell(0, 7, 'Demikian surat keterangan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.', 0, 'J');
$pdf->Ln(15);

// Bagian Tanda Tangan
$nama_pejabat = 'Prof. Ir. Akhmad Rizali Saidy, S.P., M.Ag.Sc., Ph.D., IPM.';
$nip_pejabat = '19700609 199512 1 001';

$pdf->SetX(100);
$pdf->Cell(0, 7, 'Banjarbaru, ' . tanggal_indonesia(date('Y-m-d')), 0, 1, 'L');
$pdf->SetX(100);
$pdf->Cell(0, 7, 'Dekan,', 0, 1, 'L');

// --- QR CODE DINAMIS DENGAN ENKRIPSI AES-256 ---
// Kunci Rahasia untuk Enkripsi (Harus sama dengan di verifikasi_qr.php)
$aes_key = 'KUNCI_RAHASIA_SKRIPSI_FAPERTA_26'; // Panjang ideal 32 karakter
$aes_iv = '1234567890123456'; // Initialization Vector (16 byte statis untuk kemudahan)

// Generate token verifikasi standar (hash)
$secret_key = 'ULM_FAPERTA_2025_SECRET';
$verify_token = hash('sha256', $surat['id'] . $surat['nim'] . $surat['jenis_surat'] . $secret_key);

// Siapkan data yang akan dienkripsi
$data_to_encrypt = json_encode([
    'id' => $surat['id'],
    'token' => $verify_token
]);

// Proses Enkripsi AES-256-CBC
$encrypted_data = openssl_encrypt($data_to_encrypt, 'aes-256-cbc', $aes_key, 0, $aes_iv);
$url_safe_encrypted_data = urlencode(base64_encode($encrypted_data));

// Deteksi base URL — gunakan IP jaringan lokal agar QR bisa di-scan dari device lain
// Prioritas: IP lokal > HTTP_HOST
$server_host = $_SERVER['HTTP_HOST'];
// Jika diakses via localhost, ganti dengan IP lokal supaya QR bisa diakses dari HP
if (strpos($server_host, 'localhost') !== false || strpos($server_host, '127.0.0.1') !== false) {
    // Coba deteksi IP lokal otomatis
    $local_ip = getHostByName(getHostName());
    if ($local_ip && $local_ip !== '127.0.0.1') {
        // Pertahankan port jika ada
        $port = '';
        if (strpos($server_host, ':') !== false) {
            $port = ':' . explode(':', $server_host)[1];
        }
        $server_host = $local_ip . $port;
    }
}

$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
    . '://' . $server_host
    . dirname(dirname($_SERVER['SCRIPT_NAME']));

// Isi QR Code sekarang berupa ciphertext yang aman
$qr_content = $base_url . '/verifikasi_qr.php?data=' . $url_safe_encrypted_data;

$qr_temp_file = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
$old_er = error_reporting(E_ALL & ~E_DEPRECATED);
QRcode::png($qr_content, $qr_temp_file, QR_ECLEVEL_M, 4, 2);
error_reporting($old_er);

if (file_exists($qr_temp_file)) {
    $pdf->Image($qr_temp_file, 105, $pdf->GetY() + 1, 25);
    // Hapus file temporary setelah dimasukkan ke PDF
    register_shutdown_function(function() use ($qr_temp_file) {
        if (file_exists($qr_temp_file)) unlink($qr_temp_file);
    });
}

$pdf->Ln(26);

// Nama Pejabat
$pdf->SetFont('Times', 'BU', 11);
$pdf->SetX(100);
$pdf->Cell(0, 7, $nama_pejabat, 0, 1, 'L');

// NIP Pejabat
$pdf->SetFont('Times', '', 12);
$pdf->SetX(100);
$pdf->Cell(0, 7, 'NIP. ' . $nip_pejabat, 0, 1, 'L');

// Output PDF
$nama_file = 'Surat_Keterangan_' . str_replace(' ', '_', $surat['nama_lengkap']) . '.pdf';
$pdf->Output('I', $nama_file);

?>
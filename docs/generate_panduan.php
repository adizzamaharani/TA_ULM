<?php
/**
 * Generate PDF Panduan Penggunaan Website Sistem Surat Faperta ULM
 * Bahasa super sederhana - bahkan anak kecil pun paham!
 */
require '../libs/fpdf/fpdf.php';

class PanduanPDF extends FPDF
{
    function Header()
    {
        if ($this->PageNo() == 1) return;
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 8, 'Panduan Penggunaan - Sistem Surat Menyurat Faperta ULM', 0, 1, 'C');
        $this->Line(10, 13, 200, 13);
        $this->Ln(5);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 10, 'Halaman ' . $this->PageNo(), 0, 0, 'C');
    }

    function SectionTitle($num, $title)
    {
        $this->Ln(5);
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(26, 26, 46);
        $this->SetFillColor(255, 215, 0);
        $this->Cell(0, 10, '  ' . $num . '. ' . $title, 0, 1, 'L', true);
        $this->Ln(3);
    }

    function SubTitle($title)
    {
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(50, 50, 50);
        $this->Cell(0, 7, $title, 0, 1, 'L');
        $this->Ln(1);
    }

    function Paragraph($text)
    {
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(60, 60, 60);
        $this->MultiCell(0, 6, $text, 0, 'L');
        $this->Ln(2);
    }

    function Step($num, $text)
    {
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(26, 26, 46);
        $this->Cell(8, 6, $num . '.', 0, 0, 'R');
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(60, 60, 60);
        $this->MultiCell(0, 6, ' ' . $text, 0, 'L');
        $this->Ln(1);
    }

    function Note($text)
    {
        $this->SetFont('Arial', 'I', 9);
        $this->SetTextColor(100, 100, 100);
        $this->SetFillColor(255, 253, 230);
        $this->MultiCell(0, 5, '    [Tips] ' . $text, 0, 'L', true);
        $this->Ln(3);
    }

    function CodeBox($text)
    {
        $this->SetFont('Courier', 'B', 11);
        $this->SetTextColor(0, 0, 0);
        $this->SetFillColor(240, 240, 240);
        $this->SetDrawColor(200, 200, 200);
        $this->Cell(0, 8, '  ' . $text, 1, 1, 'L', true);
        $this->Ln(2);
    }

    function Emoji($text)
    {
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(80, 80, 80);
        $this->MultiCell(0, 6, $text, 0, 'L');
        $this->Ln(1);
    }
}

// ============ BUAT PDF ============
$pdf = new PanduanPDF('P', 'mm', 'A4');
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 20);

// ===== HALAMAN COVER =====
$pdf->AddPage();
$pdf->Ln(30);
$pdf->Image('../assets/img/logo_ulm.png', 80, 35, 50);
$pdf->Ln(60);
$pdf->SetFont('Arial', 'B', 22);
$pdf->SetTextColor(26, 26, 46);
$pdf->Cell(0, 12, 'PANDUAN PENGGUNAAN', 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 18);
$pdf->SetTextColor(199, 166, 0);
$pdf->Cell(0, 10, 'Sistem Surat Menyurat Digital', 0, 1, 'C');
$pdf->Ln(5);
$pdf->SetFont('Arial', '', 12);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 7, 'Fakultas Pertanian', 0, 1, 'C');
$pdf->Cell(0, 7, 'Universitas Lambung Mangkurat', 0, 1, 'C');
$pdf->Ln(15);
$pdf->SetFont('Arial', '', 11);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(0, 7, 'Panduan ini ditulis dengan bahasa yang super mudah dipahami.', 0, 1, 'C');
$pdf->Cell(0, 7, 'Ikuti langkah-langkahnya satu per satu, pasti bisa!', 0, 1, 'C');
$pdf->Ln(30);
$pdf->SetFont('Arial', 'I', 9);
$pdf->SetTextColor(150, 150, 150);
$pdf->Cell(0, 6, 'Versi 1.0 - ' . date('F Y'), 0, 1, 'C');

// ===== DAFTAR ISI =====
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->SetTextColor(26, 26, 46);
$pdf->Cell(0, 10, 'DAFTAR ISI', 0, 1, 'C');
$pdf->Ln(5);
$pdf->SetFont('Arial', '', 11);
$pdf->SetTextColor(60, 60, 60);

$daftar = [
    '1. Nyalakan XAMPP Dulu!' => '3',
    '2. Cara Tau IP Address Laptop Kamu' => '4',
    '3. Buka Website di Laptop' => '5',
    '4. Buka Website di HP (Lewat WiFi)' => '5',
    '5. Cara Login (Masuk ke Website)' => '6',
    '6. Panduan Mahasiswa - Ajukan Surat' => '7',
    '7. Panduan Admin - Kelola Surat' => '8',
    '8. Panduan Dekan - Lihat Surat' => '9',
    '9. Cara Cetak Surat jadi PDF' => '9',
    '10. Kalau Ada Masalah (Troubleshooting)' => '10',
];

foreach ($daftar as $judul => $hal) {
    $dots = str_repeat('.', max(1, 55 - strlen($judul)));
    $pdf->Cell(0, 8, $judul . ' ' . $dots . ' ' . $hal, 0, 1, 'L');
}

// ===== BAGIAN 1: MENYALAKAN XAMPP =====
$pdf->AddPage();
$pdf->SectionTitle('1', 'Nyalakan XAMPP Dulu!');

$pdf->Paragraph('Oke, jadi gini. Website ini butuh yang namanya "server" biar bisa jalan. Nah, XAMPP itu yang bikin laptop kamu jadi server. Kalau XAMPP belum nyala, website TIDAK BISA dibuka sama sekali. Jadi ini langkah PERTAMA yang wajib dilakukan.');

$pdf->SubTitle('Apa itu XAMPP?');
$pdf->Paragraph('XAMPP itu kayak "mesin" yang bikin website bisa hidup di laptop kamu. Tanpa dia, website cuma file biasa yang gak bisa dibuka di browser.');

$pdf->SubTitle('Langkah-langkah Nyalakan XAMPP:');
$pdf->Step('1', 'Cari aplikasi "XAMPP Control Panel" di laptop kamu. Biasanya ada di Start Menu atau di Desktop.');
$pdf->Step('2', 'Buka aplikasinya. Nanti muncul jendela dengan daftar: Apache, MySQL, FileZilla, dll.');
$pdf->Step('3', 'Klik tombol "Start" yang ada di sebelah kanan tulisan "Apache".');
$pdf->Step('4', 'Klik tombol "Start" juga yang ada di sebelah kanan tulisan "MySQL".');
$pdf->Step('5', 'Tunggu sebentar... Kalau tulisan "Apache" dan "MySQL" berubah jadi WARNA HIJAU, berarti BERHASIL!');

$pdf->Ln(2);
$pdf->Note('Yang perlu di-Start cuma 2 aja: Apache dan MySQL. Yang lain (FileZilla, Mercury, Tomcat) TIDAK PERLU dinyalakan.');
$pdf->Note('Kalau tombolnya sudah berubah jadi "Stop", itu artinya sudah nyala. Jangan diklik lagi ya, nanti malah mati!');

$pdf->SubTitle('Kalau Apache GAGAL Nyala (Warna Merah):');
$pdf->Paragraph('Tenang, ini sering terjadi. Biasanya karena port 80 sudah dipakai program lain (misalnya Skype atau program bawaan Windows). Solusinya ganti port:');
$pdf->Step('1', 'Di XAMPP, klik tombol "Config" di baris Apache.');
$pdf->Step('2', 'Pilih "Apache (httpd.conf)" - nanti kebuka file teks.');
$pdf->Step('3', 'Tekan Ctrl+F (cari), ketik "Listen 80".');
$pdf->Step('4', 'Ganti angka 80 jadi 8080, jadinya "Listen 8080".');
$pdf->Step('5', 'Simpan file (Ctrl+S), tutup, lalu klik Start lagi.');
$pdf->Note('Kalau pakai port 8080, nanti alamat websitenya juga berubah ya (ditambah :8080). Tenang, dibahas di bagian selanjutnya.');

// ===== BAGIAN 2: CARA TAU IP ADDRESS =====
$pdf->AddPage();
$pdf->SectionTitle('2', 'Cara Tau IP Address Laptop Kamu');

$pdf->Paragraph('IP Address itu ibarat "alamat rumah" laptop kamu di jaringan WiFi. Kalau kamu mau buka website dari HP, HP kamu perlu tau alamat ini. Kayak mau ke rumah teman, harus tau alamatnya dulu kan?');

$pdf->SubTitle('Langkah-langkah Cek IP Address:');
$pdf->Step('1', 'Di keyboard laptop, tekan tombol Windows + R secara bersamaan (tahan Windows, tekan R).');
$pdf->Paragraph('   Nanti muncul kotak kecil di pojok kiri bawah bertuliskan "Run".');
$pdf->Step('2', 'Di kotak Run itu, ketik: cmd lalu tekan Enter.');
$pdf->Paragraph('   Nanti muncul layar hitam. Jangan takut, itu namanya Command Prompt.');
$pdf->Step('3', 'Di layar hitam itu, ketik perintah ini lalu tekan Enter:');
$pdf->CodeBox('ipconfig');
$pdf->Step('4', 'Nanti keluar banyak tulisan. Cari bagian yang ada tulisan "Wireless LAN adapter Wi-Fi".');
$pdf->Step('5', 'Di bawahnya, cari baris "IPv4 Address". Angka di sebelah kanannya itu IP kamu!');

$pdf->Paragraph('Contohnya kayak gini:');
$pdf->CodeBox('IPv4 Address. . . . . . . . . . . : 192.168.100.161');
$pdf->Paragraph('Berarti IP laptop kamu adalah 192.168.100.161');

$pdf->Ln(2);
$pdf->Note('IP Address bisa BERUBAH kalau kamu pindah WiFi. Jadi kalau pindah tempat, cek lagi ya!');
$pdf->Note('Kalau laptop pakai kabel LAN (bukan WiFi), lihat di bagian "Ethernet adapter" ya, bukan "Wireless".');
$pdf->Note('Catat atau foto IP-nya biar gampang diingat pas mau buka di HP.');

// ===== BAGIAN 3: AKSES DARI LAPTOP =====
$pdf->AddPage();
$pdf->SectionTitle('3', 'Buka Website di Laptop');

$pdf->Paragraph('Nah, sekarang XAMPP udah nyala kan? Saatnya buka websitenya! Ini gampang banget.');

$pdf->SubTitle('Langkah-langkah:');
$pdf->Step('1', 'Buka browser apa aja (Chrome, Firefox, Edge, terserah).');
$pdf->Step('2', 'Klik di bagian address bar (tempat nulis alamat website di atas).');
$pdf->Step('3', 'Ketik alamat ini:');
$pdf->CodeBox('http://localhost/TA_ULM/');
$pdf->Step('4', 'Tekan Enter.');
$pdf->Step('5', 'SELESAI! Website langsung terbuka. Kamu akan lihat halaman login.');

$pdf->Ln(2);
$pdf->Note('Kalau tadi kamu ganti port Apache ke 8080, alamatnya jadi: http://localhost:8080/TA_ULM/');
$pdf->Note('"localhost" itu artinya "laptop ini sendiri". Jadi cuma bisa dipakai di laptop yang nyalain XAMPP.');

// ===== BAGIAN 4: AKSES DARI HP =====
$pdf->Ln(5);
$pdf->SectionTitle('4', 'Buka Website di HP (Lewat WiFi)');

$pdf->Paragraph('Ini bagian yang seru! Kamu bisa buka website ini dari HP kamu, asalkan HP dan laptop terhubung ke WiFi yang SAMA. Ingat ya, HARUS WiFi yang sama!');

$pdf->SubTitle('Syarat Wajib:');
$pdf->Paragraph('- Laptop dan HP harus konek ke WiFi yang SAMA (nama WiFi-nya sama).');
$pdf->Paragraph('- XAMPP di laptop harus sudah nyala (Apache & MySQL hijau).');
$pdf->Paragraph('- Kamu sudah tau IP Address laptop (lihat Bagian 2).');

$pdf->SubTitle('Langkah-langkah:');
$pdf->Step('1', 'Di HP kamu, buka browser (Chrome atau Safari).');
$pdf->Step('2', 'Ketik alamat ini di address bar (GANTI angka IP sesuai punya kamu):');
$pdf->CodeBox('http://192.168.100.161/TA_ULM/');
$pdf->Step('3', 'Tekan Enter / Go.');
$pdf->Step('4', 'Website terbuka di HP! Sekarang kamu bisa login dari HP.');

$pdf->Ln(2);
$pdf->Note('Ganti 192.168.100.161 dengan IP laptop KAMU yang tadi dicek di Bagian 2.');
$pdf->Note('Kalau pakai port 8080: http://192.168.100.161:8080/TA_ULM/');
$pdf->Note('Kalau gak bisa kebuka, kemungkinan Firewall laptop nge-block. Lihat solusinya di bawah.');

$pdf->SubTitle('Kalau HP Gak Bisa Akses (Firewall Nge-block):');
$pdf->Paragraph('Kadang Windows Firewall nge-block akses dari luar. Cara bukanya:');
$pdf->Step('1', 'Di laptop, klik kanan tombol Start > pilih "Windows Terminal (Admin)" atau "CMD (Admin)".');
$pdf->Step('2', 'Ketik perintah ini lalu Enter:');
$pdf->CodeBox('netsh advfirewall firewall add rule name="XAMPP" dir=in action=allow protocol=TCP localport=80');
$pdf->Paragraph('Kalau pakai port 8080, ganti angka 80 jadi 8080.');
$pdf->Step('3', 'Coba lagi buka dari HP. Harusnya sudah bisa!');

// ===== BAGIAN 5: LOGIN =====
$pdf->AddPage();
$pdf->SectionTitle('5', 'Cara Login (Masuk ke Website)');

$pdf->Paragraph('Setelah website kebuka, kamu akan lihat halaman login. Di sini kamu masukin username dan password buat masuk ke dalam sistem.');

$pdf->SubTitle('Akun yang Bisa Dipakai:');
$pdf->Ln(2);

$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(255, 215, 0);
$pdf->Cell(35, 7, ' Siapa', 1, 0, 'C', true);
$pdf->Cell(40, 7, ' Username', 1, 0, 'C', true);
$pdf->Cell(40, 7, ' Password', 1, 0, 'C', true);
$pdf->Cell(65, 7, ' Bisa Ngapain', 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 9);
$pdf->SetFillColor(255, 255, 255);
$akun = [
    ['Admin', 'admin', 'admin1234', 'Kelola semua surat masuk'],
    ['Mahasiswa', 'adiza1234', 'adiza1234', 'Ajukan surat online'],
    ['Dekan', 'dekan1234', 'dekan1234', 'Lihat & pantau semua surat'],
];
foreach ($akun as $a) {
    $pdf->Cell(35, 6, ' ' . $a[0], 1, 0, 'L');
    $pdf->Cell(40, 6, ' ' . $a[1], 1, 0, 'L');
    $pdf->Cell(40, 6, ' ' . $a[2], 1, 0, 'L');
    $pdf->Cell(65, 6, ' ' . $a[3], 1, 1, 'L');
}

$pdf->Ln(4);
$pdf->SubTitle('Cara Login:');
$pdf->Step('1', 'Di kolom "Username / NIM", ketik username kamu (contoh: admin atau adiza1234).');
$pdf->Step('2', 'Di kolom "Password", ketik password kamu.');
$pdf->Step('3', 'Klik tombol kuning "Masuk".');
$pdf->Step('4', 'Kalau berhasil, kamu langsung masuk ke halaman utama (dashboard).');

$pdf->Ln(2);
$pdf->Note('Kalau salah password, akan muncul pesan merah "Username atau password salah". Coba lagi pelan-pelan.');
$pdf->Note('Lupa password? Hubungi admin buat di-reset.');

// ===== BAGIAN 6: PANDUAN MAHASISWA =====
$pdf->AddPage();
$pdf->SectionTitle('6', 'Panduan Mahasiswa - Ajukan Surat');

$pdf->Paragraph('Kalau kamu login sebagai mahasiswa, kamu bisa ajukan berbagai macam surat secara online. Gak perlu ke fakultas, gak perlu antri. Tinggal klik-klik aja dari laptop atau HP.');

$pdf->SubTitle('Jenis Surat yang Bisa Diajukan:');
$pdf->Step('1', 'Surat Keterangan Mahasiswa Aktif');
$pdf->Step('2', 'Surat Izin Penelitian');
$pdf->Step('3', 'Surat Pengantar PKL/Magang');
$pdf->Step('4', 'Surat Izin Tidak Masuk Kuliah');
$pdf->Step('5', 'Surat Keterangan Alumni');
$pdf->Step('6', 'Surat Tugas Dosen');
$pdf->Step('7', 'Surat Keterlambatan UKT');
$pdf->Step('8', 'Surat Keterangan Lulus');

$pdf->Ln(3);
$pdf->SubTitle('Cara Ajukan Surat (Step by Step):');
$pdf->Step('1', 'Login dulu sebagai mahasiswa.');
$pdf->Step('2', 'Di halaman utama, kamu akan lihat menu di sebelah kiri (sidebar). Pilih jenis surat yang mau diajukan.');
$pdf->Step('3', 'Isi semua kolom yang diminta: nama lengkap, NIM, semester, keperluan, dll.');
$pdf->Step('4', 'Kalau sudah yakin benar semua, klik tombol "Ajukan Surat".');
$pdf->Step('5', 'Surat kamu masuk ke sistem dengan status "Pending" (artinya: sedang menunggu).');
$pdf->Step('6', 'Tunggu admin memproses. Nanti statusnya berubah jadi "Diproses" terus "Selesai".');
$pdf->Step('7', 'Kalau sudah "Selesai", akan muncul tombol "PDF". Klik buat download suratnya!');

$pdf->Ln(3);
$pdf->SubTitle('Alur Singkatnya:');
$pdf->Paragraph('Pilih Jenis Surat --> Isi Form --> Kirim --> Tunggu Admin Proses --> Download PDF');

$pdf->Note('Kamu bisa cek status surat kapan aja di halaman "Riwayat Pengajuan".');
$pdf->Note('Kalau ada yang salah isi, hubungi admin buat dibatalin lalu ajukan ulang.');

// ===== BAGIAN 7: PANDUAN ADMIN =====
$pdf->AddPage();
$pdf->SectionTitle('7', 'Panduan Admin - Kelola Surat');

$pdf->Paragraph('Admin itu yang ngurusin semua surat yang masuk dari mahasiswa. Tugasnya: cek surat, proses, dan ubah statusnya biar mahasiswa bisa download PDF-nya.');

$pdf->SubTitle('Fitur yang Bisa Dipakai Admin:');
$pdf->Step('1', 'Daftar Pengajuan - Lihat semua surat yang masuk dari mahasiswa.');
$pdf->Step('2', 'Verifikasi - Ubah status surat (Pending -> Diproses -> Selesai).');
$pdf->Step('3', 'Kelola Mading - Bikin pengumuman buat mahasiswa.');
$pdf->Step('4', 'Rekap Laporan - Lihat rekap surat per bulan (ada grafiknya).');
$pdf->Step('5', 'Statistik - Lihat tren pengajuan 12 bulan terakhir.');
$pdf->Step('6', 'Backup & Restore - Backup database biar aman, atau restore kalau ada masalah.');

$pdf->Ln(3);
$pdf->SubTitle('Cara Proses Surat Mahasiswa:');
$pdf->Step('1', 'Login sebagai admin.');
$pdf->Step('2', 'Buka halaman "Daftar Pengajuan" atau "Verifikasi".');
$pdf->Step('3', 'Klik tombol "Detail" di surat yang mau diproses.');
$pdf->Step('4', 'Ubah status jadi "Diproses" atau langsung "Selesai".');
$pdf->Step('5', 'Tambahkan catatan kalau perlu (opsional).');
$pdf->Step('6', 'Klik "Simpan".');
$pdf->Step('7', 'Kalau status sudah "Selesai", mahasiswa bisa download PDF suratnya.');

$pdf->Ln(2);
$pdf->Note('Surat baru bisa dicetak PDF kalau statusnya sudah "Selesai".');
$pdf->Note('Nomor surat otomatis dibuat oleh sistem saat PDF di-generate.');

// ===== BAGIAN 8: PANDUAN DEKAN =====
$pdf->AddPage();
$pdf->SectionTitle('8', 'Panduan Dekan - Lihat Surat');

$pdf->Paragraph('Dekan atau Wakil Dekan bisa lihat semua surat yang diajukan mahasiswa. Fungsinya buat monitoring aja, biar tau apa yang terjadi di fakultas.');

$pdf->SubTitle('Yang Bisa Dilakukan Dekan:');
$pdf->Step('1', 'Lihat daftar semua pengajuan surat.');
$pdf->Step('2', 'Cari surat berdasarkan nama, NIM, atau jenis surat.');
$pdf->Step('3', 'Filter surat berdasarkan status (Pending/Diproses/Selesai).');
$pdf->Step('4', 'Lihat detail lengkap setiap surat.');
$pdf->Step('5', 'Buka pratinjau PDF surat yang sudah selesai.');

$pdf->Ln(2);
$pdf->Note('Dekan tidak bisa mengubah status surat. Yang bisa ubah status cuma Admin.');

// ===== BAGIAN 9: CETAK PDF =====
$pdf->Ln(5);
$pdf->SectionTitle('9', 'Cara Cetak Surat jadi PDF');

$pdf->Paragraph('Surat yang sudah berstatus "Selesai" bisa dicetak jadi file PDF. PDF-nya lengkap: ada kop surat resmi, tanda tangan dekan, dan QR Code buat verifikasi keaslian.');

$pdf->SubTitle('Cara Cetak:');
$pdf->Step('1', 'Pastikan surat statusnya sudah "Selesai".');
$pdf->Step('2', 'Klik tombol hijau "PDF" atau ikon printer di samping surat.');
$pdf->Step('3', 'PDF akan terbuka di tab baru browser.');
$pdf->Step('4', 'Mau simpan? Klik ikon download atau tekan Ctrl+S.');
$pdf->Step('5', 'Mau cetak ke kertas? Tekan Ctrl+P lalu pilih printer.');

$pdf->Ln(3);
$pdf->SubTitle('Soal QR Code di Surat:');
$pdf->Paragraph('Setiap surat PDF punya QR Code di bagian bawah (dekat tanda tangan). QR Code ini bisa di-scan pakai kamera HP buat ngecek apakah surat itu asli atau palsu.');
$pdf->Note('Biar QR Code bisa di-scan dan halaman verifikasi kebuka, HP harus konek ke WiFi yang sama dengan laptop server.');

// ===== BAGIAN 10: TROUBLESHOOTING =====
$pdf->AddPage();
$pdf->SectionTitle('10', 'Kalau Ada Masalah (Troubleshooting)');

$pdf->Paragraph('Jangan panik kalau ada yang gak beres. Cek daftar masalah umum di bawah ini:');

$pdf->SubTitle('Website gak bisa dibuka di laptop:');
$pdf->Step('1', 'Cek XAMPP sudah nyala belum? Apache dan MySQL harus HIJAU.');
$pdf->Step('2', 'Cek alamatnya bener: http://localhost/TA_ULM/');
$pdf->Step('3', 'Kalau pakai port 8080: http://localhost:8080/TA_ULM/');
$pdf->Step('4', 'Pastikan folder project ada di: C:/xampp/htdocs/TA_ULM/');

$pdf->Ln(3);
$pdf->SubTitle('Website gak bisa dibuka di HP:');
$pdf->Step('1', 'HP dan laptop HARUS di WiFi yang SAMA. Cek ulang!');
$pdf->Step('2', 'IP Address bener gak? Cek lagi pakai ipconfig.');
$pdf->Step('3', 'Firewall mungkin nge-block. Buka firewall (lihat Bagian 4).');
$pdf->Step('4', 'Kalau router punya fitur "AP Isolation", matikan fitur itu.');
$pdf->Step('5', 'Coba matikan antivirus sementara (kadang antivirus nge-block juga).');

$pdf->Ln(3);
$pdf->SubTitle('PDF error / gambar gak muncul:');
$pdf->Step('1', 'Buka file php.ini di folder C:/xampp/php/php.ini');
$pdf->Step('2', 'Cari baris: ;extension=gd');
$pdf->Step('3', 'Hapus tanda titik koma (;) di depannya, jadi: extension=gd');
$pdf->Step('4', 'Simpan file, lalu RESTART Apache di XAMPP (Stop dulu, Start lagi).');

$pdf->Ln(3);
$pdf->SubTitle('QR Code gak bisa di-scan:');
$pdf->Step('1', 'Pakai kamera bawaan HP (jangan app QR scanner pihak ketiga).');
$pdf->Step('2', 'Pastikan HP konek WiFi yang sama dengan laptop.');
$pdf->Step('3', 'Coba generate ulang PDF-nya (buka lagi link cetak).');

$pdf->Ln(3);
$pdf->SubTitle('Lupa password:');
$pdf->Paragraph('Hubungi admin. Admin bisa reset password atau buatkan akun baru.');

$pdf->Ln(3);
$pdf->SubTitle('Apache gagal Start (warna merah):');
$pdf->Step('1', 'Port 80 kemungkinan sudah dipakai program lain.');
$pdf->Step('2', 'Ganti port ke 8080 (caranya ada di Bagian 1).');
$pdf->Step('3', 'Atau matikan program yang pakai port 80: Skype, IIS, atau program server lain.');

// ===== HALAMAN PENUTUP =====
$pdf->AddPage();
$pdf->Ln(40);
$pdf->SetFont('Arial', 'B', 18);
$pdf->SetTextColor(26, 26, 46);
$pdf->Cell(0, 12, 'Selesai!', 0, 1, 'C');
$pdf->Ln(5);
$pdf->SetFont('Arial', '', 12);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(0, 8, 'Kalau kamu ikutin langkah-langkah di atas satu per satu,', 0, 1, 'C');
$pdf->Cell(0, 8, 'pasti bisa kok. Gak susah!', 0, 1, 'C');
$pdf->Ln(10);
$pdf->SetFont('Arial', '', 11);
$pdf->SetTextColor(120, 120, 120);
$pdf->Cell(0, 7, 'Kalau masih bingung, tanya teman yang ngerti atau hubungi admin.', 0, 1, 'C');
$pdf->Ln(20);
$pdf->SetFont('Arial', 'I', 9);
$pdf->SetTextColor(180, 180, 180);
$pdf->Cell(0, 6, 'Dokumen ini dibuat otomatis oleh sistem.', 0, 1, 'C');
$pdf->Cell(0, 6, 'Fakultas Pertanian - Universitas Lambung Mangkurat', 0, 1, 'C');
$pdf->Cell(0, 6, date('d F Y'), 0, 1, 'C');

// ===== OUTPUT =====
$pdf->Output('I', 'Panduan_Penggunaan_Sistem_Surat_Faperta_ULM.pdf');

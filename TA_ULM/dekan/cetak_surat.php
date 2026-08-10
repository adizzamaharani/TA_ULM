<?php
session_start();
require '../config/database.php';
require '../libs/fpdf/fpdf.php';

// Cek akses
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['dekan', 'wakil_dekan'])) {
    die("Akses ditolak.");
}

$id = $_GET['id'] ?? null;
if (!$id) die("ID Surat tidak ditemukan.");

// Ambil data
$query = "SELECT s.*, u.nama_lengkap, u.username as nim FROM surat s JOIN users u ON s.user_id = u.id WHERE s.id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$id]);
$surat = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$surat) die("Data tidak ditemukan.");

// Inisialisasi PDF
$pdf = new FPDF('P','mm','A4');
$pdf->AddPage();

// Header
if (file_exists('../assets/img/logo_ulm.png')) {
    $pdf->Image('../assets/img/logo_ulm.png', 10, 10, 25);
}
$pdf->SetFont('Times','B',14);
$pdf->Cell(0,5,'KEMENTERIAN PENDIDIKAN, KEBUDAYAAN,',0,1,'C');
$pdf->Cell(0,5,'RISET, DAN TEKNOLOGI',0,1,'C');
$pdf->SetFont('Times','B',16);
$pdf->Cell(0,7,'UNIVERSITAS LAMBUNG MANGKURAT',0,1,'C');
$pdf->SetFont('Times','',12);
$pdf->Cell(0,5,'Jalan Brigjen H. Hasan Basry, Kayu Tangi, Banjarmasin',0,1,'C');
$pdf->Line(10, 38, 200, 38);
$pdf->Ln(15);

// Judul Surat
$pdf->SetFont('Times','BU',14);
$pdf->Cell(0,10, strtoupper($surat['jenis_surat']),0,1,'C');
$pdf->SetFont('Times','',12);
$pdf->Cell(0,5,'Nomor: ' . $surat['id'] . '/UN8/KM/'.date('Y'),0,1,'C');
$pdf->Ln(10);

// Isi Surat
$pdf->MultiCell(0,8,"Yang bertanda tangan di bawah ini, Dekan Fakultas Universitas Lambung Mangkurat menerangkan bahwa:",0,'J');
$pdf->Ln(5);

$pdf->Cell(40,8,'Nama',0,0); $pdf->Cell(5,8,':',0,0); $pdf->Cell(0,8,$surat['nama_lengkap'],0,1);
$pdf->Cell(40,8,'NIM',0,0); $pdf->Cell(5,8,':',0,0); $pdf->Cell(0,8,$surat['nim'],0,1);
$pdf->Cell(40,8,'Keperluan',0,0); $pdf->Cell(5,8,':',0,0); $pdf->Cell(0,8,$surat['keperluan'],0,1);

$pdf->Ln(5);
$pdf->MultiCell(0,8,"Demikian surat keterangan ini dibuat untuk dipergunakan sebagaimana mestinya.",0,'J');

// Tanda Tangan
$pdf->Ln(20);
$pdf->Cell(120);
$pdf->Cell(0,5,'Banjarmasin, ' . date('d F Y'),0,1,'C');
$pdf->Cell(120);
$pdf->Cell(0,5,'Dekan,',0,1,'C');
$pdf->Ln(25);
$pdf->Cell(120);
$pdf->Cell(0,5,'( ' . $_SESSION['nama_lengkap'] . ' )',0,1,'C');

$pdf->Output();
?>
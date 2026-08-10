<?php
session_start();
require '../config/database.php';

// Atur Timezone agar aman (opsional, tapi bagus untuk konsistensi)
date_default_timezone_set('Asia/Makassar');

// Proteksi: pastikan user sudah login dan memiliki role yang valid
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['user', 'dekan', 'wakil_dekan'])) {
    header('Location: ../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil semua data dari form
    $user_id = $_SESSION['user_id'];
    $jenis_surat = $_POST['jenis_surat'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $nim = $_POST['nim'];
    $keperluan = $_POST['keperluan'];

    // Ambil data opsional dengan aman
    $semester = isset($_POST['semester']) && !empty($_POST['semester']) ? $_POST['semester'] : null;
    $dosen_pembimbing = isset($_POST['dosen_pembimbing']) ? $_POST['dosen_pembimbing'] : null;
    
    $tanggal_mulai = isset($_POST['tanggal_mulai']) && !empty($_POST['tanggal_mulai']) ? $_POST['tanggal_mulai'] : null;
    $tanggal_selesai = isset($_POST['tanggal_selesai']) && !empty($_POST['tanggal_selesai']) ? $_POST['tanggal_selesai'] : null;
    $durasi_magang = isset($_POST['durasi_magang']) ? $_POST['durasi_magang'] : null;

    try {
        // --- PERBAIKAN DISINI ---
        // Tambahkan kolom 'tanggal_pengajuan' dan 'status'
        // Gunakan fungsi NOW() dari MySQL untuk mengisi tanggal otomatis real-time
        
        $sql = "INSERT INTO surat (
                    user_id, 
                    jenis_surat, 
                    nama_lengkap, 
                    nim, 
                    semester, 
                    keperluan, 
                    dosen_pembimbing, 
                    tanggal_mulai, 
                    tanggal_selesai, 
                    durasi_magang,
                    tanggal_pengajuan,  
                    status
                ) 
                VALUES (
                    :user_id, 
                    :jenis_surat, 
                    :nama_lengkap, 
                    :nim, 
                    :semester, 
                    :keperluan, 
                    :dosen_pembimbing, 
                    :tanggal_mulai, 
                    :tanggal_selesai, 
                    :durasi_magang,
                    NOW(),             
                    'Pending'
                )";
        
        $stmt = $pdo->prepare($sql);

        // Bind semua parameter
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':jenis_surat', $jenis_surat);
        $stmt->bindParam(':nama_lengkap', $nama_lengkap);
        $stmt->bindParam(':nim', $nim);
        $stmt->bindParam(':semester', $semester);
        $stmt->bindParam(':keperluan', $keperluan);
        $stmt->bindParam(':dosen_pembimbing', $dosen_pembimbing, PDO::PARAM_STR);
        $stmt->bindParam(':tanggal_mulai', $tanggal_mulai);
        $stmt->bindParam(':tanggal_selesai', $tanggal_selesai);
        $stmt->bindParam(':durasi_magang', $durasi_magang);

        $stmt->execute();

        // Arahkan kembali ke dashboard yang sesuai dengan pesan sukses
        if (in_array($_SESSION['role'], ['dekan', 'wakil_dekan'])) {
            header("Location: ../dekan/index.php?status=pengajuan_sukses");
        } else {
            header("Location: index.php?status=pengajuan_sukses");
        }
        exit();
    } catch (PDOException $e) {
        die("Error saat menyimpan pengajuan: " . $e->getMessage());
    }
} else {
    if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['dekan', 'wakil_dekan'])) {
        header("Location: ../dekan/index.php");
    } else {
        header("Location: index.php");
    }
    exit();
}
?>
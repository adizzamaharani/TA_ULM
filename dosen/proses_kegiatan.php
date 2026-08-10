<?php
session_start();
require '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dosen') {
        header('Location: ../auth/login.php?error=access_denied');
        exit();
    }

    $dosen_id = $_SESSION['user_id'];
    $nama_kegiatan = $_POST['nama_kegiatan'];
    $jenis_kegiatan = $_POST['jenis_kegiatan'];
    $deskripsi = $_POST['deskripsi'];
    $tanggal_kegiatan = $_POST['tanggal_kegiatan'];
    $lokasi = $_POST['lokasi'];
    $mahasiswa_ids = $_POST['mahasiswa_id'] ?? []; // Array of user_id

    if (empty($mahasiswa_ids)) {
        die("Error: Anda harus memilih setidaknya satu mahasiswa.");
    }

    // Upload file lampiran (opsional)
    $lampiran_name = null;
    if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] == 0) {
        $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
        $file_info = pathinfo($_FILES['lampiran']['name']);
        $file_ext = strtolower($file_info['extension']);
        $file_size = $_FILES['lampiran']['size'];
        
        if (in_array($file_ext, $allowed_ext) && $file_size <= 2097152) { // max 2MB
            $lampiran_name = 'kegiatan_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            $upload_path = '../assets/uploads/lampiran/';
            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0777, true);
            }
            move_uploaded_file($_FILES['lampiran']['tmp_name'], $upload_path . $lampiran_name);
        } else {
            die("Error: File lampiran tidak valid atau terlalu besar.");
        }
    }

    try {
        $pdo->beginTransaction();

        // 1. Insert ke kegiatan_akademik
        $stmt = $pdo->prepare("INSERT INTO kegiatan_akademik (dosen_id, nama_kegiatan, jenis_kegiatan, deskripsi, tanggal_kegiatan, lokasi, lampiran, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
        $stmt->execute([$dosen_id, $nama_kegiatan, $jenis_kegiatan, $deskripsi, $tanggal_kegiatan, $lokasi, $lampiran_name]);
        $kegiatan_id = $pdo->lastInsertId();

        // 2. Insert ke kegiatan_mahasiswa
        $stmt_mhs = $pdo->prepare("INSERT INTO kegiatan_mahasiswa (kegiatan_id, mahasiswa_id) VALUES (?, ?)");
        foreach ($mahasiswa_ids as $mhs_id) {
            $stmt_mhs->execute([$kegiatan_id, $mhs_id]);
        }

        $pdo->commit();
        header('Location: index.php?success=1');
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error: Gagal menyimpan data kegiatan. " . $e->getMessage());
    }
} else {
    header('Location: buat_kegiatan.php');
    exit();
}

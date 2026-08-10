<?php
session_start();
require '../config/database.php';

// Proteksi: pastikan admin sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = $_POST['judul'];
    $isi = $_POST['isi'];
    $user_id = $_SESSION['user_id'];
    $nama_gambar = null;

    // --- LOGIKA UPLOAD GAMBAR ---
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $target_dir = "../assets/uploads/";
        // Buat folder jika belum ada
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        $file = $_FILES['gambar'];
        $max_size = 2 * 1024 * 1024; // 2MB
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        
        // Validasi ukuran dan tipe file
        if ($file['size'] > $max_size) {
            header("Location: mading.php?status=gagal&error=filesize");
            exit();
        }
        if (!in_array($file['type'], $allowed_types)) {
            header("Location: mading.php?status=gagal&error=filetype");
            exit();
        }

        // Buat nama file unik untuk menghindari tumpang tindih
        $nama_gambar = uniqid() . '-' . basename($file['name']);
        $target_file = $target_dir . $nama_gambar;

        // Pindahkan file yang diunggah
        if (!move_uploaded_file($file['tmp_name'], $target_file)) {
            header("Location: mading.php?status=gagal&error=upload");
            exit();
        }
    }

    // --- SIMPAN KE DATABASE ---
    try {
        $sql = "INSERT INTO mading (judul, isi, gambar, user_id) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$judul, $isi, $nama_gambar, $user_id]);

        header("Location: mading.php?status=sukses");
        exit();
    } catch (PDOException $e) {
        // die("Error: " . $e->getMessage()); // Untuk debugging
        header("Location: mading.php?status=gagal&error=db");
        exit();
    }
} else {
    header("Location: mading.php");
    exit();
}
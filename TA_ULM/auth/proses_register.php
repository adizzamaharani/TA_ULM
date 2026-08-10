<?php
require '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = $_POST['nama_lengkap'];
    $username = $_POST['username']; // Menggunakan NIM sebagai username
    $password = $_POST['password'];
    $prodi_id = $_POST['prodi_id'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Validasi Password
    if ($password !== $confirm_password) {
        header("Location: register.php?error=password_mismatch");
        exit();
    }

    // 2. Cek apakah username sudah ada
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        header("Location: register.php?error=username_exists");
        exit();
    }

    // 3. Hash password
    // Ini adalah cara yang aman untuk menyimpan password
    $hashed_password = $password;

    // 4. Masukkan user baru ke database
    // Secara default, role adalah 'user'
    try {
        $stmt = $pdo->prepare("INSERT INTO users (nama_lengkap, prodi_id, username, password, role) VALUES (?, ?, ?, ?, 'user')");
        $stmt->execute([$nama_lengkap, $prodi_id, $username, $hashed_password]);

        // 5. Arahkan ke halaman login dengan pesan sukses
        header("Location: login.php?status=register_success");
        exit();
    } catch (PDOException $e) {
        // Jika terjadi error saat insert, tampilkan pesan (opsional, bisa diganti redirect)
        die("Error saat registrasi: " . $e->getMessage());
    }

} else {
    // Jika halaman diakses langsung, kembalikan ke halaman registrasi
    header("Location: register.php");
    exit();
}
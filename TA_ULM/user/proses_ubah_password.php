<?php
session_start();
require '../config/database.php';

// Proteksi: Pastikan user login dan form disubmit
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$password_lama = $_POST['password_lama'];
$password_baru = $_POST['password_baru'];
$konfirmasi_password = $_POST['konfirmasi_password'];

// 1. Validasi Input
// Cek apakah password baru dan konfirmasi cocok
if ($password_baru !== $konfirmasi_password) {
    header('Location: profil.php?password_status=error_mismatch');
    exit();
}

// Cek panjang password baru (minimal 6 karakter)
if (strlen($password_baru) < 6) {
    header('Location: profil.php?password_status=error_short');
    exit();
}

// 2. Verifikasi Password Lama
// Ambil hash password saat ini dari database
$stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    // User tidak ditemukan, seharusnya tidak terjadi jika sesi valid
    header('Location: profil.php?password_status=error_db');
    exit();
}

// Verifikasi password lama dengan hash di database
if (!password_verify($password_lama, $user['password'])) {
    header('Location: profil.php?password_status=error_old_password');
    exit();
}

// 3. Update Password Baru
// Hash password baru sebelum disimpan
$hash_password_baru = password_hash($password_baru, PASSWORD_DEFAULT);

$update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
$update_success = $update_stmt->execute([$hash_password_baru, $user_id]);

if ($update_success) {
    header('Location: profil.php?password_status=success');
} else {
    header('Location: profil.php?password_status=error_db');
}
exit();
?>
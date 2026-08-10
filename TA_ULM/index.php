<?php
session_start();

// Cek jika user sudah login
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    // Arahkan berdasarkan role
    if ($_SESSION['role'] == 'admin') {
        header('Location: admin/index.php');
        exit();
    } elseif ($_SESSION['role'] == 'user') {
        header('Location: user/index.php');
        exit();
    }
}

// Jika tidak ada sesi atau role tidak dikenali, arahkan ke halaman login
header('Location: auth/login.php');
exit();
?>

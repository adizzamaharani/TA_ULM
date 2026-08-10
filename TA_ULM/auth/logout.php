<?php
// Selalu mulai sesi di awal
session_start();

// 1. Unset semua variabel sesi
$_SESSION = array();

// 2. Hancurkan sesi
session_destroy();

// 3. Arahkan pengguna kembali ke halaman login dengan pesan sukses (opsional)
header("Location: login.php?status=logout_success");
exit();
?>
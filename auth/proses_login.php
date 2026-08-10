<?php
session_start();
require '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Cari user berdasarkan username (NIM/NIP)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verifikasi user dan password (mendukung hash bcrypt dari reset_db.php dan plaintext dari seed_db.php)
    if ($user && (password_verify($password, $user['password']) || $password === $user['password'])) {
        // Regenerasi session ID untuk keamanan
        session_regenerate_id(true);

        // Simpan data penting ke dalam session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['role'] = $user['role'];

        // Arahkan user berdasarkan role menggunakan switch
        switch ($user['role']) {
            case 'admin':
                header('Location: ../admin/index.php');
                break;
            case 'dekan':
                header('Location: ../dekan/index.php'); // Dasbor baru untuk Dekan
                break;
            case 'wakil_dekan':
                header('Location: ../wakil_dekan/index.php'); // Dasbor baru untuk Wakil Dekan
                break;
            case 'dosen':
                header('Location: ../dosen/index.php'); // Dasbor untuk Dosen
                break;
            case 'user':
            default:
                header('Location: ../user/index.php');
                break;
        }
        exit();
    } else {
        // Jika login gagal
        header('Location: login.php?error=invalid_credentials');
        exit();
    }
}
?>
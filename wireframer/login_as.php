<?php
session_start();
require '../config/database.php';
$role = $_GET['role'] ?? 'dosen';
$stmt = $pdo->prepare("SELECT * FROM users WHERE role = ? LIMIT 1");
$stmt->execute([$role]);
$user = $stmt->fetch();
if ($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $role;
    $_SESSION['nama_lengkap'] = $user['nama_lengkap'] ?? 'Admin Test';
    $_SESSION['username'] = $user['username'] ?? 'username';
    echo "Logged in as " . $role;
} else {
    echo "User not found";
}

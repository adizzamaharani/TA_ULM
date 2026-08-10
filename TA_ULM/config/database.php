<?php
date_default_timezone_set('Asia/Jakarta');

$host = 'sql210.infinityfree.com';       // ← Ganti sesuai info dari panel InfinityFree
$dbname = 'if0_42242855_db_surat_ulm';   // ← Ganti sesuai nama database kamu
$user = 'if0_42242855';                   // ← Ganti sesuai username database kamu
$pass = 'CM90dY4stzRQ';         // ← Ganti sesuai password database kamu

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi ke database gagal: " . $e->getMessage());
}
?>

<?php
session_start();
require '../config/database.php';
date_default_timezone_set('Asia/Makassar');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php?error=access_denied');
    exit();
}

$backup_dir = __DIR__ . '/../assets/uploads/backups';
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}

// ==========================================
// AKSI: Download file backup yang sudah ada
// ==========================================
if (isset($_GET['download'])) {
    $filename = basename($_GET['download']); // Sanitasi
    $filepath = $backup_dir . '/' . $filename;
    if (file_exists($filepath) && pathinfo($filepath, PATHINFO_EXTENSION) === 'sql') {
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit();
    } else {
        header('Location: backup_restore.php?status=file_not_found');
        exit();
    }
}

// ==========================================
// AKSI: Hapus file backup
// ==========================================
if (isset($_GET['delete'])) {
    $filename = basename($_GET['delete']);
    $filepath = $backup_dir . '/' . $filename;
    if (file_exists($filepath) && pathinfo($filepath, PATHINFO_EXTENSION) === 'sql') {
        unlink($filepath);
        header('Location: backup_restore.php?status=deleted');
    } else {
        header('Location: backup_restore.php?status=file_not_found');
    }
    exit();
}

// ==========================================
// AKSI: Buat Backup Baru (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? 'download';
    $tables = ['program_studi', 'users', 'mading', 'surat'];
    $sql_output = "";

    // Header
    $sql_output .= "-- ============================================\n";
    $sql_output .= "-- BACKUP DATABASE: db_surat_ulm\n";
    $sql_output .= "-- Tanggal: " . date('Y-m-d H:i:s') . " WITA\n";
    $sql_output .= "-- Dibuat oleh: " . ($_SESSION['username'] ?? 'admin') . "\n";
    $sql_output .= "-- ============================================\n\n";
    $sql_output .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $sql_output .= "SET FOREIGN_KEY_CHECKS = 0;\n";
    $sql_output .= "START TRANSACTION;\n";
    $sql_output .= "SET time_zone = \"+00:00\";\n\n";

    foreach ($tables as $table) {
        // DROP TABLE
        $sql_output .= "-- --------------------------------------------------------\n";
        $sql_output .= "-- Struktur tabel `$table`\n";
        $sql_output .= "-- --------------------------------------------------------\n\n";
        $sql_output .= "DROP TABLE IF EXISTS `$table`;\n";

        // CREATE TABLE
        $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $sql_output .= $row['Create Table'] . ";\n\n";

        // INSERT DATA
        $stmt = $pdo->query("SELECT * FROM `$table`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($rows)) {
            $columns = array_keys($rows[0]);
            $col_list = '`' . implode('`, `', $columns) . '`';
            $sql_output .= "INSERT INTO `$table` ($col_list) VALUES\n";

            $values_list = [];
            foreach ($rows as $data_row) {
                $vals = [];
                foreach ($data_row as $val) {
                    if ($val === null) {
                        $vals[] = 'NULL';
                    } else {
                        $vals[] = $pdo->quote($val);
                    }
                }
                $values_list[] = '(' . implode(', ', $vals) . ')';
            }
            $sql_output .= implode(",\n", $values_list) . ";\n\n";
        }
    }

    $sql_output .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    $sql_output .= "COMMIT;\n";

    $timestamp = date('Y-m-d_H-i-s');
    $filename = "backup_db_surat_ulm_{$timestamp}.sql";

    if ($mode === 'save') {
        // Simpan ke server
        file_put_contents($backup_dir . '/' . $filename, $sql_output);
        header('Location: backup_restore.php?status=backup_saved');
        exit();
    } else {
        // Download langsung
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($sql_output));
        echo $sql_output;
        exit();
    }
}

// Jika tidak ada aksi
header('Location: backup_restore.php');
exit();

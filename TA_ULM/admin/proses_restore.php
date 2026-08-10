<?php
session_start();
require '../config/database.php';
date_default_timezone_set('Asia/Makassar');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php?error=access_denied');
    exit();
}

$backup_dir = __DIR__ . '/../assets/uploads/backups';

// ==========================================
// RESTORE DARI FILE SERVER
// ==========================================
if (isset($_GET['server_file'])) {
    $filename = basename($_GET['server_file']);
    $filepath = $backup_dir . '/' . $filename;

    if (!file_exists($filepath) || pathinfo($filepath, PATHINFO_EXTENSION) !== 'sql') {
        header('Location: backup_restore.php?status=restore_error&msg=' . urlencode('File tidak ditemukan.'));
        exit();
    }

    $sql_content = file_get_contents($filepath);
    $result = execute_restore($pdo, $sql_content);

    if ($result === true) {
        header('Location: backup_restore.php?status=restore_success');
    } else {
        header('Location: backup_restore.php?status=restore_error&msg=' . urlencode($result));
    }
    exit();
}

// ==========================================
// RESTORE DARI FILE UPLOAD
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['sql_file'])) {
    $file = $_FILES['sql_file'];

    // Validasi
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File terlalu besar (melebihi limit server).',
            UPLOAD_ERR_FORM_SIZE => 'File terlalu besar.',
            UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian.',
            UPLOAD_ERR_NO_FILE => 'Tidak ada file yang dipilih.',
        ];
        $msg = $errors[$file['error']] ?? 'Error upload tidak diketahui.';
        header('Location: backup_restore.php?status=restore_error&msg=' . urlencode($msg));
        exit();
    }

    if ($file['size'] > 10 * 1024 * 1024) {
        header('Location: backup_restore.php?status=restore_error&msg=' . urlencode('Ukuran file melebihi 10MB.'));
        exit();
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'sql') {
        header('Location: backup_restore.php?status=restore_error&msg=' . urlencode('Hanya file .sql yang diizinkan.'));
        exit();
    }

    $sql_content = file_get_contents($file['tmp_name']);

    if (empty(trim($sql_content))) {
        header('Location: backup_restore.php?status=restore_error&msg=' . urlencode('File SQL kosong.'));
        exit();
    }

    $result = execute_restore($pdo, $sql_content);

    if ($result === true) {
        header('Location: backup_restore.php?status=restore_success');
    } else {
        header('Location: backup_restore.php?status=restore_error&msg=' . urlencode($result));
    }
    exit();
}

// ==========================================
// FUNGSI RESTORE
// ==========================================
function execute_restore($pdo, $sql_content) {
    try {
        // Nonaktifkan foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

        // Pisahkan statement SQL berdasarkan titik koma
        // Tapi harus hati-hati dengan titik koma di dalam string
        $statements = [];
        $current = '';
        $in_string = false;
        $string_char = '';
        $escaped = false;

        for ($i = 0; $i < strlen($sql_content); $i++) {
            $char = $sql_content[$i];

            if ($escaped) {
                $current .= $char;
                $escaped = false;
                continue;
            }

            if ($char === '\\') {
                $current .= $char;
                $escaped = true;
                continue;
            }

            if ($in_string) {
                $current .= $char;
                if ($char === $string_char) {
                    $in_string = false;
                }
                continue;
            }

            if ($char === "'" || $char === '"') {
                $in_string = true;
                $string_char = $char;
                $current .= $char;
                continue;
            }

            // Skip komentar satu baris
            if ($char === '-' && isset($sql_content[$i + 1]) && $sql_content[$i + 1] === '-') {
                $end = strpos($sql_content, "\n", $i);
                if ($end === false) break;
                $i = $end;
                continue;
            }

            // Skip komentar multi-baris
            if ($char === '/' && isset($sql_content[$i + 1]) && $sql_content[$i + 1] === '*') {
                // Cek apakah ini conditional comment (/*! ... */)
                if (isset($sql_content[$i + 2]) && $sql_content[$i + 2] === '!') {
                    // Ini conditional comment MySQL, skip sampai */
                    $end = strpos($sql_content, '*/', $i);
                    if ($end === false) break;
                    $i = $end + 1;
                    continue;
                }
                $end = strpos($sql_content, '*/', $i);
                if ($end === false) break;
                $i = $end + 1;
                continue;
            }

            if ($char === ';') {
                $trimmed = trim($current);
                if (!empty($trimmed)) {
                    $statements[] = $trimmed;
                }
                $current = '';
                continue;
            }

            $current .= $char;
        }

        // Statement terakhir tanpa titik koma
        $trimmed = trim($current);
        if (!empty($trimmed)) {
            $statements[] = $trimmed;
        }

        // Eksekusi setiap statement
        $pdo->beginTransaction();

        foreach ($statements as $stmt) {
            $upper = strtoupper(trim($stmt));
            // Skip SET, START TRANSACTION, COMMIT yang sudah kita handle
            if (strpos($upper, 'SET') === 0 || strpos($upper, 'START TRANSACTION') === 0 || $upper === 'COMMIT') {
                continue;
            }
            $pdo->exec($stmt);
        }

        $pdo->commit();
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

        return true;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        return 'Error SQL: ' . $e->getMessage();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        return 'Error: ' . $e->getMessage();
    }
}

header('Location: backup_restore.php');
exit();

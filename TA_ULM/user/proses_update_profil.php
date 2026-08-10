<?php
session_start();
require '../config/database.php';

// Proteksi: pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $email = $_POST['email'];
    $telepon = $_POST['telepon'];

    // --- LOGIKA UPLOAD FOTO PROFIL ---
    $nama_foto = null;
    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] == 0) {
        $target_dir = "../assets/uploads/profil/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        $file = $_FILES['foto_profil'];
        $max_size = 2 * 1024 * 1024; // 2MB
        $allowed_types = ['image/jpeg', 'image/png'];

        if ($file['size'] > $max_size || !in_array($file['type'], $allowed_types)) {
            header("Location: profil.php?status=update_failed&error=file");
            exit();
        }

        // Hapus foto lama jika ada
        $stmt_old_foto = $pdo->prepare("SELECT foto_profil FROM users WHERE id = ?");
        $stmt_old_foto->execute([$user_id]);
        $old_foto = $stmt_old_foto->fetchColumn();
        if ($old_foto && file_exists($target_dir . $old_foto)) {
            unlink($target_dir . $old_foto);
        }

        // Buat nama file unik
        $nama_foto = 'user_' . $user_id . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $target_file = $target_dir . $nama_foto;

        if (!move_uploaded_file($file['tmp_name'], $target_file)) {
            header("Location: profil.php?status=update_failed&error=upload");
            exit();
        }
    }

    try {
        // Update data kontak dan foto profil jika ada foto baru yang diunggah
        if ($nama_foto) {
            $stmt = $pdo->prepare("UPDATE users SET email = ?, telepon = ?, foto_profil = ? WHERE id = ?");
            $stmt->execute([$email, $telepon, $nama_foto, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET email = ?, telepon = ? WHERE id = ?");
            $stmt->execute([$email, $telepon, $user_id]);
        }

        header("Location: profil.php?status=update_success");
        exit();
    } catch (PDOException $e) {
        // die("Error: " . $e->getMessage()); // Untuk debugging
        header("Location: profil.php?status=update_failed");
        exit();
    }
}
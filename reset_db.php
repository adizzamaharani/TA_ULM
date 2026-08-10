<?php
require 'config/database.php';

try {
    // Disable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // Truncate transaction tables
    $pdo->exec("TRUNCATE TABLE surat");
    $pdo->exec("TRUNCATE TABLE kegiatan_akademik");
    $pdo->exec("TRUNCATE TABLE kegiatan_mahasiswa");
    
    // Clear users
    $pdo->exec("TRUNCATE TABLE users");

    // Enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "Tables truncated.\n";

    // Re-seed Users
    $password_mhs = password_hash('mahasiswa123', PASSWORD_DEFAULT);

    // Get a valid prodi_id
    $stmt = $pdo->query("SELECT id FROM program_studi LIMIT 1");
    $prodi = $stmt->fetch();
    $prodi_id = $prodi ? $prodi['id'] : null;

    $stmt = $pdo->prepare("INSERT INTO users (username, password, role, nama_lengkap, prodi_id) VALUES (?, ?, ?, ?, ?)");

    // 1. Admin
    $stmt->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT), 'admin', 'Administrator Utama', null]);
    
    // 2. Dekan
    $stmt->execute(['dekan', password_hash('dekan123', PASSWORD_DEFAULT), 'dekan', 'Prof. Ir. Akhmad Rizali Saidy', null]);
    
    // 3. Dosen
    $stmt->execute(['dosen', password_hash('dosen123', PASSWORD_DEFAULT), 'dosen', 'Dr. Budi Santoso, M.Si', null]);

    // 4. Mahasiswa (10)
    $mahasiswa_data = [
        ['20101111', 'Ahmad Faisal'],
        ['20101112', 'Siti Nurhaliza'],
        ['20101113', 'Budi Darmawan'],
        ['20101114', 'Rina Melati'],
        ['20101115', 'Andi Saputra'],
        ['20101116', 'Dewi Lestari'],
        ['20101117', 'Reza Rahadian'],
        ['20101118', 'Aulia Rahman'],
        ['20101119', 'Dimas Anggara'],
        ['20101120', 'Fitriani Hidayah']
    ];

    foreach ($mahasiswa_data as $mhs) {
        $stmt->execute([
            $mhs[0], // username (NIM)
            $password_mhs,
            'user',
            $mhs[1], // nama_lengkap
            $prodi_id
        ]);
    }

    echo "1 Admin, 1 Dekan, 1 Dosen, and 10 Mahasiswa successfully seeded.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

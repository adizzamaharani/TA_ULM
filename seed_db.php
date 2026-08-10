<?php
require 'config/database.php';

try {
    echo "Starting database reset...\n";

    // 1. Disable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // 2. Truncate transaction and user tables
    $pdo->exec("TRUNCATE TABLE surat");
    $pdo->exec("TRUNCATE TABLE kegiatan_akademik");
    $pdo->exec("TRUNCATE TABLE kegiatan_mahasiswa");
    $pdo->exec("TRUNCATE TABLE mading");
    $pdo->exec("TRUNCATE TABLE users");

    // 3. Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "Existing transaction and user tables truncated successfully.\n";

    // 4. Get available Program Studi IDs
    $stmt_prodi = $pdo->query("SELECT id FROM program_studi");
    $prodi_ids = $stmt_prodi->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($prodi_ids)) {
        // Fallback prodi_id if program_studi is empty
        $pdo->exec("INSERT INTO program_studi (nama_prodi, nama_fakultas) VALUES ('Agribisnis', 'Fakultas Pertanian')");
        $prodi_ids = [$pdo->lastInsertId()];
    }
    
    $prodi_count = count($prodi_ids);

    // Prepare User Insert Query
    $stmt = $pdo->prepare("INSERT INTO users (nama_lengkap, prodi_id, username, password, role, semester) VALUES (?, ?, ?, ?, ?, ?)");

    // 5. Seed 1 Admin
    // Username: admin1234, Password: admin1234 (plain-text to match proses_login.php)
    $stmt->execute([
        'Administrator Utama',
        null,
        'admin1234',
        'admin1234',
        'admin',
        null
    ]);
    echo "Admin seeded: admin1234 / admin1234\n";

    // 6. Seed 1 Dekan
    // Username: dekan1234, Password: dekan1234
    $stmt->execute([
        'Prof. Dr. Ir. H. Akhmad Rizali Saidy, M.S.',
        null,
        'dekan1234',
        'dekan1234',
        'dekan',
        null
    ]);
    echo "Dekan seeded: dekan1234 / dekan1234\n";

    // 7. Seed 1 Dosen
    // Username: dosen1234, Password: dosen1234
    $stmt->execute([
        'Dr. Ir. H. Budi Santoso, M.P.',
        null,
        'dosen1234',
        'dosen1234',
        'dosen',
        null
    ]);
    echo "Dosen seeded: dosen1234 / dosen1234\n";

    // 8. Seed 50 Mahasiswa
    // Usernames: 251051210001 to 251051210050, Password: mahasiswa1234
    $first_names = ['Ahmad', 'Siti', 'Budi', 'Rina', 'Andi', 'Dewi', 'Reza', 'Aulia', 'Dimas', 'Fitri', 'Muhammad', 'Nur', 'Sri', 'Dwi', 'Eko', 'Sari', 'Hendra', 'Yulia', 'Agus', 'Tri', 'Rian', 'Putri', 'Ferry', 'Mega', 'Taufik'];
    $last_names = ['Faisal', 'Nurhaliza', 'Darmawan', 'Melati', 'Saputra', 'Lestari', 'Rahadian', 'Rahman', 'Anggara', 'Hidayah', 'Pratama', 'Wulandari', 'Kusuma', 'Sari', 'Utomo', 'Hidayat', 'Gunawan', 'Setiawan', 'Nugroho', 'Wijaya', 'Ramadhan', 'Saraswati', 'Kurniawan', 'Putra', 'Hadi'];

    $mhs_seeded = 0;
    for ($i = 1; $i <= 50; $i++) {
        // Generate name
        $fn = $first_names[($i - 1) % count($first_names)];
        $ln = $last_names[floor(($i - 1) / count($first_names)) % count($last_names)];
        $nama_mhs = $fn . ' ' . $ln;

        // Generate username (e.g., 251051210001, 251051210002, ..., 251051210050)
        $username_mhs = '25105121' . str_pad($i, 4, '0', STR_PAD_LEFT);
        $password_mhs = 'mahasiswa1234';

        // Distribute semesters evenly: 2, 4, 6, 8
        $semesters = [2, 4, 6, 8];
        $semester_mhs = $semesters[($i - 1) % 4];

        // Distribute prodi evenly
        $prodi_id = $prodi_ids[($i - 1) % $prodi_count];

        $stmt->execute([
            $nama_mhs,
            $prodi_id,
            $username_mhs,
            $password_mhs,
            'user',
            $semester_mhs
        ]);
        $mhs_seeded++;
    }

    echo "Seeded $mhs_seeded Mahasiswa successfully: 251051210001 to 251051210050 with password: mahasiswa1234 (semesters 2, 4, 6, 8 distributed)\n";

    // 9. Seed 10 Varied Kegiatan Akademik and their Mahasiswa participants
    // Dapatkan Dosen ID
    $dosen_stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'dosen' LIMIT 1");
    $dosen_stmt->execute();
    $dosen_id = $dosen_stmt->fetchColumn();

    if (!$dosen_id) {
        throw new Exception("Dosen user not found. Please make sure Dosen is seeded first.");
    }

    // Dapatkan semua Mahasiswa IDs
    $mhs_stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'user'");
    $mhs_stmt->execute();
    $mahasiswa_ids = $mhs_stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Found Dosen ID: $dosen_id\n";
    echo "Found " . count($mahasiswa_ids) . " Mahasiswa IDs.\n";

    $kegiatan_data = [
        [
            'nama_kegiatan' => 'Praktikum Agronomi Lahan Gambut',
            'jenis_kegiatan' => 'Praktikum',
            'deskripsi' => 'Praktikum lapangan mengamati profil tanah gambut dan tata air mikro.',
            'tanggal_kegiatan' => '2026-07-01',
            'lokasi' => 'Kebun Percobaan FP ULM, Banjarbaru',
            'status' => 'Selesai',
            'nomor_surat' => '001/UN8.1.2/KA/2026',
            'num_students' => 5
        ],
        [
            'nama_kegiatan' => 'Penelitian Karakteristik Fisika Tanah Lempung',
            'jenis_kegiatan' => 'Penelitian',
            'deskripsi' => 'Pengambilan sampel tanah terganggu dan tidak terganggu untuk uji permeabilitas.',
            'tanggal_kegiatan' => '2026-07-02',
            'lokasi' => 'Laboratorium Fisika Tanah FP ULM, Banjarbaru',
            'status' => 'Selesai',
            'nomor_surat' => '002/UN8.1.2/KA/2026',
            'num_students' => 4
        ],
        [
            'nama_kegiatan' => 'Workshop Penulisan Karya Ilmiah Nasional',
            'jenis_kegiatan' => 'Seminar',
            'deskripsi' => 'Pelatihan penulisan artikel ilmiah bereputasi bagi mahasiswa tingkat akhir.',
            'tanggal_kegiatan' => '2026-07-03',
            'lokasi' => 'Aula Supardi FP ULM, Banjarbaru',
            'status' => 'Diproses',
            'nomor_surat' => '003/UN8.1.2/KA/2026',
            'num_students' => 10
        ],
        [
            'nama_kegiatan' => 'Pengabdian Masyarakat: Pembuatan Kompos Organik',
            'jenis_kegiatan' => 'Pengabdian',
            'deskripsi' => 'Pelatihan pengolahan limbah jerami padi menjadi kompos bagi petani lokal.',
            'tanggal_kegiatan' => '2026-07-04',
            'lokasi' => 'Desa Tambang Ulang, Tanah Laut',
            'status' => 'Pending',
            'nomor_surat' => null,
            'num_students' => 6
        ],
        [
            'nama_kegiatan' => 'Survei Hama Padi Lahan Pasang Surut',
            'jenis_kegiatan' => 'Penelitian',
            'deskripsi' => 'Monitoring populasi wereng coklat pasca musim tanam kedua.',
            'tanggal_kegiatan' => '2026-07-05',
            'lokasi' => 'Lahan Persawahan Alalak, Barito Kuala',
            'status' => 'Ditolak',
            'nomor_surat' => null,
            'num_students' => 3
        ],
        [
            'nama_kegiatan' => 'Kunjungan Industri Pengolahan Karet',
            'jenis_kegiatan' => 'Kunjungan Industri',
            'deskripsi' => 'Mempelajari proses pengolahan lateks menjadi sit asap bergaris (RSS).',
            'tanggal_kegiatan' => '2026-07-06',
            'lokasi' => 'PT. Bridgestone Kalimantan, Tanah Laut',
            'status' => 'Selesai',
            'nomor_surat' => '004/UN8.1.2/KA/2026',
            'num_students' => 8
        ],
        [
            'nama_kegiatan' => 'Praktikum Budidaya Tanaman Hortikultura',
            'jenis_kegiatan' => 'Praktikum',
            'deskripsi' => 'Penanaman sayuran daun menggunakan teknik vertikultur di perkotaan.',
            'tanggal_kegiatan' => '2026-07-07',
            'lokasi' => 'Greenhouse Jurusan Agronomi, Banjarbaru',
            'status' => 'Diproses',
            'nomor_surat' => '005/UN8.1.2/KA/2026',
            'num_students' => 5
        ],
        [
            'nama_kegiatan' => 'Seminar Nasional Pertanian Berkelanjutan',
            'jenis_kegiatan' => 'Seminar',
            'deskripsi' => 'Deseminasi hasil penelitian dosen dan mahasiswa bertaraf nasional.',
            'tanggal_kegiatan' => '2026-07-08',
            'lokasi' => 'Grand Dafam Q Hotel, Banjarbaru',
            'status' => 'Pending',
            'nomor_surat' => null,
            'num_students' => 12
        ],
        [
            'nama_kegiatan' => 'Pengujian Organoleptik Sirup Nanas Madu',
            'jenis_kegiatan' => 'Penelitian',
            'deskripsi' => 'Uji hedonik tingkat kesukaan konsumen terhadap formulasi sirup baru.',
            'tanggal_kegiatan' => '2026-07-09',
            'lokasi' => 'Laboratorium Teknologi Pangan FP ULM, Banjarbaru',
            'status' => 'Selesai',
            'nomor_surat' => '006/UN8.1.2/KA/2026',
            'num_students' => 7
        ],
        [
            'nama_kegiatan' => 'Sosialisasi Pengendalian Hayati OPT',
            'jenis_kegiatan' => 'Pengabdian',
            'deskripsi' => 'Pengenalan musuh alami agen hayati Trichoderma kepada kelompok tani.',
            'tanggal_kegiatan' => '2026-07-10',
            'lokasi' => 'Desa Mandiangin Barat, Karang Intan',
            'status' => 'Selesai',
            'nomor_surat' => '007/UN8.1.2/KA/2026',
            'num_students' => 5
        ]
    ];

    $stmt_kegiatan = $pdo->prepare("
        INSERT INTO kegiatan_akademik 
        (dosen_id, nomor_surat, nama_kegiatan, jenis_kegiatan, deskripsi, tanggal_kegiatan, lokasi, status, tanggal_pengajuan) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt_km = $pdo->prepare("
        INSERT INTO kegiatan_mahasiswa (kegiatan_id, mahasiswa_id) 
        VALUES (?, ?)
    ");

    $kegiatan_seeded = 0;
    foreach ($kegiatan_data as $k) {
        $stmt_kegiatan->execute([
            $dosen_id,
            $k['nomor_surat'],
            $k['nama_kegiatan'],
            $k['jenis_kegiatan'],
            $k['deskripsi'],
            $k['tanggal_kegiatan'],
            $k['lokasi'],
            $k['status']
        ]);
        
        $kegiatan_id = $pdo->lastInsertId();
        $kegiatan_seeded++;

        // Acak mahasiswa untuk kegiatan ini agar bervariasi
        $shuffled_mhs_ids = $mahasiswa_ids;
        shuffle($shuffled_mhs_ids);
        
        for ($j = 0; $j < $k['num_students']; $j++) {
            $mhs_id = $shuffled_mhs_ids[$j];
            $stmt_km->execute([$kegiatan_id, $mhs_id]);
        }
    }

    echo "Seeded $kegiatan_seeded varied kegiatan_akademik successfully with student mappings.\n";

    // 10. Seed 50 Varied Surat
    echo "Starting to seed 50 varied surat records...\n";
    $mhs_detail_stmt = $pdo->query("SELECT id, username as nim, nama_lengkap, semester FROM users WHERE role = 'user'");
    $mahasiswa_details = $mhs_detail_stmt->fetchAll(PDO::FETCH_ASSOC);

    $jenis_surat_arr = [
        'Surat Mahasiswa Aktif',
        'Surat Izin Penelitian',
        'Surat Pengantar PKL/Magang',
        'Surat Izin Tidak Masuk Kuliah',
        'Surat Keterangan Benar Alumni',
        'Surat Keterangan Lulus',
        'Surat Keterlambatan UKT'
    ];

    $stmt_surat = $pdo->prepare("
        INSERT INTO surat 
        (user_id, nomor_surat, jenis_surat, nama_lengkap, nim, semester, keperluan, dosen_pembimbing, tanggal_mulai, tanggal_selesai, tanggal_penyelesaian, durasi_magang, tanggal_pengajuan, status, catatan_admin) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $approved_counter = 1;
    $surat_seeded = 0;

    for ($i = 1; $i <= 50; $i++) {
        $mhs = $mahasiswa_details[($i - 1) % count($mahasiswa_details)];
        
        // Date between 1 July and 30 July 2026
        $day = 1 + (($i - 1) % 30);
        $tanggal_pengajuan = sprintf("2026-07-%02d 09:%02d:00", $day, ($i * 7) % 60);

        // Status distribution:
        // Selesai: 25, Diproses: 10, Pending: 10, Ditolak: 5
        if ($i % 2 === 0) {
            $status = 'Selesai';
        } else if ($i % 5 === 1) {
            $status = 'Diproses';
        } else if ($i % 5 === 3) {
            $status = 'Pending';
        } else {
            $status = 'Ditolak';
        }

        // nomor_surat
        $nomor_surat = null;
        $tanggal_penyelesaian = null;
        $catatan_admin = null;

        if ($status === 'Selesai') {
            $nomor_surat = sprintf("%03d/UN8.1.2/KM/2026", $approved_counter++);
            // 1-2 days after pengajuan
            $tanggal_penyelesaian = date('Y-m-d H:i:s', strtotime($tanggal_pengajuan . " + " . ($i % 2 + 1) . " days"));
            $catatan_admin = "Surat telah diverifikasi dan disetujui.";
        } else if ($status === 'Ditolak') {
            $catatan_admin = "Pengajuan ditolak karena berkas persyaratan kurang lengkap.";
        } else if ($status === 'Diproses') {
            $catatan_admin = "Dalam proses verifikasi berkas oleh admin.";
        }

        // jenis_surat
        $jenis_surat = $jenis_surat_arr[$i % count($jenis_surat_arr)];

        // Keperluan and conditional fields
        $keperluan = "";
        $dosen_pembimbing = null;
        $tanggal_mulai = null;
        $tanggal_selesai = null;
        $durasi_magang = null;

        switch ($jenis_surat) {
            case 'Surat Mahasiswa Aktif':
                $keperluan = "Pendaftaran beasiswa akademik dan persyaratan administrasi keluarga.";
                break;
            case 'Surat Izin Penelitian':
                $keperluan = "Penelitian tugas akhir skripsi mengenai produktivitas pertanian.";
                $dosen_pembimbing = "Dr. Ir. H. Budi Santoso, M.P.";
                $tanggal_mulai = date('Y-m-d', strtotime($tanggal_pengajuan . " + 5 days"));
                $tanggal_selesai = date('Y-m-d H:i:s', strtotime($tanggal_pengajuan . " + 95 days"));
                break;
            case 'Surat Pengantar PKL/Magang':
                $keperluan = "Magang di Balai Penelitian Tanaman Rawa FP ULM.";
                $tanggal_mulai = date('Y-m-d', strtotime($tanggal_pengajuan . " + 10 days"));
                $tanggal_selesai = date('Y-m-d H:i:s', strtotime($tanggal_pengajuan . " + 70 days"));
                $durasi_magang = "2 Bulan";
                break;
            case 'Surat Izin Tidak Masuk Kuliah':
                $keperluan = "Izin tidak masuk kuliah karena sakit demam dan disarankan istirahat.";
                $tanggal_mulai = date('Y-m-d', strtotime($tanggal_pengajuan));
                $tanggal_selesai = date('Y-m-d H:i:s', strtotime($tanggal_pengajuan . " + 3 days"));
                break;
            case 'Surat Keterangan Benar Alumni':
                $keperluan = "Keperluan verifikasi data alumni untuk melamar pekerjaan di sektor perkebunan.";
                break;
            case 'Surat Keterangan Lulus':
                $keperluan = "Sebagai bukti kelulusan sementara untuk melamar pekerjaan sebelum wisuda.";
                break;
            case 'Surat Keterangan Lulus':
                $keperluan = "Sebagai bukti kelulusan sementara untuk melamar pekerjaan sebelum wisuda.";
                break;
            case 'Surat Keterlambatan UKT':
                $keperluan = "Permohonan dispensasi keterlambatan pembayaran UKT semester ini.";
                break;
        }

        $stmt_surat->execute([
            $mhs['id'],
            $nomor_surat,
            $jenis_surat,
            $mhs['nama_lengkap'],
            $mhs['nim'],
            $mhs['semester'],
            $keperluan,
            $dosen_pembimbing,
            $tanggal_mulai,
            $tanggal_selesai,
            $tanggal_penyelesaian,
            $durasi_magang,
            $tanggal_pengajuan,
            $status,
            $catatan_admin
        ]);
        $surat_seeded++;
    }

    echo "Seeded $surat_seeded varied surat successfully.\n";
    echo "Database reset and seeding completed successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

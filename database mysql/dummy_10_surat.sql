-- ============================================================
-- DATA DUMMY 10 SURAT UNTUK TESTING REKAP LAPORAN
-- Jalankan di phpMyAdmin atau MySQL CLI
-- Database: db_surat_ulm
-- Tanggal: Mei 2026 (bulan ini) supaya langsung keliatan di rekap
-- ============================================================

USE db_surat_ulm;

-- Tambah beberapa user mahasiswa dulu biar datanya realistis
INSERT INTO `users` (`id`, `nama_lengkap`, `prodi_id`, `username`, `email`, `telepon`, `password`, `foto_profil`, `role`, `created_at`) VALUES
(30, 'Ahmad Rizki Pratama', 1, '2210010101', 'rizki@gmail.com', '081234567890', 'rizki1234', NULL, 'user', '2026-01-10 08:00:00'),
(31, 'Siti Nurhaliza', 3, '2210010202', 'siti@gmail.com', '081234567891', 'siti1234', NULL, 'user', '2026-01-10 08:00:00'),
(32, 'Budi Santoso', 5, '2210010303', 'budi@gmail.com', '081234567892', 'budi1234', NULL, 'user', '2026-01-10 08:00:00'),
(33, 'Dewi Lestari', 2, '2210010404', 'dewi@gmail.com', '081234567893', 'dewi1234', NULL, 'user', '2026-01-10 08:00:00'),
(34, 'Fajar Nugroho', 4, '2210010505', 'fajar@gmail.com', '081234567894', 'fajar1234', NULL, 'user', '2026-01-10 08:00:00')
ON DUPLICATE KEY UPDATE nama_lengkap = VALUES(nama_lengkap);

-- ============================================================
-- 10 SURAT DUMMY - Variasi jenis, status, tanggal, dan user
-- Semua di bulan Mei 2026 supaya langsung muncul di rekap
-- ============================================================

INSERT INTO `surat` (`user_id`, `nomor_surat`, `jenis_surat`, `nama_lengkap`, `nim`, `semester`, `keperluan`, `dosen_pembimbing`, `tanggal_mulai`, `tanggal_selesai`, `tanggal_penyelesaian`, `durasi_magang`, `tanggal_pengajuan`, `status`, `catatan_admin`, `updated_at`) VALUES

-- 1. Surat Mahasiswa Aktif - SELESAI (Ahmad Rizki - Agronomi)
(30, '010/UN8.1.2/KM/2026', 'Surat Mahasiswa Aktif', 'Ahmad Rizki Pratama', '2210010101', 7, 'Untuk keperluan pendaftaran beasiswa LPDP 2026', NULL, NULL, NULL, '2026-05-02 10:30:00', NULL, '2026-05-01 08:15:00', 'Selesai', 'Sudah diverifikasi data mahasiswa aktif.', '2026-05-02 03:30:00'),

-- 2. Surat Izin Penelitian - SELESAI (Siti Nurhaliza - Agribisnis)
(31, '011/UN8.1.2/KM/2026', 'Surat Izin Penelitian', 'Siti Nurhaliza', '2210010202', 8, 'Penelitian analisis rantai pasok sayuran organik di Pasar Induk Banjarbaru', 'Dr. Hj. Rahmawati, S.P., M.Si', '2026-05-05', '2026-07-05 00:00:00', '2026-05-03 14:20:00', NULL, '2026-05-02 09:00:00', 'Selesai', 'Surat izin penelitian sudah diterbitkan.', '2026-05-03 07:20:00'),

-- 3. Surat Pengantar PKL/Magang - SELESAI (Budi Santoso - Peternakan)
(32, '012/UN8.1.2/KM/2026', 'Surat Pengantar PKL/Magang', 'Budi Santoso', '2210010303', 6, 'PT. Great Giant Livestock Lampung', NULL, '2026-06-01', '2026-08-01 00:00:00', '2026-05-05 11:00:00', '2 Bulan', '2026-05-04 13:45:00', 'Selesai', 'Surat pengantar magang sudah siap.', '2026-05-05 04:00:00'),

-- 4. Surat Izin Tidak Masuk Kuliah - SELESAI (Dewi Lestari - Agroekoteknologi)
(33, '013/UN8.1.2/KM/2026', 'Surat Izin Tidak Masuk Kuliah', 'Dewi Lestari', '2210010404', NULL, 'Sakit demam berdarah, dirawat di RSUD Ulin Banjarmasin', NULL, '2026-05-06', '2026-05-09 00:00:00', '2026-05-07 08:30:00', NULL, '2026-05-06 07:00:00', 'Selesai', 'Semoga lekas sembuh. Surat sudah diterbitkan.', '2026-05-07 01:30:00'),

-- 5. Surat Keterangan Benar Alumni - DIPROSES (Fajar Nugroho - Ilmu Tanah)
(34, NULL, 'Surat Keterangan Benar Alumni', 'Fajar Nugroho', '2210010505', NULL, 'Untuk melamar pekerjaan di PT. Pupuk Kalimantan Timur', NULL, NULL, NULL, NULL, NULL, '2026-05-08 10:30:00', 'Diproses', 'Sedang dicek data kelulusan.', '2026-05-08 05:00:00'),

-- 6. Surat Mahasiswa Aktif - PENDING (Siti Nurhaliza - Agribisnis)
(31, NULL, 'Surat Mahasiswa Aktif', 'Siti Nurhaliza', '2210010202', 8, 'Untuk perpanjangan KTM dan akses perpustakaan', NULL, NULL, NULL, NULL, NULL, '2026-05-10 14:00:00', 'Pending', NULL, '2026-05-10 07:00:00'),

-- 7. Surat Izin Penelitian - PENDING (Ahmad Rizki - Agronomi)
(30, NULL, 'Surat Izin Penelitian', 'Ahmad Rizki Pratama', '2210010101', 7, 'Analisis pengaruh pupuk organik terhadap produktivitas padi di lahan rawa', 'Prof. Dr. Ir. Bambang Supriyanto, M.P', '2026-05-15', '2026-08-15 00:00:00', NULL, NULL, '2026-05-12 08:20:00', 'Pending', NULL, '2026-05-12 01:20:00'),

-- 8. Surat Keterangan Lulus - SELESAI (Dewi Lestari - Agroekoteknologi)
(33, '014/UN8.1.2/KM/2026', 'Surat Keterangan Lulus', 'Dewi Lestari', '2210010404', NULL, 'Untuk pendaftaran CPNS 2026', NULL, NULL, NULL, '2026-05-14 09:15:00', NULL, '2026-05-13 11:00:00', 'Selesai', 'Data kelulusan sudah diverifikasi.', '2026-05-14 02:15:00'),

-- 9. Surat Tugas Dosen - DIPROSES (Budi Santoso - Peternakan)
(32, NULL, 'Surat Tugas Dosen', 'Budi Santoso', '2210010303', 6, 'Pendampingan dosen ke seminar nasional peternakan di Surabaya', NULL, '2026-05-20', '2026-05-22 00:00:00', NULL, NULL, '2026-05-15 09:00:00', 'Diproses', 'Menunggu konfirmasi jadwal dosen.', '2026-05-15 03:00:00'),

-- 10. Surat Keterlambatan UKT - DITOLAK (Fajar Nugroho - Ilmu Tanah)
(34, NULL, 'Surat Keterlambatan UKT', 'Fajar Nugroho', '2210010505', 7, 'Keterlambatan pembayaran UKT semester ganjil karena orang tua sakit', NULL, NULL, NULL, NULL, NULL, '2026-05-16 15:30:00', 'Ditolak', 'Mohon maaf, batas pengajuan keterlambatan UKT sudah lewat. Silakan hubungi bagian keuangan.', '2026-05-17 02:00:00');

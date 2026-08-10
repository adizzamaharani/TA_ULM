-- ============================================
-- BACKUP DATABASE: db_surat_ulm
-- Tanggal: 2026-05-19 19:20:32 WITA
-- Dibuat oleh: admin
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Struktur tabel `program_studi`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `program_studi`;
CREATE TABLE `program_studi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_prodi` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_fakultas` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `program_studi` (`id`, `nama_prodi`, `nama_fakultas`) VALUES
('1', 'Agronomi', ''),
('2', 'Agroekoteknologi', ''),
('3', 'Agribisnis', ''),
('4', 'Ilmu Tanah', ''),
('5', 'Peternakan', ''),
('6', 'Proteksi Tanaman', ''),
('7', 'Teknologi Industri Pertanian (TIP)', '');

-- --------------------------------------------------------
-- Struktur tabel `users`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_lengkap` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `prodi_id` int DEFAULT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telepon` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `foto_profil` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role` enum('admin','user','dekan','wakil_dekan') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `users_fk_prodi` (`prodi_id`),
  CONSTRAINT `users_fk_prodi` FOREIGN KEY (`prodi_id`) REFERENCES `program_studi` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`id`, `nama_lengkap`, `prodi_id`, `username`, `email`, `telepon`, `password`, `foto_profil`, `role`, `created_at`) VALUES
('1', 'Administrator', NULL, 'admin', NULL, NULL, 'admin1234', NULL, 'admin', '2025-11-07 19:01:26'),
('2', 'Contoh User Mahasiswa', NULL, 'user', NULL, NULL, '$2y$10$A.bC3dE4fG5hI6jK7lM8n.o2qE5zI9.o2qE5zI9.o2qE5zI9.o2q', NULL, 'user', '2025-11-07 19:01:26'),
('3', 'Muhammad Syafiq Yusuf', NULL, '2210010514', NULL, NULL, '$2y$10$ViEZ.h4Vxu/T9lDvc2NBCOGePhfo28tTbtjd7HoAw03qq5xmXgllO', NULL, 'user', '2025-11-08 07:22:12'),
('4', 'admin', NULL, 'admin1234', NULL, NULL, 'admin1234', NULL, 'admin', '2025-11-08 08:23:11'),
('5', 'Adizza Maharani Reynaldi', NULL, '111122223333', 'adiza@gmail.com', '087753332222', '$2y$10$T2HFQxAtGLKEXKGpkt.OROBggNI1AlFsEIuqp63ad46Fe7.zatpMi', 'user_5_1762579491.jpg', 'user', '2025-11-08 09:17:24'),
('10', 'Dr. Dekan Fakultas Teknik', NULL, 'dekan_ft', NULL, NULL, '$2y$10$Y5YJcM5.J.fJ2X.Q3zV9A.tXGf3fW.oYg.Fz.H.oYg.Fz.H.oYg.F', NULL, 'dekan', '2025-11-10 11:46:25'),
('11', 'Dr. Wakil Dekan FT', NULL, 'wadek_ft', NULL, NULL, '$2y$10$Y5YJcM5.J.fJ2X.Q3zV9A.tXGf3fW.oYg.Fz.H.oYg.Fz.H.oYg.F', NULL, 'wakil_dekan', '2025-11-10 11:46:25'),
('13', 'Adizza Maharani Reynaldi', '2', '222244445555', 'adiza@gmail.com', '087753332222', 'adiza1234', 'user_13_1766024614.png', 'user', '2025-11-24 10:52:58'),
('14', 'Dr. Bagus Ari Wijawa, M.P', NULL, 'dekan1', NULL, NULL, '$2y$10$Y1mb31FQb2B66WDwGetNluuczSJ9u.Sw2gkRC9t8WR9jnLrpGV2Yi', NULL, 'dekan', '2025-12-15 08:43:25'),
('15', 'sapik dekan', NULL, 'sapik', NULL, NULL, '1234', NULL, 'dekan', '2026-01-04 01:57:35'),
('16', 'sapik1234', NULL, 'sapik1234', NULL, NULL, 'sapik1234', NULL, 'dekan', '2026-01-04 02:01:41'),
('17', 'admin1', NULL, 'admin1', NULL, NULL, 'admin1', NULL, 'admin', '2026-01-04 02:11:17'),
('18', 'adiza1', NULL, 'adiza1', NULL, NULL, 'adiza1', NULL, 'user', '2026-01-04 02:12:12'),
('19', 'adizamahasiswa', '4', 'adizamahasiswa', NULL, NULL, 'adizamahasiswa', NULL, 'user', '2026-01-04 02:13:00'),
('20', 'adiza', '5', 'adiza1234', NULL, NULL, 'adiza1234', NULL, 'user', '2026-01-04 02:18:47'),
('21', 'adiza1111', '1', 'adiza1111', NULL, NULL, 'adiza1111', NULL, 'user', '2026-01-04 02:23:39'),
('22', 'dekan1234', NULL, 'dekan1234', NULL, NULL, 'dekan1234', NULL, 'dekan', '2026-01-04 02:24:49'),
('23', 'guest', '1', '12345677878887', NULL, NULL, '12345678', NULL, 'user', '2026-05-13 05:37:45');

-- --------------------------------------------------------
-- Struktur tabel `mading`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `mading`;
CREATE TABLE `mading` (
  `id` int NOT NULL AUTO_INCREMENT,
  `judul` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `isi` text COLLATE utf8mb4_general_ci NOT NULL,
  `gambar` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_id` int NOT NULL COMMENT 'Admin yang memposting',
  `tanggal_dibuat` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `mading_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `mading` (`id`, `judul`, `isi`, `gambar`, `user_id`, `tanggal_dibuat`) VALUES
('1', 'Jadwal Ujian Akhir Semester Ganjil 2025/2026', 'Ayo Ujian', NULL, '4', '2025-11-08 13:21:54'),
('2', 'Pendaftaran Proposal Skripsi Untuk Mahasiswa Semester 8', 'Silahkan daftar', NULL, '4', '2025-12-15 09:06:19');

-- --------------------------------------------------------
-- Struktur tabel `surat`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `surat`;
CREATE TABLE `surat` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `nomor_surat` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jenis_surat` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_lengkap` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `nim` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `semester` int DEFAULT NULL,
  `keperluan` text COLLATE utf8mb4_general_ci NOT NULL,
  `dosen_pembimbing` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` datetime DEFAULT NULL,
  `tanggal_penyelesaian` datetime DEFAULT NULL,
  `durasi_magang` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tanggal_pengajuan` datetime DEFAULT NULL,
  `status` enum('Pending','Diproses','Selesai','Ditolak') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Pending',
  `catatan_admin` text COLLATE utf8mb4_general_ci,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `surat_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

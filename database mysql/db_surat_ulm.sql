-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 03, 2026 at 07:26 PM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.0.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_surat_ulm`
--

-- --------------------------------------------------------

--
-- Table structure for table `mading`
--

CREATE TABLE `mading` (
  `id` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `isi` text NOT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Admin yang memposting',
  `tanggal_dibuat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mading`
--

INSERT INTO `mading` (`id`, `judul`, `isi`, `gambar`, `user_id`, `tanggal_dibuat`) VALUES
(1, 'Jadwal Ujian Akhir Semester Ganjil 2025/2026', 'Ayo Ujian', NULL, 4, '2025-11-08 05:21:54'),
(2, 'Pendaftaran Proposal Skripsi Untuk Mahasiswa Semester 8', 'Silahkan daftar', NULL, 4, '2025-12-15 01:06:19');

-- --------------------------------------------------------

--
-- Table structure for table `program_studi`
--

CREATE TABLE `program_studi` (
  `id` int(11) NOT NULL,
  `nama_prodi` varchar(255) NOT NULL,
  `nama_fakultas` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `program_studi`
--

INSERT INTO `program_studi` (`id`, `nama_prodi`, `nama_fakultas`) VALUES
(1, 'Agronomi', ''),
(2, 'Agroekoteknologi', ''),
(3, 'Agribisnis', ''),
(4, 'Ilmu Tanah', ''),
(5, 'Peternakan', ''),
(6, 'Proteksi Tanaman', ''),
(7, 'Teknologi Industri Pertanian (TIP)', '');

-- --------------------------------------------------------

--
-- Table structure for table `surat`
--

CREATE TABLE `surat` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nomor_surat` varchar(50) DEFAULT NULL,
  `jenis_surat` varchar(100) NOT NULL,
  `nama_lengkap` varchar(255) NOT NULL,
  `nim` varchar(20) NOT NULL,
  `semester` int(2) DEFAULT NULL,
  `keperluan` text NOT NULL,
  `dosen_pembimbing` varchar(255) DEFAULT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` datetime DEFAULT NULL,
  `tanggal_penyelesaian` datetime DEFAULT NULL,
  `durasi_magang` varchar(50) DEFAULT NULL,
  `tanggal_pengajuan` datetime DEFAULT NULL,
  `status` enum('Pending','Diproses','Selesai','Ditolak') NOT NULL DEFAULT 'Pending',
  `catatan_admin` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `surat`
--

INSERT INTO `surat` (`id`, `user_id`, `nomor_surat`, `jenis_surat`, `nama_lengkap`, `nim`, `semester`, `keperluan`, `dosen_pembimbing`, `tanggal_mulai`, `tanggal_selesai`, `tanggal_penyelesaian`, `durasi_magang`, `tanggal_pengajuan`, `status`, `catatan_admin`, `updated_at`) VALUES
(1, 3, '001/UN8.1.2/KM/2025', 'Surat Mahasiswa Aktif', 'Muhammad Syafiq Yusuf', '2210010514', 7, 'Untuk BPJS', NULL, NULL, NULL, NULL, NULL, '2025-11-08 00:00:00', 'Selesai', NULL, '2025-11-10 00:23:55'),
(2, 3, NULL, 'Surat Izin Tidak Masuk Kuliah', 'Muhammad Syafiq Yusuf', '2210010514', NULL, 'Acara keluarga', NULL, '2025-11-07', '2025-11-20 00:00:00', NULL, NULL, '2025-11-08 00:00:00', 'Diproses', NULL, '2025-11-08 01:03:40'),
(3, 5, '002/UN8.1.2/KM/2025', 'Surat Mahasiswa Aktif', 'Adizza Maharani Reynaldi', '111122223333', 7, 'untuk beasiswa', NULL, NULL, NULL, NULL, NULL, '2025-11-08 00:00:00', 'Selesai', NULL, '2025-11-10 00:27:58'),
(4, 5, NULL, 'Surat Keterangan Benar Alumni', 'Adizza Maharani Reynaldi', '111122223333', NULL, 'PT. Abadi', NULL, NULL, NULL, NULL, NULL, '2025-11-08 00:00:00', 'Selesai', NULL, '2025-11-08 01:32:54'),
(5, 5, NULL, 'Surat Izin Penelitian', 'Adizza Maharani Reynaldi', '111122223333', 7, 'Analisa Swot di Diskominfo Banjarmasin', 'Dr. Udin .AP', '2025-11-07', '2025-12-06 00:00:00', NULL, NULL, '2025-11-08 00:00:00', 'Pending', NULL, '2025-11-08 05:04:21'),
(6, 3, NULL, 'Surat Pengantar PKL/Magang', 'Muhammad Syafiq Yusuf', '2210010514', 7, 'PT. Telkom', NULL, '2025-11-01', '2025-12-31 00:00:00', NULL, '2 Bulan', '2025-11-08 00:00:00', 'Pending', NULL, '2025-11-08 05:08:55'),
(7, 5, NULL, 'Surat Mahasiswa Aktif', 'Adizza Maharani Reynaldi', '111122223333', 7, 'qwqw', NULL, NULL, NULL, NULL, NULL, '2025-11-10 00:00:00', 'Pending', NULL, '2025-11-10 05:40:46'),
(8, 13, '003/UN8.1.2/KM/2025', 'Surat Mahasiswa Aktif', 'Adizza Maharani Reynaldi', '222244445555', 7, 'untuk BPJS', NULL, NULL, '2025-11-24 00:00:00', NULL, NULL, '2025-11-24 00:00:00', 'Selesai', '', '2025-11-24 05:08:06'),
(9, 13, NULL, 'Surat Keterangan Benar Alumni', 'Adizza Maharani Reynaldi', '222244445555', NULL, 'melamar pekerjaan di PT udin', NULL, NULL, NULL, NULL, NULL, '2025-11-24 00:00:00', 'Pending', NULL, '2025-11-24 05:12:19'),
(10, 13, NULL, 'Surat Mahasiswa Aktif', 'Adizza Maharani Reynaldi', '222244445555', 7, 'sdfsdf', NULL, NULL, NULL, NULL, NULL, '2025-11-24 00:00:00', 'Pending', NULL, '2025-11-24 05:20:53'),
(11, 13, NULL, 'Surat Mahasiswa Aktif', 'Adizza Maharani Reynaldi', '222244445555', 7, 'trgtg', NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, '2025-11-24 05:23:12'),
(12, 13, NULL, 'Surat Mahasiswa Aktif', 'Adizza Maharani Reynaldi', '222244445555', 7, 'hrthrt', NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, '2025-11-24 05:26:06'),
(13, 13, NULL, 'Surat Mahasiswa Aktif', 'Adizza Maharani Reynaldi', '222244445555', 7, 'trhtrhtrhtrhrh', NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, '2025-11-24 05:29:10'),
(14, 13, NULL, 'Surat Mahasiswa Aktif', 'Adizza Maharani Reynaldi', '222244445555', 7, 'vnghn', NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL, '2025-11-24 05:33:27'),
(15, 13, NULL, 'Surat Mahasiswa Aktif', 'Adizza Maharani Reynaldi', '222244445555', 7, 'rhrthtrh', NULL, NULL, NULL, NULL, NULL, '2025-11-24 13:36:03', 'Diproses', NULL, '2025-11-24 05:36:03'),
(16, 13, NULL, 'Surat Mahasiswa Aktif', 'Adizza Maharani Reynaldi', '222244445555', 7, 'jmgjjmhj', NULL, NULL, NULL, NULL, NULL, '2025-11-24 13:38:22', 'Diproses', NULL, '2025-11-24 05:38:22'),
(17, 13, NULL, 'Surat Mahasiswa Aktif', 'Adizza Maharani Reynaldi', '222244445555', 7, 'aku adalah', NULL, NULL, NULL, NULL, NULL, '2025-11-24 13:38:50', 'Diproses', NULL, '2025-11-24 05:38:50'),
(18, 13, NULL, 'Surat Mahasiswa Aktif', 'Adizza Maharani Reynaldi', '222244445555', 7, 'okeh', NULL, NULL, '2025-11-24 13:41:10', NULL, NULL, '2025-11-24 13:40:15', 'Selesai', '', '2025-11-24 05:41:10'),
(19, 13, NULL, 'Surat Izin Tidak Masuk Kuliah', 'Adizza Maharani Reynaldi', '222244445555', NULL, 'sakit', NULL, '2025-11-23', '2025-11-24 13:59:46', NULL, NULL, '2025-11-24 13:59:11', 'Selesai', '', '2025-11-24 05:59:46'),
(20, 13, NULL, 'Surat Izin Penelitian', 'Adizza Maharani Reynaldi', '222244445555', 7, 'ergergerg', 'Dr. Udin .AP', '2025-11-24', '2025-11-24 14:07:37', NULL, NULL, '2025-11-24 14:07:24', 'Selesai', '', '2025-11-24 06:07:37'),
(21, 13, NULL, 'Surat Keterangan Benar Alumni', 'Adizza Maharani Reynaldi', '222244445555', NULL, 'Untuk melamar pekerjaan di luar negeri', NULL, NULL, '2025-11-25 08:17:29', NULL, NULL, '2025-11-25 08:16:37', 'Selesai', '', '2025-11-25 00:17:29'),
(22, 13, NULL, 'Surat Mahasiswa Aktif', 'Adizza Maharani Reynaldi', '222244445555', 7, 'Mendaftar beasiswa LDPD', NULL, NULL, '2025-11-25 08:34:47', NULL, NULL, '2025-11-25 08:30:09', 'Selesai', '', '2025-11-25 00:34:47'),
(23, 13, NULL, 'Surat Izin Penelitian', 'Adizza Maharani Reynaldi', '222244445555', 8, 'Analisis tumbuhan lahan gambut', 'Dr. Muhammad Syaiful Anwar, S.P, M.P', '2025-11-25', '2025-11-25 08:34:20', NULL, NULL, '2025-11-25 08:31:34', 'Selesai', '', '2025-11-25 00:34:20'),
(24, 13, NULL, 'Surat Pengantar PKL/Magang', 'Adizza Maharani Reynaldi', '222244445555', 7, 'Dinas Pertanian Kota Banjarbaru', NULL, '2025-11-01', '2025-11-25 08:33:50', NULL, '2 Bulan', '2025-11-25 08:32:04', 'Selesai', '', '2025-11-25 00:33:50'),
(25, 13, NULL, 'Surat Izin Tidak Masuk Kuliah', 'Adizza Maharani Reynaldi', '222244445555', NULL, 'Izin karena dirawat di RS', NULL, '2025-11-24', '2025-11-25 08:33:15', NULL, NULL, '2025-11-25 08:32:26', 'Selesai', '', '2025-11-25 00:33:15'),
(26, 13, NULL, 'Surat Izin Penelitian', 'Adizza Maharani Reynaldi', '222244445555', 8, 'Bioteknologi untuk pertanian di kota Banjarbaru', 'Dr. Muhammad Syaiful Anwar, S.P, M.P', '2025-11-25', '2025-11-25 08:43:07', NULL, NULL, '2025-11-25 08:42:28', 'Selesai', '', '2025-11-25 00:43:07'),
(27, 13, NULL, 'Surat Izin Penelitian', 'Adizza Maharani Reynaldi', '222244445555', 8, 'Analisis hujan buatan untuk pertanian di Kota Banjarbaru', 'Dr. Muhammad Syaiful Anwar, S.P, M.P', '2025-11-24', '2026-02-20 00:00:00', NULL, NULL, '2025-11-25 08:47:39', 'Pending', NULL, '2025-11-25 00:47:39'),
(28, 13, NULL, 'Surat Pengantar PKL/Magang', 'Adizza Maharani Reynaldi', '222244445555', 7, 'HRD PT. Agronika Sukses Jaya', NULL, '2025-11-24', '2026-01-24 00:00:00', '2025-11-25 08:48:58', '2 Bulan', '2025-11-25 08:48:31', 'Selesai', '', '2025-11-25 00:48:58'),
(29, 13, NULL, 'Surat Mahasiswa Aktif', 'Adizza Maharani Reynaldi', '222244445555', 7, 'Daftar Beasiswa', NULL, NULL, NULL, '2025-12-05 14:50:49', NULL, '2025-12-05 14:48:51', 'Selesai', '', '2025-12-05 06:50:49'),
(30, 13, NULL, 'Surat Izin Penelitian', 'Adizza Maharani Reynaldi', '222244445555', 8, 'Analisis tanaman gambut di kab. banjar', 'Dr. Muhammad Syaiful Anwar, S.P, M.P', '2025-12-10', '2026-01-10 00:00:00', '2025-12-05 14:50:44', NULL, '2025-12-05 14:49:21', 'Selesai', '', '2025-12-05 06:50:44'),
(31, 13, NULL, 'Surat Pengantar PKL/Magang', 'Adizza Maharani Reynaldi', '222244445555', 7, 'HRD PT. Agro Jaya Bersama', NULL, '2025-12-01', '2026-01-31 00:00:00', '2025-12-05 14:50:38', '2 Bulan', '2025-12-05 14:49:47', 'Selesai', '', '2025-12-05 06:50:38'),
(32, 13, NULL, 'Surat Izin Tidak Masuk Kuliah', 'Adizza Maharani Reynaldi', '222244445555', NULL, 'Karena ada acara keluarga', NULL, '2025-12-03', '2025-12-31 00:00:00', '2025-12-05 14:50:35', NULL, '2025-12-05 14:50:05', 'Selesai', '', '2025-12-05 06:50:35'),
(33, 13, NULL, 'Surat Keterangan Benar Alumni', 'Adizza Maharani Reynaldi', '222244445555', NULL, 'PT. Agro Jaya', NULL, NULL, NULL, '2025-12-05 14:50:30', NULL, '2025-12-05 14:50:16', 'Selesai', '', '2025-12-05 06:50:30');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `prodi_id` int(11) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `foto_profil` varchar(255) DEFAULT NULL,
  `role` enum('admin','user','dekan','wakil_dekan') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama_lengkap`, `prodi_id`, `username`, `email`, `telepon`, `password`, `foto_profil`, `role`, `created_at`) VALUES
(1, 'Administrator', NULL, 'admin', NULL, NULL, 'admin1234', NULL, 'admin', '2025-11-07 11:01:26'),
(2, 'Contoh User Mahasiswa', NULL, 'user', NULL, NULL, '$2y$10$A.bC3dE4fG5hI6jK7lM8n.o2qE5zI9.o2qE5zI9.o2qE5zI9.o2q', NULL, 'user', '2025-11-07 11:01:26'),
(3, 'Muhammad Syafiq Yusuf', NULL, '2210010514', NULL, NULL, '$2y$10$ViEZ.h4Vxu/T9lDvc2NBCOGePhfo28tTbtjd7HoAw03qq5xmXgllO', NULL, 'user', '2025-11-07 23:22:12'),
(4, 'admin', NULL, 'admin1234', NULL, NULL, 'admin1234', NULL, 'admin', '2025-11-08 00:23:11'),
(5, 'Adizza Maharani Reynaldi', NULL, '111122223333', 'adiza@gmail.com', '087753332222', '$2y$10$T2HFQxAtGLKEXKGpkt.OROBggNI1AlFsEIuqp63ad46Fe7.zatpMi', 'user_5_1762579491.jpg', 'user', '2025-11-08 01:17:24'),
(10, 'Dr. Dekan Fakultas Teknik', NULL, 'dekan_ft', NULL, NULL, '$2y$10$Y5YJcM5.J.fJ2X.Q3zV9A.tXGf3fW.oYg.Fz.H.oYg.Fz.H.oYg.F', NULL, 'dekan', '2025-11-10 03:46:25'),
(11, 'Dr. Wakil Dekan FT', NULL, 'wadek_ft', NULL, NULL, '$2y$10$Y5YJcM5.J.fJ2X.Q3zV9A.tXGf3fW.oYg.Fz.H.oYg.Fz.H.oYg.F', NULL, 'wakil_dekan', '2025-11-10 03:46:25'),
(13, 'Adizza Maharani Reynaldi', 2, '222244445555', 'adiza@gmail.com', '087753332222', 'adiza1234', 'user_13_1766024614.png', 'user', '2025-11-24 02:52:58'),
(14, 'Dr. Bagus Ari Wijawa, M.P', NULL, 'dekan1', NULL, NULL, '$2y$10$Y1mb31FQb2B66WDwGetNluuczSJ9u.Sw2gkRC9t8WR9jnLrpGV2Yi', NULL, 'dekan', '2025-12-15 00:43:25'),
(15, 'sapik dekan', NULL, 'sapik', NULL, NULL, '1234', NULL, 'dekan', '2026-01-03 17:57:35'),
(16, 'sapik1234', NULL, 'sapik1234', NULL, NULL, 'sapik1234', NULL, 'dekan', '2026-01-03 18:01:41'),
(17, 'admin1', NULL, 'admin1', NULL, NULL, 'admin1', NULL, 'admin', '2026-01-03 18:11:17'),
(18, 'adiza1', NULL, 'adiza1', NULL, NULL, 'adiza1', NULL, 'user', '2026-01-03 18:12:12'),
(19, 'adizamahasiswa', 4, 'adizamahasiswa', NULL, NULL, 'adizamahasiswa', NULL, 'user', '2026-01-03 18:13:00'),
(20, 'adiza', 5, 'adiza1234', NULL, NULL, 'adiza1234', NULL, 'user', '2026-01-03 18:18:47'),
(21, 'adiza1111', 1, 'adiza1111', NULL, NULL, 'adiza1111', NULL, 'user', '2026-01-03 18:23:39'),
(22, 'dekan1234', NULL, 'dekan1234', NULL, NULL, 'dekan1234', NULL, 'dekan', '2026-01-03 18:24:49');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `mading`
--
ALTER TABLE `mading`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `program_studi`
--
ALTER TABLE `program_studi`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `surat`
--
ALTER TABLE `surat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `users_fk_prodi` (`prodi_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `mading`
--
ALTER TABLE `mading`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `program_studi`
--
ALTER TABLE `program_studi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `surat`
--
ALTER TABLE `surat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `mading`
--
ALTER TABLE `mading`
  ADD CONSTRAINT `mading_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `surat`
--
ALTER TABLE `surat`
  ADD CONSTRAINT `surat_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_fk_prodi` FOREIGN KEY (`prodi_id`) REFERENCES `program_studi` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

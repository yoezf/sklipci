-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 19, 2026 at 08:06 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sklipci`
--

-- --------------------------------------------------------

--
-- Table structure for table `dosen`
--

CREATE TABLE `dosen` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nip` varchar(30) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `prodi_id` int(11) NOT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dosen`
--

INSERT INTO `dosen` (`id`, `user_id`, `nip`, `nama`, `prodi_id`, `no_hp`, `created_at`) VALUES
(4, 8, '410470105003', 'Suwinarno Nadajmuddin, S.kom, M.kom', 1, '1234567890', '2025-12-01 16:52:34'),
(7, 13, '410470102011', 'Dendin Supriadi, S.Pd., M.T.', 1, '1234567890', '2025-12-03 17:30:33'),
(9, 60, '410470105011', 'Muhamad Nawawi, S.Kom., M.Kom.', 1, '1234567890', '2026-01-19 00:50:35'),
(10, 62, '1234567890', 'Muhammad Fadli, S.T., M.Kom.', 1, '1234567890', '2026-01-19 00:52:53'),
(12, 65, '1234567899', 'Dra. Tuti Hartati, M.T.', 1, '1234567890', '2026-01-19 00:53:40');

-- --------------------------------------------------------

--
-- Table structure for table `jadwal`
--

CREATE TABLE `jadwal` (
  `id` int(11) NOT NULL,
  `jenis` enum('seminar','sidang') NOT NULL,
  `pengajuan_id` int(11) DEFAULT NULL,
  `mahasiswa_id` int(11) NOT NULL,
  `dosen_pembimbing_id` int(11) DEFAULT NULL,
  `dosen_penguji_id` int(11) DEFAULT NULL,
  `dosen_penguji_2_id` int(11) DEFAULT NULL,
  `tanggal` date NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time DEFAULT NULL,
  `ruangan` varchar(100) NOT NULL,
  `status` enum('dijadwalkan','dikonfirmasi','ditolak') NOT NULL DEFAULT 'dijadwalkan',
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jadwal`
--

INSERT INTO `jadwal` (`id`, `jenis`, `pengajuan_id`, `mahasiswa_id`, `dosen_pembimbing_id`, `dosen_penguji_id`, `dosen_penguji_2_id`, `tanggal`, `jam_mulai`, `jam_selesai`, `ruangan`, `status`, `catatan`, `created_at`, `updated_at`) VALUES
(4, 'seminar', 13, 3, NULL, NULL, 4, '2025-12-13', '14:48:00', '15:49:00', 'RUANG 2', 'dijadwalkan', '-', '2025-12-04 18:47:48', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `laporan_akhir`
--

CREATE TABLE `laporan_akhir` (
  `id` int(11) NOT NULL,
  `pengajuan_id` int(11) NOT NULL,
  `mahasiswa_id` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `status` enum('diajukan','diterima','ditolak') NOT NULL DEFAULT 'diajukan',
  `catatan_admin` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `laporan_akhir`
--

INSERT INTO `laporan_akhir` (`id`, `pengajuan_id`, `mahasiswa_id`, `judul`, `file_path`, `status`, `catatan_admin`, `created_at`, `updated_at`) VALUES
(3, 8, 3, 'Sistem informasi', 'public/uploads/laporan_akhir/10521035_2025_LAPORAN_sistem_informasi.pdf', 'diajukan', NULL, '2025-12-08 14:16:29', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `log_bimbingan`
--

CREATE TABLE `log_bimbingan` (
  `id` int(11) NOT NULL,
  `mahasiswa_id` int(11) NOT NULL,
  `dosen_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `topik` varchar(255) NOT NULL,
  `catatan_mahasiswa` text DEFAULT NULL,
  `catatan_dosen` text DEFAULT NULL,
  `status` enum('diajukan','disetujui','direvisi') NOT NULL DEFAULT 'diajukan',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `log_bimbingan`
--

INSERT INTO `log_bimbingan` (`id`, `mahasiswa_id`, `dosen_id`, `tanggal`, `topik`, `catatan_mahasiswa`, `catatan_dosen`, `status`, `created_at`, `updated_at`) VALUES
(3, 3, 4, '2025-12-01', 'BAB II', 'perbaikan tulisan', 'garis miring bahasa asing', 'direvisi', '2025-12-10 07:28:53', '2025-12-12 06:37:11'),
(4, 3, 4, '2025-12-13', 'BAB III', 'bahasa asing telah sudah miring', 'mantap', 'disetujui', '2025-12-12 06:37:56', '2025-12-12 06:38:16');

-- --------------------------------------------------------

--
-- Table structure for table `log_bimbingan_pkl`
--

CREATE TABLE `log_bimbingan_pkl` (
  `id` int(11) NOT NULL,
  `id_mahasiswa` int(11) NOT NULL,
  `id_dosen` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `media` enum('Tatap Muka','Online','Chat') DEFAULT 'Tatap Muka',
  `topik` varchar(255) NOT NULL,
  `catatan_mahasiswa` text DEFAULT NULL,
  `catatan_dosen` text DEFAULT NULL,
  `status_verifikasi` enum('Menunggu','Disetujui','Ditolak') DEFAULT 'Menunggu',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log_pkl`
--

CREATE TABLE `log_pkl` (
  `id` int(11) NOT NULL,
  `mahasiswa_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `kegiatan` text NOT NULL,
  `lokasi` varchar(150) DEFAULT NULL,
  `jam_mulai` time DEFAULT NULL,
  `jam_selesai` time DEFAULT NULL,
  `output` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `log_pkl`
--

INSERT INTO `log_pkl` (`id`, `mahasiswa_id`, `tanggal`, `kegiatan`, `lokasi`, `jam_mulai`, `jam_selesai`, `output`, `created_at`) VALUES
(3, 3, '2025-12-27', 'menjalani PKL', '', '08:00:00', '15:34:00', '-', '2025-12-04 07:34:20'),
(4, 3, '2025-12-27', 'membantu menyelesaikan excel', 'kantor', '08:31:00', '00:30:00', 'excel berhasil', '2025-12-04 10:29:11');

-- --------------------------------------------------------

--
-- Table structure for table `mahasiswa`
--

CREATE TABLE `mahasiswa` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nim` varchar(30) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `prodi_id` int(11) NOT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `kelas` varchar(50) DEFAULT NULL,
  `angkatan` varchar(10) DEFAULT NULL,
  `semester` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mahasiswa`
--

INSERT INTO `mahasiswa` (`id`, `user_id`, `nim`, `nama`, `prodi_id`, `no_hp`, `kelas`, `angkatan`, `semester`, `created_at`) VALUES
(3, 12, '10521035', 'Rizki Maulana', 1, '089657908809', 'IF1', '2021', NULL, '2025-12-03 06:48:28'),
(12, 40, '10521114', 'Meri Sri Anggraeni', 1, '1234567890', 'IF', '2021', 9, '2026-01-19 00:47:59'),
(13, 41, '10521149', 'Yusup Azhar', 1, '1234567890', 'IF', '2021', 9, '2026-01-19 00:47:59'),
(14, 42, '10521070', 'Muhamad Luthfi Ardiansyah Putra', 1, '1234567890', 'IF', '2021', 9, '2026-01-19 00:47:59'),
(15, 43, '10521140', 'Sifa Agustin', 1, '1234567890', 'IF', '2021', 9, '2026-01-19 00:47:59'),
(16, 44, '10521153', 'Imam Rumani', 1, '1234567890', 'IF', '2021', 9, '2026-01-19 00:47:59'),
(17, 45, '10521045', 'Wa Fia', 1, '1234567890', 'IF', '2021', 9, '2026-01-19 00:47:59'),
(18, 46, '10521072', 'Muhlis Hidayatulloh', 1, '1234567890', 'IF', '2021', 9, '2026-01-19 00:47:59'),
(19, 47, '10521056', 'Muhamad Ihsan AlmahdI', 1, '1234567890', 'IF', '2021', 9, '2026-01-19 00:47:59'),
(20, 48, '10521083', 'AI Lela', 1, '1234567890', 'IF', '2021', 9, '2026-01-19 00:47:59'),
(21, 49, '10521078', 'Abdul Gani', 1, '1234567890', 'IF', '2021', 9, '2026-01-19 00:47:59'),
(22, 50, '10521117', 'Muhamad Dikri', 1, '1234567890', 'IF', '2021', 9, '2026-01-19 00:47:59'),
(23, 51, '10521164', 'Siti Halimah', 1, '1234567890', 'IF', '2021', 9, '2026-01-19 00:47:59'),
(24, 52, '10521134', 'Reza Muhamad Sidik', 1, '1234567890', 'IF', '2021', 9, '2026-01-19 00:48:00'),
(25, 53, '10521129', 'Pirman', 1, '1234567890', 'IF', '2021', 9, '2026-01-19 00:48:00'),
(26, 54, '10521068', 'Mochamad Rifqi Hira Rafiqi', 1, '1234567890', 'IF', '2021', 9, '2026-01-19 00:48:00'),
(27, 55, '10520056', 'Sigit Hardianto', 1, '1234567890', 'IF', '2021', 9, '2026-01-19 00:48:00'),
(28, 56, '10521061', 'Dian Aldera Herdiansyah', 1, '1234567890', 'IF', '2021', 9, '2026-01-19 00:48:00'),
(29, 57, '10521065', 'Husni Moh Jaelani', 1, '1234567890', 'IF', '2021', 9, '2026-01-19 00:48:00'),
(30, 58, '10521063', 'Egi Hamdani', 1, '1234567890', 'IF', '2021', 9, '2026-01-19 00:48:00'),
(31, 59, '10521039', 'Roqib Tizar Fawaik', 1, '1234567890', 'IF', '2021', 9, '2026-01-19 00:48:00');

-- --------------------------------------------------------

--
-- Table structure for table `pengajuan`
--

CREATE TABLE `pengajuan` (
  `id` int(11) NOT NULL,
  `mahasiswa_id` int(11) NOT NULL,
  `jenis` enum('pkl','skripsi','seminar','sidang') NOT NULL,
  `judul` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `lampiran` varchar(255) DEFAULT NULL,
  `status` enum('diajukan','diterima','ditolak') NOT NULL DEFAULT 'diajukan',
  `catatan_admin` text DEFAULT NULL,
  `pembimbing_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `tempat_pkl` varchar(255) DEFAULT NULL,
  `lama_pkl` varchar(100) DEFAULT NULL,
  `judul_2` text DEFAULT NULL,
  `judul_3` text DEFAULT NULL,
  `judul_4` text DEFAULT NULL,
  `judul_5` text DEFAULT NULL,
  `file_syarat` varchar(255) DEFAULT NULL,
  `skripsi_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengajuan`
--

INSERT INTO `pengajuan` (`id`, `mahasiswa_id`, `jenis`, `judul`, `deskripsi`, `lampiran`, `status`, `catatan_admin`, `pembimbing_id`, `created_at`, `updated_at`, `tempat_pkl`, `lama_pkl`, `judul_2`, `judul_3`, `judul_4`, `judul_5`, `file_syarat`, `skripsi_id`) VALUES
(5, 3, 'pkl', 'Sistem informasi', '123', NULL, 'diterima', '', 4, '2025-12-03 18:20:41', '2025-12-03 18:27:33', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 3, 'pkl', 'PKL di abcdef', 'Tempat PKL : abcdef\nTanggal Mulai : 2025-12-12\nTanggal Selesai : 2026-01-11\n', NULL, 'diajukan', '', 7, '2025-12-04 07:22:21', '2025-12-04 07:25:07', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 3, 'skripsi', 'abc', 'Pengajuan judul skripsi.\n', NULL, 'ditolak', '', NULL, '2025-12-04 07:22:52', '2025-12-04 07:25:21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 3, 'skripsi', 'abcd', 'Pengajuan judul skripsi.\n', NULL, 'diterima', '-', 4, '2025-12-04 07:22:52', '2025-12-04 07:32:26', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, 3, 'skripsi', 'abcde', 'Pengajuan judul skripsi.\n', NULL, 'diajukan', NULL, NULL, '2025-12-04 07:22:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, 3, 'skripsi', 'abcdef', 'Pengajuan judul skripsi.\n', NULL, 'diajukan', NULL, NULL, '2025-12-04 07:22:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(11, 3, 'skripsi', 'abcdefg', 'Pengajuan judul skripsi.\n', NULL, 'diajukan', NULL, NULL, '2025-12-04 07:22:52', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(12, 3, 'pkl', 'PKL di abcdef', 'Tempat PKL : abcdef\nTanggal Mulai : 2025-12-04\nTanggal Selesai : 2026-01-01\n\nKeterangan : -\n\nLampiran syarat : public/uploads/pengajuan/pengajuan_pkl_10521035_1764833430.pdf', 'public/uploads/pengajuan/pengajuan_pkl_10521035_1764833430.pdf', 'diajukan', NULL, NULL, '2025-12-04 07:30:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(13, 3, 'seminar', 'abcd', 'Pengajuan seminar untuk skripsi: abcd\nStatus skripsi: DITERIMA\n\n\nLampiran syarat: public/uploads/pengajuan/pengajuan_seminar_10521035_1764873893.pdf', 'public/uploads/pengajuan/pengajuan_seminar_10521035_1764873893.pdf', 'diterima', '-', NULL, '2025-12-04 18:44:53', '2025-12-04 18:46:57', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `prodi`
--

CREATE TABLE `prodi` (
  `id` int(11) NOT NULL,
  `kode_prodi` varchar(10) NOT NULL,
  `nama_prodi` varchar(100) NOT NULL,
  `kaprodi_id` int(11) DEFAULT NULL,
  `kaprodi` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prodi`
--

INSERT INTO `prodi` (`id`, `kode_prodi`, `nama_prodi`, `kaprodi_id`, `kaprodi`, `created_at`) VALUES
(1, 'IF', 'Informatika', 4, NULL, '2025-11-29 16:30:34'),
(4, 'BIO', 'Biologi', NULL, NULL, '2025-12-03 17:21:25'),
(5, 'Fis', 'Fisika', NULL, NULL, '2025-12-03 17:21:49'),
(6, 'Kim', 'Kimia', NULL, NULL, '2025-12-03 17:22:11'),
(7, 'Mat', 'Matematika', NULL, NULL, '2025-12-03 17:22:22'),
(8, 'DI', 'Desain interior', NULL, NULL, '2025-12-03 17:22:37'),
(9, 'DKV', 'Desain Komunikasi Visual', NULL, NULL, '2025-12-03 17:22:48');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `role` enum('admin','mahasiswa','dosen') NOT NULL,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$mcC0bjKW1wwRgHA9OtHOMOwDjAZiY9eoJ5bWXQ.KKQmZBKCilODVS', 'Admin IWU', 'admin', 'aktif', '2025-11-29 15:46:41', '2025-12-03 20:19:30'),
(2, 'mahasiswa', '$2y$10$0E6De3Ih9JfHsbsyEYZU9ehp/.dla4N93mCk7BruZaoYX8jjcR06K', 'Mahasiswa Demo', 'mahasiswa', 'aktif', '2025-11-29 15:46:41', NULL),
(8, 'Kaprodi', '$2y$10$whDDkzw1DZD/XsX2OdD5Ae5o3kc.F74CfCGkLfN4aq/rGqegBeSEa', 'Suwinarno Nadajmuddin, S.kom, M.kom', 'dosen', 'aktif', '2025-12-01 16:52:34', NULL),
(12, '1', '$2y$10$WweWtm0ur6SUYiG.j0Tquu9zh/vTM9QnI/dES3.p9yQPi0aPBr/6i', 'Rizki Maulana', 'mahasiswa', 'aktif', '2025-12-03 06:48:28', NULL),
(13, 'dosen1', '$2y$10$/VAO1Ms5OJ43pDQc3P8EUehoBaiNvGVRY07yBbmEKoIl7Ei4JaT1K', 'Dendin Supriadi, S.Pd., M.T.', 'dosen', 'aktif', '2025-12-03 17:30:33', NULL),
(40, '10521114', '$2y$10$S2MU02Jyd0ufmnnirCoej.ZD.uQm4pvun5AxwhyehwyxVjpoG.gvW', 'Meri Sri Anggraeni', 'mahasiswa', 'aktif', '2026-01-19 00:47:59', NULL),
(41, '10521149', '$2y$10$D.qd3OQVAVVDHTDlXebfmuvPMfaMZSjonMKH1PwdEHrt.l7pXBa0K', 'Yusup Azhar', 'mahasiswa', 'aktif', '2026-01-19 00:47:59', NULL),
(42, '10521070', '$2y$10$fHxHGuE.5054QGFT/rMDo.sWOmlc9mqFnSKf2jHgQttdGOuV6gk9O', 'Muhamad Luthfi Ardiansyah Putra', 'mahasiswa', 'aktif', '2026-01-19 00:47:59', NULL),
(43, '10521140', '$2y$10$H3ZIQSJjGOUzdXSFr7ZnqOsLAI3zJEhtx0nbfI2K.QiGz6L5kwqA6', 'Sifa Agustin', 'mahasiswa', 'aktif', '2026-01-19 00:47:59', NULL),
(44, '10521153', '$2y$10$2gv7DssG/GNpYDfJX5t2BeXuZERrvMSuEVeVcofiawiFvXeFS7NAW', 'Imam Rumani', 'mahasiswa', 'aktif', '2026-01-19 00:47:59', NULL),
(45, '10521045', '$2y$10$hhncO057aIbv0gjHifqZSeyLsn0fxTY3HEysKgZbnurLAk5FUBVwq', 'Wa Fia', 'mahasiswa', 'aktif', '2026-01-19 00:47:59', NULL),
(46, '10521072', '$2y$10$.9x/ml9uvYFkf4Vk6Ob1JuzLw8hRIRKeg6B.w6o7hLCaNEmGXWVru', 'Muhlis Hidayatulloh', 'mahasiswa', 'aktif', '2026-01-19 00:47:59', NULL),
(47, '10521056', '$2y$10$tGch9bPH8VC0KntG5zBPVuv2UCndVTYhVET8NSWi9kiV0fbK/3kwq', 'Muhamad Ihsan AlmahdI', 'mahasiswa', 'aktif', '2026-01-19 00:47:59', NULL),
(48, '10521083', '$2y$10$0KoDtGbARwZf7QHC1DZPTePKoBuupS4IUr/4kRHsiWR06ocxC6iIG', 'AI Lela', 'mahasiswa', 'aktif', '2026-01-19 00:47:59', NULL),
(49, '10521078', '$2y$10$yJx2FcdAposDgx3JHR7zxeipOcX9YnV0p3woW1PCEH2DWkBNl2tRK', 'Abdul Gani', 'mahasiswa', 'aktif', '2026-01-19 00:47:59', NULL),
(50, '10521117', '$2y$10$n7FnReJF1zg/00tJDzKkU.o79..Upr0wpJMfa1Uejl2IXiSxFCRTa', 'Muhamad Dikri', 'mahasiswa', 'aktif', '2026-01-19 00:47:59', NULL),
(51, '10521164', '$2y$10$l/VKeUm2cuws2uJuUiF1QeexWMEl.sL6o6Sdrr7o5dYHQIwKS/V3y', 'Siti Halimah', 'mahasiswa', 'aktif', '2026-01-19 00:47:59', NULL),
(52, '10521134', '$2y$10$xPFHJKOcW6ALe8krj249V.NRVGAclc4DLYKxwNntmGVt/8poP9Viq', 'Reza Muhamad Sidik', 'mahasiswa', 'aktif', '2026-01-19 00:48:00', NULL),
(53, '10521129', '$2y$10$eb4EyRliElzLLzgJuqM2Bu5RqbEZrfr0lfqIB6OeSFOYfmNddIqRO', 'Pirman', 'mahasiswa', 'aktif', '2026-01-19 00:48:00', NULL),
(54, '10521068', '$2y$10$B8Nrn5icy6RAq7UZeh2rhOHbOGzFeZmV1QDIbNfGD0tX.4Nx29eTe', 'Mochamad Rifqi Hira Rafiqi', 'mahasiswa', 'aktif', '2026-01-19 00:48:00', NULL),
(55, '10520056', '$2y$10$oqpspQC5H0FU3lFi55PqMeAb9DDHsA7GZ.39q3k5XmySFhgLNE5JC', 'Sigit Hardianto', 'mahasiswa', 'aktif', '2026-01-19 00:48:00', NULL),
(56, '10521061', '$2y$10$AVIAfUNHUn2i8DWsoQ5LYOfyeUelYq996oOhlqxwIBvEYeIysRQ1K', 'Dian Aldera Herdiansyah', 'mahasiswa', 'aktif', '2026-01-19 00:48:00', NULL),
(57, '10521065', '$2y$10$IUtzwr/Dfxafn4kdoomfpuoo.PdYLjSo/zJk.Hp3TX9cMDYSk4GOW', 'Husni Moh Jaelani', 'mahasiswa', 'aktif', '2026-01-19 00:48:00', NULL),
(58, '10521063', '$2y$10$/cpnuvsgUf5I4z5BHo9X7OR7EPASnrkBNyq0eeGJJAXlQ12BPFoqe', 'Egi Hamdani', 'mahasiswa', 'aktif', '2026-01-19 00:48:00', NULL),
(59, '10521039', '$2y$10$egq6c1Kq3dzdDuEfDlImduPx9yBRP4S6ijP.JnnYjm7fgsBFvFsGG', 'Roqib Tizar Fawaik', 'mahasiswa', 'aktif', '2026-01-19 00:48:00', NULL),
(60, 'dosen2', '$2y$10$aq1e1zVL1C4pzm5t1el2ZemvwJ07OSlcIXNiyivTdvKcLBiuzcAza', 'Muhamad Nawawi, S.Kom., M.Kom.', 'dosen', 'aktif', '2026-01-19 00:50:35', NULL),
(62, 'dosen3', '$2y$10$eowPsoPOq3KDSLgsFJVcOeTXUjoH.mxt/crUP/qFP8on9XAarVcMe', 'Muhammad Fadli, S.T., M.Kom.', 'dosen', 'aktif', '2026-01-19 00:52:53', NULL),
(65, 'dosen4', '$2y$10$vMBGyYWR.9y1wyeJbLcEUefdrA1q.iLB4ciVjxU8YjHMoy8i4j63K', 'Dra. Tuti Hartati, M.T.', 'dosen', 'aktif', '2026-01-19 00:53:40', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dosen`
--
ALTER TABLE `dosen`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nip` (`nip`),
  ADD KEY `fk_dosen_user` (`user_id`),
  ADD KEY `fk_dosen_prodi` (`prodi_id`);

--
-- Indexes for table `jadwal`
--
ALTER TABLE `jadwal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_jadwal_pengajuan` (`pengajuan_id`),
  ADD KEY `fk_jadwal_mhs` (`mahasiswa_id`),
  ADD KEY `fk_jadwal_dosen_pemb` (`dosen_pembimbing_id`),
  ADD KEY `fk_jadwal_dosen_peng` (`dosen_penguji_id`);

--
-- Indexes for table `laporan_akhir`
--
ALTER TABLE `laporan_akhir`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_laporan_pengajuan` (`pengajuan_id`),
  ADD KEY `fk_laporan_mhs` (`mahasiswa_id`);

--
-- Indexes for table `log_bimbingan`
--
ALTER TABLE `log_bimbingan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_log_bimbingan_mhs` (`mahasiswa_id`),
  ADD KEY `fk_log_bimbingan_dosen` (`dosen_id`);

--
-- Indexes for table `log_bimbingan_pkl`
--
ALTER TABLE `log_bimbingan_pkl`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lbpkl_mhs` (`id_mahasiswa`),
  ADD KEY `idx_lbpkl_dosen` (`id_dosen`),
  ADD KEY `idx_lbpkl_tgl` (`tanggal`);

--
-- Indexes for table `log_pkl`
--
ALTER TABLE `log_pkl`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_log_pkl_mhs` (`mahasiswa_id`);

--
-- Indexes for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nim` (`nim`),
  ADD KEY `fk_mahasiswa_user` (`user_id`),
  ADD KEY `fk_mahasiswa_prodi` (`prodi_id`);

--
-- Indexes for table `pengajuan`
--
ALTER TABLE `pengajuan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pengajuan_mhs` (`mahasiswa_id`),
  ADD KEY `fk_pengajuan_dosen` (`pembimbing_id`);

--
-- Indexes for table `prodi`
--
ALTER TABLE `prodi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_prodi` (`kode_prodi`),
  ADD KEY `fk_prodi_kaprodi` (`kaprodi_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dosen`
--
ALTER TABLE `dosen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `jadwal`
--
ALTER TABLE `jadwal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `laporan_akhir`
--
ALTER TABLE `laporan_akhir`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `log_bimbingan`
--
ALTER TABLE `log_bimbingan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `log_bimbingan_pkl`
--
ALTER TABLE `log_bimbingan_pkl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `log_pkl`
--
ALTER TABLE `log_pkl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `pengajuan`
--
ALTER TABLE `pengajuan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `prodi`
--
ALTER TABLE `prodi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dosen`
--
ALTER TABLE `dosen`
  ADD CONSTRAINT `fk_dosen_prodi` FOREIGN KEY (`prodi_id`) REFERENCES `prodi` (`id`),
  ADD CONSTRAINT `fk_dosen_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `jadwal`
--
ALTER TABLE `jadwal`
  ADD CONSTRAINT `fk_jadwal_dosen_pemb` FOREIGN KEY (`dosen_pembimbing_id`) REFERENCES `dosen` (`id`),
  ADD CONSTRAINT `fk_jadwal_dosen_peng` FOREIGN KEY (`dosen_penguji_id`) REFERENCES `dosen` (`id`),
  ADD CONSTRAINT `fk_jadwal_mhs` FOREIGN KEY (`mahasiswa_id`) REFERENCES `mahasiswa` (`id`),
  ADD CONSTRAINT `fk_jadwal_pengajuan` FOREIGN KEY (`pengajuan_id`) REFERENCES `pengajuan` (`id`);

--
-- Constraints for table `laporan_akhir`
--
ALTER TABLE `laporan_akhir`
  ADD CONSTRAINT `fk_laporan_mhs` FOREIGN KEY (`mahasiswa_id`) REFERENCES `mahasiswa` (`id`),
  ADD CONSTRAINT `fk_laporan_pengajuan` FOREIGN KEY (`pengajuan_id`) REFERENCES `pengajuan` (`id`);

--
-- Constraints for table `log_bimbingan`
--
ALTER TABLE `log_bimbingan`
  ADD CONSTRAINT `fk_log_bimbingan_dosen` FOREIGN KEY (`dosen_id`) REFERENCES `dosen` (`id`),
  ADD CONSTRAINT `fk_log_bimbingan_mhs` FOREIGN KEY (`mahasiswa_id`) REFERENCES `mahasiswa` (`id`);

--
-- Constraints for table `log_pkl`
--
ALTER TABLE `log_pkl`
  ADD CONSTRAINT `fk_log_pkl_mhs` FOREIGN KEY (`mahasiswa_id`) REFERENCES `mahasiswa` (`id`);

--
-- Constraints for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD CONSTRAINT `fk_mahasiswa_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pengajuan`
--
ALTER TABLE `pengajuan`
  ADD CONSTRAINT `fk_pengajuan_dosen` FOREIGN KEY (`pembimbing_id`) REFERENCES `dosen` (`id`),
  ADD CONSTRAINT `fk_pengajuan_mhs` FOREIGN KEY (`mahasiswa_id`) REFERENCES `mahasiswa` (`id`);

--
-- Constraints for table `prodi`
--
ALTER TABLE `prodi`
  ADD CONSTRAINT `fk_prodi_kaprodi` FOREIGN KEY (`kaprodi_id`) REFERENCES `dosen` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

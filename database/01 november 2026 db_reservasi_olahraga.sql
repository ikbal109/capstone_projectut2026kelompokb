-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 01, 2026 at 03:22 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_reservasi_olahraga`
--

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `id` int(11) NOT NULL,
  `kode_booking` varchar(20) NOT NULL,
  `sarana_id` int(11) NOT NULL,
  `nama_penyewa` varchar(100) NOT NULL,
  `no_hp` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `instansi` varchar(150) DEFAULT NULL,
  `tanggal` date NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `durasi_jam` int(11) NOT NULL,
  `harga_per_jam` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `diskon_id` int(11) DEFAULT NULL,
  `diskon_persen` decimal(5,2) DEFAULT 0.00,
  `total_bayar` decimal(12,2) NOT NULL,
  `metode_pembayaran` enum('qris','transfer') DEFAULT NULL,
  `status_pembayaran` enum('belum_bayar','menunggu_verifikasi','berhasil','gagal') DEFAULT 'belum_bayar',
  `bukti_transfer` varchar(255) DEFAULT NULL,
  `catatan_pembayaran` text DEFAULT NULL,
  `verified_payment_by` int(11) DEFAULT NULL,
  `verified_payment_at` datetime DEFAULT NULL,
  `status` enum('pending','terverifikasi','ditolak','selesai','batal') DEFAULT 'pending',
  `kode_gambar` varchar(255) DEFAULT NULL,
  `catatan_petugas` text DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`id`, `kode_booking`, `sarana_id`, `nama_penyewa`, `no_hp`, `email`, `instansi`, `tanggal`, `jam_mulai`, `jam_selesai`, `durasi_jam`, `harga_per_jam`, `subtotal`, `diskon_id`, `diskon_persen`, `total_bayar`, `metode_pembayaran`, `status_pembayaran`, `bukti_transfer`, `catatan_pembayaran`, `verified_payment_by`, `verified_payment_at`, `status`, `kode_gambar`, `catatan_petugas`, `verified_by`, `verified_at`, `created_at`) VALUES
(10, 'BKG-261101-D4658', 1, 'tes6', '085270821454', 'hsgvsudh@gmail.com', 'PDIP', '2026-12-27', '08:00:00', '11:00:00', 3, 150000.00, 450000.00, NULL, 0.00, 450000.00, 'transfer', 'berhasil', 'assets/bukti_transfer/bukti-10-1793539313.jpg', '', 1, '2026-11-01 20:53:57', 'terverifikasi', 'assets/booking_codes/kode_BKG-261101-D4658.svg', '', 1, '2026-11-01 20:54:01', '2026-11-01 13:21:48'),
(11, 'BKG-261101-824EB', 2, 'tes', '085172203031', 'hsgvsudh@gmail.com', 'GOLKAR', '2026-12-17', '08:00:00', '11:00:00', 3, 50000.00, 150000.00, NULL, 0.00, 150000.00, 'transfer', 'belum_bayar', NULL, NULL, NULL, NULL, 'pending', 'assets/booking_codes/kode_BKG-261101-824EB.svg', NULL, NULL, NULL, '2026-11-01 13:52:23'),
(12, 'BKG-261101-C68F0', 1, 'tes2', '085172203031', 'hsgvsudh@gmail.com', 'PDIP', '2026-12-30', '12:00:00', '15:00:00', 3, 150000.00, 450000.00, NULL, 0.00, 450000.00, 'transfer', 'belum_bayar', NULL, NULL, NULL, NULL, 'pending', 'assets/booking_codes/kode_BKG-261101-C68F0.svg', NULL, NULL, NULL, '2026-11-01 13:52:42');

-- --------------------------------------------------------

--
-- Table structure for table `pengaturan`
--

CREATE TABLE `pengaturan` (
  `id` int(11) NOT NULL,
  `kunci` varchar(50) NOT NULL,
  `nilai` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengaturan`
--

INSERT INTO `pengaturan` (`id`, `kunci`, `nilai`, `updated_at`) VALUES
(1, 'nama_bank', 'Bank Kalsel', '2026-10-16 16:41:26'),
(2, 'nomor_rekening', '1234567890', '2026-10-16 16:41:26'),
(3, 'nama_pemilik_rekening', 'BPSDMD Provinsi Kalimantan Selatan', '2026-10-16 16:41:26'),
(4, 'catatan_tunai', 'Pembayaran dilakukan secara tunai di kantor BPSDMD Provinsi Kalimantan Selatan pada jam kerja (Senin-Jumat, 08.00-16.00 WITA). Harap membawa kode booking.', '2026-10-16 16:41:26'),
(5, 'qris_image', 'qris-1793281559.png', '2026-10-29 13:45:59');

-- --------------------------------------------------------

--
-- Table structure for table `sarana`
--

CREATE TABLE `sarana` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `slug` varchar(150) DEFAULT NULL,
  `jenis` varchar(50) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `kelengkapan` text DEFAULT NULL,
  `harga_per_jam` decimal(12,2) NOT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `galeri` text DEFAULT NULL,
  `status` enum('tersedia','maintenance') DEFAULT 'tersedia',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sarana`
--

INSERT INTO `sarana` (`id`, `nama`, `slug`, `jenis`, `deskripsi`, `kelengkapan`, `harga_per_jam`, `gambar`, `galeri`, `status`, `created_at`) VALUES
(1, 'Lapangan Futsal', 'lapangan-futsal', 'Futsal', 'Lapangan futsal indoor dengan rumput sintetis', 'TES\r\ntes\r\nTes', 150000.00, 'sarana-1793280610-9efdd6.jpeg', NULL, 'tersedia', '2026-10-14 13:31:32'),
(2, 'Lapangan Tenis', 'lapangan-tenis', 'Tenis', 'Lapangan Tenis', '', 50000.00, 'sarana-1793280626-006f3c.jpg', NULL, 'tersedia', '2026-10-14 13:31:32');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('admin','petugas') NOT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `aktif` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `role`, `no_hp`, `aktif`, `created_at`) VALUES
(1, 'admin', '$2y$10$yf3HOyWb8GJiWVOv6LpbIeEBXgqMuUbCLJipUoYZyuGraO95hQq/6', 'Administrator', 'admin', NULL, 1, '2026-10-14 13:31:32');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_booking` (`kode_booking`),
  ADD KEY `sarana_id` (`sarana_id`),
  ADD KEY `diskon_id` (`diskon_id`),
  ADD KEY `verified_by` (`verified_by`);

--
-- Indexes for table `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kunci` (`kunci`);

--
-- Indexes for table `sarana`
--
ALTER TABLE `sarana`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

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
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `pengaturan`
--
ALTER TABLE `pengaturan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sarana`
--
ALTER TABLE `sarana`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `booking_ibfk_1` FOREIGN KEY (`sarana_id`) REFERENCES `sarana` (`id`),
  ADD CONSTRAINT `booking_ibfk_2` FOREIGN KEY (`diskon_id`) REFERENCES `diskon` (`id`),
  ADD CONSTRAINT `booking_ibfk_3` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

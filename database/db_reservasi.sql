CREATE DATABASE IF NOT EXISTS db_reservasi_olahraga;
USE db_reservasi_olahraga;

-- Users: admin & petugas
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  nama_lengkap VARCHAR(100) NOT NULL,
  role ENUM('admin','petugas') NOT NULL,
  no_hp VARCHAR(20),
  aktif TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sarana olahraga
CREATE TABLE sarana (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  jenis VARCHAR(50),                 -- futsal, badminton, basket, dll
  deskripsi TEXT,
  harga_per_jam DECIMAL(12,2) NOT NULL,
  gambar VARCHAR(255),
  status ENUM('tersedia','maintenance') DEFAULT 'tersedia',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Diskon (berlaku sesuai rentang tanggal)
CREATE TABLE diskon (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  persen DECIMAL(5,2) NOT NULL,      -- contoh: 20.00 = 20%
  tanggal_mulai DATE NOT NULL,
  tanggal_selesai DATE NOT NULL,
  aktif TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Booking
CREATE TABLE booking (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode_booking VARCHAR(20) UNIQUE NOT NULL,
  sarana_id INT NOT NULL,
  nama_penyewa VARCHAR(100) NOT NULL,
  no_hp VARCHAR(20) NOT NULL,
  email VARCHAR(100),
  instansi VARCHAR(150),
  tanggal DATE NOT NULL,
  jam_mulai TIME NOT NULL,
  jam_selesai TIME NOT NULL,
  durasi_jam INT NOT NULL,
  harga_per_jam DECIMAL(12,2) NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  diskon_id INT NULL,
  diskon_persen DECIMAL(5,2) DEFAULT 0,
  total_bayar DECIMAL(12,2) NOT NULL,
  status ENUM('pending','terverifikasi','ditolak','selesai','batal') DEFAULT 'pending',
  kode_gambar VARCHAR(255),
  catatan_petugas TEXT,
  verified_by INT NULL,
  verified_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sarana_id) REFERENCES sarana(id),
  FOREIGN KEY (diskon_id) REFERENCES diskon(id),
  FOREIGN KEY (verified_by) REFERENCES users(id)
);

-- User default: admin & petugas (password = "admin123" / "petugas123", hash bcrypt)
INSERT INTO users (username, password, nama_lengkap, role) VALUES
('admin', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1HlCS4bZJ18JuywdBq3WQ4oG8s4y0m2', 'Administrator', 'admin'),
('petugas', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1HlCS4bZJ18JuywdBq3WQ4oG8s4y0m2', 'Petugas Lapangan', 'petugas');

INSERT INTO sarana (nama, jenis, deskripsi, harga_per_jam) VALUES
('Lapangan Futsal A', 'Futsal', 'Lapangan futsal indoor dengan rumput sintetis', 150000),
('Lapangan Badminton 1', 'Badminton', 'Lapangan badminton standar BWF', 50000),
('Lapangan Basket', 'Basket', 'Lapangan basket outdoor', 100000);
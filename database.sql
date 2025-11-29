-- Database untuk Aplikasi Pengelolaan Administrasi Guru
CREATE DATABASE IF NOT EXISTS administrasi_guru;
USE administrasi_guru;

-- Tabel untuk menyimpan data administrasi guru
CREATE TABLE IF NOT EXISTS administrasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(255) NOT NULL,
    mata_pelajaran VARCHAR(255) NOT NULL,
    pertemuan_ke INT NOT NULL,
    tanggal DATE NOT NULL,
    materi TEXT NOT NULL,
    foto VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk menyimpan data user/login
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(255) NOT NULL,
    no_whatsapp VARCHAR(20) DEFAULT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Update tabel users untuk menambahkan kolom no_whatsapp jika belum ada
ALTER TABLE users ADD COLUMN IF NOT EXISTS no_whatsapp VARCHAR(20) DEFAULT NULL AFTER nama_lengkap;

-- Update tabel users untuk menambahkan kolom role jika belum ada
ALTER TABLE users ADD COLUMN IF NOT EXISTS role ENUM('admin', 'user') DEFAULT 'user' AFTER no_whatsapp;

-- Insert user default (username: admin, password: admin123)
-- Password di-hash menggunakan password_hash PHP (default: admin123)
-- Jika hash tidak bekerja, jalankan file fix_password.php untuk memperbaiki password
-- Atau jalankan setup_user.php untuk membuat user baru
INSERT INTO users (username, password, nama_lengkap, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin')
ON DUPLICATE KEY UPDATE role = 'admin';


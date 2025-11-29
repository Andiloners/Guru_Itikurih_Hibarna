-- Update database untuk menambahkan kolom role
-- Jalankan file ini jika database sudah ada dan ingin menambahkan fitur role

USE administrasi_guru;

-- Tambahkan kolom role jika belum ada
ALTER TABLE users ADD COLUMN IF NOT EXISTS role ENUM('admin', 'user') DEFAULT 'user' AFTER no_whatsapp;

-- Update user admin yang sudah ada menjadi role admin
UPDATE users SET role = 'admin' WHERE username = 'admin';


-- Update database untuk menambahkan kolom nomor WhatsApp
-- Jalankan file ini jika database sudah ada dan ingin menambahkan fitur WhatsApp

USE administrasi_guru;

-- Tambahkan kolom no_whatsapp jika belum ada
ALTER TABLE users ADD COLUMN no_whatsapp VARCHAR(20) DEFAULT NULL AFTER nama_lengkap;


# Aplikasi Pengelolaan Administrasi Guru

Aplikasi web berbasis PHP untuk mengelola administrasi guru dengan fitur upload foto.

## Fitur

- ✅ **Sistem Login** - Autentikasi pengguna dengan session
- ✅ Input data guru (Nama, Mata Pelajaran, Pertemuan, Tanggal, Materi)
- ✅ Upload foto dengan kapasitas maksimal 2MB
- ✅ CRUD lengkap (Create, Read, Update, Delete)
- ✅ Tampilan modern dan responsive
- ✅ Preview foto dalam modal

## Persyaratan

- PHP 7.4 atau lebih tinggi
- MySQL/MariaDB
- Web server (Apache/Nginx) atau PHP built-in server
- Extension PHP: mysqli, fileinfo, gd (untuk image processing)

## Instalasi

1. **Import Database**
   - Buka phpMyAdmin atau MySQL client
   - Import file `database.sql` untuk membuat database dan tabel
   - Database akan otomatis membuat user default:
     - Username: `admin`
     - Password: `admin123`
   - Jika user tidak terbuat, jalankan `setup_user.php` melalui browser

2. **Konfigurasi Database**
   - Edit file `config.php`
   - Sesuaikan kredensial database:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     define('DB_NAME', 'administrasi_guru');
     ```

3. **Set Permission Folder Upload**
   - Pastikan folder `uploads/` memiliki permission write (akan dibuat otomatis jika belum ada)

4. **Jalankan Aplikasi**
   
   **Menggunakan PHP Built-in Server:**
   ```bash
   php -S localhost:8000
   ```
   
   Kemudian buka browser dan akses: `http://localhost:8000/login.php`

   **Menggunakan XAMPP/WAMP:**
   - Copy folder aplikasi ke `htdocs` (XAMPP) atau `www` (WAMP)
   - Akses melalui: `http://localhost/administrasi-guru/login.php`
   
   **Login:**
   - Username default: `admin`
   - Password default: `admin123`
   - Setelah login, Anda akan diarahkan ke halaman utama

## Struktur File

```
administrasi-guru/
├── config.php          # Konfigurasi database dan upload
├── auth.php            # Helper untuk pengecekan session
├── login.php           # Halaman login
├── login_process.php   # Proses autentikasi login
├── logout.php          # Proses logout
├── setup_user.php      # Setup user pertama kali (opsional)
├── index.php           # Halaman utama (daftar data) - memerlukan login
├── form.php            # Form tambah/edit data - memerlukan login
├── process.php         # Handler untuk proses CRUD - memerlukan login
├── delete.php          # Handler untuk hapus data - memerlukan login
├── style.css           # Styling aplikasi
├── database.sql        # Script SQL untuk database (termasuk tabel users)
├── uploads/            # Folder untuk menyimpan foto (auto-created)
└── README.md           # Dokumentasi
```

## Penggunaan

1. **Login**
   - Buka halaman login (`login.php`)
   - Masukkan username dan password
   - Klik tombol "Masuk"
   - Jika berhasil, Anda akan diarahkan ke halaman utama

2. **Tambah Data Baru**
   - Setelah login, klik tombol "+ Tambah Data Baru" di halaman utama
   - Isi semua field yang wajib
   - Upload foto (opsional, maksimal 2MB)
   - Klik "Simpan Data"

3. **Edit Data**
   - Klik tombol "Edit" pada data yang ingin diubah
   - Ubah data yang diperlukan
   - Upload foto baru (opsional, akan mengganti foto lama)
   - Klik "Update Data"

4. **Hapus Data**
   - Klik tombol "Hapus" pada data yang ingin dihapus
   - Konfirmasi penghapusan
   - Foto akan otomatis terhapus juga

5. **Lihat Foto**
   - Klik pada thumbnail foto untuk melihat dalam ukuran penuh

6. **Logout**
   - Klik tombol "Logout" di header halaman utama
   - Konfirmasi logout
   - Anda akan diarahkan kembali ke halaman login

## Keamanan

- **Sistem Autentikasi** - Semua halaman memerlukan login
- **Password Hashing** - Password disimpan menggunakan password_hash PHP
- **Session Management** - Menggunakan PHP session untuk menjaga status login
- Validasi input pada server-side
- Validasi ukuran dan tipe file
- Sanitasi output untuk mencegah XSS
- Prepared statements untuk mencegah SQL injection
- Penamaan file unik untuk menghindari konflik

## Catatan

- Ukuran maksimal foto: 2MB
- Format foto yang didukung: JPG, PNG, GIF
- Folder `uploads/` akan dibuat otomatis saat pertama kali diakses

## Lisensi

Aplikasi ini dibuat untuk keperluan administrasi guru.


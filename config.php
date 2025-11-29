<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');        // Host database
define('DB_USER', 'root');             // Username database
define('DB_PASS', '');                 // Password database (kosong)
define('DB_NAME', 'administrasi_guru'); // Nama database

// Konfigurasi Upload
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB dalam bytes

// Koneksi Database
function getConnection() {
    try {
        // Cek extension mysqli
        if (!extension_loaded('mysqli')) {
            die("Error: Extension mysqli tidak tersedia. Silakan install extension mysqli di PHP.");
        }
        
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            $errorMsg = "Koneksi database gagal: " . $conn->connect_error . "<br><br>";
            $errorMsg .= "Kemungkinan masalah:<br>";
            $errorMsg .= "1. Database MySQL/MariaDB tidak berjalan<br>";
            $errorMsg .= "2. Username atau password salah<br>";
            $errorMsg .= "3. Database '" . DB_NAME . "' belum dibuat<br>";
            $errorMsg .= "4. Host '" . DB_HOST . "' tidak dapat diakses<br><br>";
            $errorMsg .= "Solusi: Import file database.sql ke MySQL atau periksa konfigurasi di config.php";
            die($errorMsg);
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}

// Membuat folder upload jika belum ada
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}
?>


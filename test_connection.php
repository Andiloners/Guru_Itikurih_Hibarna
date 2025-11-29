<?php
/**
 * File untuk test koneksi database
 * Akses file ini melalui browser untuk melihat status koneksi
 */

echo "<h2>Test Koneksi Database</h2>";
echo "<pre>";

// Test 1: Cek PHP Version
echo "1. PHP Version: " . phpversion() . "\n";
echo "   Status: " . (version_compare(phpversion(), '7.4.0', '>=') ? "✓ OK" : "✗ Minimal PHP 7.4") . "\n\n";

// Test 2: Cek Extension mysqli
echo "2. Extension mysqli: ";
if (extension_loaded('mysqli')) {
    echo "✓ Tersedia\n";
} else {
    echo "✗ TIDAK TERSEDIA - Install extension mysqli\n";
}
echo "\n";

// Test 3: Cek Extension fileinfo
echo "3. Extension fileinfo: ";
if (extension_loaded('fileinfo')) {
    echo "✓ Tersedia\n";
} else {
    echo "✗ TIDAK TERSEDIA - Install extension fileinfo\n";
}
echo "\n";

// Test 4: Cek file config.php
echo "4. File config.php: ";
if (file_exists('config.php')) {
    echo "✓ Ada\n";
    require_once 'config.php';
} else {
    echo "✗ TIDAK ADA\n";
    die("File config.php tidak ditemukan!");
}
echo "\n";

// Test 5: Cek koneksi database
echo "5. Koneksi Database:\n";
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    if ($conn->connect_error) {
        echo "   ✗ Gagal: " . $conn->connect_error . "\n";
        echo "\n   Kemungkinan masalah:\n";
        echo "   - MySQL/MariaDB tidak berjalan\n";
        echo "   - Username/password salah\n";
        echo "   - Host tidak dapat diakses\n";
    } else {
        echo "   ✓ Koneksi berhasil\n";
        
        // Test 6: Cek database
        echo "\n6. Database '" . DB_NAME . "':\n";
        $dbCheck = $conn->select_db(DB_NAME);
        
        if (!$dbCheck) {
            echo "   ✗ Database tidak ada\n";
            echo "   Solusi: Import file database.sql ke MySQL\n";
        } else {
            echo "   ✓ Database ada\n";
            
            // Test 7: Cek tabel
            echo "\n7. Tabel 'administrasi':\n";
            $result = $conn->query("SHOW TABLES LIKE 'administrasi'");
            
            if ($result && $result->num_rows > 0) {
                echo "   ✓ Tabel ada\n";
                
                // Test 8: Cek struktur tabel
                echo "\n8. Struktur Tabel:\n";
                $result = $conn->query("DESCRIBE administrasi");
                if ($result) {
                    echo "   Kolom:\n";
                    while ($row = $result->fetch_assoc()) {
                        echo "   - " . $row['Field'] . " (" . $row['Type'] . ")\n";
                    }
                }
            } else {
                echo "   ✗ Tabel tidak ada\n";
                echo "   Solusi: Import file database.sql ke MySQL\n";
            }
        }
        
        $conn->close();
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// Test 9: Cek folder uploads
echo "\n9. Folder uploads:\n";
if (!file_exists(UPLOAD_DIR)) {
    echo "   ⚠ Folder belum ada, akan dibuat otomatis\n";
    if (mkdir(UPLOAD_DIR, 0777, true)) {
        echo "   ✓ Folder berhasil dibuat\n";
    } else {
        echo "   ✗ Gagal membuat folder\n";
    }
} else {
    echo "   ✓ Folder ada\n";
    if (is_writable(UPLOAD_DIR)) {
        echo "   ✓ Folder dapat ditulis\n";
    } else {
        echo "   ✗ Folder tidak dapat ditulis (permission issue)\n";
    }
}

echo "\n</pre>";
echo "<hr>";
echo "<p><a href='index.php'>Kembali ke Aplikasi</a></p>";
?>


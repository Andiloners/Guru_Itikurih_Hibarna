<?php
/**
 * File untuk mengirim notifikasi WhatsApp otomatis
 * File ini bisa dijalankan via cron job atau scheduled task
 * 
 * Cara menjalankan:
 * 1. Via Cron Job (Linux/Mac): 
 *    0,30 (setiap jam di menit 0 dan 30) /usr/bin/php /path/to/auto_notifikasi.php
 * 
 * 2. Via Scheduled Task (Windows):
 *    php C:\xampp\htdocs\administrasi_guru\auto_notifikasi.php
 * 
 * 3. Via Browser (untuk testing):
 *    http://localhost/administrasi-guru/auto_notifikasi.php
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering untuk browser
if (php_sapi_name() !== 'cli') {
    ob_start();
    header('Content-Type: text/html; charset=utf-8');
}

try {
    require_once 'config.php';
} catch (Exception $e) {
    if (php_sapi_name() !== 'cli') {
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Error</title></head><body>";
        echo "<h2 style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</h2>";
        echo "<p><a href='index.php'>Kembali ke Dashboard</a></p>";
        echo "</body></html>";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
    exit(1);
}

// Log file
$log_file = 'logs/notifikasi.log';
$log_dir = dirname($log_file);

// Buat folder logs jika belum ada
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0777, true);
}

function writeLog($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
    
    // Output untuk browser dan CLI
    if (php_sapi_name() !== 'cli') {
        echo htmlspecialchars($log_message) . "<br>";
        flush();
        ob_flush();
    } else {
        echo $log_message;
    }
}

writeLog("=== Auto Notifikasi WhatsApp Dimulai ===");

try {
    $conn = getConnection();
} catch (Exception $e) {
    writeLog("ERROR: Gagal koneksi database - " . $e->getMessage());
    if (php_sapi_name() !== 'cli') {
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Error</title></head><body>";
        echo "<h2 style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</h2>";
        echo "<p><a href='index.php'>Kembali ke Dashboard</a></p>";
        echo "</body></html>";
    }
    exit(1);
}

// Ambil data yang belum diupload dalam 7 hari terakhir
$tanggal_7_hari = date('Y-m-d', strtotime('-7 days'));
$tanggal_hari_ini = date('Y-m-d');

// Query untuk mendapatkan data yang belum upload dengan nomor WhatsApp
$query = "
    SELECT DISTINCT a.nama, u.no_whatsapp, 
           GROUP_CONCAT(CONCAT(a.mata_pelajaran, '|', a.pertemuan_ke, '|', a.tanggal, '|', LEFT(a.materi, 50)) SEPARATOR '||') as data_list,
           COUNT(*) as jumlah
    FROM administrasi a
    LEFT JOIN users u ON u.nama_lengkap = a.nama
    WHERE a.tanggal >= '$tanggal_7_hari'
    AND (a.foto IS NULL OR a.foto = '')
    AND u.no_whatsapp IS NOT NULL
    AND u.no_whatsapp != ''
    GROUP BY a.nama, u.no_whatsapp
    ORDER BY a.nama ASC
";

$result = $conn->query($query);

if (!$result) {
    writeLog("Error: " . $conn->error);
    $conn->close();
    exit;
}

$total_notifikasi = 0;
$notifikasi_berhasil = 0;
$notifikasi_gagal = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $nama = $row['nama'];
        $no_whatsapp = $row['no_whatsapp'];
        $data_list_str = $row['data_list'];
        $jumlah = $row['jumlah'];
        
        // Parse data list
        $data_list = [];
        $items = explode('||', $data_list_str);
        foreach ($items as $item) {
            $parts = explode('|', $item);
            if (count($parts) >= 3) {
                $data_list[] = [
                    'mata_pelajaran' => $parts[0],
                    'pertemuan_ke' => $parts[1],
                    'tanggal' => $parts[2],
                    'materi' => $parts[3] ?? ''
                ];
            }
        }
        
        // Generate pesan WhatsApp
        $message = "Halo *" . $nama . "*,\n\n";
        $message .= "📢 *PEMBERITAHUAN UPLOAD ADMINISTRASI*\n";
        $message .= "SMK ITIKURIH HIBARNA\n\n";
        $message .= "Anda memiliki *$jumlah* data administrasi yang *belum diupload*:\n\n";
        
        $no = 1;
        foreach ($data_list as $item) {
            $message .= $no . ". *" . $item['mata_pelajaran'] . "*\n";
            $message .= "   Pertemuan ke-" . $item['pertemuan_ke'] . "\n";
            $message .= "   Tanggal: " . date('d/m/Y', strtotime($item['tanggal'])) . "\n";
            if (!empty($item['materi'])) {
                $materi = substr($item['materi'], 0, 50);
                $message .= "   Materi: " . $materi . (strlen($item['materi']) > 50 ? '...' : '') . "\n";
            }
            $message .= "\n";
            $no++;
        }
        
        $message .= "⏰ *Segera upload sebelum waktu terlambat!*\n\n";
        $message .= "Terima kasih 🙏\n";
        $message .= "_Pesan otomatis dari Sistem Administrasi Guru_";
        
        // Generate WhatsApp URL
        $no_whatsapp_clean = preg_replace('/[^0-9]/', '', $no_whatsapp);
        $message_encoded = urlencode($message);
        $whatsapp_url = "https://wa.me/$no_whatsapp_clean?text=$message_encoded";
        
        // Simpan ke file untuk dikirim (karena tidak bisa langsung kirim tanpa API)
        $notifikasi_file = "logs/notifikasi_pending_" . date('Y-m-d') . ".txt";
        $notifikasi_data = "$nama|$no_whatsapp_clean|$whatsapp_url\n";
        file_put_contents($notifikasi_file, $notifikasi_data, FILE_APPEND);
        
        writeLog("Notifikasi untuk $nama ($no_whatsapp_clean) - $jumlah data - URL: $whatsapp_url");
        $total_notifikasi++;
        $notifikasi_berhasil++;
    }
} else {
    writeLog("Tidak ada data yang perlu dikirim notifikasi");
}

$conn->close();

writeLog("=== Auto Notifikasi Selesai ===");
writeLog("Total: $total_notifikasi notifikasi");
writeLog("Berhasil: $notifikasi_berhasil");
writeLog("Gagal: $notifikasi_gagal");
writeLog("");

// Output HTML header untuk browser
if (php_sapi_name() !== 'cli') {
    echo "<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Auto Notifikasi WhatsApp</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #333; }
        pre { background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        .btn:hover { background: #5568d3; }
    </style>
</head>
<body>
    <div class='container'>
        <h2>📱 Auto Notifikasi WhatsApp</h2>";
    
    if (file_exists($log_file)) {
        echo "<pre>";
        echo htmlspecialchars(file_get_contents($log_file));
        echo "</pre>";
    } else {
        echo "<p>Log file belum dibuat.</p>";
    }
    
    echo "<p><strong>Total:</strong> $total_notifikasi notifikasi</p>";
    echo "<p><strong>Berhasil:</strong> $notifikasi_berhasil</p>";
    echo "<p><strong>Gagal:</strong> $notifikasi_gagal</p>";
    echo "<p><a href='index.php' class='btn'>Kembali ke Dashboard</a></p>";
    echo "<p><a href='notifikasi.php' class='btn' style='background: #25D366;'>Lihat Halaman Notifikasi</a></p>";
    echo "</div></body></html>";
    ob_end_flush();
}
?>


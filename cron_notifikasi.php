<?php
/**
 * File untuk cron job - Auto send notifikasi WhatsApp
 * 
 * Setup Cron Job:
 * 
 * Linux/Mac:
 * crontab -e
 * Tambahkan baris:
 * 0,30 * * * * /usr/bin/php /path/to/administrasi_guru/cron_notifikasi.php >> /path/to/logs/cron.log 2>&1
 * 
 * Windows (Task Scheduler):
 * 1. Buka Task Scheduler
 * 2. Create Basic Task
 * 3. Set trigger (misalnya setiap 30 menit)
 * 4. Action: Start a program
 * 5. Program: C:\xampp\php\php.exe
 * 6. Arguments: C:\xampp\htdocs\administrasi_guru\cron_notifikasi.php
 */

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Set execution time limit
set_time_limit(300); // 5 menit

// Enable error reporting untuk debugging
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
$log_dir = __DIR__ . '/logs';
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0777, true);
}

$log_file = $log_dir . '/cron_notifikasi_' . date('Y-m-d') . '.log';

function writeLog($message, $log_file) {
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
        h1 { color: #333; }
        pre { background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        .btn:hover { background: #5568d3; }
        .success { color: #25D366; }
        .error { color: #dc3545; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>📱 Auto Notifikasi WhatsApp</h1>
        <p>Status pengiriman notifikasi:</p>
        <pre>";
}

writeLog("=== CRON: Auto Notifikasi WhatsApp Dimulai ===", $log_file);

try {
    $conn = getConnection();
} catch (Exception $e) {
    writeLog("ERROR: Gagal koneksi database - " . $e->getMessage(), $log_file);
    if (php_sapi_name() !== 'cli') {
        echo "</pre><p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<a href='index.php' class='btn'>Kembali ke Dashboard</a></div></body></html>";
    }
    exit(1);
}

// Cek apakah ada notifikasi yang perlu dikirim hari ini
$tanggal_hari_ini = date('Y-m-d');
$tanggal_7_hari = date('Y-m-d', strtotime('-7 days'));

// Query untuk mendapatkan guru yang perlu dikirim notifikasi
$query = "
    SELECT DISTINCT a.nama, u.no_whatsapp, 
           GROUP_CONCAT(
               CONCAT(
                   a.id, '|',
                   a.mata_pelajaran, '|',
                   a.pertemuan_ke, '|',
                   a.tanggal, '|',
                   LEFT(REPLACE(a.materi, '\n', ' '), 50)
               ) 
               ORDER BY a.tanggal DESC
               SEPARATOR '||'
           ) as data_list,
           COUNT(*) as jumlah
    FROM administrasi a
    LEFT JOIN users u ON u.nama_lengkap = a.nama
    WHERE a.tanggal >= '$tanggal_7_hari'
    AND (a.foto IS NULL OR a.foto = '')
    AND u.no_whatsapp IS NOT NULL
    AND u.no_whatsapp != ''
    AND u.no_whatsapp REGEXP '^[0-9]{10,15}$'
    GROUP BY a.nama, u.no_whatsapp
    HAVING jumlah > 0
    ORDER BY a.nama ASC
";

$result = $conn->query($query);

if (!$result) {
    writeLog("ERROR: " . $conn->error, $log_file);
    $conn->close();
    exit(1);
}

$total_notifikasi = 0;
$notifikasi_berhasil = 0;

if ($result->num_rows > 0) {
    writeLog("Ditemukan {$result->num_rows} guru yang perlu dikirim notifikasi", $log_file);
    
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
            if (count($parts) >= 5) {
                $data_list[] = [
                    'id' => $parts[0],
                    'mata_pelajaran' => $parts[1],
                    'pertemuan_ke' => $parts[2],
                    'tanggal' => $parts[3],
                    'materi' => $parts[4] ?? ''
                ];
            }
        }
        
        // Generate pesan WhatsApp
        $message = "Halo *" . $nama . "*,\n\n";
        $message .= "📢 *PEMBERITAHUAN UPLOAD ADMINISTRASI*\n";
        $message .= "SMK ITIKURIH HIBARNA\n\n";
        $message .= "Anda memiliki *$jumlah* data administrasi yang *belum diupload*:\n\n";
        
        $no = 1;
        foreach (array_slice($data_list, 0, 10) as $item) { // Maksimal 10 data
            $message .= $no . ". *" . $item['mata_pelajaran'] . "*\n";
            $message .= "   Pertemuan ke-" . $item['pertemuan_ke'] . "\n";
            $message .= "   Tanggal: " . date('d/m/Y', strtotime($item['tanggal'])) . "\n";
            if (!empty($item['materi'])) {
                $materi = str_replace(['*', '_', '~', '`'], '', $item['materi']); // Hapus karakter khusus
                $message .= "   Materi: " . substr($materi, 0, 50) . "\n";
            }
            $message .= "\n";
            $no++;
        }
        
        if ($jumlah > 10) {
            $message .= "... dan " . ($jumlah - 10) . " data lainnya\n\n";
        }
        
        $message .= "⏰ *Segera upload sebelum waktu terlambat!*\n\n";
        $message .= "Terima kasih 🙏\n";
        $message .= "_Pesan otomatis dari Sistem Administrasi Guru_";
        
        // Generate WhatsApp URL
        $no_whatsapp_clean = preg_replace('/[^0-9]/', '', $no_whatsapp);
        $message_encoded = urlencode($message);
        $whatsapp_url = "https://wa.me/$no_whatsapp_clean?text=$message_encoded";
        
        // Simpan ke queue file
        $queue_file = $log_dir . '/whatsapp_queue_' . date('Y-m-d') . '.txt';
        $queue_data = date('Y-m-d H:i:s') . "|$nama|$no_whatsapp_clean|$whatsapp_url|$jumlah\n";
        file_put_contents($queue_file, $queue_data, FILE_APPEND);
        
        writeLog("✓ Queue: $nama ($no_whatsapp_clean) - $jumlah data", $log_file);
        $total_notifikasi++;
        $notifikasi_berhasil++;
        
        // Jika menggunakan WhatsApp API, uncomment baris di bawah
        // $result_api = sendWhatsAppAPI($no_whatsapp_clean, $message);
        // if ($result_api['success']) {
        //     writeLog("✓ API: $nama - Berhasil dikirim", $log_file);
        // } else {
        //     writeLog("✗ API: $nama - Gagal: " . $result_api['message'], $log_file);
        // }
    }
} else {
    writeLog("Tidak ada data yang perlu dikirim notifikasi", $log_file);
}

$conn->close();

writeLog("=== CRON: Selesai - Total: $total_notifikasi notifikasi ===", $log_file);
writeLog("", $log_file);

// Output HTML footer untuk browser
if (php_sapi_name() !== 'cli') {
    echo "</pre>";
    echo "<p><strong>Total Notifikasi:</strong> $total_notifikasi</p>";
    echo "<p><strong>Berhasil:</strong> $notifikasi_berhasil</p>";
    
    if ($total_notifikasi > 0) {
        echo "<p class='success'>✓ Notifikasi berhasil ditambahkan ke queue!</p>";
        echo "<p>File queue: <code>logs/whatsapp_queue_" . date('Y-m-d') . ".txt</code></p>";
    } else {
        echo "<p>Tidak ada data yang perlu dikirim notifikasi saat ini.</p>";
    }
    
    echo "<p><a href='index.php' class='btn'>Kembali ke Dashboard</a></p>";
    echo "<p><a href='notifikasi.php' class='btn' style='background: #25D366;'>Lihat Halaman Notifikasi</a></p>";
    echo "</div></body></html>";
    ob_end_flush();
}

exit(0);
?>


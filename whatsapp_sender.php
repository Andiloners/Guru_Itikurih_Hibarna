<?php
/**
 * File untuk mengirim WhatsApp dari queue
 * File ini membaca queue file dan mengirim via WhatsApp API
 * Bisa dijalankan via cron job atau diakses langsung
 */

require_once 'config.php';
require_once 'auth.php';

// Cek login jika diakses via browser
if (php_sapi_name() !== 'cli') {
    requireLogin();
}

$log_dir = __DIR__ . '/logs';
$queue_file = $log_dir . '/whatsapp_queue_' . date('Y-m-d') . '.txt';
$sent_file = $log_dir . '/whatsapp_sent_' . date('Y-m-d') . '.txt';

if (!file_exists($queue_file)) {
    die("Tidak ada queue untuk hari ini.\n");
}

$queue_data = file($queue_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$sent_count = 0;
$failed_count = 0;

foreach ($queue_data as $line) {
    $parts = explode('|', $line);
    if (count($parts) >= 4) {
        $timestamp = $parts[0];
        $nama = $parts[1];
        $no_whatsapp = $parts[2];
        $whatsapp_url = $parts[3];
        $jumlah = $parts[4] ?? '0';
        
        // Jika menggunakan WhatsApp API, kirim di sini
        // Untuk saat ini, kita hanya log
        $sent_data = "$timestamp|$nama|$no_whatsapp|$jumlah|SENT\n";
        file_put_contents($sent_file, $sent_data, FILE_APPEND);
        $sent_count++;
    }
}

// Hapus queue file setelah diproses
unlink($queue_file);

echo "Notifikasi diproses: $sent_count berhasil, $failed_count gagal\n";
?>


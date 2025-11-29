<?php
/**
 * File untuk mengirim WhatsApp menggunakan API
 * Menggunakan WhatsApp Business API atau service pihak ketiga
 * 
 * Opsi yang bisa digunakan:
 * 1. WhatsApp Business API (resmi, berbayar)
 * 2. Twilio WhatsApp API (berbayar)
 * 3. WhatsApp Web API (tidak resmi, bisa di-banned)
 * 
 * Untuk saat ini, file ini menggunakan pendekatan file-based
 * yang bisa diintegrasikan dengan service WhatsApp API
 */

require_once 'config.php';

// Konfigurasi WhatsApp API (sesuaikan dengan service yang digunakan)
define('WHATSAPP_API_URL', ''); // URL API WhatsApp
define('WHATSAPP_API_KEY', ''); // API Key
define('WHATSAPP_API_TOKEN', ''); // API Token

/**
 * Fungsi untuk mengirim pesan WhatsApp via API
 * 
 * @param string $phone_number Nomor WhatsApp (format: 6281234567890)
 * @param string $message Pesan yang akan dikirim
 * @return array Hasil pengiriman
 */
function sendWhatsAppAPI($phone_number, $message) {
    // Bersihkan nomor
    $phone_number = preg_replace('/[^0-9]/', '', $phone_number);
    
    // Jika menggunakan WhatsApp Business API
    if (!empty(WHATSAPP_API_URL) && !empty(WHATSAPP_API_KEY)) {
        $data = [
            'phone' => $phone_number,
            'message' => $message
        ];
        
        $ch = curl_init(WHATSAPP_API_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . WHATSAPP_API_KEY
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200) {
            return ['success' => true, 'message' => 'Pesan berhasil dikirim'];
        } else {
            return ['success' => false, 'message' => 'Gagal mengirim pesan: ' . $response];
        }
    }
    
    // Fallback: simpan ke file untuk dikirim manual atau via service lain
    $log_file = 'logs/whatsapp_queue_' . date('Y-m-d') . '.txt';
    $log_data = date('Y-m-d H:i:s') . "|$phone_number|$message\n";
    file_put_contents($log_file, $log_data, FILE_APPEND);
    
    return ['success' => true, 'message' => 'Pesan ditambahkan ke queue'];
}

// Contoh penggunaan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send'])) {
    $phone = $_POST['phone'] ?? '';
    $message = $_POST['message'] ?? '';
    
    if (!empty($phone) && !empty($message)) {
        $result = sendWhatsAppAPI($phone, $message);
        echo json_encode($result);
    } else {
        echo json_encode(['success' => false, 'message' => 'Phone dan message harus diisi']);
    }
    exit;
}
?>


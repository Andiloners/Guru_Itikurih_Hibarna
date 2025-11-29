<?php
/**
 * File untuk setup cron job otomatis
 * Halaman ini membantu user setup cron job untuk auto notifikasi
 */

require_once 'config.php';
require_once 'auth.php';

// Cek apakah user adalah admin
requireAdmin();

$log_dir = __DIR__ . '/logs';
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0777, true);
}

// Cek apakah cron sudah berjalan
$cron_log = $log_dir . '/cron_notifikasi_' . date('Y-m-d') . '.log';
$cron_running = file_exists($cron_log) && (time() - filemtime($cron_log)) < 3600; // Dalam 1 jam terakhir
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Auto Notifikasi - Administrasi Guru</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header class="dashboard-header">
            <div class="header-content">
                <div class="header-left">
                    <div class="logo-container">
                        <?php 
                        $logo_path = 'assets/images/logo.png';
                        if (!file_exists($logo_path)) {
                            $logo_path = 'assets/images/logo.jpg';
                        }
                        if (file_exists($logo_path)):
                        ?>
                            <div class="logo-icon">
                                <img src="<?php echo $logo_path; ?>" alt="Logo SMK ITIKURIH HIBARNA" class="logo-image">
                            </div>
                        <?php else: ?>
                            <div class="logo-icon">
                                <div class="logo-fallback">📚</div>
                            </div>
                        <?php endif; ?>
                        <div class="logo-text">
                            <h1>⚙️ Setup Auto Notifikasi</h1>
                            <p class="welcome-text">Konfigurasi pengiriman otomatis</p>
                        </div>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="notifikasi.php" class="btn btn-secondary">
                        <span class="btn-icon">←</span> Kembali
                    </a>
                </div>
            </div>
        </header>

        <div class="content">
            <div class="info-box">
                <div class="info-icon">ℹ️</div>
                <div class="info-content">
                    <h3>Cara Setup Auto Notifikasi</h3>
                    <p>Sistem akan otomatis mengirim notifikasi WhatsApp setiap 30 menit untuk data yang belum diupload.</p>
                </div>
            </div>

            <!-- Status Cron -->
            <div class="widget">
                <div class="widget-header">
                    <h3>📊 Status Auto Notifikasi</h3>
                </div>
                <div class="widget-body">
                    <?php if ($cron_running): ?>
                        <div class="alert alert-success">
                            <span class="alert-icon">✓</span>
                            Auto notifikasi aktif! Terakhir berjalan: <?php echo date('d/m/Y H:i:s', filemtime($cron_log)); ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-error">
                            <span class="alert-icon">⚠</span>
                            Auto notifikasi belum aktif. Silakan setup cron job sesuai instruksi di bawah.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Instruksi Setup -->
            <div class="widget">
                <div class="widget-header">
                    <h3>📋 Instruksi Setup</h3>
                </div>
                <div class="widget-body">
                    <h4>Untuk Windows (Task Scheduler):</h4>
                    <ol style="line-height: 2;">
                        <li>Buka <strong>Task Scheduler</strong> (Cari di Start Menu)</li>
                        <li>Klik <strong>"Create Basic Task"</strong></li>
                        <li>Nama: <code>Auto Notifikasi WhatsApp</code></li>
                        <li>Trigger: <strong>Daily</strong> atau <strong>Repeat task every: 30 minutes</strong></li>
                        <li>Action: <strong>Start a program</strong></li>
                        <li>Program: <code><?php echo str_replace('/', '\\', str_replace('htdocs', 'php', __DIR__)) . '\\php.exe'; ?></code></li>
                        <li>Arguments: <code><?php echo __DIR__ . '\\cron_notifikasi.php'; ?></code></li>
                        <li>Start in: <code><?php echo __DIR__; ?></code></li>
                        <li>Klik <strong>Finish</strong></li>
                    </ol>

                    <h4 style="margin-top: 30px;">Untuk Linux/Mac (Cron Job):</h4>
                    <ol style="line-height: 2;">
                        <li>Buka terminal</li>
                        <li>Jalankan: <code>crontab -e</code></li>
                        <li>Tambahkan baris berikut:</li>
                    </ol>
                    <div style="background: #f5f5f5; padding: 15px; border-radius: 8px; margin: 15px 0;">
                        <code style="font-size: 13px;">
                            */30 * * * * /usr/bin/php <?php echo __DIR__; ?>/cron_notifikasi.php >> <?php echo $log_dir; ?>/cron.log 2>&1
                        </code>
                    </div>
                    <p><small>Ini akan menjalankan setiap 30 menit</small></p>

                    <h4 style="margin-top: 30px;">Test Manual:</h4>
                    <p>Untuk test, Anda bisa:</p>
                    <ol style="line-height: 2;">
                        <li>Akses via browser:
                            <div style="background: #f5f5f5; padding: 10px; border-radius: 8px; margin: 10px 0;">
                                <code>
                                    <a href="cron_notifikasi.php" target="_blank"><?php echo 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/cron_notifikasi.php'; ?></a>
                                </code>
                            </div>
                        </li>
                        <li>Atau jalankan file batch (Windows):
                            <div style="background: #f5f5f5; padding: 10px; border-radius: 8px; margin: 10px 0;">
                                <code>auto_notifikasi.bat</code>
                            </div>
                        </li>
                    </ol>
                    
                    <div style="margin-top: 20px;">
                        <a href="cron_notifikasi.php" class="btn btn-primary" target="_blank">
                            <span class="btn-icon">▶️</span> Test Sekarang
                        </a>
                    </div>
                </div>
            </div>

            <!-- Log Viewer -->
            <?php if (file_exists($cron_log)): ?>
            <div class="widget">
                <div class="widget-header">
                    <h3>📝 Log Terakhir</h3>
                </div>
                <div class="widget-body">
                    <div style="background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 8px; max-height: 300px; overflow-y: auto; font-family: 'Courier New', monospace; font-size: 12px;">
                        <?php 
                        $log_content = file_get_contents($cron_log);
                        echo nl2br(htmlspecialchars(substr($log_content, -2000))); // 2000 karakter terakhir
                        ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

